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
use Filament\Support\RawJs;
use Filament\Forms\Components\Repeater;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GudangForm
{
    public static function hitungTotal($get, $set): void
    {
        $hargaRaw = $get('harga_beli');
        $qty = (float) ($get('qty') ?? 0);
        $hargaMurni = (float) preg_replace('/[^0-9]/', '', $hargaRaw);

        $set('total_harga', $hargaMurni * $qty);
    }
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema(self::getHeaderFields())
                    ->columnSpanFull(),

                Section::make('Detail Barang')
                    ->schema(self::getProductFields())
                    ->columnSpanFull(),
            ]);
    }

    public static function getHeaderFields(): array
    {
        return [
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
                ->placeholder('Contoh: 2025001')
                ->maxLength(255)
                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                ->dehydrateStateUsing(fn($state) => strtoupper($state)),

            DatePicker::make('tgl_masuk')
                ->label('Tanggal Masuk')
                ->default(now())
                ->required(),

            Select::make('cabang_id')
                ->label('Cabang Tujuan')
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
                ->columnSpanFull(),
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
                ->native(false) // Keep searchable for better UX
                ->autofocus() // Auto-focus so scanner input works immediately
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

            Select::make('unitsatuan_id') // Harus sama dengan nama kolom di migrasi
                ->label('Satuan')
                ->relationship('unitSatuan', 'nama_satuan') // 'unitSatuan' adalah nama fungsi di Model
                ->required()
                ->searchable()
                ->preload()
                ->required()
                ->columnSpanFull(),

            TextInput::make('harga_beli')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->required(fn() => auth()->user()->isAdmin())
                ->disabled(fn() => !auth()->user()->isAdmin())
                ->dehydrated()
                ->default(0)
                ->label('Harga Beli')
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set) {
                    self::hitungTotal($get, $set);
                }),

            TextInput::make('qty')
                ->numeric()
                ->required()
                ->label('Jumlah Masuk')
                ->live(onBlur: true) // Aktifkan mode live
                ->afterStateUpdated(function (Get $get, Set $set) {
                    // Panggil fungsi hitung saat qty berubah
                    self::hitungTotal($get, $set);
                }),

            TextInput::make('total_harga')
                ->mask(RawJs::make('$money($input)'))
                ->stripCharacters(',')
                ->required()
                ->label('Total Harga')
                ->readonly() // Opsional: buat readonly agar user tidak mengedit manual
                ->helperText('Otomatis terhitung (Harga Beli x Qty)'),
        ];
    }
}
