<?php

namespace App\Http\Controllers;

use App\Enums\BillingUnit;
use App\Models\Reservation;
use App\Models\Space;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints JSON consumidos pelo front (Alpine.js) para mostrar a agenda
 * e o orçamento em tempo real, sem recarregar a página.
 */
class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    /** Grade de horários de um dia (espaços cobrados por hora). */
    public function hours(Request $request, Space $space): JsonResponse
    {
        $space = $this->bookable($space);
        abort_unless($space->billing_unit === BillingUnit::Hour, 404);

        $data = $request->validate(['date' => $this->dateRule()]);

        return response()->json([
            'slots' => $this->availability->hourSlots($space, CarbonImmutable::parse($data['date'])),
        ]);
    }

    /** Dias livres/ocupados dentro do horizonte de reserva (espaços por dia ou por mês). */
    public function days(Space $space): JsonResponse
    {
        $space = $this->bookable($space);

        return response()->json([
            'days' => $this->availability->dayCalendar($space, (int) config('aurora.booking_horizon_days') + 1),
        ]);
    }

    /** Orçamento + checagem de conflito para "início + quantidade". */
    public function quote(Request $request, Space $space): JsonResponse
    {
        $space = $this->bookable($space);
        $category = $space->category;
        $hourly = $space->billing_unit === BillingUnit::Hour;

        $data = $request->validate([
            'date' => $this->dateRule(),
            'start_hour' => [$hourly ? 'required' : 'nullable', 'integer', 'between:0,23'],
            'quantity' => ['required', 'integer', "between:{$category->min_quantity},{$category->max_quantity}"],
            'guests' => ['nullable', 'integer', 'min:1', "max:{$space->capacity}"],
        ]);

        $guests = (int) ($data['guests'] ?? 1);
        $start = CarbonImmutable::parse($data['date'])->setTime($hourly ? (int) $data['start_hour'] : 0, 0);
        [$from, $to] = $this->availability->window($space, $start, (int) $data['quantity']);
        $quote = $this->pricing->quote($space, (int) $data['quantity'], $guests);

        // Reserva "de mentira" (não salva) só para reaproveitar a formatação do período
        $preview = new Reservation([
            'starts_at' => $from, 'ends_at' => $to,
            'billing_unit' => $space->billing_unit, 'quantity' => $quote['quantity'],
        ]);

        return response()->json([
            'available' => (! $hourly || $start->isFuture()) && $space->isFreeBetween($from, $to, $guests),
            'seats_left' => $space->is_shared ? $space->seatsLeftBetween($from, $to) : null,
            'starts_at' => $from->toIso8601String(),
            'ends_at' => $to->toIso8601String(),
            'period' => $preview->period_label,
            'quantity_label' => $quote['label'],
            'total' => $quote['total'],
        ]);
    }

    private function bookable(Space $space): Space
    {
        $space->loadMissing('unit', 'category');
        abort_unless($space->isBookable(), 404);

        return $space;
    }

    private function dateRule(): array
    {
        return ['required', 'date_format:Y-m-d', 'after_or_equal:today',
            'before_or_equal:'.today()->addDays((int) config('aurora.booking_horizon_days'))->toDateString()];
    }
}
