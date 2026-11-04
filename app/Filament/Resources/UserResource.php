<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administração';
    protected static ?string $modelLabel = 'usuário';
    protected static ?string $pluralModelLabel = 'usuários';

    /** Gestão de usuários e papéis: somente administrador. */
    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->columns(2)->schema([
            Forms\Components\TextInput::make('name')->label('Nome')->required(),
            Forms\Components\TextInput::make('email')->label('E-mail')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('phone')->label('Telefone')->tel(),
            Forms\Components\Select::make('role')->label('Papel')->options(UserRole::class)->required()->default(UserRole::Guest),
            Forms\Components\TextInput::make('password')->label('Senha')->password()->revealable()
                ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText('Deixe em branco para manter a senha atual.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nome')->weight(FontWeight::SemiBold)->searchable()->description(fn (User $record) => $record->email),
                Tables\Columns\TextColumn::make('phone')->label('Telefone')->placeholder('—'),
                Tables\Columns\TextColumn::make('role')->label('Papel')->badge(),
                Tables\Columns\TextColumn::make('reservations_count')->label('Reservas')->counts('reservations')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Cadastro')->date('d/m/Y')->sortable(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('role')->label('Papel')->options(UserRole::class)])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => UserResource\Pages\ManageUsers::route('/')];
    }
}
