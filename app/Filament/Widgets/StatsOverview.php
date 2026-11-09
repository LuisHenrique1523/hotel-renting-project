<?php

namespace App\Filament\Widgets;

use App\Enums\BillingUnit;
use App\Models\Reservation;
use App\Models\Space;
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
        $active = max(1, Space::bookable()->count());

        // Ocupação do dia = espaços ativos com pelo menos uma reserva ativa naquele dia
        $occupancy = fn (CarbonImmutable $day) => (int) round(100 * Reservation::blocking()
            ->overlapping($day->setTime(6, 0), $day->setTime(23, 0))
            ->distinct()->count('space_id') / $active);
        $occTrend = collect(range(6, 0))->map(fn ($i) => $occupancy($today->subDays($i)))->all();

        $startingToday = Reservation::blocking()->whereDate('starts_at', $today);
        $next = (clone $startingToday)->billedBy(BillingUnit::Hour)->where('starts_at', '>=', now())->orderBy('starts_at')->with('space')->first();

        // Receita prevista do mês (reservas pagas) e quanto dela já foi realizado até hoje
        $monthRevenue = (float) Reservation::revenue()->whereBetween('starts_at', [$today->startOfMonth(), $today->endOfMonth()])->sum('total');
        $mtd = (float) Reservation::revenue()->whereBetween('starts_at', [$today->startOfMonth(), $today->endOfDay()])->sum('total');
        $revTrend = collect(range(13, 0))->map(fn ($i) => (float) Reservation::revenue()->whereDate('starts_at', $today->subDays($i))->sum('total'))->all();

        $contracts = Reservation::billedBy(BillingUnit::Month)->blocking()->where('ends_at', '>', now())->where('starts_at', '<=', now());
        $mrr = (float) (clone $contracts)->sum('unit_price');

        return [
            Stat::make('Ocupação hoje', end($occTrend).'%')
                ->description("dos {$active} espaços ativos têm reserva")
                ->descriptionIcon('heroicon-m-building-office-2')
                ->chart($occTrend)->color('primary'),

            Stat::make('Reservas que começam hoje', (clone $startingToday)->count())
                ->description($next ? 'Próxima: '.$next->starts_at->format('H:i').' · '.$next->space?->name : 'Sem mais reservas por hora hoje')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Receita prevista no mês', 'R$ '.number_format($monthRevenue, 0, ',', '.'))
                ->description('R$ '.number_format($mtd, 0, ',', '.').' já realizados até hoje')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($revTrend)->color('success'),

            Stat::make('Contratos mensais ativos', $contracts->count())
                ->description('R$ '.number_format($mrr, 0, ',', '.').' de receita recorrente por mês')
                ->descriptionIcon('heroicon-m-key')
                ->color('warning'),
        ];
    }
}
