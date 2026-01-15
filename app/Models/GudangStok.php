<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GudangStok extends Model
{
    protected $table = 'gudang_stoks'; // pastikan nama tabel sama dengan migrasi

    protected $fillable = [
        'product_id',
        'jumlah_keluar',
        'sisa_stok_akhir',
        'keterangan',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
