<?php

namespace App\Filament\Resources\OpnameTokos\Pages;

use App\Filament\Resources\OpnameTokos\OpnameTokoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOpnameTokos extends ListRecords
{
    protected static string $resource = OpnameTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
