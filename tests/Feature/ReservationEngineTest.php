<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\PaymentMethod;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Exceptions\ReservationException;
use App\Mail\ReservationStatusMail;
use App\Models\Space;
use App\Models\SpaceCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReservationEngineTest extends TestCase
{
    use RefreshDatabase;

    private ReservationService $engine;
    private User $guest;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Segunda-feira, 10h: datas previsíveis
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00:00'));

        $this->engine = app(ReservationService::class);
        $this->guest = User::factory()->create(['role' => UserRole::Guest]);
        $this->unit = Unit::create([
            'name' => 'Unidade Teste', 'city' => 'São Paulo', 'state' => 'SP', 'address' => 'Rua X, 1',
            'description' => 'x', 'opens_hour' => 8, 'closes_hour' => 20,
        ]);
    }

    private function space(BillingUnit $billing, float $price = 100, int $capacity = 10, bool $shared = false, int $min = 1, int $max = 12): Space
    {
        $category = SpaceCategory::create([
            'name' => 'Cat '.$billing->value.($shared ? ' shared' : '').uniqid(), 'billing_unit' => $billing,
            'min_quantity' => $min, 'max_quantity' => $max, 'is_shared' => $shared,
        ]);

        return Space::create([
            'unit_id' => $this->unit->id, 'space_category_id' => $category->id, 'name' => 'Espaço '.uniqid(),
            'description' => 'x', 'capacity' => $capacity, 'price' => $price,
        ])->load('unit', 'category');
    }

    // ── Janela e preço por forma de cobrança ──────────────────

    public function test_hourly_booking_uses_whole_hours_and_charges_per_hour(): void
    {
        $space = $this->space(BillingUnit::Hour, price: 150);

        $r = $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 09:00'), 3, 5);

        $this->assertEquals('2026-11-04 12:00:00', $r->ends_at->toDateTimeString());
        $this->assertEquals(450, $r->total);
        $this->assertSame('3 horas', $r->quantity_label);
    }

    public function test_daily_booking_covers_opening_hours_of_each_day(): void
    {
        $space = $this->space(BillingUnit::Day, price: 200);

        $r = $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-05'), 3, 4);

        $this->assertEquals('2026-11-05 08:00:00', $r->starts_at->toDateTimeString());
        $this->assertEquals('2026-11-07 20:00:00', $r->ends_at->toDateTimeString());
        $this->assertEquals(600, $r->total);
    }

    public function test_monthly_contract_ends_the_day_before_the_same_date_n_months_later(): void
    {
        $space = $this->space(BillingUnit::Month, price: 3000);

        $r = $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-10'), 2, 4);

        $this->assertEquals('2026-11-10 08:00:00', $r->starts_at->toDateTimeString());
        $this->assertEquals('2027-01-09 20:00:00', $r->ends_at->toDateTimeString());
        $this->assertEquals(6000, $r->total);
    }

    // ── Conflito: a regra central ─────────────────────────────

    public function test_exclusive_space_cannot_be_booked_twice_in_overlapping_hours(): void
    {
        $space = $this->space(BillingUnit::Hour);
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 09:00'), 3);

        $this->expectException(ReservationException::class);
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 11:00'), 2);
    }

    public function test_back_to_back_hours_are_allowed(): void
    {
        $space = $this->space(BillingUnit::Hour);
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 09:00'), 2);
        $second = $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 11:00'), 2);

        $this->assertSame(ReservationStatus::Pending, $second->status);
    }

    public function test_monthly_contract_blocks_daily_and_hourly_use_of_the_same_space(): void
    {
        $space = $this->space(BillingUnit::Month);
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-10'), 1);

        // Mesma janela por outra reserva mensal começando no meio do contrato
        $this->expectException(ReservationException::class);
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-25'), 1);
    }

    public function test_shared_space_accepts_reservations_until_capacity(): void
    {
        $space = $this->space(BillingUnit::Day, price: 50, capacity: 5, shared: true);
        $day = CarbonImmutable::parse('2026-11-05');

        $a = $this->engine->book($this->guest, $space, $day, 1, 3);
        $this->engine->book($this->guest, $space, $day, 1, 2);

        $this->assertEquals(150, $a->total); // preço por pessoa: 3 × R$ 50
        $this->assertSame(0, $space->seatsLeftBetween(...app(AvailabilityService::class)->window($space, $day, 1)));

        $this->expectExceptionMessage('Não há vaga');
        $this->engine->book($this->guest, $space, $day, 1, 1);
    }

    public function test_cancelling_frees_the_period(): void
    {
        $space = $this->space(BillingUnit::Day);
        $reservation = $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-05'), 2);

        $this->engine->cancel($reservation, $this->guest);
        $again = $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-05'), 2);

        $this->assertSame(ReservationStatus::Pending, $again->status);
    }

    // ── Regras da categoria e da unidade ──────────────────────

    public function test_category_minimum_duration_is_enforced(): void
    {
        $space = $this->space(BillingUnit::Hour, min: 2);

        $this->expectExceptionMessage('reserve de 2 horas');
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 09:00'), 1);
    }

    public function test_hourly_booking_must_fit_unit_opening_hours(): void
    {
        $space = $this->space(BillingUnit::Hour);

        $this->expectExceptionMessage('Funcionamento da unidade');
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 19:00'), 2); // fecha às 20h
    }

    public function test_capacity_is_enforced(): void
    {
        $space = $this->space(BillingUnit::Hour, capacity: 6);

        $this->expectExceptionMessage('Capacidade máxima');
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-04 09:00'), 1, 7);
    }

    public function test_inactive_unit_makes_spaces_unbookable(): void
    {
        $space = $this->space(BillingUnit::Hour);
        $this->unit->update(['is_active' => false]);

        $this->expectExceptionMessage('não está disponível');
        $this->engine->book($this->guest, $space->fresh(['unit', 'category']), CarbonImmutable::parse('2026-11-04 09:00'), 1);
    }

    public function test_hour_slots_reflect_existing_bookings(): void
    {
        $space = $this->space(BillingUnit::Hour);
        $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-03 10:00'), 2);

        $slots = collect(app(AvailabilityService::class)->hourSlots($space, CarbonImmutable::parse('2026-11-03')))->keyBy('time');

        $this->assertCount(12, $slots); // 08h às 20h
        $this->assertTrue($slots['09:00']['available']);
        $this->assertSame('busy', $slots['10:00']['reason']);
        $this->assertSame('busy', $slots['11:00']['reason']);
        $this->assertTrue($slots['12:00']['available']);
    }

    // ── Máquina de estados ─────────────────────────────────────

    public function test_payment_confirms_reservation_and_sends_email(): void
    {
        Mail::fake();
        $reservation = $this->engine->book($this->guest, $this->space(BillingUnit::Day), CarbonImmutable::parse('2026-11-05'), 1);

        $this->engine->pay($reservation, PaymentMethod::Pix);

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame('paid', $reservation->payment->fresh()->status->value);
        Mail::assertSent(ReservationStatusMail::class, fn ($mail) => $mail->hasTo($this->guest->email));
    }

    public function test_invalid_status_transition_is_blocked(): void
    {
        $reservation = $this->engine->book($this->guest, $this->space(BillingUnit::Day), CarbonImmutable::parse('2026-11-05'), 1);

        $this->expectException(ReservationException::class);
        $this->engine->checkIn($reservation); // pendente não pode fazer check-in
    }

    public function test_guest_cannot_cancel_less_than_24h_before_but_staff_can(): void
    {
        $space = $this->space(BillingUnit::Hour);
        $reservation = $this->engine->book($this->guest, $space, CarbonImmutable::parse('2026-11-02 15:00'), 1); // hoje às 15h
        $this->engine->confirm($reservation);

        try {
            $this->engine->cancel($reservation, $this->guest);
            $this->fail('Cliente não deveria conseguir cancelar.');
        } catch (ReservationException) {
        }

        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $this->engine->cancel($reservation, $staff);

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
    }
}
