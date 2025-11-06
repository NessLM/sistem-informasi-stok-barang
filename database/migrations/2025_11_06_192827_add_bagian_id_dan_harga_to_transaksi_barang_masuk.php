<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_barang_masuk', function (Blueprint $table) {
            // tambah kolom kalau belum ada
            if (!Schema::hasColumn('transaksi_barang_masuk', 'bagian_id')) {
                $table->unsignedBigInteger('bagian_id')->nullable()->after('kode_barang');
            }
            if (!Schema::hasColumn('transaksi_barang_masuk', 'harga')) {
                // bebas: integer/decimal; aku pakai decimal biar aman
                $table->decimal('harga', 15, 2)->nullable()->after('jumlah');
            }
        });

        // tambahkan FK dengan nama default (aman dipanggil sekali via migrate)
        if (Schema::hasColumn('transaksi_barang_masuk', 'bagian_id')) {
            Schema::table('transaksi_barang_masuk', function (Blueprint $table) {
                $table->foreign('bagian_id', 'tbm_bagian_id_foreign')
                      ->references('id')->on('bagian')
                      ->nullOnDelete(); // kalau bagian dihapus → set null
            });
        }
    }

    public function down(): void
    {
        // drop FK kalau ada, lalu drop kolom
        if (Schema::hasColumn('transaksi_barang_masuk', 'bagian_id')) {
            Schema::table('transaksi_barang_masuk', function (Blueprint $table) {
                // pakai nama yang kita set di up(): tbm_bagian_id_foreign
                try { $table->dropForeign('tbm_bagian_id_foreign'); } catch (\Throwable $e) {}
                try { $table->dropForeign(['bagian_id']); } catch (\Throwable $e) {}
            });
        }

        Schema::table('transaksi_barang_masuk', function (Blueprint $table) {
            if (Schema::hasColumn('transaksi_barang_masuk', 'bagian_id')) {
                $table->dropColumn('bagian_id');
            }
            if (Schema::hasColumn('transaksi_barang_masuk', 'harga')) {
                $table->dropColumn('harga');
            }
        });
    }
};
