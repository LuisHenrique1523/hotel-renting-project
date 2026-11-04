<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Operação';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Calendário';
    protected static ?string $title = 'Calendário de reservas';
    protected static string $view = 'filament.pages.calendar';

    public string $kind = 'professional';

    public function mount(): void
    {
        // Profissional só tem agenda de atendimentos
        if (auth()->user()->isProfessional()) {
            $this->kind = 'professional';
        }
    }

    public function setKind(string $kind): void
    {
        if (in_array($kind, ['professional', 'space', 'room', 'all'], true)) {
            $this->kind = $kind;
        }
    }
}
