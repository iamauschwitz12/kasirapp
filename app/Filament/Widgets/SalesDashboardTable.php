<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Sale;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Support\RawJs;


use Illuminate\Support\Facades\Auth;

class SalesDashboardTable extends BaseWidget
{
    protected static ?int $sort = 1; // Tampilan paling atas
    protected int|string|array $columnSpan = 1; // Lebar 1/2 layar (jika default 2 kolom)

    public static function canView(): bool
    {
        return Auth::user() && Auth::user()->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Sale::query()->latest())
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->date('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nomor_transaksi')
                    ->label('No. Transaksi')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Belanja')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total')
                    ]),
                Tables\Columns\TextColumn::make('bayar')
                    ->label('Bayar')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Kasir')
                    ->placeholder('Kasir Tidak Terdeteksi') // Jika user_id kosong
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Kasir')
                    ->relationship('user', 'name', fn(Builder $query) => $query->where('role', 'kasir'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('rentang_tanggal')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('sampai_tanggal')
                            ->label('Sampai Tanggal'),
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
                    }),
                SelectFilter::make('bulan')
                    ->label('Filter Bulan')
                    ->options([
                        1 => 'Januari',
                        2 => 'Februari',
                        3 => 'Maret',
                        4 => 'April',
                        5 => 'Mei',
                        6 => 'Juni',
                        7 => 'Juli',
                        8 => 'Agustus',
                        9 => 'September',
                        10 => 'Oktober',
                        11 => 'November',
                        12 => 'Desember',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query, $date): Builder => $query->whereMonth('created_at', $date)
                            );
                    }),
                SelectFilter::make('tahun')
                    ->label('Filter Tahun')
                    ->options(function () {
                        $years = range(date('Y'), 2023);
                        return array_combine($years, $years);
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn(Builder $query, $year): Builder => $query->whereYear('created_at', $year)
                            );
                    }),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('print_report')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredTableQuery(); // Get filtered query
                        $sales = $query->get(); // Execute query
            
                        // Calculate grand total
                        $totalAmount = $sales->sum('total_harga');

                        // Format data for print
                        $items = $sales->map(function ($sale) {
                            return [
                                'tanggal' => $sale->created_at->format('d/m/Y H:i'),
                                'nomor_transaksi' => $sale->nomor_transaksi,
                                'kasir' => $sale->user->name ?? 'Kasir Tidak Terdeteksi',
                                'total_harga' => 'Rp ' . number_format($sale->total_harga, 0, ',', '.'),
                                'bayar' => 'Rp ' . number_format($sale->bayar, 0, ',', '.'),
                            ];
                        })->toArray();

                        // Get filter info for display
                        $filterInfo = [];
                        $filters = $livewire->tableFilters;

                        if (!empty($filters['user_id']['value'])) {
                            $user = \App\Models\User::find($filters['user_id']['value']);
                            if ($user)
                                $filterInfo[] = "Kasir: " . $user->name;
                        }

                        if (!empty($filters['rentang_tanggal']['dari_tanggal'])) {
                            $filterInfo[] = "Dari: " . \Carbon\Carbon::parse($filters['rentang_tanggal']['dari_tanggal'])->format('d M Y');
                        }

                        if (!empty($filters['rentang_tanggal']['sampai_tanggal'])) {
                            $filterInfo[] = "Sampai: " . \Carbon\Carbon::parse($filters['rentang_tanggal']['sampai_tanggal'])->format('d M Y');
                        }

                        if (!empty($filters['bulan']['value'])) {
                            $months = [
                                1 => 'Januari',
                                2 => 'Februari',
                                3 => 'Maret',
                                4 => 'April',
                                5 => 'Mei',
                                6 => 'Juni',
                                7 => 'Juli',
                                8 => 'Agustus',
                                9 => 'September',
                                10 => 'Oktober',
                                11 => 'November',
                                12 => 'Desember'
                            ];
                            $filterInfo[] = "Bulan: " . $months[$filters['bulan']['value']];
                        }

                        if (!empty($filters['tahun']['value'])) {
                            $filterInfo[] = "Tahun: " . $filters['tahun']['value'];
                        }


                        $printData = [
                            'printed_at' => now()->format('d M Y H:i'),
                            'filters' => implode(', ', $filterInfo),
                            'total_amount' => 'Rp ' . number_format($totalAmount, 0, ',', '.'),
                            'items' => $items,
                        ];

                        $livewire->dispatch('print-sales-report', $printData);
                    }),
            ]);
    }
}
