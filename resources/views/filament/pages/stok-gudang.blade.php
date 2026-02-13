@assets
@include('filament.pages.pos-receipt')
@endassets

<x-filament-panels::page>
    <div id="stok-container" class="relative">
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                important: '#stok-container',
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
        <div class="space-y-6 bg-gradient-to-br from-blue-50 via-white to-mint-50 -m-6 p-6 min-h-screen">
            {{-- Header & Filter Section --}}
            <div
                class="bg-white rounded-2xl shadow-lg border-2 border-mint-200 p-4 transition-all hover:shadow-xl">
                <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                    <div class="relative w-full md:max-w-md group">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <x-heroicon-m-magnifying-glass
                                class="w-5 h-5 text-mint-400 group-focus-within:text-mint-600 transition-colors" />
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari stok barang..."
                            class="block w-full pl-10 pr-4 py-2.5 border-2 border-mint-200 rounded-xl text-gray-700 bg-white text-sm focus:ring-2 focus:ring-mint-400 focus:border-mint-400 transition-all placeholder:text-gray-400 shadow-sm">
                    </div>

                    <div class="w-full md:w-64">
                        <select wire:model.live="cabang_id" {{ auth()->user()->role === 'gudang' ? 'disabled' : '' }}
                            class="block w-full py-2.5 pl-4 pr-10 border-2 border-mint-200 rounded-xl bg-white text-gray-700 text-sm focus:ring-2 focus:ring-mint-400 focus:border-mint-400 transition-all shadow-sm {{ auth()->user()->role === 'gudang' ? 'cursor-not-allowed opacity-60' : 'cursor-pointer' }}">
                            @if(auth()->user()->role !== 'gudang')
                                <option value="">-- Semua Cabang --</option>
                            @endif
                            @foreach($listCabang as $cabang)
                                <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if($search || $cabang_id)
                    <div class="mt-3 flex items-center justify-between text-xs px-1 animate-fadeIn">
                        <p class="text-gray-600">
                            Menampilkan hasil untuk: <span
                                class="font-bold text-mint-700">"{{ $search ?: 'Semua' }}"</span>
                        </p>
                        @if(auth()->user()->role === 'gudang')
                            <button wire:click="$set('search', '')"
                                class="text-rose-500 hover:text-rose-600 font-medium hover:underline flex items-center gap-1 transition-colors">
                                <x-heroicon-m-x-mark class="w-3 h-3" /> Reset Filter
                            </button>
                        @else
                            <button wire:click="$set('search', ''); $set('cabang_id', '')"
                                class="text-rose-500 hover:text-rose-600 font-medium hover:underline flex items-center gap-1 transition-colors">
                                <x-heroicon-m-x-mark class="w-3 h-3" /> Reset Filter
                            </button>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Grid Content --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                @forelse($stokProduk as $item)
                    <div
                        class="group bg-white p-4 rounded-xl shadow-md border-2 border-blue-100 flex flex-col items-center justify-center text-center relative overflow-hidden transition-all duration-200 hover:shadow-xl hover:-translate-y-1 hover:border-mint-300 active:scale-95">

                        {{-- Decorative Background Gradient --}}
                        <div
                            class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-transparent via-mint-500 to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        </div>

                        <div
                            class="p-3 bg-coral-50 rounded-full mb-3 group-hover:scale-110 group-hover:bg-mint-100 transition-all duration-300">
                            <x-heroicon-o-cube class="w-6 h-6 text-coral-500 group-hover:text-mint-600 transition-colors" />
                        </div>

                        <h3
                            class="text-sm font-bold text-gray-800 mb-2 line-clamp-2 min-h-[2.5rem] flex items-center leading-tight">
                            {{ $item->nama_produk }}
                        </h3>

                        <div class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-2">Sisa Stok</div>

                        <div
                            class="text-3xl font-black text-gray-900 mb-2 tracking-tight group-hover:text-mint-600 transition-colors">
                            {{ number_format($item->total_sisa, 0) }}
                        </div>

                        <div
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 border border-blue-200">
                            {{ $item->label_satuan ?? 'PCS' }}
                        </div>
                    </div>
                @empty
                    <div
                        class="col-span-full flex flex-col items-center justify-center py-12 bg-white rounded-2xl border-2 border-dashed border-mint-200 text-center">
                        <div class="p-4 bg-mint-50 rounded-full mb-4">
                            <x-heroicon-o-magnifying-glass class="w-10 h-10 text-mint-400" />
                        </div>
                        <h3 class="text-base font-bold text-gray-900">Tidak ada barang ditemukan</h3>
                        <p class="text-sm text-gray-500 mt-1">Coba kata kunci lain atau reset filter pencarian Anda.
                        </p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>