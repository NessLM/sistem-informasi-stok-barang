<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stok_gudang', function (Blueprint $table) {
            $table->id();
            $table->foreignId('barang_id')->constrained('barang')->onDelete('cascade');
            $table->foreignId('gudang_id')->constrained('gudang')->onDelete('cascade');
            $table->integer('stok')->default(0);
            $table->timestamps();
            
            // Pastikan kombinasi barang + gudang unik
            $table->unique(['barang_id', 'gudang_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stok_gudang');
    }
};