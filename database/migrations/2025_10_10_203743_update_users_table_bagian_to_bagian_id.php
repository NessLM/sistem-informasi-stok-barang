<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus kolom bagian yang lama jika ada (tipe string)
            if (Schema::hasColumn('users', 'bagian')) {
                $table->dropColumn('bagian');
            }
            
            // Tambah kolom bagian_id sebagai foreign key
            $table->unsignedBigInteger('bagian_id')->nullable()->after('role_id');
            $table->foreign('bagian_id')->references('id')->on('bagian')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key dan kolom bagian_id
            $table->dropForeign(['bagian_id']);
            $table->dropColumn('bagian_id');
            
            // Kembalikan kolom bagian sebagai string
            $table->string('bagian')->nullable()->after('role_id');
        });
    }
};