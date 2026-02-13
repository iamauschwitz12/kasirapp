<div x-data="{
    purchaseNoteData: null,
    isPrinting: false,
    handlePrint(event) {
        if (this.isPrinting) return;
        this.isPrinting = true;

        console.log('Print event received:', event);
        
        let data = event.detail;
        if (Array.isArray(data)) {
            data = data.find(p => p && p.hasOwnProperty('no_invoice')) || data[0];
        }

        if (!data || !data.items) {
            console.error('Invalid data received', data);
            this.isPrinting = false;
            return;
        }

        this.purchaseNoteData = data;
        
        // Wait for Alpine to update DOM
        this.$nextTick(() => {
            setTimeout(() => {
                window.print();
                this.isPrinting = false;
            }, 500);
        });
    }
}" x-on:print-purchase-note.window="handlePrint($event)">

    <style>
        @media screen {
            #purchase-note-container {
                display: none;
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #purchase-note-container,
            #purchase-note-container * {
                visibility: visible;
            }

            #purchase-note-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            @page {
                margin: 20mm;
            }
        }
    </style>

    <div id="purchase-note-container" x-show="purchaseNoteData">
        <template x-if="purchaseNoteData">
            <div style="max-width: 800px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="margin: 0; font-size: 24px;">NOTA PEMBELIAN</h1>
                    <p style="margin: 5px 0; color: #666;">Purchase Order</p>
                </div>

                <div style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold; width: 150px;">Tanggal Transaksi:</td>
                                <td style="padding: 5px 0;" x-text="purchaseNoteData.tanggal_transaksi"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Supplier:</td>
                                <td style="padding: 5px 0;" x-text="purchaseNoteData.supplier"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">PIC:</td>
                                <td style="padding: 5px 0;" x-text="purchaseNoteData.user_name"></td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold; width: 150px;">No. Invoice:</td>
                                <td style="padding: 5px 0;" x-text="purchaseNoteData.no_invoice"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Tanggal Masuk:</td>
                                <td style="padding: 5px 0;" x-text="purchaseNoteData.tanggal_masuk"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Cabang Tujuan:</td>
                                <td style="padding: 5px 0;" x-text="purchaseNoteData.cabang"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center; width: 50px;">No</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Nama Barang</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Satuan</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;"
                            x-show="purchaseNoteData.show_prices">Harga Beli</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Jumlah Masuk</th>
                        <th style="padding: 10px; border: 1px solid #ddd; text-align: right;"
                            x-show="purchaseNoteData.show_prices">Total Harga</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in purchaseNoteData.items" :key="item.nama_produk">
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="index + 1"></td>
                                <td style="padding: 8px; border: 1px solid #ddd;" x-text="item.nama_produk"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="item.satuan"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;"
                                    x-text="item.harga_beli" x-show="purchaseNoteData.show_prices"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;" x-text="item.qty">
                                </td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;"
                                    x-text="item.total_harga" x-show="purchaseNoteData.show_prices"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <!-- Total Qty Row -->
                        <tr style="background-color: #f5f5f5; font-weight: bold;">
                            <td :colspan="purchaseNoteData.show_prices ? 4 : 3"
                                style="padding: 10px; border: 1px solid #ddd; text-align: right;">TOTAL ITEM:</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;"
                                x-text="purchaseNoteData.items.reduce((acc, curr) => acc + parseInt(curr.qty), 0)">
                            </td>
                            <td x-show="purchaseNoteData.show_prices" style="padding: 10px; border: 1px solid #ddd;">
                            </td>
                        </tr>
                        <!-- Grand Total Row -->
                        <tr style="background-color: #f5f5f5; font-weight: bold;" x-show="purchaseNoteData.show_prices">
                            <td colspan="5" style="padding: 10px; border: 1px solid #ddd; text-align: right;">GRAND
                                TOTAL:</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">
                                Rp <span
                                    x-text="purchaseNoteData.items.reduce((acc, curr) => acc + parseFloat(curr.total_harga_raw), 0).toLocaleString('id-ID')"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div
                    style="margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center;">
                    <div>
                        <p style="margin: 0; font-weight: bold;">Dibuat Oleh,</p>
                        <div style="height: 60px;"></div>
                        <p
                            style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 150px;">
                            (_____________)</p>
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: bold;">Diperiksa Oleh,</p>
                        <div style="height: 60px;"></div>
                        <p
                            style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 150px;">
                            (_____________)</p>
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: bold;">Disetujui Oleh,</p>
                        <div style="height: 60px;"></div>
                        <p
                            style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 150px;">
                            (_____________)</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>