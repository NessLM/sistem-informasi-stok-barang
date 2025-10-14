<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Backup data barang yang ada
        $existingBarang = DB::table('barang')->get();
        
        // 2. Drop foreign keys dan constraint yang ada dengan pengecekan
        Schema::table('barang', function (Blueprint $table) {
            // Cek dan drop foreign key kategori_id
            $foreignKeys = $this->getForeignKeys('barang');
            foreach ($foreignKeys as $fk) {
                if (strpos($fk, 'kategori_id') !== false) {
                    $table->dropForeign($fk);
                }
                if (strpos($fk, 'jenis_barang_id') !== false) {
                    $table->dropForeign($fk);
                }
            }
        });

        // Drop unique constraint jika ada
        $indexes = $this->getIndexes('barang');
        if (in_array('barang_kode_kategori_id_unique', $indexes)) {
            DB::statement('ALTER TABLE `barang` DROP INDEX `barang_kode_kategori_id_unique`');
        }

        // 3. Drop tabel stok_gudang jika ada
        if (Schema::hasTable('stok_gudang')) {
            Schema::table('stok_gudang', function (Blueprint $table) {
                $foreignKeys = $this->getForeignKeys('stok_gudang');
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk);
                }
            });
            Schema::dropIfExists('stok_gudang');
        }

        // 4. Drop tabel riwayat_barang jika ada
        if (Schema::hasTable('riwayat_barang')) {
            Schema::table('riwayat_barang', function (Blueprint $table) {
                $foreignKeys = $this->getForeignKeys('riwayat_barang');
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk);
                }
            });
            Schema::dropIfExists('riwayat_barang');
        }

        // 5. Drop tabel barang_keluars jika ada
        if (Schema::hasTable('barang_keluars')) {
            Schema::table('barang_keluars', function (Blueprint $table) {
                $foreignKeys = $this->getForeignKeys('barang_keluars');
                foreach ($foreignKeys as $fk) {
                    $table->dropForeign($fk);
                }
            });
            Schema::dropIfExists('barang_keluars');
        }
        
        // 6. Restructure tabel barang
        Schema::dropIfExists('barang');
        Schema::create('barang', function (Blueprint $table) {
            $table->string('kode_barang', 20)->primary();
            $table->unsignedBigInteger('id_kategori');
            $table->string('nama_barang', 100);
            $table->decimal('harga_barang', 15, 2)->nullable();
            $table->string('satuan', 50);
            $table->timestamps();

            $table->foreign('id_kategori')
                  ->references('id')
                  ->on('kategori')
                  ->onDelete('cascade');
        });

        // 7. Buat tabel pb_stok
        Schema::create('pb_stok', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20);
            $table->integer('stok')->default(0);
            $table->timestamps();

            $table->foreign('kode_barang')
                  ->references('kode_barang')
                  ->on('barang')
                  ->onDelete('cascade');
            
            $table->unique('kode_barang');
        });

        // 8. Buat tabel pj_stok
        Schema::create('pj_stok', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_gudang');
            $table->string('kode_barang', 20);
            $table->unsignedBigInteger('id_kategori');
            $table->integer('stok')->default(0);
            $table->timestamps();

            $table->foreign('id_gudang')
                  ->references('id')
                  ->on('gudang')
                  ->onDelete('cascade');
            
            $table->foreign('kode_barang')
                  ->references('kode_barang')
                  ->on('barang')
                  ->onDelete('cascade');
            
            $table->foreign('id_kategori')
                  ->references('id')
                  ->on('kategori')
                  ->onDelete('cascade');
            
            $table->unique(['kode_barang', 'id_gudang']);
        });

        // 9. Buat tabel transaksi_barang_masuk
        Schema::create('transaksi_barang_masuk', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20);
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->unsignedBigInteger('user_id');
            $table->text('keterangan')->nullable();
            $table->string('bukti')->nullable();
            $table->timestamps();

            $table->foreign('kode_barang')
                  ->references('kode_barang')
                  ->on('barang')
                  ->onDelete('cascade');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // 10. Buat tabel transaksi_distribusi
        Schema::create('transaksi_distribusi', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20);
            $table->unsignedBigInteger('id_gudang_tujuan');
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->unsignedBigInteger('user_id');
            $table->text('keterangan')->nullable();
            $table->string('bukti')->nullable();
            $table->timestamps();

            $table->foreign('kode_barang')
                  ->references('kode_barang')
                  ->on('barang')
                  ->onDelete('cascade');
            
            $table->foreign('id_gudang_tujuan')
                  ->references('id')
                  ->on('gudang')
                  ->onDelete('cascade');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // 11. Buat tabel transaksi_barang_keluar
        Schema::create('transaksi_barang_keluar', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang', 20);
            $table->unsignedBigInteger('id_gudang');
            $table->unsignedBigInteger('bagian_id')->nullable();
            $table->string('nama_penerima');
            $table->integer('jumlah');
            $table->date('tanggal');
            $table->unsignedBigInteger('user_id');
            $table->text('keterangan')->nullable();
            $table->string('bukti')->nullable();
            $table->timestamps();

            $table->foreign('kode_barang')
                  ->references('kode_barang')
                  ->on('barang')
                  ->onDelete('cascade');
            
            $table->foreign('id_gudang')
                  ->references('id')
                  ->on('gudang')
                  ->onDelete('cascade');
            
            $table->foreign('bagian_id')
                  ->references('id')
                  ->on('bagian')
                  ->onDelete('set null');
            
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // 12. Restore data barang (dengan pengecekan duplikat)
        $insertedCodes = [];
        foreach ($existingBarang as $item) {
            // Skip jika kode sudah diinsert
            if (in_array($item->kode, $insertedCodes)) {
                continue;
            }

            DB::table('barang')->insert([
                'kode_barang' => $item->kode,
                'id_kategori' => $item->kategori_id,
                'nama_barang' => $item->nama,
                'harga_barang' => $item->harga,
                'satuan' => $item->satuan,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);

            // Inisialisasi stok PB
            DB::table('pb_stok')->insert([
                'kode_barang' => $item->kode,
                'stok' => $item->stok ?? 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $insertedCodes[] = $item->kode;
        }
    }

    /**
     * Get foreign keys for a table
     */
    private function getForeignKeys($table)
    {
        $foreignKeys = [];
        $constraints = DB::select(
            "SELECT CONSTRAINT_NAME 
             FROM information_schema.TABLE_CONSTRAINTS 
             WHERE TABLE_SCHEMA = ? 
             AND TABLE_NAME = ? 
             AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [env('DB_DATABASE'), $table]
        );
        
        foreach ($constraints as $constraint) {
            $foreignKeys[] = $constraint->CONSTRAINT_NAME;
        }
        
        return $foreignKeys;
    }

    /**
     * Get indexes for a table
     */
    private function getIndexes($table)
    {
        $indexes = [];
        $result = DB::select("SHOW INDEXES FROM `{$table}`");
        
        foreach ($result as $index) {
            $indexes[] = $index->Key_name;
        }
        
        return array_unique($indexes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_barang_keluar');
        Schema::dropIfExists('transaksi_distribusi');
        Schema::dropIfExists('transaksi_barang_masuk');
        Schema::dropIfExists('pj_stok');
        Schema::dropIfExists('pb_stok');
        
        // Restore struktur lama
        Schema::dropIfExists('barang');
        Schema::create('barang', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id');
            $table->unsignedBigInteger('jenis_barang_id')->nullable();
            $table->string('nama');
            $table->integer('jumlah')->nullable();
            $table->string('kode', 50);
            $table->decimal('harga', 15, 2)->nullable();
            $table->integer('stok')->default(0);
            $table->string('satuan', 50)->nullable();
            $table->timestamps();

            $table->foreign('kategori_id')
                  ->references('id')
                  ->on('kategori')
                  ->onDelete('cascade');
            
            $table->foreign('jenis_barang_id')
                  ->references('id')
                  ->on('jenis_barang')
                  ->onDelete('cascade');
            
            $table->unique(['kode', 'kategori_id']);
        });
    }
};