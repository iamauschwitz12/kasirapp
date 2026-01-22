{{-- Letakkan script di luar komponen agar tidak bentrok dengan render Livewire --}}
@assets
@include('filament.pages.pos-receipt')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    // Suppress Tailwind production warning
    tailwind.config = {
        important: true,
    };
</script>
@endassets

<x-filament-panels::page
    x-data="{ isPrinting: false }" 
    x-on:print-receipt.window="
        if (isPrinting) return;
        isPrinting = true;

        const data = $event.detail[0] || $event.detail;
        if (!data) { isPrinting = false; return; }

        // 1. Isi Data Header
        document.getElementById('p-nomor').innerText = data.nomor_transaksi;
        document.getElementById('p-tgl').innerText = data.tanggal;
        document.getElementById('p-jam').innerText = data.jam;
        document.getElementById('p-total').innerText = data.total;
        document.getElementById('p-bayar').innerText = data.bayar;
        document.getElementById('p-kembali').innerText = data.kembali;
        if(document.getElementById('p-qty')) document.getElementById('p-qty').innerText = data.total_qty;

        // 2. Isi Tabel Item
        const itemTable = document.getElementById('p-items');
        if (itemTable) {
            itemTable.innerHTML = ''; 
            data.items.forEach(item => {
                itemTable.innerHTML += `
                    <tr>
                        <td colspan='2' style='font-weight:bold; padding-top:5px;'>${item.nama_produk}</td>
                    </tr>
                    <tr>
                        <td style='font-size:11px;'>${item.qty} ${item.nama_satuan} x ${item.harga}</td>
                        <td style='text-align:right;'>${item.subtotal}</td>
                    </tr>`;
            });
        }

        // 3. Eksekusi Print dengan Guard
        const printArea = document.getElementById('receipt-print-area');
        if (printArea) {
            printArea.style.display = 'block';
            setTimeout(() => {
                window.print();
                printArea.style.display = 'none';
                // Reset status printing setelah popup ditutup
                isPrinting = false;
            }, 150);
        } else {
            isPrinting = false;
        }
    "
