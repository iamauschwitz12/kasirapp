<?php

namespace App\Filament\Resources\PenjualanStoks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables;;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;


class PenjualanStoksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_inv')->label('No. Invoice'),
                TextColumn::make('pengirim.nama_pengirim')->label('Nama Pengirim')->sortable(),
                TextColumn::make('asalGudang.nama_gudang')->label('Asal Gudang'),
                TextColumn::make('product.nama_produk')->label('Produk'),
                TextColumn::make('qty')
                ->label('Kuantitas')
                ->formatStateUsing(function ($record) {
                    $konversi = $record->product->isi_konversi ?? 1;
                    $utama = floor($record->qty / $konversi);
                    $sisa = $record->qty % $konversi;
                    $satuanBesar = $record->product->satuan_besar ?? 'Ikat';
                    $satuanKecil = $record->product->unitSatuan->nama_satuan ?? 'Pcs';

                    if ($utama > 0 && $sisa > 0) return "{$utama} {$satuanBesar} + {$sisa} {$satuanKecil}";
                    return $utama > 0 ? "{$utama} {$satuanBesar}" : "{$sisa} {$satuanKecil}";
                }),
                TextColumn::make('created_at')->label('Tanggal Masuk')->dateTime(),
            ])
            ->filters([
                // 1. Filter berdasarkan Produk
                SelectFilter::make('product_id')
                    ->label('Filter Produk')
                    ->relationship('product', 'nama_produk')
                    ->searchable()
                    ->preload(),

                // 2. Filter berdasarkan Pengirim
                SelectFilter::make('pengirim_id')
                    ->label('Filter Pengirim')
                    ->relationship('pengirim', 'nama_pengirim')
                    ->searchable()
                    ->preload(),

                // 3. Filter berdasarkan Asal Gudang
                SelectFilter::make('asal_gudang_id')
                    ->label('Asal Gudang')
                    ->relationship('asalGudang', 'nama_gudang'),

                // 4. Filter Rentang Tanggal (Sangat Berguna untuk Stok Masuk)
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['dari_tanggal'] ?? null) {
                            $indicators[] = 'Dari: ' . \Carbon\Carbon::parse($data['dari_tanggal'])->toFormattedDateString();
                        }
                        if ($data['sampai_tanggal'] ?? null) {
                            $indicators[] = 'Sampai: ' . \Carbon\Carbon::parse($data['sampai_tanggal'])->toFormattedDateString();
                        }
                        return $indicators;
                    }),
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
