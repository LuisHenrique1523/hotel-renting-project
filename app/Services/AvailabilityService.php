<?php

namespace App\Services;

use App\Enums\BillingUnit;
use App\Models\Reservation;
use App\Models\Space;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Consulta de disponibilidade. Toda verificação usa a MESMA regra de sobreposição
 * (Reservation::scopeOverlapping), seja a reserva por hora, por dia ou por mês.
 */
class AvailabilityService
{
    /**
     * Converte "início + quantidade" na janela real ocupada, conforme a cobrança da categoria:
     *  - hora: [início, início + N horas)
     *  - dia:  do 1º dia na abertura até o último dia no fechamento
     *  - mês:  do 1º dia na abertura até a véspera do mesmo dia N meses depois, no fechamento
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function window(Space $space, CarbonInterface $start, int $quantity): array
    {
        $start = CarbonImmutable::parse($start);
        $unit = $space->unit;

        return match ($space->billing_unit) {
            BillingUnit::Hour => [$start, $start->addHours($quantity)],
            BillingUnit::Day => [
                $start->setTime($unit->opens_hour, 0),
                $start->addDays($quantity - 1)->setTime($unit->closes_hour, 0),
            ],
            BillingUnit::Month => [
                $start->setTime($unit->opens_hour, 0),
                $start->addMonthsNoOverflow($quantity)->subDay()->setTime($unit->closes_hour, 0),
            ],
        };
    }

    /**
     * Grade de horários de um espaço cobrado por hora em um dia (blocos de 1h).
     *
     * @return array<int, array{time:string, end:string, start:string, available:bool, reason:?string, left:int}>
     */
    public function hourSlots(Space $space, CarbonInterface $date): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $busy = $this->busyIntervals($space, $day, $day->endOfDay());
        $slots = [];

        for ($h = $space->unit->opens_hour; $h < $space->unit->closes_hour; $h++) {
            $start = $day->setTime($h, 0);
            $end = $start->addHour();

            $left = $this->left($space, $busy, $start, $end);
            $reason = match (true) {
                $start->lessThan(now()) => 'past',
                $left === 0 => 'busy',
                default => null,
            };

            $slots[] = [
                'time' => $start->format('H:i'),
                'end' => $end->format('H:i'),
                'start' => $start->toIso8601String(),
                'available' => $reason === null,
                'reason' => $reason,
                'left' => $left,
            ];
        }

        return $slots;
    }

    /**
     * Situação de cada dia a partir de hoje, para espaços cobrados por dia ou por mês.
     * Um dia está "ocupado" se uma reserva ativa toca o expediente dele (ou, se compartilhado, se lotou).
     *
     * @return array<int, array{date:string, available:bool, reason:?string, left:int}>
     */
    public function dayCalendar(Space $space, int $days): array
    {
        $first = CarbonImmutable::today();
        $last = $first->addDays($days - 1);
        [$opens, $closes] = [$space->unit->opens_hour, $space->unit->closes_hour];

        $busy = $this->busyIntervals($space, $first->setTime($opens, 0), $last->setTime($closes, 0));
        $calendar = [];

        for ($day = $first; $day <= $last; $day = $day->addDay()) {
            $start = $day->setTime($opens, 0);
            $end = $day->setTime($closes, 0);

            $left = $this->left($space, $busy, $start, $end);
            $reason = match (true) {
                $end->lessThanOrEqualTo(now()) => 'past',
                $left === 0 => 'busy',
                default => null,
            };

            $calendar[] = ['date' => $day->toDateString(), 'available' => $reason === null, 'reason' => $reason, 'left' => $left];
        }

        return $calendar;
    }

    /** Intervalos ocupados de um espaço em uma janela (uma única consulta). */
    private function busyIntervals(Space $space, CarbonInterface $from, CarbonInterface $to): Collection
    {
        return $space->reservations()
            ->blocking()
            ->overlapping($from, $to)
            ->get(['starts_at', 'ends_at', 'guests']);
    }

    /**
     * Quanto ainda cabe no intervalo: espaço exclusivo → 1 (livre) ou 0 (ocupado);
     * compartilhado → vagas restantes (capacidade − pessoas já reservadas).
     */
    private function left(Space $space, Collection $busy, CarbonInterface $start, CarbonInterface $end): int
    {
        $overlapping = $busy->filter(fn (Reservation $r) => $r->starts_at < $end && $r->ends_at > $start);

        if (! $space->is_shared) {
            return $overlapping->isEmpty() ? 1 : 0;
        }

        return max(0, $space->capacity - (int) $overlapping->sum('guests'));
    }
}
