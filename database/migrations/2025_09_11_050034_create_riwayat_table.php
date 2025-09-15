<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('riwayat', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->time('waktu');
            $table->string('gudang');
            $table->string('nama_barang');
            $table->integer('jumlah');
            $table->string('bagian');
            $table->string('bukti')->nullable(); // Diubah dari boolean menjadi string nullable
            $table->enum('alur_barang', ['Masuk', 'Keluar']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('riwayat');
    }
};