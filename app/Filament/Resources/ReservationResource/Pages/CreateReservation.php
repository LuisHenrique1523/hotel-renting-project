<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Exceptions\ReservationException;
use App\Filament\Resources\ReservationResource;
use App\Models\Professional;
use App\Models\RoomType;
use App\Models\Service;
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
        $guest = User::findOrFail($data['user_id']);

        try {
            $reservation = match ($data['kind']) {
                'room' => $engine->bookRoom($guest, RoomType::findOrFail($data['room_type_id']),
                    CarbonImmutable::parse($data['check_in']), CarbonImmutable::parse($data['check_out']),
                    (int) $data['guests'], $data['notes'] ?? null),
                'space' => $engine->bookSpace($guest, Space::findOrFail($data['space_id']),
                    CarbonImmutable::parse($data['space_start']), CarbonImmutable::parse($data['space_end']),
                    (int) $data['guests'], $data['notes'] ?? null),
                'professional' => $engine->bookService($guest, Professional::findOrFail($data['professional_id']),
                    Service::findOrFail($data['service_id']), CarbonImmutable::parse($data['service_start']),
                    $data['notes'] ?? null),
            };

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
