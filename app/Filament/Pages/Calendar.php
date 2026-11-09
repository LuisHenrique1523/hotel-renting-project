<?php

namespace App\Filament\Pages;

use App\Models\Unit;
use Filament\Pages\Page;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Operação';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Calendário';
    protected static ?string $title = 'Calendário de reservas';
    protected static string $view = 'filament.pages.calendar';

    /** Unidade filtrada (null = todas). */
    public ?int $unit = null;

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    public function mount(): void
    {
        $this->unit = Unit::active()->orderBy('city')->orderBy('name')->value('id');
    }

    public function setUnit(?int $unit): void
    {
        $this->unit = $unit;
    }

    protected function getViewData(): array
    {
        return ['units' => Unit::active()->orderBy('city')->orderBy('name')->get(['id', 'name'])];
    }
}
