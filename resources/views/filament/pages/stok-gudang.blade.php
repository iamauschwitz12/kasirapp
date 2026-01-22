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

<x-filament-panels::page>
<div class="space-y-6">
    <div class="flex flex-col md:flex-row gap-4 items-center">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <x-heroicon-m-magnifying-glass class="w-5 h-5 text-gray-400" />
                </div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search" 
                    placeholder="Cari nama barang..." 
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white bg-gray-800 text-sm focus:ring-primary-500 focus:border-primary-500"
                >
            </div>

            <div class="w-full md:w-64">
                <select 
                    wire:model.live="cabang_id"
                    class="block w-full py-2 pl-3 pr-10 border border-gray-300 dark:border-gray-700 rounded-lg bg-white bg-gray-800 text-sm focus:ring-primary-500 focus:border-primary-500"
                >
                    <option value="">-- Semua Cabang --</option>
                    @foreach($listCabang as $cabang)
                        <option value="{{ $cabang->id }}">{{ $cabang->nama_cabang }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($search || $cabang_id)
            <p class="text-xs text-gray-500">
                Menyaring data... <button wire:click="$set('search', ''); $set('cabang_id', '')" class="text-primary-600 underline">Reset Filter</button>
            </p>
        @endif
    <div class="flex justify-start">
            <div class="relative w-full max-w-md">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <x-heroicon-m-magnifying-glass class="w-5 h-5 text-gray-400" />
                </div>
                <input 
                    type="text" 
                    wire:model.live="search" 
                    placeholder="Ketik nama barang lalu tunggu sekejap..." 
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-primary-500 focus:border-primary-500 sm:text-sm"
                >
            </div>
        </div>
        <p class="text-sm text-gray-500">
                Menampilkan hasil untuk: <strong>{{ $search }}</strong>
            </p>
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse($stokProduk as $item)
            <div class="bg-white dark:bg-gray-800 p-6 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 flex flex-col items-center justify-center text-center">
                <div class="p-3 bg-primary-500/10 rounded-full mb-4">
                    <x-heroicon-o-cube class="w-8 h-8 text-primary-600" />
                </div>
                
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-1">
                    {{ $item->nama_produk }}
                </h3>
                
                <p class="text-sm text-gray-500 mb-4">Sisa Stok Tersedia</p>
                
                <div class="text-5xl font-black text-primary-600">
                    {{ number_format($item->total_sisa, 0) }}
                </div>
                
                <div class="mt-4 px-3 py-1 bg-gray-100 dark:bg-gray-700 rounded-lg text-xs font-medium uppercase tracking-wider text-gray-600 dark:text-gray-300">
                    {{ $item->label_satuan ?? 'PCS' }}
                </div>
            </div>
            @empty
                <div class="col-span-full flex flex-col items-center justify-center py-12 bg-gray-50 dark:bg-gray-900/50 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-800">
                    <x-heroicon-o-face-frown class="w-12 h-12 text-gray-400 mb-3" />
                    <p class="text-gray-500">Barang "{{ $search }}" tidak ditemukan.</p>
                </div>
        @endforelse
    </div>
</div>
</x-filament-panels::page>
