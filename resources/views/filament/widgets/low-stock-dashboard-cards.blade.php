<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        @if($this->getProducts()->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($this->getProducts() as $product)
                    @php
                        $stock = $product->total_stok;
                        $isDanger = $stock <= 3;
                        $isWarning = $stock > 3 && $stock <= 5;
                    @endphp

                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-md transition-shadow">
                        
                        {{-- Header Section --}}
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0">
                                <h3 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 mb-1">
                                    {{ $product->nama_produk }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                    {{ $product->barcode_number }}
                                </p>
                            </div>
                            <div class="ml-3 shrink-0">
                                @if($isDanger)
                                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-danger-100 dark:bg-danger-900/20 text-danger-700 dark:text-danger-400 font-bold text-lg">
                                        {{ $stock }}
                                    </span>
                                @elseif($isWarning)
                                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-warning-100 dark:bg-warning-900/20 text-warning-700 dark:text-warning-400 font-bold text-lg">
                                        {{ $stock }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-bold text-lg">
                                        {{ $stock }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Info Section --}}
                        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-700">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                {{ $product->unitSatuan->nama_satuan ?? 'PCS' }}
                            </span>
                            
                            @if($isDanger)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-danger-600 dark:text-danger-400">
                                    <x-heroicon-m-exclamation-circle class="w-4 h-4" />
                                    Segera isi!
                                </span>
                            @elseif($isWarning)
                                <span class="inline-flex items-center gap-1 text-xs font-medium text-warning-600 dark:text-warning-400">
                                    <x-heroicon-m-exclamation-triangle class="w-4 h-4" />
                                    Perhatian
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Summary --}}
            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">
                        Total produk dengan stok rendah
                    </span>
                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ $this->getProducts()->count() }} produk
                    </span>
                </div>
            </div>
        @else
            {{-- Empty State --}}
            <div class="flex flex-col items-center justify-center py-12 text-center">
                <div class="p-3 bg-success-100 dark:bg-success-900/20 rounded-full mb-3">
                    <x-heroicon-o-check-circle class="w-10 h-10 text-success-600 dark:text-success-400" />
                </div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                    Semua Stok Aman! 🎉
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Tidak ada produk dengan stok di bawah 10 unit
                </p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>