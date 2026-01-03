<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 
        'product_id', 
        'qty', 
        'harga_saat_ini', 
        'subtotal',
        'nama_satuan',
        'satuan_pilihan',
    ];

    /**
     * Relasi balik ke Sale
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relasi ke Product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
