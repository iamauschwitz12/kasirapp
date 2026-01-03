<?php

namespace App\Filament\Resources\PenjualanStoks\Pages;

use App\Filament\Resources\PenjualanStoks\PenjualanStokResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPenjualanStoks extends ListRecords
{
    protected static string $resource = PenjualanStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
