<?php

namespace Database\Seeders;

use App\Enums\BillingUnit;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\Space;
use App\Models\User;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gera ~2 meses de histórico realista (35 dias passados, 30 futuros)
 * para que dashboard, gráficos, calendário e mapa de ocupação tenham vida na apresentação.
 */
class ReservationSeeder extends Seeder
{
    private Collection $guests;
    private PricingService $pricing;
    private AvailabilityService $availability;
    private CarbonImmutable $now;

    public function run(PricingService $pricing, AvailabilityService $availability): void
    {
        mt_srand(2026); // dados determinísticos: a demo fica igual sempre
        $this->pricing = $pricing;
        $this->availability = $availability;
        $this->now = CarbonImmutable::now();
        $this->guests = User::where('role', 'guest')->where('email', '!=', 'cliente@aurora.test')->get();

        $from = $this->now->subDays(35)->startOfDay();
        $to = $this->now->addDays(30)->startOfDay();

        // Uma única transação: milhares de inserts sem um commit (fsync) cada
        DB::transaction(function () use ($from, $to) {
            foreach (Space::with('unit', 'category')->get() as $space) {
                match (true) {
                    $space->billing_unit === BillingUnit::Hour => $this->seedHourly($space, $from, $to),
                    $space->billing_unit === BillingUnit::Month => $this->seedMonthly($space),
                    $space->is_shared => $this->seedShared($space, $from, $to),
                    default => $this->seedDaily($space, $from, $to),
                };
            }

            $this->seedDemoGuest();
        });
    }

    /** Salas de reunião e auditórios: alguns blocos de horas por dia útil. */
    private function seedHourly(Space $space, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $min = $space->category->min_quantity;
        $big = $space->capacity >= 50;
        [$opens, $closes] = [$space->unit->opens_hour, $space->unit->closes_hour];

        for ($day = $from; $day < $to; $day = $day->addDay()) {
            if ($day->isSunday()) {
                continue;
            }

            $bookings = $big ? (mt_rand(0, 9) < 3 ? 1 : 0) : mt_rand(0, $day->isSaturday() ? 1 : 4);

            for ($i = 0; $i < $bookings; $i++) {
                $hours = $big ? mt_rand(max($min, 3), 6) : mt_rand($min, 3);
                $start = $day->setTime(mt_rand($opens, $closes - $hours), 0);
                $this->tryBook($space, $start, $hours, mt_rand(2, min(40, $space->capacity)));
            }
        }
    }

    /** Coworking: várias pessoas por dia dividindo o mesmo salão. */
    private function seedShared(Space $space, CarbonImmutable $from, CarbonImmutable $to): void
    {
        for ($day = $from; $day < $to; $day = $day->addDay()) {
            if ($day->isSunday()) {
                continue;
            }

            $bookings = $day->isSaturday() ? mt_rand(1, 3) : mt_rand(4, 10);

            for ($i = 0; $i < $bookings; $i++) {
                $this->tryBook($space, $day, mt_rand(1, 100) <= 80 ? 1 : mt_rand(2, 5), mt_rand(1, 3));
            }
        }
    }

    /** Salas de treinamento: alguns dias inteiros por semana. */
    private function seedDaily(Space $space, CarbonImmutable $from, CarbonImmutable $to): void
    {
        for ($day = $from; $day < $to; $day = $day->addDay()) {
            if (! $day->isWeekend() && mt_rand(1, 100) <= 30) {
                $this->tryBook($space, $day, mt_rand(1, 2), mt_rand(8, $space->capacity));
            }
        }
    }

    /**
     * Escritórios privativos: contratos mensais. Cerca de 1/3 fica livre
     * nas próximas semanas para a demonstração sempre ter o que alugar.
     */
    private function seedMonthly(Space $space): void
    {
        $roll = mt_rand(1, 100);

        if ($roll <= 55) { // contrato em andamento
            $this->tryBook($space, $this->now->subDays(mt_rand(10, 80))->startOfDay(), mt_rand(3, 6), $space->capacity);
        } elseif ($roll <= 70) { // contrato que começa daqui a algumas semanas
            $this->tryBook($space, $this->now->addDays(mt_rand(20, 40))->startOfDay(), mt_rand(6, 12), $space->capacity);
        }

        // Um contrato antigo, já concluído, para o histórico
        $this->tryBook($space, $this->now->subMonths(mt_rand(8, 10))->startOfDay(), mt_rand(1, 3), $space->capacity);
    }

