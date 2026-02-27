<?php

namespace App\Filament\Resources\OpnameTokos;

use App\Filament\Resources\OpnameTokos\Pages\CreateOpnameToko;
use App\Filament\Resources\OpnameTokos\Pages\EditOpnameToko;
use App\Filament\Resources\OpnameTokos\Pages\ListOpnameTokos;
use App\Filament\Resources\OpnameTokos\Schemas\OpnameTokoForm;
use App\Filament\Resources\OpnameTokos\Tables\OpnameTokosTable;
use App\Models\OpnameToko;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OpnameTokoResource extends Resource
{
    protected static ?string $model = OpnameToko::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMap;

    protected static ?string $recordTitleAttribute = 'OpnameToko';

    protected static ?string $navigationLabel = 'Opname Stok Fisik';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Jika user BUKAN Super Admin (Anda bisa sesuaikan logika role-nya di sini)
        // Contoh: jika Anda menggunakan Spatie Permissions: if (!$user->hasRole('super_admin'))
        // Atau cara sederhana: jika email bukan email owner
        if ($user->email !== 'plastik_admin@gmail.com') {
            return $query->where('toko_id', $user->toko_id);
        }

        // Jika Super Admin, tampilkan SEMUA data dari SEMUA toko
        return $query;
    }
    public static function canViewAny(): bool
    {
        // Kasir tidak akan melihat menu ini di sidebar
        return in_array(auth()->user()->role, ['admin', 'kasir']);
    }

    public static function canEdit($record): bool
    {
        // Hanya admin yang bisa edit
        return auth()->user()->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return OpnameTokoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OpnameTokosTable::configure($table);
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
            'index' => ListOpnameTokos::route('/'),
            'create' => CreateOpnameToko::route('/create'),
            'edit' => EditOpnameToko::route('/{record}/edit'),
        ];
    }
}
