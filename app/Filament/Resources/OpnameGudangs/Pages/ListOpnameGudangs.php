<?php

namespace App\Filament\Resources\OpnameGudangs\Pages;

use App\Filament\Resources\OpnameGudangs\OpnameGudangResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpnameGudangs extends ListRecords
{
    protected static string $resource = OpnameGudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
