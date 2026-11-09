<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Visão geral';
    protected static ?string $navigationLabel = 'Visão geral';
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    public function getSubheading(): ?string
    {
        return 'Olá, '.strtok(auth()->user()->name, ' ').'! Hoje é '.now()->translatedFormat('l, d \d\e F').'.';
    }

    public function getWidgets(): array
    {
        return [
            Widgets\StatsOverview::class,
            Widgets\ReservationsChart::class,
            Widgets\RevenueByUnitChart::class,
            Widgets\TodayAgenda::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return ['md' => 3];
    }
}
