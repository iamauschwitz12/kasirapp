<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use UnitEnum;
use App\Models\Gudang;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;

class StokGudang extends Page
{
    protected string $view = 'filament.pages.stok-gudang';
    protected static string | UnitEnum | null $navigationGroup = 'Gudang Manajemen';

    public $search = '';
    public $cabang_id = '';

    public function getViewData(): array
    {
        $listCabang = DB::table('cabangs')->select('id', 'nama_cabang')->get();
        
        $query = DB::table('gudangs')
            ->join('products', 'gudangs.product_id', '=', 'products.id')
            ->leftJoin('unit_satuans', 'gudangs.unitsatuan_id', '=', 'unit_satuans.id') 
            ->select(
                'products.nama_produk',
                'gudangs.product_id',
                'unit_satuans.nama_satuan AS label_satuan', 
                DB::raw('SUM(gudangs.sisa_stok) as total_sisa')
            );

        // Tambahkan logika pencarian jika variabel search diisi
        if (!empty($this->search)) {
            $query->where('products.nama_produk', 'like', '%' . $this->search . '%');
        }

        if (!empty($this->cabang_id)) {
            $query->where('gudangs.cabang_id', $this->cabang_id);
        }

        $stokProduk = $query->groupBy('gudangs.product_id', 'products.nama_produk', 'unit_satuans.nama_satuan')
            ->get();

        return [
            'stokProduk' => $stokProduk,
            'listCabang' => $listCabang,
        ];
    }
}
