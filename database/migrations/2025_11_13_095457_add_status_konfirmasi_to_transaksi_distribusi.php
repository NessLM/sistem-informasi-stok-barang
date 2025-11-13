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
        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            // Tambah kolom status konfirmasi
            $table->enum('status_konfirmasi', ['pending', 'confirmed'])
                  ->default('pending')
                  ->after('bukti')
                  ->comment('Status konfirmasi barang masuk: pending = belum dikonfirmasi, confirmed = sudah dikonfirmasi masuk ke stok');
            
            // Tambah kolom waktu konfirmasi
            $table->timestamp('confirmed_at')
                  ->nullable()
                  ->after('status_konfirmasi')
                  ->comment('Waktu barang dikonfirmasi masuk ke stok');
            
            // Tambah kolom user yang konfirmasi
            $table->unsignedBigInteger('confirmed_by')
                  ->nullable()
                  ->after('confirmed_at')
                  ->comment('ID user yang mengkonfirmasi barang masuk');
            
            // Foreign key ke users table
            $table->foreign('confirmed_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['confirmed_by']);
            
            // Drop kolom
            $table->dropColumn(['status_konfirmasi', 'confirmed_at', 'confirmed_by']);
        });
    }
};