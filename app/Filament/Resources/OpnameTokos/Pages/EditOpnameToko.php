<?php

namespace App\Filament\Resources\OpnameTokos\Pages;

use App\Filament\Resources\OpnameTokos\OpnameTokoResource;
use App\Filament\Resources\OpnameTokos\Schemas\OpnameTokoForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\OpnameToko;

class EditOpnameToko extends EditRecord
{
    protected static string $resource = OpnameTokoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (OpnameToko $record) {
                    // Delete all items with the same toko and tanggal_opname
                    OpnameToko::where('toko_id', $record->toko_id)
                        ->where('tanggal_opname', $record->tanggal_opname)
                        ->where('pic_opname', $record->pic_opname)
                        ->delete();

                    return redirect()->to(OpnameTokoResource::getUrl('index'));
                }),
        ];
    }

    public function mount($record): void
    {
        // Custom mount to load all related opname items
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();

        // Get grouping identifiers
        $tokoId = $this->record->toko_id;
        $tanggalOpname = $this->record->tanggal_opname;
        $picOpname = $this->record->pic_opname;

        // Fetch ALL records with same grouping
        $existingItems = OpnameToko::where('toko_id', $tokoId)
            ->where('tanggal_opname', $tanggalOpname)
            ->where('pic_opname', $picOpname)
            ->get();

        // Take header data from the first item
        $data = $existingItems->first()->toArray();

        // Prepare the 'products' repeater data
        $products = $existingItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'nama_barang' => $item->nama_barang,
                'satuan_besar' => $item->satuan_besar,
                'stok_fisik' => $item->stok_fisik,
                'stok_pcs' => $item->stok_pcs,
                'stok_sistem' => $item->stok_sistem,
                'isi_konversi' => $item->isi_konversi,
                'total_fisik_pcs' => $item->total_fisik_pcs,
                'status_opname' => $item->status_opname,
                'keterangan' => $item->keterangan,
                // Format stock display
                'stok_sistem_display' => $item->stok_sistem > 0
                    ? self::formatStockDisplay($item->stok_sistem, $item->isi_konversi, $item->satuan_besar)
                    : '-',
            ];
        })->toArray();

        $data['products'] = $products;
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Umum')
                    ->schema(OpnameTokoForm::getHeaderFields())
                    ->columns(2),

                Section::make('Detail Produk')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(OpnameTokoForm::getProductFields())
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['nama_barang'] ?? 'Produk Baru')
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $productsData = $data['products'] ?? [];
        $headerData = collect($data)->except('products')->toArray();

        DB::transaction(function () use ($record, $headerData, $productsData) {
            // Get existing records with same grouping
            $existingRecords = OpnameToko::where('toko_id', $record->toko_id)
                ->where('tanggal_opname', $record->tanggal_opname)
                ->where('pic_opname', $record->pic_opname)
                ->get();

            $existingIds = $existingRecords->pluck('id')->toArray();
            $submittedIds = array_filter(array_column($productsData, 'id'));

            // Delete removed items
            $idsToDelete = array_diff($existingIds, $submittedIds);
            if (!empty($idsToDelete)) {
                OpnameToko::destroy($idsToDelete);
            }

            // Upsert items
            foreach ($productsData as $itemData) {
                $mergedData = array_merge($headerData, $itemData);

                if (isset($itemData['id']) && in_array($itemData['id'], $existingIds)) {
                    // Update existing
                    $existingItem = OpnameToko::find($itemData['id']);
                    $existingItem->update($mergedData);
                } else {
                    // Create new
                    OpnameToko::create($mergedData);
                }
            }
        });

        return $record->refresh();
    }

    /**
     * Format stock display helper
     */
    private static function formatStockDisplay(int $stok, int $konversi, string $satuanBesar): string
    {
        $konversi = $konversi ?: 1;
        $satuanBesar = $satuanBesar ?: 'Unit';
        $jumlahBesar = floor($stok / $konversi);
        $pcs = $stok % $konversi;

        if ($jumlahBesar > 0 && $pcs > 0) {
            return "{$jumlahBesar} {$satuanBesar} + {$pcs} Pcs";
        } elseif ($jumlahBesar > 0) {
            return "{$jumlahBesar} {$satuanBesar}";
        } else {
            return "{$pcs} Pcs";
        }
    }
}
