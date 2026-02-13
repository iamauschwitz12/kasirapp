<?php

namespace App\Filament\Resources\Gudangs\Pages;

use App\Filament\Resources\Gudangs\GudangResource;
use App\Filament\Resources\Gudangs\Schemas\GudangForm;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Components\Section;
use Illuminate\Contracts\View\View;


class CreateGudang extends CreateRecord
{
    protected static string $resource = GudangResource::class;


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

                // Set sisa_stok sama dengan qty
                $createData['sisa_stok'] = $createData['qty'];

                // Set user who created this record
                $createData['user_id'] = auth()->id();

                // Buat record
                $record = static::getModel()::create($createData);
            }
        });

        return $record;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema(GudangForm::getHeaderFields())
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Detail Barang')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(GudangForm::getProductFields())
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                            ->reorderableWithButtons()
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function getFooter(): ?View
    {
        return view('filament.resources.gudangs.barcode-scanner');
    }

    public function findProductByBarcode($barcode)
    {
        $product = \App\Models\Product::where('barcode_number', $barcode)->first();

        if ($product) {
            return [
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'barcode_number' => $product->barcode_number,
                    'nama_produk' => $product->nama_produk,
                ]
            ];
        }

        return ['success' => false];
    }
}
