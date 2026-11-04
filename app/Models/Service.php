<?php

namespace App\Models;

use App\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Service extends Model
{
    use HasFactory;

    protected $attributes = ['is_active' => true];

    protected $fillable = ['name', 'slug', 'category', 'description', 'duration_min', 'price', 'image', 'is_active'];

    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Service $service) {
            $service->slug ??= Str::slug($service->name);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : asset('images/placeholder.svg');
    }

    public function getDurationLabelAttribute(): string
    {
        $h = intdiv($this->duration_min, 60);
        $m = $this->duration_min % 60;

        return trim(($h ? "{$h}h" : '').($m ? " {$m}min" : ''));
    }
}
