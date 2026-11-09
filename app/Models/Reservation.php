<?php

namespace App\Models;

use App\Enums\BillingUnit;
use App\Enums\ReservationStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'user_id', 'space_id', 'billing_unit', 'quantity', 'unit_price',
        'starts_at', 'ends_at', 'guests', 'status', 'total', 'notes',
        'confirmed_at', 'checked_in_at', 'checked_out_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'billing_unit' => BillingUnit::class,
            'user_id' => 'integer',
            'space_id' => 'integer',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'total' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Reservation $reservation) {
            $reservation->code ??= static::generateCode();
        });
    }

    public static function generateCode(): string
    {
        do {
            $code = 'AUR-'.Str::upper(Str::random(5));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    // ── Relações ────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // ── Scopes: o coração da regra de conflito ─────────────────

    /** Interseção de intervalos semiabertos: A.início < B.fim E A.fim > B.início */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): Builder
    {
        return $query->where('starts_at', '<', $end)->where('ends_at', '>', $start);
    }

    /** Reservas que ocupam a agenda (canceladas e no-show liberam o horário). */
    public function scopeBlocking(Builder $query): Builder
    {
        return $query->whereIn('status', ReservationStatus::blocking());
    }

    public function scopeBilledBy(Builder $query, BillingUnit|string $unit): Builder
    {
        return $query->where('billing_unit', $unit instanceof BillingUnit ? $unit->value : $unit);
    }

    public function scopeInUnit(Builder $query, int $unitId): Builder
    {
        return $query->whereHas('space', fn ($q) => $q->where('unit_id', $unitId));
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('ends_at', '>=', now())
            ->whereIn('status', [ReservationStatus::Pending, ReservationStatus::Confirmed, ReservationStatus::CheckedIn]);
    }

    public function scopeRevenue(Builder $query): Builder
    {
        return $query->whereIn('status', [ReservationStatus::Confirmed, ReservationStatus::CheckedIn, ReservationStatus::Completed]);
    }

    // ── Apresentação ────────────────────────────────────────────

    public function getTitleAttribute(): string
    {
        return $this->space?->name ?? 'Espaço removido';
    }

    /** Nome da categoria: "Sala de reunião". */
    public function getKindLabelAttribute(): string
    {
        return $this->space?->category?->name ?? 'Reserva';
    }

    /** "Aurora Paulista · São Paulo/SP" */
    public function getSubtitleAttribute(): string
    {
        $unit = $this->space?->unit;

        return $unit ? "{$unit->name} · {$unit->city}/{$unit->state}" : '';
    }

    public function getImageUrlAttribute(): string
    {
        return $this->space?->image_url ?? asset('images/placeholder.svg');
    }

    /** "3 horas", "5 dias", "6 meses" */
    public function getQuantityLabelAttribute(): string
    {
        return $this->billing_unit->quantityLabel($this->quantity);
    }

    /** Texto do período adaptado à forma de cobrança. */
    public function getPeriodLabelAttribute(): string
    {
        if ($this->billing_unit === BillingUnit::Hour) {
            return mb_convert_case($this->starts_at->translatedFormat('D, d M Y'), MB_CASE_TITLE).' · '
                .$this->starts_at->format('H:i').'–'.$this->ends_at->format('H:i');
        }

        if ($this->quantity === 1 && $this->billing_unit === BillingUnit::Day) {
            return mb_convert_case($this->starts_at->translatedFormat('D, d M Y'), MB_CASE_TITLE).' · dia inteiro';
        }

        return $this->starts_at->translatedFormat('d M Y').' → '.$this->ends_at->translatedFormat('d M Y').' · '.$this->quantity_label;
    }

    /** Pendente (não paga): pode desistir a qualquer momento. Confirmada: até 24h antes do início. */
    public function canBeCancelledByGuest(): bool
    {
        return match ($this->status) {
            ReservationStatus::Pending => true,
            ReservationStatus::Confirmed => now()->diffInHours($this->starts_at, false) >= (int) config('aurora.guest_cancellation_hours'),
            default => false,
        };
    }
}
