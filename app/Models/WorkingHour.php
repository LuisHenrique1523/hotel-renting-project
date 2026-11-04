<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    public const WEEKDAYS = [
        0 => 'Domingo',
        1 => 'Segunda-feira',
        2 => 'Terça-feira',
        3 => 'Quarta-feira',
        4 => 'Quinta-feira',
        5 => 'Sexta-feira',
        6 => 'Sábado',
    ];

    protected $fillable = ['professional_id', 'weekday', 'start_time', 'end_time'];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    /** Normaliza para HH:MM:SS (comparações de horário dependem disso). */
    protected function startTime(): Attribute
    {
        return Attribute::set(fn ($value) => Carbon::parse($value)->format('H:i:s'));
    }

    protected function endTime(): Attribute
    {
        return Attribute::set(fn ($value) => Carbon::parse($value)->format('H:i:s'));
    }
}
