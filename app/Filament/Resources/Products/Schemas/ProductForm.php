<?php

namespace App\Filament\Resources\Products\Schemas;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Support\RawJs;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Support\RawPhp\RawPhp;

use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 TextInput::make('nama_produk')
                ->placeholder('Masukan nama produk')
                ->required(),
                 TextInput::make('kode')
                ->placeholder('Masukan harga produk')
                ->unique(ignoreRecord: true)
                ->required(),
                TextInput::make('harga')
                ->label('Harga Eceran (Pcs)')
                ->numeric()
                ->prefix('Rp'),
                TextInput::make('harga_grosir')
                ->label('Harga Grosir')
                ->numeric()
                ->prefix('Rp'),
                TextInput::make('satuan_besar')
                ->label('Nama Satuan Grosir')
                ->placeholder('Contoh: Dus / Kotak'),
                TextInput::make('isi_konversi')
                ->label('Isi (Jumlah Eceran dalam 1 Satuan Grosir)')
                ->numeric()
                ->default(1),
                Select::make('unit_satuan_id')
                ->relationship('unitSatuan', 'nama_satuan')
                ->searchable()
                ->preload()
                ->required(),
                TextInput::make('isi_konversi')
                        ->label('Isi per Satuan Besar')
                        ->numeric()
                        ->required()
                        ->live() // Memantau perubahan secara real-time
                        ->helperText('Contoh: 1 Kotak isi 10 Pcs, maka isi 10'),
                TextInput::make('input_satuan_besar')
                    ->label('Jumlah Masuk')
                    ->numeric()
                    ->live()
                    ->dehydrated(false)
                    // HAPUS "Set $set" dan "Get $get", ganti jadi "$set" dan "$get" saja
                    ->afterStateUpdated(function ($set, $get, $state) { 
                        $konversi = (int) $get('isi_konversi') ?: 1;
                        
                        // Melakukan perhitungan
                        $hasil = (int) $state * $konversi;
                        
                        // Mengisi field stok
                        $set('stok', $hasil);
                    }),
                TextInput::make('stok')
                    ->label('Total Stok (Pcs)')
                    ->numeric()
                    ->live() // Tambahkan ini agar reaktif
                    ->readOnly(),
            ]);
    }
}
