<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SpaceType: string implements HasLabel
{
    case Meeting = 'meeting';
    case Event = 'event';
    case Coworking = 'coworking';

    public function getLabel(): string
    {
        return match ($this) {
            self::Meeting => 'Sala de reunião',
            self::Event => 'Salão de eventos',
            self::Coworking => 'Coworking',
        };
    }
}
