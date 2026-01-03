<?php

namespace App\Filament\Resources\Tokos\Pages;

use App\Filament\Resources\Tokos\TokoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditToko extends EditRecord
{
    protected static string $resource = TokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
