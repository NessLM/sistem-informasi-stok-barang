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
     * LOGIKA BISNIS YANG BENAR:
     * 1. PB STOK = Stok di Gudang Utama (ID 1) - Dikelola Pengelola Barang
     * 2. PJ STOK = Stok di Gudang Spesifik (ID 2-5) - Dikelola Penanggung Jawab
     * 
     * Alur:
     * - Barang masuk → masuk ke PB Stok (Gudang Utama)
     * - Distribusi → dari PB ke PJ (gudang 2-5)
     * - Barang keluar → dari PJ ke user/bagian
     */
    public function run(): void
    {
        $gudangUtama = Gudang::find(1);
        $gudangPJ = Gudang::whereIn('id', [2, 3, 4, 5])->get(); // Gudang ATK, Listrik, Kebersihan, B Komputer
        $barangList = Barang::with('kategori.gudang')->get();

        $this->command->info('====================================');
        $this->command->info('🚀 Memulai seeding PB & PJ Stok...');
        $this->command->info('====================================');
        $this->command->info('');

        if (!$gudangUtama) {
            $this->command->error('❌ Gudang Utama (ID 1) tidak ditemukan!');
            return;
        }

        foreach ($barangList as $barang) {
            // Validasi: barang harus punya kategori dan gudang
            if (!$barang->kategori || !$barang->kategori->gudang) {
                $this->command->warn("⚠ Barang {$barang->kode_barang} tidak memiliki kategori/gudang yang valid. Dilewati.");
                continue;
            }

            $gudangKategori = $barang->kategori->gudang;

            // ========== 1. PB STOK (GUDANG UTAMA - ID 1) ==========
            // Semua barang ada di Gudang Utama dengan stok besar
            $stokPB = rand(5, 20);

            PbStok::updateOrCreate(
                ['kode_barang' => $barang->kode_barang],
                ['stok' => $stokPB]
            );

            $this->command->info("✓ PB Stok: {$barang->kode_barang} → Gudang Utama (Stok: {$stokPB})");

            // ========== 2. PJ STOK (GUDANG 2-5) ==========
            
            // A. Stok di Gudang Kategori (Gudang utama barang ini)
            if (in_array($gudangKategori->id, [2, 3, 4, 5])) {
                // Stok lebih banyak di gudang sesuai kategori
                $stokGudangKategori = rand(5, 20);

                PjStok::updateOrCreate(
                    [
                        'kode_barang' => $barang->kode_barang,
                        'id_gudang' => $gudangKategori->id,
                    ],
                    [
                        'id_kategori' => $barang->id_kategori,
                        'stok' => $stokGudangKategori,
                    ]
                );

                $this->command->info("  └─ PJ Stok: {$gudangKategori->nama} (Stok: {$stokGudangKategori}) [Gudang Utama Barang]");
            }

            // B. Distribusi Silang ke Gudang Lain (Probabilitas)
            foreach ($gudangPJ as $gudang) {
                // Skip gudang kategori (sudah diproses)
                if ($gudang->id === $gudangKategori->id) {
                    continue;
                }

                // Probabilitas distribusi silang berdasarkan jenis barang:
                $kategoriNama = $barang->kategori->nama;
                
                // Barang ATK sering didistribusikan ke semua gudang
                $isATK = in_array($kategoriNama, ['Alat Tulis', 'Kertas & Buku', 'Aksesoris Kantor']);
                
                // Barang listrik kadang ada di gudang lain untuk maintenance
                $isListrik = in_array($kategoriNama, ['Kabel & Stopkontak', 'Lampu & Perlengkapan', 'Peralatan Instalasi']);
                
                // Barang IT jarang didistribusikan silang
                $isIT = in_array($kategoriNama, ['Perangkat Keras', 'Aksesoris Komputer', 'Jaringan & Server']);

                // Tentukan probabilitas
                if ($isATK) {
                    $probability = 60; // ATK: 60% kemungkinan
                } elseif ($isListrik) {
                    $probability = 40; // Listrik: 40% kemungkinan
                } elseif ($isIT) {
                    $probability = 20; // IT: 20% kemungkinan
                } else {
                    $probability = 30; // Lainnya: 30% kemungkinan
                }

                // Roll dice!
                if (rand(1, 100) <= $probability) {
                    // Stok di gudang lain lebih sedikit (distribusi kecil)
                    $stokGudangLain = rand(5, 20);

                    PjStok::updateOrCreate(
                        [
                            'kode_barang' => $barang->kode_barang,
                            'id_gudang' => $gudang->id,
                        ],
                        [
                            'id_kategori' => $barang->id_kategori,
                            'stok' => $stokGudangLain,
                        ]
                    );

                    $this->command->info("  └─ PJ Stok: {$gudang->nama} (Stok: {$stokGudangLain}) [Distribusi Silang]");
                }
            }

            $this->command->info('');
        }

        // ========== TESTING SCENARIOS ==========
        $this->command->info('📝 Membuat skenario testing...');
        $this->command->info('');

        // 1. Stok Habis di beberapa gudang PJ (untuk alert testing)
        $this->command->info('  → Membuat 5 barang dengan stok habis (PJ)...');
        $randomBarang = Barang::inRandomOrder()->limit(5)->get();
        foreach ($randomBarang as $barang) {
            $randomGudang = $gudangPJ->random();

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

        // 2. Stok Rendah di PJ (1-5 unit)
        $this->command->info('  → Membuat 8 barang dengan stok rendah (PJ)...');
        $randomBarangLow = Barang::inRandomOrder()->limit(8)->get();
        foreach ($randomBarangLow as $barang) {
            $randomGudang = $gudangPJ->random();

            PjStok::updateOrCreate(
                [
                    'kode_barang' => $barang->kode_barang,
                    'id_gudang' => $randomGudang->id,
                ],
                [
                    'id_kategori' => $barang->id_kategori,
                    'stok' => rand(1, 5), // Stok rendah
                ]
            );
        }

        // 3. Stok Rendah di PB (untuk alert restock)
        $this->command->info('  → Membuat 5 barang dengan stok rendah (PB)...');
        $randomBarangPbLow = Barang::inRandomOrder()->limit(5)->get();
        foreach ($randomBarangPbLow as $barang) {
            PbStok::updateOrCreate(
                ['kode_barang' => $barang->kode_barang],
                ['stok' => rand(1, 10)] // Stok PB rendah
            );
        }

        // 4. Beberapa barang dengan stok tinggi (untuk testing distribusi)
        $this->command->info('  → Membuat 3 barang dengan stok tinggi (PB)...');
        $randomBarangHigh = Barang::inRandomOrder()->limit(3)->get();
        foreach ($randomBarangHigh as $barang) {
            PbStok::updateOrCreate(
                ['kode_barang' => $barang->kode_barang],
                ['stok' => rand(100, 200)] // Stok tinggi
            );
        }

        $this->command->info('');

        // ========== SUMMARY REPORT ==========
        $this->command->info('====================================');
        $this->command->info('✅ SEEDING SELESAI!');
        $this->command->info('====================================');
        $this->command->info('');

        // Statistik umum
        $this->command->info('📊 STATISTIK UMUM:');
        $this->command->info('  Total Barang         : ' . $barangList->count());
        $this->command->info('  Total Gudang         : ' . ($gudangPJ->count() + 1));
        $this->command->info('  Total PB Stok        : ' . PbStok::count() . ' (Gudang Utama)');
        $this->command->info('  Total PJ Stok        : ' . PjStok::count() . ' (Gudang 2-5)');
        $this->command->info('');

        // Statistik PB Stok (Gudang Utama)
        $totalStokPB = PbStok::sum('stok');
        $this->command->info('📦 STOK GUDANG UTAMA (PB):');
        $this->command->info("  {$gudangUtama->nama}: " . PbStok::count() . " item, Total: {$totalStokPB} unit");
        $this->command->info('');

        // Statistik PJ Stok per gudang
        $this->command->info('📦 STOK GUDANG SPESIFIK (PJ):');
        foreach ($gudangPJ as $gudang) {
            $totalStok = PjStok::where('id_gudang', $gudang->id)->sum('stok');
            $jumlahItem = PjStok::where('id_gudang', $gudang->id)->count();
            $this->command->info("  {$gudang->nama}: {$jumlahItem} item, Total: {$totalStok} unit");
        }
        $this->command->info('');

        // Statistik distribusi silang
        $totalBarang = Barang::count();
        $barangDiSemua = PjStok::select('kode_barang')
            ->groupBy('kode_barang')
            ->havingRaw('COUNT(DISTINCT id_gudang) = ?', [4])
            ->count();
        
        $persentaseDistribusi = round(($barangDiSemua / $totalBarang) * 100, 1);
        
        $this->command->info('🔄 DISTRIBUSI SILANG:');
        $this->command->info("  Barang di semua gudang PJ: {$barangDiSemua} / {$totalBarang} ({$persentaseDistribusi}%)");
        $this->command->info('');

        // Alert testing
        $this->command->info('⚠ TESTING SCENARIOS:');
        $this->command->info('  Stok Habis (PJ)      : ' . PjStok::where('stok', 0)->count() . ' record');
        $this->command->info('  Stok Rendah (PJ)     : ' . PjStok::whereBetween('stok', [1, 5])->count() . ' record');
        $this->command->info('  Stok Rendah (PB)     : ' . PbStok::whereBetween('stok', [1, 10])->count() . ' record');
        $this->command->info('  Stok Tinggi (PB)     : ' . PbStok::where('stok', '>=', 100)->count() . ' record');
        $this->command->info('====================================');
        $this->command->info('');
        
        $this->command->info('💡 TIPS:');
        $this->command->info('  - PB bisa melihat semua stok di Gudang Utama');
        $this->command->info('  - PB bisa distribusikan barang ke gudang 2-5');
        $this->command->info('  - PJ hanya bisa melihat stok di gudangnya sendiri');
        $this->command->info('  - PJ bisa keluarkan barang ke user/bagian');
        $this->command->info('');
    }
}