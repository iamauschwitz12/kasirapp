<?php

namespace App\Filament\Resources\UnitSatuans\Schemas;
use Filament\Forms\Components\TextInput;

use Filament\Schemas\Schema;

class UnitSatuanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                    TextInput::make('nama_satuan')
                    ->placeholder('Masukan nama satuan')
                    ->unique(ignoreRecord: true)
                    ->required(),
            ]);
    }
}
