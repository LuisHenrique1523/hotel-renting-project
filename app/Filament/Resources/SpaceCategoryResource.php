<?php

namespace App\Filament\Resources;

use App\Enums\BillingUnit;
use App\Filament\Concerns\GuardsDeletion;
use App\Filament\Concerns\StaffOnly;
use App\Models\SpaceCategory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/** CRUD das categorias de espaço e da sua forma de cobrança. */
class SpaceCategoryResource extends Resource
{
    use GuardsDeletion, StaffOnly;

    protected static ?string $model = SpaceCategory::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Cadastros';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'categoria';
    protected static ?string $pluralModelLabel = 'categorias';
    protected static ?string $slug = 'categories';

    /** Ícones oferecidos no cadastro (Heroicons). */
    public const ICONS = [
        'heroicon-o-user-group' => 'Pessoas (reunião)',
        'heroicon-o-computer-desktop' => 'Computador (estação)',
        'heroicon-o-building-office' => 'Prédio (escritório)',
        'heroicon-o-building-office-2' => 'Prédios',
        'heroicon-o-academic-cap' => 'Capelo (treinamento)',
        'heroicon-o-megaphone' => 'Megafone (auditório)',
        'heroicon-o-presentation-chart-bar' => 'Apresentação',
        'heroicon-o-video-camera' => 'Câmera (estúdio)',
        'heroicon-o-phone' => 'Telefone (cabine)',
        'heroicon-o-home-modern' => 'Casa',
        'heroicon-o-sparkles' => 'Brilho (evento)',
        'heroicon-o-cube' => 'Cubo (genérico)',
    ];

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\Section::make('Identificação')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->label('Nome')->required()->maxLength(80)
                    ->placeholder('Ex.: Sala de reunião')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                Forms\Components\TextInput::make('slug')->label('URL amigável')->required()->unique(ignoreRecord: true)->alphaDash(),
                Forms\Components\TextInput::make('tagline')->label('Frase curta')->maxLength(120)->columnSpanFull(),
                Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->columnSpanFull(),
                Forms\Components\Select::make('icon')->label('Ícone')->options(self::ICONS)->native(false)->searchable(),
                Forms\Components\TextInput::make('sort_order')->label('Ordem de exibição')->numeric()->default(0)->minValue(0),
                Forms\Components\FileUpload::make('image')->label('Foto de capa')->image()->disk('public')->directory('img')->imageEditor()->columnSpanFull(),
            ])->columnSpan(1),

            Forms\Components\Section::make('Cobrança e regras de reserva')
                ->description('Vale para todos os espaços desta categoria.')
                ->schema([
                    Forms\Components\ToggleButtons::make('billing_unit')->label('Forma de cobrança')
                        ->options(BillingUnit::class)->inline()->required()->live()
                        ->default(BillingUnit::Hour)
                        ->afterStateUpdated(fn (Set $set, $state) => $state ? $set('max_quantity', BillingUnit::from($state instanceof BillingUnit ? $state->value : $state)->defaultMax()) : null),
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('min_quantity')->label('Mínimo por reserva')->numeric()->required()->default(1)->minValue(1)
                            ->suffix(fn (Get $get) => self::unitName($get('billing_unit'))),
                        Forms\Components\TextInput::make('max_quantity')->label('Máximo por reserva')->numeric()->required()->default(12)->minValue(1)
                            ->gte('min_quantity')
                            ->suffix(fn (Get $get) => self::unitName($get('billing_unit'))),
                    ]),
                    Forms\Components\Toggle::make('is_shared')->label('Uso compartilhado')
                        ->helperText(new HtmlString('Várias pessoas reservam o mesmo espaço ao mesmo tempo até lotar a capacidade, e o preço é <b>por pessoa</b>. Use para coworking/estações. Desligado: cada reserva ocupa o espaço inteiro.')),
                    Forms\Components\Toggle::make('is_active')->label('Ativa (aparece no site)')->default(true),
                ])->columnSpan(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->height(48)->width(72)->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                Tables\Columns\TextColumn::make('name')->label('Categoria')->weight(FontWeight::SemiBold)->searchable()
                    ->icon(fn (SpaceCategory $record) => $record->icon)
                    ->description(fn (SpaceCategory $record) => $record->tagline),
                Tables\Columns\TextColumn::make('billing_unit')->label('Cobrança')->badge(),
                Tables\Columns\TextColumn::make('limits')->label('Duração por reserva')
                    ->state(fn (SpaceCategory $record) => $record->min_quantity === $record->max_quantity
                        ? $record->billing_unit->quantityLabel($record->min_quantity)
                        : "{$record->min_quantity} a {$record->billing_unit->quantityLabel($record->max_quantity)}"),
                Tables\Columns\IconColumn::make('is_shared')->label('Compartilhado')->boolean()->alignCenter(),
                Tables\Columns\TextColumn::make('spaces_count')->label('Espaços')->counts('spaces')->badge()->color('gray'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Ativa'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('billing_unit')->label('Cobrança')->options(BillingUnit::class),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->modalWidth(\Filament\Support\Enums\MaxWidth::FiveExtraLarge),
                static::guardDeletion(Tables\Actions\DeleteAction::make(), 'spaces',
                    'Há espaços cadastrados nesta categoria. Mova-os para outra categoria ou desative a categoria.'),
            ]);
    }

    private static function unitName(mixed $state): string
    {
        $unit = $state instanceof BillingUnit ? $state : BillingUnit::tryFrom((string) $state);

        return $unit?->unitName() ?? '';
    }

    public static function getPages(): array
    {
        return ['index' => SpaceCategoryResource\Pages\ManageSpaceCategories::route('/')];
    }
}
