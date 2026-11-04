<?php

namespace App\Policies;

use App\Models\Reservation;
use App\Models\User;

/**
 * Quem pode ver e mexer em cada reserva.
 * Usada tanto no site (hóspede) quanto no painel Filament (equipe e profissionais).
 */
class ReservationPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // cada tela filtra pelo escopo do usuário
    }

    public function view(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id
            || $user->isStaff()
            || $this->isAssignedProfessional($user, $reservation);
    }

    public function create(User $user): bool
    {
        return $user->isStaff();
    }

    /** Hóspede só paga a própria reserva. */
    public function pay(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }

    public function cancel(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id || $user->isStaff();
    }

    /** Operações de balcão: confirmar, check-in, check-out. */
    public function operate(User $user, Reservation $reservation): bool
    {
        return $user->isStaff() || $this->isAssignedProfessional($user, $reservation);
    }

    public function update(User $user, Reservation $reservation): bool
    {
        return $user->isStaff();
    }

    public function delete(User $user, Reservation $reservation): bool
    {
        return $user->isAdmin();
    }

    private function isAssignedProfessional(User $user, Reservation $reservation): bool
    {
        return $user->isProfessional()
            && $reservation->reservable_type === Reservation::KIND_PROFESSIONAL
            && $reservation->reservable_id === $user->professional?->id;
    }
}
