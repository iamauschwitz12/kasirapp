<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{

    // Tambahkan baris ini untuk mengizinkan pengisian data
    protected $fillable = [
        'nama_toko',
        'alamat',
        'telepon',
    ];

    /**
     * Relasi ke Gudang Keluar (Jika diperlukan)
     */
    public function gudangKeluars()
    {
        return $this->hasMany(GudangKeluar::class);
    }
}
