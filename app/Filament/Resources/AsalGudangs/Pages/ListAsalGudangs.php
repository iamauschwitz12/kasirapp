<?php

namespace App\Filament\Resources\AsalGudangs\Pages;

use App\Filament\Resources\AsalGudangs\AsalGudangResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAsalGudangs extends ListRecords
{
    protected static string $resource = AsalGudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
