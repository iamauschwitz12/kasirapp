<?php

namespace App\Filament\Resources\GudangKeluars\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Closure;
use Illuminate\Support\Facades\DB;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;

class GudangKeluarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Transaksi')
                    ->schema([
                        Select::make('cabang_id')
                            ->label('Cabang Asal')
                            ->relationship('cabang', 'nama_cabang')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(function () {
                                $user = auth()->user();
                                // Auto-fill untuk user gudang
                                if ($user->role === 'gudang' && $user->cabang_id) {
                                    return $user->cabang_id;
                                }
                                return null;
                            })
                            ->disabled(function () {
                                $user = auth()->user();
                                // Disable untuk user gudang
                                return $user->role === 'gudang';
                            })
                            ->dehydrated(true) // Pastikan value tetap terkirim meskipun disabled
                            ->live(), // Make live so we can access it in repeater validation


                        Select::make('toko_id')
                            ->label('Toko Tujuan')
                            ->relationship('toko', 'nama_toko')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nama_toko')->required(),
                                Forms\Components\TextInput::make('alamat'),
                                Forms\Components\TextInput::make('telepon'),
                            ]),

                        TextInput::make('no_referensi')
                            ->label('No. Referensi')
                            ->required()
                            ->placeholder('Contoh: OUT-2025001')
                            ->maxLength(255)
                            ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                            ->dehydrateStateUsing(fn($state) => strtoupper($state)),

                        DatePicker::make('tgl_keluar')
                            ->label('Tanggal Keluar')->default(now())->required(),

                        TextInput::make('keterangan')
                            ->label('Keterangan (Opsional)')->placeholder('Contoh: Barang Rusak / Retur'),
                    ])->columns(2)
                    ->columnSpanFull(),

                Section::make('Detail Barang')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema([
                                Select::make('product_id')
                                    ->label('Scan Barcode')
                                    ->relationship('product', 'barcode_number')
                                    ->autofocus()
                                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->barcode_number} - {$record->nama_produk}")
                                    ->searchable(['barcode_number', 'nama_produk'])
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($set, $state) {
                                        $product = \App\Models\Product::find($state);
                                        if ($product) {
                                            $set('nama_display', $product->nama_produk);
                                        }
                                    })
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(), // Prevent selecting same item twice

                                TextInput::make('nama_display')
                                    ->label('Nama Barang')->disabled()->dehydrated(false),

                                Select::make('unitsatuan_id')
                                    ->label('Satuan')->relationship('unitSatuan', 'nama_satuan')
                                    ->required(),

                                TextInput::make('qty')
                                    ->numeric()
                                    ->required()
                                    ->rules([
                                        fn($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                            $productId = $get('product_id');
                                            // Access parent state for cabang_id since we are in a repeater
                                            $cabangId = $get('../../cabang_id');

                                            if (!$productId || !$cabangId) {
                                                return;
                                            }

                                            $stokTersedia = DB::table('gudangs')
                                                ->where('product_id', $productId)
                                                ->where('cabang_id', $cabangId)
                                                ->sum('sisa_stok');

                                            if ($value > $stokTersedia) {
                                                $fail("Stok tidak cukup. Sisa: {$stokTersedia}");
                                            }
                                        },
                                    ]),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
