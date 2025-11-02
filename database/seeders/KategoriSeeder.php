<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\StokBagian;
use App\Models\Bagian;

class KategoriSeeder extends Seeder
{
    /**
     * KONSEP BARU:
     * - Harga tersimpan di stok_bagian (bukan barang_bagian)
     * - 1 barang bisa punya harga berbeda per bagian
     * - Tidak ada lagi tabel barang_bagian
     */
    public function run(): void
    {
        $this->command->info('🚀 Memulai seeding kategori dan barang...');
        $this->command->newLine();

        // Daftar kategori
        $kategoriList = [
            'Alat Tulis',
            'Kertas & Buku',
            'Aksesoris Kantor',
            'Kabel & Stopkontak',
            'Lampu & Perlengkapan',
            'Peralatan Instalasi',
            'Alat Kebersihan',
            'Bahan Pembersih',
            'Perlengkapan Sanitasi',
            'Perangkat Keras',
            'Aksesoris Komputer',
            'Jaringan & Server',
        ];

        // Jenis barang per kategori
        $jenisPerKategori = [
            'Alat Tulis' => ['Pulpen', 'Pensil', 'Spidol'],
            'Kertas & Buku' => ['Kertas A4', 'Buku Catatan', 'Map Dokumen'],
            'Aksesoris Kantor' => ['Binder', 'Clip', 'Lakban'],
            'Kabel & Stopkontak' => ['Kabel Listrik', 'Stopkontak', 'Colokan'],
            'Lampu & Perlengkapan' => ['Bohlam', 'Lampu LED', 'Lampu TL'],
            'Peralatan Instalasi' => ['Obeng', 'Tang', 'Tespen'],
            'Alat Kebersihan' => ['Sapu', 'Pel', 'Kain Lap'],
            'Bahan Pembersih' => ['Detergen', 'Cairan Pembersih', 'Sabun Cuci'],
            'Perlengkapan Sanitasi' => ['Ember', 'Tempat Sampah', 'Sarung Tangan'],
            'Perangkat Keras' => ['Motherboard', 'Processor', 'RAM'],
            'Aksesoris Komputer' => ['Mouse', 'Keyboard', 'Headset'],
            'Jaringan & Server' => ['Router', 'Switch', 'Kabel LAN'],
        ];

        // Ambil semua bagian
        $allBagian = Bagian::all();

        if ($allBagian->isEmpty()) {
            $this->command->error('❌ Tidak ada bagian! Jalankan BagianSeeder dulu.');
            return;
        }

        $totalBarang = 0;
        $totalStokBagian = 0;

        // Proses seeding per kategori
        foreach ($kategoriList as $kategoriNama) {
            $this->command->info("📂 Kategori: {$kategoriNama}");

            // Create kategori
            $kategori = Kategori::firstOrCreate([
                'nama' => $kategoriNama,
            ]);

            $jenisList = $jenisPerKategori[$kategoriNama] ?? [];

            foreach ($jenisList as $jenisNama) {
                for ($i = 1; $i <= 3; $i++) {
                    $namaBarang = $jenisNama . ' ' . $i;
                    $satuan = $this->getSatuan($namaBarang);
                    
                    // Generate kode barang
                    $prefix = strtoupper(substr(str_replace(' ', '', $jenisNama), 0, 2));
                    $kodeBarang = $prefix . str_pad($i, 3, '0', STR_PAD_LEFT);

                    // Create barang
                    $barang = Barang::firstOrCreate(
                        ['kode_barang' => $kodeBarang],
                        [
                            'nama_barang' => $namaBarang,
                            'id_kategori' => $kategori->id,
                            'satuan' => $satuan,
                        ]
                    );

                    $totalBarang++;

                    // Create stok PB (Global)
                    $stokPB = rand(50, 500);
                    PbStok::firstOrCreate(
                        ['kode_barang' => $barang->kode_barang],
                        ['stok' => $stokPB]
                    );

                    $this->command->info("  ✓ {$namaBarang} ({$kodeBarang}) → PB Stok: {$stokPB}");

                    // Distribusi ke bagian (random 3-5 bagian)
                    $bagianSample = $allBagian->random(rand(3, min(5, $allBagian->count())));
                    
                    foreach ($bagianSample as $bagian) {
                        $stokBagian = rand(0, 50);
                        
                        // Generate harga dengan variasi per bagian
                        $hargaBase = $this->generateHargaDasar($namaBarang);
                        $variance = rand(-10, 15); // variasi ±10-15%
                        $hargaBagian = $hargaBase + ($hargaBase * $variance / 100);

                        // 🔥 POIN PENTING: Harga masuk ke stok_bagian
                        StokBagian::firstOrCreate(
                            [
                                'kode_barang' => $barang->kode_barang,
                                'bagian_id' => $bagian->id,
                            ],
                            [
                                'stok' => $stokBagian,
                                'harga' => round($hargaBagian, 2), // 🔥 HARGA DI SINI
                            ]
                        );

                        $totalStokBagian++;

                        $this->command->info("    └─ {$bagian->nama}: Stok {$stokBagian}, Harga Rp " . number_format($hargaBagian, 0, ',', '.'));
                    }

                    $this->command->newLine();
                }
            }
        }

        // Summary
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('✅ SEEDING KATEGORI & BARANG SELESAI');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info("📦 Total Barang: {$totalBarang}");
        $this->command->info("📋 Total Kategori: " . count($kategoriList));
        $this->command->info("🏢 Total Stok Bagian: {$totalStokBagian}");
        $this->command->info("👥 Total Bagian: {$allBagian->count()}");
        $this->command->newLine();
    }

