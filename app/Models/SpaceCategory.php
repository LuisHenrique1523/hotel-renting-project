<?php

namespace App\Models;

use App\Enums\BillingUnit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Categoria de espaço (sala de reunião, coworking, escritório privativo...).
 * Define a forma de cobrança e os limites de duração de toda reserva dos seus espaços.
 */
class SpaceCategory extends Model
{
    use HasFactory;

    protected $attributes = ['is_active' => true, 'is_shared' => false, 'min_quantity' => 1, 'sort_order' => 0];

    protected $fillable = [
        'name', 'slug', 'tagline', 'description', 'billing_unit', 'min_quantity', 'max_quantity', 'is_shared',
        'icon', 'image', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'billing_unit' => BillingUnit::class,
            'min_quantity' => 'integer',
            'max_quantity' => 'integer',
            'is_shared' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (SpaceCategory $category) {
            $category->slug ??= Str::slug($category->name);
            $category->max_quantity ??= $category->billing_unit?->defaultMax() ?? 12;
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

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : asset('images/placeholder.svg');
    }
}
