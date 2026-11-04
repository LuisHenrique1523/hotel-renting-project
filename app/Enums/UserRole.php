<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel, HasColor
{
    case Admin = 'admin';
    case Staff = 'staff';
    case Professional = 'professional';
    case Guest = 'guest';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Staff => 'Recepção',
            self::Professional => 'Profissional',
            self::Guest => 'Hóspede',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Staff => 'warning',
            self::Professional => 'info',
            self::Guest => 'gray',
        };
    }
}
