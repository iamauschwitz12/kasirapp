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
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Columns\Summarizers\Sum;


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
                    ->label('Cabng')
                    ->alignCenter(),
                TextColumn::make('unitSatuan.nama_satuan')
                    ->label('unit Satuan')
                    ->alignCenter(),
                TextColumn::make('qty')
                    ->label('QTY Masuk')
                    ->alignCenter(),
                TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('idr', true)
                    ->alignRight()
                    ->summarize([
                    Sum::make()->label('Total')
                    ]),
                TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->money('idr', true)
                    ->alignRight()
                    ->summarize([
                    Sum::make()->label('Total')
                    ]),
                TextColumn::make('tgl_masuk')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('rentang_tanggal') // Beri nama unik untuk filternya
                ->form([
                    DatePicker::make('dari_tanggal')
                        ->label('Dari Tanggal'),
                    DatePicker::make('sampai_tanggal')
                        ->label('Sampai Tanggal'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    // Gunakan return dan akses $data dengan kunci yang tepat
                    return $query
                        ->when(
                            $data['dari_tanggal'],
                            fn (Builder $query, $date): Builder => $query->whereDate('tgl_masuk', '>=', $date),
                        )
                        ->when(
                            $data['sampai_tanggal'],
                            fn (Builder $query, $date): Builder => $query->whereDate('tgl_masuk', '<=', $date),
                        );
                })
                ->indicateUsing(function (array $data): array {
                    $indicators = [];
                    if ($data['dari_tanggal'] ?? null) {
                        $indicators['dari_tanggal'] = 'Sejak: ' . $data['dari_tanggal'];
                    }
                    if ($data['sampai_tanggal'] ?? null) {
                        $indicators['sampai_tanggal'] = 'Hingga: ' . $data['sampai_tanggal'];
                    }
                    return $indicators;
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
