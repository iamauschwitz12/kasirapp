<div class="flex flex-col items-start p-2 border-none bg-transparent">
    @php
        $barcodeNumber = $getRecord()->barcode_number ?? $getRecord()->kode;
    @endphp

    @if($barcodeNumber)
        <div class="mb-1">
            {{-- Menggunakan format HTML agar tidak perlu install GD Library untuk PNG --}}
            {!! DNS1D::getBarcodeHTML($barcodeNumber, 'C128', 1.2, 30, 'black') !!}
        </div>
        <span class="text-[10px] font-mono text-gray-500 tracking-widest">
            {{ $barcodeNumber }}
        </span>
    @else
        <span class="text-xs text-gray-400 italic">No Barcode</span>
    @endif
</div>