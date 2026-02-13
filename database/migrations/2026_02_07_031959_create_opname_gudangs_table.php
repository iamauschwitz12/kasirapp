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
        Schema::create('opname_gudangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('nama_barang')->nullable();
            $table->integer('stok_fisik')->default(0);  // Physical stock count
            $table->integer('stok_sistem')->default(0);  // System stock snapshot
            $table->string('status_opname')->nullable(); // selisih/lebih/pas
            $table->date('tanggal_opname');
            $table->string('pic_opname');
            $table->foreignId('cabang_id')->nullable()->constrained('cabangs')->onDelete('set null');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opname_gudangs');
    }
};
