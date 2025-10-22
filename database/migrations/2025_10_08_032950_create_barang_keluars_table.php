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
        Schema::create('barang_keluars', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20); // Ganti dari barang_id
            $table->unsignedBigInteger('gudang_id');
            $table->unsignedBigInteger('user_id');
            $table->string('nama_penerima');
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->string('bagian')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('bukti')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('kode_barang')
                  ->references('kode_barang')
                  ->on('barang')
                  ->onDelete('cascade');

            $table->foreign('gudang_id')
                  ->references('id')
                  ->on('gudang')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barang_keluars');
    }
};