<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Events\ReservationCancelled;
use App\Events\ReservationConfirmed;
use App\Exceptions\ReservationException;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Motor de reservas: cria e movimenta reservas pela máquina de estados.
 * Toda criação roda em transação com bloqueio de linha (SELECT ... FOR UPDATE)
 * no recurso reservado, o que impede duas reservas simultâneas do mesmo horário.
 */
class ReservationService
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    // ── Criação ─────────────────────────────────────────────────

    public function bookRoom(User $user, RoomType $type, CarbonInterface $checkIn, CarbonInterface $checkOut, int $guests, ?string $notes = null): Reservation
    {
        $checkIn = CarbonImmutable::parse($checkIn)->startOfDay();
        $checkOut = CarbonImmutable::parse($checkOut)->startOfDay();

        $this->ensure($checkIn->gte(today()), 'A data de check-in não pode estar no passado.');
        $this->ensure($checkOut->gt($checkIn), 'O check-out deve ser depois do check-in.');
        $this->ensure($checkIn->diffInDays($checkOut) <= config('hotel.max_nights'), 'O limite é de '.config('hotel.max_nights').' diárias por reserva.');
        $this->ensure($guests >= 1 && $guests <= $type->capacity, "O {$type->name} acomoda até {$type->capacity} hóspedes.");

        [$start, $end] = $this->availability->stayWindow($checkIn, $checkOut);
        $total = $this->pricing->quoteRoom($type, $checkIn, $checkOut)['total'];

        return DB::transaction(function () use ($user, $type, $start, $end, $total, $guests, $notes) {
            // Trava os quartos do tipo: reservas concorrentes do mesmo tipo esperam aqui
            $room = $type->rooms()->operational()->orderBy('number')->lockForUpdate()->get()
                ->first(fn (Room $room) => $room->isFreeBetween($start, $end));

            if (! $room) {
                throw ReservationException::invalid("Não há mais {$type->name} disponível nessas datas. Tente outras datas ou outra categoria.");
            }

            return $this->create($user, $room, $start, $end, $total, $guests, $notes);
        });
    }

    public function bookSpace(User $user, Space $space, CarbonInterface $start, CarbonInterface $end, int $guests = 1, ?string $notes = null): Reservation
    {
        $start = CarbonImmutable::parse($start);
        $end = CarbonImmutable::parse($end);

        $this->ensure($space->is_active, 'Este espaço não está disponível para reservas.');
        $this->ensure($start->isFuture(), 'Escolha um horário futuro.');
        $this->ensure($end->gt($start) && $start->isSameDay($end), 'Intervalo de horário inválido.');
        $this->ensure($start->minute === 0 && $end->minute === 0, 'Salas são reservadas em blocos de hora cheia.');
        $this->ensure($start->hour >= $space->opens_hour && ($end->hour <= $space->closes_hour), "Funcionamento: {$space->opens_hour}h às {$space->closes_hour}h.");
        $this->ensure($guests <= $space->capacity, "Capacidade máxima: {$space->capacity} pessoas.");

        $total = $this->pricing->quoteSpace($space, $start, $end)['total'];

        return DB::transaction(function () use ($user, $space, $start, $end, $total, $guests, $notes) {
            $locked = Space::whereKey($space->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isFreeBetween($start, $end)) {
                throw ReservationException::unavailable('Esse horário da sala');
            }

            return $this->create($user, $locked, $start, $end, $total, $guests, $notes);
        });
    }

    public function bookService(User $user, Professional $professional, Service $service, CarbonInterface $start, ?string $notes = null): Reservation
    {
        $start = CarbonImmutable::parse($start);
        $end = $start->addMinutes($service->duration_min);

        $this->ensure($professional->is_active && $service->is_active, 'Serviço indisponível no momento.');
        $this->ensure($professional->services()->whereKey($service->id)->exists(), "{$professional->name} não realiza {$service->name}.");
        $this->ensure($start->isFuture(), 'Escolha um horário futuro.');
        $this->ensure($this->availability->withinWorkingHours($professional, $start, $end), 'Fora do horário de atendimento do profissional.');

        $total = $this->pricing->quoteService($service);

        return DB::transaction(function () use ($user, $professional, $service, $start, $end, $total, $notes) {
            $locked = Professional::whereKey($professional->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isFreeBetween($start, $end)) {
                throw ReservationException::unavailable('Esse horário com '.$professional->name);
            }

            return $this->create($user, $locked, $start, $end, $total, 1, $notes, $service);
        });
    }

    // ── Pagamento e máquina de estados ─────────────────────────

    /** Pagamento simulado: aprova na hora e confirma a reserva. */
    public function pay(Reservation $reservation, PaymentMethod $method): Reservation
    {
        $this->ensure($reservation->status === ReservationStatus::Pending, 'Esta reserva não está aguardando pagamento.');

        return DB::transaction(function () use ($reservation, $method) {
            $reservation->payment()->updateOrCreate([], [
                'method' => $method,
                'amount' => $reservation->total,
                'status' => PaymentStatus::Paid,
                'transaction_id' => 'SIM-'.Str::upper(Str::random(10)),
                'paid_at' => now(),
            ]);

            return $this->transition($reservation, ReservationStatus::Confirmed);
        });
    }

    /** Confirmação manual pela recepção (ex.: pagamento no balcão). */
    public function confirm(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation->payment()->updateOrCreate([], [
                'method' => $reservation->payment?->method ?? PaymentMethod::OnSite,
                'amount' => $reservation->total,
                'status' => PaymentStatus::Paid,
                'paid_at' => now(),
            ]);

            return $this->transition($reservation, ReservationStatus::Confirmed);
        });
    }

    public function cancel(Reservation $reservation, ?User $by = null): Reservation
    {
        if ($by && ! $by->isStaff()) {
            $this->ensure($reservation->canBeCancelledByGuest(), 'Cancelamentos online são permitidos até '.config('hotel.guest_cancellation_hours').'h antes. Fale com a recepção.');
        }

        return DB::transaction(function () use ($reservation) {
            if ($reservation->payment?->status === PaymentStatus::Paid) {
                $reservation->payment->update(['status' => PaymentStatus::Refunded]);
            }

            return $this->transition($reservation, ReservationStatus::Cancelled);
        });
    }

    public function checkIn(Reservation $reservation): Reservation
    {
        $this->ensure($reservation->starts_at->startOfDay()->lte(now()), 'O check-in só pode ser feito a partir do dia de início da reserva.');

        return $this->transition($reservation, ReservationStatus::CheckedIn);
    }

    public function checkOut(Reservation $reservation): Reservation
    {
        return $this->transition($reservation, ReservationStatus::Completed);
    }

    public function markNoShow(Reservation $reservation): Reservation
    {
        return $this->transition($reservation, ReservationStatus::NoShow);
    }

    public function transition(Reservation $reservation, ReservationStatus $to): Reservation
    {
        $from = $reservation->status;

        if (! $from->canTransitionTo($to)) {
            throw ReservationException::invalidTransition($from, $to);
        }

        $reservation->status = $to;

        match ($to) {
            ReservationStatus::Confirmed => $reservation->confirmed_at = now(),
            ReservationStatus::CheckedIn => $reservation->checked_in_at = now(),
            ReservationStatus::Completed => $reservation->checked_out_at = now(),
            ReservationStatus::Cancelled, ReservationStatus::NoShow => $reservation->cancelled_at = now(),
            default => null,
        };

        $reservation->save();

        match ($to) {
            ReservationStatus::Confirmed => ReservationConfirmed::dispatch($reservation),
            ReservationStatus::Cancelled => ReservationCancelled::dispatch($reservation),
            default => null,
        };

        return $reservation;
    }

    // ── Internos ────────────────────────────────────────────────

    private function create(User $user, Model $reservable, CarbonInterface $start, CarbonInterface $end, float $total, int $guests, ?string $notes, ?Service $service = null): Reservation
    {
        $reservation = new Reservation([
            'user_id' => $user->id,
            'service_id' => $service?->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'guests' => $guests,
            'status' => ReservationStatus::Pending,
            'total' => $total,
            'notes' => $notes,
        ]);
        $reservation->reservable()->associate($reservable);
        $reservation->save();

        $reservation->payment()->create(['amount' => $total, 'status' => PaymentStatus::Pending]);

        return $reservation;
    }

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ReservationException::invalid($message);
        }
    }
}
