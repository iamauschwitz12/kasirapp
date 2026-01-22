<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Summarizers\Sum;

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
            ->recordActions([
                // TAMBAHKAN AKSI CETAK DI SINI
                \Filament\Actions\Action::make('print')
                ->label('Cetak Nota')
                ->icon('heroicon-o-printer')
                ->action(function ($record, $livewire) {
                    // Ambil data yang dibutuhkan untuk struk
                    $receiptData = [
                        'nomor_transaksi' => $record->nomor_transaksi,
                        'tanggal' => $record->created_at->format('d/m/Y'),
                        'jam' => $record->created_at->format('H:i'),
                        'total' => number_format($record->total_harga, 0, ',', '.'),
                        'total_qty' => $record->items->sum('qty'),
                        'bayar' => number_format($record->bayar, 0, ',', '.'),
                        
                        // PERBAIKAN DI SINI: Ganti 'kembali' menjadi 'kembalian'
                        // Dan tambahkan perhitungan manual (fallback) jika data di DB masih 0
                        'kembali' => number_format($record->kembalian ?? ($record->bayar - $record->total_harga), 0, ',', '.'),
                        
                        'items' => $record->items->map(function($item) {
                            return [
                                'nama_produk' => $item->product->nama_produk,
                                'qty' => $item->qty,
                                // Jika grosir, gunakan satuan besar, jika eceran gunakan satuan kecil
                                'nama_satuan' => $item->nama_satuan ?? 'pcs', 
                                'harga' => number_format($item->harga_saat_ini, 0, ',', '.'),
                                'subtotal' => number_format($item->subtotal, 0, ',', '.'),
                            ];
                        })->toArray(),
                    ];

                    // Kirim event ke browser
                    $livewire->dispatch('print-receipt', $receiptData);
                    
                    Notification::make()
                        ->title('Mencetak Nota...')
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
