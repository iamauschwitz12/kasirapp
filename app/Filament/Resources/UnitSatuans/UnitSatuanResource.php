<?php

namespace App\Filament\Resources\UnitSatuans;

use App\Filament\Resources\UnitSatuans\Pages\CreateUnitSatuan;
use App\Filament\Resources\UnitSatuans\Pages\EditUnitSatuan;
use App\Filament\Resources\UnitSatuans\Pages\ListUnitSatuans;
use App\Filament\Resources\UnitSatuans\Schemas\UnitSatuanForm;
use App\Filament\Resources\UnitSatuans\Tables\UnitSatuansTable;
use App\Models\UnitSatuan;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class UnitSatuanResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin']);
    }
    protected static ?string $model = UnitSatuan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSwatch;

    protected static ?string $recordTitleAttribute = 'UnitSatuan';

    protected static ?int $navigationSort = 4;

    protected static string | UnitEnum | null $navigationGroup = 'Kategori Manajemen';

    public static function form(Schema $schema): Schema

    {
        return UnitSatuanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UnitSatuansTable::configure($table);
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
            'index' => ListUnitSatuans::route('/'),
            'create' => CreateUnitSatuan::route('/create'),
            'edit' => EditUnitSatuan::route('/{record}/edit'),
        ];
    }
}