    /** Histórico da conta de demonstração usada na apresentação. */
    private function seedDemoGuest(): void
    {
        $demo = User::where('email', 'cliente@aurora.test')->first();
        $assign = fn ($query, int $limit = 1) => $query->orderBy('starts_at')->limit($limit)->update(['user_id' => $demo->id]);
        $of = fn (BillingUnit $unit) => Reservation::where('billing_unit', $unit->value);

        $assign($of(BillingUnit::Hour)->where('starts_at', '<', $this->now->subDays(10))->where('status', ReservationStatus::Completed), 2);
        $assign($of(BillingUnit::Hour)->where('starts_at', '>', $this->now->addDays(3))->where('status', ReservationStatus::Confirmed));
        $assign($of(BillingUnit::Day)->where('starts_at', '>', $this->now->addDays(6))->where('status', ReservationStatus::Confirmed));
        $assign($of(BillingUnit::Day)->where('starts_at', '<', $this->now->subDays(5))->where('status', ReservationStatus::Cancelled));
    }

    private function tryBook(Space $space, CarbonImmutable $start, int $quantity, int $guests): void
    {
        $guests = max(1, min($guests, $space->capacity));
        [$from, $to] = $this->availability->window($space, $start, $quantity);

        if ($space->isFreeBetween($from, $to, $guests)) {
            $this->make($space, $from, $to, $quantity, $guests);
        }
    }

    private function make(Space $space, CarbonImmutable $start, CarbonImmutable $end, int $quantity, int $guests): void
    {
        $quote = $this->pricing->quote($space, $quantity, $guests);
        $status = $this->statusFor($start, $end);
        $createdAt = $start->subDays(mt_rand(1, 25))->setTime(mt_rand(8, 22), mt_rand(0, 59));
        $createdAt = $createdAt->min($this->now->subMinutes(mt_rand(5, 600)));

        if ($status === ReservationStatus::Pending) {
            $createdAt = $this->now->subMinutes(mt_rand(1, 20)); // pendentes são sempre recentes
        }

        $reservation = new Reservation([
            'code' => 'AUR-'.Str::upper(Str::random(5)),
            'user_id' => $this->guests->random()->id,
            'space_id' => $space->id,
            'billing_unit' => $space->billing_unit,
            'quantity' => $quantity,
            'unit_price' => $quote['unit_price'],
            'starts_at' => $start,
            'ends_at' => $end,
            'guests' => $guests,
            'status' => $status,
            'total' => $quote['total'],
            'confirmed_at' => $status !== ReservationStatus::Pending ? $createdAt->addMinutes(3) : null,
            'checked_in_at' => in_array($status, [ReservationStatus::CheckedIn, ReservationStatus::Completed]) ? $start->addMinutes(mt_rand(0, 90)) : null,
            'checked_out_at' => $status === ReservationStatus::Completed ? $end->subMinutes(mt_rand(0, 60)) : null,
            'cancelled_at' => in_array($status, [ReservationStatus::Cancelled, ReservationStatus::NoShow]) ? $start->subDays(1) : null,
        ]);
        $reservation->created_at = $createdAt;
        $reservation->updated_at = $createdAt;
        $reservation->save();

        $method = [PaymentMethod::Pix, PaymentMethod::Pix, PaymentMethod::Card, PaymentMethod::Card, PaymentMethod::OnSite][mt_rand(0, 4)];
        $reservation->payment()->create([
            'amount' => $quote['total'],
            'method' => $status === ReservationStatus::Pending ? null : $method,
            'status' => match ($status) {
                ReservationStatus::Pending => PaymentStatus::Pending,
                ReservationStatus::Cancelled => PaymentStatus::Refunded,
                default => PaymentStatus::Paid,
            },
            'transaction_id' => $status === ReservationStatus::Pending ? null : 'SIM-'.Str::upper(Str::random(10)),
            'paid_at' => $status === ReservationStatus::Pending ? null : $createdAt->addMinutes(3),
        ]);
    }

    private function statusFor(CarbonImmutable $start, CarbonImmutable $end): ReservationStatus
    {
        $roll = mt_rand(1, 100);

        if ($end <= $this->now) {
            return match (true) {
                $roll <= 7 => ReservationStatus::Cancelled,
                $roll <= 10 => ReservationStatus::NoShow,
                default => ReservationStatus::Completed,
            };
        }

        if ($start <= $this->now) {
            return ReservationStatus::CheckedIn;
        }

        return match (true) {
            $roll <= 5 => ReservationStatus::Cancelled,
            $roll <= 9 && $start->diffInDays($this->now, true) > 2 => ReservationStatus::Pending,
            default => ReservationStatus::Confirmed,
        };
    }
}
