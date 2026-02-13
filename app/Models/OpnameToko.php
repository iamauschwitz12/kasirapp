<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpnameToko extends Model
{
    protected $fillable = [
        'product_id',
        'nama_barang',
        'satuan_besar',
        'stok_fisik',
        'stok_pcs',
        'stok_sistem',
        'isi_konversi',
        'total_fisik_pcs',
        'status_opname',
        'tanggal_opname',
        'pic_opname',
        'toko_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_opname' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function toko()
    {
        return $this->belongsTo(\App\Models\Toko::class);
    }
}
