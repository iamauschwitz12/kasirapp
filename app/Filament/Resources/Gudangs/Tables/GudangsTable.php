<?php

namespace App\Filament\Resources\Gudangs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ViewColumn;


class GudangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_invoice')
                ->label('No. Invoice')
                ->searchable(),
                TextColumn::make('supplier.nama_supplier')
                ->label('Nama Supplier')
                ->searchable(),
                ViewColumn::make('product.barcode_number')
                    ->label('Barcode')
                    ->view('filament.tables.columns.barcode-display-keluar'),
                TextColumn::make('product.nama_produk')
                    ->label('Nama Barang')
                    ->searchable(),
                TextColumn::make('cabang.nama_cabang')
                    ->label('Cabng Drop')
                    ->alignCenter(),
                TextColumn::make('unitSatuan.nama_satuan')
                    ->label('unit Satuan')
                    ->alignCenter(),
                TextColumn::make('qty')
                    ->label('QTY Masuk')
                    ->alignCenter(),
                TextColumn::make('tgl_masuk')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('tgl_masuk')
                ->form([
                    DatePicker::make('dari_tanggal'),
                    DatePicker::make('sampai_tanggal'),
                ])
                ->query(function ($query, array $data) {
                    return $query
                        ->when($data['dari_tanggal'], fn($q) => $q->whereDate('tgl_masuk', '>=', $data['dari_tanggal']))
                        ->when($data['sampai_tanggal'], fn($q) => $q->whereDate('tgl_masuk', '<=', $data['sampai_tanggal']));
                }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
