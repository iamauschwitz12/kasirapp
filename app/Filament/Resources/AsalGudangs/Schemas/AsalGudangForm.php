<?php

namespace App\Filament\Resources\AsalGudangs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AsalGudangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama_gudang')
                    ->label('Nama Gudang')
                    ->required(),
                TextInput::make('lokasi')
                    ->label('Lokasi'),
            ]);
    }
}
