<?php

namespace App\Filament\Resources\Cabangs;

use App\Filament\Resources\Cabangs\Pages\CreateCabang;
use App\Filament\Resources\Cabangs\Pages\EditCabang;
use App\Filament\Resources\Cabangs\Pages\ListCabangs;
use App\Filament\Resources\Cabangs\Schemas\CabangForm;
use App\Filament\Resources\Cabangs\Tables\CabangsTable;
use App\Models\Cabang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CabangResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin']);
    }
    protected static ?string $model = Cabang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'Cabang';

    protected static ?string $navigationLabel = 'Cabang Gudang';

    protected static string | UnitEnum | null $navigationGroup = 'Kategori Manajemen';

    public static function form(Schema $schema): Schema
    {
        return CabangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CabangsTable::configure($table);
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
            'index' => ListCabangs::route('/'),
            'create' => CreateCabang::route('/create'),
            'edit' => EditCabang::route('/{record}/edit'),
        ];
    }
}
