<div x-data="{
    gudangData: null,
    isPrinting: false,
    handlePrint(event) {
        if (this.isPrinting) return;
        this.isPrinting = true;

        console.log('Print event received:', event);
        
        let data = event.detail;
        if (Array.isArray(data)) {
            data = data.find(p => p && p.hasOwnProperty('items')) || data[0];
        }

        if (!data || !data.items) {
            console.error('Invalid data received', data);
            this.isPrinting = false;
            return;
        }

        this.gudangData = data;
        
        // Wait for Alpine to update DOM
        this.$nextTick(() => {
            setTimeout(() => {
                window.print();
                this.isPrinting = false;
            }, 500);
        });
    }
}" x-on:print-gudang-report.window="handlePrint($event)">

    <style>
        @media screen {
            #gudang-report-container {
                display: none;
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #gudang-report-container,
            #gudang-report-container * {
                visibility: visible;
            }

            #gudang-report-container {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            @page {
                margin: 10mm;
                size: landscape;
            }
        }
    </style>

    <div id="gudang-report-container" x-show="gudangData">
        <template x-if="gudangData">
            <div style="width: 100%; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="margin: 0; font-size: 24px;">LAPORAN BARANG MASUK GUDANG</h1>
                    <p style="margin: 5px 0; color: #666;" x-text="gudangData.filters || 'Semua Data'"></p>
                </div>

                <div style="margin-bottom: 20px;">
                    <p><strong>Dicetak pada:</strong> <span x-text="gudangData.printed_at"></span></p>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: center; width: 40px;">No</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tanggal Masuk</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">No. Invoice</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Cabang</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nama Barang</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: center;">Qty</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Harga Beli</th>
                            <th style="padding: 8px; border: 1px solid #ddd; text-align: right;">Total Beli</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in gudangData.items" :key="index">
                            <tr>
                                <td style="padding: 6px; border: 1px solid #ddd; text-align: center;"
                                    x-text="index + 1"></td>
                                <td style="padding: 6px; border: 1px solid #ddd;" x-text="item.tgl_masuk"></td>
                                <td style="padding: 6px; border: 1px solid #ddd;" x-text="item.no_invoice"></td>
                                <td style="padding: 6px; border: 1px solid #ddd;" x-text="item.cabang"></td>
                                <td style="padding: 6px; border: 1px solid #ddd;" x-text="item.nama_barang"></td>
                                <td style="padding: 6px; border: 1px solid #ddd; text-align: center;" x-text="item.qty">
                                </td>
                                <td style="padding: 6px; border: 1px solid #ddd; text-align: right;"
                                    x-text="item.harga_beli"></td>
                                <td style="padding: 6px; border: 1px solid #ddd; text-align: right;"
                                    x-text="item.total_harga"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f5f5f5; font-weight: bold;">
                            <td colspan="7" style="padding: 8px; border: 1px solid #ddd; text-align: right;">GRAND
                                TOTAL:</td>
                            <td style="padding: 8px; border: 1px solid #ddd; text-align: right;">
                                <span x-text="gudangData.total_amount"></span>
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <div
                    style="margin-top: 50px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; text-align: center;">
                    <div></div>
                    <div>
                        <p style="margin: 0; font-weight: bold;">Mengetahui,</p>
                        <div style="height: 60px;"></div>
                        <p
                            style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 150px;">
                            ( Owner / Manager )</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>