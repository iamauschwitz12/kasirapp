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
        Schema::create('penjualan_stoks', function (Blueprint $table) {
            $table->id();
            // $table->string('pengirim'); // Manual input via createOptionForm (Select)
            $table->string('no_inv'); // Tulis manual
            // $table->string('asal_gudang'); // Manual input via createOptionForm (Select)
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->integer('qty');
            $table->date('tgl_masuk');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penjualan_stoks');
    }
};
