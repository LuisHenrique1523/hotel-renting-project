<?php

namespace App\Services;

use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Consulta de disponibilidade. Toda verificação usa a MESMA regra de sobreposição
 * (Reservation::scopeOverlapping), seja para quartos, salas ou profissionais.
 */
class AvailabilityService
{
    /** Converte datas de check-in/out na janela real ocupada (14h → 12h). */
    public function stayWindow(CarbonInterface $checkIn, CarbonInterface $checkOut): array
    {
        return [
            CarbonImmutable::parse($checkIn)->setTime((int) config('hotel.check_in_hour'), 0),
            CarbonImmutable::parse($checkOut)->setTime((int) config('hotel.check_out_hour'), 0),
        ];
    }

    /** Tipos de quarto que comportam os hóspedes, com a contagem de unidades livres no período. */
    public function roomTypesFor(CarbonInterface $checkIn, CarbonInterface $checkOut, int $guests = 1): Collection
    {
        [$start, $end] = $this->stayWindow($checkIn, $checkOut);

        return RoomType::query()
            ->where('capacity', '>=', $guests)
            ->withCount([
                'rooms as available_rooms_count' => fn ($q) => $q->operational()->freeBetween($start, $end),
            ])
            ->orderBy('base_price')
            ->get();
    }

    public function freeRoomsCount(RoomType $type, CarbonInterface $checkIn, CarbonInterface $checkOut): int
    {
        [$start, $end] = $this->stayWindow($checkIn, $checkOut);

        return $type->rooms()->operational()->freeBetween($start, $end)->count();
    }

    public function findFreeRoom(RoomType $type, CarbonInterface $start, CarbonInterface $end): ?Room
    {
        return $type->rooms()->operational()->freeBetween($start, $end)->orderBy('number')->first();
    }

    /**
     * Grade de horários de uma sala em um dia (blocos de 1h).
     *
     * @return array<int, array{time:string, start:string, available:bool, reason:?string}>
     */
    public function spaceSlots(Space $space, CarbonInterface $date): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $busy = $this->busyIntervals($space, $day);
        $slots = [];

        for ($h = $space->opens_hour; $h < $space->closes_hour; $h++) {
            $start = $day->setTime($h, 0);
            $slots[] = $this->slot($start, $start->addHour(), $busy);
        }

        return $slots;
    }

    /**
     * Horários livres de um profissional para um serviço: cruza expediente,
     * duração do serviço e reservas existentes.
     *
     * @return array<int, array{time:string, start:string, available:bool, reason:?string}>
     */
    public function professionalSlots(Professional $professional, Service $service, CarbonInterface $date): array
    {
        $day = CarbonImmutable::parse($date)->startOfDay();
        $busy = $this->busyIntervals($professional, $day);
        $step = (int) config('hotel.slot_step_minutes');
        $slots = [];

        $shifts = $professional->workingHours()->where('weekday', $day->dayOfWeek)->get();

        foreach ($shifts as $shift) {
            $cursor = $day->setTimeFromTimeString($shift->start_time);
            $shiftEnd = $day->setTimeFromTimeString($shift->end_time);

            while ($cursor->addMinutes($service->duration_min) <= $shiftEnd) {
                $slots[] = $this->slot($cursor, $cursor->addMinutes($service->duration_min), $busy);
                $cursor = $cursor->addMinutes($step);
            }
        }

        return $slots;
    }

    /** Verifica se o intervalo cabe inteiro dentro de algum turno do profissional. */
    public function withinWorkingHours(Professional $professional, CarbonInterface $start, CarbonInterface $end): bool
    {
        if (! $start->isSameDay($end)) {
            return false;
        }

        return $professional->workingHours()
            ->where('weekday', $start->dayOfWeek)
            ->where('start_time', '<=', $start->format('H:i:s'))
            ->where('end_time', '>=', $end->format('H:i:s'))
            ->exists();
    }

    /** Intervalos ocupados de um recurso em um dia (uma única consulta). */
    private function busyIntervals($reservable, CarbonImmutable $day): Collection
    {
        return $reservable->reservations()
            ->blocking()
            ->overlapping($day, $day->endOfDay())
            ->get(['starts_at', 'ends_at']);
    }

    private function slot(CarbonImmutable $start, CarbonImmutable $end, Collection $busy): array
    {
        $reason = null;

        if ($start->lessThan(now())) {
            $reason = 'past';
        } elseif ($busy->contains(fn (Reservation $r) => $r->starts_at < $end && $r->ends_at > $start)) {
            $reason = 'busy';
        }

        return [
            'time' => $start->format('H:i'),
            'end' => $end->format('H:i'),
            'start' => $start->toIso8601String(),
            'available' => $reason === null,
            'reason' => $reason,
        ];
    }
}
