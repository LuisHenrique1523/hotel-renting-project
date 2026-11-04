<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ReservationResource;
use App\Models\Reservation;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/** Agenda operacional do dia, com as ações de balcão à mão. */
class TodayAgenda extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $heading = 'Agenda de hoje';

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => ReservationResource::getEloquentQuery()
                ->where(fn ($q) => $q->whereDate('starts_at', today())
                    ->orWhere(fn ($q) => $q->where('reservable_type', 'room')->whereDate('ends_at', today())))
                ->whereNotIn('status', ['cancelled']))
            ->defaultSort('starts_at')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->emptyStateHeading('Nada agendado para hoje')
            ->emptyStateIcon('heroicon-o-sun')
            ->columns([
                Tables\Columns\TextColumn::make('when')->label('Horário')->weight(FontWeight::Bold)
                    ->state(fn (Reservation $record) => match (true) {
                        $record->reservable_type !== 'room' => $record->starts_at->format('H:i').'–'.$record->ends_at->format('H:i'),
                        $record->starts_at->isToday() => 'Chegada '.$record->starts_at->format('H\h'),
                        default => 'Saída até '.$record->ends_at->format('H\h'),
                    }),
                Tables\Columns\TextColumn::make('reservable_type')->label('Tipo')->badge()
                    ->formatStateUsing(fn (string $state) => Reservation::KINDS[$state])
                    ->color(fn (string $state) => match ($state) { 'room' => 'gray', 'space' => 'info', default => 'primary' }),
                Tables\Columns\TextColumn::make('title')->label('Recurso')->description(fn (Reservation $record) => $record->subtitle),
                Tables\Columns\TextColumn::make('user.name')->label('Hóspede'),
                Tables\Columns\TextColumn::make('code')->label('Código')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make(ReservationResource::workflowActions())->button()->label('Ações')->color('gray')->size('sm'),
            ])
            ->recordUrl(fn (Reservation $record) => ReservationResource::getUrl('view', ['record' => $record]));
    }
}
