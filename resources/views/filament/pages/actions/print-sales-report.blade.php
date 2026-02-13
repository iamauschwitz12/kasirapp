<div x-data="{
    salesData: null,
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

        this.salesData = data;
        
        // Wait for Alpine to update DOM
        this.$nextTick(() => {
            setTimeout(() => {
                window.print();
                this.isPrinting = false;
            }, 500);
        });
    }
}" x-on:print-sales-report.window="handlePrint($event)">

    <style>
        @media screen {
            #sales-report-container {
                display: none;
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #sales-report-container,
            #sales-report-container * {
                visibility: visible;
            }

            #sales-report-container {
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

    <div id="sales-report-container" x-show="salesData">
        <template x-if="salesData">
            <div style="max-width: 800px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="margin: 0; font-size: 24px;">LAPORAN PENJUALAN</h1>
                    <p style="margin: 5px 0; color: #666;" x-text="salesData.filters || 'Semua Data'"></p>
                </div>

                <div style="margin-bottom: 20px;">
                    <p><strong>Dicetak pada:</strong> <span x-text="salesData.printed_at"></span></p>
                    <p x-show="salesData.cashier"><strong>Kasir:</strong> <span x-text="salesData.cashier"></span></p>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center; width: 50px;">No</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Tanggal</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">No. Transaksi</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Kasir</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Total Belanja</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: right;">Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in salesData.items" :key="item.nomor_transaksi">
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="index + 1"></td>
                                <td style="padding: 8px; border: 1px solid #ddd;" x-text="item.tanggal"></td>
                                <td style="padding: 8px; border: 1px solid #ddd;" x-text="item.nomor_transaksi"></td>
                                <td style="padding: 8px; border: 1px solid #ddd;" x-text="item.kasir"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;"
                                    x-text="item.total_harga"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: right;"
                                    x-text="item.bayar"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f5f5f5; font-weight: bold;">
                            <td colspan="4" style="padding: 10px; border: 1px solid #ddd; text-align: right;">GRAND
                                TOTAL:</td>
                            <td style="padding: 10px; border: 1px solid #ddd; text-align: right;">
                                <span x-text="salesData.total_amount"></span>
                            </td>
                            <td style="padding: 10px; border: 1px solid #ddd;"></td>
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