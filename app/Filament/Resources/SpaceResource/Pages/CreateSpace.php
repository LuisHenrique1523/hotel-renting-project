<?php

namespace App\Filament\Resources\SpaceResource\Pages;

use App\Filament\Resources\SpaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpace extends CreateRecord
{
    protected static string $resource = SpaceResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
