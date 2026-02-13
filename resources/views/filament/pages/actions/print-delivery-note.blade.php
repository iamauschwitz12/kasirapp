<div x-data="{
    deliveryNoteData: null,
    isPrinting: false,
    handlePrint(event) {
        if (this.isPrinting) return;
        this.isPrinting = true;

        console.log('Print event received:', event);
        
        let data = event.detail;
        if (Array.isArray(data)) {
            data = data.find(p => p && p.hasOwnProperty('no_referensi')) || data[0];
        }

        if (!data || !data.items) {
            console.error('Invalid data received', data);
            this.isPrinting = false;
            return;
        }

        this.deliveryNoteData = data;
        
        // Wait for Alpine to update DOM
        this.$nextTick(() => {
            setTimeout(() => {
                window.print();
                this.isPrinting = false;
            }, 500);
        });
    }
}" x-on:print-delivery-note.window="handlePrint($event)">

    <style>
        @media screen {
            #delivery-note-container {
                display: none;
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #delivery-note-container,
            #delivery-note-container * {
                visibility: visible;
            }

            #delivery-note-container {
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

    <div id="delivery-note-container" x-show="deliveryNoteData">
        <template x-if="deliveryNoteData">
            <div style="max-width: 800px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="margin: 0; font-size: 24px;">NOTA PENGIRIMAN</h1>
                    <p style="margin: 5px 0; color: #666;">Delivery Note</p>
                </div>

                <div style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold; width: 150px;">Tanggal Transaksi:</td>
                                <td style="padding: 5px 0;" x-text="deliveryNoteData.tanggal_transaksi"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Toko Tujuan:</td>
                                <td style="padding: 5px 0;" x-text="deliveryNoteData.toko_tujuan"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">PIC:</td>
                                <td style="padding: 5px 0;" x-text="deliveryNoteData.user_name"></td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold; width: 150px;">No. Referensi:</td>
                                <td style="padding: 5px 0;" x-text="deliveryNoteData.no_referensi"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Tanggal Keluar:</td>
                                <td style="padding: 5px 0;" x-text="deliveryNoteData.tanggal_keluar"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Cabang Asal:</td>
                                <td style="padding: 5px 0;" x-text="deliveryNoteData.cabang_asal"></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center; width: 50px;">No</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Nama Barang</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Satuan</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Jumlah Keluar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in deliveryNoteData.items" :key="item.nama_produk">
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="index + 1"></td>
                                <td style="padding: 8px; border: 1px solid #ddd;" x-text="item.nama_produk"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="item.satuan"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;" x-text="item.qty">
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f5f5f5; font-weight: bold;">
                            <td colspan="3" style="padding: 10px; border: 1px solid #ddd; text-align: right;">TOTAL
                                ITEM:</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: center;">
                                <span
                                    x-text="deliveryNoteData.items.reduce((acc, curr) => acc + parseInt(curr.qty), 0)"></span>
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
                        <p style="margin: 0; font-weight: bold;">Diterima Oleh,</p>
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