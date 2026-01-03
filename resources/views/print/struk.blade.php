<!DOCTYPE html>
<html>
<head>
    <style>
        @page { size: 58mm auto; margin: 0; }
        body { 
            font-family: 'Courier New', Courier, monospace; 
            width: 58mm; 
            margin: 0; 
            padding: 10px 5px; 
            font-size: 12px; 
            color: #000;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .line { border-top: 1px dashed #000; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; }
        .item-name { display: block; font-weight: bold; text-transform: uppercase; }
        .footer { margin-top: 15px; font-size: 10px; }
    </style>
</head>
<body onload="window.print(); window.onafterprint = function() { window.close(); }">
    
    <div class="text-center">
        <strong style="font-size: 14px;">NAMA TOKO ANDA</strong><br>
        Alamat Toko Anda No. 123<br>
        Telp: 0812-3456-7890
    </div>
    
    <div class="line"></div>
    
    {{-- Detail Transaksi --}}
    <table>
        <tr>
            <td>No. Trx</td>
            <td>: {{ $sale->nomor_transaksi }}</td>
        </tr>
        <tr>
            <td>Tgl/Jam</td>
            <td>: {{ $sale->created_at->format('d/m/Y H:i') }}</td>
        </tr>
        <tr>
            <td>Kasir</td>
            <td>: {{ $sale->user->name ?? 'Admin' }}</td>
        </tr>
    </table>
    
    <div class="line"></div>
    
    {{-- Daftar Barang --}}
    <table>
        @foreach($sale->items as $item)
        <div style="display: flex; flex-direction: column; margin-bottom: 5px;">
            <span>{{ $item->product->nama_produk }} ({{ strtoupper($item->satuan_pilihan) }})</span>
            
            <div style="display: flex; justify-content: space-between;">
                <span>{{ $item->qty }} {{ $item->nama_satuan }} x {{ number_format($item->harga_saat_ini, 0, ',', '.') }}</span>
                
                <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
            </div>
        </div>
        @endforeach
    </table>
    
    <hr style="border-top: 1px dashed #000;">

        <div style="display: flex; justify-content: space-between;">
            <span>TOTAL</span>
            <span>Rp {{ number_format($sale->total_harga, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between;">
            <span>BAYAR</span>
            <span>Rp {{ number_format($sale->bayar, 0, ',', '.') }}</span>
        </div>
        <div style="display: flex; justify-content: space-between; font-weight: bold;">
            <span>KEMBALI</span>
            <span>Rp {{ number_format($sale->kembalian, 0, ',', '.') }}</span>
        </div>
    
    <div class="line"></div>
    
    <div class="text-center footer">
        -- TERIMA KASIH --<br>
        Barang yang sudah dibeli<br>
        tidak dapat ditukar/dikembalikan
    </div>

</body>

    <script>
        window.onload = function() {
            window.print();
            window.onafterprint = function() {
                window.close();
            };
        }
    </script>
</html>