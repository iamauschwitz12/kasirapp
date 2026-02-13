<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpnameGudang extends Model
{
    protected $fillable = [
        'product_id',
        'nama_barang',
        'satuan_besar',
        'stok_fisik',
        'stok_sistem',
        'status_opname',
        'tanggal_opname',
        'pic_opname',
        'cabang_id',
        'keterangan',
    ];

    protected $casts = [
        'tanggal_opname' => 'date',
    ];

    public function product()
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function cabang()
    {
        return $this->belongsTo(\App\Models\Cabang::class);
    }
}
