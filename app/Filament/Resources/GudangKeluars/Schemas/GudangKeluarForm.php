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

class GudangKeluarForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('cabang_id')
                    ->label('Cabang Tujuan') // LABEL DIGANTI JADI TOKO
                    ->relationship('cabang', 'nama_cabang')
                    ->searchable()
                    ->preload()
                    ->required(),

                Select::make('toko_id')
                    ->label('Toko Tujuan')
                    ->relationship('toko', 'nama_toko') // Relasi ke tabel tokos
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([ // Fitur keren: bisa tambah toko baru langsung dari sini
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
                    ->dehydrateStateUsing(fn ($state) => strtoupper($state)),

                Select::make('product_id')
                    ->label('Scan Barcode')
                    // Parameter pertama adalah nama fungsi di model (product)
                    // Parameter kedua adalah kolom yang ingin ditampilkan (barcode_number)
                    ->relationship('product', 'barcode_number') 
                    ->autofocus()
                    // Agar saat dicari muncul barcode DAN nama barang
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->barcode_number} - {$record->nama_produk}")
                    
                    // Aktifkan pencarian untuk kedua kolom tersebut
                    ->searchable(['barcode_number', 'nama_produk'])
                    ->preload() // Memuat data di awal agar cepat
                    ->live()
                    ->required()
                    ->afterStateUpdated(function ($set, $state) {
                        $product = \App\Models\Product::find($state);
                        if ($product) {
                            $set('nama_display', $product->nama_produk);
                        }
                    }),

                TextInput::make('nama_display')
                    ->label('Nama Barang')->disabled()->dehydrated(false),

                TextInput::make('qty')
                    ->numeric()
                    ->required()
                    ->rules([
                        // Hapus "Get" di depan $get jika masih error namespace, 
                        // atau pastikan sudah pakai namespace Utilities di atas
                        fn ($get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                            $productId = $get('product_id');
                            $cabangId = $get('cabang_id');

                            if (!$productId || !$cabangId) {
                                return;
                            }

                            $stokTersedia = DB::table('gudangs')
                                ->where('product_id', $productId)
                                ->where('cabang_id', $cabangId)
                                ->sum('sisa_stok');

                            if ($value > $stokTersedia) {
                                $fail("Maaf, stok di cabang ini tidak cukup. Tersedia: {$stokTersedia}");
                            }
                        },
                    ]),

                Select::make('unitsatuan_id')
                    ->label('Satuan')->relationship('unitSatuan', 'nama_satuan'),

                DatePicker::make('tgl_keluar')
                    ->label('Tanggal Keluar')->default(now())->required(),

                TextInput::make('keterangan')
                    ->label('Keterangan (Opsional)')->placeholder('Contoh: Barang Rusak / Retur'),
            ]);
    }
}
