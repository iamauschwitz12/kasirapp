<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $guarded = [];
    // Pastikan field ini sesuai dengan migrasi Anda
    protected $fillable = [
        'nomor_transaksi', // Tambahkan ini
        'total_harga', 
        'bayar', 
        'kembalian', 
        'user_id',
        'nama_satuan',     // Tambahkan ini
        'satuan_pilihan',
        'total_price',   // atau 'total' (sesuaikan nama kolom Anda)
        'cash_received',
        'cash_change',  // Tambahkan ini
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function items()
    {
        return $this->hasMany(\App\Models\SaleItem::class); // Sesuaikan nama model detail Anda
    }
}
