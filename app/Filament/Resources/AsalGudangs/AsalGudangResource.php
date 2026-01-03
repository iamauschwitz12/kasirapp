<?php

namespace App\Filament\Resources\AsalGudangs;

use App\Filament\Resources\AsalGudangs\Pages\CreateAsalGudang;
use App\Filament\Resources\AsalGudangs\Pages\EditAsalGudang;
use App\Filament\Resources\AsalGudangs\Pages\ListAsalGudangs;
use App\Filament\Resources\AsalGudangs\Schemas\AsalGudangForm;
use App\Filament\Resources\AsalGudangs\Tables\AsalGudangsTable;
use App\Models\AsalGudang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class AsalGudangResource extends Resource
{
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin']);
    }
    protected static ?string $model = AsalGudang::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $recordTitleAttribute = 'AsalGudang';

    protected static string | UnitEnum | null $navigationGroup = 'Kategori Manajemen';

    public static function form(Schema $schema): Schema
    {
        return AsalGudangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AsalGudangsTable::configure($table);
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
            'index' => ListAsalGudangs::route('/'),
            'create' => CreateAsalGudang::route('/create'),
            'edit' => EditAsalGudang::route('/{record}/edit'),
        ];
    }
}
