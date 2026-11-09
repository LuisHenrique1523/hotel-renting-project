<?php

namespace App\Filament\Widgets;

use App\Enums\BillingUnit;
use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class ReservationsChart extends ChartWidget
{
    protected static ?string $heading = 'Reservas por dia';
    protected static ?string $description = 'Últimos 14 dias e próximos 14 dias, por forma de cobrança';
    protected static ?int $sort = 2;
    protected static ?string $maxHeight = '280px';
    protected int|string|array $columnSpan = ['md' => 2];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    protected function getData(): array
    {
        $start = CarbonImmutable::today()->subDays(14);
        $days = collect(range(0, 28))->map(fn ($i) => $start->addDays($i));

        $counts = Reservation::blocking()
            ->whereBetween('starts_at', [$start, $start->addDays(29)])
            ->selectRaw('DATE(starts_at) as day, billing_unit, COUNT(*) as total')
            ->groupBy('day', 'billing_unit')
            ->get()
            ->groupBy(fn ($row) => $row->billing_unit->value);

        $series = fn (BillingUnit $unit) => $days->map(fn ($d) => (int) ($counts->get($unit->value)?->firstWhere('day', $d->toDateString())?->total ?? 0))->all();
        $labels = [BillingUnit::Hour->value => 'Por hora', BillingUnit::Day->value => 'Por dia', BillingUnit::Month->value => 'Contratos mensais (início)'];

        return [
            'datasets' => collect(BillingUnit::cases())->map(fn (BillingUnit $unit) => [
                'label' => $labels[$unit->value],
                'data' => $series($unit),
                'borderColor' => $unit->hex(),
                'backgroundColor' => $unit->hex().'22',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 0,
            ])->all(),
            'labels' => $days->map(fn ($d) => $d->isToday() ? 'Hoje' : $d->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]], 'x' => ['grid' => ['display' => false]]],
            'interaction' => ['mode' => 'index', 'intersect' => false],
        ];
    }
}