    /**
     * Generate harga dasar berdasarkan nama barang
     */
    private function generateHargaDasar(string $namaBarang): int
    {
        $nama = strtolower($namaBarang);

        // Elektronik & IT (mahal)
        if (
            str_contains($nama, 'processor') ||
            str_contains($nama, 'motherboard')
        ) {
            return rand(2000000, 5000000);
        }

        if (
            str_contains($nama, 'ram') ||
            str_contains($nama, 'router') ||
            str_contains($nama, 'switch')
        ) {
            return rand(500000, 2000000);
        }

        if (
            str_contains($nama, 'keyboard') ||
            str_contains($nama, 'mouse') ||
            str_contains($nama, 'headset')
        ) {
            return rand(100000, 500000);
        }

        // Listrik & Instalasi
        if (
            str_contains($nama, 'kabel') ||
            str_contains($nama, 'lampu') ||
            str_contains($nama, 'bohlam')
        ) {
            return rand(20000, 100000);
        }

        if (
            str_contains($nama, 'stopkontak') ||
            str_contains($nama, 'colokan')
        ) {
            return rand(15000, 50000);
        }

        if (
            str_contains($nama, 'obeng') ||
            str_contains($nama, 'tang') ||
            str_contains($nama, 'tespen')
        ) {
            return rand(10000, 50000);
        }

        // Kebersihan
        if (
            str_contains($nama, 'sapu') ||
            str_contains($nama, 'pel') ||
            str_contains($nama, 'ember')
        ) {
            return rand(15000, 50000);
        }

        if (
            str_contains($nama, 'detergen') ||
            str_contains($nama, 'sabun') ||
            str_contains($nama, 'pembersih')
        ) {
            return rand(20000, 80000);
        }

        // ATK (murah)
        if (
            str_contains($nama, 'pulpen') ||
            str_contains($nama, 'pensil') ||
            str_contains($nama, 'spidol')
        ) {
            return rand(2000, 10000);
        }

        if (
            str_contains($nama, 'clip') ||
            str_contains($nama, 'binder')
        ) {
            return rand(5000, 20000);
        }

        // Kertas & Buku
        if (
            str_contains($nama, 'kertas') ||
            str_contains($nama, 'buku') ||
            str_contains($nama, 'map')
        ) {
            return rand(10000, 50000);
        }

        // Default
        return rand(10000, 100000);
    }

    /**
     * Tentukan satuan berdasarkan nama barang
     */
    private function getSatuan(string $namaBarang): string
    {
        $nama = strtolower($namaBarang);

        return match (true) {
            str_contains($nama, 'pulpen'),
            str_contains($nama, 'pensil'),
            str_contains($nama, 'spidol'),
            str_contains($nama, 'mouse'),
            str_contains($nama, 'keyboard'),
            str_contains($nama, 'headset'),
            str_contains($nama, 'bohlam'),
            str_contains($nama, 'lampu'),
            str_contains($nama, 'obeng'),
            str_contains($nama, 'tang'),
            str_contains($nama, 'tespen'),
            str_contains($nama, 'colokan'),
            str_contains($nama, 'stopkontak'),
            str_contains($nama, 'router'),
            str_contains($nama, 'switch'),
            str_contains($nama, 'ram') => 'Pcs',

            str_contains($nama, 'kertas'),
            str_contains($nama, 'buku'),
            str_contains($nama, 'map'),
            str_contains($nama, 'clip'),
            str_contains($nama, 'lakban'),
            str_contains($nama, 'binder') => 'Pack',

            str_contains($nama, 'sabun'),
            str_contains($nama, 'detergen'),
            str_contains($nama, 'cairan') => 'Box',

            str_contains($nama, 'kabel') => 'Rim',

            str_contains($nama, 'sapu'),
            str_contains($nama, 'pel'),
            str_contains($nama, 'lap'),
            str_contains($nama, 'ember'),
            str_contains($nama, 'sampah'),
            str_contains($nama, 'sarung'),
            str_contains($nama, 'motherboard'),
            str_contains($nama, 'processor') => 'Unit',

            default => 'Pcs',
        };
    }
}