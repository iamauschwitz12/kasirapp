<?php

namespace App\Filament\Resources\PenjualanStoks\Pages;

use App\Filament\Resources\PenjualanStoks\PenjualanStokResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePenjualanStok extends CreateRecord
{
    protected static string $resource = PenjualanStokResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record;

        // Mencari produk terkait
        $product = \App\Models\Product::find($record->product_id);

        if ($product) {
            // Menambah stok di tabel product secara otomatis
            $product->increment('stok', $record->qty);
        }
    }
}
