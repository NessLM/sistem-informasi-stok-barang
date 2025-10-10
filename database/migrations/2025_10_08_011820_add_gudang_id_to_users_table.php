<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom gudang_id ke tabel users
     */
    public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->unsignedBigInteger('gudang_id')->nullable();
        $table->foreign('gudang_id')
              ->references('id')
              ->on('gudang')
              ->onDelete('set null');
    });
}


    /**
     * Hapus kolom gudang_id (rollback)
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['gudang_id']);
            $table->dropColumn('gudang_id');
        });
    }
};
