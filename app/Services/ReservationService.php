<?php

namespace App\Services;

use App\Enums\BillingUnit;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Events\ReservationCancelled;
use App\Events\ReservationConfirmed;
use App\Exceptions\ReservationException;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
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

    /**
     * Reserva um espaço a partir de "início + quantidade" na unidade de cobrança da categoria.
     * Hora: $start traz data e hora cheia. Dia e mês: só a data de início importa.
     */
    public function book(User $user, Space $space, CarbonInterface $start, int $quantity, int $guests = 1, ?string $notes = null): Reservation
    {
        $space->loadMissing('unit', 'category');
        $category = $space->category;
        $billing = $category->billing_unit;
        $start = CarbonImmutable::parse($start);

        $this->ensure($space->isBookable(), 'Este espaço não está disponível para reservas.');
        $this->ensure($quantity >= $category->min_quantity && $quantity <= $category->max_quantity,
            "{$category->name}: reserve de {$billing->quantityLabel($category->min_quantity)} a {$billing->quantityLabel($category->max_quantity)}.");
        $this->ensure($guests >= 1 && $guests <= $space->capacity, "Capacidade máxima: {$space->capacity} ".($space->capacity === 1 ? 'pessoa' : 'pessoas').'.');
        $this->ensure($start->startOfDay()->lte(today()->addDays((int) config('aurora.booking_horizon_days'))),
            'Reservas podem começar em até '.config('aurora.booking_horizon_days').' dias.');

        [$from, $to] = $this->availability->window($space, $start, $quantity);
        $unit = $space->unit;

        if ($billing === BillingUnit::Hour) {
            $this->ensure($start->minute === 0 && $start->second === 0, 'Espaços por hora são reservados em blocos de hora cheia.');
            $this->ensure($start->isFuture(), 'Escolha um horário futuro.');
            $this->ensure($start->hour >= $unit->opens_hour && $to->lte($start->startOfDay()->setTime($unit->closes_hour, 0)),
                "Funcionamento da unidade: {$unit->hours_label}.");
        } else {
            $this->ensure($start->startOfDay()->gte(today()), 'A data de início não pode estar no passado.');
            $this->ensure($start->startOfDay()->setTime($unit->closes_hour, 0)->isFuture(), 'A unidade já encerrou o expediente nesta data. Escolha a partir de amanhã.');
        }

        $quote = $this->pricing->quote($space, $quantity, $guests);

        return DB::transaction(function () use ($user, $space, $from, $to, $quote, $guests, $notes, $billing) {
            // Trava a linha do espaço: reservas concorrentes do mesmo espaço esperam aqui
            $locked = Space::whereKey($space->id)->lockForUpdate()->firstOrFail()
                ->setRelation('category', $space->category);

            if (! $locked->isFreeBetween($from, $to, $guests)) {
                throw $locked->is_shared
                    ? ReservationException::invalid('Não há '.($guests === 1 ? 'vaga' : "{$guests} vagas").' nesse período (restam '.$locked->seatsLeftBetween($from, $to).'). Escolha outra data.')
                    : ReservationException::unavailable($billing === BillingUnit::Hour ? 'Esse horário' : 'Esse período');
            }

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'space_id' => $locked->id,
                'billing_unit' => $billing,
                'quantity' => $quote['quantity'],
                'unit_price' => $quote['unit_price'],
                'starts_at' => $from,
                'ends_at' => $to,
                'guests' => $guests,
                'status' => ReservationStatus::Pending,
                'total' => $quote['total'],
                'notes' => $notes,
            ]);

            $reservation->payment()->create(['amount' => $quote['total'], 'status' => PaymentStatus::Pending]);

            return $reservation;
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
            $this->ensure($reservation->canBeCancelledByGuest(), 'Cancelamentos online são permitidos até '.config('aurora.guest_cancellation_hours').'h antes. Fale com a recepção da unidade.');
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

    private function ensure(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ReservationException::invalid($message);
        }
    }
}
