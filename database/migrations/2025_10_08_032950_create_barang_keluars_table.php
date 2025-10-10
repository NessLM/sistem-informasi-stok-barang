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
            // Change 'barangs' to 'barang' if that's your actual table name
            $table->foreignId('barang_id')->constrained('barang')->onDelete('cascade');
            // Change 'gudangs' to 'gudang' if that's your actual table name
            $table->foreignId('gudang_id')->constrained('gudang')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_penerima');
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->string('bagian')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('bukti')->nullable();
            $table->timestamps();
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