<?php

namespace App\Filament\Resources\SpaceCategoryResource\Pages;

use App\Filament\Resources\SpaceCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Enums\MaxWidth;

class ManageSpaceCategories extends ManageRecords
{
    protected static string $resource = SpaceCategoryResource::class;

    public function getSubheading(): ?string
    {
        return 'A categoria define como os espaços são cobrados (por hora, dia ou mês) e os limites de cada reserva.';
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nova categoria')->modalWidth(MaxWidth::FiveExtraLarge)];
    }
}
