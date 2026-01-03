<div wire:ignore>
    @include('filament.pages.pos-receipt')

    <div x-data="{ isPrinting: false }" 
         x-on:print-receipt.window="
            if (isPrinting) return;
            isPrinting = true;
            const data = $event.detail[0] || $event.detail;
            
            // Isi Data
            document.getElementById('p-nomor').innerText = data.nomor_transaksi;
            document.getElementById('p-tgl').innerText = data.tanggal;
            document.getElementById('p-jam').innerText = data.jam;
            document.getElementById('p-total').innerText = data.total;
            document.getElementById('p-bayar').innerText = data.bayar;
            document.getElementById('p-kembali').innerText = data.kembali;
            if(document.getElementById('p-qty')) document.getElementById('p-qty').innerText = data.total_qty;

            // Isi Tabel
            const itemTable = document.getElementById('p-items');
            itemTable.innerHTML = ''; 
            data.items.forEach(item => {
                itemTable.innerHTML += `
                    <tr>
                        <td colspan='2' style='font-weight:bold;'>${item.nama_produk}</td>
                    </tr>
                    <tr>
                        <td>${item.qty} ${item.nama_satuan} x ${item.harga}</td>
                        <td style='text-align:right;'>${item.subtotal}</td>
                    </tr>`;
            });

            // Jalankan Print
            const area = document.getElementById('receipt-print-area');
            area.style.display = 'block';
            setTimeout(() => {
                window.print();
                area.style.display = 'none';
                isPrinting = false;
            }, 200);
         ">
    </div>
</div>