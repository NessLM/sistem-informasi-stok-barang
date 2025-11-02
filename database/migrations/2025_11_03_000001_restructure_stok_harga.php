<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * KONSEP BARU:
     * - barang_bagian DIHAPUS (redundan)
     * - stok_bagian DITAMBAH kolom harga
     * - Harga per bagian tersimpan di stok_bagian
     * - PBP input harga saat distribusi
     */
    public function up(): void
    {
        // 1. Tambah kolom harga di stok_bagian
        Schema::table('stok_bagian', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->nullable()->after('stok');
        });

        // 2. Hapus tabel barang_bagian (sudah tidak diperlukan)
        Schema::dropIfExists('barang_bagian');

        // 3. Update transaksi_distribusi: tambah kolom harga
        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->nullable()->after('jumlah');
        });

        // 4. Optional: Tambah kolom harga di transaksi_barang_masuk
        // Untuk tracking harga saat barang masuk ke PB
        Schema::table('transaksi_barang_masuk', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->nullable()->after('jumlah');
        });
    }

    public function down(): void
    {
        // Rollback: kembalikan struktur lama

        // 1. Recreate barang_bagian
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

        // 2. Hapus kolom harga dari stok_bagian
        Schema::table('stok_bagian', function (Blueprint $table) {
            $table->dropColumn('harga');
        });

        // 3. Hapus kolom harga dari transaksi_distribusi
        Schema::table('transaksi_distribusi', function (Blueprint $table) {
            $table->dropColumn('harga');
        });

        // 4. Hapus kolom harga dari transaksi_barang_masuk
        Schema::table('transaksi_barang_masuk', function (Blueprint $table) {
            $table->dropColumn('harga');
        });
    }
};