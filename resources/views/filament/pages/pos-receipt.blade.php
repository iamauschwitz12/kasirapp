<style>
        @media print {
            /* Sembunyikan semua elemen UI dashboard agar tidak ikut tercetak */
            body * { visibility: hidden; }
            .fi-notifications, .fi-sidebar, .fi-topbar, [role="status"] { display: none !important; }

            #receipt-print-area, #receipt-print-area * { 
                visibility: visible; 
                display: block !important;
            }
            #receipt-print-area {
                position: absolute;
                left: 0; top: 0;
                width: 58mm;
                font-family: 'Courier New', monospace;
                font-size: 12px;
                line-height: 1.5;
                padding: 0 10px; /* Jarak kiri-kanan */
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
            }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .border-dashed { 
                border-top: 2px dashed #000 !important; 
                margin: 8px 0; 
                width: 100%;
            }
            table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        }
    </style>

<div id="receipt-print-area" style="display: none;">

    <div class="text-center" style="margin-bottom: 5px;">
        <strong style="font-size: 14px;">NAMA TOKO ANDA</strong><br>
        Jl. Contoh Alamat No. 123
    </div>

    <div class="border-dashed"></div>
    <table style="width: 100%;">
        <tr>
            <td id="p-kasir" style="width: 65%;"></td>
        </tr>
        <tr><td id="p-nomor" colspan="2" style="word-break: break-all;"></td></tr>
        <tr>
            <td id="p-tgl"></td>
            <td id="p-jam" class="text-right"></td>
        </tr>
    </table>
    <div class="border-dashed"></div>

    <table id="p-items" style="width: 100%;">
        </table>

    <div class="border-dashed"></div>
    <table style="width: 100%;">
        <tr>
            <td style="width: 40%;">Total QTY</td>
            <td id="p-qty" class="text-right" style="width: 60%; font-weight: bold;"></td>
        </tr>
        <tr>
            <td style="width: 40%;">TOTAL</td>
            <td id="p-total" class="text-right" style="width: 60%; font-weight: bold;"></td>
        </tr>
        <tr>
            <td style="width: 40%;">BAYAR</td>
            <td id="p-bayar" class="text-right" style="width: 60%;"></td>
        </tr>
        <tr>
            <td style="width: 40%; font-weight: bold;">KEMBALI</td>
            <td id="p-kembali" class="text-right" style="width: 60%; font-weight: bold;"></td>
        </tr>
    </table>
    <div class="border-dashed"></div>
    <div class="text-center" style="margin-top: 10px;">TERIMA KASIH</div>
</div>