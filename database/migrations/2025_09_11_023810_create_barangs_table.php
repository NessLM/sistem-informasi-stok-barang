<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->decimal('harga', 15, 2);
            $table->integer('stok');
            $table->string('satuan');
            $table->unsignedBigInteger('jenis_barang_id');
            $table->foreign('jenis_barang_id')->references('id')->on('jenis_barang')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang');
    }
};
