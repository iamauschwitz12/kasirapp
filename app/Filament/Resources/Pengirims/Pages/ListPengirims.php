<?php

namespace App\Filament\Resources\Pengirims\Pages;

use App\Filament\Resources\Pengirims\PengirimResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPengirims extends ListRecords
{
    protected static string $resource = PengirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
