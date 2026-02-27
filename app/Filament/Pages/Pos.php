<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;
use App\Models\PenjualanStok;
use App\Models\Sale;
use App\Models\SaleItem;

class Pos extends Page
{
    public bool $isProcessing = false; // Tambahkan ini di deretan properti atas

    public function getMaxContentWidth(): \Filament\Support\Enums\Width|string|null
    {
        return 'full';
    }

    public static function canAccess(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'kasir']);
    }

    public static function getNavigationItems(): array
    {
        if (!static::canAccess()) {
            return [];
        }

        return [
            \Filament\Navigation\NavigationItem::make(static::getNavigationLabel())
                ->group(static::getNavigationGroup())
                ->icon(static::getNavigationIcon() ?? 'heroicon-o-computer-desktop')
                ->isActiveWhen(fn() => request()->routeIs(static::getRouteName()))
                ->sort(static::getNavigationSort())
                ->url(static::getNavigationUrl(), shouldOpenInNewTab: true),
        ];
    }

    protected static ?string $title = 'Kasir (Toko)';

    public $cart = [];
    public $total = 0;
    public $tokos = [];
    public $bayar = 0;

    // Tambahkan ini untuk memaksa sinkronisasi
    protected $listeners = ['refreshComponent' => '$refresh'];

    // app/Filament/Pages/Pos.php

    public $selectedTokoId;

    public function mount()
    {
        $this->selectedTokoId = auth()->user()->toko_id;
        $this->cart = [];

        // Cache toko list untuk admin agar tidak query setiap render
        if (auth()->user()->email === 'plastik_admin@gmail.com') {
            $this->tokos = \App\Models\Toko::all()->toArray();
        }

        unset($this->products);
    }
    public function updatedSelectedTokoId()
    {
        // Menghapus cache computed property agar fungsi products() dijalankan ulang
        unset($this->products);
    }

    // Gunakan fungsi ini untuk menggantikan pemanggilan products() di Blade khusus untuk Admin

    public function updatedSearch($value)
    {
        if (strlen($value) < 1)
            return;

        // Cari berdasarkan barcode_number atau kode produk
        $product = Product::where('barcode_number', $value)
            ->orWhere('kode', $value)
            ->first();

        if ($product) {
            $this->addToCart($product->id);
            $this->reset('search');
        }
    }

    // 3. Properti Logika (Bukan Static)
    public $kembalian = 0;

    // Gunakan updatedBayar untuk menghitung kembalian secara otomatis saat input berubah
    public function updatedBayar()
    {
        $this->kembalian = (int) $this->bayar - (int) $this->total;
    }

    // Saat cart diupdate, reset bayar dan kembalian
    public function updatedCart($value, $key)
    {
        // Handle nested property updates like cart.0.qty
        // $key will be something like "0.qty"
        if (str_contains($key, '.qty')) {
            $index = (int) explode('.', $key)[0];

            if (isset($this->cart[$index])) {
                // Ensure qty is at least 1
                if ($this->cart[$index]['qty'] < 1) {
                    $this->cart[$index]['qty'] = 1;
                }

                // Recalculate subtotal for this item with discount
                $this->recalculateItemSubtotal($index);
            }
        } elseif (str_contains($key, '.discount')) {
            $index = (int) explode('.', $key)[0];
            if (isset($this->cart[$index])) {
                $this->recalculateItemSubtotal($index);
            }
        }

        // Recalculate total
        $this->calculateTotal();

        // Update kembalian if bayar is already set
        if ($this->bayar > 0) {
            $this->kembalian = (int) $this->bayar - (int) $this->total;
        }
    }

    public function checkout()
    {
        if ($this->isProcessing)
            return;
        $this->isProcessing = true;

        // Pastikan total sinkron sebelum bayar
        $this->total = collect($this->cart)->sum('subtotal');

        if (count($this->cart) === 0) {
            $this->isProcessing = false;
            return;
        }

        if ((float) $this->bayar < $this->total) {
            $this->dispatch('notify', ['message' => 'Uang tidak cukup!', 'type' => 'danger']);
            $this->isProcessing = false;
            return;
        }

        try {
            DB::beginTransaction();

            $targetTokoId = (auth()->user()->email === 'plastik_admin@gmail.com')
                ? $this->selectedTokoId
                : auth()->user()->toko_id;

            $newSale = Sale::create([
                'nomor_transaksi' => 'TRX-' . date('YmdHis'),
                'total_harga' => (float) $this->total,
                'bayar' => (float) $this->bayar,
                'kembalian' => (float) $this->bayar - (float) $this->total,
                'user_id' => auth()->id(),
                'toko_id' => $targetTokoId,
            ]);

            foreach ($this->cart as $item) {
                $qtyPcs = (int) $item['qty'] * (int) ($item['konversi'] ?: 1);

                // Kurangi stok dari penjualan_stoks secara urut (FIFO)
                $stokRecords = DB::table('penjualan_stoks')
                    ->where('product_id', $item['product_id'])
                    ->where('toko_id', $targetTokoId)
                    ->orderBy('id', 'asc')
                    ->get();

                $sisaQty = $qtyPcs;
                foreach ($stokRecords as $stokRecord) {
                    if ($sisaQty <= 0)
                        break;

                    $qtyYangDikurangi = min($sisaQty, $stokRecord->qty);

                    DB::table('penjualan_stoks')
                        ->where('id', $stokRecord->id)
                        ->decrement('qty', $qtyYangDikurangi);

                    $sisaQty -= $qtyYangDikurangi;
                }

                $newSale->items()->create([
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'nama_satuan' => $item['nama_satuan'],
                    'satuan_pilihan' => $item['satuan_pilihan'],
                    'harga_saat_ini' => $item['harga'],
                    'discount' => $item['discount'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal_before_discount' => $item['subtotal_before_discount'] ?? ($item['qty'] * $item['harga']),
                    'subtotal' => (float) $item['subtotal'],
                ]);
            }

            DB::commit();

            $this->dispatch('close-modal', id: 'modal-pembayaran');
            $this->dispatch('notify', ['message' => 'Berhasil!', 'type' => 'success']);
            $this->dispatch('open-print-window', url: route('print.struk', ['id' => $newSale->id]));

            // Reset serentak
            $this->cart = [];
            $this->total = 0;
            $this->bayar = 0;
            $this->kembalian = 0;
            unset($this->products);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', ['message' => 'Gagal: ' . $e->getMessage(), 'type' => 'danger']);
        } finally {
            $this->isProcessing = false;
        }
    }

    public function scanBarcode($barcode)
    {
        // Cari produk berdasarkan barcode_number atau kode unik
        $product = \App\Models\Product::with('unitSatuan')
            ->where('barcode_number', $barcode)
            ->orWhere('kode', $barcode)
            ->first();

        if ($product) {
            $this->addToCart($product->id);

            // Kirim notifikasi sukses (Opsional)
            $this->js('new Audio("/sounds/beep.mp3").play();'); // Bunyi beep jika ada file suaranya
        } else {
            // Notifikasi jika barang tidak ditemukan
            \Filament\Notifications\Notification::make()
                ->title('Produk tidak ditemukan')
                ->danger()
                ->send();
        }

        // Reset input pencarian setelah scan
        $this->search = '';
    }
    // Kembalikan ke NON-STATIC sesuai error yang muncul
    protected string $view = 'filament.pages.pos';

    public $search = '';

    /**
     * Menggunakan Atribut Computed agar data reaktif dengan benar
     * Diakses di blade dengan $this->products
     */
    #[Computed]
    public function products()
    {
        $user = auth()->user();
        $isAdmin = $user->email === 'plastik_admin@gmail.com';
        $tokoId = $isAdmin ? ($this->selectedTokoId ?? $user->toko_id) : $user->toko_id;

        if (!$tokoId)
            return [];

        // Query stok yang sudah digabung (SUM) dengan filter search di SQL
        $query = \App\Models\PenjualanStok::query()
            ->select('product_id', \DB::raw('SUM(qty) as total_qty'))
            ->where('toko_id', $tokoId)
            ->when(!empty($this->search), function ($q) {
                $search = $this->search;
                $q->whereHas('product', function ($pq) use ($search) {
                    $pq->where('nama_produk', 'LIKE', "%{$search}%")
                        ->orWhere('barcode_number', 'LIKE', "%{$search}%")
                        ->orWhere('kode', 'LIKE', "%{$search}%");
                });
            })
            ->groupBy('product_id')
            ->with(['product.unitSatuan'])
            ->get();

        $results = [];

        foreach ($query as $stokItem) {
            $product = $stokItem->product;
            if (!$product)
                continue;

            $totalPcs = (int) $stokItem->total_qty;
            $konversi = (int) ($product->isi_konversi ?: 1);

            $jumlahBesar = floor($totalPcs / $konversi);
            $sisaEceran = $totalPcs % $konversi;
            $namaUnitSatuan = $product->unitSatuan->nama_satuan ?? 'PCS';
            $satuanBesar = $product->satuan_besar ?? $namaUnitSatuan;

            $stokInformatif = ($konversi > 1)
                ? "{$jumlahBesar} {$namaUnitSatuan} + {$sisaEceran} {$satuanBesar}"
                : "{$totalPcs} {$namaUnitSatuan}";

            $results[] = (object) [
                'id' => $product->id,
                'nama_produk' => $product->nama_produk,
                'stok' => (int) $totalPcs,
                'stok_lengkap' => $stokInformatif,
                'harga_ecer' => (float) $product->harga,
                'harga' => (float) $product->harga,
                'harga_grosir' => (float) $product->harga_grosir,
                'satuan_besar' => $satuanBesar,
                'satuan_kecil' => $namaUnitSatuan,
                'nama_unit_satuan' => $namaUnitSatuan,
                'has_grosir' => $product->harga_grosir > 0,
                'isi_konversi' => (int) $konversi,
                'satuan' => $namaUnitSatuan,
            ];
        }

        return $results;
    }

    /**
     * Menghitung Total Harga
     * Diakses di blade dengan $this->total
     */


    #[Computed]
    public function totalQty()
    {
        return collect($this->cart)->sum('qty');
    }

    public function addToCart($productId, $tipe = 'eceran')
    {
        // Query langsung dari DB untuk 1 produk, bukan recompute semua products
        $user = auth()->user();
        $isAdmin = $user->email === 'plastik_admin@gmail.com';
        $tokoId = $isAdmin ? ($this->selectedTokoId ?? $user->toko_id) : $user->toko_id;

        $stokItem = \App\Models\PenjualanStok::query()
            ->select('product_id', \DB::raw('SUM(qty) as total_qty'))
            ->where('toko_id', $tokoId)
            ->where('product_id', $productId)
            ->groupBy('product_id')
            ->with('product.unitSatuan')
            ->first();

        if (!$stokItem || !$stokItem->product)
            return;

        $product = $stokItem->product;
        $namaUnitSatuan = $product->unitSatuan->nama_satuan ?? 'PCS';
        $konversi = (int) ($product->isi_konversi ?: 1);
        $totalPcs = (int) $stokItem->total_qty;
        $jumlahBesar = floor($totalPcs / $konversi);
        $productData = (object) [
            'id' => $product->id,
            'nama_produk' => $product->nama_produk,
            'harga_ecer' => (float) $product->harga,
            'harga_grosir' => (float) $product->harga_grosir,
            'satuan_besar' => $product->satuan_besar ?? $namaUnitSatuan,
            'satuan_kecil' => $namaUnitSatuan,
            'has_grosir' => $product->harga_grosir > 0,
            'isi_konversi' => $konversi,
            'stok_besar' => (int) $jumlahBesar,
        ];

        $cartKey = $productId . '_' . $tipe;
        $index = collect($this->cart)->search(fn($item) => ($item['cart_key'] ?? '') === $cartKey);

        if ($index !== false) {
            $this->cart[$index]['qty']++;
            // Pastikan subtotal dihitung ulang di sini dengan discount
            $this->recalculateItemSubtotal($index);
        } else {
            $harga = ($tipe === 'grosir') ? $productData->harga_grosir : $productData->harga_ecer;
            $konversi = ($tipe === 'grosir') ? (int) $productData->isi_konversi : 1;
            $subtotal_before_discount = (float) $harga;

            $this->cart[] = [
                'cart_key' => $cartKey,
                'product_id' => $productId,
                'nama_produk' => $productData->nama_produk,
                'qty' => 1,
                'harga' => (float) $harga,
                'nama_satuan' => ($tipe === 'grosir') ? $productData->satuan_besar : $productData->satuan_kecil,
                'satuan_pilihan' => $tipe,
                'konversi' => $konversi,
                'discount' => 0,
                'discount_amount' => 0,
                'subtotal_before_discount' => $subtotal_before_discount,
                'subtotal' => $subtotal_before_discount, // Harga langsung muncul karena subtotal sudah diisi
                'satuan_kecil' => $productData->satuan_kecil,
                'satuan_besar' => $productData->satuan_besar,
                'has_grosir' => $productData->has_grosir,
                'stok_besar' => $productData->stok_besar,
            ];
        }

        // PAKSA HITUNG TOTAL KESELURUHAN
        $this->calculateTotal();
    }

    // Tambahkan fungsi pembantu ini jika belum ada
    public function calculateTotal()
    {
        $this->total = collect($this->cart)->sum('subtotal');
    }

    // Method baru untuk update discount
    public function updateDiscount($index, $discountValue)
    {
        if (!isset($this->cart[$index]))
            return;

        $this->cart[$index]['discount'] = (float) $discountValue;
        $this->recalculateItemSubtotal($index);
        $this->calculateTotal();
    }

    // Helper method untuk recalculate subtotal dengan discount
    private function recalculateItemSubtotal($index)
    {
        if (!isset($this->cart[$index]))
            return;

        $item = &$this->cart[$index];
        $subtotal_before_discount = (float) $item['qty'] * (float) $item['harga'];
        $discount_amount = (float) $item['discount'];

        // Hitung subtotal setelah diskon
        $subtotal_after_discount = $subtotal_before_discount - $discount_amount;

        // Pastikan subtotal tidak negatif
        if ($subtotal_after_discount < 0) {
            $subtotal_after_discount = 0;
            $discount_amount = $subtotal_before_discount;
        }

        $item['subtotal_before_discount'] = $subtotal_before_discount;
        $item['discount_amount'] = $discount_amount;
        $item['subtotal'] = $subtotal_after_discount;
    }

    public function toggleSatuan($index, $satuanBaru)
    {
        if (!isset($this->cart[$index]))
            return;

        $item = $this->cart[$index];
        $product = \App\Models\Product::with('unitSatuan')->find($item['product_id']);

        // 1. Buat Key Baru berdasarkan satuan yang dipilih
        $newCartKey = $item['product_id'] . '_' . $satuanBaru;

        // 2. Cari apakah satuan tersebut sudah ada di baris lain?
        $existingIndex = collect($this->cart)
            ->search(fn($cartItem, $key) => $cartItem['cart_key'] === $newCartKey && $key != $index);

        if ($existingIndex !== false) {
            // Jika SUDAH ADA baris dengan satuan tersebut, gabungkan qty-nya
            $this->cart[$existingIndex]['qty'] += $item['qty'];
            $this->recalculateItemSubtotal($existingIndex);

            // Hapus baris yang lama
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart); // Reset urutan array
        } else {
            // Jika BELUM ADA, ubah data di baris tersebut
            $hargaBaru = ($satuanBaru === 'grosir') ? $product->harga_grosir : $product->harga;
            $namaSatuanBaru = ($satuanBaru === 'grosir') ? $product->satuan_besar : ($product->unitSatuan->nama_satuan ?? 'PCS');
            $konversiBaru = ($satuanBaru === 'grosir') ? (int) $product->isi_konversi : 1;

            $this->cart[$index]['cart_key'] = $newCartKey;
            $this->cart[$index]['satuan_pilihan'] = $satuanBaru;
            $this->cart[$index]['nama_satuan'] = $namaSatuanBaru;
            $this->cart[$index]['harga'] = $hargaBaru;
            $this->cart[$index]['konversi'] = $konversiBaru;
            // Reset discount saat ganti satuan
            $this->cart[$index]['discount'] = 0;
            $this->cart[$index]['discount_amount'] = 0;
            $this->recalculateItemSubtotal($index);
        }

        $this->calculateTotal(); // Update total tagihan
    }

    public function increaseQty($index)
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['qty']++;
            $this->recalculateItemSubtotal($index);
            $this->calculateTotal();
        }
    }

    public function decreaseQty($index)
    {
        if (isset($this->cart[$index])) {
            if ($this->cart[$index]['qty'] > 1) {
                $this->cart[$index]['qty']--;
                $this->recalculateItemSubtotal($index);
            } else {
                // Jika sisa 1 dan dikurang lagi, hapus dari keranjang
                $this->removeFromCart($index);
            }
            $this->calculateTotal();
        }
    }

    public function removeFromCart($index)
    {
        if (isset($this->cart[$index])) {
            unset($this->cart[$index]);

            // Reset urutan array agar tidak ada index yang bolong (0, 1, 2, ...)
            $this->cart = array_values($this->cart);

            // Hitung ulang total tagihan
            $this->calculateTotal();

            $this->dispatch('notify', ['message' => 'Produk dihapus', 'type' => 'info']);
        }
    }
}