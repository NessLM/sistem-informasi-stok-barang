<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();                                    // PK
            $table->string('nama');                          // Nama lengkap
            $table->string('username')->unique();            // Username unik
            $table->string('password');                      // HASH password
            $table->string('role');                          // Admin|Pengelola Barang|Penanggung Jawab
            $table->string('bagian')->nullable();            // ← Bagian/Unit kerja (opsional)
            $table->rememberToken();                         // "remember me"
            $table->timestamps();

            $table->index('role');
            $table->index('bagian');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
