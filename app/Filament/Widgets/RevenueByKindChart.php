<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use Filament\Widgets\ChartWidget;

class RevenueByKindChart extends ChartWidget
{
    protected static ?string $heading = 'Receita do mês por área';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '280px';
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    public function getDescription(): ?string
    {
        return now()->translatedFormat('F \d\e Y');
    }

    protected function getData(): array
    {
        $totals = Reservation::revenue()
            ->whereBetween('starts_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('reservable_type, SUM(total) as total')
            ->groupBy('reservable_type')
            ->pluck('total', 'reservable_type');

        $kinds = ['room' => '#2B3F5C', 'professional' => '#C8A15A', 'space' => '#0EA5E9'];

        return [
            'datasets' => [[
                'data' => collect($kinds)->keys()->map(fn ($k) => round((float) ($totals[$k] ?? 0), 2))->all(),
                'backgroundColor' => array_values($kinds),
                'borderWidth' => 0,
                'hoverOffset' => 8,
            ]],
            'labels' => collect($kinds)->keys()->map(fn ($k) => Reservation::KINDS[$k])->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout' => '68%',
            'plugins' => ['legend' => ['position' => 'bottom', 'labels' => ['usePointStyle' => true, 'boxWidth' => 8]]],
            'scales' => ['x' => ['display' => false], 'y' => ['display' => false]],
        ];
    }
}
