<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\PjStok;

class PbPjStokSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua gudang dan barang
        $gudangList = Gudang::all();
        $barangList = Barang::with('kategori.gudang')->get();

        $this->command->info('Memulai seeding PB Stok dan PJ Stok...');

        foreach ($barangList as $barang) {
            // Pastikan barang memiliki kategori dan gudang
            if (!$barang->kategori || !$barang->kategori->gudang) {
                continue;
            }

            // ========== PB STOK (Pengelola Barang) ==========
            // Buat stok untuk Pengelola Barang dengan jumlah lebih banyak
            PbStok::firstOrCreate(
                [
                    'kode_barang' => $barang->kode_barang,
                ],
                [
                    'stok' => rand(100, 500), // Stok PB lebih banyak karena stok pusat
                ]
            );

            // ========== PJ STOK (Penanggung Jawab per Gudang) ==========
            // Gudang utama (sesuai kategori barang)
            $gudangUtama = $barang->kategori->gudang;

            // Buat stok di gudang utama dengan jumlah lebih banyak
            PjStok::firstOrCreate(
                [
                    'kode_barang' => $barang->kode_barang,
                    'id_gudang' => $gudangUtama->id,
                ],
                [
                    'id_kategori' => $barang->id_kategori,
                    'stok' => rand(50, 200), // Stok di gudang utama
                ]
            );

            // Distribusi ke gudang lain dengan probabilitas tertentu
            foreach ($gudangList as $gudang) {
                // Skip jika gudang sama dengan gudang utama
                if ($gudang->id === $gudangUtama->id) {
                    continue;
                }

                // 40% kemungkinan barang ada di gudang lain
                if (rand(1, 100) <= 40) {
                    PjStok::firstOrCreate(
                        [
                            'kode_barang' => $barang->kode_barang,
                            'id_gudang' => $gudang->id,
                        ],
                        [
                            'id_kategori' => $barang->id_kategori,
                            'stok' => rand(5, 50), // Stok di gudang lain lebih sedikit
                        ]
                    );
                }
            }
        }

        // ========== TESTING SCENARIOS ==========

        // 1. Buat beberapa barang dengan stok habis di PJ untuk testing
        $this->command->info('Membuat stok habis untuk testing...');
        $randomBarang = Barang::inRandomOrder()->limit(5)->get();
        foreach ($randomBarang as $barang) {
            $randomGudang = Gudang::inRandomOrder()->first();

            PjStok::updateOrCreate(
                [
                    'kode_barang' => $barang->kode_barang,
                    'id_gudang' => $randomGudang->id,
                ],
                [
                    'id_kategori' => $barang->id_kategori,
                    'stok' => 0, // Stok habis
                ]
            );
        }

        // 2. Buat beberapa barang dengan stok rendah di PJ untuk testing
        $this->command->info('Membuat stok rendah untuk testing...');
        $randomBarangLow = Barang::inRandomOrder()->limit(8)->get();
        foreach ($randomBarangLow as $barang) {
            $randomGudang = Gudang::inRandomOrder()->first();

            PjStok::updateOrCreate(
                [
                    'kode_barang' => $barang->kode_barang,
                    'id_gudang' => $randomGudang->id,
                ],
                [
                    'id_kategori' => $barang->id_kategori,
                    'stok' => rand(1, 10), // Stok rendah
                ]
            );
        }

        // 3. Buat beberapa barang dengan stok rendah di PB untuk testing
        $this->command->info('Membuat stok rendah di PB untuk testing...');
        $randomBarangPbLow = Barang::inRandomOrder()->limit(5)->get();
        foreach ($randomBarangPbLow as $barang) {
            PbStok::updateOrCreate(
                ['kode_barang' => $barang->kode_barang],
                ['stok' => rand(1, 20)] // Stok PB rendah
            );
        }

        // ========== SUMMARY ==========
        $this->command->info('');
        $this->command->info('====================================');
        $this->command->info('✓ Seeding PB & PJ Stok Selesai!');
        $this->command->info('====================================');
        $this->command->info('Total Barang      : ' . $barangList->count());
        $this->command->info('Total Gudang      : ' . $gudangList->count());
        $this->command->info('Total PB Stok     : ' . PbStok::count());
        $this->command->info('Total PJ Stok     : ' . PjStok::count());
        $this->command->info('');
        $this->command->info('Stok Habis (PJ)   : ' . PjStok::where('stok', 0)->count() . ' record');
        $this->command->info('Stok Rendah (PJ)  : ' . PjStok::whereBetween('stok', [1, 10])->count() . ' record');
        $this->command->info('Stok Rendah (PB)  : ' . PbStok::whereBetween('stok', [1, 20])->count() . ' record');
        $this->command->info('====================================');
    }
}