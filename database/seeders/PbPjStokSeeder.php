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
     * 
     * Seeder ini mengikuti logika bisnis:
     * 1. PB Stok = Stok pusat (Pengelola Barang) - terbanyak
     * 2. PJ Stok Gudang Utama = Stok terbesar kedua
     * 3. PJ Stok Gudang Spesifik = Stok sesuai kategori barang
     * 4. PJ Stok Gudang Lain = Stok sedikit (distribusi silang)
     */
    public function run(): void
    {
        $gudangList = Gudang::all();
        $barangList = Barang::with('kategori.gudang')->get();

        $this->command->info('Memulai seeding PB Stok dan PJ Stok...');
        $this->command->info('');

        foreach ($barangList as $barang) {
            // Validasi: barang harus punya kategori dan gudang
            if (!$barang->kategori || !$barang->kategori->gudang) {
                $this->command->warn("⚠ Barang {$barang->kode_barang} tidak memiliki kategori/gudang yang valid. Dilewati.");
                continue;
            }

            // Identifikasi gudang yang sesuai dengan kategori barang
            $gudangKategori = $barang->kategori->gudang;
            $isGudangUtama = $gudangKategori->id === 1; // Gudang Utama ID = 1

            // ========== 1. PB STOK (Pengelola Barang - Stok Pusat) ==========
            // Stok terbesar karena ini stok pusat sebelum didistribusi
            $stokPB = rand(100, 500);

            PbStok::firstOrCreate(
                ['kode_barang' => $barang->kode_barang],
                ['stok' => $stokPB]
            );

            // ========== 2. PJ STOK GUDANG UTAMA ==========
            // Gudang Utama selalu punya semua barang (stok sedang-besar)
            $gudangUtama = Gudang::find(1);
            if ($gudangUtama) {
                // Jika barang memang dari Gudang Utama, stok lebih besar
                $stokGudangUtama = $isGudangUtama ? rand(80, 200) : rand(30, 100);

                PjStok::firstOrCreate(
                    [
                        'kode_barang' => $barang->kode_barang,
                        'id_gudang' => $gudangUtama->id,
                    ],
                    [
                        'id_kategori' => $barang->id_kategori,
                        'stok' => $stokGudangUtama,
                    ]
                );
            }

            // ========== 3. PJ STOK GUDANG SPESIFIK (sesuai kategori) ==========
            // Barang disimpan di gudang yang sesuai dengan kategorinya
            if (!$isGudangUtama) {
                // Stok di gudang spesifik (lebih banyak karena ini gudang utamanya)
                $stokGudangSpesifik = rand(50, 150);

                PjStok::firstOrCreate(
                    [
                        'kode_barang' => $barang->kode_barang,
                        'id_gudang' => $gudangKategori->id,
                    ],
                    [
                        'id_kategori' => $barang->id_kategori,
                        'stok' => $stokGudangSpesifik,
                    ]
                );
            }

            // ========== 4. DISTRIBUSI SILANG KE GUDANG LAIN ==========
            // Beberapa barang bisa ada di gudang lain (probabilitas lebih kecil)
            foreach ($gudangList as $gudang) {
                // Skip Gudang Utama (sudah diproses)
                if ($gudang->id === 1) {
                    continue;
                }

                // Skip gudang spesifik kategori (sudah diproses)
                if ($gudang->id === $gudangKategori->id) {
                    continue;
                }

                // Probabilitas distribusi silang:
                // - Barang ATK: 50% kemungkinan ada di gudang lain
                // - Barang lainnya: 30% kemungkinan
                $kategoriNama = $barang->kategori->nama;
                $isATK = in_array($kategoriNama, ['Alat Tulis', 'Kertas & Buku', 'Aksesoris Kantor']);
                $probability = $isATK ? 50 : 30;

                if (rand(1, 100) <= $probability) {
                    // Stok di gudang lain lebih sedikit
                    $stokGudangLain = rand(5, 40);

                    PjStok::firstOrCreate(
                        [
                            'kode_barang' => $barang->kode_barang,
                            'id_gudang' => $gudang->id,
                        ],
                        [
                            'id_kategori' => $barang->id_kategori,
                            'stok' => $stokGudangLain,
                        ]
                    );
                }
            }
        }

        // ========== TESTING SCENARIOS ==========
        $this->command->info('');
        $this->command->info('Membuat skenario testing...');

        // 1. Stok Habis di beberapa gudang (untuk alert testing)
        $randomBarang = Barang::inRandomOrder()->limit(5)->get();
        foreach ($randomBarang as $barang) {
            // Pilih gudang random (bukan Gudang Utama)
            $randomGudang = Gudang::where('id', '!=', 1)->inRandomOrder()->first();

            if ($randomGudang) {
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
        }

        // 2. Stok Rendah di PJ (1-10 unit)
        $randomBarangLow = Barang::inRandomOrder()->limit(8)->get();
        foreach ($randomBarangLow as $barang) {
            $randomGudang = Gudang::where('id', '!=', 1)->inRandomOrder()->first();

            if ($randomGudang) {
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
        }

        // 3. Stok Rendah di PB (untuk alert restock)
        $randomBarangPbLow = Barang::inRandomOrder()->limit(5)->get();
        foreach ($randomBarangPbLow as $barang) {
            PbStok::updateOrCreate(
                ['kode_barang' => $barang->kode_barang],
                ['stok' => rand(1, 20)] // Stok PB rendah
            );
        }

        // ========== SUMMARY REPORT ==========
        $this->command->info('');
        $this->command->info('====================================');
        $this->command->info('✓ Seeding PB & PJ Stok Selesai!');
        $this->command->info('====================================');

        // Statistik umum
        $this->command->info('📊 STATISTIK UMUM:');
        $this->command->info('  Total Barang      : ' . $barangList->count());
        $this->command->info('  Total Gudang      : ' . $gudangList->count());
        $this->command->info('  Total PB Stok     : ' . PbStok::count());
        $this->command->info('  Total PJ Stok     : ' . PjStok::count());
        $this->command->info('');

        // Statistik per gudang
        $this->command->info('📦 STOK PER GUDANG:');
        foreach ($gudangList as $gudang) {
            $totalStok = PjStok::where('id_gudang', $gudang->id)->sum('stok');
            $jumlahItem = PjStok::where('id_gudang', $gudang->id)->count();
            $this->command->info("  {$gudang->nama}: {$jumlahItem} item, Total: {$totalStok} unit");
        }
        $this->command->info('');

        // Alert testing
        $this->command->info('⚠ TESTING SCENARIOS:');
        $this->command->info('  Stok Habis (PJ)   : ' . PjStok::where('stok', 0)->count() . ' record');
        $this->command->info('  Stok Rendah (PJ)  : ' . PjStok::whereBetween('stok', [1, 10])->count() . ' record');
        $this->command->info('  Stok Rendah (PB)  : ' . PbStok::whereBetween('stok', [1, 20])->count() . ' record');
        $this->command->info('====================================');
        $this->command->info('');
    }
}