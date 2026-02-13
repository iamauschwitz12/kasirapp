<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanStok extends Model
{
    protected $fillable = [
        'pengirim_id',
        'no_inv',
        'asal_gudang_id',
        'product_id',
        'qty',
        'toko_id',
        'tgl_masuk'
    ];

    public function pengirim()
    {
        return $this->belongsTo(Pengirim::class);
    }
    public function asalGudang()
    {
        return $this->belongsTo(Cabang::class, 'asal_gudang_id');
    }

    // Alias for asalGudang
    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'asal_gudang_id');
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    public function toko()
    {
        return $this->belongsTo(Toko::class);
    }

    public function unitSatuan()
    {
        return $this->belongsTo(UnitSatuan::class, 'unitsatuan_id');
    }
}
