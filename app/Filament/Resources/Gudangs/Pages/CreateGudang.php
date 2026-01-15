<?php

namespace App\Filament\Resources\Gudangs\Pages;

use App\Filament\Resources\Gudangs\GudangResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGudang extends CreateRecord
{
    protected static string $resource = GudangResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Kode ini WAJIB ada di sini agar otomatis mengisi sisa_stok
        $data['sisa_stok'] = $data['qty'];
        
        return $data;
    }
}
