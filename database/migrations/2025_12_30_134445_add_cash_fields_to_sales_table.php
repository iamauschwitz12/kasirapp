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
            if (!Schema::hasColumn('sales', 'total_price')) {
            $table->decimal('total_price', 15, 2)->default(0)->after('id');
            }

            // Tambahkan cash_received
            if (!Schema::hasColumn('sales', 'cash_received')) {
                $table->decimal('cash_received', 15, 2)->default(0)->after('total_price');
            }

            // Tambahkan cash_change
            if (!Schema::hasColumn('sales', 'cash_change')) {
                $table->decimal('cash_change', 15, 2)->default(0)->after('cash_received');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['cash_received', 'cash_change']);
        });
    }
};
