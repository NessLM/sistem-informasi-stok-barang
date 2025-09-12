<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('barang', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('kategori_id')
                  ->constrained('kategori')
                  ->onDelete('cascade'); // Relasi ke kategori

            $table->string('nama');
            $table->integer('jumlah')->nullable();
            $table->string('kode', 50)->unique(); // kode barang unik
            $table->decimal('harga', 15, 2)->nullable();
            $table->integer('stok')->default(0);
            $table->string('satuan', 50)->nullable();
            
            $table->timestamps(); // created_at & updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('barang');
    }
};


;
