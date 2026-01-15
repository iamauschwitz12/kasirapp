<?php

namespace App\Filament\Resources\GudangKeluars\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;

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
