<?php

namespace App\Filament\Resources\PenjualanStoks\Pages;

use App\Filament\Resources\PenjualanStoks\PenjualanStokResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPenjualanStok extends EditRecord
{
    protected static string $resource = PenjualanStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
