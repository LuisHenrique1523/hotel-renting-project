<?php

namespace App\Filament\Concerns;

/** Cadastros do hotel: visíveis apenas para administração e recepção. */
trait StaffOnly
{
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }
}
