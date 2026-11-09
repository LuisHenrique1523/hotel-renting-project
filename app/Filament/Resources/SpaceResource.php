<?php

namespace App\Filament\Resources;

use App\Enums\BillingUnit;
use App\Filament\Concerns\GuardsDeletion;
use App\Filament\Concerns\StaffOnly;
use App\Models\Space;
use App\Models\SpaceCategory;
use App\Models\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/** CRUD dos espaços reserváveis de cada unidade. */
class SpaceResource extends Resource
{
    use GuardsDeletion, StaffOnly;

    protected static ?string $model = Space::class;
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'espaço';
    protected static ?string $pluralModelLabel = 'espaços';
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->columns(3)->schema(static::formSchema());
    }

    /**
     * Campos do espaço. Reaproveitado pela aba "Espaços" dentro da unidade,
     * onde a unidade já é conhecida ($withUnit = false).
     */
    public static function formSchema(bool $withUnit = true): array
    {
        return [
            Forms\Components\Section::make('Espaço')->columnSpan(2)->columns(2)->schema([
                Forms\Components\Select::make('unit_id')->label('Unidade')
                    ->options(fn () => Unit::orderBy('name')->pluck('name', 'id'))
                    ->required()->searchable()->preload()
                    ->visible($withUnit),
                Forms\Components\Select::make('space_category_id')->label('Categoria')
                    ->options(fn () => SpaceCategory::ordered()->get()->mapWithKeys(fn ($c) => [$c->id => "{$c->name} · {$c->billing_unit->getLabel()}"]))
                    ->required()->searchable()->live()
                    ->columnSpan($withUnit ? 1 : 2),
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(80)
                    ->placeholder('Ex.: Sala Trianon')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                Forms\Components\TextInput::make('slug')->label('URL amigável')->required()->unique(ignoreRecord: true)->alphaDash(),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->required()->columnSpanFull(),
                Forms\Components\TagsInput::make('amenities')->label('Recursos e comodidades')->placeholder('Ex.: TV 55", Videoconferência')->columnSpanFull(),
                Forms\Components\FileUpload::make('image')->label('Foto')->image()->disk('public')->directory('img')->imageEditor()
                    ->helperText('Sem foto, o site usa a foto da categoria.')->columnSpanFull(),
            ]),

            Forms\Components\Section::make('Capacidade e preço')->columnSpan(1)->schema([
                Forms\Components\TextInput::make('price')->label(fn (Get $get) => 'Preço '.mb_strtolower(static::billingOf($get('space_category_id'))?->getLabel() ?? ''))
                    ->numeric()->prefix('R$')->required()->minValue(1)
                    ->suffix(fn (Get $get) => static::billingOf($get('space_category_id'))?->suffix())
                    ->helperText(fn (Get $get) => SpaceCategory::find($get('space_category_id'))?->is_shared ? 'Categoria compartilhada: preço por pessoa.' : null),
                Forms\Components\TextInput::make('capacity')->label('Capacidade')->numeric()->suffix('pessoas')->required()->minValue(1),
                Forms\Components\TextInput::make('size_m2')->label('Área')->numeric()->suffix('m²'),
                Forms\Components\TextInput::make('floor')->label('Andar / localização')->maxLength(20)->placeholder('Ex.: 12º'),
                Forms\Components\Toggle::make('is_featured')->label('Destaque na página inicial'),
                Forms\Components\Toggle::make('is_active')->label('Aceita reservas')->default(true),
            ]),
        ];
    }

    private static function billingOf(mixed $categoryId): ?BillingUnit
    {
        return $categoryId ? SpaceCategory::find($categoryId)?->billing_unit : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('unit', 'category'))
            ->defaultSort('name')
            ->columns(static::tableColumns())
            ->filters([
                Tables\Filters\SelectFilter::make('unit_id')->label('Unidade')->options(fn () => Unit::orderBy('name')->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('space_category_id')->label('Categoria')->options(fn () => SpaceCategory::ordered()->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aceita reservas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                static::guardDeletion(Tables\Actions\DeleteAction::make(), 'reservations',
                    'Este espaço já tem reservas no histórico. Desligue "Aceita reservas" para tirá-lo do site.'),
            ]);
    }

    /** @return array<Tables\Columns\Column> */
    public static function tableColumns(bool $withUnit = true): array
    {
        return array_values(array_filter([
            Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->height(48)->width(72)->extraImgAttributes(['class' => 'rounded-lg object-cover']),
            Tables\Columns\TextColumn::make('name')->label('Espaço')->weight(FontWeight::SemiBold)->searchable()->sortable()
                ->description(fn (Space $record) => $record->floor ? "Andar {$record->floor}" : null),
            $withUnit ? Tables\Columns\TextColumn::make('unit.name')->label('Unidade')->sortable()
                ->description(fn (Space $record) => $record->unit?->location) : null,
            Tables\Columns\TextColumn::make('category.name')->label('Categoria')->badge()->color('gray')
                ->icon(fn (Space $record) => $record->category?->icon),
            Tables\Columns\TextColumn::make('capacity')->label('Capacidade')->suffix(' pessoas')->sortable(),
            Tables\Columns\TextColumn::make('price')->label('Preço')->sortable()
                ->formatStateUsing(fn (Space $record) => $record->price_label),
            Tables\Columns\TextColumn::make('reservations_count')->label('Reservas')->counts('reservations')->sortable()->toggleable(),
            Tables\Columns\ToggleColumn::make('is_featured')->label('Destaque')->toggleable(),
            Tables\Columns\ToggleColumn::make('is_active')->label('Ativo'),
        ]));
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'unit.name', 'category.name'];
    }

    public static function getPages(): array
    {
        return [
            'index' => SpaceResource\Pages\ListSpaces::route('/'),
            'create' => SpaceResource\Pages\CreateSpace::route('/create'),
            'edit' => SpaceResource\Pages\EditSpace::route('/{record}/edit'),
        ];
    }
}
