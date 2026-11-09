<?php

namespace App\Filament\Calendar;

use App\Enums\BillingUnit;
use App\Filament\Resources\ReservationResource;
use App\Filament\Resources\SpaceResource;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

/**
 * Calendário visual das reservas. Fica fora de App\Filament\Widgets para não
 * entrar no dashboard; é renderizado pela página "Calendário".
 */
class ReservationCalendarWidget extends FullCalendarWidget
{
    /** Unidade filtrada (null = todas). */
    public ?int $unit = null;

    protected function headerActions(): array
    {
        return [];
    }

    protected function modalActions(): array
    {
        return [];
    }

    public function config(): array
    {
        return [
            'initialView' => 'timeGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'displayEventEnd' => true,
            // Diárias, contratos mensais e lotação do coworking ficam na faixa "dia inteiro"
            'allDaySlot' => true,
            'allDayText' => 'Dia todo',
            'eventMaxStack' => 3,
            'slotEventOverlap' => false,
        ];
    }

    public function fetchEvents(array $info): array
    {
        // O FullCalendar manda strings ISO com fuso; converter antes de comparar no banco
        // (no SQLite a comparação seria de texto e perderia eventos do primeiro dia)
        $from = CarbonImmutable::parse($info['start'])->setTimezone(config('app.timezone'));
        $to = CarbonImmutable::parse($info['end'])->setTimezone(config('app.timezone'));

        $reservations = ReservationResource::getEloquentQuery()
            ->blocking()
            ->overlapping($from, $to)
            ->when($this->unit, fn ($q, $id) => $q->inUnit($id))
            ->limit(1500)
            ->get();

        // Coworking (compartilhado) tem dezenas de reservas por dia: vira um evento por dia com a lotação
        [$shared, $exclusive] = $reservations->partition(fn (Reservation $r) => $r->space?->is_shared);

        return [
            ...$exclusive->map(fn (Reservation $r) => [
                'id' => $r->id,
                'title' => $r->space?->name.' · '.strtok($r->user?->name ?? '', ' ').($this->unit ? '' : ' · '.$r->space?->unit?->name),
                ...($r->billing_unit === BillingUnit::Hour
                    ? ['start' => $r->starts_at->toIso8601String(), 'end' => $r->ends_at->toIso8601String()]
                    : ['start' => $r->starts_at->toDateString(), 'end' => $r->ends_at->addDay()->toDateString(), 'allDay' => true]),
                'url' => ReservationResource::getUrl('view', ['record' => $r]),
                'backgroundColor' => $r->billing_unit->hex(),
                'borderColor' => $r->billing_unit->hex(),
                'textColor' => '#FFFFFF',
                'classNames' => $r->status->value === 'pending' ? ['ev-pending'] : [],
            ])->values()->all(),
            ...$this->sharedOccupancy($shared, $from, $to),
        ];
    }

    /** Agrega as reservas de espaços compartilhados em "Coworking X · N/capacidade" por dia. */
    private function sharedOccupancy($reservations, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $events = [];

        foreach ($reservations->groupBy('space_id') as $items) {
            $space = $items->first()->space;
            $unit = $space->unit;

            for ($day = $from->startOfDay(); $day < $to; $day = $day->addDay()) {
                $start = $day->setTime($unit->opens_hour, 0);
                $end = $day->setTime($unit->closes_hour, 0);
                $people = $items->filter(fn (Reservation $r) => $r->starts_at < $end && $r->ends_at > $start)->sum('guests');

                if ($people > 0) {
                    $events[] = [
                        'id' => "shared-{$space->id}-{$day->toDateString()}",
                        'title' => "{$space->name} · {$people}/{$space->capacity} pessoas",
                        'start' => $day->toDateString(),
                        'allDay' => true,
                        'url' => SpaceResource::getUrl('edit', ['record' => $space]),
                        'backgroundColor' => '#C8A15A33',
                        'borderColor' => '#C8A15A',
                        'textColor' => '#7A5A1E',
                    ];
                }
            }
        }

        return $events;
    }
}
