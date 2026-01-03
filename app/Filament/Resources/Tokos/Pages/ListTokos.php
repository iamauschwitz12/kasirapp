<?php

namespace App\Filament\Resources\Tokos\Pages;

use App\Filament\Resources\Tokos\TokoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTokos extends ListRecords
{
    protected static string $resource = TokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
