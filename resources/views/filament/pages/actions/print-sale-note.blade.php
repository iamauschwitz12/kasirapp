<div x-data="{
    saleNoteData: null,
    isPrinting: false,
    handlePrint(event) {
        if (this.isPrinting) return;
        this.isPrinting = true;

        let data = event.detail;
        if (Array.isArray(data)) {
            data = data.find(p => p && p.hasOwnProperty('nomor_transaksi')) || data[0];
        }

        if (!data || !data.items) {
            console.error('Invalid sale data received', data);
            this.isPrinting = false;
            return;
        }

        this.saleNoteData = data;

        this.$nextTick(() => {
            setTimeout(() => {
                window.print();
                this.isPrinting = false;
            }, 500);
        });
    }
}" x-on:print-sale-note.window="handlePrint($event)">

    <style>
        @media screen {
            #sale-note-container {
                display: none !important;
            }
        }

        @media print {
            @page {
                size: 58mm auto;
                margin: 5;
            }

            /* Hide all page content visually */
            body * {
                visibility: hidden !important;
                margin: 0 !important;
                padding: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
                line-height: 0 !important;
                font-size: 0 !important;
                border: none !important;
                float: none !important;
            }

            /* Our receipt container: fixed to viewport top-left, above everything */
            #sale-note-container {
                visibility: visible !important;
                position: fixed !important;
                left: 0 !important;
                top: 0 !important;
                width: 58mm !important;
                height: auto !important;
                overflow: visible !important;
                z-index: 999999 !important;
                background: #fff !important;
                margin: 5px !important;
                padding: 5px !important;
            }

            /* Restore all children inside the receipt */
            #sale-note-container *,
            #sale-note-container div,
            #sale-note-container span,
            #sale-note-container table,
            #sale-note-container tr,
            #sale-note-container td,
            #sale-note-container img,
            #sale-note-container strong,
            #sale-note-container br,
            #sale-note-container hr {
                visibility: visible !important;
                height: auto !important;
                overflow: visible !important;
                line-height: 1.4 !important;
                font-size: 12px !important;
                margin: revert !important;
                padding: revert !important;
            }

            /* Fix specific element sizes */
            #sale-note-container strong {
                font-size: 14px !important;
            }

            #sale-note-container .struk-discount-text {
                font-size: 10px !important;
            }

            #sale-note-container .struk-footer {
                font-size: 10px !important;
            }

            #sale-note-container img {
                display: block !important;
                width: 40px !important;
                height: auto !important;
            }

            #sale-note-container table {
                width: 50% !important;
                border-collapse: collapse !important;
            }

            #sale-note-container hr {
                border-top: 1px dashed #000 !important;
                height: 0 !important;
                margin: 5px 0 !important;
            }
        }
    </style>

    <div id="sale-note-container" x-show="saleNoteData" x-cloak>
        <template x-if="saleNoteData">
            <div style="font-family: 'Courier New', Courier, monospace; width: 58mm; padding: 10px 5px; color: #000;">

                {{-- Logo --}}
                <div style="display: flex; justify-content: center;">
                    <img src="{{ asset('favicon.png') }}" alt="Logo"
                        style="width: 40px; height: auto; margin: 0 auto 5px; filter: grayscale(100%);">
                </div>

                {{-- Header Toko --}}
                <div style="text-align: center;">
                    <strong>{{ Auth::user()->toko->nama_toko ?? 'NAMA TOKO' }}</strong><br>
                    {{ Auth::user()->toko->alamat ?? 'Alamat Belum Diatur' }}<br>
                    Telp: {{ Auth::user()->toko->telepon ?? '-' }}
                </div>

                <hr>

                {{-- Detail Transaksi --}}
                <table>
                    <tr>
                        <td style="white-space: nowrap; ">No. Trx : <span x-text="saleNoteData.nomor_transaksi"></span></td>
                    </tr>
                    <tr>
                        <td style="white-space: nowrap;">Tgl/Jam : <span x-text="saleNoteData.waktu"></span></td>
                    </tr>
                    <tr>
                        <td style="white-space: nowrap;">Kasir</td>
                        <td>: <span x-text="saleNoteData.kasir"></span></td>
                    </tr>
                </table>

                <hr>

                {{-- Daftar Barang --}}
                <template x-for="(item, index) in saleNoteData.items" :key="index">
                    <div style="margin-bottom: 5px;">
                        <span style="display: block; font-weight: bold; text-transform: uppercase;"
                            x-text="item.nama_produk + ' (' + item.satuan_pilihan.toUpperCase() + ')'"></span>

                        <div style="display: flex; justify-content: space-between;">
                            <span x-text="item.qty + ' ' + item.nama_satuan + ' x ' + item.harga_formatted"></span>
                            <template x-if="item.discount_amount > 0">
                                <span x-text="'Rp ' + item.subtotal_before_discount_formatted"></span>
                            </template>
                            <template x-if="!(item.discount_amount > 0)">
                                <span x-text="'Rp ' + item.subtotal_formatted"></span>
                            </template>
                        </div>

                        <template x-if="item.discount_amount > 0">
                            <div>
                                <div class="struk-discount-text" style="display: flex; justify-content: space-between; padding-left: 10px; color: #059669;">
                                    <span>- Diskon</span>
                                    <span x-text="'- Rp ' + item.discount_formatted"></span>
                                </div>
                                <div style="display: flex; justify-content: space-between; padding-left: 10px; font-weight: bold;">
                                    <span>Subtotal</span>
                                    <span x-text="'Rp ' + item.subtotal_formatted"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <hr>

                {{-- Total Diskon --}}
                <template x-if="saleNoteData.total_discount > 0">
                    <div>
                        <div style="display: flex; justify-content: space-between; color: #059669; font-weight: bold; margin-bottom: 3px;">
                            <span>TOTAL HEMAT</span>
                            <span x-text="'Rp ' + saleNoteData.total_discount_formatted"></span>
                        </div>
                        <div style="border-top: 1px dashed #059669; margin: 3px 0;"></div>
                    </div>
                </template>

                {{-- Total, Bayar, Kembali --}}
                <div style="display: flex; justify-content: space-between;">
                    <span>TOTAL</span>
                    <span x-text="'Rp ' + saleNoteData.total_harga_formatted"></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>BAYAR</span>
                    <span x-text="'Rp ' + saleNoteData.bayar_formatted"></span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: bold;">
                    <span>KEMBALI</span>
                    <span x-text="'Rp ' + saleNoteData.kembalian_formatted"></span>
                </div>

                <hr>

                {{-- Footer --}}
                <div class="struk-footer" style="text-align: center; margin-top: 15px;">
                    -- TERIMA KASIH --<br>
                    Barang yang sudah dibeli<br>
                    tidak dapat ditukar/dikembalikan
                </div>
            </div>
        </template>
    </div>
</div>