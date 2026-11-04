<?php

namespace App\Filament\Resources\ReservationResource\Pages;

use App\Enums\ReservationStatus;
use App\Filament\Resources\ReservationResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListReservations extends ListRecords
{
    protected static string $resource = ReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Nova reserva no balcão')->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        $base = fn () => ReservationResource::getEloquentQuery();

        return [
            'all' => Tab::make('Todas'),
            'today' => Tab::make('Hoje')->icon('heroicon-m-sun')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereDate('starts_at', today()))
                ->badge(fn () => $base()->whereDate('starts_at', today())->count()),
            'pending' => Tab::make('Aguardando pagamento')->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $q) => $q->where('status', ReservationStatus::Pending))
                ->badge(fn () => $base()->where('status', ReservationStatus::Pending)->count())->badgeColor('warning'),
            'room' => Tab::make('Hospedagem')->modifyQueryUsing(fn (Builder $q) => $q->where('reservable_type', 'room')),
            'professional' => Tab::make('Spa')->modifyQueryUsing(fn (Builder $q) => $q->where('reservable_type', 'professional')),
            'space' => Tab::make('Salas')->modifyQueryUsing(fn (Builder $q) => $q->where('reservable_type', 'space')),
        ];
    }
}
