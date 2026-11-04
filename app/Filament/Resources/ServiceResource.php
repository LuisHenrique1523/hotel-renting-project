<?php

namespace App\Filament\Resources;

use App\Enums\ServiceCategory;
use App\Filament\Concerns\StaffOnly;
use App\Models\Service;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ServiceResource extends Resource
{
    use StaffOnly;

    protected static ?string $model = Service::class;
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Spa & Bem-estar';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'serviço';
    protected static ?string $pluralModelLabel = 'serviços';

    public static function form(Form $form): Form
    {
        return $form->columns(4)->schema([
            Forms\Components\TextInput::make('name')->label('Nome')->required()->columnSpan(2)
                ->live(onBlur: true)->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),
            Forms\Components\TextInput::make('slug')->label('URL amigável')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('category')->label('Categoria')->options(ServiceCategory::class)->required(),
            Forms\Components\Textarea::make('description')->label('Descrição')->rows(3)->required()->columnSpanFull(),
            Forms\Components\TextInput::make('duration_min')->label('Duração')->numeric()->suffix('min')->step(10)->minValue(10)->required(),
            Forms\Components\TextInput::make('price')->label('Preço')->numeric()->prefix('R$')->required(),
            Forms\Components\Select::make('professionals')->label('Profissionais')->relationship('professionals', 'specialty')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->name.' · '.$record->specialty)->multiple()->preload()->columnSpan(2),
            Forms\Components\FileUpload::make('image')->label('Foto')->image()->disk('public')->directory('img')->columnSpan(3),
            Forms\Components\Toggle::make('is_active')->label('Disponível')->inline(false)->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('category')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('')->disk('public')->height(56)->width(80)->extraImgAttributes(['class' => 'rounded-lg object-cover']),
                Tables\Columns\TextColumn::make('name')->label('Serviço')->weight(FontWeight::SemiBold)->searchable(),
                Tables\Columns\TextColumn::make('category')->label('Categoria')->badge(),
                Tables\Columns\TextColumn::make('duration_label')->label('Duração'),
                Tables\Columns\TextColumn::make('price')->label('Preço')->money('BRL', locale: 'pt_BR')->sortable(),
                Tables\Columns\TextColumn::make('professionals_count')->label('Profissionais')->counts('professionals')->badge(),
                Tables\Columns\TextColumn::make('reservations_count')->label('Agendamentos')->counts('reservations')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Ativo'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('category')->label('Categoria')->options(ServiceCategory::class)])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ServiceResource\Pages\ManageServices::route('/')];
    }
}
