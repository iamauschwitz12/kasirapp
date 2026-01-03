<?php

namespace App\Filament\Resources\GudangKeluars\Pages;

use App\Filament\Resources\GudangKeluars\GudangKeluarResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGudangKeluar extends EditRecord
{
    protected static string $resource = GudangKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
