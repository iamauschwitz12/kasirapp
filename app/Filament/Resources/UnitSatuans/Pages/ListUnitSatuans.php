<?php

namespace App\Filament\Resources\UnitSatuans\Pages;

use App\Filament\Resources\UnitSatuans\UnitSatuanResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUnitSatuans extends ListRecords
{
    protected static string $resource = UnitSatuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
