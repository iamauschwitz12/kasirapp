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
            if (Schema::hasColumn('penjualan_stoks', 'pengirim')) {
            $table->dropColumn('pengirim');
            }
            
            if (Schema::hasColumn('penjualan_stoks', 'asal_gudang')) {
                $table->dropColumn('asal_gudang');
            }
    
            // Tambahkan kolom relasi baru
            // Gunakan if agar tidak terjadi duplikasi kolom jika dijalankan ulang
            if (!Schema::hasColumn('penjualan_stoks', 'pengirim_id')) {
                $table->foreignId('pengirim_id')->nullable()->constrained('pengirims')->onDelete('set null');
            }
            
            if (!Schema::hasColumn('penjualan_stoks', 'asal_gudang_id')) {
                $table->foreignId('asal_gudang_id')->nullable()->constrained('asal_gudangs')->onDelete('set null');
            }
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
