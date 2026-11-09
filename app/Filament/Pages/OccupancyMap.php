<?php

namespace App\Filament\Pages;

use App\Enums\BillingUnit;
use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\SpaceResource;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

/**
 * Mapa de ocupação da recepção: situação de cada espaço de cada unidade em uma data.
 */
class OccupancyMap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Operação';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationLabel = 'Mapa de ocupação';
    protected static ?string $title = 'Mapa de ocupação';
    protected static ?string $slug = 'occupancy';
    protected static string $view = 'filament.pages.occupancy-map';

    public string $date;

    public ?int $unit = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    public function mount(): void
    {
        $this->date = today()->toDateString();
    }

    public function shiftDay(int $days): void
    {
        $this->date = CarbonImmutable::parse($this->date)->addDays($days)->toDateString();
    }

    public function today(): void
    {
        $this->date = today()->toDateString();
    }

    public function setUnit(?int $unit): void
    {
        $this->unit = $unit;
    }

    protected function getViewData(): array
    {
        $day = CarbonImmutable::parse($this->date);

        // Reservas ativas que tocam o dia, de uma vez só
        $reservations = Reservation::blocking()
            ->where('starts_at', '<', $day->endOfDay())->where('ends_at', '>', $day->startOfDay())
            ->when($this->unit, fn ($q, $id) => $q->inUnit($id))
            ->with('user')->orderBy('starts_at')->get()->groupBy('space_id');

        $spaces = Space::with('unit', 'category')
            ->when($this->unit, fn ($q, $id) => $q->where('unit_id', $id))
            ->whereHas('unit', fn ($q) => $q->where('is_active', true))
            ->join('space_categories', 'space_categories.id', '=', 'spaces.space_category_id')
            ->orderBy('space_categories.sort_order')->orderBy('spaces.name')
            ->select('spaces.*')
            ->get()
            ->map(fn (Space $space) => $this->describe($space, $reservations->get($space->id, collect()), $day));

        return [
            'day' => $day,
            'units' => Unit::active()->orderBy('city')->orderBy('name')->get(['id', 'name']),
            'groups' => $spaces->groupBy(fn ($item) => $item['space']->unit->name),
            'summary' => $spaces->countBy('state'),
            'states' => [
                'free' => ['Livre', 'bg-emerald-50 ring-emerald-200 text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30', 'bg-emerald-500'],
                'partial' => ['Reservas no dia', 'bg-sky-50 ring-sky-300 text-sky-900 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/30', 'bg-sky-500'],
                'occupied' => ['Ocupado', 'bg-slate-800 ring-slate-700 text-white dark:bg-slate-700', 'bg-slate-800'],
                'full' => ['Lotado', 'bg-amber-50 ring-amber-300 text-amber-900 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30', 'bg-amber-500'],
                'inactive' => ['Inativo', 'bg-gray-100 ring-gray-200 text-gray-500 dark:bg-white/5 dark:text-gray-400 dark:ring-white/10', 'bg-gray-400'],
            ],
        ];
    }

    /** Situação de um espaço no dia + a linha de detalhe mostrada no cartão. */
    private function describe(Space $space, $list, CarbonImmutable $day): array
    {
        $open = $day->setTime($space->unit->opens_hour, 0);
        $close = $day->setTime($space->unit->closes_hour, 0);
        $first = $list->first();
        $url = $first ? ReservationResource::getUrl('view', ['record' => $first]) : SpaceResource::getUrl('edit', ['record' => $space]);

        if (! $space->is_active) {
            return ['space' => $space, 'state' => 'inactive', 'detail' => 'Não aceita reservas', 'sub' => null, 'url' => $url];
        }

        if ($space->is_shared) {
            $people = (int) $list->filter(fn ($r) => $r->starts_at < $close && $r->ends_at > $open)->sum('guests');

            return [
                'space' => $space,
                'state' => $people >= $space->capacity ? 'full' : ($people > 0 ? 'partial' : 'free'),
                'detail' => "{$people} de {$space->capacity} vagas ocupadas",
                'sub' => null,
                'percent' => (int) round(100 * $people / max(1, $space->capacity)),
                'url' => SpaceResource::getUrl('edit', ['record' => $space]),
            ];
        }

        // Diária ou contrato mensal cobrindo o dia: espaço ocupado o dia inteiro
        $whole = $list->first(fn ($r) => $r->billing_unit !== BillingUnit::Hour);
        if ($whole) {
            return [
                'space' => $space,
                'state' => 'occupied',
                'detail' => $whole->user?->name,
                'sub' => $whole->billing_unit === BillingUnit::Month ? 'Contrato até '.$whole->ends_at->format('d/m/Y') : 'Dia inteiro',
                'url' => ReservationResource::getUrl('view', ['record' => $whole]),
            ];
        }

        if ($list->isEmpty()) {
            return ['space' => $space, 'state' => 'free', 'detail' => 'Agenda livre', 'sub' => null, 'url' => $url];
        }

        $hours = $list->sum(fn ($r) => $r->starts_at->diffInHours($r->ends_at));
        $next = $list->first(fn ($r) => $r->ends_at->isFuture()) ?? $list->last();

        return [
            'space' => $space,
            'state' => $hours >= ($space->unit->closes_hour - $space->unit->opens_hour) ? 'occupied' : 'partial',
            'detail' => $hours.'h reservadas · '.$list->count().' '.($list->count() === 1 ? 'reserva' : 'reservas'),
            'sub' => ($next->ends_at->isFuture() ? 'Próxima: ' : 'Última: ').$next->starts_at->format('H:i').'–'.$next->ends_at->format('H:i'),
            'url' => ReservationResource::getUrl('view', ['record' => $next]),
        ];
    }
}
