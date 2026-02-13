<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\Action;
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
            ->actions([
                // TAMBAHKAN AKSI CETAK DI SINI
                // TAMBAHKAN AKSI CETAK DI SINI
                Action::make('print')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->url(fn($record) => route('print.struk', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
