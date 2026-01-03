<?php

namespace App\Filament\Resources\UnitSatuans\Pages;

use App\Filament\Resources\UnitSatuans\UnitSatuanResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUnitSatuan extends EditRecord
{
    protected static string $resource = UnitSatuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
