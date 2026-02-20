<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Models\Sale;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nomor_transaksi')
                    ->searchable()
                    ->sortable()
                    ->copyable() // Memudahkan kasir copy nomor transaksi
                    ->label('No. Transaksi'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Waktu')
                    ->sortable(),

                TextColumn::make('total_harga')->label('Total')->money('idr')
                    ->summarize(
                        Sum::make()
                            ->label('Total Penjualan')
                            ->money('idr') // Format IDR di bagian bawah tabel
                    ),
                TextColumn::make('bayar')->label('Bayar')->money('idr'),
                TextColumn::make('kembalian')->label('Kembali')->money('idr'),
                TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),

                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->placeholder('Kasir Tidak Terdeteksi') // Jika user_id kosong
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('dari'),
                        \Filament\Forms\Components\DatePicker::make('sampai'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['dari'], fn($q) => $q->whereDate('created_at', '>=', $data['dari']))
                            ->when($data['sampai'], fn($q) => $q->whereDate('created_at', '<=', $data['sampai']));
                    }),
                SelectFilter::make('user_id')
                    ->label('Berdasarkan Kasir')
                    ->relationship('user', 'name')
                    ->preload(),
            ])
            ->actions([
                \Filament\Actions\Action::make('print')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->action(function ($record, $livewire) {
                        // Load the sale with its items and related product data
                        $sale = Sale::with(['items.product', 'user'])->find($record->id);

                        if (!$sale) {
                            return;
                        }

                        $totalDiscount = $sale->items->sum('discount_amount');

                        $saleNoteData = [
                            'nomor_transaksi' => $sale->nomor_transaksi,
                            'waktu' => $sale->created_at->format('d/m/Y H:i'),
                            'kasir' => $sale->user->name ?? 'Admin',
                            'total_harga' => $sale->total_harga,
                            'total_harga_formatted' => number_format($sale->total_harga, 0, ',', '.'),
                            'bayar' => $sale->bayar,
                            'bayar_formatted' => number_format($sale->bayar, 0, ',', '.'),
                            'kembalian' => $sale->kembalian ?? ($sale->bayar - $sale->total_harga),
                            'kembalian_formatted' => number_format($sale->kembalian ?? ($sale->bayar - $sale->total_harga), 0, ',', '.'),
                            'total_discount' => $totalDiscount,
                            'total_discount_formatted' => number_format($totalDiscount, 0, ',', '.'),
                            'items' => $sale->items->map(function ($item) {
                                return [
                                    'nama_produk' => $item->product->nama_produk ?? '-',
                                    'satuan_pilihan' => $item->satuan_pilihan ?? 'pcs',
                                    'nama_satuan' => $item->nama_satuan ?? 'pcs',
                                    'qty' => $item->qty,
                                    'harga_saat_ini' => $item->harga_saat_ini,
                                    'harga_formatted' => number_format($item->harga_saat_ini, 0, ',', '.'),
                                    'subtotal' => $item->subtotal,
                                    'subtotal_formatted' => number_format($item->subtotal, 0, ',', '.'),
                                    'discount_amount' => $item->discount_amount ?? 0,
                                    'discount_formatted' => number_format($item->discount_amount ?? 0, 0, ',', '.'),
                                    'subtotal_before_discount' => $item->subtotal_before_discount ?? ($item->qty * $item->harga_saat_ini),
                                    'subtotal_before_discount_formatted' => number_format($item->subtotal_before_discount ?? ($item->qty * $item->harga_saat_ini), 0, ',', '.'),
                                ];
                            })->toArray(),
                        ];

                        // Dispatch event to browser for in-page printing
                        $livewire->dispatch('print-sale-note', $saleNoteData);
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
