<?php

namespace App\Filament\Resources\UnitResource\Pages;

use App\Filament\Resources\UnitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUnit extends EditRecord
{
    protected static string $resource = UnitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('site')->label('Ver no site')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => route('units.show', $this->record), shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->is_active),
        ];
    }
}
