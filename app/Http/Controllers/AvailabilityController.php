<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Space;
use App\Services\AvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints JSON consumidos pelo front (Alpine.js) para montar a grade de horários
 * em tempo real, sem recarregar a página.
 */
class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availability) {}

    public function service(Request $request, Service $service): JsonResponse
    {
        $data = $request->validate([
            'professional' => ['required', 'integer'],
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(config('hotel.booking_horizon_days'))->toDateString()],
        ]);

        $professional = $service->professionals()->where('is_active', true)->findOrFail($data['professional']);

        return response()->json([
            'slots' => $this->availability->professionalSlots($professional, $service, CarbonImmutable::parse($data['date'])),
        ]);
    }

    public function space(Request $request, Space $space): JsonResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(config('hotel.booking_horizon_days'))->toDateString()],
        ]);

        return response()->json([
            'slots' => $this->availability->spaceSlots($space, CarbonImmutable::parse($data['date'])),
        ]);
    }
}
