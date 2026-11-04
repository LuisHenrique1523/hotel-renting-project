<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Concerns\StaffOnly;
use App\Filament\Resources\ProfessionalResource\Pages;
use App\Models\Professional;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class ProfessionalResource extends Resource
{
    use StaffOnly;

    protected static ?string $model = Professional::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Spa & Bem-estar';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'profissional';
    protected static ?string $pluralModelLabel = 'profissionais';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Perfil')->columns(3)->schema([
                Forms\Components\FileUpload::make('photo')->label('Foto')->image()->avatar()->disk('public')->directory('img')->imageEditor(),
                Forms\Components\Group::make([
                    Forms\Components\Select::make('user_id')->label('Usuário (login)')
                        ->relationship('user', 'name', fn ($query) => $query->where('role', UserRole::Professional))
                        ->searchable()->preload()->required()->unique(ignoreRecord: true)
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')->label('Nome')->required(),
                            Forms\Components\TextInput::make('email')->label('E-mail')->email()->required()->unique('users', 'email'),
                            Forms\Components\TextInput::make('password')->label('Senha')->password()->required()->minLength(8),
                        ])
                        ->createOptionUsing(fn (array $data) => User::create($data + ['role' => UserRole::Professional])->id),
                    Forms\Components\TextInput::make('specialty')->label('Especialidade')->required(),
                    Forms\Components\Toggle::make('is_active')->label('Atendendo')->default(true),
                ])->columnSpan(2),
                Forms\Components\Textarea::make('bio')->label('Mini biografia')->rows(3)->columnSpanFull(),
                Forms\Components\CheckboxList::make('services')->label('Serviços que realiza')
                    ->relationship('services', 'name')->columns(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->label('')->disk('public')->circular()->size(48),
                Tables\Columns\TextColumn::make('user.name')->label('Nome')->weight(FontWeight::SemiBold)->searchable()
                    ->description(fn (Professional $record) => $record->specialty),
                Tables\Columns\TextColumn::make('services.name')->label('Serviços')->badge()->limitList(3),
                Tables\Columns\TextColumn::make('working_hours_count')->label('Turnos/semana')->counts('workingHours'),
                Tables\Columns\TextColumn::make('reservations_count')->label('Atendimentos')->counts('reservations')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Ativo'),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getRelations(): array
    {
        return [ProfessionalResource\RelationManagers\WorkingHoursRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProfessionals::route('/'),
            'create' => Pages\CreateProfessional::route('/create'),
            'edit' => Pages\EditProfessional::route('/{record}/edit'),
        ];
    }
}
