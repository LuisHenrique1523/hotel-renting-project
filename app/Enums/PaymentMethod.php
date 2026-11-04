<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Pix = 'pix';
    case Card = 'card';
    case OnSite = 'on_site';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pix => 'Pix',
            self::Card => 'Cartão de crédito',
            self::OnSite => 'Pagamento no hotel',
        };
    }
}
