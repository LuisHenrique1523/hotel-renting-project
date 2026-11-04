<?php

namespace App\Services;

use App\Models\RoomType;
use App\Models\Service;
use App\Models\Space;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;

/**
 * Cálculo de preços. Isolado para ser reutilizado pelo site, pelo painel e pelos testes.
 */
class PricingService
{
    /**
     * Orçamento de hospedagem: soma diária a diária, com acréscimo em sexta e sábado.
     *
     * @return array{nights:int, items:array<int, array{date:CarbonInterface, price:float, weekend:bool}>, total:float}
     */
    public function quoteRoom(RoomType $type, CarbonInterface $checkIn, CarbonInterface $checkOut): array
    {
        $surcharge = (float) config('hotel.weekend_surcharge');
        $items = [];

        // Cada diária corresponde à noite que começa naquela data
        foreach (CarbonPeriod::create($checkIn->copy()->startOfDay(), $checkOut->copy()->startOfDay()->subDay()) as $night) {
            $weekend = in_array($night->dayOfWeek, [CarbonInterface::FRIDAY, CarbonInterface::SATURDAY], true);
            $price = (float) $type->base_price * ($weekend ? 1 + $surcharge : 1);

            $items[] = ['date' => $night->copy(), 'price' => round($price, 2), 'weekend' => $weekend];
        }

        return [
            'nights' => count($items),
            'items' => $items,
            'total' => round(array_sum(array_column($items, 'price')), 2),
        ];
    }

    /** @return array{hours:float, total:float} */
    public function quoteSpace(Space $space, CarbonInterface $start, CarbonInterface $end): array
    {
        $hours = $start->diffInMinutes($end) / 60;

        return ['hours' => $hours, 'total' => round($hours * (float) $space->hourly_price, 2)];
    }

    public function quoteService(Service $service): float
    {
        return round((float) $service->price, 2);
    }
}
