<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Gudang;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;

use Illuminate\Support\Facades\Auth;

class GudangDashboardTable extends BaseWidget
{
    protected static ?int $sort = 2; // Tampilan di bawah Sales
    protected int|string|array $columnSpan = 1;

    public static function canView(): bool
    {
        return Auth::user() && Auth::user()->isAdmin();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Gudang::query()->latest('tgl_masuk'))
            ->heading('Informasi Gudang Masuk')
            ->columns([
                Tables\Columns\TextColumn::make('tgl_masuk')
                    ->label('Tanggal Masuk')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('no_invoice')
                    ->label('No. Invoice')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('product.nama_produk')
                    ->label('Nama Barang')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty Masuk'),
                Tables\Columns\TextColumn::make('harga_beli')
                    ->label('Harga Beli')
                    ->money('IDR'),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Beli')
                    ->money('IDR')
                    ->sortable()
                    ->summarize([
                        Sum::make()->label('Total')
                    ]),
            ])
            ->filters([
                SelectFilter::make('cabang_id')
                    ->label('Filter Cabang')
                    ->relationship('cabang', 'nama_cabang'),
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
                                fn(Builder $query, $date): Builder => $query->whereDate('tgl_masuk', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tgl_masuk', '<=', $date),
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
                                fn(Builder $query, $date): Builder => $query->whereMonth('tgl_masuk', $date)
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
                                fn(Builder $query, $year): Builder => $query->whereYear('tgl_masuk', $year)
                            );
                    }),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('print_report')
                    ->label('Cetak Laporan')
                    ->icon('heroicon-o-printer')
                    ->action(function ($livewire) {
                        $query = $livewire->getFilteredTableQuery();
                        $gudangs = $query->get();

                        $totalAmount = $gudangs->sum('total_harga');

                        $items = $gudangs->map(function ($gudang) {
                            return [
                                'tgl_masuk' => $gudang->tgl_masuk->format('d/m/Y'),
                                'no_invoice' => $gudang->no_invoice,
                                'cabang' => $gudang->cabang->nama_cabang ?? '-',
                                'nama_barang' => $gudang->product->nama_produk ?? '-',
                                'qty' => $gudang->qty,
                                'harga_beli' => 'Rp ' . number_format($gudang->harga_beli, 0, ',', '.'),
                                'total_harga' => 'Rp ' . number_format($gudang->total_harga, 0, ',', '.'),
                            ];
                        })->toArray();

                        $filterInfo = [];
                        $filters = $livewire->tableFilters;

                        if (!empty($filters['cabang_id']['value'])) {
                            $cabang = \App\Models\Cabang::find($filters['cabang_id']['value']);
                            if ($cabang)
                                $filterInfo[] = "Cabang: " . $cabang->nama_cabang;
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

                        $livewire->dispatch('print-gudang-report', $printData);
                    }),
            ]);
    }
}
