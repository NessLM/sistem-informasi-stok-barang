<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // Hapus unique constraint lama pada kolom 'kode'
            $table->dropUnique('barang_kode_unique');
            
            // Tambah composite unique constraint pada 'kode' dan 'kategori_id'
            $table->unique(['kode', 'kategori_id'], 'barang_kode_kategori_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // Kembalikan ke struktur semula
            $table->dropUnique('barang_kode_kategori_unique');
            $table->unique('kode', 'barang_kode_unique');
        });
    }
};