<?php

namespace App\Filament\Resources\Pengirims\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class PengirimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_pengirim')->required(),
                TextInput::make('telepon'),
                TextInput::make('alamat'),
            ]);
    }
}
