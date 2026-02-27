{{-- Letakkan script di luar komponen agar tidak bentrok dengan render Livewire --}}
@assets
@include('filament.pages.pos-receipt')
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        corePlugins: {
            preflight: false,
        },
        theme: {
            extend: {
                colors: {
                    primary: { 500: '#60a5fa', 600: '#3b82f6' },
                    mint: { 50: '#f0fdfa', 100: '#ccfbf1', 300: '#5eead4', 500: '#14b8a6', 600: '#0d9488' },
                    coral: { 50: '#fff7ed', 100: '#ffedd5', 400: '#fb923c', 500: '#f97316' }
                }
            }
        }
    }
</script>
@endassets

<x-filament-panels::page x-data="{ isPrinting: false }" x-on:print-receipt.window="
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
                let itemHTML = `
                    <tr>
                        <td colspan='2' style='font-weight:bold; padding-top:5px;'>${item.nama_produk}</td>
                    </tr>
                    <tr>
                        <td style='font-size:11px;'>${item.qty} ${item.nama_satuan} x ${item.harga}</td>
                        <td style='text-align:right;'>${item.subtotal_before_discount || item.subtotal}</td>
                    </tr>`;
                
                // Tambahkan baris diskon jika ada
                if (item.discount_amount && item.discount_amount > 0) {
                    itemHTML += `
                    <tr>
                        <td style='font-size:10px; color:#059669; padding-left:10px;'>- Diskon</td>
                        <td style='text-align:right; font-size:10px; color:#059669;'>-${item.discount_amount}</td>
                    </tr>`;
                }
                
                itemTable.innerHTML += itemHTML;
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
    ">
    @include('filament.pages.pos-helper')

    <div wire:key="pos-main-container"
        class="flex flex-col lg:flex-row gap-4 lg:gap-6 h-[calc(100vh-140px)] -m-4 sm:-m-6 p-4 sm:p-6 overflow-hidden font-sans bg-gradient-to-br from-blue-50 via-white to-mint-50">

        {{-- LEFT COLUMN: PRODUCT GRID --}}
        <div class="flex-1 flex flex-col min-h-0 relative">

            {{-- Search Bar --}}
            <div class="mb-4 lg:mb-6 relative group z-10">
                <div class="absolute inset-y-0 left-0 pl-4 lg:pl-5 flex items-center pointer-events-none">
                    <x-heroicon-o-magnifying-glass
                        class="h-5 w-5 lg:h-6 lg:w-6 text-mint-400 group-focus-within:text-mint-600 transition-colors duration-300" />
                </div>
                <!-- Hotkey Hint Badge -->
                <div class="absolute inset-y-0 right-0 pr-3 lg:pr-4 flex items-center">
                    <span
                        class="px-2 py-1 rounded-md bg-mint-50 text-mint-600 text-xs font-bold border border-mint-200">F2</span>
                </div>

                <input type="text" wire:model.live.debounce.300ms="search" id="search-input" autofocus
                    placeholder="Cari Produk atau Scan..."
                    class="w-full pl-12 lg:pl-14 pr-12 py-3 lg:py-4 text-sm lg:text-base bg-white border-none rounded-xl lg:rounded-2xl shadow-lg ring-2 ring-mint-200 focus:ring-2 focus:ring-mint-400 placeholder:text-gray-400 text-gray-700 transition-all">
            </div>

            {{-- Product Grid Area --}}
            <div class="flex-1 overflow-y-auto pr-1 lg:pr-2 custom-scrollbar pb-4 lg:pb-10">

                <div id="pos-container" class="relative">
                    <script src="https://cdn.tailwindcss.com"></script>
                    <script>
                        tailwind.config = {
                            important: '#pos-container',
                            corePlugins: {
                                preflight: false,
                            },
                            theme: {
                                extend: {
                                    colors: {
                                        primary: { 500: '#3b82f6', 600: '#2563eb' }
                                    }
                                }
                            }
                        }
                    </script>

                    <style>
                        @media (max-width: 1024px) {

                            /* Hide Filament Sidebar */
                            aside.fi-sidebar {
                                display: none !important;
                            }

                            /* Hide Filament Topbar Toggle (Hamburger) */
                            .fi-topbar nav button {
                                display: none !important;
                            }

                            /* Ensure Main Content takes full width */
                            .fi-main {
                                margin-left: 0 !important;
                                width: 100% !important;
                            }
                        }
                    </style>

                    {{-- Main Layout: Stack vertical on mobile, Side-by-side on desktop --}}
                    <div class="flex flex-col lg:flex-row gap-6 h-auto lg:h-[calc(100vh-8rem)]">

                        {{-- LEFT COLUMN: PRODUCT GRID --}}
                        <div class="flex-1 flex flex-col min-h-[500px] lg:min-h-0 relative">

                            {{-- Search Bar --}}

                            {{-- Product Grid Scrollable Area --}}
                            <div class="flex-1 overflow-y-auto custom-scrollbar pr-1 lg:pr-2 pb-2 relative">
                                {{-- Loading Overlay --}}
                                <div wire:loading
                                    wire:target="search, addToCart, toggleSatuan, increaseQty, decreaseQty, removeFromCart"
                                    class="absolute inset-0 bg-white/60 backdrop-blur-sm z-30 flex items-center justify-center rounded-xl"
                                    style="display: none;">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="animate-spin h-8 w-8 text-mint-500"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                                stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span class="text-xs font-bold text-mint-600">Memuat...</span>
                                    </div>
                                </div>
                                <div
                                    class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 gap-2.5 sm:gap-3 lg:gap-4">
                                    @forelse($this->products as $product)
                                        <div wire:click="addToCart({{ $product->id }})"
                                            wire:key="product-{{ $product->id }}"
                                            class="group bg-white rounded-xl lg:rounded-2xl p-2.5 sm:p-3 lg:p-4 shadow-md active:shadow-xl lg:hover:shadow-2xl ring-2 ring-blue-100 active:ring-mint-300 lg:hover:ring-mint-300 cursor-pointer transition-all duration-200 relative overflow-hidden active:scale-95 lg:hover:-translate-y-2 lg:hover:scale-105">

                                            {{-- Hover Gradient Effect --}}
                                            <div
                                                class="absolute inset-0 bg-gradient-to-br from-mint-100/30 to-blue-100/30 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
                                            </div>

                                            <div class="relative z-10 flex flex-col h-full justify-between">
                                                <div>
                                                    <div class="flex justify-between items-start mb-1.5 lg:mb-2">
                                                        <div
                                                            class="p-1.5 lg:p-2 rounded-lg lg:rounded-xl bg-coral-50 text-coral-500 group-active:bg-mint-100 group-active:text-mint-600 lg:group-hover:bg-mint-100 lg:group-hover:text-mint-600 transition-colors shadow-sm">
                                                            <x-heroicon-o-cube
                                                                class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6" />
                                                        </div>
                                                        @if($product->stok <= 10)
                                                            <span
                                                                class="animate-pulse px-2 py-0.5 rounded-full bg-rose-100 text-rose-600 text-[10px] sm:text-xs font-bold shadow-sm">
                                                                Sisa {{ $product->stok }}
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <h3
                                                        class="font-bold text-gray-800 text-xs sm:text-sm lg:text-base leading-tight mb-0.5 lg:mb-1 line-clamp-2 group-active:text-mint-700 lg:group-hover:text-mint-700 transition-colors">
                                                        {{ $product->nama_produk }}
                                                    </h3>
                                                    <p
                                                        class="text-[9px] sm:text-[10px] lg:text-xs text-gray-500 font-medium tracking-wide">
                                                        {{ $product->stok_lengkap }}
                                                    </p>
                                                </div>

                                                <div
                                                    class="mt-2 lg:mt-3 pt-2 lg:pt-3 border-t border-blue-100 flex items-end justify-between">
                                                    <div class="flex flex-col">
                                                        <span class="text-[9px] lg:text-[10px] text-gray-500">Harga
                                                            Satuan</span>
                                                        <span
                                                            class="font-bold text-gray-900 text-xs sm:text-sm lg:text-base">
                                                            {{ number_format($product->harga_ecer, 0, ',', '.') }}
                                                        </span>
                                                    </div>
                                                    <button
                                                        class="p-1.5 lg:p-2 rounded-lg bg-mint-50 text-mint-500 group-active:bg-mint-500 group-active:text-white lg:group-hover:bg-mint-500 lg:group-hover:text-white transition-all shadow-md">
                                                        <x-heroicon-m-plus class="w-4 h-4 lg:w-5 lg:h-5" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    @empty
                                        <div
                                            class="col-span-full flex flex-col items-center justify-center py-12 text-center opacity-60">
                                            <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-full mb-3">
                                                <x-heroicon-o-magnifying-glass class="w-8 h-8 text-gray-400" />
                                            </div>
                                            <p class="font-bold text-gray-400 text-sm">Produk tidak ditemukan</p>
                                            <p class="text-xs text-gray-400">Coba kata kunci lain atau scan barcode</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN: CART (Stack to bottom on mobile) --}}
                        <div
                            class="w-full lg:w-[420px] flex flex-col bg-white rounded-2xl lg:rounded-3xl shadow-xl lg:shadow-2xl overflow-hidden border-2 border-mint-200 h-[600px] sm:h-[650px] lg:h-full">
                            {{-- Cart Header --}}
                            <div
                                class="p-3 sm:p-4 lg:p-5 bg-gradient-to-r from-blue-100 via-mint-50 to-blue-50 border-b-2 border-mint-200 z-10 shadow-md">
                                <div class="flex items-center justify-between mb-1.5 lg:mb-2">
                                    <div class="flex items-center gap-2 lg:gap-3">
                                        <div class="bg-mint-100 p-1.5 lg:p-2 rounded-lg lg:rounded-xl shadow-sm">
                                            <x-heroicon-m-shopping-cart class="w-4 h-4 lg:w-5 lg:h-5 text-mint-600" />
                                        </div>
                                        <h2 class="text-base lg:text-lg font-bold text-gray-800">Keranjang</h2>
                                    </div>
                                </div>

                                {{-- Toko Selector --}}
                                @if(auth()->user()->email === 'plastik_admin@gmail.com')
                                    <div class="mt-2 lg:mt-3">
                                        <select wire:model.live="selectedTokoId"
                                            class="block w-full py-2 lg:py-2.5 pl-3 lg:pl-4 pr-8 lg:pr-10 border-2 border-mint-200 rounded-lg lg:rounded-xl bg-white text-gray-700 text-xs lg:text-sm focus:ring-2 focus:ring-mint-400 focus:border-mint-400 transition-all cursor-pointer shadow-sm">
                                            @foreach($tokos as $toko)
                                                <option value="{{ $toko['id'] }}">{{ $toko['nama_toko'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>

                            {{-- Cart Items --}}
                            <div
                                class="flex-1 overflow-y-auto custom-scrollbar p-2 sm:p-3 space-y-2 sm:space-y-3 bg-gradient-to-b from-blue-50/30 to-mint-50/30">
                                @if(count($cart) > 0)
                                    @foreach($cart as $index => $item)
                                        <div wire:key="cart-item-{{ $index }}"
                                            class="group bg-white active:bg-mint-50/50 lg:hover:bg-mint-50/50 p-2.5 lg:p-3 rounded-xl lg:rounded-2xl shadow-md border-2 border-blue-100 active:border-mint-300 lg:hover:border-mint-300 transition-all">
                                            <div class="flex justify-between items-start gap-2 lg:gap-3">
                                                <div class="flex-1 min-w-0">
                                                    <h4
                                                        class="font-bold text-gray-800 text-xs lg:text-sm truncate mb-0.5 lg:mb-1">
                                                        {{ $item['nama_produk'] }}
                                                    </h4>
                                                    <div
                                                        class="flex flex-wrap items-center gap-1.5 lg:gap-2 text-[10px] lg:text-xs">
                                                        <div
                                                            class="px-1.5 lg:px-2 py-0.5 rounded-md bg-blue-100 text-blue-700 font-medium">
                                                            Rp {{ number_format($item['harga'], 0, ',', '.') }}
                                                        </div>
                                                        @if($item['has_grosir'])
                                                            <div class="flex bg-blue-50 rounded-md p-0.5">
                                                                <button
                                                                    class="px-1.5 py-0.5 rounded {{ $item['satuan_pilihan'] == 'eceran' ? 'bg-white shadow-sm text-mint-600 font-bold' : 'text-gray-500 hover:text-gray-700' }} transition-all"
                                                                    wire:click="toggleSatuan({{ $index }}, 'eceran')">
                                                                    {{ $item['satuan_besar'] }}
                                                                </button>
                                                                @if(($item['stok_besar'] ?? 0) > 0)
                                                                    <button
                                                                        class="px-1.5 py-0.5 rounded {{ $item['satuan_pilihan'] == 'grosir' ? 'bg-white shadow-sm text-mint-600 font-bold' : 'text-gray-500 hover:text-gray-700' }} transition-all"
                                                                        wire:click="toggleSatuan({{ $index }}, 'grosir')">
                                                                        {{ $item['satuan_kecil'] }}
                                                                    </button>
                                                                @else
                                                                    <span
                                                                        class="px-1.5 py-0.5 rounded text-gray-300 cursor-not-allowed line-through text-[10px]"
                                                                        title="Stok {{ $item['satuan_kecil'] }} tidak tersedia">
                                                                        {{ $item['satuan_kecil'] }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @else
                                                            <span class="text-gray-500 capitalize">{{ $item['nama_satuan'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    @if(($item['discount_amount'] ?? 0) > 0)
                                                        {{-- Show original price with strikethrough if discount exists --}}
                                                        <span class="block text-[10px] text-gray-400 line-through">
                                                            Rp
                                                            {{ number_format($item['subtotal_before_discount'] ?? ($item['qty'] * $item['harga']), 0, ',', '.') }}
                                                        </span>
                                                        <span class="block font-bold text-green-600 text-xs lg:text-sm">
                                                            Rp {{ number_format($item['subtotal'], 0, ',', '.') }}
                                                        </span>
                                                        <span class="block text-[9px] text-green-600 font-medium">
                                                            Hemat Rp {{ number_format($item['discount_amount'], 0, ',', '.') }}
                                                        </span>
                                                    @else
                                                        {{-- Normal price display --}}
                                                        <span class="block font-bold text-mint-600 text-xs lg:text-sm">
                                                            Rp {{ number_format($item['qty'] * $item['harga'], 0, ',', '.') }}
                                                        </span>
                                                    @endif
                                                    <button wire:click="removeFromCart({{ $index }})"
                                                        class="mt-1 text-[10px] text-rose-500 active:text-rose-600 lg:hover:text-rose-600 hover:underline font-medium">
                                                        Hapus
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Qty Control --}}
                                            <div class="mt-3 flex items-center justify-between gap-2 bg-blue-50 rounded-xl p-1">
                                                <button wire:click="decreaseQty({{ $index }})"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-rose-400 shadow-sm text-white hover:bg-rose-500 active:scale-95 transition-all font-bold">
                                                    -
                                                </button>
                                                <input type="number" wire:model.live.debounce.150ms="cart.{{ $index }}.qty"
                                                    min="1"
                                                    class="flex-1 text-center font-bold text-gray-700 text-sm bg-white border-2 border-mint-200 rounded-lg px-2 py-1 focus:ring-2 focus:ring-mint-400 focus:border-mint-400"
                                                    placeholder="Qty">
                                                <button wire:click="increaseQty({{ $index }})"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-mint-500 shadow-sm text-white hover:bg-mint-600 active:scale-95 transition-all font-bold">
                                                    +
                                                </button>
                                            </div>

                                            {{-- Discount Input --}}
                                            <div class="mt-2 bg-yellow-50 rounded-xl p-2 border border-yellow-200">
                                                <label class="block text-[10px] font-bold text-gray-600 mb-1">
                                                    💰 Diskon (Rp)
                                                </label>
                                                <input type="number" wire:model.live.debounce.300ms="cart.{{ $index }}.discount"
                                                    min="0" max="{{ $item['qty'] * $item['harga'] }}"
                                                    class="w-full text-center font-bold text-gray-700 text-sm bg-white border-2 border-yellow-300 rounded-lg px-2 py-1.5 focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400"
                                                    placeholder="0">
                                                <p class="text-[9px] text-gray-500 mt-1 text-center">
                                                    Maksimal: Rp {{ number_format($item['qty'] * $item['harga'], 0, ',', '.') }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div
                                        class="h-full flex flex-col items-center justify-center text-center opacity-40 mt-10">
                                        <x-heroicon-o-shopping-cart class="w-16 h-16 text-gray-300 mb-4" />
                                        <p class="font-bold text-gray-400 text-sm">KERANJANG KOSONG</p>
                                        <p class="text-xs text-gray-400">Scan barang atau pilih dari menu</p>
                                    </div>
                                @endif
                            </div>

                            {{-- Cart Action --}}
                            <div
                                class="p-3 sm:p-4 lg:p-5 bg-gradient-to-r from-blue-50 to-mint-50 border-t-2 border-mint-200 shadow-[0_-8px_25px_rgba(20,184,166,0.15)] z-20">
                                <div class="flex justify-between items-end mb-2 lg:mb-4">
                                    <span class="text-xs lg:text-sm font-medium text-gray-600">Total Produk</span>
                                    <span class="font-bold text-gray-800 text-sm lg:text-base">{{ $this->totalQty }}
                                        Item</span>
                                </div>
                                <div class="flex justify-between items-end mb-3 lg:mb-6">
                                    <span
                                        class="text-[10px] lg:text-xs font-bold tracking-widest text-gray-500 uppercase">Total
                                        Bayar</span>
                                    <span class="text-2xl lg:text-3xl font-black text-mint-700 tracking-tight">
                                        Rp{{ number_format($total, 0, ',', '.') }}
                                    </span>
                                </div>

                                <button @if($total > 0) wire:click="$dispatch('open-modal', { id: 'modal-pembayaran' })"
                                @endif
                                    class="w-full py-3.5 lg:py-4 rounded-xl font-bold text-sm lg:text-base text-white shadow-xl shadow-mint-500/30 transform active:scale-[0.96] transition-all flex items-center justify-center gap-2
                        {{ $total > 0 ? 'bg-gradient-to-r from-mint-500 to-mint-600 active:from-mint-600 active:to-mint-700 lg:hover:from-mint-600 lg:hover:to-mint-700' : 'bg-gray-300 cursor-not-allowed' }}">
                                    <span>Proses Pembayaran</span>
                                    <x-heroicon-m-arrow-right class="w-4 h-4 lg:w-5 lg:h-5" />
                                </button>

                                <!-- Modified Payment Modal Container -->
                                <x-filament::modal id="modal-pembayaran" width="md">
                                    <x-slot name="heading">
                                        Pembayaran
                                    </x-slot>

                                    <div class="space-y-4">
                                        <div
                                            class="p-4 rounded-xl bg-gradient-to-br from-mint-50 to-blue-50 text-center border-2 border-mint-200 shadow-md">
                                            <div class="text-xs text-gray-600 mb-1 font-medium">Total Tagihan</div>
                                            <div class="text-3xl font-black text-mint-600">Rp
                                                {{ number_format($total, 0, ',', '.') }}
                                            </div>
                                        </div>

                                        <div>
                                            <label class="text-sm font-bold text-gray-700 mb-2 block">Nominal
                                                Bayar</label>
                                            <div class="relative">
                                                <div
                                                    class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 font-bold">Rp</span>
                                                </div>
                                                <input type="number" wire:model.live.debounce.300ms="bayar"
                                                    id="bayar-input" autofocus
                                                    class="w-full pl-12 pr-4 py-3 lg:py-4 text-lg lg:text-xl font-bold bg-white border-2 border-mint-200 rounded-xl focus:ring-2 focus:ring-mint-400 focus:border-mint-400 shadow-sm"
                                                    placeholder="0">
                                            </div>

                                            {{-- Quick Money Buttons --}}
                                            <div class="grid grid-cols-4 gap-2 mt-3">
                                                <button type="button" wire:click="$set('bayar', {{ $total }})"
                                                    class="px-2 py-2 text-xs font-bold bg-blue-100 hover:bg-blue-200 text-blue-700 rounded-lg transition shadow-sm">
                                                    Uang Pas
                                                </button>
                                                <button type="button" wire:click="$set('bayar', @this.bayar + 10000)"
                                                    class="px-2 py-2 text-xs font-bold bg-mint-100 hover:bg-mint-200 text-mint-700 rounded-lg transition shadow-sm">
                                                    +10rb
                                                </button>
                                                <button type="button" wire:click="$set('bayar', @this.bayar + 50000)"
                                                    class="px-2 py-2 text-xs font-bold bg-mint-100 hover:bg-mint-200 text-mint-700 rounded-lg transition shadow-sm">
                                                    +50rb
                                                </button>
                                                <button type="button" wire:click="$set('bayar', @this.bayar + 100000)"
                                                    class="px-2 py-2 text-xs font-bold bg-mint-100 hover:bg-mint-200 text-mint-700 rounded-lg transition shadow-sm">
                                                    +100rb
                                                </button>
                                            </div>
                                        </div>

                                        <div
                                            class="p-4 rounded-xl bg-gradient-to-br from-mint-600 to-mint-700 text-black shadow-lg">
                                            <div class="flex justify-between text-sm mb-1 opacity-90">
                                                <span>Kembalian</span>
                                            </div>
                                            <div wire:key="kembalian-{{ $kembalian }}"
                                                class="text-3xl font-black {{ $kembalian < 0 ? 'text-rose-300' : 'text-black' }}">
                                                Rp {{ number_format($kembalian, 0, ',', '.') }}
                                            </div>
                                        </div>
                                    </div>

                                    <x-slot name="footer">
                                        <div class="flex gap-3 pt-2">
                                            <button type="button"
                                                wire:click="$dispatch('close-modal', { id: 'modal-pembayaran' })"
                                                class="flex-1 px-4 py-3 text-sm font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-xl transition shadow-sm">
                                                Batal
                                            </button>
                                            <button type="button" wire:click.prevent="checkout"
                                                wire:loading.attr="disabled" wire:target="checkout"
                                                wire:key="btn-checkout-final"
                                                class="flex-[2] px-4 py-3 text-sm font-bold text-white bg-gradient-to-r from-mint-500 to-mint-600 hover:from-mint-600 hover:to-mint-700 rounded-xl transition shadow-lg shadow-mint-500/30 flex items-center justify-center gap-2">

                                                <svg wire:loading wire:target="checkout"
                                                    class="animate-spin h-5 w-5 text-white"
                                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                    </path>
                                                </svg>

                                                <span wire:loading.remove wire:target="checkout">
                                                    Cetak Struk (F10)
                                                </span>
                                            </button>
                                        </div>
                                    </x-slot>
                                </x-filament::modal>
                            </div>
                        </div>
                    </div>
                </div>

                <div wire:ignore>
                    <script>
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
                            document.addEventListener('keydown', function (e) {
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
                    .custom-scrollbar::-webkit-scrollbar {
                        width: 6px;
                    }

                    .custom-scrollbar::-webkit-scrollbar-track {
                        background: #f0fdfa;
                        border-radius: 10px;
                    }

                    .custom-scrollbar::-webkit-scrollbar-thumb {
                        background: #5eead4;
                        border-radius: 10px;
                    }

                    .custom-scrollbar::-webkit-scrollbar-thumb:hover {
                        background: #14b8a6;
                    }

                    /* Menghilangkan ring biru bawaan filament pada input agar serasi */
                    input:focus {
                        outline: none !important;
                        box-shadow: none !important;
                    }
                </style>
</x-filament-panels::page>