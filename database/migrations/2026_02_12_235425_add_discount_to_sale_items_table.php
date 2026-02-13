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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('discount', 10, 2)->default(0)->after('subtotal')->comment('Nilai diskon (bisa % atau nominal)');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount')->comment('Nilai diskon dalam rupiah');
            $table->decimal('subtotal_before_discount', 10, 2)->default(0)->after('discount_amount')->comment('Subtotal sebelum diskon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['discount', 'discount_amount', 'subtotal_before_discount']);
        });
    }
};
