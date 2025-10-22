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
        Schema::create('riwayat_barang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20); // Ganti dari barang_id ke kode_barang
            $table->string('jenis_transaksi'); // masuk, keluar, distribusi
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->unsignedBigInteger('user_id');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            // Foreign key ke kode_barang bukan id
            $table->foreign('kode_barang')
                ->references('kode_barang')
                ->on('barang')
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
        Schema::dropIfExists('riwayat_barang');
    }
};