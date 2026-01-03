<?php

namespace App\Filament\Resources\Gudangs;

use App\Filament\Resources\Gudangs\Pages\CreateGudang;
use App\Filament\Resources\Gudangs\Pages\EditGudang;
use App\Filament\Resources\Gudangs\Pages\ListGudangs;
use App\Filament\Resources\Gudangs\Schemas\GudangForm;
use App\Filament\Resources\Gudangs\Tables\GudangsTable;
use App\Models\Gudang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GudangResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin', 'gudang']);
    }
    protected static ?string $model = Gudang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBarsArrowDown;

    protected static ?string $recordTitleAttribute = 'Gudang';

    protected static ?string $navigationLabel = 'Gudang Masuk';
    protected static ?string $pluralLabel = 'Gudang Masuk';
    protected static ?string $modelLabel = 'Barang Masuk';
    protected static string | UnitEnum | null $navigationGroup = 'Gudang Manajemen';
    

    public static function form(Schema $schema): Schema
    {
        return GudangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GudangsTable::configure($table);
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
            'index' => ListGudangs::route('/'),
            'create' => CreateGudang::route('/create'),
            'edit' => EditGudang::route('/{record}/edit'),
        ];
    }
}
