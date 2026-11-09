<?php

namespace App\Filament\Widgets;

use App\Enums\BillingUnit;
use App\Filament\Resources\ReservationResource;
use App\Models\Reservation;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/** Agenda operacional do dia (reservas que começam hoje), com as ações de balcão à mão. */
class TodayAgenda extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Agenda de hoje';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->isStaff();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => ReservationResource::getEloquentQuery()
                ->whereDate('starts_at', today())
                ->whereNotIn('status', ['cancelled']))
            ->defaultSort('starts_at')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nada começa hoje')
            ->emptyStateIcon('heroicon-o-sun')
            ->columns([
                Tables\Columns\TextColumn::make('when')->label('Horário')->weight(FontWeight::Bold)
                    ->state(fn (Reservation $record) => $record->billing_unit === BillingUnit::Hour
                        ? $record->starts_at->format('H:i').'–'.$record->ends_at->format('H:i')
                        : 'A partir das '.$record->starts_at->format('H\h'))
                    ->description(fn (Reservation $record) => $record->quantity_label),
                Tables\Columns\TextColumn::make('space.name')->label('Espaço')->description(fn (Reservation $record) => $record->subtitle),
                Tables\Columns\TextColumn::make('kind_label')->label('Categoria')->badge()
                    ->color(fn (Reservation $record) => $record->billing_unit->getColor()),
                Tables\Columns\TextColumn::make('user.name')->label('Cliente')
                    ->description(fn (Reservation $record) => $record->guests.' '.($record->guests === 1 ? 'pessoa' : 'pessoas')),
                Tables\Columns\TextColumn::make('code')->label('Código')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make(ReservationResource::workflowActions())->button()->label('Ações')->color('gray')->size('sm'),
            ])
            ->recordUrl(fn (Reservation $record) => ReservationResource::getUrl('view', ['record' => $record]));
    }
}
