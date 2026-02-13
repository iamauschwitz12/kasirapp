<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Hash;
use Filament\Schemas\Components\Section;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Pengguna')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email (Untuk Login)')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            // Password hanya wajib diisi saat membuat user baru
                            ->required(fn(string $context): bool => $context === 'create')
                            // Hash password secara otomatis sebelum disimpan
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state)),

                        Select::make('role')
                            ->label('Hak Akses / Role')
                            ->options([
                                'admin' => 'Administrator (Akses Semua)',
                                'kasir' => 'Kasir (Hanya Penjualan)',
                                'gudang' => 'Gudang (Hanya Mengelola Stok Gudang)',
                            ])
                            ->required()
                            ->live() // Make it reactive so other fields can respond to changes
                            ->native(false),

                        Select::make('toko_id')
                            ->label('Toko Cabang')
                            ->relationship('toko', 'nama_toko')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih Toko untuk User ini')
                            ->visible(fn(callable $get) => in_array($get('role'), ['admin', 'kasir']))
                            ->required(fn(callable $get) => in_array($get('role'), ['admin', 'kasir'])),

                        Select::make('cabang_id')
                            ->label('Cabang Gudang')
                            ->relationship('cabang', 'nama_cabang')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih Cabang Gudang untuk User ini')
                            ->visible(fn(callable $get) => $get('role') === 'gudang')
                            ->required(fn(callable $get) => $get('role') === 'gudang'),
                    ])->columns(2)
            ]);
    }
}
