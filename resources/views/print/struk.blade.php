<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            size: 58mm auto;
            margin: 0;
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            width: 58mm;
            margin: 5;
            padding: 10px 5px;
            font-size: 12px;
            color: #000;
        }

        body img {
            text-align: center;
        }

        .text-center {
            text-align: center;
        }

        .text-center.img {
            text-align: center;
            filter: grayscale(100%);
        }

        .text-right {
            text-align: right;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .item-name {
            display: block;
            font-weight: bold;
            text-transform: uppercase;
        }

        .footer {
            margin-top: 15px;
            font-size: 10px;
        }
    </style>
</head>

<body onload="window.print(); window.onafterprint = function() { window.close(); }">
    <div class="text-center img">
        <img src="{{ asset('favicon.png') }}" alt="Logo" style="width: 40px; height: auto; margin: 0 auto 5px;">
    </div>
    <div class="text-center">
        <strong style="font-size: 14px;">{{ Auth::user()->toko->nama_toko ?? 'NAMA TOKO' }}</strong><br>
        {{ Auth::user()->toko->alamat ?? 'Alamat Belum Diatur' }}<br>
        Telp: {{ Auth::user()->toko->telepon ?? '-' }}
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
                <span class="item-name">{{ $item->product->nama_produk }} ({{ strtoupper($item->satuan_pilihan) }})</span>

                <div style="display: flex; justify-content: space-between;">
                    <span>{{ $item->qty }} {{ $item->nama_satuan }} x
                        {{ number_format($item->harga_saat_ini, 0, ',', '.') }}</span>

                    @if(($item->discount_amount ?? 0) > 0)
                        {{-- Tampilkan subtotal sebelum diskon --}}
                        <span>Rp
                            {{ number_format($item->subtotal_before_discount ?? ($item->qty * $item->harga_saat_ini), 0, ',', '.') }}</span>
                    @else
                        {{-- Tampilkan subtotal normal --}}
                        <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    @endif
                </div>

                {{-- Tampilkan baris diskon jika ada --}}
                @if(($item->discount_amount ?? 0) > 0)
                    <div
                        style="display: flex; justify-content: space-between; padding-left: 10px; font-size: 10px; color: #059669;">
                        <span>- Diskon</span>
                        <span>- Rp {{ number_format($item->discount_amount, 0, ',', '.') }}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding-left: 10px; font-weight: bold;">
                        <span>Subtotal</span>
                        <span>Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        @endforeach
    </table>

    <hr style="border-top: 1px dashed #000;">

    {{-- Tampilkan Total Diskon jika ada --}}
    @php
        $totalDiscount = $sale->items->sum('discount_amount');
    @endphp

    @if($totalDiscount > 0)
        <div style="display: flex; justify-content: space-between; color: #059669; font-weight: bold; margin-bottom: 3px;">
            <span>TOTAL HEMAT</span>
            <span>Rp {{ number_format($totalDiscount, 0, ',', '.') }}</span>
        </div>
        <hr style="border-top: 1px dashed #059669; margin: 3px 0;">
    @endif

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
        <span>Rp {{ number_format($sale->kembalian ?? ($sale->bayar - $sale->total_harga), 0, ',', '.') }}</span>
    </div>

    <div class="line"></div>

    <div class="text-center footer">
        -- TERIMA KASIH --<br>
        Barang yang sudah dibeli<br>
        tidak dapat ditukar/dikembalikan
    </div>

</body>

<script>
    window.onload = function () {
        window.print();
        window.onafterprint = function () {
            window.close();
        };
    }
</script>

</html>