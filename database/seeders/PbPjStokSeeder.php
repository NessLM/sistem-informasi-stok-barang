<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\PjStok;
use Illuminate\Support\Facades\DB;

class PbPjStokSeeder extends Seeder
{
    /**
     * LOGIKA BISNIS:
     * - PB STOK = Gudang Utama (ID 1) - semua barang
     * - PJ STOK = Gudang 2-5 - barang sesuai kategori
     * 
     * Mapping berdasarkan NAMA kategori:
     * - Gudang ATK (2): Alat Tulis, Kertas & Buku, Aksesoris Kantor
     * - Gudang Listrik (3): Kabel & Stopkontak, Lampu & Perlengkapan, Peralatan Instalasi
     * - Gudang Kebersihan (4): Alat Kebersihan, Bahan Pembersih, Perlengkapan Sanitasi
     * - Gudang B Komputer (5): Perangkat Keras, Aksesoris Komputer, Jaringan & Server
     */
    public function run(): void
    {
        // ========== CLEANUP ==========
        $this->command->info('====================================');
        $this->command->info('🧹 MEMBERSIHKAN DATA LAMA...');
        $this->command->info('====================================');
        
        DB::table('pb_stok')->truncate();
        DB::table('pj_stok')->truncate();
        
        $this->command->info('  ✓ Data lama berhasil dihapus');
        $this->command->info('');

        // ========== MAPPING BERDASARKAN NAMA KATEGORI ==========
        $kategoriGudangMap = [
            2 => ['Alat Tulis', 'Kertas & Buku', 'Aksesoris Kantor'],
            3 => ['Kabel & Stopkontak', 'Lampu & Perlengkapan', 'Peralatan Instalasi'],
            4 => ['Alat Kebersihan', 'Bahan Pembersih', 'Perlengkapan Sanitasi'],
            5 => ['Perangkat Keras', 'Aksesoris Komputer', 'Jaringan & Server'],
        ];

        $this->command->info('====================================');
        $this->command->info('📋 MAPPING KATEGORI:');
        $this->command->info('====================================');
        foreach ($kategoriGudangMap as $gudangId => $namaKategori) {
            $gudang = Gudang::find($gudangId);
            $this->command->info("  {$gudang->nama} → " . implode(', ', $namaKategori));
        }
        $this->command->info('');

        // ========== SEEDING ==========
        $this->command->info('====================================');
        $this->command->info('🚀 MULAI SEEDING...');
        $this->command->info('====================================');
        $this->command->info('');

        $barangList = Barang::with('kategori')->get();
        $pbCount = 0;
        $pjCount = 0;

        foreach ($barangList as $barang) {
            if (!$barang->kategori) {
                continue;
            }

            // ========== 1. PB STOK (GUDANG 1 SAJA) ==========
            $stokPB = rand(50, 200);
            
            DB::table('pb_stok')->insert([
                'kode_barang' => $barang->kode_barang,
                'stok' => $stokPB,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            $pbCount++;
            $this->command->info("✓ PB: {$barang->kode_barang} ({$barang->nama_barang}) → Stok: {$stokPB}");

            // ========== 2. PJ STOK (GUDANG 2-5) ==========
            $namaKategoriBarang = $barang->kategori->nama;
            
            // Cari gudang yang cocok berdasarkan nama kategori
            foreach ($kategoriGudangMap as $gudangId => $namaKategoriList) {
                if (in_array($namaKategoriBarang, $namaKategoriList)) {
                    $stokPJ = rand(10, 50);
                    
                    // Ambil kategori_id dari gudang tujuan (bukan dari barang)
                    $kategoriTujuan = Kategori::where('gudang_id', $gudangId)
                        ->where('nama', $namaKategoriBarang)
                        ->first();
                    
                    if (!$kategoriTujuan) {
                        $this->command->warn("  ⚠ Kategori '{$namaKategoriBarang}' tidak ditemukan di Gudang {$gudangId}. Dilewati.");
                        break;
                    }
                    
                    DB::table('pj_stok')->insert([
                        'kode_barang' => $barang->kode_barang,
                        'id_gudang' => $gudangId,
                        'id_kategori' => $kategoriTujuan->id,
                        'stok' => $stokPJ,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $gudangNama = Gudang::find($gudangId)->nama;
                    $pjCount++;
                    $this->command->info("  └─ PJ: {$gudangNama} (Kategori ID: {$kategoriTujuan->id}) → Stok: {$stokPJ}");
                    
                    break; // Hanya 1 gudang per barang
                }
            }

            $this->command->info('');
        }

        // ========== TESTING SCENARIOS ==========
        $this->command->info('====================================');
        $this->command->info('📝 MEMBUAT SKENARIO TESTING...');
        $this->command->info('====================================');
        $this->command->info('');

        // Stok habis
        $this->command->info('  → Stok habis (PJ)...');
        $stokHabis = DB::table('pj_stok')->inRandomOrder()->limit(5)->get();
        foreach ($stokHabis as $stok) {
            DB::table('pj_stok')->where('id', $stok->id)->update(['stok' => 0]);
        }

        // Stok rendah PJ
        $this->command->info('  → Stok rendah (PJ)...');
        $stokRendah = DB::table('pj_stok')->inRandomOrder()->limit(8)->get();
        foreach ($stokRendah as $stok) {
            DB::table('pj_stok')->where('id', $stok->id)->update(['stok' => rand(1, 10)]);
        }

        // Stok rendah PB
        $this->command->info('  → Stok rendah (PB)...');
        $stokPbRendah = DB::table('pb_stok')->inRandomOrder()->limit(5)->get();
        foreach ($stokPbRendah as $stok) {
            DB::table('pb_stok')->where('id', $stok->id)->update(['stok' => rand(5, 20)]);
        }

        // Stok tinggi PB
        $this->command->info('  → Stok tinggi (PB)...');
        $stokTinggi = DB::table('pb_stok')->inRandomOrder()->limit(3)->get();
        foreach ($stokTinggi as $stok) {
            DB::table('pb_stok')->where('id', $stok->id)->update(['stok' => rand(500, 1000)]);
        }

        $this->command->info('');

        // ========== VALIDASI ==========
        $this->command->info('====================================');
        $this->command->info('🔍 VALIDASI DATA');
        $this->command->info('====================================');

        // CEK FATAL: PJ Stok di gudang 1
        $pjDiGudang1 = DB::table('pj_stok')->where('id_gudang', 1)->count();
        if ($pjDiGudang1 > 0) {
            $this->command->error("  ✗ FATAL: {$pjDiGudang1} PJ Stok di Gudang 1!");
            return;
        } else {
            $this->command->info("  ✓ PJ Stok: TIDAK ada di Gudang 1 (benar!)");
        }

        // Cek data per gudang PJ
        $semuaValid = true;
        foreach ([2, 3, 4, 5] as $gudangId) {
            $gudang = Gudang::find($gudangId);
            $jumlah = DB::table('pj_stok')->where('id_gudang', $gudangId)->count();
            
            if ($jumlah > 0) {
                $this->command->info("  ✓ {$gudang->nama}: {$jumlah} barang");
            } else {
                $this->command->warn("  ⚠ {$gudang->nama}: TIDAK ADA BARANG!");
                $semuaValid = false;
            }
        }

        $this->command->info('');
        if ($semuaValid && DB::table('pj_stok')->count() > 0) {
            $this->command->info('  🎉 VALIDASI BERHASIL!');
        } else {
            $this->command->error('  ❌ ADA MASALAH! Cek mapping kategori.');
        }
        $this->command->info('');

        // ========== SUMMARY ==========
        $this->command->info('====================================');
        $this->command->info('✅ SEEDING SELESAI');
        $this->command->info('====================================');
        $this->command->info('');

        $totalPb = DB::table('pb_stok')->count();
        $totalPj = DB::table('pj_stok')->count();
        $totalStokPb = DB::table('pb_stok')->sum('stok');

        $this->command->info('📊 STATISTIK:');
        $this->command->info("  Total Barang      : " . $barangList->count());
        $this->command->info("  PB Stok (Gudang 1): {$totalPb} item ({$totalStokPb} unit)");
        $this->command->info("  PJ Stok (Gudang 2-5): {$totalPj} item");
        $this->command->info('');

        $this->command->info('📦 DETAIL PER GUDANG PJ:');
        foreach ([2, 3, 4, 5] as $gudangId) {
            $gudang = Gudang::find($gudangId);
            $jumlah = DB::table('pj_stok')->where('id_gudang', $gudangId)->count();
            $total = DB::table('pj_stok')->where('id_gudang', $gudangId)->sum('stok');
            $this->command->info("  {$gudang->nama}: {$jumlah} item ({$total} unit)");
        }
        $this->command->info('');

        $this->command->info('⚠ TESTING:');
        $this->command->info('  Stok habis (PJ)  : ' . DB::table('pj_stok')->where('stok', 0)->count());
        $this->command->info('  Stok rendah (PJ) : ' . DB::table('pj_stok')->whereBetween('stok', [1, 10])->count());
        $this->command->info('  Stok rendah (PB) : ' . DB::table('pb_stok')->whereBetween('stok', [1, 20])->count());
        $this->command->info('  Stok tinggi (PB) : ' . DB::table('pb_stok')->where('stok', '>=', 500)->count());
        $this->command->info('');
        $this->command->info('====================================');
    }
}