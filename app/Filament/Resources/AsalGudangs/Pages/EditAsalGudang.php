<?php

namespace App\Filament\Resources\AsalGudangs\Pages;

use App\Filament\Resources\AsalGudangs\AsalGudangResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAsalGudang extends EditRecord
{
    protected static string $resource = AsalGudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
