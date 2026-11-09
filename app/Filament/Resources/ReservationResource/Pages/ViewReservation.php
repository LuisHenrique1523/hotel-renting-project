<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Filament\Resources\ReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewReservation extends ViewRecord
{
    protected static string $resource = ReservationResource::class;

    public function getTitle(): string
    {
        return 'Reserva '.$this->record->code;
    }

    protected function getHeaderActions(): array
    {
        return [
            ...ReservationResource::workflowActions(Actions\Action::class),
            Actions\Action::make('email')->label('E-mail ao cliente')->icon('heroicon-o-envelope')->color('gray')
                ->url(fn () => route('reservations.email', $this->record), shouldOpenInNewTab: true),
        ];
    }
}
