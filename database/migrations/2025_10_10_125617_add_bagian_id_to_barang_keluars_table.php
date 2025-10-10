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
        Schema::table('barang_keluars', function (Blueprint $table) {
            // Add bagian_id column after user_id
            $table->unsignedBigInteger('bagian_id')->nullable()->after('user_id');
            
            // Add foreign key constraint
            $table->foreign('bagian_id')
                  ->references('id')
                  ->on('bagian')
                  ->onDelete('set null');
        });
        
        // Optional: Update existing data if column 'bagian' has value
        // You can map the string values to bagian_id here
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barang_keluars', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['bagian_id']);
            // Then drop column
            $table->dropColumn('bagian_id');
        });
    }
};