<?php

namespace App\Filament\Resources\Cabangs\Pages;

use App\Filament\Resources\Cabangs\CabangResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCabang extends EditRecord
{
    protected static string $resource = CabangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
