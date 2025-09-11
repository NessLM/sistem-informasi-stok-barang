<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            if (!Schema::hasColumn('barang', 'jenis_barang_id')) {
                $table->unsignedBigInteger('jenis_barang_id')->after('id');
                $table->foreign('jenis_barang_id')->references('id')->on('jenis_barang')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->dropForeign(['jenis_barang_id']);
            $table->dropColumn('jenis_barang_id');
        });
    }
};
