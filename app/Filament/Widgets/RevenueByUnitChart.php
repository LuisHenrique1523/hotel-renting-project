<?php

namespace App\Filament\Widgets;

use App\Models\Reservation;
use App\Models\Unit;
use Filament\Widgets\ChartWidget;

class RevenueByUnitChart extends ChartWidget
{
    protected static ?string $heading = 'Receita do mês por unidade';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '280px';
    protected int|string|array $columnSpan = 1;

    private const PALETTE = ['#2B3F5C', '#C8A15A', '#0EA5E9', '#10B981', '#8B5CF6', '#F43F5E', '#64748B'];

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
            ->whereBetween('reservations.starts_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->join('spaces', 'spaces.id', '=', 'reservations.space_id')
            ->selectRaw('spaces.unit_id, SUM(reservations.total) as total')
            ->groupBy('spaces.unit_id')
            ->pluck('total', 'unit_id');

        $units = Unit::orderBy('name')->get(['id', 'name'])->filter(fn ($u) => isset($totals[$u->id]))->values();

        return [
            'datasets' => [[
                'data' => $units->map(fn ($u) => round((float) $totals[$u->id], 2))->all(),
                'backgroundColor' => $units->keys()->map(fn ($i) => self::PALETTE[$i % count(self::PALETTE)])->all(),
                'borderWidth' => 0,
                'hoverOffset' => 8,
            ]],
            'labels' => $units->pluck('name')->all(),
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
