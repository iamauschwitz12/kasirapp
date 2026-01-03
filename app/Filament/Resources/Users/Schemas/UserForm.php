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
                        ->required(fn (string $context): bool => $context === 'create')
                        // Hash password secara otomatis sebelum disimpan
                        ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                        ->dehydrated(fn ($state) => filled($state)),

                    Select::make('role')
                        ->label('Hak Akses / Role')
                        ->options([
                            'admin' => 'Administrator (Akses Semua)',
                            'kasir' => 'Kasir (Hanya Penjualan)',
                            'gudang' => 'Gudang (Hanya Mengelola Stok Gudang)',
                        ])
                        ->required()
                        ->native(false),
                ])->columns(2)
            ]);
    }
}
