<?php

namespace App\Filament\Resources\PenjualanStoks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use App\Models\PenjualanStok;
use Illuminate\Support\Facades\DB;


class PenjualanStoksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Group by Invoice to show 1 row per Invoice
                return $query
                    ->select(
                        'no_inv',
                        DB::raw('MAX(id) as id'), // Required for actions
                        DB::raw('MAX(pengirim_id) as pengirim_id'),
                        DB::raw('MAX(toko_id) as toko_id'),
                        DB::raw('MAX(asal_gudang_id) as asal_gudang_id'),
                        DB::raw('MAX(created_at) as created_at'),
                        DB::raw('COUNT(*) as total_items'),
                        DB::raw('SUM(qty) as total_qty') // Total items in pieces
                    )
                    ->groupBy('no_inv');
            })
            ->columns([
                TextColumn::make('no_inv')
                    ->label('No. Invoice')
                    ->searchable(),

                TextColumn::make('pengirim.nama_pengirim')
                    ->label('Nama Pengirim')
                    ->sortable(),

                TextColumn::make('toko.nama_toko') // Menampilkan nama toko
                    ->label('Toko Tujuan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('asalGudang.nama_gudang')
                    ->label('Asal Gudang'),

                // Since we group by invoice, listing a single product is misleading if there are multiple.
                // Instead, we show the count of items.
                TextColumn::make('total_items')
                    ->label('Jml Jenis Barang')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('total_qty')
                    ->label('Total Pcs')
                    ->numeric()
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Tanggal Masuk')
                    ->dateTime(),
            ])
            ->filters([
                // 1. Filter berdasarkan Produk (Might be tricky with Group By logic if not careful, but usually ok as WHERE runs before GROUP BY)
                SelectFilter::make('product_id')
                    ->label('Filter Produk (Terdapat Dalam Invoice)')
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
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
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
                DeleteAction::make()
                    ->visible(fn() => auth()->user()->isAdmin())
                    ->action(function (PenjualanStok $record) {
                        // Delete all items for this invoice
                        // Also decrement stock? Or is it handled by model events? 
                        // Our custom Edit page handled it manually. Delete action here should also handle it.
                        // But wait, standard DeleteAction usually deletes $record.
                        // Since we group, $record is just one representative.
            
                        $items = PenjualanStok::where('no_inv', $record->no_inv)->get();
                        foreach ($items as $item) {
                            // Decrement stock
                            $product = \App\Models\Product::find($item->product_id);
                            if ($product) {
                                $product->decrement('stok', $item->qty);
                            }
                            $item->delete();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
