<?php

namespace App\Models;

use App\Enums\BillingUnit;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Espaço reservável de uma unidade (sala, estação, escritório, auditório...). */
class Space extends Model
{
    use HasFactory;

    protected $attributes = ['is_active' => true, 'is_featured' => false];

    protected $fillable = [
        'unit_id', 'space_category_id', 'name', 'slug', 'description', 'capacity', 'size_m2', 'floor',
        'price', 'amenities', 'image', 'is_featured', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'unit_id' => 'integer',
            'space_category_id' => 'integer',
            'amenities' => 'array',
            'price' => 'decimal:2',
            'is_featured' => 'boolean',
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

    // ── Relações ────────────────────────────────────────────────

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(SpaceCategory::class, 'space_category_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    // ── Scopes ──────────────────────────────────────────────────

    /** Espaço, unidade e categoria ativos: o que aparece no site. */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('spaces.is_active', true)
            ->whereHas('unit', fn ($q) => $q->where('is_active', true))
            ->whereHas('category', fn ($q) => $q->where('is_active', true));
    }

    /**
     * O espaço comporta mais uma reserva no intervalo [start, end)?
     *  - exclusivo (sala, escritório): nenhuma reserva ativa pode sobrepor;
     *  - compartilhado (coworking): a soma de pessoas das reservas sobrepostas + as novas cabe na capacidade.
     */
    public function isFreeBetween(CarbonInterface $start, CarbonInterface $end, int $guests = 1): bool
    {
        $overlapping = $this->reservations()->blocking()->overlapping($start, $end);

        if (! $this->is_shared) {
            return ! $overlapping->exists();
        }

        return (int) $overlapping->sum('guests') + $guests <= $this->capacity;
    }

    /** Vagas livres em um intervalo (só faz sentido para espaços compartilhados). */
    public function seatsLeftBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        return max(0, $this->capacity - (int) $this->reservations()->blocking()->overlapping($start, $end)->sum('guests'));
    }

    public function isBookable(): bool
    {
        return $this->is_active && $this->unit?->is_active && $this->category?->is_active;
    }

    public function getIsSharedAttribute(): bool
    {
        return (bool) $this->category?->is_shared;
    }

    // ── Apresentação ────────────────────────────────────────────

    public function getBillingUnitAttribute(): BillingUnit
    {
        return $this->category->billing_unit;
    }

    public function getImageUrlAttribute(): string
    {
        return $this->image
            ? Storage::disk('public')->url($this->image)
            : ($this->category?->image_url ?? asset('images/placeholder.svg'));
    }

    /** "R$ 90,00/h" ou, compartilhado, "R$ 49,00/dia por pessoa" */
    public function getPriceLabelAttribute(): string
    {
        return 'R$ '.number_format((float) $this->price, 2, ',', '.').$this->billing_unit->suffix()
            .($this->is_shared ? ' por pessoa' : '');
    }
}
