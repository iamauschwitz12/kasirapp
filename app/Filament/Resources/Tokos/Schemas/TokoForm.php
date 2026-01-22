<?php

namespace App\Filament\Resources\Tokos\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class TokoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_toko')->required(),
                TextInput::make('alamat'),
                TextInput::make('telepon'),
            ]);
    }
}
