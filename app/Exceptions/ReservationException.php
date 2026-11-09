<?php

namespace App\Exceptions;

use App\Enums\ReservationStatus;
use RuntimeException;

/**
 * Violação de regra de negócio do motor de reservas.
 * A mensagem é amigável e pode ser exibida diretamente ao usuário.
 */
class ReservationException extends RuntimeException
{
    public static function unavailable(string $what = 'Este horário'): self
    {
        return new self("{$what} não está mais disponível para este espaço. Escolha outra data ou outro espaço.");
    }

    public static function invalid(string $message): self
    {
        return new self($message);
    }

    public static function invalidTransition(ReservationStatus $from, ReservationStatus $to): self
    {
        return new self("Não é possível mudar uma reserva de \"{$from->getLabel()}\" para \"{$to->getLabel()}\".");
    }
}
