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
        Schema::create('stok_gudang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20); // Ganti dari barang_id
            $table->unsignedBigInteger('gudang_id');
            $table->integer('stok')->default(0);
            $table->timestamps();

            // Foreign key ke kode_barang
            $table->foreign('kode_barang')
                  ->references('kode_barang')
                  ->on('barang')
                  ->onDelete('cascade');

            $table->foreign('gudang_id')
                  ->references('id')
                  ->on('gudang')
                  ->onDelete('cascade');

            // Unique constraint untuk kombinasi kode_barang dan gudang_id
            $table->unique(['kode_barang', 'gudang_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_gudang');
    }
};