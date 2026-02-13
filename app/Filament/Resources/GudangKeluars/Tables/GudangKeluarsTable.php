<?php

namespace App\Filament\Resources\GudangKeluars\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Columns\Column;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\GudangKeluar;
use App\Models\Gudang;

class GudangKeluarsTable
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

                return $query
                    ->select(
                        'no_referensi',
                        DB::raw('MAX(id) as id'),
                        DB::raw('MAX(toko_id) as toko_id'),
                        DB::raw('MAX(cabang_id) as cabang_id'),
                        DB::raw('MAX(tgl_keluar) as tgl_keluar'),
                        DB::raw('MAX(created_at) as created_at'),
                        DB::raw('MAX(user_id) as user_id'),
                        DB::raw('COUNT(*) as total_items'),
                        DB::raw('SUM(qty) as total_qty')
                    )
                    ->groupBy('no_referensi');
            })
            ->columns([
                TextColumn::make('no_referensi')->label('No. Ref')->searchable(),

                TextColumn::make('toko.nama_toko')
                    ->label('Toko Tujuan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('cabang.nama_cabang')
                    ->label('Cabang Tujuan')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('total_items')
                    ->label('Total Item')
                    ->alignCenter(),

                TextColumn::make('total_qty')
                    ->label('Total QTY')
                    ->alignCenter(),

                TextColumn::make('tgl_keluar')->date(),
            ])
            ->headerActions([
                ExportAction::make()
                    ->label('Export Excel')
                    ->exports([
                        ExcelExport::make('gudangs_keluar')
                            ->withColumns([
                                Column::make('no_referensi')->heading('No. Invoice'),
                                Column::make('toko.nama_toko')->heading('Toko Tujuan'),
                                Column::make('cabang.nama_cabang')->heading('Cabang Tujuan'),
                                Column::make('total_items')->heading('Total Items'),
                                Column::make('total_qty')->heading('Total QTY'),
                                Column::make('tgl_keluar')->heading('Tgl Keluar'),
                                Column::make('created_at')->heading('Tanggal Input'),
                            ])
                            ->withFilename('gudangs_keluar-' . now()->format('Y-m-d'))
                            ->fromTable()
                            ->withChunkSize(500),
                    ]),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\Action::make('print_delivery_note')
                    ->label('Cetak Nota')
                    ->icon('heroicon-o-printer')
                    ->action(function ($record, $livewire) {
                        // Fetch all items for this reference
                        $items = GudangKeluar::where('no_referensi', $record->no_referensi)->get();

                        // Prepare data for the print view
                        $deliveryNoteData = [
                            'no_referensi' => $record->no_referensi,
                            'tanggal_transaksi' => $record->created_at->format('d/m/Y H:i'),
                            'tanggal_keluar' => \Carbon\Carbon::parse($record->tgl_keluar)->format('d/m/Y'),
                            'toko_tujuan' => $record->toko->nama_toko ?? '-',
                            'cabang_asal' => $record->cabang->nama_cabang ?? '-', // Note: In schema, cabang_id is labeled "Cabang Tujuan" or "Toko"? Wait, let's check schema.
                            // In GudangKeluarsTable columns: 'toko.nama_toko' is Toko Tujuan, 'cabang.nama_cabang' is Cabang Tujuan?
                            // In Migration: 'cabang_id' is constrained to cabangs. Table label says "Cabang Tujuan".
                            // So 'cabang_asal' might not be stored? Or is it implicit?
                            // Let's assume 'cabang_id' in record is destination branch if that's what table says.
                            // User request said: "sesuai dengan kolom pada GudangKeluarsTable.php"
                            // Columns are: Toko Tujuan, Cabang Tujuan.
                            // So I will print Cabang Tujuan.
                            'cabang_asal' => $record->cabang->nama_cabang ?? '-', // I'll label it "Cabang Tujuan" in print view if needed, or just use this data field.
                            'user_name' => $record->user->name ?? '-',
                            'items' => $items->map(function ($item) {
                            return [
                                'nama_produk' => $item->product->nama_produk ?? '-',
                                'satuan' => $item->unitSatuan->nama_satuan ?? 'pcs',
                                'qty' => $item->qty,
                            ];
                        })->toArray(),
                        ];

                        // Dispatch event to browser
                        $livewire->dispatch('print-delivery-note', $deliveryNoteData);
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->action(function (GudangKeluar $record) {
                        DB::transaction(function () use ($record) {
                            $ref = $record->no_referensi;
                            $items = GudangKeluar::where('no_referensi', $ref)->get();

                            foreach ($items as $item) {
                                // Restore Stock Logic
                                // Copying simplified logic from EditGudangKeluar
                                $latestBatch = DB::table('gudangs')
                                    ->where('product_id', $item->product_id)
                                    ->where('cabang_id', $item->cabang_id)
                                    ->orderBy('created_at', 'desc')
                                    ->first();

                                if ($latestBatch) {
                                    DB::table('gudangs')
                                        ->where('id', $latestBatch->id)
                                        ->increment('sisa_stok', $item->qty);
                                }

                                // Delete item
                                $item->delete();
                            }
                        });
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
