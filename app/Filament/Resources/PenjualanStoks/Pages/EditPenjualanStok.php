<?php

namespace App\Filament\Resources\PenjualanStoks\Pages;

use App\Filament\Resources\PenjualanStoks\PenjualanStokResource;
use App\Filament\Resources\PenjualanStoks\Schemas\PenjualanStokForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\PenjualanStok;

class EditPenjualanStok extends EditRecord
{
    protected static string $resource = PenjualanStokResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (PenjualanStok $record) {
                    // Delete all items with this invoice number
                    PenjualanStok::where('no_inv', $record->no_inv)->delete();

                    return redirect()->to(PenjualanStokResource::getUrl('index'));
                }),
        ];
    }

    public function mount($record): void
    {
        // Don't call parent::mount($record) directly as we need custom filling
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        // 1. Get the invoice number from the record being edited
        $invoice = $this->record->no_inv;

        // 2. Fetch ALL records with this invoice
        $existingItems = PenjualanStok::where('no_inv', $invoice)->get();

        // 3. Prepare data for the form
        // Take header data from the *first* item
        $data = $existingItems->first()->toArray();

        // Prepare the 'products' repeater data
        $products = $existingItems->map(function ($item) {
            return [
                'id' => $item->id, // Important for tracking existing items
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'input_satuan_besar' => 0, // Will be calculated by form logic/hydration if needed, but safe to default
                'input_satuan_kecil' => 0,
                'isi_konversi' => $item->product->isi_konversi ?? 1,
                'nama_barang' => $item->product->nama_produk ?? '',
                // Add other product-specific fields if needed
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
                    ->schema(PenjualanStokForm::getHeaderFields())
                    ->columns(2),

                Section::make('Detail Barang')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(PenjualanStokForm::getProductFields())
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                            ->reorderableWithButtons()
                            ->deletable(fn() => auth()->user()->isAdmin())
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $productsData = $data['products'] ?? [];
        $headerData = collect($data)->except('products')->toArray();
        // $oldInvoice = $record->no_inv; // If needed to track invoice number change

        DB::transaction(function () use ($record, $headerData, $productsData) {
            $existingIds = PenjualanStok::where('no_inv', $record->no_inv)->pluck('id')->toArray();
            $submittedIds = array_filter(array_column($productsData, 'id'));

            // -- DELETE REMOVED ITEMS --
            $idsToDelete = array_diff($existingIds, $submittedIds);
            if (!empty($idsToDelete)) {
                // Must adjust stock before deleting?
                // Logic: if we delete a record, we should DECREMENT the stock from Product?
                // The Create page INCREMENTED stock. So Delete should DECREMENT.
                foreach (PenjualanStok::whereIn('id', $idsToDelete)->get() as $itemToDelete) {
                    $product = \App\Models\Product::find($itemToDelete->product_id);
                    if ($product) {
                        $product->decrement('stok', $itemToDelete->qty);
                    }
                }
                PenjualanStok::destroy($idsToDelete);
            }

            // -- UPSERT ITEMS --
            foreach ($productsData as $itemData) {
                // Merge header data
                $mergedData = array_merge($headerData, $itemData);

                // If ID exists, Update
                if (isset($itemData['id']) && in_array($itemData['id'], $existingIds)) {
                    $existingItem = PenjualanStok::find($itemData['id']);

                    // Capture OLD state
                    $oldProductId = $existingItem->product_id;
                    $oldQty = $existingItem->qty;

                    // Update Record
                    $existingItem->update($mergedData);

                    // Handle Stock Adjustment
                    if ($oldProductId == $mergedData['product_id']) {
                        // Product same, just diff qty
                        $qtyDiff = $mergedData['qty'] - $oldQty;
                        if ($qtyDiff != 0) {
                            $product = \App\Models\Product::find($mergedData['product_id']);
                            if ($product)
                                $product->increment('stok', $qtyDiff);
                        }
                    } else {
                        // Product changed
                        // 1. Revert OLD product stock
                        $oldProduct = \App\Models\Product::find($oldProductId);
                        if ($oldProduct)
                            $oldProduct->decrement('stok', $oldQty);

                        // 2. Add NEW product stock
                        $newProduct = \App\Models\Product::find($mergedData['product_id']);
                        if ($newProduct)
                            $newProduct->increment('stok', $mergedData['qty']);
                    }
                } else {
                    // Create New
                    $newItem = PenjualanStok::create($mergedData);
                    // Increment Stock
                    $product = \App\Models\Product::find($mergedData['product_id']);
                    if ($product) {
                        $product->increment('stok', $mergedData['qty']);
                    }
                }
            }
        });

        return $record->refresh();
    }
}
