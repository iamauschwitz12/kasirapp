<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];
    protected $fillable = [
    'nama_produk', 
    'harga',
    'harga_grosir',
    'satuan_besar',
    'isi_konversi',
    'unit_satuan_id', 
    'kode', 
    'barcode_number', 
    'harga', 
    'stok'];

    protected static function booted()
    {
        static::creating(function ($product) {
            // Jika barcode_number kosong, isi otomatis menggunakan angka unik
            // Contoh: Menggunakan timestamp + angka random agar unik (12 digit)
            if (!$product->barcode_number) {
                $product->barcode_number = '88' . str_pad(rand(0, 9999999999), 10, '0', STR_PAD_LEFT);
            }
        });
    }

    public function unitSatuan()
    {
        return $this->belongsTo(UnitSatuan::class, 'unit_satuan_id');
    }
    public function getStokLengkapAttribute()
    {
        $konversi = $this->isi_konversi ?: 1; 
        $totalPcs = (int) $this->stok; 

        // Mengambil label satuan besar dari database (default ke 'Unit' jika kosong)
        $satuanBesar = $this->satuan_besar ?: 'Unit';

        // Menghitung jumlah grosir (Satuan Besar)
        $jumlahBesar = floor($totalPcs / $konversi);
        
        // Menghitung sisa eceran (Pcs)
        $pcs = $totalPcs % $konversi;

        // Logika Tampilan
        if ($jumlahBesar > 0 && $pcs > 0) {
            return "{$jumlahBesar} {$satuanBesar} + {$pcs} Pcs";
        }
        
        if ($jumlahBesar > 0) {
            return "{$jumlahBesar} {$satuanBesar}";
        }

        return "{$pcs} Pcs";
    }
}