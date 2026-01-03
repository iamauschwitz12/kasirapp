<?php

namespace App\Filament\Resources\GudangKeluars\Pages;

use App\Filament\Resources\GudangKeluars\GudangKeluarResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGudangKeluars extends ListRecords
{
    protected static string $resource = GudangKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
