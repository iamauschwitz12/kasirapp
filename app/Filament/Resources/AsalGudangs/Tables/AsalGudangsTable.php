<?php

namespace App\Filament\Resources\AsalGudangs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class AsalGudangsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_gudang')->label('Nama Gudang')->sortable()->searchable(),
                TextColumn::make('lokasi')->label('Lokasi')->sortable()->searchable(),
                TextColumn::make('created_at')->label('Dibuat Pada')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('updated_at')->label('Diperbarui Pada')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
