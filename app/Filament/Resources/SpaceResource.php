<?php

namespace App\Filament\Resources;

use App\Enums\SpaceType;
use App\Filament\Concerns\StaffOnly;
use App\Models\Space;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SpaceResource extends Resource
{
    use StaffOnly;

    protected static ?string $model = Space::class;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationGroup = 'Salas & Eventos';
    protected static ?string $modelLabel = 'espaço';
    protected static ?string $pluralModelLabel = 'espaços';
    protected static ?string $navigationLabel = 'Salas e espaços';

    public static function form(Form $form): Form
    {
        return $form->columns(4)->schema([
            Forms\Components\TextInput::make('name')->label('Nome')->required()->columnSpan(2)
                ->live(onBlur: true)->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')->label('URL amigável')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('type')->label('Tipo')->options(SpaceType::class)->required(),
            Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->required()->columnSpanFull(),
            Forms\Components\TextInput::make('capacity')->label('Capacidade')->numeric()->suffix('pessoas')->required(),
            Forms\Components\TextInput::make('hourly_price')->label('Valor/hora')->numeric()->prefix('R$')->required(),
            Forms\Components\TextInput::make('opens_hour')->label('Abre às')->numeric()->minValue(0)->maxValue(23)->suffix('h')->required(),
            Forms\Components\TextInput::make('closes_hour')->label('Fecha às')->numeric()->minValue(1)->maxValue(24)->suffix('h')->gt('opens_hour')->required(),
            Forms\Components\TagsInput::make('amenities')->label('Recursos')->columnSpan(3),
            Forms\Components\Toggle::make('is_active')->label('Aceita reservas')->inline(false)->default(true),
            Forms\Components\FileUpload::make('image')->label('Foto')->image()->disk('public')->directory('img')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->height(56)->width(80)->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                Tables\Columns\TextColumn::make('name')->label('Espaço')->weight(FontWeight::SemiBold)->searchable(),
                Tables\Columns\TextColumn::make('type')->label('Tipo')->badge(),
                Tables\Columns\TextColumn::make('capacity')->label('Capacidade')->suffix(' pessoas'),
                Tables\Columns\TextColumn::make('opens_hour')->label('Horário')->formatStateUsing(fn (Space $record) => "{$record->opens_hour}h às {$record->closes_hour}h"),
                Tables\Columns\TextColumn::make('hourly_price')->label('Valor/hora')->money('BRL', locale: 'pt_BR')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Ativo'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => SpaceResource\Pages\ManageSpaces::route('/')];
    }
}
