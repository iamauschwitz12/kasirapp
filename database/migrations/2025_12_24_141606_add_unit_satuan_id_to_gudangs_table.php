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
        Schema::table('gudangs', function (Blueprint $table) {
            $table->foreignId('unitsatuan_id')->nullable()->constrained('unit_satuans')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gudangs', function (Blueprint $table) {
            $table->dropForeign(['unitsatuan_id']);
            $table->dropColumn('unitsatuan_id');
        });
    }
};
