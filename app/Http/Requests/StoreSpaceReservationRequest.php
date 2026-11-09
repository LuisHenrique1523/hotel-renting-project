<?php

namespace App\Http\Requests;

use App\Enums\BillingUnit;
use App\Models\Space;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação de formato da reserva. As regras de negócio (conflito, expediente,
 * limites da categoria) ficam no ReservationService.
 */
class StoreSpaceReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        /** @var Space $space */
        $space = $this->route('space');
        $hourly = $space->billing_unit === BillingUnit::Hour;

        return [
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_hour' => [$hourly ? 'required' : 'nullable', 'integer', 'between:0,23'],
            'quantity' => ['required', 'integer', 'min:1', 'max:365'],
            'guests' => ['required', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'date' => 'data',
            'start_hour' => 'horário de início',
            'quantity' => 'duração',
            'guests' => 'pessoas',
        ];
    }
}
