<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/print-struk/{id}', function($id) {
    // Ambil data transaksi beserta item dan produknya
    $sale = \App\Models\Sale::with('items.product')->findOrFail($id);
    
    return view('print.struk', [
        'sale' => $sale,
        'items' => $sale->items
    ]);
})->name('print.struk');