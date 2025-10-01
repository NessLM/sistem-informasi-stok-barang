<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('riwayat_barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barang_id');
            $table->enum('jenis_transaksi', ['masuk', 'keluar', 'distribusi']);
            $table->integer('jumlah');
            $table->integer('stok_sebelum');
            $table->integer('stok_sesudah');
            
            // Untuk Barang Masuk
            $table->string('keterangan')->nullable();
            
            // Untuk Distribusi
            $table->unsignedBigInteger('kategori_asal_id')->nullable();
            $table->unsignedBigInteger('kategori_tujuan_id')->nullable();
            $table->unsignedBigInteger('gudang_tujuan_id')->nullable();
            $table->unsignedBigInteger('barang_tujuan_id')->nullable();
            
            $table->string('bukti')->nullable();
            $table->date('tanggal');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('barang_id')->references('id')->on('barang')->onDelete('cascade');
            $table->foreign('kategori_asal_id')->references('id')->on('kategori')->onDelete('set null');
            $table->foreign('kategori_tujuan_id')->references('id')->on('kategori')->onDelete('set null');
            $table->foreign('gudang_tujuan_id')->references('id')->on('gudang')->onDelete('set null');
            $table->foreign('barang_tujuan_id')->references('id')->on('barang')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('riwayat_barang');
    }
};