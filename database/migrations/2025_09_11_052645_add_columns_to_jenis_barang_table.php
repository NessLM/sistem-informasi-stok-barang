<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('jenis_barang', function (Blueprint $table) {
            if (!Schema::hasColumn('jenis_barang', 'kode')) {
                $table->string('kode')->nullable()->after('kategori_id');
            }
            if (!Schema::hasColumn('jenis_barang', 'harga')) {
                $table->decimal('harga', 12, 2)->nullable()->after('kode');
            }
            if (!Schema::hasColumn('jenis_barang', 'satuan')) {
                $table->string('satuan')->nullable()->after('harga');
            }
        });
    }

    public function down()
    {
        Schema::table('jenis_barang', function (Blueprint $table) {
            $table->dropColumn(['kode','harga','satuan']);
        });
    }
};
