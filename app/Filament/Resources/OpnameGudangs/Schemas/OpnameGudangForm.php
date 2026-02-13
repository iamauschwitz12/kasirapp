<?php

namespace App\Filament\Resources\OpnameGudangs\Schemas;

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

class OpnameGudangForm
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
            TextInput::make('cabang_id')
                ->default(fn() => auth()->user()->cabang_id)
                ->hidden()
                ->dehydrated(),

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
                ->options(function () {
                    $user = auth()->user();

                    // WAREHOUSE ONLY: Get products from gudangs table
                    $query = DB::table('gudangs')->distinct();

                    // Filter by cabang_id if user is not admin
                    if ($user && $user->role !== 'admin' && $user->cabang_id) {
                        $query->where('cabang_id', $user->cabang_id);
                    }

                    $productIds = $query->pluck('product_id');

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
                    $user = auth()->user();

                    if ($product) {
                        $set('nama_barang', $product->nama_produk);
                        $set('satuan_besar', $product->satuan_besar ?: 'Pcs');

                        // WAREHOUSE ONLY: Calculate stock from gudangs table (sisa_stok)
                        $query = DB::table('gudangs')->where('product_id', $state);

                        // Filter by cabang_id if user is not admin
                        if ($user && $user->role !== 'admin' && $user->cabang_id) {
                            $query->where('cabang_id', $user->cabang_id);
                        }

                        $stokGudang = $query->sum('sisa_stok');
                        $set('stok_sistem', $stokGudang);
                    } else {
                        $set('nama_barang', '');
                        $set('satuan_besar', '');
                        $set('stok_sistem', 0);
                    }

                    // Reset physical stock input
                    $set('stok_fisik', 0);
                }),

            TextInput::make('nama_barang')
                ->label('Nama Barang')
                ->disabled()
                ->dehydrated(),

            TextInput::make('satuan_besar')
                ->hidden()
                ->dehydrated(),

            TextInput::make('stok_sistem')
                ->label('Stok Sistem Saat Ini (Gudang)')
                ->numeric()
                ->disabled()
                ->dehydrated()
                ->default(0)
                ->helperText('Stok dari database gudang'),

            TextInput::make('stok_fisik')
                ->label('Stok Fisik (Hasil Hitung)')
                ->numeric()
                ->default(0)
                ->required()
                ->minValue(0)
                ->live()
                ->helperText('Hasil penghitungan fisik')
                ->afterStateUpdated(function ($set, $get, $state) {
                    self::calculateStockComparison($set, $get);
                }),

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
        $stokFisik = (int) $get('stok_fisik') ?: 0;
        $stokSistem = (int) $get('stok_sistem') ?: 0;

        // Determine status
        if ($stokFisik < $stokSistem) {
            $set('status_opname', 'Selisih');
        } elseif ($stokFisik > $stokSistem) {
            $set('status_opname', 'Lebih');
        } else {
            $set('status_opname', 'Pas');
        }
    }
}
