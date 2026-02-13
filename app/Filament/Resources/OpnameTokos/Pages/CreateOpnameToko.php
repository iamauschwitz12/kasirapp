<?php

namespace App\Filament\Resources\OpnameTokos\Pages;

use App\Filament\Resources\OpnameTokos\OpnameTokoResource;
use App\Filament\Resources\OpnameTokos\Schemas\OpnameTokoForm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\DB;

class CreateOpnameToko extends CreateRecord
{
    protected static string $resource = OpnameTokoResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $products = $data['products'] ?? [];
        $record = null;

        DB::transaction(function () use ($data, $products, &$record) {
            // Get header data (excluding products repeater)
            $headerData = collect($data)->except('products')->toArray();

            foreach ($products as $productData) {
                // Merge header data with product data
                $createData = array_merge($headerData, $productData);

                // Create record for each product
                $record = static::getModel()::create($createData);
            }
        });

        return $record; // Returns the last created record for redirect
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema(OpnameTokoForm::getHeaderFields())
                    ->columns(2),

                Section::make('Detail Produk')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(OpnameTokoForm::getProductFields())
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['nama_barang'] ?? 'Produk Baru')
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
