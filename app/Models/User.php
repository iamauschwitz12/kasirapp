<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    const ROLE_ADMIN = 'admin';
    const ROLE_KASIR = 'kasir';
    const ROLE_GUDANG = 'gudang';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'toko_id',
        'cabang_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isKasir(): bool
    {
        return $this->role === self::ROLE_KASIR;
    }

    public function isGudang(): bool
    {
        return $this->role === self::ROLE_GUDANG;
    }
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
    public function toko()
    {
        return $this->belongsTo(Toko::class, 'toko_id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }
}

// For Production
// <?php

// namespace App\Models;

// // 1. IMPORT CLASS FILAMENT
// use Filament\Models\Contracts\FilamentUser;
// use Filament\Panel;

// // use Illuminate\Contracts\Auth\MustVerifyEmail;
// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;

// // 2. TAMBAHKAN 'implements FilamentUser' DI SINI
// class User extends Authenticatable implements FilamentUser
// {
//     /** @use HasFactory<\Database\Factories\UserFactory> */
//     use HasFactory, Notifiable;

//     const ROLE_ADMIN = 'admin';
//     const ROLE_KASIR = 'kasir';
//     const ROLE_GUDANG = 'gudang';

//     protected $fillable = [
//         'name',
//         'email',
//         'password',
//         'role',
//         'toko_id',
//         'cabang_id',
//     ];

//     protected $hidden = [
//         'password',
//         'remember_token',
//     ];

//     protected function casts(): array
//     {
//         return [
//             'email_verified_at' => 'datetime',
//             'password' => 'hashed',
//         ];
//     }

//     /* --- LOGIKA ROLE ANDA --- */
//     public function isAdmin(): bool
//     {
//         return $this->role === self::ROLE_ADMIN;
//     }

//     public function isKasir(): bool
//     {
//         return $this->role === self::ROLE_KASIR;
//     }

//     public function isGudang(): bool
//     {
//         return $this->role === self::ROLE_GUDANG;
//     }

//     /* 3. TAMBAHKAN METHOD INI 
//        Ini adalah kunci agar 403 hilang.
//     */
//     public function canAccessPanel(Panel $panel): bool
//     {
//         // OPSI 1: Hanya Admin yang boleh masuk Admin Panel
//         // return $this->isAdmin();

//         // OPSI 2: Jika Admin, Kasir, dan Gudang boleh masuk semua:
//         return $this->isAdmin() || $this->isKasir() || $this->isGudang();
        
//         // OPSI 3: Izinkan siapa saja (HANYA UNTUK TESTING DARURAT)
//         // return true;
//     }

//     public function sales()
//     {
//         return $this->hasMany(Sale::class);
//     }
    
//     public function toko()
//     {
//         return $this->belongsTo(Toko::class, 'toko_id');
//     }

//     public function cabang()
//     {
//         return $this->belongsTo(Cabang::class, 'cabang_id');
//     }
// }