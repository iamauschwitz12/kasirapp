<?php

namespace App\Filament\Resources\PenjualanStoks;

use App\Filament\Resources\PenjualanStoks\Pages\CreatePenjualanStok;
use App\Filament\Resources\PenjualanStoks\Pages\EditPenjualanStok;
use App\Filament\Resources\PenjualanStoks\Pages\ListPenjualanStoks;
use App\Filament\Resources\PenjualanStoks\Schemas\PenjualanStokForm;
use App\Filament\Resources\PenjualanStoks\Tables\PenjualanStoksTable;
use App\Models\PenjualanStok;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Livewire\Attributes\Url;

class PenjualanStokResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin', 'kasir']);
    }

    protected static ?string $model = PenjualanStok::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $recordTitleAttribute = 'PenjualanStok';

    protected static ?string $navigationLabel = 'Barang Masuk (Toko)';

    public static function form(Schema $schema): Schema
    {
        return PenjualanStokForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenjualanStoksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenjualanStoks::route('/'),
            'create' => CreatePenjualanStok::route('/create'),
            'edit' => EditPenjualanStok::route('/{record}/edit'),
        ];
    }
}
