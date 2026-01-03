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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('harga_grosir', 15, 2)->default(0)->after('harga');
            $table->string('satuan_besar')->nullable()->comment('Contoh: Dus, Kotak, Bal')->after('harga_grosir');
            $table->integer('isi_konversi')->default(1)->comment('Jumlah eceran dalam 1 satuan besar')->after('satuan_besar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['harga_grosir', 'satuan_besar', 'isi_konversi']);
        });
    }
};
