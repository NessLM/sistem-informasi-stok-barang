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
        Schema::table('pb_stok', function (Blueprint $table) {
            // Tambah kolom bagian_id untuk tracking asal barang
            $table->bigInteger('bagian_id')->unsigned()->nullable()->after('kode_barang');

            // Tambah kolom harga untuk menyimpan harga barang saat diterima
            $table->decimal('harga', 15, 2)->nullable()->after('stok');

            // Tambah foreign key ke tabel bagian
            $table->foreign('bagian_id')
                ->references('id')
                ->on('bagian')
                ->onDelete('set null');

            // Tambah index untuk performa query
            $table->index(['kode_barang', 'bagian_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pb_stok', function (Blueprint $table) {
            // Drop foreign key terlebih dahulu
            $table->dropForeign(['bagian_id']);

            // Drop index
            $table->dropIndex(['kode_barang', 'bagian_id']);

            // Drop kolom
            $table->dropColumn(['bagian_id', 'harga']);
        });
    }
};