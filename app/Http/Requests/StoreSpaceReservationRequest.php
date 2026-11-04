<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpaceReservationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_hour' => ['required', 'integer', 'between:0,23'],
            'end_hour' => ['required', 'integer', 'between:1,24', 'gt:start_hour'],
            'guests' => ['required', 'integer', 'min:1', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['date' => 'data', 'start_hour' => 'horário inicial', 'end_hour' => 'horário final', 'guests' => 'participantes'];
    }
}
