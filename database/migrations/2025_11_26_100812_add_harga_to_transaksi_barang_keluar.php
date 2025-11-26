<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_barang_keluar', function (Blueprint $table) {
            if (!Schema::hasColumn('transaksi_barang_keluar', 'harga')) {
                // mirip transaksi_barang_masuk: harga satuan, boleh null buat data lama
                $table->decimal('harga', 15, 2)->nullable()->after('jumlah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_barang_keluar', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_barang_keluar', 'harga')) {
                $table->dropColumn('harga');
            }
        });
    }
};