>
@include('filament.pages.pos-helper')
    
    <div wire:key="pos-main-container" class="flex flex-col lg:flex-row gap-4 h-[calc(100vh-160px)] -m-6 p-4 overflow-hidden bg-gray-50/50">
        
        <div class="flex-1 flex flex-col min-h-0">
            <div class="mb-4 bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex items-center px-4 focus-within:ring-2 focus-within:ring-blue-500 transition-all">
                <input type="text" 
                   wire:model.live="search" 
                    id="search-input"
                    autofocus 
                    placeholder="Cari Nama atau Scan Barcode (F2)..."
                    class="w-full border-none focus:ring-0 py-3 text-sm placeholder:text-gray-400 bg-transparent text-gray-700">
            </div>
            <div class="flex-1 overflow-y-auto pr-2 custom-scrollbar">
                @if(auth()->user()->email === 'admin@gmail.com')
                    <div class="mb-4 p-4 bg-gray-100 rounded-lg border">
                        <label class="block text-xs font-bold text-gray-500 mb-1 uppercase">Pantau Stok Toko:</label>
                        <select wire:model.live="selectedTokoId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500">
                            @foreach(\App\Models\Toko::all() as $toko)
                                <option value="{{ $toko->id }}">{{ $toko->nama_toko }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($this->products as $product)
                <div wire:key="p-{{ $product->id }}" class="bg-white border rounded-xl p-4 shadow-sm flex flex-col justify-between">
                    <div>
                        <h3 class="font-bold text-gray-800 uppercase text-xs">{{ $product->nama_produk }}</h3>
                        <p class="text-[11px] text-blue-600 font-bold mb-3">Stok: {{ $product->stok_lengkap }}</p>
                    </div>

                    <div class="space-y-2">
                        {{-- Tambahkan .stop untuk menghentikan bubbling event --}}
                        {{-- Tombol Eceran --}}
                        <button type="button" 
                            wire:click.stop="addToCart({{ $product->id }}, 'eceran')" 
                            wire:loading.attr="disabled"
                            class="w-full flex justify-between items-center px-2 py-2 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 text-[10px]">
                            <span>Ecer</span>
                            <span wire:loading.remove wire:target="addToCart({{ $product->id }}, 'eceran')">
                                Rp{{ number_format($product->harga_ecer, 0, ',', '.') }}
                            </span>
                            <span wire:loading wire:target="addToCart({{ $product->id }}, 'eceran')">...</span>
                        </button>

                        @if($product->has_grosir)
                        <button type="button" 
                            wire:click.stop="addToCart({{ $product->id }}, 'grosir')" 
                            class="w-full flex justify-between items-center px-2 py-2 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 text-[10px]">
                            <span class="text-blue-600 font-bold">{{ $product->satuan_besar }}</span>
                            <span class="font-bold text-blue-700">Rp{{ number_format($product->harga_grosir, 0, ',', '.') }}</span>
                        </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            </div>
        </div>

        <div class="w-full lg:w-[400px] flex flex-col bg-white border border-gray-200 rounded-2xl shadow-xl overflow-hidden min-h-0">
            <div class="p-4 border-b bg-gray-50/50 flex justify-between items-center">
                <div class="flex flex-col">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-[0.1em]">Pelanggan</span>
                    <span class="text-xs font-bold text-gray-800 uppercase">Pelanggan Umum</span>
                </div>
                <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
            </div>

            <div class="flex-1 overflow-y-auto custom-scrollbar bg-white p-2 space-y-2">
                @forelse($cart as $index => $item)
                {{-- Gunakan cart_key sebagai ID yang stabil --}}
                <div wire:key="cart-item-{{ $item['cart_key'] }}" class="relative p-3 rounded-xl border border-gray-100 bg-gray-50/50 hover:bg-gray-50 transition-all group">
                    
                    {{-- Tombol Hapus --}}
                    <button type="button" wire:click="removeFromCart({{ $index }})"
                        class="absolute -top-2 -right-2 bg-red-100 text-red-600 rounded-full p-1 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-500 hover:text-white shadow-sm z-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-start">
                            <span class="text-[12px] font-black text-blue-500">{{ $loop->iteration }}.</span>
                            <div class="flex flex-col flex-1 px-2">
                                <span class="text-[12px] font-bold text-gray-700 leading-tight">{{ $item['nama_produk'] }}</span>
                                <span class="text-[10px] text-gray-400">@ Rp {{ number_format($item['harga'], 0, ',', '.') }}</span>
                            </div>
                        </div>

                        {{-- Tombol Switch Satuan (Opsional: Jika tidak perlu ganti di keranjang, hapus saja bagian ini) --}}
                        <div class="flex gap-2 mb-2">
                            <span class="text-[10px] self-center text-gray-400 uppercase font-bold">Tipe:</span>
                            <button type="button" 
                                class="px-2 py-0.5 text-[9px] rounded font-bold uppercase transition {{ $item['satuan_pilihan'] === 'eceran' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                Eceran
                            </button>
                            @if($item['konversi'] > 1)
                            <button type="button"
                                class="px-2 py-0.5 text-[9px] rounded font-bold uppercase transition {{ $item['satuan_pilihan'] === 'grosir' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                                Grosir
                            </button>
                            @endif
                        </div>

                        {{-- Input Qty & Subtotal --}}
                        <div class="flex justify-between items-center mt-1">
                            <div class="flex items-center bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden text-[13px]">
                                <button type="button" wire:click="decreaseQty({{ $index }})" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 text-gray-600 font-black">-</button>
                                <span class="w-10 text-center font-black text-gray-700">{{ $item['qty'] }}</span>
                                <button type="button" wire:click="increaseQty({{ $index }})" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 text-gray-600 font-black">+</button>
                            </div>
                            <div class="text-right">
                                <span class="text-[13px] font-black text-blue-700">
                                    Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                </span>
                                <p class="text-[9px] text-gray-400 font-bold uppercase">{{ $item['nama_satuan'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="h-full flex flex-col items-center justify-center p-10 text-center opacity-40">
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">Belum Ada Pesanan</p>
                </div>
            @endforelse
            </div>

            <div class="p-5 bg-white border-t border-gray-100">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-600 font-medium">Total Item (QTY):</span>
                    <span class="text-xl font-bold text-primary-600">{{ $this->totalQty }} Item</span>
                </div>
                <div class="flex justify-between items-end mb-5">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest leading-none">Total Tagihan</span>
                    <span class="text-3xl font-black text-blue-700 font-mono tracking-tighter leading-none">
                        Rp {{ number_format($total, 0, ',', '.') }}
                    </span>
                </div>
                <x-filament::button 
    icon="heroicon-m-banknotes" 
    size="xl" 
    class="w-full mt-4" 
    color="success"
    x-on:click="$dispatch('open-modal', { id: 'modal-pembayaran' })"
    :disabled="empty($cart)">
    Bayar Sekarang
</x-filament::button>

    <x-filament::modal id="modal-pembayaran" width="md">
        <x-slot name="heading">
            Proses Pembayaran
        </x-slot>

        <div class="space-y-4">
            <div wire:key="total-section-{{ count($cart) }}">
                <span class="text-gray-600">Total Tagihan:</span>
                <span class="text-2xl font-bold text-blue-600">
                    Rp {{ number_format($total, 0, ',', '.') }}
                </span>
            </div>

            <div>
                <label class="text-sm font-medium mb-2 block">Uang Bayar (Rp)</label>
                <x-filament::input.wrapper>
                    <input
                        type="text"
                        x-data="{ 
                            val: @entangle('bayar').live 
                        }"
                        x-model.number="val"
                        @input="val = $event.target.value.replace(/\D/g, '')"
                        :value="val ? val.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.') : ''"
                        placeholder="0"
                        class="text-2xl font-bold text-success-600 px-3 py-2 border border-gray-300 rounded-lg w-full focus:ring focus:ring-blue-300"
                    />
                </x-filament::input.wrapper>

                <div class="grid grid-cols-3 gap-2 mt-3">
                    <button type="button" 
                        wire:click="$set('bayar', {{ $total }})"
                        class="px-3 py-2 text-sm font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                        Pas
                    </button>
                    <button type="button" 
                        wire:click="$set('bayar', @this.bayar + 10000)"
                        class="px-3 py-2 text-sm font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                        +10rb
                    </button>
                    <button type="button" 
                        wire:click="$set('bayar', @this.bayar + 20000)"
                        class="px-3 py-2 text-sm font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                        +20rb
                    </button>
                    <button type="button" 
                        wire:click="$set('bayar', @this.bayar + 50000)"
                        class="px-3 py-2 text-sm font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                        +50rb
                    </button>
                    <button type="button" 
                        wire:click="$set('bayar', @this.bayar + 100000)"
                        class="px-3 py-2 text-sm font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition">
                        +100rb
                    </button>
                </div>
            </div>

            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-800">
                <div class="flex justify-between text-sm">
                    <span>Kembalian:</span>
                </div>
                <div wire:key="kembalian-{{ $kembalian }}" class="text-3xl font-black {{ $kembalian < 0 ? 'text-danger-600' : 'text-primary-600' }}">
                    Rp {{ number_format($kembalian, 0, ',', '.') }}
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex gap-3">
                <button
                    type="button"
                    wire:click="$dispatch('close-modal', { id: 'modal-pembayaran' })"
                    wire:loading.attr="disabled"
                    class="flex-1 px-4 py-3 text-sm font-bold text-gray-700 bg-gray-200 hover:bg-gray-300 rounded-lg transition border border-gray-300">
                    Batal
                </button>
                <button 
                    type="button" 
                    wire:click.prevent="checkout"
                    wire:loading.attr="disabled"
                    wire:target="checkout"
                    wire:key="btn-checkout-final"
                    class="flex-1 px-4 py-3 text-sm font-bold text-white bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 rounded-lg transition shadow-lg hover:shadow-xl border border-green-700 flex items-center justify-center gap-2"
                >
                    {{-- Ikon Loading --}}
                    <svg wire:loading wire:target="checkout" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    {{-- Ikon Print --}}
                    <svg wire:loading.remove wire:target="checkout" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    
                    <span wire:loading.remove wire:target="checkout">
                        Simpan & Cetak (F10)
                    </span>
                    
                    <span wire:loading wire:target="checkout">
                        Memproses...
                    </span>
                </button>
            </div>
        </x-slot>
    </x-filament::modal>
            </div>
        </div>
    </div>
    
    <div wire:ignore>
        <script>
            tailwind.config = {
                theme: {
                    extend: {
                        colors: {
                            primary: { 500: '#3b82f6', 600: '#2563eb' }
                        }
                    }
                }
            }
            let barcodeBuffer = "";
            let timeoutHandler = null;

            document.addEventListener('keydown', (e) => {
                // Abaikan jika user sedang mengetik di input pencarian agar tidak bentrok
                if (e.target.tagName === 'INPUT') return;

                // Reset buffer jika ada jeda terlalu lama (berarti ketikan manual, bukan scanner)
                clearTimeout(timeoutHandler);
                timeoutHandler = setTimeout(() => {
                    barcodeBuffer = "";
                }, 100); // 100ms adalah standar kecepatan scanner

                // Jika scanner mengirim 'Enter' (selesai scan)
                if (e.key === 'Enter') {
                    if (barcodeBuffer.length > 2) {
                        // Panggil fungsi Livewire
                        @this.scanBarcode(barcodeBuffer);
                        barcodeBuffer = "";
                    }
                    e.preventDefault();
                    return;
                }

                // Kumpulkan karakter (Abaikan tombol spesial seperti Shift, Alt, dsb)
                if (e.key.length === 1) {
                    barcodeBuffer += e.key;
                }
            });
            // Kita gunakan variabel global yang tidak akan dibuat ulang

            window.addEventListener('open-print-window', event => {
                // Ambil URL dari data event
                const url = event.detail[0].url; 
                
                // Buka jendela baru untuk cetak
                const printWindow = window.open(url, '_blank', 'width=300,height=600');
                
                // Pastikan fokus kembali ke input pencarian setelah jendela cetak muncul
                if (printWindow) {
                    printWindow.focus();
                }
            });
        document.addEventListener('livewire:init', () => {
            // Menangkap sinyal dari Pos.php
            Livewire.on('open-print-window', (event) => {
            // Livewire v3 mengirim data dalam objek, kita ambil property 'url'
            const url = event.url;

            if (url) {
                // Membuka jendela cetak di tab baru
                const printWindow = window.open(url, '_blank', 'width=450,height=600');
                
                // Cek jika diblokir oleh browser popup blocker
                if (printWindow) {
                    printWindow.focus();
                } else {
                    alert('Mohon izinkan Pop-up pada browser Anda untuk mencetak struk.');
                }
            }
        });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F10') {
                    e.preventDefault();
                    @this.checkout(); // Memanggil fungsi checkout langsung
                }
            });
        });
        </script>
    </div>
    <style>
        /* Perbaikan scrollbar agar lebih elegan */
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 10px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d1d5db; }
        
        /* Menghilangkan ring biru bawaan filament pada input agar serasi */
        input:focus { outline: none !important; box-shadow: none !important; }
    </style>
</x-filament-panels::page>  