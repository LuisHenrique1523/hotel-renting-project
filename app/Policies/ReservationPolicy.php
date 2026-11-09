<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * Quem pode ver e mexer em cada reserva.
 * Usada tanto no site (cliente) quanto no painel Filament (recepção e administração).
 */
class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id || $user->isStaff();
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /** Cliente só paga a própria reserva. */
    public function pay(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id || $user->isStaff();
    }

    /** Operações de balcão: confirmar, check-in, conclusão. */
    public function operate(User $user, Reservation $reservation): bool
    {
        return $user->isStaff();
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin();
    }
}
