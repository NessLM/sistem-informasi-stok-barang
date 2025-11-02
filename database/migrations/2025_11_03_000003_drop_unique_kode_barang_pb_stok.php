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
        Schema::table('pb_stok', function (Blueprint $table) {
            // Drop unique constraint pada kode_barang
            // Karena satu barang bisa ada di multiple bagian dengan harga berbeda
            $table->dropUnique('pb_stok_kode_barang_unique');
            
            // Tambah bagian_id (jika belum ada dari migration sebelumnya)
            if (!Schema::hasColumn('pb_stok', 'bagian_id')) {
                $table->bigInteger('bagian_id')->unsigned()->nullable()->after('kode_barang');
            }
            
            // Tambah harga (jika belum ada dari migration sebelumnya)
            if (!Schema::hasColumn('pb_stok', 'harga')) {
                $table->decimal('harga', 15, 2)->nullable()->after('stok');
            }
            
            // Tambah foreign key ke tabel bagian (jika belum ada)
            if (!Schema::hasColumn('pb_stok', 'bagian_id')) {
                $table->foreign('bagian_id')
                      ->references('id')
                      ->on('bagian')
                      ->onDelete('set null');
            }
            
            // Buat unique constraint baru untuk kombinasi kode_barang + bagian_id
            // Satu barang hanya boleh 1x per bagian
            $table->unique(['kode_barang', 'bagian_id'], 'pb_stok_barang_bagian_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pb_stok', function (Blueprint $table) {
            // Drop unique constraint kombinasi
            $table->dropUnique('pb_stok_barang_bagian_unique');
            
            // Restore unique constraint lama
            $table->unique('kode_barang', 'pb_stok_kode_barang_unique');
            
            // Drop foreign key jika ada
            $table->dropForeign(['bagian_id']);
            
            // Drop kolom bagian_id dan harga
            $table->dropColumn(['bagian_id', 'harga']);
        });
    }
};