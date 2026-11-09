<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\ReservationStatus;
use App\Enums\UserRole;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\SpaceCategory;
use App\Models\Unit;
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
        $unit = Unit::first();
        $urls = ['/', '/unidades', "/unidades/{$unit->slug}", '/espacos', '/espacos?cidade=S%C3%A3o+Paulo&categoria=sala-de-reuniao&pessoas=4', '/login', '/register'];

        foreach (Space::all() as $space) {
            $urls[] = "/espacos/{$space->slug}";
        }

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_search_filters_by_city_and_category(): void
    {
        $this->get('/espacos?cidade=Belo+Horizonte&categoria=sala-de-reuniao')
            ->assertOk()
            ->assertSee('Sala Liberdade')
            ->assertDontSee('Sala Trianon');
    }

    public function test_inactive_space_is_hidden_from_site(): void
    {
        Space::where('slug', 'sala-trianon')->update(['is_active' => false]);

        $this->get('/espacos/sala-trianon')->assertNotFound();
        $this->get('/espacos')->assertDontSee('Sala Trianon');
    }

    public function test_visitor_must_login_before_booking(): void
    {
        $this->post('/espacos/sala-trianon/reservar', [])->assertRedirect('/login');
    }

    public function test_guest_books_a_meeting_room_pays_and_sees_voucher(): void
    {
        $user = $this->guest();

        $response = $this->actingAs($user)->post('/espacos/sala-trianon/reservar', [
            'date' => '2026-11-04', 'start_hour' => 9, 'quantity' => 2, 'guests' => 4,
        ]);

        $reservation = Reservation::latest('id')->first();
        $response->assertRedirect(route('reservations.checkout', $reservation));
        $this->assertSame(ReservationStatus::Pending, $reservation->status);
        $this->assertEquals(75 * 2, (float) $reservation->total);

        $this->actingAs($user)->get(route('reservations.checkout', $reservation))->assertOk()->assertSee($reservation->code);

        $this->actingAs($user)->post(route('reservations.pay', $reservation), ['method' => 'pix'])
            ->assertRedirect(route('reservations.show', $reservation));

        $this->assertSame(ReservationStatus::Confirmed, $reservation->fresh()->status);
        $this->actingAs($user)->get(route('reservations.show', $reservation))->assertOk()->assertSee('Confirmada');
        $this->actingAs($user)->get('/minhas-reservas')->assertOk()->assertSee($reservation->code);
    }

    public function test_guest_books_coworking_seats_and_pays_per_person(): void
    {
        $this->actingAs($this->guest())->post('/espacos/coworking-paulista/reservar', [
            'date' => '2026-11-05', 'quantity' => 2, 'guests' => 3,
        ])->assertRedirect();

        $this->assertEquals(49 * 2 * 3, (float) Reservation::latest('id')->first()->total);
    }

    public function test_hourly_booking_requires_start_hour(): void
    {
        $this->actingAs($this->guest())
            ->from('/espacos/sala-trianon')
            ->post('/espacos/sala-trianon/reservar', ['date' => '2026-11-04', 'quantity' => 2, 'guests' => 2])
            ->assertSessionHasErrors('start_hour');
    }

    public function test_business_rule_violation_returns_friendly_message(): void
    {
        $this->actingAs($this->guest())
            ->from('/espacos/sala-trianon')
            ->post('/espacos/sala-trianon/reservar', ['date' => '2026-11-04', 'start_hour' => 9, 'quantity' => 1, 'guests' => 20])
            ->assertRedirect('/espacos/sala-trianon')
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'Capacidade máxima: 6'));
    }

    public function test_guest_cannot_see_or_pay_someone_elses_reservation(): void
    {
        $this->actingAs($this->guest())->post('/espacos/sala-trianon/reservar', ['date' => '2026-11-04', 'start_hour' => 9, 'quantity' => 1, 'guests' => 1]);
        $reservation = Reservation::latest('id')->first();

        $intruder = $this->guest();
        $this->actingAs($intruder)->get(route('reservations.show', $reservation))->assertForbidden();
        $this->actingAs($intruder)->post(route('reservations.pay', $reservation), ['method' => 'pix'])->assertForbidden();
        $this->actingAs($intruder)->post(route('reservations.cancel', $reservation))->assertForbidden();
    }

    public function test_availability_endpoints(): void
    {
        $this->actingAs($this->guest())->post('/espacos/sala-trianon/reservar', ['date' => '2026-11-03', 'start_hour' => 10, 'quantity' => 1, 'guests' => 2]);

        $slots = collect($this->getJson('/disponibilidade/sala-trianon/horarios?date=2026-11-03')->assertOk()->json('slots'))->keyBy('time');
        $this->assertSame('busy', $slots['10:00']['reason']);
        $this->assertTrue($slots['11:00']['available']);

        $this->getJson('/disponibilidade/escritorio-1204/dias')->assertOk()->assertJsonPath('days.0.date', '2026-11-02');

        $this->getJson('/disponibilidade/escritorio-1204/orcamento?date=2026-11-10&quantity=3')
            ->assertOk()->assertJsonPath('available', true)->assertJsonPath('total', 11700);

        // Espaço por dia não tem grade de horas
        $this->getJson('/disponibilidade/coworking-paulista/horarios?date=2026-11-03')->assertNotFound();
    }

    // Um teste por papel: o AuthenticateSession do painel invalida a sessão ao trocar de usuário no meio do teste

    public function test_guest_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->guest())->get('/admin')->assertForbidden();
    }

    public function test_staff_operates_and_manages_catalog_but_not_users(): void
    {
        $staff = User::factory()->create(['role' => UserRole::Staff]);
        $unit = Unit::first();

        foreach (['/admin', '/admin/reservations', '/admin/calendar', '/admin/occupancy', '/admin/units', '/admin/units/create',
            "/admin/units/{$unit->slug}/edit", "/admin/spaces/sala-trianon/edit", '/admin/categories', '/admin/spaces', '/admin/spaces/create'] as $url) {
            $this->actingAs($staff)->get($url)->assertOk();
        }

        $this->actingAs($staff)->get('/admin/users')->assertForbidden(); // só admin
    }

    public function test_admin_can_manage_users_and_create_reservations(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/reservations/create')->assertOk();
    }

    public function test_new_category_unit_and_space_show_up_on_site(): void
    {
        // O mesmo que o admin faz pelo painel: cadastrar unidade → categoria → espaço
        $unit = Unit::create(['name' => 'Aurora Centro Brasília', 'city' => 'Brasília', 'state' => 'df', 'address' => 'SCS Quadra 1', 'description' => 'Nova unidade']);
        $category = SpaceCategory::create(['name' => 'Estúdio de podcast', 'billing_unit' => BillingUnit::Hour, 'min_quantity' => 1, 'max_quantity' => 4]);
        Space::create(['unit_id' => $unit->id, 'space_category_id' => $category->id, 'name' => 'Estúdio Eixo', 'description' => 'x', 'capacity' => 4, 'price' => 120]);

        $this->assertSame('DF', $unit->fresh()->state);
        $this->get('/unidades')->assertSee('Aurora Centro Brasília');
        $this->get('/espacos?categoria=estudio-de-podcast')->assertSee('Estúdio Eixo');
        $this->get('/espacos/estudio-eixo')->assertOk()->assertSee('R$ 120,00');
    }

    public function test_housekeeping_expires_unpaid_reservations(): void
    {
        $this->actingAs($this->guest())->post('/espacos/sala-trianon/reservar', ['date' => '2026-11-04', 'start_hour' => 9, 'quantity' => 1, 'guests' => 1]);
        $reservation = Reservation::latest('id')->first();

        $this->travel(31)->minutes();
        $this->artisan('reservations:housekeeping')->assertSuccessful();

        $this->assertSame(ReservationStatus::Cancelled, $reservation->fresh()->status);
    }
}
