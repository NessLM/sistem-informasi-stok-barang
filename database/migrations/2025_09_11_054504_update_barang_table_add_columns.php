<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // Tambahkan kolom jika belum ada
            if (!Schema::hasColumn('barang', 'kode')) {
                $table->string('kode', 50)->unique()->after('nama_barang');
            }
            if (!Schema::hasColumn('barang', 'harga')) {
                $table->decimal('harga', 15, 2)->default(0)->after('kode');
            }
            if (!Schema::hasColumn('barang', 'stok')) {
                $table->integer('stok')->default(0)->after('harga');
            }
            if (!Schema::hasColumn('barang', 'satuan')) {
                $table->string('satuan', 50)->nullable()->after('stok');
            }
            if (!Schema::hasColumn('barang', 'kategori_id')) {
                $table->unsignedBigInteger('kategori_id')->after('satuan');
                $table->foreign('kategori_id')->references('id')->on('kategoris')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            if (Schema::hasColumn('barang', 'kategori_id')) {
                $table->dropForeign(['kategori_id']);
                $table->dropColumn('kategori_id');
            }
            if (Schema::hasColumn('barang', 'satuan')) {
                $table->dropColumn('satuan');
            }
            if (Schema::hasColumn('barang', 'stok')) {
                $table->dropColumn('stok');
            }
            if (Schema::hasColumn('barang', 'harga')) {
                $table->dropColumn('harga');
            }
            if (Schema::hasColumn('barang', 'kode')) {
                $table->dropColumn('kode');
            }
        });
    }
};
