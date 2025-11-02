<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop foreign keys dulu
        Schema::table('pj_stok', function (Blueprint $table) {
            $table->dropForeign(['id_gudang']);
            $table->dropForeign(['kode_barang']);
            $table->dropForeign(['id_kategori']);
        });
        
        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            $table->dropForeign(['kode_barang']);
            $table->dropForeign(['id_gudang_tujuan']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('transaksi_barang_keluar', function (Blueprint $table) {
            $table->dropForeign(['kode_barang']);
            $table->dropForeign(['id_gudang']);
            $table->dropForeign(['bagian_id']);
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gudang_id']);
        });
        
        Schema::table('kategori', function (Blueprint $table) {
            $table->dropForeign(['gudang_id']);
        });
        
        Schema::table('barang', function (Blueprint $table) {
            $table->dropForeign(['id_kategori']);
        });

        // 2. Drop table gudang & update struktur
        Schema::dropIfExists('gudang');

        // 3. Rename pj_stok jadi stok_bagian (stok per bagian)
        Schema::rename('pj_stok', 'stok_bagian');

        // 4. Update tabel kategori - hapus relasi ke gudang
        Schema::table('kategori', function (Blueprint $table) {
            $table->dropColumn('gudang_id');
        });

        // 5. Update tabel barang - hapus harga_barang, tapi tetap ada id_kategori
        Schema::table('barang', function (Blueprint $table) {
            $table->dropColumn('harga_barang');
        });
        
        // Re-add foreign key untuk kategori
        Schema::table('barang', function (Blueprint $table) {
            $table->foreign('id_kategori')->references('id')->on('kategori')->onDelete('cascade');
        });

        // 6. Update stok_bagian (dulunya pj_stok)
        Schema::table('stok_bagian', function (Blueprint $table) {
            // Rename id_gudang jadi bagian_id
            $table->renameColumn('id_gudang', 'bagian_id');
            // Hapus id_kategori (ga perlu lagi, udah ada di barang)
            $table->dropColumn('id_kategori');
        });

        // Re-add foreign keys untuk stok_bagian
        Schema::table('stok_bagian', function (Blueprint $table) {
            $table->foreign('bagian_id')->references('id')->on('bagian')->onDelete('cascade');
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
        });

        // 7. Buat tabel barang_bagian untuk harga per bagian
        Schema::create('barang_bagian', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20);
            $table->unsignedBigInteger('bagian_id');
            $table->decimal('harga_barang', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['kode_barang', 'bagian_id']);
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
            $table->foreign('bagian_id')->references('id')->on('bagian')->onDelete('cascade');
        });

        // 8. Update transaksi_distribusi - ganti id_gudang_tujuan jadi bagian_id
        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            $table->renameColumn('id_gudang_tujuan', 'bagian_id');
        });

        // Re-add foreign keys untuk transaksi_distribusi
        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
            $table->foreign('bagian_id')->references('id')->on('bagian')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 9. Update transaksi_barang_keluar - hapus id_gudang, bagian_id jadi sumber
        Schema::table('transaksi_barang_keluar', function (Blueprint $table) {
            $table->dropColumn('id_gudang');
        });

        // Re-add foreign keys untuk transaksi_barang_keluar
        Schema::table('transaksi_barang_keluar', function (Blueprint $table) {
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
            $table->foreign('bagian_id')->references('id')->on('bagian')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 10. Update users - hapus gudang_id
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gudang_id');
        });
    }

    public function down(): void
    {
        // Rollback
        Schema::create('gudang', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::table('kategori', function (Blueprint $table) {
            $table->unsignedBigInteger('gudang_id')->after('id');
            $table->foreign('gudang_id')->references('id')->on('gudang')->onDelete('cascade');
        });

        Schema::table('barang', function (Blueprint $table) {
            $table->decimal('harga_barang', 15, 2)->nullable()->after('nama_barang');
        });

        Schema::dropIfExists('barang_bagian');

        Schema::rename('stok_bagian', 'pj_stok');

        Schema::table('pj_stok', function (Blueprint $table) {
            $table->renameColumn('bagian_id', 'id_gudang');
            $table->unsignedBigInteger('id_kategori')->after('kode_barang');
            $table->foreign('id_gudang')->references('id')->on('gudang')->onDelete('cascade');
            $table->foreign('kode_barang')->references('kode_barang')->on('barang')->onDelete('cascade');
            $table->foreign('id_kategori')->references('id')->on('kategori')->onDelete('cascade');
        });

        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            $table->renameColumn('bagian_id', 'id_gudang_tujuan');
            $table->foreign('id_gudang_tujuan')->references('id')->on('gudang')->onDelete('cascade');
        });

        Schema::table('transaksi_barang_keluar', function (Blueprint $table) {
            $table->unsignedBigInteger('id_gudang')->after('kode_barang');
            $table->foreign('id_gudang')->references('id')->on('gudang')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('gudang_id')->nullable()->after('bagian_id');
            $table->foreign('gudang_id')->references('id')->on('gudang')->onDelete('set null');
        });
    }
};