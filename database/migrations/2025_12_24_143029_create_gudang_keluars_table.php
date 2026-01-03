<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gudang_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('no_referensi'); // Ganti dari no_invoice
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('cabang_id')->constrained('cabangs')->onDelete('cascade'); // Tetap pakai cabang_id di DB, tapi label di UI jadi "Toko"
            $table->foreignId('unitsatuan_id')->nullable()->constrained('unit_satuans')->onDelete('set null');
            $table->integer('qty');
            $table->date('tgl_keluar');
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gudang_keluars');
    }
};
