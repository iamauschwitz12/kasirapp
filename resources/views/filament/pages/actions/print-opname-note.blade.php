<div x-data="{
    opnameNoteData: null,
    isPrinting: false,
    handlePrint(event) {
        if (this.isPrinting) return;
        this.isPrinting = true;

        console.log('Print event received:', event);
        
        // Handle both single record and array format
        let data = event.detail;
        if (Array.isArray(data)) {
            data = data[0]; 
        }

        if (!data || !data.items) {
            console.error('Invalid data received', data);
            this.isPrinting = false;
            return;
        }

        this.opnameNoteData = data;
        
        // Wait for Alpine to update DOM
        this.$nextTick(() => {
            setTimeout(() => {
                window.print();
                this.isPrinting = false;
            }, 500);
        });
    }
}" x-on:print-opname-note.window="handlePrint($event)">

    <style>
        @media screen {
            #opname-note-container {
                display: none;
            }
        }

        @media print {
            body * {
                visibility: hidden;
            }

            #opname-note-container,
            #opname-note-container * {
                visibility: visible;
            }

            #opname-note-container {
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

    <div id="opname-note-container" x-show="opnameNoteData">
        <template x-if="opnameNoteData">
            <div style="max-width: 800px; margin: 0 auto; padding: 20px; font-family: Arial, sans-serif;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h1 style="margin: 0; font-size: 24px;">BERITA ACARA OPNAME TOKO</h1>
                    <p style="margin: 5px 0; color: #666;">Laporan Hasil Perhitungan Stok Fisik</p>
                </div>

                <div style="margin-bottom: 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold; width: 130px;">Tanggal Opname:</td>
                                <td style="padding: 5px 0;" x-text="opnameNoteData.tanggal_opname"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Toko / Cabang:</td>
                                <td style="padding: 5px 0;" x-text="opnameNoteData.nama_toko"></td>
                            </tr>
                        </table>
                    </div>
                    <div>
                        <table style="width: 100%;">
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold; width: 130px;">PIC Opname:</td>
                                <td style="padding: 5px 0;" x-text="opnameNoteData.pic_opname"></td>
                            </tr>
                            <tr>
                                <td style="padding: 5px 0; font-weight: bold;">Dicetak Pada:</td>
                                <td style="padding: 5px 0;"
                                    x-text="new Date().toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' })">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <table style="width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px;">
                    <thead>
                        <tr style="background-color: #f5f5f5;">
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center; width: 40px;">No</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Nama Barang</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Stok Sistem</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Stok Fisik</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Hasil</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: center;">Status</th>
                            <th style="padding: 10px; border: 1px solid #ddd; text-align: left;">Ket</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(item, index) in opnameNoteData.items" :key="index">
                            <tr>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="index + 1"></td>
                                <td style="padding: 8px; border: 1px solid #ddd;" x-text="item.nama_barang"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="item.stok_sistem_display"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="item.stok_fisik_display"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center; font-weight: bold;"
                                    :style="item.selisih_pcs < 0 ? 'color: red;' : (item.selisih_pcs > 0 ? 'color: orange;' : 'color: green;')"
                                    x-text="item.selisih_display"></td>
                                <td style="padding: 8px; border: 1px solid #ddd; text-align: center;"
                                    x-text="item.status_opname"></td>
                                <td style="padding: 8px; border: 1px solid #ddd;" x-text="item.keterangan || '-'"></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr style="background-color: #f9f9f9; font-weight: bold;">
                            <td colspan="2" style="padding: 10px; border: 1px solid #ddd; text-align: right;">TOTAL
                                ITEM:</td>
                            <td colspan="5" style="padding: 10px; border: 1px solid #ddd; text-align: left;"
                                x-text="opnameNoteData.items.length + ' Items'"></td>
                        </tr>
                    </tfoot>
                </table>

                <div
                    style="margin-top: 60px; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; text-align: center;">
                    <div>
                        <p style="margin: 0; font-weight: bold;">Dihitung Oleh (PIC),</p>
                        <div style="height: 70px;"></div>
                        <p style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 150px;"
                            x-text="opnameNoteData.pic_opname"></p>
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: bold;">Saksi / Toko,</p>
                        <div style="height: 70px;"></div>
                        <p
                            style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 150px;">
                            (________________)</p>
                    </div>
                    <div>
                        <p style="margin: 0; font-weight: bold;">Menyetujui (Admin),</p>
                        <div style="height: 70px;"></div>
                        <p
                            style="margin: 0; border-top: 1px solid #000; display: inline-block; padding-top: 5px; min-width: 150px;">
                            (________________)</p>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>