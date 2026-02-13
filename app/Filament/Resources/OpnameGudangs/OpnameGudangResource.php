<?php

namespace App\Filament\Resources\OpnameGudangs;

use App\Filament\Resources\OpnameGudangs\Pages\CreateOpnameGudang;
use App\Filament\Resources\OpnameGudangs\Pages\EditOpnameGudang;
use App\Filament\Resources\OpnameGudangs\Pages\ListOpnameGudangs;
use App\Filament\Resources\OpnameGudangs\Schemas\OpnameGudangForm;
use App\Filament\Resources\OpnameGudangs\Tables\OpnameGudangsTable;
use App\Models\OpnameGudang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OpnameGudangResource extends Resource
{
    protected static ?string $model = OpnameGudang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'OpnameGudang';

    protected static ?string $navigationLabel = 'Opname Stok Gudang';

    protected static string|UnitEnum|null $navigationGroup = 'Gudang Manajemen';

    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin', 'gudang']);
    }

    public static function canEdit($record): bool
    {
        // Hanya admin yang bisa edit
        return auth()->user()->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return OpnameGudangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpnameGudangsTable::configure($table);
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
            'index' => ListOpnameGudangs::route('/'),
            'create' => CreateOpnameGudang::route('/create'),
            'edit' => EditOpnameGudang::route('/{record}/edit'),
        ];
    }
}
