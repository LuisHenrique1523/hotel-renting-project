<?php

namespace App\Filament\Resources\ProfessionalResource\RelationManagers;

use App\Models\WorkingHour;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WorkingHoursRelationManager extends RelationManager
{
    protected static string $relationship = 'workingHours';
    protected static ?string $title = 'Expediente semanal';
    protected static ?string $modelLabel = 'turno';

    public function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Select::make('weekday')->label('Dia da semana')->options(WorkingHour::WEEKDAYS)->required(),
            Forms\Components\TimePicker::make('start_time')->label('Início')->seconds(false)->required(),
            Forms\Components\TimePicker::make('end_time')->label('Fim')->seconds(false)->after('start_time')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('weekday')
            ->columns([
                Tables\Columns\TextColumn::make('weekday')->label('Dia')->formatStateUsing(fn (int $state) => WorkingHour::WEEKDAYS[$state])->weight('semibold'),
                Tables\Columns\TextColumn::make('start_time')->label('Início')->time('H:i'),
                Tables\Columns\TextColumn::make('end_time')->label('Fim')->time('H:i'),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }
}
