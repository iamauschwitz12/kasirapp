<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gudang extends Model
{
    protected $fillable = [
        'no_invoice',
        'product_id',
        'qty',
        'tgl_masuk',
        'cabang_id',
        'unitsatuan_id',
        'supplier_id',
        'sisa_stok',
        'harga_beli',
        'total_harga',
        'user_id',
    ];

    protected $casts = [
        'tgl_masuk' => 'date',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class);
    }

    public function unitSatuan()
    {
        return $this->belongsTo(UnitSatuan::class, 'unitsatuan_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
