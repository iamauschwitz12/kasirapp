<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GudangKeluar extends Model
{
    protected $fillable = [
        'no_referensi', 
        'product_id', 
        'cabang_id', 
        'toko_id',
        'unitsatuan_id', 
        'qty', 
        'tgl_keluar', 
        'keterangan'];

    public function product() // Pastikan nama fungsinya 'product'
    {
        return $this->belongsTo(Product::class);
    }

    public function cabang() 
    { 
        return $this->belongsTo(Cabang::class); 
    } // Ini relasi ke toko

    public function unitSatuan() 
    { 
        return $this->belongsTo(UnitSatuan::class, 'unitsatuan_id'); 
    }
    public function toko() {
        return $this->belongsTo(Toko::class);
    }
}
