<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Enums\BillingUnit;
use App\Exceptions\ReservationException;
use App\Filament\Resources\ReservationResource;
use App\Models\Space;
use App\Models\User;
use App\Services\ReservationService;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;

/**
 * Reserva feita pela recepção (telefone ou balcão).
 * Passa pelo MESMO motor do site: mesmas validações e mesma regra de conflito.
 */
class CreateReservation extends CreateRecord
{
    protected static string $resource = ReservationResource::class;

    protected static ?string $title = 'Nova reserva no balcão';

    protected function handleRecordCreation(array $data): Model
    {
        $engine = app(ReservationService::class);
        $client = User::findOrFail($data['user_id']);
        $space = Space::with('unit', 'category')->findOrFail($data['space_id']);

        $start = CarbonImmutable::parse($data['date'])->startOfDay();
        if ($space->billing_unit === BillingUnit::Hour) {
            $start = $start->setTime((int) $data['start_hour'], 0);
        }

        try {
            $reservation = $engine->book($client, $space, $start, (int) $data['quantity'], (int) $data['guests'], $data['notes'] ?? null);

            if ($data['confirm_now'] ?? false) {
                $engine->confirm($reservation);
            }

            return $reservation;
        } catch (ReservationException $e) {
            Notification::make()->title('Reserva não realizada')->body($e->getMessage())->danger()->persistent()->send();

            throw new Halt;
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Reserva criada com sucesso';
    }

    protected function getRedirectUrl(): string
    {
        return ReservationResource::getUrl('view', ['record' => $this->record]);
    }
}
