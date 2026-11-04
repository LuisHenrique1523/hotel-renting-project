<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class ReservationsChart extends ChartWidget
{
    protected static ?string $heading = 'Reservas por dia';
    protected static ?string $description = 'Últimos 14 dias e próximos 14 dias, por tipo';
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
            ->selectRaw('DATE(starts_at) as day, reservable_type, COUNT(*) as total')
            ->groupBy('day', 'reservable_type')
            ->get()
            ->groupBy('reservable_type');

        $series = fn (string $kind) => $days->map(fn ($d) => (int) ($counts->get($kind)?->firstWhere('day', $d->toDateString())?->total ?? 0))->all();

        return [
            'datasets' => [
                ['label' => 'Spa & Serviços', 'data' => $series('professional'), 'borderColor' => '#C8A15A', 'backgroundColor' => 'rgba(200,161,90,.15)', 'fill' => true, 'tension' => 0.35, 'pointRadius' => 0],
                ['label' => 'Salas', 'data' => $series('space'), 'borderColor' => '#0EA5E9', 'backgroundColor' => 'rgba(14,165,233,.08)', 'fill' => true, 'tension' => 0.35, 'pointRadius' => 0],
                ['label' => 'Hospedagem (check-ins)', 'data' => $series('room'), 'borderColor' => '#2B3F5C', 'backgroundColor' => 'rgba(43,63,92,.08)', 'fill' => true, 'tension' => 0.35, 'pointRadius' => 0],
            ],
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
