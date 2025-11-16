<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stok_bagian', function (Blueprint $table) {
            // 1. Drop foreign key dulu (jika ada)
            $table->dropForeign(['kode_barang']); // FK ke tabel barang
            $table->dropForeign(['bagian_id']);   // FK ke tabel bagian
        });
        
        // 2. Drop unique constraint lama
        DB::statement('ALTER TABLE stok_bagian DROP INDEX pj_stok_kode_barang_id_gudang_unique');
        
        Schema::table('stok_bagian', function (Blueprint $table) {
            // 3. Tambah kolom batch_number
            $table->string('batch_number', 50)->nullable()->after('bagian_id');
            
            // 4. Re-create foreign keys
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
            $table->foreign('bagian_id')->references('id')->on('bagian')->onDelete('cascade');
            
            // 5. Buat index baru untuk performance
            $table->index(['kode_barang', 'bagian_id'], 'idx_kode_bagian');
        });
        
        // 6. Buat unique constraint baru
        DB::statement('
            ALTER TABLE stok_bagian 
            ADD UNIQUE INDEX stok_bagian_unique (kode_barang, bagian_id, batch_number)
        ');
    }

    public function down(): void
    {
        Schema::table('stok_bagian', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['kode_barang']);
            $table->dropForeign(['bagian_id']);
        });
        
        // Drop indexes
        DB::statement('ALTER TABLE stok_bagian DROP INDEX stok_bagian_unique');
        
        Schema::table('stok_bagian', function (Blueprint $table) {
            $table->dropIndex('idx_kode_bagian');
            $table->dropColumn('batch_number');
            
            // Restore unique constraint lama
            $table->unique(['kode_barang', 'bagian_id'], 'pj_stok_kode_barang_id_gudang_unique');
            
            // Re-create foreign keys
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
            $table->foreign('bagian_id')->references('id')->on('bagian')->onDelete('cascade');
        });
    }
};