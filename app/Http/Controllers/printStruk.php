<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class printStruk extends Controller
{
    public function printStruk($id)
    {
        // Ambil data sale beserta itemnya
        $sale = \App\Models\Sale::with('items')->findOrFail($id);

        return view('struk', [
            'sale' => $sale // Ini yang akan dibaca oleh struk.blade.php
        ]);
    }
}
