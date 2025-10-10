<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\Barang;
use App\Models\StokGudang;

class StokGudangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua gudang dan barang
        $gudangList = Gudang::all();
        $barangList = Barang::with('kategori.gudang')->get();

        foreach ($barangList as $barang) {
            // Pastikan barang memiliki kategori dan gudang
            if (!$barang->kategori || !$barang->kategori->gudang) {
                continue;
            }

            // Gudang utama (sesuai kategori barang)
            $gudangUtama = $barang->kategori->gudang;
            
            // Buat stok di gudang utama dengan jumlah lebih banyak
            StokGudang::firstOrCreate(
                [
                    'barang_id' => $barang->id,
                    'gudang_id' => $gudangUtama->id,
                ],
                [
                    'stok' => rand(50, 200), // Stok di gudang utama lebih banyak
                ]
            );

            // Distribusi ke gudang lain dengan probabilitas tertentu
            foreach ($gudangList as $gudang) {
                // Skip jika gudang sama dengan gudang utama
                if ($gudang->id === $gudangUtama->id) {
                    continue;
                }

                // 30% kemungkinan barang ada di gudang lain
                if (rand(1, 100) <= 30) {
                    StokGudang::firstOrCreate(
                        [
                            'barang_id' => $barang->id,
                            'gudang_id' => $gudang->id,
                        ],
                        [
                            'stok' => rand(0, 50), // Stok di gudang lain lebih sedikit
                        ]
                    );
                }
            }
        }

        // Buat beberapa barang dengan stok habis untuk testing
        $randomBarang = Barang::inRandomOrder()->limit(5)->get();
        foreach ($randomBarang as $barang) {
            $randomGudang = Gudang::inRandomOrder()->first();
            
            StokGudang::updateOrCreate(
                [
                    'barang_id' => $barang->id,
                    'gudang_id' => $randomGudang->id,
                ],
                [
                    'stok' => 0, // Stok habis
                ]
            );
        }

        // Buat beberapa barang dengan stok rendah untuk testing
        $randomBarangLow = Barang::inRandomOrder()->limit(8)->get();
        foreach ($randomBarangLow as $barang) {
            $randomGudang = Gudang::inRandomOrder()->first();
            
            StokGudang::updateOrCreate(
                [
                    'barang_id' => $barang->id,
                    'gudang_id' => $randomGudang->id,
                ],
                [
                    'stok' => rand(1, 10), // Stok rendah
                ]
            );
        }

        $this->command->info('Stok Gudang berhasil di-seed!');
    }
}