<?php

namespace App\Filament\Resources;

use App\Enums\RoomStatus;
use App\Filament\Concerns\StaffOnly;
use App\Filament\Resources\RoomTypeResource\Pages;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RoomTypeResource extends Resource
{
    use StaffOnly;

    protected static ?string $model = RoomType::class;
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup = 'Hospedagem';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'categoria de quarto';
    protected static ?string $pluralModelLabel = 'categorias de quarto';
    protected static ?string $navigationLabel = 'Categorias e quartos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Apresentação')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(80)
                    ->live(onBlur: true)->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->label('URL amigável')->required()->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('tagline')->label('Frase de destaque')->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(4)->required()->columnSpanFull(),
                Forms\Components\FileUpload::make('cover_image')->label('Foto de capa')->image()->disk('public')->directory('img')->imageEditor()->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Detalhes e preço')->columns(4)->schema([
                Forms\Components\TextInput::make('base_price')->label('Diária base')->numeric()->prefix('R$')->required()->minValue(1),
                Forms\Components\TextInput::make('capacity')->label('Capacidade')->numeric()->suffix('hóspedes')->required()->minValue(1)->maxValue(10),
                Forms\Components\TextInput::make('size_m2')->label('Área')->numeric()->suffix('m²'),
                Forms\Components\TextInput::make('bed')->label('Cama'),
                Forms\Components\TagsInput::make('amenities')->label('Comodidades')->columnSpan(3)->placeholder('Ex.: Wi-Fi'),
                Forms\Components\Toggle::make('is_featured')->label('Destaque na home')->inline(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('base_price')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image')->label('')->disk('public')->height(56)->width(80)->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                Tables\Columns\TextColumn::make('name')->label('Categoria')->weight(FontWeight::SemiBold)->searchable()->description(fn (RoomType $record) => $record->tagline),
                Tables\Columns\TextColumn::make('capacity')->label('Capacidade')->suffix(' hóspedes'),
                Tables\Columns\TextColumn::make('rooms_count')->label('Unidades')->counts('rooms')->badge(),
                Tables\Columns\TextColumn::make('base_price')->label('Diária base')->money('BRL', locale: 'pt_BR')->sortable(),
                Tables\Columns\ToggleColumn::make('is_featured')->label('Destaque'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [RoomTypeResource\RelationManagers\RoomsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit' => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }
}
