<?php

namespace App\Filament\Resources\GudangKeluars\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class GudangKeluarsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_referensi')->label('No. Ref'),
                TextColumn::make('toko.nama_toko') // Menampilkan nama toko
                    ->label('Toko Tujuan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('cabang.nama_cabang') // Menampilkan nama toko
                    ->label('Cabang Tujuan')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.nama_produk')->label('Nama Barang'),
                ViewColumn::make('product.barcode_number')
                    ->label('Barcode')
                    ->view('filament.tables.columns.barcode-display-keluar'),
                TextColumn::make('qty')->label('QTY'),
                TextColumn::make('tgl_keluar')->date(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Excel')
                    ->exports([
                        ExcelExport::make('gudangs_keluar')
                            // kita tidak pakai fromTable() di sini, tetapi bisa juga kombinasikan
                            ->withColumns([
                                Column::make('no_referensi')->heading('No. Invoice'),
                                Column::make('toko.nama_toko')->heading('Toko Tujuan'),
                                Column::make('cabang.nama_cabang')->heading('Cabang Tujuan'),
                                Column::make('product.nama_produk')->heading('Nama Barang'),
                                Column::make('product.barcode_number')->heading('Barcode'),
                                Column::make('qty')->heading('QTY'),
                                Column::make('tgl_keluar')->heading('Tgl Keluar'),
                                Column::make('created_at')->heading('Tanggal Input'),
                            ])
                            ->withFilename('gudangs_keluar-' . now()->format('Y-m-d'))
                            ->fromTable() // jika mau sumber datanya tetap berasal dari table query (filter/search ikut)
                            ->withChunkSize(500),
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
