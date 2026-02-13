<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;

class LowStockProductsWidget extends Widget
{
    protected string $view = 'filament.widgets.low-stock-products-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1; // Show at top of dashboard

    public static function canView(): bool
    {
        // Only show to gudang users
        return auth()->user()->role === 'gudang';
    }

    #[Computed]
    public function products()
    {
        $user = auth()->user();

        // If user doesn't have cabang_id, return empty collection
        if (!$user || !$user->cabang_id) {
            return collect([]);
        }

        // Get products with low stock (< 10) from user's warehouse
        return DB::table('gudangs')
            ->join('products', 'gudangs.product_id', '=', 'products.id')
            ->leftJoin('unit_satuans', 'gudangs.unitsatuan_id', '=', 'unit_satuans.id')
            ->leftJoin('cabangs', 'gudangs.cabang_id', '=', 'cabangs.id')
            ->select(
                'products.nama_produk',
                'products.barcode_number',
                'gudangs.product_id',
                'unit_satuans.nama_satuan',
                'cabangs.nama_cabang',
                DB::raw('SUM(gudangs.sisa_stok) as total_stok')
            )
            ->where('gudangs.cabang_id', $user->cabang_id)
            ->groupBy('gudangs.product_id', 'products.nama_produk', 'products.barcode_number', 'unit_satuans.nama_satuan', 'cabangs.nama_cabang')
            ->havingRaw('SUM(gudangs.sisa_stok) < 10')
            ->orderBy('total_stok', 'asc')
            ->get();
    }
}
