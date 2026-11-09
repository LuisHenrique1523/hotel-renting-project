<?php

namespace Tests\Feature;

use App\Enums\BillingUnit;
use App\Enums\UserRole;
use App\Filament\Calendar\ReservationCalendarWidget;
use App\Filament\Pages\OccupancyMap;
use App\Filament\Resources\ReservationResource\Pages\CreateReservation;
use App\Filament\Resources\SpaceCategoryResource\Pages\ManageSpaceCategories;
use App\Filament\Resources\SpaceResource\Pages\CreateSpace;
use App\Filament\Resources\UnitResource\Pages\CreateUnit;
use App\Filament\Resources\UnitResource\Pages\ListUnits;
use App\Filament\Widgets;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\SpaceCategory;
use App\Models\Unit;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** CRUD dos cadastros e operação de balcão pelo painel Filament. */
class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(CarbonImmutable::parse('2026-11-02 10:00:00'));
        $this->seed(CatalogSeeder::class);

        $this->client = User::factory()->create(['role' => UserRole::Guest]);
        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]));
    }

    public function test_admin_creates_category_unit_and_space(): void
    {
        Livewire::test(ManageSpaceCategories::class)
            ->callAction('create', [
                'name' => 'Estúdio de podcast', 'slug' => 'estudio-de-podcast', 'billing_unit' => BillingUnit::Hour->value,
                'min_quantity' => 1, 'max_quantity' => 4, 'is_active' => true,
            ])
            ->assertHasNoActionErrors();

        Livewire::test(CreateUnit::class)
            ->fillForm([
                'name' => 'Aurora Batel', 'slug' => 'aurora-batel', 'description' => 'Nova unidade em Curitiba',
                'address' => 'Av. do Batel, 1868', 'city' => 'Curitiba', 'state' => 'PR', 'opens_hour' => 8, 'closes_hour' => 20,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        Livewire::test(CreateSpace::class)
            ->fillForm([
                'unit_id' => Unit::where('slug', 'aurora-batel')->value('id'),
                'space_category_id' => SpaceCategory::where('slug', 'estudio-de-podcast')->value('id'),
                'name' => 'Estúdio Batel', 'slug' => 'estudio-batel', 'description' => 'Estúdio com isolamento acústico',
                'capacity' => 4, 'price' => 120, 'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/espacos/estudio-batel')->assertOk()->assertSee('Aurora Batel');
    }

    public function test_unit_with_spaces_cannot_be_deleted(): void
    {
        $unit = Unit::where('slug', 'aurora-paulista')->first();

        Livewire::test(ListUnits::class)->callTableAction('delete', $unit);

        $this->assertModelExists($unit);
    }

    public function test_desk_booking_goes_through_the_same_engine(): void
    {
        $space = Space::where('slug', 'sala-trianon')->first();

        Livewire::test(CreateReservation::class)
            ->fillForm([
                'user_id' => $this->client->id, 'unit_id' => $space->unit_id, 'space_id' => $space->id,
                'date' => '2026-11-04', 'start_hour' => 9, 'quantity' => 2, 'guests' => 3, 'confirm_now' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $reservation = Reservation::latest('id')->first();
        $this->assertSame('confirmed', $reservation->status->value);
        $this->assertEquals(150, $reservation->total);

        // Mesmo horário de novo: o motor recusa e nada é criado
        Livewire::test(CreateReservation::class)
            ->fillForm([
                'user_id' => $this->client->id, 'unit_id' => $space->unit_id, 'space_id' => $space->id,
                'date' => '2026-11-04', 'start_hour' => 10, 'quantity' => 1, 'guests' => 2,
            ])
            ->call('create');

        $this->assertSame(1, Reservation::count());
    }

    public function test_dashboard_widgets_calendar_and_occupancy_map_render(): void
    {
        $engine = app(ReservationService::class);
        $engine->book($this->client, Space::where('slug', 'sala-trianon')->first(), CarbonImmutable::parse('2026-11-02 14:00'), 2);
        $engine->book($this->client, Space::where('slug', 'coworking-paulista')->first(), CarbonImmutable::parse('2026-11-02'), 1, 3);
        $engine->book($this->client, Space::where('slug', 'escritorio-1204')->first(), CarbonImmutable::parse('2026-11-02'), 3, 4);

        foreach ([Widgets\StatsOverview::class, Widgets\ReservationsChart::class, Widgets\RevenueByUnitChart::class, Widgets\TodayAgenda::class] as $widget) {
            Livewire::test($widget)->assertOk();
        }

        $events = collect((new ReservationCalendarWidget)->fetchEvents(['start' => '2026-11-02T00:00:00', 'end' => '2026-11-09T00:00:00']));
        $this->assertTrue($events->contains(fn ($e) => str_contains($e['title'], 'Sala Trianon') && ! ($e['allDay'] ?? false)));
        $this->assertTrue($events->contains(fn ($e) => str_contains($e['title'], 'Coworking Paulista · 3/60')));
        $this->assertTrue($events->contains(fn ($e) => str_contains($e['title'], 'Escritório 1204') && ($e['allDay'] ?? false)));

        Livewire::test(OccupancyMap::class)->assertOk()->assertSee('3 de 60 vagas ocupadas')->assertSee('Contrato até');
    }
}
