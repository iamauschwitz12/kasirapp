<?php

namespace App\Filament\Resources\Pengirims;

use App\Filament\Resources\Pengirims\Pages\CreatePengirim;
use App\Filament\Resources\Pengirims\Pages\EditPengirim;
use App\Filament\Resources\Pengirims\Pages\ListPengirims;
use App\Filament\Resources\Pengirims\Schemas\PengirimForm;
use App\Filament\Resources\Pengirims\Tables\PengirimsTable;
use App\Models\Pengirim;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PengirimResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin']);
    }
    protected static ?string $model = Pengirim::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowDown;

    protected static ?string $recordTitleAttribute = 'Pengirim';

    protected static string | UnitEnum | null $navigationGroup = 'Kategori Manajemen';

    public static function form(Schema $schema): Schema
    {
        return PengirimForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PengirimsTable::configure($table);
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
            'index' => ListPengirims::route('/'),
            'create' => CreatePengirim::route('/create'),
            'edit' => EditPengirim::route('/{record}/edit'),
        ];
    }
}
