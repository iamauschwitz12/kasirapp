<?php

namespace App\Filament\Resources\Cabangs\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class CabangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_cabang')->required(),
                TextInput::make('lokasi'),
            ]);
    }
}
