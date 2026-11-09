<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\GuardsDeletion;
use App\Filament\Concerns\StaffOnly;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/** CRUD das unidades (endereços) da rede. Os espaços são cadastrados dentro de cada unidade. */
class UnitResource extends Resource
{
    use GuardsDeletion, StaffOnly;

    protected static ?string $model = Unit::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'unidade';
    protected static ?string $pluralModelLabel = 'unidades';
    protected static ?string $recordTitleAttribute = 'name';

    public const STATES = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR',
        'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema([
            Forms\Components\Section::make('Apresentação')->columnSpan(2)->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome da unidade')->required()->maxLength(80)
                    ->placeholder('Ex.: Aurora Paulista')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                Forms\Components\TextInput::make('slug')->label('URL amigável')->required()->unique(ignoreRecord: true)->alphaDash(),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->required()->columnSpanFull(),
                Forms\Components\TagsInput::make('amenities')->label('Comodidades da unidade')->placeholder('Ex.: Recepção, Café, Estacionamento')->columnSpanFull(),
                Forms\Components\FileUpload::make('image')->label('Foto da fachada ou recepção')->image()->disk('public')->directory('img')->imageEditor()->columnSpanFull(),
            ]),

            Forms\Components\Group::make()->columnSpan(1)->schema([
                Forms\Components\Section::make('Endereço')->columns(2)->schema([
                    Forms\Components\TextInput::make('address')->label('Logradouro, número e andar')->required()->columnSpanFull(),
                    Forms\Components\TextInput::make('neighborhood')->label('Bairro'),
                    Forms\Components\TextInput::make('zip_code')->label('CEP')->mask('99999-999')->placeholder('00000-000'),
                    Forms\Components\TextInput::make('city')->label('Cidade')->required()
                        ->datalist(fn () => Unit::distinct()->orderBy('city')->pluck('city')->all()),
                    Forms\Components\Select::make('state')->label('UF')->options(array_combine(self::STATES, self::STATES))->required()->searchable(),
                ]),
                Forms\Components\Section::make('Funcionamento e contato')->columns(2)->schema([
                    Forms\Components\TextInput::make('opens_hour')->label('Abre às')->numeric()->minValue(0)->maxValue(23)->suffix('h')->required()->default(8),
                    Forms\Components\TextInput::make('closes_hour')->label('Fecha às')->numeric()->minValue(1)->maxValue(24)->suffix('h')->required()->default(20)->gt('opens_hour'),
                    Forms\Components\TextInput::make('phone')->label('Telefone')->tel()->mask('(99) 9999-9999'),
                    Forms\Components\TextInput::make('email')->label('E-mail')->email(),
                    Forms\Components\Toggle::make('is_active')->label('Unidade ativa (aparece no site)')->default(true)->columnSpanFull(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('city')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->height(56)->width(80)->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                Tables\Columns\TextColumn::make('name')->label('Unidade')->weight(FontWeight::SemiBold)->searchable()->sortable()
                    ->description(fn (Unit $record) => $record->address),
                Tables\Columns\TextColumn::make('city')->label('Cidade')->sortable()->searchable()
                    ->formatStateUsing(fn (Unit $record) => "{$record->city}/{$record->state}")
                    ->description(fn (Unit $record) => $record->neighborhood),
                Tables\Columns\TextColumn::make('opens_hour')->label('Horário')->formatStateUsing(fn (Unit $record) => $record->hours_label),
                Tables\Columns\TextColumn::make('spaces_count')->label('Espaços')->counts('spaces')->badge()->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Ativa'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('city')->label('Cidade')
                    ->options(fn () => Unit::distinct()->orderBy('city')->pluck('city', 'city')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                static::guardDeletion(Tables\Actions\DeleteAction::make(), 'spaces',
                    'Esta unidade tem espaços cadastrados. Exclua ou mova os espaços antes, ou desative a unidade.'),
            ]);
    }

    public static function getRelations(): array
    {
        return [UnitResource\RelationManagers\SpacesRelationManager::class];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'city', 'neighborhood'];
    }

    public static function getPages(): array
    {
        return [
            'index' => UnitResource\Pages\ListUnits::route('/'),
            'create' => UnitResource\Pages\CreateUnit::route('/create'),
            'edit' => UnitResource\Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
