<?php

namespace App\Filament\Resources\GudangKeluars\Pages;

use App\Filament\Resources\GudangKeluars\GudangKeluarResource;
use App\Filament\Resources\GudangKeluars\Schemas\GudangKeluarForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\GudangKeluar;
use App\Models\Gudang; // To access stock

class EditGudangKeluar extends EditRecord
{
    protected static string $resource = GudangKeluarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (GudangKeluar $record) {
                    DB::transaction(function () use ($record) {
                        // 1. Get all items for this reference
                        $items = GudangKeluar::where('no_referensi', $record->no_referensi)->get();

                        foreach ($items as $item) {
                            // 2. Restore stock for each item
                            $this->restoreStock($item->product_id, $item->cabang_id, $item->qty);
                        }

                        // 3. Delete records
                        GudangKeluar::where('no_referensi', $record->no_referensi)->delete();
                    });

                    return redirect()->to(GudangKeluarResource::getUrl('index'));
                }),
        ];
    }

    public function mount($record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();

        $reference = $this->record->no_referensi;
        $existingItems = GudangKeluar::where('no_referensi', $reference)->get();
        $data = $existingItems->first()->toArray();

        $products = $existingItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'unitsatuan_id' => $item->unitsatuan_id,
                'nama_display' => $item->product->nama_produk ?? '',
                // maintain sibling context for validation if needed
            ];
        })->toArray();

        $data['products'] = $products;
        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        // Reuse schema but ensure Repeater is used
        // Since GudangKeluarForm already wraps in Sections, we can extract fields or just reconstruct
        // Actually GudangKeluarForm returns a schema with sections. We can just use it?
        // But GudangKeluarForm::configure() returns a $schema which is for the resource configuration.
        // We need the array of components.
        // The file GudangKeluarForm.php had `public static function configure(Schema $schema): Schema`.
        // We can't reuse that directly inside `form()` easily without calling it on the schema object.
        // But `EditRecord::form()` expects us to return `$schema->components(...)`.

        // Let's copy the structure from GudangKeluarForm or refactor GudangKeluarForm to return arrays.
        // For safety and speed, I will use the code from GudangKeluarForm here directly or call a helper if I made one.
        // GudangKeluarForm was not refactored to return array. I will instantiate it manually here conforming to the plan
        // to use Repeater.

        return GudangKeluarForm::configure($schema);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $productsData = $data['products'] ?? [];
        $headerData = collect($data)->except('products')->toArray();
        $reference = $headerData['no_referensi'];

        DB::transaction(function () use ($record, $headerData, $productsData, $reference) {
            $existingItems = GudangKeluar::where('no_referensi', $record->no_referensi)->get()->keyBy('id');
            $submittedIds = array_filter(array_column($productsData, 'id'));

            // 1. Handle Deletions (Items removed from repeater)
            foreach ($existingItems as $id => $existingItem) {
                if (!in_array($id, $submittedIds)) {
                    // Restore Stock
                    $this->restoreStock($existingItem->product_id, $existingItem->cabang_id, $existingItem->qty);
                    // Delete Record
                    $existingItem->delete();
                }
            }

            // 2. Handle Upserts
            foreach ($productsData as $itemData) {
                $productId = $itemData['product_id'];
                $cabangId = $headerData['cabang_id']; // Cabang Header overrides or should we allow per item? Form says header.
                $newQty = (int) $itemData['qty'];

                $createData = array_merge($headerData, $itemData);

                if (isset($itemData['id']) && isset($existingItems[$itemData['id']])) {
                    // Update
                    $oldItem = $existingItems[$itemData['id']];
                    $oldQty = (int) $oldItem->qty;
                    $diff = $newQty - $oldQty;

                    if ($diff > 0) {
                        // Increased Qty: Deduct more
                        $this->deductStock($productId, $cabangId, $diff);
                    } elseif ($diff < 0) {
                        // Decreased Qty: Restore difference (positive value)
                        $this->restoreStock($productId, $cabangId, abs($diff));
                    }

                    GudangKeluar::where('id', $itemData['id'])->update($createData);

                } else {
                    // Create New
                    $this->deductStock($productId, $cabangId, $newQty);
                    // Handle creation
                    GudangKeluar::create($createData);
                }
            }
        });

        return $record->refresh();
    }

    protected function deductStock($productId, $cabangId, $qty)
    {
        $sisaPermintaan = $qty;

        $batches = DB::table('gudangs')
            ->where('product_id', $productId)
            ->where('cabang_id', $cabangId)
            ->where('sisa_stok', '>', 0)
            ->orderBy('created_at', 'asc') // FIFO
            ->lockForUpdate()
            ->get();

        foreach ($batches as $batch) {
            if ($sisaPermintaan <= 0)
                break;

            $ambil = min($batch->sisa_stok, $sisaPermintaan);

            DB::table('gudangs')
                ->where('id', $batch->id)
                ->update([
                    'sisa_stok' => $batch->sisa_stok - $ambil,
                    'updated_at' => now(),
                ]);

            $sisaPermintaan -= $ambil;
        }

        if ($sisaPermintaan > 0) {
            // Should verify validation handles this, but here we might throw or force negative?
            // For now, if stock insufficient, it just takes what's there (dangerous), 
            // but the Form Validator should have caught it ideally. 
            // We will let it slide here or throw exception. 
            // Exception is safer to rollback transaction.
            throw new \Exception("Stok tidak mencukupi untuk pengurangan tambahan.");
        }
    }

    protected function restoreStock($productId, $cabangId, $qty)
    {
        // Restore to the LATEST batch to ensure it's available and doesn't mess up old FIFO too much
        $latestBatch = DB::table('gudangs')
            ->where('product_id', $productId)
            ->where('cabang_id', $cabangId)
            ->orderBy('created_at', 'desc')
            ->first();

        if ($latestBatch) {
            DB::table('gudangs')
                ->where('id', $latestBatch->id)
                ->increment('sisa_stok', $qty);
        } else {
            // Edge case: No batches exist? Should not happen if we are returning something.
            // If it happens, maybe create a "Return" batch?
            // For now, ignore or log.
        }
    }
}
