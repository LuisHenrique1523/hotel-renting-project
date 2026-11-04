<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ServiceCategory: string implements HasLabel, HasColor
{
    case Spa = 'spa';
    case Wellness = 'wellness';
    case Fitness = 'fitness';

    public function getLabel(): string
    {
        return match ($this) {
            self::Spa => 'Spa & Massagem',
            self::Wellness => 'Bem-estar & Estética',
            self::Fitness => 'Fitness',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Spa => 'primary',
            self::Wellness => 'success',
            self::Fitness => 'info',
        };
    }
}
