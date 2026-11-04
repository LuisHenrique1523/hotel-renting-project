<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReservationStatus: string implements HasLabel, HasColor, HasIcon
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Aguardando pagamento',
            self::Confirmed => 'Confirmada',
            self::CheckedIn => 'Em andamento',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
            self::NoShow => 'Não compareceu',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed => 'success',
            self::CheckedIn => 'info',
            self::Completed => 'gray',
            self::Cancelled => 'danger',
            self::NoShow => 'danger',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Pending => 'heroicon-m-clock',
            self::Confirmed => 'heroicon-m-check-badge',
            self::CheckedIn => 'heroicon-m-key',
            self::Completed => 'heroicon-m-flag',
            self::Cancelled => 'heroicon-m-x-circle',
            self::NoShow => 'heroicon-m-user-minus',
        };
    }

    /** Classes Tailwind para o badge no site público. */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-100 text-amber-800 ring-amber-200',
            self::Confirmed => 'bg-emerald-100 text-emerald-800 ring-emerald-200',
            self::CheckedIn => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::Completed => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Cancelled, self::NoShow => 'bg-rose-100 text-rose-800 ring-rose-200',
        };
    }

    /** Transições permitidas pela máquina de estados. */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::CheckedIn, self::Cancelled, self::NoShow],
            self::CheckedIn => [self::Completed],
            self::Completed, self::Cancelled, self::NoShow => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->allowedTransitions(), true);
    }

    /** Status que ocupam a agenda (bloqueiam o horário). */
    public static function blocking(): array
    {
        return [self::Pending, self::Confirmed, self::CheckedIn, self::Completed];
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Pending, self::Confirmed, self::CheckedIn], true);
    }
}
