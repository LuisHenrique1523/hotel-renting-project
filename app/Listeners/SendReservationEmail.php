<?php

namespace App\Listeners;

use App\Events\ReservationCancelled;
use App\Events\ReservationConfirmed;
use App\Mail\ReservationStatusMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Reage aos eventos do motor de reservas enviando e-mail ao cliente.
 * O motor não conhece e-mail: o desacoplamento é feito pelo evento.
 */
class SendReservationEmail
{
    public function handle(ReservationConfirmed|ReservationCancelled $event): void
    {
        $reservation = $event->reservation->loadMissing('user', 'space.unit', 'space.category');
        $kind = $event instanceof ReservationConfirmed ? 'confirmed' : 'cancelled';

        try {
            Mail::to($reservation->user)->send(new ReservationStatusMail($reservation, $kind));
        } catch (Throwable $e) {
            // Falha de e-mail nunca deve derrubar a reserva
            Log::warning('Falha ao enviar e-mail da reserva '.$reservation->code.': '.$e->getMessage());
        }
    }
}
