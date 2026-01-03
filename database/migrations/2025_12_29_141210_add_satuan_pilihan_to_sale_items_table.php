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
        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'nama_satuan')) {
            $table->string('nama_satuan')->nullable()->after('qty');
            }

            // Tambahkan satuan_pilihan (eceran/grosir)
            if (!Schema::hasColumn('sale_items', 'satuan_pilihan')) {
                $table->string('satuan_pilihan')->nullable()->after('nama_satuan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['nama_satuan', 'satuan_pilihan']);
        });
    }
};
