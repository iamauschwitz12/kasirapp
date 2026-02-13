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
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use App\Models\Gudang;
use Illuminate\Support\Facades\DB;

class GudangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Filter by cabang_id if user is not admin
                $user = auth()->user();
                if ($user && $user->role !== 'admin' && $user->cabang_id) {
                    $query->where('cabang_id', $user->cabang_id);
                }

                // IMPORTANT: We must select 'id' to make Filament actions work. 
                // Since we group by 'no_invoice', we pick MAX(id) to represent the row.
                return $query
                    ->select(
                        'no_invoice',
                        DB::raw('MAX(id) as id'),
                        DB::raw('MAX(supplier_id) as supplier_id'),
                        DB::raw('MAX(cabang_id) as cabang_id'),
                        DB::raw('MAX(tgl_masuk) as tgl_masuk'),
                        DB::raw('MAX(created_at) as created_at'),
                        DB::raw('MAX(user_id) as user_id'),
                        DB::raw('COUNT(*) as total_items'),
                        DB::raw('SUM(total_harga) as total_harga_invoice')
                    )
                    ->groupBy('no_invoice');
            })
            ->columns([
                TextColumn::make('no_invoice')
                    ->label('No. Invoice')
                    ->searchable(),

                TextColumn::make('supplier.nama_supplier')
                    ->label('Nama Supplier')
                    ->searchable(),

                TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang')
                    ->alignCenter(),

                TextColumn::make('total_items')
                    ->label('Total Item')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('tgl_masuk')
                    ->label('Tgl Masuk')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Done' => 'success',
                        'Undone' => 'danger',
                    })
                    ->getStateUsing(function ($record) {
                        $incomplete = Gudang::where('no_invoice', $record->no_invoice)
                            ->where(function ($q) {
                                $q->whereNull('harga_beli')
                                    ->orWhere('harga_beli', 0);
                            })
                            ->exists();
                        return $incomplete ? 'Undone' : 'Done';
                    })
                    ->visible(fn() => auth()->user()->role === 'admin'),
            ])
            ->filters([
                Tables\Filters\Filter::make('rentang_tanggal')
                    ->form([
                        DatePicker::make('dari_tanggal')
                            ->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')
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
            ->headerActions([
                ExportAction::make()
                    ->label('Export Excel')
                    ->exports([
                        ExcelExport::make('gudangs_masuk')
                            ->withColumns([
                                Column::make('no_invoice')->heading('No. Invoice'),
                                Column::make('supplier.nama_supplier')->heading('Nama Supplier'),
                                // Since it's grouped, exporting individual item details might be tricky.
                                // For now, we export the same aggregated columns or raw data (if fromTable is false).
                                // But if 'fromTable()' is used coupled with groupBy, the export might only show 1 row per invoice.
                                Column::make('cabang.nama_cabang')->heading('Cabang'),
                                Column::make('total_items')->heading('QTY Item'), // Custom aggregate
                                Column::make('total_harga_invoice')->heading('Total Harga'), // Custom aggregate
                                Column::make('tgl_masuk')->heading('Tgl Masuk'),
                                Column::make('created_at')->heading('Tanggal Input'),
                            ])
                            ->withFilename('gudangs_masuk-' . now()->format('Y-m-d'))
                            ->fromTable()
                            ->withChunkSize(500),
                    ]),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('print_purchase_note')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->action(function ($record, $livewire) {
                        // Fetch all items for this invoice
                        $items = Gudang::where('no_invoice', $record->no_invoice)->get();

                        // Prepare data for the print view
                        $purchaseNoteData = [
                            'no_invoice' => $record->no_invoice,
                            'tanggal_transaksi' => $record->created_at->format('d/m/Y H:i'),
                            'tanggal_masuk' => \Carbon\Carbon::parse($record->tgl_masuk)->format('d/m/Y'),
                            'supplier' => $record->supplier->nama_supplier ?? '-',
                            'cabang' => $record->cabang->nama_cabang ?? '-',
                            'user_name' => $record->user->name ?? '-',
                            'show_prices' => auth()->user()->role === 'admin',
                            'items' => $items->map(function ($item) {
                            return [
                                'nama_produk' => $item->product->nama_produk ?? '-',
                                'satuan' => $item->unitSatuan->nama_satuan ?? 'pcs',
                                'harga_beli' => 'Rp ' . number_format($item->harga_beli, 0, ',', '.'),
                                'qty' => $item->qty,
                                'total_harga' => 'Rp ' . number_format($item->total_harga, 0, ',', '.'),
                                'total_harga_raw' => $item->total_harga,
                            ];
                        })->toArray(),
                        ];

                        // Dispatch event to browser
                        $livewire->dispatch('print-purchase-note', $purchaseNoteData);
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (Gudang $record) {
                        // Delete all items for this invoice
                        Gudang::where('no_invoice', $record->no_invoice)->delete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
