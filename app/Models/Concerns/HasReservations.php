<?php

namespace App\Models\Concerns;

use App\Models\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Comportamento comum a tudo que pode ser reservado (quarto, sala, profissional).
 * É a "interface" do motor de reservas polimórfico.
 */
trait HasReservations
{
    public function reservations(): MorphMany
    {
        return $this->morphMany(Reservation::class, 'reservable');
    }

    /** Recursos SEM reserva ativa sobrepondo o intervalo [start, end). */
    public function scopeFreeBetween(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->whereDoesntHave('reservations', fn (Builder $q) => $q->blocking()->overlapping($start, $end));
    }

    public function isFreeBetween(CarbonInterface $start, CarbonInterface $end, ?int $ignoreReservationId = null): bool
    {
        return ! $this->reservations()
            ->blocking()
            ->overlapping($start, $end)
            ->when($ignoreReservationId, fn ($q) => $q->whereKeyNot($ignoreReservationId))
            ->exists();
    }

    /** Nome exibido nas telas e no calendário. */
    abstract public function displayName(): string;
}
