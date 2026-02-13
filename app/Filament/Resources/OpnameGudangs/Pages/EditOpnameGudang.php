<?php

namespace App\Filament\Resources\OpnameGudangs\Pages;

use App\Filament\Resources\OpnameGudangs\OpnameGudangResource;
use App\Filament\Resources\OpnameGudangs\Schemas\OpnameGudangForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\OpnameGudang;

class EditOpnameGudang extends EditRecord
{
    protected static string $resource = OpnameGudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (OpnameGudang $record) {
                    // Delete all items with the same cabang_id, tanggal_opname and pic_opname
                    OpnameGudang::where('cabang_id', $record->cabang_id)
                        ->where('tanggal_opname', $record->tanggal_opname)
                        ->where('pic_opname', $record->pic_opname)
                        ->delete();

                    return redirect()->to(OpnameGudangResource::getUrl('index'));
                }),
        ];
    }

    public function mount($record): void
    {
        // Custom mount to load all related opname items
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();

        // Get grouping identifiers
        $cabangId = $this->record->cabang_id;
        $tanggalOpname = $this->record->tanggal_opname;
        $picOpname = $this->record->pic_opname;

        // Fetch ALL records with same grouping
        $existingItems = OpnameGudang::where('cabang_id', $cabangId)
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
                'stok_fisik' => $item->stok_fisik,
                'stok_sistem' => $item->stok_sistem,
                'status_opname' => $item->status_opname,
                'keterangan' => $item->keterangan,
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
                    ->schema(OpnameGudangForm::getHeaderFields())
                    ->columns(2),

                Section::make('Detail Produk')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(OpnameGudangForm::getProductFields())
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
            $existingRecords = OpnameGudang::where('cabang_id', $record->cabang_id)
                ->where('tanggal_opname', $record->tanggal_opname)
                ->where('pic_opname', $record->pic_opname)
                ->get();

            $existingIds = $existingRecords->pluck('id')->toArray();
            $submittedIds = array_filter(array_column($productsData, 'id'));

            // Delete removed items
            $idsToDelete = array_diff($existingIds, $submittedIds);
            if (!empty($idsToDelete)) {
                OpnameGudang::destroy($idsToDelete);
            }

            // Upsert items
            foreach ($productsData as $itemData) {
                $mergedData = array_merge($headerData, $itemData);

                if (isset($itemData['id']) && in_array($itemData['id'], $existingIds)) {
                    // Update existing
                    $existingItem = OpnameGudang::find($itemData['id']);
                    $existingItem->update($mergedData);
                } else {
                    // Create new
                    OpnameGudang::create($mergedData);
                }
            }
        });

        return $record->refresh();
    }
}
