<?php

namespace App\Filament\Imports;

use App\Models\Product;
use App\Models\UnitSatuan;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\DB;

class ProductImporter extends Importer
{
    protected static ?string $model = Product::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('nama_produk')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->label('Nama Produk'),

            ImportColumn::make('harga')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer', 'min:0'])
                ->label('Harga Eceran'),

            ImportColumn::make('harga_grosir')
                ->numeric()
                ->rules(['nullable', 'numeric', 'min:0'])
                ->label('Harga Grosir'),

            ImportColumn::make('unit_satuan_id')
                ->requiredMapping()
                ->label('Nama Satuan (contoh: PCS, BUNGKUS)')
                ->fillRecordUsing(function (Product $record, string $state): void {
                    // Cari unit satuan berdasarkan nama, buat baru jika belum ada
                    $unitSatuan = UnitSatuan::firstOrCreate(
                        ['nama_satuan' => strtoupper(trim($state))]
                    );
                    $record->unit_satuan_id = $unitSatuan->id;
                }),

            ImportColumn::make('satuan_besar')
                ->rules(['nullable', 'string', 'max:255'])
                ->label('Satuan Besar'),

            ImportColumn::make('isi_konversi')
                ->numeric()
                ->rules(['nullable', 'integer', 'min:1'])
                ->label('Isi Konversi'),
        ];
    }

    public function resolveRecord(): ?Product
    {
        // Selalu buat record baru
        return new Product();
    }

    public function beforeFill(): void
    {
        // Generate kode otomatis
        $maxKode = (int) DB::table('products')
            ->selectRaw('MAX(CAST(kode AS UNSIGNED)) as max_kode')
            ->value('max_kode');

        $this->record->kode = $maxKode + 1;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import produk selesai! ' . number_format($import->successful_rows) . ' produk berhasil diimport.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal diimport.';
        }

        return $body;
    }
}
