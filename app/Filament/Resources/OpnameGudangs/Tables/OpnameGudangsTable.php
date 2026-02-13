<?php

namespace App\Filament\Resources\OpnameGudangs\Tables;

use App\Models\OpnameGudang;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class OpnameGudangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();

                // Filter by cabang_id if user is not admin
                if ($user && $user->role !== 'admin' && $user->cabang_id) {
                    $query->where('cabang_id', $user->cabang_id);
                }

                // Group by opname session (cabang, tanggal, pic)
                return $query
                    ->select(
                        'cabang_id',
                        DB::raw('DATE(tanggal_opname) as tanggal_opname'),
                        'pic_opname',
                        DB::raw('MAX(id) as id'), // Required for actions
                        DB::raw('COUNT(*) as total_items'),
                        DB::raw('SUM(CASE WHEN status_opname = "Pas" THEN 1 ELSE 0 END) as jumlah_pas'),
                        // Calculate total difference for "lebih" items
                        DB::raw('SUM(CASE WHEN status_opname = "Lebih" THEN (stok_fisik - stok_sistem) ELSE 0 END) as total_lebih'),
                        // Calculate total difference for "selisih" items (as positive number)
                        DB::raw('SUM(CASE WHEN status_opname = "Selisih" THEN (stok_sistem - stok_fisik) ELSE 0 END) as total_selisih'),
                        DB::raw('MAX(created_at) as created_at')
                    )
                    ->groupBy(DB::raw('DATE(tanggal_opname)'), 'pic_opname', 'cabang_id');
            })
            ->columns([
                TextColumn::make('tanggal_opname')
                    ->label('Tanggal Opname')
                    ->date('d M Y')
                    ->sortable(),

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
                    ->formatStateUsing(fn($state) => $state > 0 ? $state : ''),

                TextColumn::make('total_lebih')
                    ->label('Lebih')
                    ->alignCenter()
                    ->color('warning')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->formatStateUsing(function ($state) {
                        if (!$state || $state <= 0)
                            return '';
                        return number_format((float) $state, 0, ',', '.');
                    }),

                TextColumn::make('total_selisih')
                    ->label('Kurang')
                    ->alignCenter()
                    ->color('danger')
                    ->icon('heroicon-o-arrow-down-circle')
                    ->formatStateUsing(function ($state) {
                        if (!$state || $state <= 0)
                            return '';
                        return number_format((float) $state, 0, ',', '.');
                    }),
            ])
            ->filters([
                SelectFilter::make('pic_opname')
                    ->label('PIC Opname')
                    ->options(function () {
                        $user = auth()->user();
                        $query = \App\Models\OpnameGudang::distinct();

                        // Filter by cabang_id if user is not admin
                        if ($user && $user->role !== 'admin' && $user->cabang_id) {
                            $query->where('cabang_id', $user->cabang_id);
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
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['value'])) {
                            return $query;
                        }

                        $status = $data['value'];

                        return $query->havingRaw(
                            "SUM(CASE WHEN status_opname = ? THEN 1 ELSE 0 END) > 0",
                            [$status]
                        );
                    }),

                Filter::make('tanggal_opname')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')->label('Dari Tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->havingRaw('MAX(tanggal_opname) >= ?', [$date]),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->havingRaw('MAX(tanggal_opname) <= ?', [$date]),
                            );
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
                    ->action(function (OpnameGudang $record, \Livewire\Component $livewire) {
                        // Get all items in this opname session
                        $items = OpnameGudang::where('cabang_id', $record->cabang_id)
                            ->where('tanggal_opname', $record->tanggal_opname)
                            ->where('pic_opname', $record->pic_opname)
                            ->get();

                        $formattedItems = $items->map(function ($item) {
                            // Calculate difference
                            $diff = $item->stok_fisik - $item->stok_sistem;
                            $satuan = $item->satuan_besar ?: 'Pcs';

                            // Format difference display with unit
                            if ($diff > 0)
                                $diffDisplay = "+ " . number_format(abs($diff), 0, ',', '.') . " {$satuan}";
                            elseif ($diff < 0)
                                $diffDisplay = "- " . number_format(abs($diff), 0, ',', '.') . " {$satuan}";
                            else
                                $diffDisplay = "Pas";

                            return [
                                'nama_barang' => $item->nama_barang,
                                'stok_sistem' => number_format($item->stok_sistem, 0, ',', '.'),
                                'stok_fisik' => number_format($item->stok_fisik, 0, ',', '.'),
                                'selisih' => $diff,
                                'selisih_display' => $diffDisplay,
                                'status_opname' => $item->status_opname,
                                'keterangan' => $item->keterangan,
                            ];
                        });

                        // Get location name
                        $lokasi = 'Gudang';
                        if ($record->cabang_id) {
                            $cabang = \App\Models\Cabang::find($record->cabang_id);
                            if ($cabang) {
                                $lokasi = "Gudang " . $cabang->nama_cabang;
                            }
                        }

                        $data = [
                            'tanggal_opname' => \Carbon\Carbon::parse($record->tanggal_opname)->format('d M Y'),
                            'lokasi' => $lokasi,
                            'pic_opname' => $record->pic_opname,
                            'items' => $formattedItems,
                        ];

                        $livewire->dispatch('print-opname-gudang-note', $data);
                    }),
                DeleteAction::make()
                    ->visible(fn() => auth()->user()->isAdmin())
                    ->action(function (OpnameGudang $record) {
                        // Delete all items from this opname session
                        OpnameGudang::where('cabang_id', $record->cabang_id)
                            ->where('tanggal_opname', $record->tanggal_opname)
                            ->where('pic_opname', $record->pic_opname)
                            ->delete();
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->isAdmin()),
                ]),
            ])
            ->defaultSort('tanggal_opname', 'desc');
    }
}
