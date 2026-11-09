<?php

namespace App\Filament\Resources\UnitResource\RelationManagers;

use App\Filament\Resources\SpaceResource;
use App\Models\Space;
use App\Models\SpaceCategory;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;

/** Aba "Espaços" dentro da unidade: cadastra salas, estações e escritórios daquele endereço. */
class SpacesRelationManager extends RelationManager
{
    protected static string $relationship = 'spaces';
    protected static ?string $title = 'Espaços desta unidade';
    protected static ?string $modelLabel = 'espaço';
    protected static ?string $pluralModelLabel = 'espaços';
    protected static ?string $icon = 'heroicon-o-squares-plus';

    public function form(Form $form): Form
    {
        return $form->columns(3)->schema(SpaceResource::formSchema(withUnit: false));
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('category'))
            ->defaultSort('name')
            ->columns(SpaceResource::tableColumns(withUnit: false))
            ->filters([
                Tables\Filters\SelectFilter::make('space_category_id')->label('Categoria')->options(fn () => SpaceCategory::ordered()->pluck('name', 'id')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Novo espaço')->icon('heroicon-o-plus')->modalWidth(MaxWidth::SevenExtraLarge),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->modalWidth(MaxWidth::SevenExtraLarge),
                Tables\Actions\DeleteAction::make()->before(function (Space $record, Tables\Actions\DeleteAction $action) {
                    if ($record->reservations()->exists()) {
                        Notification::make()->title('Não é possível excluir')
                            ->body('Este espaço já tem reservas no histórico. Desligue "Aceita reservas" para tirá-lo do site.')
                            ->danger()->persistent()->send();
                        $action->cancel();
                    }
                }),
            ])
            ->emptyStateHeading('Nenhum espaço cadastrado')
            ->emptyStateDescription('Cadastre as salas, estações de coworking e escritórios desta unidade.');
    }
}
