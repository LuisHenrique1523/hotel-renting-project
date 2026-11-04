<?php

namespace App\Filament\Pages;

use App\Enums\RoomStatus;
use App\Filament\Resources\ReservationResource;
use App\Models\Reservation;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Filament\Pages\Page;

/**
 * Mapa de quartos da recepção: situação de cada unidade em uma data.
 */
class RoomMap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'Hospedagem';
    protected static ?int $navigationSort = 0;
    protected static ?string $navigationLabel = 'Mapa de quartos';
    protected static ?string $title = 'Mapa de quartos';
    protected static string $view = 'filament.pages.room-map';

    public string $date;

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

    protected function getViewData(): array
    {
        $day = CarbonImmutable::parse($this->date);
        $night = [$day->setTime(20, 0), $day->setTime(20, 1)];

        // Reservas que tocam o dia: hospedados, chegando ou saindo
        $reservations = Reservation::ofKind('room')->blocking()
            ->where('starts_at', '<', $day->endOfDay())->where('ends_at', '>', $day->startOfDay())
            ->with('user')->get()->groupBy('reservable_id');

        $rooms = Room::with('type')->orderBy('floor')->orderBy('number')->get()->map(function (Room $room) use ($reservations, $day, $night) {
            $list = $reservations->get($room->id, collect());
            $arrival = $list->first(fn ($r) => $r->starts_at->isSameDay($day));
            $departure = $list->first(fn ($r) => $r->ends_at->isSameDay($day));
            $staying = $list->first(fn ($r) => $r->starts_at < $night[1] && $r->ends_at > $night[0]);

            $state = match (true) {
                $room->status === RoomStatus::Maintenance => 'maintenance',
                $arrival && $departure => 'turnover',
                (bool) $arrival => 'arrival',
                (bool) $departure => 'departure',
                (bool) $staying => 'occupied',
                default => 'free',
            };

            $main = $arrival ?? $staying ?? $departure;

            return [
                'room' => $room,
                'state' => $state,
                'guest' => $main?->user?->name,
                'until' => $main?->ends_at,
                'url' => $main ? ReservationResource::getUrl('view', ['record' => $main]) : null,
            ];
        });

        return [
            'day' => $day,
            'floors' => $rooms->groupBy(fn ($r) => $r['room']->floor),
            'summary' => $rooms->countBy('state'),
            'states' => [
                'free' => ['Livre', 'bg-emerald-50 ring-emerald-200 text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200 dark:ring-emerald-500/30', 'bg-emerald-500'],
                'occupied' => ['Ocupado', 'bg-slate-800 ring-slate-700 text-white dark:bg-slate-700', 'bg-slate-800'],
                'arrival' => ['Chegada', 'bg-sky-50 ring-sky-300 text-sky-900 dark:bg-sky-500/10 dark:text-sky-200 dark:ring-sky-500/30', 'bg-sky-500'],
                'departure' => ['Saída', 'bg-amber-50 ring-amber-300 text-amber-900 dark:bg-amber-500/10 dark:text-amber-200 dark:ring-amber-500/30', 'bg-amber-500'],
                'turnover' => ['Saída + chegada', 'bg-violet-50 ring-violet-300 text-violet-900 dark:bg-violet-500/10 dark:text-violet-200 dark:ring-violet-500/30', 'bg-violet-500'],
                'maintenance' => ['Manutenção', 'bg-rose-50 ring-rose-300 text-rose-900 dark:bg-rose-500/10 dark:text-rose-200 dark:ring-rose-500/30', 'bg-rose-500'],
            ],
        ];
    }
}
