<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class LowStockDashboardTable extends Widget
{
    protected string $view = 'filament.widgets.low-stock-dashboard-cards';

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Auth::user() && Auth::user()->isKasir();
    }

    public function getProducts()
    {
        $user = Auth::user();
        $tokoId = $user->toko_id;

        return Product::query()
            ->whereHas('penjualanStoks', function (Builder $query) use ($tokoId) {
                $query->where('toko_id', $tokoId);
            })
            ->withSum([
                'penjualanStoks as total_stok' => function (Builder $query) use ($tokoId) {
                    $query->where('toko_id', $tokoId);
                }
            ], 'qty')
            ->having('total_stok', '<', 10)
            ->orderBy('total_stok', 'asc')
            ->get();
    }

    public function getHeading(): string
    {
        $user = Auth::user();
        return '⚠️ Peringatan Stok Kritis (< 10) - ' . ($user->toko->nama_toko ?? 'Toko Saya');
    }
}
