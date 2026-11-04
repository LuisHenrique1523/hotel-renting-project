<?php

namespace App\Filament\Resources\RoomTypeResource\RelationManagers;

use App\Enums\RoomStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RoomsRelationManager extends RelationManager
{
    protected static string $relationship = 'rooms';
    protected static ?string $title = 'Unidades (quartos físicos)';
    protected static ?string $modelLabel = 'quarto';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')->label('Número')->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('floor')->label('Andar')->numeric()->required(),
            Forms\Components\Select::make('status')->label('Situação')->options(RoomStatus::class)->default(RoomStatus::Available)->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('number')
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('Quarto')->weight('bold'),
                Tables\Columns\TextColumn::make('floor')->label('Andar'),
                Tables\Columns\TextColumn::make('status')->label('Situação')->badge(),
                Tables\Columns\TextColumn::make('reservations_count')->label('Reservas')->counts('reservations'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
