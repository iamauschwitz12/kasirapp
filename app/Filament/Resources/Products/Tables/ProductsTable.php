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
                TextColumn::make('stok_lengkap') // Memanggil Accessor dari Model
                ->label('Stok Saat Ini')
                ->badge()
                ->color('success')
                // Baris di bawah ini penting agar pengurutan (sorting) tetap akurat berdasarkan angka asli
                ->sortable(['stok']),
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
                    ->modalContent(fn ($record) => view('filament.pages.actions.print-barcode', ['record' => $record])),
                
                    \Filament\Actions\Action::make('tambahStok')
                    ->label('Tambah Stok')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        TextInput::make('jumlah_tambah')
                            ->label('Jumlah (Satuan Besar)')
                            ->numeric()
                            ->required()
                            ->helperText(fn (Product $record) => "Akan menambah " . ($record->isi_konversi ?? 1) . " pcs per unit."),
                    ])
                    ->action(function (Product $record, array $data): void {
                        $konversi = $record->isi_konversi ?: 1;
                        $tambahanPcs = $data['jumlah_tambah'] * $konversi;
                        
                        $record->increment('stok', $tambahanPcs);
                        
                        Notification::make()
                            ->title('Stok berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
