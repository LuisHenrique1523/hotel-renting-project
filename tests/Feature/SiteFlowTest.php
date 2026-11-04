<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\User;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00:00')); // segunda-feira
        $this->seed(CatalogSeeder::class);
    }

    private function guest(): User
    {
        return User::factory()->create(['role' => UserRole::Guest]);
    }

    public function test_public_pages_render(): void
    {
        $type = RoomType::first();
        $service = Service::first();

        foreach (['/', '/quartos', "/quartos/{$type->slug}", '/spa', "/spa/{$service->slug}", '/salas', '/salas/sala-executiva-atlantico', '/login', '/register'] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_visitor_must_login_before_booking(): void
    {
        $this->post('/quartos/standard-jardim/reservar', [])->assertRedirect('/login');
    }

    public function test_guest_books_a_room_pays_and_sees_voucher(): void
    {
        $user = $this->guest();

        $response = $this->actingAs($user)->post('/quartos/superior-vista-mar/reservar', [
            'check_in' => '2026-11-06', 'check_out' => '2026-11-08', 'guests' => 2,
        ]);

        $reservation = Reservation::latest('id')->first();
        $response->assertRedirect(route('reservations.checkout', $reservation));
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertEquals(529 * 1.2 * 2, (float) $reservation->total); // sexta e sábado com acréscimo

        $this->actingAs($user)->get(route('reservations.checkout', $reservation))->assertOk()->assertSee($reservation->code);

        $this->actingAs($user)->post(route('reservations.pay', $reservation), ['method' => 'pix'])
            ->assertRedirect(route('reservations.show', $reservation));

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->actingAs($user)->get(route('reservations.show', $reservation))->assertOk()->assertSee('Confirmada');
        $this->actingAs($user)->get('/minhas-reservas')->assertOk()->assertSee($reservation->code);
    }

    public function test_validation_rejects_checkout_before_checkin(): void
    {
        $this->actingAs($this->guest())
            ->from('/quartos/standard-jardim')
            ->post('/quartos/standard-jardim/reservar', ['check_in' => '2026-11-10', 'check_out' => '2026-11-09', 'guests' => 1])
            ->assertSessionHasErrors('check_out');
    }

    public function test_business_rule_violation_returns_friendly_message(): void
    {
        $this->actingAs($this->guest())
            ->from('/quartos/standard-jardim')
            ->post('/quartos/standard-jardim/reservar', ['check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'guests' => 4])
            ->assertRedirect('/quartos/standard-jardim')
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'acomoda até 2'));
    }

    public function test_guest_cannot_see_or_pay_someone_elses_reservation(): void
    {
        $owner = $this->guest();
        $this->actingAs($owner)->post('/quartos/standard-jardim/reservar', ['check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'guests' => 1]);
        $reservation = Reservation::latest('id')->first();

        $intruder = $this->guest();
        $this->actingAs($intruder)->get(route('reservations.show', $reservation))->assertForbidden();
        $this->actingAs($intruder)->post(route('reservations.pay', $reservation), ['method' => 'pix'])->assertForbidden();
        $this->actingAs($intruder)->post(route('reservations.cancel', $reservation))->assertForbidden();
    }

    public function test_availability_endpoint_marks_booked_slots(): void
    {
        $service = Service::where('slug', 'massagem-relaxante')->first();
        $pro = Professional::whereHas('user', fn ($q) => $q->where('email', 'spa@aurora.test'))->first(); // terça 09–13 / 14–19

        $this->actingAs($this->guest())->post("/spa/{$service->slug}/reservar", [
            'professional_id' => $pro->id, 'starts_at' => '2026-11-03T10:00:00-03:00',
        ])->assertRedirect();

        $slots = collect($this->getJson("/disponibilidade/spa/{$service->slug}?professional={$pro->id}&date=2026-11-03")
            ->assertOk()->json('slots'))->keyBy('time');

        $this->assertFalse($slots['10:00']['available']);
        $this->assertSame('busy', $slots['10:00']['reason']);
        $this->assertTrue($slots['11:00']['available']);
    }

    // Um teste por papel: o AuthenticateSession do painel invalida a sessão ao trocar de usuário no meio do teste

    public function test_guest_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->guest())->get('/admin')->assertForbidden();
    }

    public function test_staff_operates_hotel_but_cannot_manage_users(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);

        $this->actingAs($staff)->get('/admin')->assertOk();
        $this->actingAs($staff)->get('/admin/reservations')->assertOk();
        $this->actingAs($staff)->get('/admin/calendar')->assertOk();
        $this->actingAs($staff)->get('/admin/room-map')->assertOk();
        $this->actingAs($staff)->get('/admin/users')->assertForbidden(); // só admin
    }

    public function test_professional_sees_only_own_agenda(): void
    {
        $professional = User::where('email', 'spa@aurora.test')->first();

        $this->actingAs($professional)->get('/admin/reservations')->assertOk();
        $this->actingAs($professional)->get('/admin/room-types')->assertForbidden();
        $this->actingAs($professional)->get('/admin/room-map')->assertForbidden();
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/reservations/create')->assertOk();
    }

    public function test_housekeeping_expires_unpaid_reservations(): void
    {
        $user = $this->guest();
        $this->actingAs($user)->post('/quartos/standard-jardim/reservar', ['check_in' => '2026-11-10', 'check_out' => '2026-11-12', 'guests' => 1]);
        $reservation = Reservation::latest('id')->first();

        $this->travel(31)->minutes();
        $this->artisan('reservations:housekeeping')->assertSuccessful();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
    }
}
