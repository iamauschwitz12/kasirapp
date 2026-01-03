<?php

namespace App\Filament\Resources\GudangKeluars;

use App\Filament\Resources\GudangKeluars\Pages\CreateGudangKeluar;
use App\Filament\Resources\GudangKeluars\Pages\EditGudangKeluar;
use App\Filament\Resources\GudangKeluars\Pages\ListGudangKeluars;
use App\Filament\Resources\GudangKeluars\Schemas\GudangKeluarForm;
use App\Filament\Resources\GudangKeluars\Tables\GudangKeluarsTable;
use App\Models\GudangKeluar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GudangKeluarResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin', 'gudang']);
    }
    
    protected static ?string $model = GudangKeluar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBarsArrowUp;

    protected static ?string $recordTitleAttribute = 'GudangKeluar';

    protected static ?string $navigationLabel = 'Gudang Keluar';
    protected static ?string $pluralLabel = 'Gudang Keluar';
    protected static ?string $modelLabel = 'Barang Keluar';

    protected static string | UnitEnum | null $navigationGroup = 'Gudang Manajemen';

    public static function form(Schema $schema): Schema
    {
        return GudangKeluarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GudangKeluarsTable::configure($table);
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
            'index' => ListGudangKeluars::route('/'),
            'create' => CreateGudangKeluar::route('/create'),
            'edit' => EditGudangKeluar::route('/{record}/edit'),
        ];
    }
}
