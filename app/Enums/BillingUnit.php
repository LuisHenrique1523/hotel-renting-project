<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

/**
 * Forma de cobrança de uma categoria de espaço. Define a unidade de tempo da reserva:
 * sala de reunião por hora, coworking por dia, escritório privativo por mês.
 */
enum BillingUnit: string implements HasLabel, HasColor, HasIcon
{
    case Hour = 'hour';
    case Day = 'day';
    case Month = 'month';

    public function getLabel(): string
    {
        return match ($this) {
            self::Hour => 'Por hora',
            self::Day => 'Por dia',
            self::Month => 'Por mês',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Hour => 'info',
            self::Day => 'primary',
            self::Month => 'success',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Hour => 'heroicon-m-clock',
            self::Day => 'heroicon-m-sun',
            self::Month => 'heroicon-m-calendar',
        };
    }

    /** Sufixo de preço: "R$ 90/h". */
    public function suffix(): string
    {
        return match ($this) {
            self::Hour => '/h',
            self::Day => '/dia',
            self::Month => '/mês',
        };
    }

    /** "3 horas", "1 dia", "6 meses". */
    public function quantityLabel(int $quantity): string
    {
        [$one, $many] = match ($this) {
            self::Hour => ['hora', 'horas'],
            self::Day => ['dia', 'dias'],
            self::Month => ['mês', 'meses'],
        };

        return $quantity.' '.($quantity === 1 ? $one : $many);
    }

    public function unitName(): string
    {
        return match ($this) {
            self::Hour => 'horas',
            self::Day => 'dias',
            self::Month => 'meses',
        };
    }

    /** Limites padrão sugeridos ao cadastrar uma categoria. */
    public function defaultMax(): int
    {
        return match ($this) {
            self::Hour => 12,
            self::Day => 30,
            self::Month => 12,
        };
    }

    /** Cor usada no calendário e nos gráficos do painel. */
    public function hex(): string
    {
        return match ($this) {
            self::Hour => '#0EA5E9',
            self::Day => '#C8A15A',
            self::Month => '#2B3F5C',
        };
    }
}
