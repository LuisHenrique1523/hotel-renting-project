<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RoomType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'tagline', 'description', 'capacity', 'size_m2', 'bed',
        'base_price', 'amenities', 'cover_image', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'base_price' => 'decimal:2',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (RoomType $type) {
            $type->slug ??= Str::slug($type->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }

    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(Reservation::class, Room::class, 'room_type_id', 'reservable_id')
            ->where('reservable_type', 'room');
    }

    public function getCoverUrlAttribute(): string
    {
        return $this->cover_image
            ? Storage::disk('public')->url($this->cover_image)
            : asset('images/placeholder.svg');
    }
}
