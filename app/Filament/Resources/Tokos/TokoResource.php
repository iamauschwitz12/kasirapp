<?php

namespace App\Filament\Resources\Tokos;

use App\Filament\Resources\Tokos\Pages\CreateToko;
use App\Filament\Resources\Tokos\Pages\EditToko;
use App\Filament\Resources\Tokos\Pages\ListTokos;
use App\Filament\Resources\Tokos\Schemas\TokoForm;
use App\Filament\Resources\Tokos\Tables\TokosTable;
use App\Models\Toko;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TokoResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin']);
    }
    protected static ?string $model = Toko::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $recordTitleAttribute = 'Toko';

    protected static string | UnitEnum | null $navigationGroup = 'Kategori Manajemen';

    public static function form(Schema $schema): Schema
    {
        return TokoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TokosTable::configure($table);
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
            'index' => ListTokos::route('/'),
            'create' => CreateToko::route('/create'),
            'edit' => EditToko::route('/{record}/edit'),
        ];
    }
}
