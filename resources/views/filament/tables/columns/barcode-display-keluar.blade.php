<div class="flex flex-col items-center justify-center p-2">
    <div style="background-color: white; padding: 5px; border-radius: 4px;">
        {{-- Menggunakan library DNS1D yang sama dengan menu product --}}
        {!! DNS1D::getBarcodeHTML($getRecord()->product->barcode_number, 'C128', 1, 30, 'black') !!}
    </div>
    <div class="text-xs font-mono mt-1">
        {{ $getRecord()->product->barcode_number }}
    </div>
</div>