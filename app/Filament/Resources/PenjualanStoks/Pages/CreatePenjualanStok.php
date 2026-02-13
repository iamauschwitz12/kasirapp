<?php

namespace App\Filament\Resources\PenjualanStoks\Pages;

use App\Filament\Resources\PenjualanStoks\PenjualanStokResource;
use App\Filament\Resources\PenjualanStoks\Schemas\PenjualanStokForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\DB;

class CreatePenjualanStok extends CreateRecord
{
    protected static string $resource = PenjualanStokResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $products = $data['products'] ?? [];
        $record = null;

        DB::transaction(function () use ($data, $products, &$record) {
            // Ambil data header (selain products)
            $headerData = collect($data)->except('products')->toArray();

            foreach ($products as $productData) {
                // Gabungkan data header dengan data produk
                $createData = array_merge($headerData, $productData);

                // Buat record
                $record = static::getModel()::create($createData);

                // Update Stok Produk (Logic from previous afterCreate)
                $product = \App\Models\Product::find($createData['product_id']);
                if ($product) {
                    $product->increment('stok', $createData['qty']);
                }
            }
        });

        return $record; // Returns the last created record, sufficient for redirect
    }

    // Disable the default afterCreate since we handled it in the transaction loop
    protected function afterCreate(): void
    {
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema(PenjualanStokForm::getHeaderFields())
                    ->columns(2),

                Section::make('Detail Barang')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(PenjualanStokForm::getProductFields())
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                            ->reorderableWithButtons()
                            ->deletable(fn() => auth()->user()->isAdmin())
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
