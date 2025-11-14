<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pb_stok', function (Blueprint $table) {
            // Hapus unique constraint
            $table->dropUnique('pb_stok_barang_bagian_unique');
            
            // Tambah kolom batch_number untuk tracking batch barang
            $table->string('batch_number', 50)->nullable()->after('bagian_id');
            
            // Buat index baru
            $table->index(['kode_barang', 'bagian_id', 'harga']);
        });
    }

    public function down()
    {
        Schema::table('pb_stok', function (Blueprint $table) {
            $table->dropColumn('batch_number');
            $table->unique(['kode_barang', 'bagian_id'], 'pb_stok_barang_bagian_unique');
        });
    }
};