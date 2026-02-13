<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('opname_tokos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('nama_barang')->nullable();
            $table->string('satuan_besar')->nullable();
            $table->integer('stok_fisik')->default(0);
            $table->integer('stok_pcs')->default(0);
            $table->integer('stok_sistem')->default(0); // System stock snapshot in Pcs
            $table->integer('isi_konversi')->default(1); // Conversion rate
            $table->integer('total_fisik_pcs')->default(0); // Total physical stock in Pcs
            $table->string('status_opname')->nullable(); // selisih/lebih/pas
            $table->date('tanggal_opname');
            $table->string('pic_opname');
            $table->foreignId('toko_id')->nullable()->constrained('tokos')->onDelete('set null');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opname_tokos');
    }
};
