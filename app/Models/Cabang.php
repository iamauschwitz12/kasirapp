<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    protected $fillable = ['nama_cabang', 'lokasi'];

    public function gudangs() {
        return $this->hasMany(Gudang::class);
    }
}
