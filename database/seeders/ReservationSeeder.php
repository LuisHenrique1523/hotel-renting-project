<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Professional;
use App\Models\Reservation;
use App\Models\Room;
use App\Models\Space;
use App\Models\User;
use App\Services\PricingService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Gera ~2 meses de histórico realista (35 dias passados, 30 futuros)
 * para que dashboard, gráficos e calendário tenham vida na apresentação.
 */
class ReservationSeeder extends Seeder
{
    private Collection $guests;
    private PricingService $pricing;
    private CarbonImmutable $now;

    public function run(PricingService $pricing): void
    {
        mt_srand(2026); // dados determinísticos: a demo fica igual sempre
        $this->pricing = $pricing;
        $this->now = CarbonImmutable::now();
        $this->guests = User::where('role', 'guest')->where('email', '!=', 'cliente@aurora.test')->get();

        $from = $this->now->subDays(35)->startOfDay();
        $to = $this->now->addDays(30)->startOfDay();

        // Uma única transação: milhares de inserts sem um commit (fsync) cada
        DB::transaction(function () use ($from, $to) {
            $this->seedRooms($from, $to);
            $this->seedSpaces($from, $to);
            $this->seedProfessionals($from, $to);
            $this->seedDemoGuest();
        });
    }

    private function seedRooms(CarbonImmutable $from, CarbonImmutable $to): void
    {
        // Em cada categoria, ~1/3 das unidades fica livre nas próximas 2 semanas:
        // a demonstração sempre encontra disponibilidade (com "últimas unidades" em algumas).
        $demoWindowEnd = $this->now->addDays(14);

        foreach (Room::operational()->with('type')->orderBy('number')->get()->groupBy('room_type_id') as $rooms) {
            $keepFree = (int) max(1, round($rooms->count() / 3));

            foreach ($rooms->values() as $index => $room) {
                $isReserve = $index >= $rooms->count() - $keepFree;
                $cursor = $from->addDays(mt_rand(0, 3));

                while ($cursor < $to) {
                    $nights = mt_rand(1, 5);
                    $checkIn = $cursor;
                    $checkOut = $cursor->addDays($nights);
                    $cursor = $checkOut->addDays(mt_rand(1, 4));

                    if ($isReserve && $checkOut > $this->now && $checkIn < $demoWindowEnd) {
                        continue;
                    }

                    $this->make($room, $checkIn->setTime(14, 0), $checkOut->setTime(12, 0),
                        $this->pricing->quoteRoom($room->type, $checkIn, $checkOut)['total'],
                        mt_rand(1, $room->type->capacity));
                }
            }
        }
    }

    private function seedSpaces(CarbonImmutable $from, CarbonImmutable $to): void
    {
        foreach (Space::all() as $space) {
            for ($day = $from; $day < $to; $day = $day->addDay()) {
                $bookings = $space->type->value === 'event' ? (mt_rand(0, 9) < 3 ? 1 : 0) : mt_rand(0, 3);

                for ($i = 0; $i < $bookings; $i++) {
                    $hours = $space->type->value === 'event' ? mt_rand(3, 6) : mt_rand(1, 3);
                    $startHour = mt_rand($space->opens_hour, $space->closes_hour - $hours);
                    $start = $day->setTime($startHour, 0);
                    $end = $start->addHours($hours);

                    if ($space->isFreeBetween($start, $end)) {
                        $this->make($space, $start, $end, $this->pricing->quoteSpace($space, $start, $end)['total'],
                            mt_rand(2, min(40, $space->capacity)));
                    }
                }
            }
        }
    }

