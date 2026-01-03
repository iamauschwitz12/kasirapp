<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Product;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\DB;

class Pos extends Page
{
    public static function canAccess(): bool
    {
        return in_array(auth()->user()->role, ['admin', 'kasir']);
    }

    protected static ?string $title = 'Penjualan (Toko)';

    public $cart = [];
    public $total = 0;
    public $bayar = 0;

    // Tambahkan ini untuk memaksa sinkronisasi
    protected $listeners = ['refreshComponent' => '$refresh'];
    
    public function updatedSearch($value)
    {
        // Jika input pencarian terlalu pendek, abaikan (untuk performa)
        if (strlen($value) < 3) return;

        // Cari produk yang barcode_name nya SAMA PERSIS (100% match)
        $product = \App\Models\Product::where('barcode_name', $value)->first();

        if ($product) {
            // Jika ditemukan, langsung panggil fungsi addToCart
            $this->addToCart($product->id);

            // Kosongkan kembali kolom pencarian agar siap scan barang berikutnya
            $this->reset('search');

            // Opsional: Berikan notifikasi suara atau pesan kecil (pake dispatch)
            $this->dispatch('notify', [
                'message' => 'Barang ditambahkan: ' . $product->nama_produk,
                'type' => 'success'
            ]);
        }
    }

    // 3. Properti Logika (Bukan Static)
    public $kembalian = 0;

    // Gunakan updatedBayar untuk menghitung kembalian secara otomatis saat input berubah
    public function updatedBayar()
    {
        $this->kembalian = (int)$this->bayar - (int)$this->total;
    }
    
