<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReservationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Reservation $reservation, public string $kind = 'confirmed') {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->kind === 'confirmed'
                ? "Reserva confirmada · {$this->reservation->code}"
                : "Reserva cancelada · {$this->reservation->code}",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.reservation-status');
    }
}
