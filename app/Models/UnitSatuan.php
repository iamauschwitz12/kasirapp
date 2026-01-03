<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitSatuan extends Model
{
    protected $guarded = [];

    protected $fillable = ['nama_satuan'];

    public function products()
    {
        return $this->hasMany(\App\Models\Pribadi::class, 'unit_satuan_id');
    }

    public function gudangs() {
        return $this->hasMany(Gudang::class, 'unit_satuan_id');
    }
}
