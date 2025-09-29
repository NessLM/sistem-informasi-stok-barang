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
        Schema::create('distribusi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->onDelete('cascade');
            $table->foreignId('user_asal_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('user_tujuan_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('gudang_tujuan')->nullable()->constrained('gudang')->onDelete('set null');
            $table->integer('jumlah');
            $table->dateTime('tanggal');
            $table->string('keterangan')->nullable();
            $table->enum('status', ['pending', 'diterima', 'ditolak'])->default('pending');
            $table->timestamps();
            
            // Index untuk optimasi query
            $table->index('barang_id');
            $table->index('user_asal_id');
            $table->index('user_tujuan_id');
            $table->index('gudang_tujuan');
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribusi');
    }
};