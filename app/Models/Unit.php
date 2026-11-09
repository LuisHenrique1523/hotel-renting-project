<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Unidade da rede (endereço físico). Cadastrada no painel. */
class Unit extends Model
{
    use HasFactory;

    protected $attributes = ['is_active' => true, 'opens_hour' => 8, 'closes_hour' => 20];

    protected $fillable = [
        'name', 'slug', 'city', 'state', 'neighborhood', 'address', 'zip_code', 'phone', 'email',
        'description', 'image', 'amenities', 'opens_hour', 'closes_hour', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amenities' => 'array',
            'opens_hour' => 'integer',
            'closes_hour' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Unit $unit) {
            $unit->slug ??= Str::slug($unit->name);
            $unit->state = Str::upper($unit->state);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function spaces(): HasMany
    {
        return $this->hasMany(Space::class);
    }

    public function reservations(): HasManyThrough
    {
        return $this->hasManyThrough(Reservation::class, Space::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : asset('images/placeholder.svg');
    }

    /** "São Paulo · SP" */
    public function getLocationAttribute(): string
    {
        return "{$this->city} · {$this->state}";
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address, $this->neighborhood, "{$this->city}/{$this->state}"])->filter()->implode(' · ');
    }

    public function getHoursLabelAttribute(): string
    {
        return "{$this->opens_hour}h às {$this->closes_hour}h";
    }
}
