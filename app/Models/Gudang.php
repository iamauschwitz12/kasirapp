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

    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
}
