<?php

namespace App\Filament\Resources\Pengirims\Pages;

use App\Filament\Resources\Pengirims\PengirimResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPengirim extends EditRecord
{
    protected static string $resource = PengirimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
