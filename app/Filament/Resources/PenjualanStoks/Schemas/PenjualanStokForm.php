<?php

namespace App\Filament\Resources\PenjualanStoks\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;

class PenjualanStokForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema(self::getHeaderFields())
                    ->columns(2),

                Section::make('Detail Barang')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(self::getProductFields())
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                            ->reorderableWithButtons()
                            ->deletable(fn() => auth()->user()->isAdmin())
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getHeaderFields(): array
    {
        return [
            Select::make('pengirim_id')
                ->label('Nama Pengirim')
                ->relationship('pengirim', 'nama_pengirim')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    TextInput::make('nama_pengirim')->required(),
                    TextInput::make('telepon'),
                ]),

            TextInput::make('no_inv')
                ->label('No. Invoice')
                ->placeholder('Contoh: INV-2025001')
                ->maxLength(255)
                ->required()
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->dehydrateStateUsing(fn($state) => strtoupper($state)),

            Select::make('toko_id')
                ->label('Toko Tujuan')
                ->relationship('toko', 'nama_toko')
                ->searchable()
                ->preload()
                ->default(fn() => auth()->user()->toko_id)
                ->disabled(fn() => !auth()->user()->isAdmin() && auth()->user()->toko_id)
                ->dehydrated()
                ->required()
                ->createOptionForm([
                    TextInput::make('nama_toko')->required(),
                    TextInput::make('alamat'),
                    TextInput::make('telepon'),
                ]),

            Select::make('asal_gudang_id')
                ->label('Asal Gudang')
                ->relationship('cabang', 'nama_cabang')
                ->searchable()
                ->preload()
                ->required()
                ->createOptionForm([
                    TextInput::make('nama_cabang')->required(),
                    TextInput::make('lokasi'),
                ]),

            DatePicker::make('tgl_masuk')
                ->label('Tanggal Masuk')
                ->default(now())
                ->required(),
        ];
    }

    public static function getProductFields(): array
    {
        return [
            Select::make('product_id')
                ->label('Cari Barcode / Produk')
                ->relationship('product', 'barcode_number')
                ->getOptionLabelFromRecordUsing(fn($record) => "{$record->barcode_number} - {$record->nama_produk}")
                ->searchable(['barcode_number', 'nama_produk'])
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    $product = \App\Models\Product::find($state);
                    if ($product) {
                        $set('nama_barang', $product->nama_produk);
                        $set('isi_konversi', $product->isi_konversi); // Auto-fill conversion if available
                    } else {
                        $set('nama_barang', '');
                    }
                }),

            TextInput::make('nama_barang')
                ->label('Nama Barang')
                ->disabled()
                ->dehydrated(false),

            TextInput::make('isi_konversi')
                ->label('Isi per Satuan Besar')
                ->numeric()
                ->required()
                ->live()
                ->placeholder('Masukkan isi (misal: 10)')
                ->helperText('Jumlah pcs dalam 1 satuan besar (Dus/Ikat)'),

            TextInput::make('input_satuan_besar')
                ->label('Jumlah Satuan Besar')
                ->numeric()
                ->default(0)
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(function ($set, $get, $state) {
                    $konversi = (int) $get('isi_konversi') ?: 1;
                    $sisa = (int) $get('input_satuan_kecil') ?: 0;
                    $set('qty', ((int) $state * $konversi) + $sisa);
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
                    $set('qty', ($dus * $konversi) + (int) $state);
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
                ->readOnly()
                ->helperText('Otomatis terhitung: (Satuan Besar x Isi) + Sisa Pcs')
                ->live(),
        ];
    }
}
