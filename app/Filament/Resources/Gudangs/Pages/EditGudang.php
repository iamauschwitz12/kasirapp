<?php

namespace App\Filament\Resources\Gudangs\Pages;

use App\Filament\Resources\Gudangs\GudangResource;
use App\Filament\Resources\Gudangs\Schemas\GudangForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Gudang;

class EditGudang extends EditRecord
{
    protected static string $resource = GudangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->action(function (Gudang $record) {
                    // Delete all items with this invoice number
                    Gudang::where('no_invoice', $record->no_invoice)->delete();

                    return redirect()->to(GudangResource::getUrl('index'));
                }),
        ];
    }

    public function mount($record): void
    {
        // Don't call parent::mount($record) directly as we need custom filling
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        // 1. Get the invoice number from the record being edited
        $invoice = $this->record->no_invoice;

        // 2. Fetch ALL records with this invoice
        $existingItems = Gudang::where('no_invoice', $invoice)->get();

        // 3. Prepare data for the form
        // Take header data from the *first* item (they should be identical for the same invoice)
        $data = $existingItems->first()->toArray();

        // Prepare the 'products' repeater data
        $products = $existingItems->map(function ($item) {
            return [
                'id' => $item->id, // Important for tracking existing items
                'product_id' => $item->product_id,
                'qty' => $item->qty,
                'unitsatuan_id' => $item->unitsatuan_id,
                'harga_beli' => $item->harga_beli,
                'total_harga' => $item->total_harga,
                'nama_display' => $item->product->nama_produk ?? '',
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
                    ->schema(GudangForm::getHeaderFields())
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Detail Barang')
                    ->schema([
                        Repeater::make('products')
                            ->label('Daftar Barang')
                            ->schema(GudangForm::getProductFields())
                            ->columns(2)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Barang')
                            ->reorderableWithButtons()
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // $record is just one of the items (the one the URL ID points to).
        // However, we are updating the entire "Invoice" concept.

        $productsData = $data['products'] ?? [];
        $headerData = collect($data)->except('products')->toArray();
        $invoiceNumber = $headerData['no_invoice'];

        // We need to handle the transaction carefully
        DB::transaction(function () use ($record, $headerData, $productsData, $invoiceNumber) {

            // 1. Update Header Info for ALL existing items of this invoice 
            // (in case user changed supplier, date, etc.)
            // Note: If the user changed the Invoice Number itself, we need to handle that carefully.
            // But usually PK or unique IDs are tricky. Let's assume we update based on the OLD ID's specific record
            // or simply update all items that *currently* match the records we loaded.

            // Allow changing invoice number:
            // The $record->no_invoice is the OLD invoice number (before update).
            // We should find objects by the OLD invoice number if the user changed it.
            // But $record is already re-hydrated? No, handleRecordUpdate receives form data.

            // Better approach:
            // We have the list of IDs from the `products` repeater. 
            // Any ID present in `products` => Update
            // Any ID missing from `products` (but existed in DB for this invoice) => Delete
            // No ID in `products` => Create

            $existingIds = Gudang::where('no_invoice', $record->no_invoice)->pluck('id')->toArray();
            $submittedIds = array_filter(array_column($productsData, 'id'));

            // -- DELETE REMOVED ITEMS --
            $idsToDelete = array_diff($existingIds, $submittedIds);
            if (!empty($idsToDelete)) {
                Gudang::destroy($idsToDelete);
            }

            // -- UPSERT ITEMS --
            foreach ($productsData as $itemData) {
                // Merge header data
                $mergedData = array_merge($headerData, $itemData);
                $mergedData['sisa_stok'] = $mergedData['qty']; // Reset/Update logic for stock? verify business logic

                // If ID exists, Update
                if (isset($itemData['id']) && in_array($itemData['id'], $existingIds)) {
                    Gudang::where('id', $itemData['id'])->update($mergedData);
                } else {
                    // Create New
                    Gudang::create($mergedData);
                }
            }
        });

        return $record->refresh();
    }
}
