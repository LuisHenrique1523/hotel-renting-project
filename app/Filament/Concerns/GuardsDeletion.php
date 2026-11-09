<?php

namespace App\Filament\Concerns;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Impede excluir um cadastro que ainda tem dependentes (ex.: unidade com espaços,
 * espaço com reservas). O banco também bloqueia (restrictOnDelete); aqui o usuário
 * recebe uma explicação em vez de um erro.
 */
trait GuardsDeletion
{
    protected static function guardDeletion(Action|TableAction $action, string $relation, string $message): Action|TableAction
    {
        return $action->before(function (Model $record, Action|TableAction $action) use ($relation, $message) {
            if ($record->{$relation}()->exists()) {
                Notification::make()->title('Não é possível excluir')->body($message)->danger()->persistent()->send();
                $action->cancel();
            }
        });
    }
}
