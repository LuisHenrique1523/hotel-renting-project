<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use App\Models\Room;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    protected function getStats(): array
    {
        $today = CarbonImmutable::today();
        $operational = max(1, Room::operational()->count());

        // Ocupação de uma noite = quartos com reserva ativa às 20h daquele dia
        $occupancy = fn (CarbonImmutable $day) => (int) round(100 * Reservation::ofKind('room')->blocking()
            ->overlapping($day->setTime(20, 0), $day->setTime(20, 1))->count() / $operational);
        $occTrend = collect(range(6, 0))->map(fn ($i) => $occupancy($today->subDays($i)))->all();

        $arrivals = Reservation::ofKind('room')->blocking()->whereDate('starts_at', $today)->count();
        $departures = Reservation::ofKind('room')->blocking()->whereDate('ends_at', $today)->count();

        // Receita prevista do mês (reservas pagas) e quanto dela já foi realizado até hoje
        $monthRevenue = (float) Reservation::revenue()->whereBetween('starts_at', [$today->startOfMonth(), $today->endOfMonth()])->sum('total');
        $mtd = (float) Reservation::revenue()->whereBetween('starts_at', [$today->startOfMonth(), $today->endOfDay()])->sum('total');
        $revTrend = collect(range(13, 0))->map(fn ($i) => (float) Reservation::revenue()->whereDate('starts_at', $today->subDays($i))->sum('total'))->all();

        $hourly = Reservation::whereIn('reservable_type', ['space', 'professional'])->blocking()->whereDate('starts_at', $today);
        $next = (clone $hourly)->where('starts_at', '>=', now())->orderBy('starts_at')->with('service', 'reservable')->first();

        return [
            Stat::make('Ocupação esta noite', end($occTrend).'%')
                ->description(Room::operational()->count().' quartos operacionais')
                ->descriptionIcon('heroicon-m-home-modern')
                ->chart($occTrend)->color('primary'),

            Stat::make('Chegadas hoje', $arrivals)
                ->description($departures.' '.($departures === 1 ? 'saída prevista' : 'saídas previstas'))
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('info'),

            Stat::make('Receita prevista no mês', 'R$ '.number_format($monthRevenue, 0, ',', '.'))
                ->description('R$ '.number_format($mtd, 0, ',', '.').' já realizados até hoje')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($revTrend)->color('success'),

            Stat::make('Spa e salas hoje', $hourly->count())
                ->description($next ? 'Próximo: '.$next->starts_at->format('H:i').' · '.$next->title : 'Sem mais atendimentos hoje')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
        ];
    }
}