    private function seedProfessionals(CarbonImmutable $from, CarbonImmutable $to): void
    {
        foreach (Professional::with('services', 'workingHours')->get() as $pro) {
            for ($day = $from; $day < $to; $day = $day->addDay()) {
                $shifts = $pro->workingHours->where('weekday', $day->dayOfWeek);

                foreach ($shifts as $shift) {
                    $attempts = mt_rand(1, 3);

                    for ($i = 0; $i < $attempts; $i++) {
                        $service = $pro->services->random();
                        $shiftStart = $day->setTimeFromTimeString($shift->start_time);
                        $shiftEnd = $day->setTimeFromTimeString($shift->end_time);
                        $maxSteps = intdiv($shiftStart->diffInMinutes($shiftEnd) - $service->duration_min, 30);

                        if ($maxSteps < 0) {
                            continue;
                        }

                        $start = $shiftStart->addMinutes(30 * mt_rand(0, $maxSteps));
                        $end = $start->addMinutes($service->duration_min);

                        if ($pro->isFreeBetween($start, $end)) {
                            $this->make($pro, $start, $end, (float) $service->price, 1, $service->id);
                        }
                    }
                }
            }
        }
    }

    /** Histórico da conta de demonstração usada na apresentação. */
    private function seedDemoGuest(): void
    {
        $demo = User::where('email', 'cliente@aurora.test')->first();

        // Reservas aleatórias atribuídas a ela (passadas e futuras) para "Minhas reservas" ficar rico
        Reservation::where('reservable_type', 'room')->where('starts_at', '<', $this->now->subDays(10))
            ->where('status', ReservationStatus::Completed)->orderBy('starts_at')->limit(2)->update(['user_id' => $demo->id]);

        Reservation::where('reservable_type', 'room')->where('starts_at', '>', $this->now->addDays(12))
            ->where('status', ReservationStatus::Confirmed)->orderBy('starts_at')->limit(1)->update(['user_id' => $demo->id]);

        Reservation::where('reservable_type', 'professional')->where('starts_at', '>', $this->now->addDays(3))
            ->where('status', ReservationStatus::Confirmed)->orderBy('starts_at')->limit(1)->update(['user_id' => $demo->id]);

        Reservation::where('reservable_type', 'space')->where('starts_at', '<', $this->now->subDays(5))
            ->where('status', ReservationStatus::Cancelled)->orderBy('starts_at')->limit(1)->update(['user_id' => $demo->id]);
    }

    private function make(Model $reservable, CarbonImmutable $start, CarbonImmutable $end, float $total, int $guests, ?int $serviceId = null): void
    {
        $status = $this->statusFor($start, $end);
        $createdAt = $start->subDays(mt_rand(1, 25))->setTime(mt_rand(8, 22), mt_rand(0, 59));
        $createdAt = $createdAt->min($this->now->subMinutes(mt_rand(5, 600)));

        if ($status === ReservationStatus::Pending) {
            $createdAt = $this->now->subMinutes(mt_rand(1, 20)); // pendentes são sempre recentes
        }

        $reservation = new Reservation([
            'code' => 'AUR-'.Str::upper(Str::random(5)),
            'user_id' => $this->guests->random()->id,
            'service_id' => $serviceId,
            'starts_at' => $start,
            'ends_at' => $end,
            'guests' => $guests,
            'status' => $status,
            'total' => $total,
            'confirmed_at' => $status !== ReservationStatus::Pending ? $createdAt->addMinutes(3) : null,
            'checked_in_at' => in_array($status, [ReservationStatus::CheckedIn, ReservationStatus::Completed]) ? $start->addMinutes(mt_rand(0, 90)) : null,
            'checked_out_at' => $status === ReservationStatus::Completed ? $end->subMinutes(mt_rand(0, 60)) : null,
            'cancelled_at' => in_array($status, [ReservationStatus::Cancelled, ReservationStatus::NoShow]) ? $start->subDays(1) : null,
        ]);
        $reservation->reservable()->associate($reservable);
        $reservation->created_at = $createdAt;
        $reservation->updated_at = $createdAt;
        $reservation->save();

        $method = [PaymentMethod::Pix, PaymentMethod::Pix, PaymentMethod::Card, PaymentMethod::Card, PaymentMethod::OnSite][mt_rand(0, 4)];
        $reservation->payment()->create([
            'amount' => $total,
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
