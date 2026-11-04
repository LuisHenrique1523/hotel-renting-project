<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\ServiceCategory;
use App\Enums\SpaceType;
use App\Enums\UserRole;
use App\Exceptions\ReservationException;
use App\Mail\ReservationStatusMail;
use App\Models\Professional;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\PricingService;
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

    protected function setUp(): void
    {
        parent::setUp();

        // Segunda-feira, 10h: datas previsíveis para diárias e expediente
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00:00'));

        $this->engine = app(ReservationService::class);
        $this->guest = User::factory()->create(['role' => UserRole::Guest]);
    }

    private function roomType(int $rooms = 1, float $price = 500): RoomType
    {
        $type = RoomType::create([
            'name' => 'Suíte Teste', 'description' => 'x', 'capacity' => 2, 'base_price' => $price,
        ]);

        foreach (range(1, $rooms) as $n) {
            $type->rooms()->create(['number' => (string) (100 + $n), 'floor' => 1]);
        }

        return $type;
    }

    private function professional(): array
    {
        $service = Service::create([
            'name' => 'Massagem', 'category' => ServiceCategory::Spa, 'description' => 'x',
            'duration_min' => 60, 'price' => 280,
        ]);
        $user = User::factory()->create(['role' => UserRole::Professional]);
        $pro = Professional::create(['user_id' => $user->id, 'specialty' => 'Massoterapeuta']);
        $pro->services()->attach($service);
        // Terça-feira, 09h às 13h
        $pro->workingHours()->create(['weekday' => 2, 'start_time' => '09:00', 'end_time' => '13:00']);

        return [$pro, $service];
    }

    // ── Preço ───────────────────────────────────────────────────

    public function test_room_price_adds_weekend_surcharge_on_friday_and_saturday(): void
    {
        $type = $this->roomType(price: 100);

        // Qui → Dom: noites de quinta (100), sexta (120) e sábado (120)
        $quote = app(PricingService::class)->quoteRoom($type, CarbonImmutable::parse('2026-11-05'), CarbonImmutable::parse('2026-11-08'));

        $this->assertSame(3, $quote['nights']);
        $this->assertSame(340.0, $quote['total']);
    }

    // ── Conflito de horários: a regra central ─────────────────

    public function test_same_room_cannot_be_booked_twice_for_overlapping_dates(): void
    {
        $type = $this->roomType(rooms: 1);

        $this->engine->bookRoom($this->guest, $type, now()->addDays(5), now()->addDays(8), 2);

        $this->expectException(ReservationException::class);
        $this->engine->bookRoom($this->guest, $type, now()->addDays(7), now()->addDays(9), 2);
    }

    public function test_back_to_back_stays_are_allowed_because_checkout_is_before_checkin(): void
    {
        $type = $this->roomType(rooms: 1);

        $first = $this->engine->bookRoom($this->guest, $type, now()->addDays(5), now()->addDays(8), 2);
        $second = $this->engine->bookRoom($this->guest, $type, now()->addDays(8), now()->addDays(10), 2);

        $this->assertSame($first->reservable_id, $second->reservable_id);
    }

    public function test_engine_allocates_another_room_of_the_same_type_when_one_is_taken(): void
    {
        $type = $this->roomType(rooms: 2);

        $a = $this->engine->bookRoom($this->guest, $type, now()->addDays(5), now()->addDays(8), 2);
        $b = $this->engine->bookRoom($this->guest, $type, now()->addDays(5), now()->addDays(8), 2);

        $this->assertNotSame($a->reservable_id, $b->reservable_id);
        $this->assertSame(0, app(AvailabilityService::class)->freeRoomsCount($type, now()->addDays(5), now()->addDays(8)));
    }

    public function test_cancelling_frees_the_slot(): void
    {
        $type = $this->roomType(rooms: 1);
        $reservation = $this->engine->bookRoom($this->guest, $type, now()->addDays(5), now()->addDays(8), 2);

        $this->engine->cancel($reservation, $this->guest);
        $again = $this->engine->bookRoom($this->guest, $type, now()->addDays(5), now()->addDays(8), 2);

        $this->assertSame(ReservationStatus::Pending, $again->status);
    }

    public function test_professional_cannot_have_two_appointments_at_the_same_time(): void
    {
        [$pro, $service] = $this->professional();

        $this->engine->bookService($this->guest, $pro, $service, CarbonImmutable::parse('2026-11-03 10:00'));

        $this->expectException(ReservationException::class);
        $this->engine->bookService($this->guest, $pro, $service, CarbonImmutable::parse('2026-11-03 10:30'));
    }

    public function test_professional_booking_outside_working_hours_is_rejected(): void
    {
        [$pro, $service] = $this->professional();

        $this->expectExceptionMessage('Fora do horário');
        $this->engine->bookService($this->guest, $pro, $service, CarbonImmutable::parse('2026-11-03 12:30'));
    }

    public function test_professional_slots_reflect_existing_bookings(): void
    {
        [$pro, $service] = $this->professional();
        $this->engine->bookService($this->guest, $pro, $service, CarbonImmutable::parse('2026-11-03 10:00'));

        $slots = collect(app(AvailabilityService::class)->professionalSlots($pro, $service, CarbonImmutable::parse('2026-11-03')))
            ->keyBy('time');

        $this->assertTrue($slots['09:00']['available']);
        $this->assertSame('busy', $slots['09:30']['reason']); // 09:30–10:30 colide
        $this->assertSame('busy', $slots['10:00']['reason']);
        $this->assertTrue($slots['11:00']['available']);
        $this->assertArrayNotHasKey('12:30', $slots->all()); // não cabe 60min antes das 13h
    }

    public function test_space_is_booked_by_whole_hours_inside_opening_time(): void
    {
        $space = Space::create([
            'name' => 'Sala Teste', 'type' => SpaceType::Meeting, 'description' => 'x', 'capacity' => 10,
            'hourly_price' => 150, 'opens_hour' => 8, 'closes_hour' => 20,
        ]);

        $r = $this->engine->bookSpace($this->guest, $space, CarbonImmutable::parse('2026-11-04 09:00'), CarbonImmutable::parse('2026-11-04 12:00'), 5);
        $this->assertEquals(450, $r->total);

        $this->expectException(ReservationException::class);
        $this->engine->bookSpace($this->guest, $space, CarbonImmutable::parse('2026-11-04 11:00'), CarbonImmutable::parse('2026-11-04 13:00'), 5);
    }

    // ── Máquina de estados ─────────────────────────────────────

    public function test_payment_confirms_reservation_and_sends_email(): void
    {
        Mail::fake();
        $reservation = $this->engine->bookRoom($this->guest, $this->roomType(), now()->addDays(5), now()->addDays(6), 1);

        $this->engine->pay($reservation, \App\Enums\PaymentMethod::Pix);

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->assertSame('paid', $reservation->payment->fresh()->status->value);
        Mail::assertSent(ReservationStatusMail::class, fn ($mail) => $mail->hasTo($this->guest->email));
    }

    public function test_invalid_status_transition_is_blocked(): void
    {
        $reservation = $this->engine->bookRoom($this->guest, $this->roomType(), now()->addDays(5), now()->addDays(6), 1);

        $this->expectException(ReservationException::class);
        $this->engine->checkIn($reservation); // pendente não pode fazer check-in
    }

    public function test_guest_cannot_cancel_less_than_24h_before_but_staff_can(): void
    {
        $type = $this->roomType();
        $reservation = $this->engine->bookRoom($this->guest, $type, today(), today()->addDay(), 1); // hoje às 14h
        $this->engine->confirm($reservation);

        try {
            $this->engine->cancel($reservation, $this->guest);
            $this->fail('Hóspede não deveria conseguir cancelar.');
        } catch (ReservationException) {
        }

        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $this->engine->cancel($reservation, $staff);

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
    }
}
