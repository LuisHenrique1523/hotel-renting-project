<?php

namespace App\Models;

use App\Models\Concerns\HasReservations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Professional extends Model
{
    use HasFactory, HasReservations;

    protected $attributes = ['is_active' => true];

    protected $fillable = ['user_id', 'specialty', 'bio', 'photo', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class)->orderBy('weekday')->orderBy('start_time');
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }

    public function getPhotoUrlAttribute(): string
    {
        return $this->photo
            ? Storage::disk('public')->url($this->photo)
            : 'https://ui-avatars.com/api/?background=0F1E33&color=E9DCC9&name='.urlencode($this->name ?? 'P');
    }

    public function displayName(): string
    {
        return $this->name ?? 'Profissional';
    }
}
