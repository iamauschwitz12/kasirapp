<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cast to unsigned int for correct numeric MAX on string column
        $maxKode = (int) DB::table('products')->selectRaw('MAX(CAST(kode AS UNSIGNED)) as max_kode')->value('max_kode');
        $data['kode'] = $maxKode + 1;

        return $data;
    }
}
