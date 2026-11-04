<?php

namespace App\Filament\Calendar;

use App\Filament\Resources\ReservationResource;
use App\Models\Reservation;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

/**
 * Calendário visual das reservas. Fica fora de App\Filament\Widgets para não
 * entrar no dashboard; é renderizado pela página "Calendário".
 */
class ReservationCalendarWidget extends FullCalendarWidget
{
    public string $kind = 'professional';

    private const COLORS = [
        'room' => '#2B3F5C',
        'space' => '#0EA5E9',
        'professional' => '#C8A15A',
    ];

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
            'initialView' => $this->kind === 'room' ? 'dayGridMonth' : 'timeGridWeek',
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listWeek',
            ],
            'displayEventEnd' => true,
            'eventMaxStack' => 3,
            'slotEventOverlap' => false,
        ];
    }

    public function fetchEvents(array $info): array
    {
        return ReservationResource::getEloquentQuery()
            ->blocking()
            ->where('starts_at', '<', $info['end'])
            ->where('ends_at', '>', $info['start'])
            ->when($this->kind !== 'all', fn ($q) => $q->where('reservable_type', $this->kind))
            ->limit(800)
            ->get()
            ->map(fn (Reservation $r) => [
                'id' => $r->id,
                'title' => $this->title($r),
                'start' => $r->starts_at->toIso8601String(),
                'end' => $r->ends_at->toIso8601String(),
                'url' => ReservationResource::getUrl('view', ['record' => $r]),
                'backgroundColor' => self::COLORS[$r->reservable_type],
                'borderColor' => self::COLORS[$r->reservable_type],
                'textColor' => $r->reservable_type === 'professional' ? '#0F1E33' : '#FFFFFF',
                'classNames' => $r->status->value === 'pending' ? ['ev-pending'] : [],
            ])
            ->all();
    }

    private function title(Reservation $r): string
    {
        $guest = strtok($r->user?->name ?? '', ' ');

        return match ($r->reservable_type) {
            'room' => "Q{$r->reservable?->number} · {$guest}",
            'space' => "{$r->reservable?->name} · {$guest}",
            default => "{$r->service?->name} · ".strtok($r->reservable?->name ?? '', ' '),
        };
    }
}
