<?php

namespace App\Services;

use App\Models\Space;

/**
 * Cálculo de preços. Isolado para ser reutilizado pelo site, pelo painel e pelos testes.
 * O preço do espaço vale por unidade de cobrança da categoria (hora, dia ou mês).
 * Em categorias compartilhadas (coworking) o preço é por pessoa.
 */
class PricingService
{
    /** @return array{quantity:int, guests:int, unit_price:float, total:float, label:string} */
    public function quote(Space $space, int $quantity, int $guests = 1): array
    {
        $unitPrice = round((float) $space->price, 2);
        $people = $space->is_shared ? max(1, $guests) : 1;

        return [
            'quantity' => $quantity,
            'guests' => $people,
            'unit_price' => $unitPrice,
            'total' => round($unitPrice * $quantity * $people, 2),
            'label' => $space->billing_unit->quantityLabel($quantity),
        ];
    }
}
