<?php

namespace App\Filament\Resources\OpnameTokos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use App\Models\OpnameToko;
use Illuminate\Support\Facades\DB;

class OpnameTokosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                if ($user && !$user->isAdmin()) {
                    $query->where('toko_id', $user->toko_id);
                }

                // Group by opname session (toko, tanggal, pic)
                return $query
                    ->select(
                        'toko_id',
                        'tanggal_opname',
                        'pic_opname',
                        DB::raw('MAX(id) as id'), // Required for actions
                        DB::raw('COUNT(*) as total_items'),
                        DB::raw('SUM(CASE WHEN status_opname = "Pas" THEN 1 ELSE 0 END) as jumlah_pas'),
                        // Calculate total difference in pcs for "lebih" items
                        DB::raw('SUM(CASE WHEN status_opname = "Lebih" THEN (total_fisik_pcs - stok_sistem) ELSE 0 END) as total_lebih_pcs'),
                        // Calculate total difference in pcs for "selisih" items (as positive number)
                        DB::raw('SUM(CASE WHEN status_opname = "Selisih" THEN (stok_sistem - total_fisik_pcs) ELSE 0 END) as total_selisih_pcs'),
                        // Get conversion info for single-item rows
                        DB::raw('MAX(isi_konversi) as max_konversi'),
                        DB::raw('MAX(satuan_besar) as max_satuan'),
                        DB::raw('MAX(created_at) as created_at')
                    )
                    ->groupBy('toko_id', 'tanggal_opname', 'pic_opname');
            })
            ->columns([
                TextColumn::make('tanggal_opname')
                    ->label('Tanggal Opname')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('toko.nama_toko')
                    ->label('Toko')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('pic_opname')
                    ->label('PIC Opname')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('total_items')
                    ->label('Jml Produk')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('jumlah_pas')
                    ->label('Pas')
                    ->alignCenter()
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->formatStateUsing(fn($state) => $state > 0 ? 'Stok Pas' : ''),

                TextColumn::make('total_lebih_pcs')
                    ->label('Lebih')
                    ->alignCenter()
                    ->color('warning')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->formatStateUsing(function ($state, OpnameToko $record) {
                        if (!$state || $state <= 0)
                            return '';

                        // Only format with large unit if it's a single item row or same product
                        if ($record->total_items == 1 && $record->max_konversi > 1) {
                            $besar = floor($state / $record->max_konversi);
                            $pcs = $state % $record->max_konversi;
                            $satuan = $record->max_satuan ?: 'Unit';

                            if ($besar > 0 && $pcs > 0) {
                                return "{$besar} {$satuan} + {$pcs} Pcs";
                            } elseif ($besar > 0) {
                                return "{$besar} {$satuan}";
                            }
                        }

                        return number_format((float) $state, 0, ',', '.') . ' Pcs';
                    }),

                TextColumn::make('total_selisih_pcs')
                    ->label('Kurang')
                    ->alignCenter()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->formatStateUsing(function ($state, OpnameToko $record) {
                        if (!$state || $state <= 0)
                            return '';

                        // Only format with large unit if it's a single item row or same product
                        if ($record->total_items == 1 && $record->max_konversi > 1) {
                            $besar = floor($state / $record->max_konversi);
                            $pcs = $state % $record->max_konversi;
                            $satuan = $record->max_satuan ?: 'Unit';

                            if ($besar > 0 && $pcs > 0) {
                                return "{$besar} {$satuan} + {$pcs} Pcs";
                            } elseif ($besar > 0) {
                                return "{$besar} {$satuan}";
                            }
                        }

                        return number_format((float) $state, 0, ',', '.') . ' Pcs';
                    }),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('pic_opname')
                    ->label('PIC Opname')
                    ->options(function () {
                        $user = auth()->user();
                        $query = \App\Models\OpnameToko::distinct();

                        if ($user && !$user->isAdmin()) {
                            $query->where('toko_id', $user->toko_id);
                        }

                        return $query->pluck('pic_opname', 'pic_opname')->toArray();
                    })
                    ->searchable(),

                SelectFilter::make('status_opname')
                    ->label('Filter Status')
                    ->options([
                        'Pas' => 'Pas',
                        'Lebih' => 'Lebih',
                        'Selisih' => 'Selisih',
                    ]),

                Filter::make('tanggal_opname')
                    ->form([
                        DatePicker::make('dari_tanggal')->label('Dari Tanggal'),
                        DatePicker::make('sampai_tanggal')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['dari_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_opname', '>=', $date),
                            )
                            ->when(
                                $data['sampai_tanggal'],
                                fn(Builder $query, $date): Builder => $query->whereDate('tanggal_opname', '<=', $date),
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
            ->recordUrl(fn() => null) // Disable row click navigation
            ->recordActions([
                EditAction::make()
                    ->visible(fn() => auth()->user()->isAdmin())
                    ->authorize(fn() => auth()->user()->isAdmin()),
                Action::make('cetak_opname')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->action(function (OpnameToko $record, \Livewire\Component $livewire) {
                        // Get all items in this opname session
                        $items = OpnameToko::where('toko_id', $record->toko_id)
                            ->where('tanggal_opname', $record->tanggal_opname)
                            ->where('pic_opname', $record->pic_opname)
                            ->get();

                        $formattedItems = $items->map(function ($item) {
                            // Calculate display for system stock
                            $sysBesar = floor($item->stok_sistem / $item->isi_konversi);
                            $sysPcs = $item->stok_sistem % $item->isi_konversi;
                            $sysDisplay = $sysBesar > 0
                                ? "{$sysBesar} {$item->satuan_besar}" . ($sysPcs > 0 ? " + {$sysPcs} Pcs" : "")
                                : "{$sysPcs} Pcs";

                            // Calculate display for physical stock
                            $physDisplay = $item->stok_fisik > 0
                                ? "{$item->stok_fisik} {$item->satuan_besar}" . ($item->stok_pcs > 0 ? " + {$item->stok_pcs} Pcs" : "")
                                : "{$item->stok_pcs} Pcs";

                            // Calculate difference display directly in units if possible
                            $diff = $item->total_fisik_pcs - $item->stok_sistem;
                            $diffAbs = abs($diff);
                            $diffBesar = floor($diffAbs / $item->isi_konversi);
                            $diffPcs = $diffAbs % $item->isi_konversi;

                            $diffDisplay = $diffBesar > 0
                                ? "{$diffBesar} {$item->satuan_besar}" . ($diffPcs > 0 ? " + {$diffPcs} Pcs" : "")
                                : "{$diffPcs} Pcs";

                            // Add +/- sign
                            if ($diff > 0)
                                $diffDisplay = "+ " . $diffDisplay;
                            elseif ($diff < 0)
                                $diffDisplay = "- " . $diffDisplay;
                            else
                                $diffDisplay = "Pas";

                            return [
                                'nama_barang' => $item->nama_barang,
                                'stok_sistem_display' => $sysDisplay,
                                'stok_fisik_display' => $physDisplay,
                                'selisih_pcs' => $diff,
                                'selisih_display' => $diffDisplay,
                                'status_opname' => $item->status_opname,
                                'keterangan' => $item->keterangan,
                            ];
                        });

                        $data = [
                            'tanggal_opname' => $record->tanggal_opname->format('d M Y'),
                            'nama_toko' => $record->toko->nama_toko ?? '-',
                            'pic_opname' => $record->pic_opname,
                            'items' => $formattedItems,
                        ];

                        $livewire->dispatch('print-opname-note', $data);
                    }),
                DeleteAction::make()
                    ->visible(fn() => auth()->user()->isAdmin())
                    ->action(function (OpnameToko $record) {
                        // Delete all items from this opname session
                        OpnameToko::where('toko_id', $record->toko_id)
                            ->where('tanggal_opname', $record->tanggal_opname)
                            ->where('pic_opname', $record->pic_opname)
                            ->delete();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
