<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\Bagian;
use Illuminate\Support\Facades\DB;

class PbPjStokSeeder extends Seeder
{
    /**
     * LOGIKA BISNIS BARU (REVISED):
     * 1. PBP menginput barang masuk + harga + bagian asal barang
     * 2. Harga dan bagian_id tersimpan di pb_stok
     * 3. Saat distribusi, harga dari pb_stok akan dicopy ke stok_bagian
     * 4. Harga di pb_stok dan stok_bagian HARUS SINKRON
     * 5. Distribusi hanya bisa ke bagian yang sama dengan bagian_id di pb_stok
     */
    public function run(): void
    {
        $this->command->info('🚀 Memulai seeding dengan konsep baru (PB Stok sebagai sumber)...');
        $this->command->newLine();

        // Cleanup
        DB::table('pb_stok')->truncate();
        DB::table('stok_bagian')->truncate();

        $barangList = Barang::with('kategori')->get();
        $allBagian = Bagian::all()->keyBy('nama');

        // Mapping kategori ke bagian (bagian yang bisa menerima barang ini)
        $kategoriBagianMap = [
            'Alat Tulis' => 'all',
            'Kertas & Buku' => 'all',
            'Aksesoris Kantor' => 'all',
            'Kabel & Stopkontak' => ['Organisasi', 'Umum & Rumah Tangga'],
            'Lampu & Perlengkapan' => ['Organisasi', 'Umum & Rumah Tangga'],
            'Peralatan Instalasi' => ['Organisasi'],
            'Alat Kebersihan' => ['Umum & Rumah Tangga', 'Organisasi'],
            'Bahan Pembersih' => ['Umum & Rumah Tangga'],
            'Perlengkapan Sanitasi' => ['Umum & Rumah Tangga'],
            'Perangkat Keras' => ['Organisasi', 'Perencanaan & Keuangan'],
            'Aksesoris Komputer' => ['Organisasi', 'Perencanaan & Keuangan'],
            'Jaringan & Server' => ['Organisasi'],
        ];

        foreach ($barangList as $barang) {
            if (!$barang->kategori)
                continue;

            $namaKategori = $barang->kategori->nama;
            $bagianTarget = $kategoriBagianMap[$namaKategori] ?? [];

            if ($bagianTarget === 'all') {
                $bagianTarget = $allBagian->pluck('nama')->toArray();
            }

            // Loop tiap bagian target
            foreach ($bagianTarget as $namaBagian) {
                $bagian = $allBagian->get($namaBagian);
                if (!$bagian)
                    continue;

                // 🎯 Generate harga unik per barang per bagian
                $hargaDasar = $this->generateHargaUnik($barang->nama_barang, $bagian->nama);

                // 1. Insert ke PB Stok
                $stokPB = rand(50, 500);
                DB::table('pb_stok')->insert([
                    'kode_barang' => $barang->kode_barang,
                    'bagian_id' => $bagian->id,
                    'stok' => $stokPB,
                    'harga' => $hargaDasar,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("✓ PB: {$barang->nama_barang} ({$bagian->nama}) → Stok: {$stokPB}, Harga: Rp " . number_format($hargaDasar, 0, ',', '.'));

                // 2. Insert ke stok_bagian (SINKRON harga)
                $stokBagian = rand(10, 50);
                DB::table('stok_bagian')->insert([
                    'bagian_id' => $bagian->id,
                    'kode_barang' => $barang->kode_barang,
                    'stok' => $stokBagian,
                    'harga' => $hargaDasar,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->command->info("  └─ Distribusi ke {$bagian->nama} → Stok: {$stokBagian}, Harga: Rp " . number_format($hargaDasar, 0, ',', '.'));
            }

            $this->command->newLine();
        }


        // Testing scenarios
        $this->createTestingData();

        // Summary
        $this->showSummary();
    }

    private function createTestingData(): void
    {
        $this->command->info('📝 Membuat skenario testing...');

        // Stok habis di beberapa bagian
        $stokHabis = DB::table('stok_bagian')->inRandomOrder()->limit(5)->pluck('id');
        DB::table('stok_bagian')->whereIn('id', $stokHabis)->update(['stok' => 0]);

        // Stok rendah bagian
        $stokRendah = DB::table('stok_bagian')->inRandomOrder()->limit(8)->pluck('id');
        DB::table('stok_bagian')->whereIn('id', $stokRendah)->update(['stok' => rand(1, 9)]);

        // Stok rendah PB
        $pbRendah = DB::table('pb_stok')->inRandomOrder()->limit(5)->pluck('id');
        DB::table('pb_stok')->whereIn('id', $pbRendah)->update(['stok' => rand(5, 20)]);

        $this->command->newLine();
    }

    private function showSummary(): void
    {
        $this->command->info('✅ SEEDING SELESAI');
        $this->command->info('═══════════════════════════════════════');

        $totalPB = DB::table('pb_stok')->count();
        $totalStokPB = DB::table('pb_stok')->sum('stok');
        $totalBagian = DB::table('stok_bagian')->count();
        $totalStokBagian = DB::table('stok_bagian')->sum('stok');

        $this->command->info("📦 PB Stok: {$totalPB} item ({$totalStokPB} unit)");
        $this->command->info("🏢 Stok Bagian: {$totalBagian} item ({$totalStokBagian} unit)");
        $this->command->newLine();

        // Verifikasi sinkronisasi harga
        $this->command->info('🔍 VERIFIKASI SINKRONISASI HARGA:');

        // Ambil sample untuk validasi
        $sample = DB::table('pb_stok')
            ->join('stok_bagian', function ($join) {
                $join->on('pb_stok.kode_barang', '=', 'stok_bagian.kode_barang')
                    ->on('pb_stok.bagian_id', '=', 'stok_bagian.bagian_id');
            })
            ->join('barang', 'pb_stok.kode_barang', '=', 'barang.kode_barang')
            ->join('bagian', 'pb_stok.bagian_id', '=', 'bagian.id')
            ->select(
                'barang.nama_barang',
                'bagian.nama as bagian',
                'pb_stok.harga as harga_pb',
                'stok_bagian.harga as harga_bagian',
                DB::raw('IF(pb_stok.harga = stok_bagian.harga, "✓ SINKRON", "✗ TIDAK SINKRON") as status')
            )
            ->limit(5)
            ->get();

        foreach ($sample as $item) {
            $this->command->info("  {$item->nama_barang} ({$item->bagian})");
            $this->command->info("    PB: Rp " . number_format($item->harga_pb, 0, ',', '.') .
                " | Bagian: Rp " . number_format($item->harga_bagian, 0, ',', '.') .
                " → {$item->status}");
        }

        // Check apakah ada yang tidak sinkron
        $tidakSinkron = DB::table('pb_stok')
            ->join('stok_bagian', function ($join) {
                $join->on('pb_stok.kode_barang', '=', 'stok_bagian.kode_barang')
                    ->on('pb_stok.bagian_id', '=', 'stok_bagian.bagian_id');
            })
            ->whereColumn('pb_stok.harga', '!=', 'stok_bagian.harga')
            ->count();

        $this->command->newLine();
        if ($tidakSinkron > 0) {
            $this->command->error("⚠️  PERHATIAN: Ada {$tidakSinkron} data dengan harga tidak sinkron!");
        } else {
            $this->command->info("✅ Semua harga tersinkronisasi dengan baik!");
        }
    }

    private function generateHargaDasar(string $namaBarang): int
    {
        $namaLower = strtolower($namaBarang);

        if (str_contains($namaLower, 'processor') || str_contains($namaLower, 'motherboard')) {
            return rand(200000, 500000);
        }

        if (str_contains($namaLower, 'ram') || str_contains($namaLower, 'router')) {
            return rand(100000, 300000);
        }

        if (str_contains($namaLower, 'kabel') || str_contains($namaLower, 'bohlam')) {
            return rand(20000, 100000);
        }

        if (str_contains($namaLower, 'sapu') || str_contains($namaLower, 'detergen')) {
            return rand(15000, 50000);
        }

        if (str_contains($namaLower, 'pensil') || str_contains($namaLower, 'pulpen')) {
            return rand(2000, 15000);
        }

        if (str_contains($namaLower, 'kertas') || str_contains($namaLower, 'buku')) {
            return rand(10000, 50000);
        }

        return rand(10000, 100000);
    }
    private function generateHargaUnik(string $namaBarang, string $namaBagian): int
    {
        // Dapatkan harga dasar berdasarkan nama barang
        $hargaDasar = $this->generateHargaDasar($namaBarang);

        // Setiap bagian punya variasi harga  ±20% dari harga dasar
        $variasi = rand(-20, 20) / 100; // bisa -20% sampai +20%
        $hargaFinal = (int) round($hargaDasar * (1 + $variasi));

        // Supaya konsisten tapi tetap random, bisa tambahkan sedikit hash unik
        $hash = crc32($namaBarang . $namaBagian);
        $offset = ($hash % 500) - 250; // ±250
        $hargaFinal += $offset;

        // Minimal harga gak boleh di bawah 1000
        return max(1000, $hargaFinal);
    }

}