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
        Schema::table('penjualan_stoks', function (Blueprint $table) {
            $table->dropColumn(['pengirim', 'asal_gudang']);
        
            // Tambahkan kolom relasi baru
            $table->foreignId('pengirim_id')->nullable()->constrained('pengirims')->onDelete('set null');
            $table->foreignId('asal_gudang_id')->nullable()->constrained('asal_gudangs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualan_stoks', function (Blueprint $table) {
            //
        });
    }
};
