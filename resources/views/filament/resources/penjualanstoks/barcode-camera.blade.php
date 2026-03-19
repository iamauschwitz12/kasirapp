@php
    $statePath = $getStatePath();
    $livewireId = $getLivewire()->getId();
@endphp

@once
    <!-- Modal Scanner Kamera PenjualanStok -->
    <x-filament::modal id="modal-scanner-penjualan-stok" width="md" class="z-50">
        <x-slot name="heading">
            Scan Barcode Menggunakan Kamera
        </x-slot>

        <div class="flex flex-col items-center justify-center p-2">
            <div id="reader-penjualan-stok"
                style="width: 100%; min-height: 250px; border-radius: 0.75rem; overflow: hidden; border: 2px solid #a7f3d0; background-color: #f8fafc;">
            </div>
            <p class="text-[11px] lg:text-xs text-gray-500 mt-3 text-center font-medium">Jika ada tombol <strong>Request
                    Camera Permissions</strong> silakan di klik, lalu pilih <strong>Izinkan/Allow</strong> pada popup
                browser Anda.</p>
        </div>

        <x-slot name="footer">
            <div class="flex gap-3 pt-2">
                <x-filament::button type="button" color="gray" x-data
                    x-on:click="$dispatch('close-modal', { id: 'modal-scanner-penjualan-stok' })" class="w-full">
                    Tutup Scanner
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let html5QrcodeScanner = null;
            let currentTargetStatePath = null;
            let currentLivewireId = '{{ $livewireId }}';

            document.addEventListener('open-modal', (event) => {
                if (event.detail.id === 'modal-scanner-penjualan-stok') {
                    currentTargetStatePath = event.detail.statePath;
                    setTimeout(() => {
                        if (!html5QrcodeScanner) {
                            if (!window.isSecureContext && location.hostname !== "localhost" && location.hostname !== "127.0.0.1") {
                                alert("Peringatan: Kamera membutuhkan koneksi aman (HTTPS) atau diakses dari Localhost. Jika kamera tetap tidak muncul, silakan gunakan akses HTTPS/Localhost.");
                            }

                            html5QrcodeScanner = new Html5QrcodeScanner(
                                "reader-penjualan-stok",
                                {
                                    fps: 10,
                                    qrbox: { width: 250, height: 150 },
                                    rememberLastUsedCamera: true
                                },
                                false
                            );

                            html5QrcodeScanner.render((decodedText, decodedResult) => {
                                const wire = window.Livewire.find(currentLivewireId);
                                if (!wire) {
                                    console.error("Livewire instance not found for ID:", currentLivewireId);
                                    return;
                                }

                                wire.call('scanBarcodeCamera', decodedText).then(product => {
                                    if (product) {
                                        // Tentukan path row yang sama
                                        const basePath = currentTargetStatePath.substring(0, currentTargetStatePath.lastIndexOf('.'));
                                        const productIdPath = basePath + '.product_id';

                                        // Set product_id — afterStateUpdated Filament otomatis mengisi nama_barang & isi_konversi
                                        wire.set(productIdPath, product.id, true);

                                        // Tutup modal secara otomatis
                                        html5QrcodeScanner.clear().then(() => {
                                            html5QrcodeScanner = null;
                                            window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'modal-scanner-penjualan-stok' } }));
                                        }).catch(e => console.error("Gagal clear scanner", e));
                                    } else {
                                        alert('Produk dengan barcode ' + decodedText + ' tidak ditemukan!');
                                    }
                                });

                            }, (errorMessage) => {
                                // Abaikan error frame scanning
                            });
                        }
                    }, 300);
                }
            });

            document.addEventListener('close-modal', (event) => {
                if (event.detail.id === 'modal-scanner-penjualan-stok') {
                    if (html5QrcodeScanner) {
                        html5QrcodeScanner.clear().then(() => {
                            html5QrcodeScanner = null;
                        }).catch((err) => console.error("Gagal menghentikan scanner.", err));
                    }
                }
            });
        });
    </script>
@endonce

<div class="flex items-center justify-center pt-6">
    <x-filament::button type="button"
        x-on:click="$dispatch('open-modal', { id: 'modal-scanner-penjualan-stok', statePath: '{{ $statePath }}' })"
        icon="heroicon-o-camera" color="success" size="sm" class="w-full h-9" title="Scan Barcode dengan Kamera">
    </x-filament::button>
</div>
