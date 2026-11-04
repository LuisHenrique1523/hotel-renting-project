<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoomController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly PricingService $pricing,
    ) {}

    public function index(Request $request): View
    {
        [$checkIn, $checkOut, $guests] = $this->searchParams($request);

        $types = $this->availability->roomTypesFor($checkIn, $checkOut, $guests)
            ->each(fn (RoomType $type) => $type->quote = $this->pricing->quoteRoom($type, $checkIn, $checkOut));

        return view('site.rooms.index', compact('types', 'checkIn', 'checkOut', 'guests'));
    }

    public function show(Request $request, RoomType $roomType): View
    {
        [$checkIn, $checkOut, $guests] = $this->searchParams($request);
        $guests = min($guests, $roomType->capacity);

        $this->rememberForLogin($request);

        return view('site.rooms.show', [
            'type' => $roomType,
            'checkIn' => $checkIn,
            'checkOut' => $checkOut,
            'guests' => $guests,
            'quote' => $this->pricing->quoteRoom($roomType, $checkIn, $checkOut),
            'free' => $this->availability->freeRoomsCount($roomType, $checkIn, $checkOut),
            'others' => RoomType::whereKeyNot($roomType->id)->inRandomOrder()->take(3)->get(),
        ]);
    }

    /** Datas da busca com valores padrão seguros (hoje + 1 → +3, 2 hóspedes). */
    private function searchParams(Request $request): array
    {
        $request->validate([
            'check_in' => ['nullable', 'date'],
            'check_out' => ['nullable', 'date'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $checkIn = CarbonImmutable::parse($request->input('check_in', today()->addDay()))->startOfDay();
        if ($checkIn->lt(today())) {
            $checkIn = CarbonImmutable::today();
        }

        $checkOut = CarbonImmutable::parse($request->input('check_out', $checkIn->addDays(2)))->startOfDay();
        if ($checkOut->lte($checkIn)) {
            $checkOut = $checkIn->addDay();
        }
        if ($checkIn->diffInDays($checkOut) > config('hotel.max_nights')) {
            $checkOut = $checkIn->addDays(config('hotel.max_nights'));
        }

        return [$checkIn, $checkOut, (int) $request->input('guests', 2)];
    }
}
