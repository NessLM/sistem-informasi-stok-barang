<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah kolom sudah ada
        if (!Schema::hasColumn('transaksi_distribusi', 'status_konfirmasi')) {
            Schema::table('transaksi_distribusi', function (Blueprint $table) {
                // Tambah kolom status konfirmasi
                $table->enum('status_konfirmasi', ['pending', 'confirmed'])
                      ->default('pending')
                      ->after('bukti');
                
                // Tambah kolom waktu konfirmasi
                $table->timestamp('confirmed_at')
                      ->nullable()
                      ->after('status_konfirmasi');
                
                // Tambah kolom user yang konfirmasi
                $table->unsignedBigInteger('confirmed_by')
                      ->nullable()
                      ->after('confirmed_at');
                
                // Foreign key ke users table
                $table->foreign('confirmed_by')
                      ->references('id')
                      ->on('users')
                      ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaksi_distribusi', 'status_konfirmasi')) {
            Schema::table('transaksi_distribusi', function (Blueprint $table) {
                $table->dropForeign(['confirmed_by']);
                $table->dropColumn(['status_konfirmasi', 'confirmed_at', 'confirmed_by']);
            });
        }
    }
};