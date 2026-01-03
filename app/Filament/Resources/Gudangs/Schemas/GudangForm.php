<?php

namespace App\Filament\Resources\Gudangs\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\Card;

class GudangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('supplier_id')
                    ->label('Supplier / Pemasok')
                    ->relationship('supplier', 'nama_supplier')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nama_supplier')->required(),
                        Forms\Components\TextInput::make('kontak'),
                        Forms\Components\Textarea::make('alamat'),
                    ])
                    ->required(),
                TextInput::make('no_invoice')
                        ->label('No. Invoice')
                        ->required()
                        // Menambahkan teks tetap di depan kotak input
                        ->prefix('INV-') 
                        // Memastikan data yang tersimpan di database tetap utuh dengan "INV-"
                        ->dehydrateStateUsing(fn ($state) => str_starts_with($state, 'INV-') ? $state : "INV-{$state}")
                        ->placeholder('Contoh: 2025001')
                        ->maxLength(255),

                    Select::make('product_id')
                        ->label('Cari Barcode / Produk')
                        ->relationship('product', 'barcode_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->barcode_number} - {$record->nama_produk}")
                        ->searchable()
                        ->preload()
                        ->live()
                        ->required()
                        // Mengambil nama produk saat barcode dipilih
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $product = \App\Models\Product::find($state);
                            if ($product) {
                                $set('nama_display', $product->nama_produk);
                            } else {
                                $set('nama_display', '');
                            }
                        }),

                    TextInput::make('nama_display')
                        ->label('Nama Barang')
                        ->disabled() // Hanya untuk konfirmasi visual
                        ->dehydrated(false), // Tidak dikirim ke database gudang

                    Select::make('cabang_id')
                        ->label('Cabang Tujuan')
                        ->relationship('cabang', 'nama_cabang')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),
                        
                    Select::make('unitsatuan_id') // Harus sama dengan nama kolom di migrasi
                        ->label('Satuan')
                        ->relationship('unitSatuan', 'nama_satuan') // 'unitSatuan' adalah nama fungsi di Model
                        ->required()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->columnSpanFull(),

                    TextInput::make('qty')
                        ->label('Jumlah Barang Masuk')
                        ->numeric()
                        ->required()
                        ->suffix('Item'),

                    DatePicker::make('tgl_masuk')
                        ->label('Tanggal Masuk')
                        ->default(now())
                        ->required(),
            ]);
    }
}
