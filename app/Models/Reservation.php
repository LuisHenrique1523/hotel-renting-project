<?php

namespace App\Models;

use App\Enums\ReservationStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Reservation extends Model
{
    use HasFactory;

    /** Tipos do morph map (ver AppServiceProvider). */
    public const KIND_ROOM = 'room';
    public const KIND_SPACE = 'space';
    public const KIND_PROFESSIONAL = 'professional';

    public const KINDS = [
        self::KIND_ROOM => 'Hospedagem',
        self::KIND_SPACE => 'Sala',
        self::KIND_PROFESSIONAL => 'Spa & Serviços',
    ];

    protected $fillable = [
        'code', 'user_id', 'reservable_type', 'reservable_id', 'service_id',
        'starts_at', 'ends_at', 'guests', 'status', 'total', 'notes',
        'confirmed_at', 'checked_in_at', 'checked_out_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReservationStatus::class,
            'user_id' => 'integer',
            'reservable_id' => 'integer',
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

    public function reservable(): MorphTo
    {
        return $this->morphTo();
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
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

    public function scopeOfKind(Builder $query, string $kind): Builder
    {
        return $query->where('reservable_type', $kind);
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

    public function getKindAttribute(): string
    {
        return $this->reservable_type;
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->reservable_type] ?? 'Reserva';
    }

    public function getTitleAttribute(): string
    {
        return match ($this->reservable_type) {
            self::KIND_ROOM => $this->reservable?->type?->name ?? 'Hospedagem',
            self::KIND_SPACE => $this->reservable?->name ?? 'Sala',
            self::KIND_PROFESSIONAL => $this->service?->name ?? 'Serviço',
            default => 'Reserva',
        };
    }

    public function getSubtitleAttribute(): string
    {
        return match ($this->reservable_type) {
            self::KIND_ROOM => 'Quarto '.$this->reservable?->number,
            self::KIND_SPACE => $this->reservable?->type?->getLabel() ?? '',
            self::KIND_PROFESSIONAL => 'com '.$this->reservable?->name,
            default => '',
        };
    }

    public function getImageUrlAttribute(): string
    {
        return match ($this->reservable_type) {
            self::KIND_ROOM => $this->reservable?->type?->cover_url,
            self::KIND_SPACE => $this->reservable?->image_url,
            self::KIND_PROFESSIONAL => $this->service?->image_url,
            default => null,
        } ?? asset('images/placeholder.svg');
    }

    public function getNightsAttribute(): int
    {
        return (int) $this->starts_at->copy()->startOfDay()->diffInDays($this->ends_at->copy()->startOfDay());
    }

    /** Texto do período adaptado ao tipo de reserva. */
    public function getPeriodLabelAttribute(): string
    {
        if ($this->reservable_type === self::KIND_ROOM) {
            $n = $this->nights;

            return $this->starts_at->translatedFormat('d M').' → '.$this->ends_at->translatedFormat('d M Y')
                ." · {$n} ".($n === 1 ? 'diária' : 'diárias');
        }

        return mb_convert_case($this->starts_at->translatedFormat('D, d M Y'), MB_CASE_TITLE).' · '
            .$this->starts_at->format('H:i').'–'.$this->ends_at->format('H:i');
    }

    /** Pendente (não paga): pode desistir a qualquer momento. Confirmada: até 24h antes do início. */
    public function canBeCancelledByGuest(): bool
    {
        return match ($this->status) {
            ReservationStatus::Pending => true,
            ReservationStatus::Confirmed => now()->diffInHours($this->starts_at, false) >= (int) config('hotel.guest_cancellation_hours'),
            default => false,
        };
    }
}
