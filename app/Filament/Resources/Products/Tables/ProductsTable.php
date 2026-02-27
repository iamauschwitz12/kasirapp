<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use App\Models\Product;
use Filament\Forms\Components\TextInput;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;


class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ViewColumn::make('barcode_number')
                    ->view('filament.tables.columns.barcode-display'),
                TextColumn::make('nama_produk')->label('Nama Produk')->searchable()->sortable(),
                TextColumn::make('kode')->label('Kode Produk')->searchable()->sortable(),
                TextColumn::make('unitSatuan.nama_satuan')->label('Satuan')->searchable()->sortable(),
                TextColumn::make('harga')->label('Harga')->money('idr', true)->sortable(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Excel')
                    ->exports([
                        ExcelExport::make('products')
                            ->withColumns([
                                Column::make('nama_produk')->heading('Nama Produk'),
                                Column::make('kode')->heading('Kode Produk'),
                                Column::make('barcode_number')->heading('Barcode'),
                                Column::make('unitSatuan.nama_satuan')->heading('Satuan'),
                                Column::make('harga')->heading('Harga Eceran'),
                                Column::make('harga_grosir')->heading('Harga Grosir'),
                                Column::make('satuan_besar')->heading('Satuan Besar'),
                                Column::make('isi_konversi')->heading('Isi Konversi'),
                                Column::make('stok')->heading('Stok'),
                            ])
                            ->withFilename('products-' . now()->format('Y-m-d'))
                            ->fromTable()
                            ->withChunkSize(500),
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([

            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('print_barcode')
                    ->label('Cetak Barcode')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->modalHeading('Cetak Barcode Produk')
                    ->modalSubmitAction(false) // Kita hilangkan tombol submit modal bawaan
                    ->modalContent(fn($record) => view('filament.pages.actions.print-barcode', ['record' => $record])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
