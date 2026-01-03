<?php

namespace App\Filament\Resources\Pengirims\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class PengirimsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_pengirim')->label('Nama Pengirim')->sortable()->searchable(),
                TextColumn::make('telepon')->label('Telepon')->sortable()->searchable(),
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
