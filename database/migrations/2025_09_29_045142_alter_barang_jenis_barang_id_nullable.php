<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->unsignedBigInteger('jenis_barang_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('barang', function (Blueprint $table) {
            $table->unsignedBigInteger('jenis_barang_id')->nullable(false)->change();
        });
    }
};