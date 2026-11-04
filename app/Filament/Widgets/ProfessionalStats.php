<?php

namespace App\Filament\Widgets;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Painel do profissional: apenas os números da própria agenda. */
class ProfessionalStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isProfessional();
    }

    protected function getStats(): array
    {
        $mine = fn () => Reservation::ofKind('professional')->where('reservable_id', auth()->user()->professional?->id ?? 0);

        $today = $mine()->blocking()->whereDate('starts_at', today())->count();
        $week = $mine()->blocking()->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $done = $mine()->where('status', ReservationStatus::Completed)->whereMonth('starts_at', now()->month)->count();
        $next = $mine()->blocking()->where('starts_at', '>=', now())->orderBy('starts_at')->with('service', 'user')->first();

        return [
            Stat::make('Atendimentos hoje', $today)->icon('heroicon-o-calendar')->color('primary'),
            Stat::make('Nesta semana', $week)->icon('heroicon-o-calendar-days'),
            Stat::make('Concluídos no mês', $done)->icon('heroicon-o-check-badge')->color('success'),
            Stat::make('Próximo cliente', $next ? $next->starts_at->format('d/m H:i') : '—')
                ->description($next ? $next->service?->name.' · '.$next->user?->name : 'Agenda livre'),
        ];
    }
}
