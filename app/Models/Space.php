<?php

namespace App\Models;

use App\Enums\SpaceType;
use App\Models\Concerns\HasReservations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Space extends Model
{
    use HasFactory, HasReservations;

    protected $attributes = ['is_active' => true, 'opens_hour' => 8, 'closes_hour' => 22];

    protected $fillable = [
        'name', 'slug', 'type', 'description', 'capacity', 'hourly_price',
        'amenities', 'image', 'opens_hour', 'closes_hour', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => SpaceType::class,
            'amenities' => 'array',
            'hourly_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Space $space) {
            $space->slug ??= Str::slug($space->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : asset('images/placeholder.svg');
    }

    public function displayName(): string
    {
        return $this->name;
    }
}
