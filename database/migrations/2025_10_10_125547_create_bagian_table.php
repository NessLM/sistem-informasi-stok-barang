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
        // Create bagian table
        Schema::create('bagian', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->timestamps();
        });

        // Insert data bagian
        DB::table('bagian')->insert([
            ['nama' => 'Tata Pemerintahan', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Kesejahteraan Rakyat & Kemasyarakatan', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Hukum & HAM', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'ADM Pembangunan', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Perekonomian', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'ADM Pelayanan Pengadaan Barang & Jasa', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Protokol', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Organisasi', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Umum & Rumah Tangga', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Perencanaan & Keuangan', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bagian');
    }
};