    public function checkout()
    {
        // 1. Sinkronkan total harga terlebih dahulu
        $this->updatedCart();

        // Validasi jika total nol atau keranjang kosong
        if (count($this->cart) === 0 || $this->total <= 0) {
            $this->dispatch('notify', [
                'message' => 'Keranjang kosong atau total tidak valid!',
                'type' => 'danger'
            ]);
            return;
        }

        // Validasi Pembayaran (Cegah bayar kurang)
        if ((float)$this->bayar < $this->total) {
            $this->dispatch('notify', [
                'message' => 'Uang bayar tidak cukup!',
                'type' => 'danger'
            ]);
            return;
        }

        try {
            \DB::beginTransaction();

            // 2. GENERATE NOMOR TRANSAKSI
            $today = date('Ymd');
            $count = \App\Models\Sale::whereDate('created_at', date('Y-m-d'))->count() + 1;
            $nomor_transaksi = 'TRX-' . $today . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

            // 3. HITUNG NILAI AKHIR
            $totalHarga = (float) $this->total;
            $uangBayar = (float) $this->bayar;
            $kembalian = $uangBayar - $totalHarga;

            // 4. SIMPAN TRANSAKSI UTAMA (SALES)
            $newSale = \App\Models\Sale::create([
                'nomor_transaksi' => $nomor_transaksi,
                'total_harga'     => $totalHarga,
                'bayar'           => $uangBayar,
                'kembalian'       => $kembalian,
                'user_id'         => auth()->id(),
            ]);

            // 5. SIMPAN ITEM & POTONG STOK
            foreach ($this->cart as $item) {
                // CARI PRODUK DULU (Penting: harus sebelum logika stok)
                $product = \App\Models\Product::find($item['product_id']);
                
                if ($product) {
                    // Hitung Qty dalam PCS (Satuan terkecil)
                    $qtyPcs = ($item['satuan_pilihan'] === 'grosir') 
                        ? (int)$item['qty'] * (int)($product->isi_konversi ?: 1)
                        : (int)$item['qty'];

                    // POTONG STOK (Hanya sekali panggil)
                    $product->decrement('stok', $qtyPcs);

                    // SIMPAN DETAIL ITEM
                    $newSale->items()->create([
                        'product_id'     => $item['product_id'],
                        'qty'            => $item['qty'],
                        'nama_satuan'    => $item['nama_satuan'],
                        'satuan_pilihan' => $item['satuan_pilihan'],
                        'harga_saat_ini' => $item['harga'],
                        'subtotal'       => (float)$item['qty'] * (float)$item['harga'],
                    ]);
                }
            }

            \DB::commit();

            // 6. DISPATCH & RESET
            $this->dispatch('notify', ['message' => 'Transaksi Berhasil!', 'type' => 'success']);
            
            // Buka Tab Cetak Struk
            $this->dispatch('open-print-window', url: route('print.struk', ['id' => $newSale->id]));

            // Bersihkan Form
            $this->reset(['cart', 'total', 'bayar']);

        } catch (\Exception $e) {
            \DB::rollBack();
            $this->dispatch('notify', [
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'type' => 'danger'
            ]);
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
        
        return \App\Models\Product::with('unitSatuan') // Load relasi satuan
        ->where('nama_produk', 'like', '%' . $this->search . '%')
        ->orWhere('barcode_number', $this->search)
        ->limit(20)
        ->get();

        $results = [];

        foreach ($query as $product) {
            // 1. Tambahkan Pilihan Grosir (Misal: Kotak)
            if ($product->harga_grosir > 0) {
                $results[] = (object) [
                    'id' => $product->id,
                    'unique_key' => $product->id . '-grosir', // Kunci unik untuk Livewire
                    'nama_produk' => $product->nama_produk . ' (' . $product->satuan_besar . ')',
                    'harga' => $product->harga_grosir,
                    'stok' => floor($product->stok / $product->isi_konversi), // Tampilkan stok dalam satuan besar
                    'satuan' => $product->satuan_besar,
                    'tipe' => 'grosir',
                    'konversi' => $product->isi_konversi
                ];
            }

            // 2. Tambahkan Pilihan Eceran (Misal: Pcs)
            $results[] = (object) [
                'id' => $product->id,
                'unique_key' => $product->id . '-eceran',
                'nama_produk' => $product->nama_produk . ' (Pcs)',
                'harga' => $product->harga, // Ini harga eceran
                'stok' => $product->stok,
                'satuan' => $product->unitSatuan->nama_satuan ?? 'Pcs',
                'tipe' => 'eceran',
                'konversi' => 1
            ];
        }

        return $results;
    }

    /**
     * Menghitung Total Harga
     * Diakses di blade dengan $this->total
     */
    #[Computed]
    public function total()
    {
        return collect($this->cart)->sum(fn($item) => $item['harga'] * $item['qty']);
    }

    #[Computed] 
    public function totalQty()
    {
        return collect($this->cart)->sum('qty');
    }

    public function addToCart($productId)
    {
        $product = \App\Models\Product::find($productId);
        $satuanAwal = 'eceran'; // Default saat klik pertama kali
        $cartKey = $productId . '_' . $satuanAwal;

        $index = collect($this->cart)->search(fn($item) => $item['cart_key'] === $cartKey);

        if ($index !== false) {
            $this->cart[$index]['qty']++;
            $this->cart[$index]['subtotal'] = $this->cart[$index]['qty'] * $this->cart[$index]['harga'];
        } else {
            $this->cart[] = [
                'cart_key' => $cartKey,
                'product_id' => $product->id,
                'nama_produk' => $product->nama_produk,
                'qty' => 1,
                'harga' => $product->harga,
                'nama_satuan' => $product->satuan_kecil,
                'satuan_pilihan' => $satuanAwal,
                'subtotal' => $product->harga,
            ];
        }
        $this->updatedCart();
    }

    public function toggleSatuan($index, $satuanBaru)
    {
        if (!isset($this->cart[$index])) return;

        $item = $this->cart[$index];
        $product = \App\Models\Product::find($item['product_id']);
        
        // 1. Buat Key Baru berdasarkan satuan yang dipilih
        $newCartKey = $item['product_id'] . '_' . $satuanBaru;

        // 2. Cari apakah satuan tersebut sudah ada di baris lain?
        $existingIndex = collect($this->cart)
            ->search(fn($cartItem, $key) => $cartItem['cart_key'] === $newCartKey && $key != $index);

        if ($existingIndex !== false) {
            // Jika SUDAH ADA baris dengan satuan tersebut, gabungkan qty-nya
            $this->cart[$existingIndex]['qty'] += $item['qty'];
            $this->cart[$existingIndex]['subtotal'] = $this->cart[$existingIndex]['qty'] * $this->cart[$existingIndex]['harga'];
            
            // Hapus baris yang lama
            unset($this->cart[$index]);
            $this->cart = array_values($this->cart); // Reset urutan array
        } else {
            // Jika BELUM ADA, ubah data di baris tersebut
            $hargaBaru = ($satuanBaru === 'grosir') ? $product->harga_grosir : $product->harga;
            $namaSatuanBaru = ($satuanBaru === 'grosir') ? $product->satuan_besar : $product->satuan_kecil;

            $this->cart[$index]['cart_key'] = $newCartKey;
            $this->cart[$index]['satuan_pilihan'] = $satuanBaru;
            $this->cart[$index]['nama_satuan'] = $namaSatuanBaru;
            $this->cart[$index]['harga'] = $hargaBaru;
            $this->cart[$index]['subtotal'] = $this->cart[$index]['qty'] * $hargaBaru;
        }

        $this->updatedCart(); // Update total tagihan
    }

    public function increaseQty(int $index) // Tambahkan 'int' di sini
    {
        if (isset($this->cart[$index])) {
            $this->cart[$index]['qty']++;
            $this->calculateTotal();
        }
    }

    public function decreaseQty(int $index) // Tambahkan 'int' di sini
    {
        if (isset($this->cart[$index])) {
            if ($this->cart[$index]['qty'] > 1) {
                $this->cart[$index]['qty']--;
            } else {
                unset($this->cart[$index]);
                $this->cart = array_values($this->cart);
            }
            
            // Pemicu re-render
            $this->cart = $this->cart;
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
    public function updatedCart()
    {
        $this->total = collect($this->cart)->sum(function ($item) {
            return (float)($item['harga'] * $item['qty']);
        });
    }

    public function calculateTotal()
    {
        $this->total = collect($this->cart)->sum(function ($item) {
            return (float)$item['qty'] * (float)$item['harga'];
        });
    }
    public function updateQty($index, $operator)
    {
        if ($operator == '+') {
            $this->cart[$index]['qty']++;
        } else {
            if ($this->cart[$index]['qty'] > 1) $this->cart[$index]['qty']--;
        }
        
        // Selalu hitung ulang subtotal setelah Qty berubah
        $this->cart[$index]['subtotal'] = $this->cart[$index]['qty'] * $this->cart[$index]['harga'];
    }
}