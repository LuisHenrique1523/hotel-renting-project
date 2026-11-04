<?php

namespace App\Models;

use App\Enums\RoomStatus;
use App\Models\Concerns\HasReservations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Room extends Model
{
    use HasFactory, HasReservations;

    protected $attributes = ['status' => 'available'];

    protected $fillable = ['room_type_id', 'number', 'floor', 'status'];

    protected function casts(): array
    {
        return ['status' => RoomStatus::class];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RoomType::class, 'room_type_id');
    }

    public function scopeOperational(Builder $query): Builder
    {
        return $query->where('status', RoomStatus::Available);
    }

    public function displayName(): string
    {
        return "Quarto {$this->number} · {$this->type?->name}";
    }
}
