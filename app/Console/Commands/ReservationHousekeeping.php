<?php

namespace App\Console\Commands;

use App\Enums\ReservationStatus;
use App\Exceptions\ReservationException;
use App\Models\Reservation;
use App\Services\ReservationService;
use Illuminate\Console\Command;

/**
 * Rotina automática (agendada a cada 5 minutos):
 *  1. Pendentes sem pagamento após o prazo → canceladas (libera o horário)
 *  2. Confirmadas sem check-in após a tolerância → não compareceu
 *  3. Reservas em andamento cujo período já terminou → concluídas
 */
class ReservationHousekeeping extends Command
{
    protected $signature = 'reservations:housekeeping';

    protected $description = 'Expira reservas não pagas, marca no-show e conclui reservas encerradas';

    public function handle(ReservationService $engine): int
    {
        $expired = $this->each(
            Reservation::where('status', ReservationStatus::Pending)
                ->where('created_at', '<', now()->subMinutes((int) config('aurora.pending_expiration_minutes'))),
            fn ($r) => $engine->transition($r, ReservationStatus::Cancelled),
        );

        $noShow = $this->each(
            Reservation::where('status', ReservationStatus::Confirmed)
                ->where('starts_at', '<', now()->subHours((int) config('aurora.no_show_tolerance_hours'))),
            fn ($r) => $engine->markNoShow($r),
        );

        $finished = $this->each(
            Reservation::where('status', ReservationStatus::CheckedIn)
                ->where('ends_at', '<', now()),
            fn ($r) => $engine->checkOut($r),
        );

        $this->components->info("Expiradas: {$expired} · Não compareceram: {$noShow} · Concluídas: {$finished}");

        return self::SUCCESS;
    }

    private function each($query, callable $action): int
    {
        $count = 0;

        $query->lazyById()->each(function (Reservation $reservation) use ($action, &$count) {
            try {
                $action($reservation);
                $count++;
            } catch (ReservationException $e) {
                $this->components->warn("{$reservation->code}: {$e->getMessage()}");
            }
        });

        return $count;
    }
}
