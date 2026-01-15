<?php

namespace App\Filament\Resources\GudangKeluars\Pages;

use App\Filament\Resources\GudangKeluars\GudangKeluarResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Product;
use App\Models\GudangStok;
use App\Models\BarangMasuk;
use Illuminate\Support\Facades\DB;

class CreateGudangKeluar extends CreateRecord
{
    protected static string $resource = GudangKeluarResource::class;

    protected function afterCreate(): void
    {
        $record = $this->record; // Data barang keluar (yang berisi product_id, cabang_id, dan qty)
        $qtyKeluar = (int) $record->qty;

        // 1. Cari data di tabel gudangs yang sesuai PRODUK dan CABANG
        $stokGudang = \DB::table('gudangs')
            ->where('product_id', $record->product_id)
            ->where('cabang_id', $record->cabang_id) // <--- TAMBAHKAN BARIS INI
            ->where('sisa_stok', '>', 0)
            ->orderBy('created_at', 'asc') // Tetap gunakan FIFO (stok lama habis dulu)
            ->first();

        if ($stokGudang) {
            // 2. Hitung Sisa Baru
            $sisaBaru = (int) $stokGudang->sisa_stok - $qtyKeluar;

            // Pastikan stok tidak menjadi minus jika input user lebih besar dari sisa
            if ($sisaBaru < 0) $sisaBaru = 0;

            // 3. Update kolom sisa_stok di tabel gudangs sesuai ID yang ditemukan
            \DB::table('gudangs')
                ->where('id', $stokGudang->id)
                ->update([
                    'sisa_stok' => $sisaBaru,
                    'updated_at' => now(),
                ]);

            // 4. (Opsional) Jika Anda menggunakan tabel log histori
            \DB::table('gudang_stoks')->insert([
                'product_id' => $record->product_id,
                'jumlah_keluar' => $qtyKeluar,
                'sisa_stok_akhir' => $sisaBaru,
                'keterangan' => "Keluar dari Cabang ID: {$record->cabang_id}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
