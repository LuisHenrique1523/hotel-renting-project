<?php

namespace App\Filament\Resources\SpaceResource\Pages;

use App\Filament\Resources\SpaceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSpace extends EditRecord
{
    protected static string $resource = SpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('site')->label('Ver no site')->icon('heroicon-o-arrow-top-right-on-square')->color('gray')
                ->url(fn () => route('spaces.show', $this->record), shouldOpenInNewTab: true)
                ->visible(fn () => $this->record->isBookable()),
        ];
    }
}
