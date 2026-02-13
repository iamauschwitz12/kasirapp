<div class="flex flex-col items-center justify-center p-4">
    <div id="barcode-print-content-{{ $record->id }}" class="bg-white p-6 text-center">
        <div style="font-family: Arial, sans-serif; text-align: center; width: 100%;">
            <div style="font-weight: bold; font-size: 16px; margin-bottom: 10px; color: black;">
                {{ $record->nama_produk }}
            </div>

            <div
                style="display: flex; justify-content: center; margin-bottom: 10px; -webkit-print-color-adjust: exact; print-color-adjust: exact;">
                {!! DNS1D::getBarcodeHTML($record->barcode_number ?? $record->kode, 'C128', 2, 60, 'black', true) !!}
            </div>
            <div
                style="margin-top: 5px; display: flex; justify-content: center; gap: 10px; font-size: 12px; color: black; font-weight: bold; font-family: Arial, sans-serif;">
                <div>Eceran: Rp {{ number_format($record->harga, 0, ',', '.') }}</div>
                <div>Grosir: Rp {{ number_format($record->harga_grosir, 0, ',', '.') }}</div>
            </div>
            <div
                style="font-family: 'Courier New', monospace; font-size: 14px; letter-spacing: 3px; color: black; font-weight: bold;">
                {{ $record->barcode_number ?? $record->kode }}
            </div>
        </div>
    </div>

    <x-filament::button color="success" icon="heroicon-m-printer" class="mt-6" x-on:click="
            const content = document.getElementById('barcode-print-content-{{ $record->id }}').innerHTML;
            const printWindow = window.open('', '_blank', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Cetak Barcode</title>');
            printWindow.document.write('<style>');
            /* Memaksa pencetakan warna dan garis background */
            printWindow.document.write('body { display: flex; justify-content: center; align-items: center; height: 90vh; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }');
            printWindow.document.write('div { text-align: center; }');
            /* CSS Khusus agar elemen barcode DNS1D (yang biasanya div/span) terlihat */
            printWindow.document.write('.barcode-container div { display: inline-block !important; }'); 
            printWindow.document.write('</style></head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            
            setTimeout(() => {
                printWindow.focus();
                printWindow.print();
                printWindow.close();
            }, 600);
        ">
        Klik untuk Cetak
    </x-filament::button>
</div>