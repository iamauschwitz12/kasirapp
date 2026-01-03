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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'bayar')) {
                $table->decimal('bayar', 15, 2)->default(0)->after('total_harga');
            }
            if (!Schema::hasColumn('sales', 'kembalian')) {
                $table->decimal('kembalian', 15, 2)->default(0)->after('bayar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['bayar', 'kembalian']);
        });
    }
};
