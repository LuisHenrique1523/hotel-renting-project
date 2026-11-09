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
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('starts_at', today()))
                ->badge(fn () => $base()->whereDate('starts_at', today())->count()),
            'pending' => Tab::make('Aguardando pagamento')->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', ReservationStatus::Pending))
                ->badge(fn () => $base()->where('status', ReservationStatus::Pending)->count())->badgeColor('warning'),
            'hour' => Tab::make('Por hora')->icon('heroicon-m-clock')->modifyQueryUsing(fn (Builder $query) => $query->billedBy('hour')),
            'day' => Tab::make('Por dia')->icon('heroicon-m-sun')->modifyQueryUsing(fn (Builder $query) => $query->billedBy('day')),
            'month' => Tab::make('Mensais')->icon('heroicon-m-calendar')->modifyQueryUsing(fn (Builder $query) => $query->billedBy('month')),
        ];
    }
}
