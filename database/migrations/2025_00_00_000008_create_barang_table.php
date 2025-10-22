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
        Schema::create('barang', function (Blueprint $table) {
            $table->string('kode_barang', 20)->primary();
            $table->unsignedBigInteger('id_kategori');
            $table->string('nama_barang', 100);
            $table->decimal('harga_barang', 15, 2)->nullable();
            $table->string('satuan', 50);
            $table->timestamps();

            // Foreign key hanya ke kategori
            $table->foreign('id_kategori')
                ->references('id')
                ->on('kategori')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};