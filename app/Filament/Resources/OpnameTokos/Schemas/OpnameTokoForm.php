<?php

namespace App\Filament\Resources\OpnameTokos\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Repeater;
use Illuminate\Support\Facades\DB;

class OpnameTokoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema(self::getHeaderFields())
                    ->columns(2),

                Section::make('Detail Produk')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(self::getProductFields())
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

    public static function getHeaderFields(): array
    {
        return [
            Select::make('toko_id')
                ->label('Toko')
                ->relationship('toko', 'nama_toko')
                ->searchable()
                ->preload()
                ->live() // Make it reactive so product dropdown updates
                ->default(fn() => auth()->user()->toko_id)
                ->disabled(fn() => !auth()->user()->isAdmin() && auth()->user()->toko_id)
                ->dehydrated()
                ->required()
                ->createOptionForm([
                    TextInput::make('nama_toko')->required(),
                    TextInput::make('alamat'),
                    TextInput::make('telepon'),
                ]),

            DatePicker::make('tanggal_opname')
                ->label('Tanggal Opname')
                ->default(now())
                ->required(),

            TextInput::make('pic_opname')
                ->label('PIC Opname')
                ->placeholder('Nama PIC yang melakukan opname')
                ->maxLength(255)
                ->required(),
        ];
    }

    public static function getProductFields(): array
    {
        return [
            Select::make('product_id')
                ->label('Cari Barcode / Produk')
                ->options(function (Get $get) {
                    $tokoId = $get('../../toko_id');

                    if (!$tokoId) {
                        return [];
                    }

                    // Get products that have been received by this toko
                    $productIds = DB::table('penjualan_stoks')
                        ->where('toko_id', $tokoId)
                        ->distinct()
                        ->pluck('product_id');

                    return \App\Models\Product::whereIn('id', $productIds)
                        ->get()
                        ->mapWithKeys(function ($product) {
                            return [$product->id => "{$product->barcode_number} - {$product->nama_produk}"];
                        });
                })
                ->searchable()
                ->preload()
                ->live()
                ->required()
                ->afterStateUpdated(function (Get $get, Set $set, $state) {
                    $product = \App\Models\Product::find($state);
                    $tokoId = $get('../../toko_id');

                    if ($product && $tokoId) {
                        $set('nama_barang', $product->nama_produk);
                        $set('satuan_besar', $product->satuan_besar ?: 'Pcs');
                        $set('isi_konversi', $product->isi_konversi ?: 1);

                        // Calculate stock for THIS toko specifically
                        $stokToko = DB::table('penjualan_stoks')
                            ->where('toko_id', $tokoId)
                            ->where('product_id', $state)
                            ->sum('qty');

                        $set('stok_sistem', $stokToko); // Store-specific stock in Pcs
        
                        // Format stock display
                        $konversi = $product->isi_konversi ?: 1;
                        $satuanBesar = $product->satuan_besar ?: 'Unit';
                        $jumlahBesar = floor($stokToko / $konversi);
                        $pcs = $stokToko % $konversi;

                        if ($jumlahBesar > 0 && $pcs > 0) {
                            $set('stok_sistem_display', "{$jumlahBesar} {$satuanBesar} + {$pcs} Pcs");
                        } elseif ($jumlahBesar > 0) {
                            $set('stok_sistem_display', "{$jumlahBesar} {$satuanBesar}");
                        } else {
                            $set('stok_sistem_display', "{$pcs} Pcs");
                        }
                    } else {
                        $set('nama_barang', '');
                        $set('satuan_besar', '');
                        $set('isi_konversi', 1);
                        $set('stok_sistem', 0);
                        $set('stok_sistem_display', '-');
                    }

                    // Reset physical stock inputs
                    $set('stok_fisik', 0);
                    $set('stok_pcs', 0);
                }),

            TextInput::make('nama_barang')
                ->label('Nama Barang')
                ->disabled()
                ->dehydrated(),

            TextInput::make('satuan_besar')
                ->label('Satuan Besar')
                ->disabled()
                ->dehydrated()
                ->helperText('Satuan: Dus/Kotak/Roll/Ikat'),

            TextInput::make('stok_sistem_display')
                ->label('Stok Sistem Saat Ini')
                ->disabled()
                ->dehydrated(false)
                ->placeholder('-')
                ->helperText('Stok dari database'),

            TextInput::make('stok_sistem')
                ->label('Stok Sistem (Pcs)')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->default(0),

            TextInput::make('isi_konversi')
                ->label('Isi per Satuan Besar')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->default(1)
                ->helperText('Jumlah pcs dalam 1 satuan besar'),

            TextInput::make('stok_fisik')
                ->label('Stok Fisik (Satuan Besar)')
                ->numeric()
                ->default(0)
                ->required()
                ->minValue(0)
                ->live()
                ->helperText('Hasil penghitungan fisik')
                ->afterStateUpdated(function ($set, $get, $state) {
                    self::calculateStockComparison($set, $get);
                }),

            TextInput::make('stok_pcs')
                ->label('Sisa Stok (Pcs)')
                ->numeric()
                ->default(0)
                ->required()
                ->minValue(0)
                ->live()
                ->helperText('Sisa dalam satuan kecil (pieces)')
                ->afterStateUpdated(function ($set, $get, $state) {
                    self::calculateStockComparison($set, $get);
                }),

            TextInput::make('total_fisik_pcs')
                ->label('Total Fisik (Pcs)')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->default(0)
                ->helperText('Otomatis terhitung'),

            TextInput::make('status_opname')
                ->label('Status Opname')
                ->disabled()
                ->dehydrated()
                ->placeholder('Otomatis terisi')
                ->suffixIcon(fn($state) => match ($state) {
                    'Pas' => 'heroicon-o-check-circle',
                    'Lebih' => 'heroicon-o-arrow-up-circle',
                    'Selisih' => 'heroicon-o-arrow-down-circle',
                    default => null,
                })
                ->extraInputAttributes(fn($state) => [
                    'style' => match ($state) {
                        'Pas' => 'color: #10b981; font-weight: bold;',
                        'Lebih' => 'color: #3b82f6; font-weight: bold;',
                        'Selisih' => 'color: #ef4444; font-weight: bold;',
                        default => '',
                    }
                ])
                ->helperText(fn($state) => match ($state) {
                    'Pas' => '✓ Stok sesuai dengan sistem',
                    'Lebih' => '↑ Stok fisik lebih dari sistem',
                    'Selisih' => '↓ Stok fisik kurang dari sistem',
                    default => 'Status akan muncul setelah input stok fisik',
                }),

            Textarea::make('keterangan')
                ->label('Keterangan')
                ->placeholder('Catatan tambahan (opsional)')
                ->rows(3)
                ->columnSpanFull(),
        ];
    }

    /**
     * Calculate stock comparison between physical count and system stock
     */
    private static function calculateStockComparison(Set $set, Get $get): void
    {
        $konversi = (int) $get('isi_konversi') ?: 1;
        $stokFisik = (int) $get('stok_fisik') ?: 0;
        $stokPcs = (int) $get('stok_pcs') ?: 0;
        $stokSistem = (int) $get('stok_sistem') ?: 0;

        // Calculate total physical stock in Pcs
        $totalFisikPcs = ($stokFisik * $konversi) + $stokPcs;
        $set('total_fisik_pcs', $totalFisikPcs);

        // Determine status
        if ($totalFisikPcs < $stokSistem) {
            $set('status_opname', 'Selisih');
        } elseif ($totalFisikPcs > $stokSistem) {
            $set('status_opname', 'Lebih');
        } else {
            $set('status_opname', 'Pas');
        }
    }
}