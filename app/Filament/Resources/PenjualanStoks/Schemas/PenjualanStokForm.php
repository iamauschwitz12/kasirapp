<?php

namespace App\Filament\Resources\PenjualanStoks\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;

class PenjualanStokForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('pengirim_id')
                    ->label('Nama Pengirim')
                    ->relationship('pengirim', 'nama_pengirim') // Relasi ke tabel pengirims
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('nama_pengirim')->required(),
                        TextInput::make('telepon'),
                    ]),

                // 2. No Inv Manual
                    TextInput::make('no_inv')
                        ->label('No. Invoice')
                        ->prefix('INV-') 
                        // Memastikan data yang tersimpan di database tetap utuh dengan "INV-"
                        ->dehydrateStateUsing(fn ($state) => str_starts_with($state, 'INV-') ? $state : "INV-{$state}")
                        ->placeholder('Contoh: 2025001')
                        ->maxLength(255)
                        ->required(),

                    // 3. Asal Gudang
                    Select::make('asal_gudang_id')
                    ->label('Asal Gudang')
                    ->relationship('asalGudang', 'nama_gudang') // Relasi ke tabel asal_gudangs
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        TextInput::make('nama_gudang')->required(),
                        TextInput::make('lokasi'),
                    ]),

                    // 4. Kode Barcode
                    Select::make('product_id')
                        ->label('Scan Barcode')
                        ->relationship('product', 'barcode_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->barcode_number} - {$record->nama_produk}")
                    
                        // Aktifkan pencarian untuk kedua kolom tersebut
                        ->searchable(['barcode_number', 'nama_produk'])
                        ->preload() // Memuat data di awal agar cepat
                        ->live()
                        ->searchable()
                        ->afterStateUpdated(function (Set $set, $state) {
                            $product = \App\Models\Product::find($state);
                            $set('nama_barang', $product?->nama_produk);
                        })
                        ->required(),

                    // 5. Nama Barang (Otomatis)
                    TextInput::make('nama_barang')
                        ->label('Nama Barang')
                        ->disabled()
                        ->dehydrated(false),

                    
                    TextInput::make('isi_konversi')
                        ->label('Isi per Satuan Besar')
                        ->numeric()
                        ->required()
                        ->live() // Wajib agar perhitungan di bawahnya langsung jalan saat angka diubah
                        // HAPUS ->readOnly() atau ->disabled() jika ada
                        ->placeholder('Masukkan isi (misal: 10)')
                        ->helperText('Jumlah pcs dalam 1 satuan besar (Dus/Ikat)'),

                    TextInput::make('input_satuan_besar')
                        ->label('Jumlah Satuan Besar')
                        ->numeric()
                        ->default(0)
                        ->live()
                        ->dehydrated(false) // Tidak disimpan langsung ke DB
                        ->afterStateUpdated(function ($set, $get, $state) {
                            $konversi = (int) $get('isi_konversi') ?: 1;
                            $sisa = (int) $get('input_satuan_kecil') ?: 0;
                            // Rumus: (Jumlah Dus * Isi) + Sisa Pcs
                            $set('qty', ((int)$state * $konversi) + $sisa);
                        })
                        ->afterStateHydrated(function ($set, $get, $record) {
                            if ($record) {
                                $konversi = $record->isi_konversi ?: 1;
                                $set('input_satuan_besar', floor($record->qty / $konversi));
                            }
                        }),
                    TextInput::make('input_satuan_kecil')
                        ->label('Sisa (Pcs)')
                        ->numeric()
                        ->default(0)
                        ->live()
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($set, $get, $state) {
                            $konversi = (int) $get('isi_konversi') ?: 1;
                            $dus = (int) $get('input_satuan_besar') ?: 0;
                            // Rumus: (Jumlah Dus * Isi) + Input Pcs Baru
                            $set('qty', ($dus * $konversi) + (int)$state);
                        })
                        ->afterStateHydrated(function ($set, $get, $record) {
                            if ($record) {
                                $konversi = $record->isi_konversi ?: 1;
                                $set('input_satuan_kecil', $record->qty % $konversi);
                            }
                        }),
                    TextInput::make('qty')
                        ->label('Total Stok Akhir (Dalam PCS)')
                        ->numeric()
                        ->required()
                        ->readOnly() // Dikunci agar admin tidak input manual
                        ->helperText('Otomatis terhitung: (Satuan Besar x Isi) + Sisa Pcs')
                        ->live(),
                    // 7. Tgl Barang Masuk
                    DatePicker::make('tgl_masuk')
                        ->label('Tanggal Masuk')
                        ->default(now())
                        ->required(),

                   
            ]);
    }
}
