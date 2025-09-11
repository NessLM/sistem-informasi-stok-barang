<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // Hapus id kalau sebelumnya pakai auto increment
            if (Schema::hasColumn('barang', 'id')) {
                $table->dropColumn('id');
            }

            // Tambahkan kolom kode sebagai primary key
            if (!Schema::hasColumn('barang', 'kode')) {
                $table->string('kode', 50)->primary();
            }
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            // rollback: hapus primary key kode, tambahkan id kembali
            $table->dropPrimary(['kode']);
            $table->dropColumn('kode');
            $table->bigIncrements('id');
        });
    }
};
