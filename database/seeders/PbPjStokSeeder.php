<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Barang;
use App\Models\Bagian;
use App\Models\Kategori;
use Illuminate\Support\Facades\DB;

class PbPjStokSeeder extends Seeder
{
    /**
     * LOGIKA BISNIS:
     * 1. PBP menginput barang masuk + harga + bagian asal barang
     * 2. Harga dan bagian_id tersimpan di pb_stok
     * 3. Saat distribusi, harga dari pb_stok akan dicopy ke stok_bagian
     * 4. Harga di pb_stok dan stok_bagian HARUS SINKRON
     */
    
    private $barangRealistis = [
        'Alat Tulis' => [
            ['nama' => 'Pensil 2B Faber-Castell', 'harga' => [3500, 5000]],
            ['nama' => 'Pensil Mekanik Joyko 0.5mm', 'harga' => [8000, 12000]],
            ['nama' => 'Pulpen Pilot G-2 Hitam', 'harga' => [5000, 8000]],
            ['nama' => 'Pulpen Snowman Biru', 'harga' => [3000, 5000]],
            ['nama' => 'Spidol Whiteboard Snowman Hitam', 'harga' => [8000, 12000]],
            ['nama' => 'Spidol Permanent Artline 70 Hitam', 'harga' => [12000, 18000]],
            ['nama' => 'Stabilo Boss Original Kuning', 'harga' => [15000, 22000]],
            ['nama' => 'Penghapus Steadtler Putih', 'harga' => [4000, 6000]],
            ['nama' => 'Correction Pen Tip-Ex', 'harga' => [8000, 12000]],
            ['nama' => 'Isi Staples Max No.10', 'harga' => [3000, 5000]],
        ],
        'Kertas & Buku' => [
            ['nama' => 'Kertas HVS A4 70gsm Sidu (1 Rim)', 'harga' => [35000, 45000]],
            ['nama' => 'Kertas HVS F4 70gsm PaperOne (1 Rim)', 'harga' => [38000, 48000]],
            ['nama' => 'Kertas A3 80gsm Double A (1 Rim)', 'harga' => [65000, 80000]],
            ['nama' => 'Buku Tulis Sinar Dunia 58 Lembar', 'harga' => [5000, 8000]],
            ['nama' => 'Buku Folio Bergaris 100 Lembar', 'harga' => [12000, 18000]],
            ['nama' => 'Buku Agenda A5 Hard Cover', 'harga' => [25000, 35000]],
            ['nama' => 'Amplop Coklat F4', 'harga' => [1500, 3000]],
            ['nama' => 'Amplop Putih Jaya Ukuran Kecil', 'harga' => [800, 1500]],
            ['nama' => 'Map Plastik Folio Bening', 'harga' => [2000, 4000]],
            ['nama' => 'Karton Manila Warna-Warni', 'harga' => [2500, 4000]],
        ],
        'Aksesoris Kantor' => [
            ['nama' => 'Stapler HD-10 Max Jepang', 'harga' => [25000, 35000]],
            ['nama' => 'Perforator Joyko 2 Lubang', 'harga' => [18000, 28000]],
            ['nama' => 'Gunting Stainless Steel 8 inch', 'harga' => [15000, 25000]],
            ['nama' => 'Cutter Kenko Besar L-150', 'harga' => [8000, 12000]],
            ['nama' => 'Penggaris Besi 30cm', 'harga' => [8000, 12000]],
            ['nama' => 'Clipboard A4 Durable', 'harga' => [12000, 18000]],
            ['nama' => 'Paper Clip No.3 Joyko (1 Box)', 'harga' => [8000, 12000]],
            ['nama' => 'Binder Clip 155 (1 Box)', 'harga' => [15000, 22000]],
            ['nama' => 'Lakban Bening Daimaru 2 inch', 'harga' => [12000, 18000]],
            ['nama' => 'Lem Povinal PVAc 350gr', 'harga' => [18000, 25000]],
        ],
        'Kabel & Stopkontak' => [
            ['nama' => 'Kabel NYM 2x1.5mm Supreme (Per Meter)', 'harga' => [3500, 5000]],
            ['nama' => 'Kabel NYA 2.5mm Eterna (Per Meter)', 'harga' => [4000, 6000]],
            ['nama' => 'Kabel Roll 10 Meter 4 Lubang', 'harga' => [85000, 120000]],
            ['nama' => 'Extension Socket 3 Lubang Broco', 'harga' => [35000, 50000]],
            ['nama' => 'Stop Kontak Panasonic WEJ 2918', 'harga' => [25000, 35000]],
            ['nama' => 'Saklar Tunggal Broco', 'harga' => [12000, 18000]],
            ['nama' => 'Fitting Lampu E27 Keramik', 'harga' => [8000, 12000]],
            ['nama' => 'Kabel Ties 20cm Putih (1 Pack)', 'harga' => [8000, 12000]],
            ['nama' => 'Terminal Kabel Warna (1 Set)', 'harga' => [15000, 22000]],
            ['nama' => 'Isolasi Listrik 3M Hitam', 'harga' => [12000, 18000]],
        ],
        'Lampu & Perlengkapan' => [
            ['nama' => 'Lampu LED Philips 9 Watt Putih', 'harga' => [25000, 35000]],
            ['nama' => 'Lampu LED Hannochs 12 Watt', 'harga' => [28000, 38000]],
            ['nama' => 'Lampu TL LED 18 Watt Philips', 'harga' => [45000, 65000]],
            ['nama' => 'Bohlam Pijar 40 Watt', 'harga' => [8000, 12000]],
            ['nama' => 'Lampu Emergency Fitting 5 Watt', 'harga' => [35000, 50000]],
            ['nama' => 'Downlight LED Panel 6 Watt', 'harga' => [38000, 55000]],
            ['nama' => 'Fitting Gantung Keramik E27', 'harga' => [15000, 22000]],
            ['nama' => 'Ballast TL Electronic 36 Watt', 'harga' => [35000, 50000]],
            ['nama' => 'Starter TL 4-80 Watt', 'harga' => [3000, 5000]],
            ['nama' => 'Kap Lampu TL Lengkap 120cm', 'harga' => [65000, 85000]],
        ],
        'Peralatan Instalasi' => [
            ['nama' => 'Tang Kombinasi 8 inch Tekiro', 'harga' => [45000, 65000]],
            ['nama' => 'Tang Potong Stanley 6 inch', 'harga' => [38000, 55000]],
            ['nama' => 'Obeng Set Jackly 31 in 1', 'harga' => [35000, 50000]],
            ['nama' => 'Tespen Indikator Digital', 'harga' => [15000, 25000]],
            ['nama' => 'Tang Ampere Kyoritsu', 'harga' => [350000, 500000]],
            ['nama' => 'MCB 1 Phase 6A Schneider', 'harga' => [55000, 75000]],
            ['nama' => 'ELCB 2 Phase 25A', 'harga' => [180000, 250000]],
            ['nama' => 'Box MCB 4 Group Plastik', 'harga' => [65000, 90000]],
            ['nama' => 'Kabel Lug 25mm Terminal Skun', 'harga' => [8000, 12000]],
            ['nama' => 'Pipa PVC 3/4 inch Rucika (4 meter)', 'harga' => [22000, 32000]],
        ],
        'Alat Kebersihan' => [
            ['nama' => 'Sapu Lidi Super Halus', 'harga' => [15000, 22000]],
            ['nama' => 'Sapu Ijuk Bentuk Kipas', 'harga' => [18000, 28000]],
            ['nama' => 'Pel Lantai Super Mop', 'harga' => [35000, 50000]],
            ['nama' => 'Kemoceng Bulu Ayam Asli', 'harga' => [12000, 18000]],
            ['nama' => 'Sikat WC Plastik + Holder', 'harga' => [15000, 22000]],
            ['nama' => 'Tempat Sampah Plastik 50 Liter', 'harga' => [45000, 65000]],
            ['nama' => 'Ember Plastik Maspion 12 Liter', 'harga' => [25000, 35000]],
            ['nama' => 'Lap Microfiber 30x30cm (3 Pcs)', 'harga' => [18000, 28000]],
            ['nama' => 'Kanebo Lap Kaca Super Absorbent', 'harga' => [12000, 18000]],
            ['nama' => 'Kain Pel Katun Tebal', 'harga' => [15000, 22000]],
        ],
        'Bahan Pembersih' => [
            ['nama' => 'Karbol Wangi Kresek 4 Liter', 'harga' => [35000, 48000]],
            ['nama' => 'Detergen Rinso Matic 1.8 kg', 'harga' => [28000, 38000]],
            ['nama' => 'Sabun Cuci Piring Sunlight 800ml', 'harga' => [15000, 22000]],
            ['nama' => 'Pembersih Lantai Vixal 800ml', 'harga' => [18000, 25000]],
            ['nama' => 'Cairan Pembersih Kaca Mr. Muscle 500ml', 'harga' => [22000, 32000]],
            ['nama' => 'Kamper Kapur Anti Bau WC', 'harga' => [8000, 12000]],
            ['nama' => 'Pewangi Ruangan Stella Spray 400ml', 'harga' => [25000, 35000]],
            ['nama' => 'Pembersih Toilet Duck Gel 500ml', 'harga' => [22000, 32000]],
            ['nama' => 'Sabun Cream Ekonomis 1kg', 'harga' => [18000, 28000]],
            ['nama' => 'Wipol Karbol Sereh 800ml', 'harga' => [15000, 22000]],
        ],
        'Perlengkapan Sanitasi' => [
            ['nama' => 'Tissue Gulung Paseo Elegant 250 Sheet', 'harga' => [8000, 12000]],
            ['nama' => 'Tissue Kotak Nice 250 Sheet', 'harga' => [15000, 22000]],
            ['nama' => 'Sabun Cuci Tangan Lifebuoy 250ml', 'harga' => [12000, 18000]],
            ['nama' => 'Hand Sanitizer Antis 60ml', 'harga' => [8000, 12000]],
            ['nama' => 'Sabun Batang Lifebuoy 110gr', 'harga' => [5000, 8000]],
            ['nama' => 'Kantong Plastik Kresek Hitam Jumbo', 'harga' => [18000, 28000]],
            ['nama' => 'Plastik Sampah Medis Kuning 60x80cm', 'harga' => [35000, 48000]],
            ['nama' => 'Sarung Tangan Karet Tebal', 'harga' => [12000, 18000]],
            ['nama' => 'Masker Sensi 3 Ply (1 Box 50 Pcs)', 'harga' => [35000, 50000]],
            ['nama' => 'Dispenser Sabun Cair Dinding', 'harga' => [45000, 65000]],
        ],
        'Perangkat Keras' => [
            ['nama' => 'Processor Intel Core i5-12400', 'harga' => [2200000, 2800000]],
            ['nama' => 'Processor AMD Ryzen 5 5600G', 'harga' => [1800000, 2400000]],
            ['nama' => 'Motherboard ASUS Prime H610M', 'harga' => [1200000, 1600000]],
            ['nama' => 'RAM DDR4 8GB Kingston 3200MHz', 'harga' => [350000, 450000]],
            ['nama' => 'SSD 256GB WD Green SATA', 'harga' => [320000, 420000]],
            ['nama' => 'HDD 1TB Seagate BarraCuda', 'harga' => [580000, 720000]],
            ['nama' => 'Power Supply Corsair 550W 80+ Bronze', 'harga' => [750000, 950000]],
            ['nama' => 'Casing PC Imperion Mid Tower', 'harga' => [350000, 500000]],
            ['nama' => 'Monitor LED 22 inch Samsung', 'harga' => [1400000, 1800000]],
            ['nama' => 'Keyboard Mouse Logitech MK235 Wireless', 'harga' => [280000, 380000]],
        ],
        'Aksesoris Komputer' => [
            ['nama' => 'Mouse Wireless Logitech M171', 'harga' => [95000, 135000]],
            ['nama' => 'Keyboard Rexus Legionare MX5', 'harga' => [180000, 250000]],
            ['nama' => 'Webcam Logitech C270 HD', 'harga' => [380000, 480000]],
            ['nama' => 'Headset Gaming Rexus F22', 'harga' => [150000, 220000]],
            ['nama' => 'Flashdisk Sandisk 32GB USB 3.0', 'harga' => [85000, 120000]],
            ['nama' => 'External HDD Seagate 1TB', 'harga' => [680000, 850000]],
            ['nama' => 'Kabel HDMI 2.0 5 Meter', 'harga' => [65000, 95000]],
            ['nama' => 'USB Hub 4 Port 3.0', 'harga' => [85000, 125000]],
            ['nama' => 'Cooling Pad Laptop 2 Fan', 'harga' => [95000, 145000]],
            ['nama' => 'Converter HDMI to VGA + Audio', 'harga' => [45000, 65000]],
        ],
        'Jaringan & Server' => [
            ['nama' => 'Router TP-Link Archer C6 AC1200', 'harga' => [350000, 480000]],
            ['nama' => 'Switch TP-Link 8 Port Gigabit', 'harga' => [280000, 380000]],
            ['nama' => 'Access Point Tenda AC10', 'harga' => [320000, 450000]],
            ['nama' => 'Modem Huawei 4G LTE', 'harga' => [450000, 650000]],
            ['nama' => 'Kabel UTP Cat6 Belden (Per Meter)', 'harga' => [3500, 5500]],
            ['nama' => 'RJ45 Connector Cat6 AMP (1 Pack)', 'harga' => [35000, 50000]],
            ['nama' => 'Patch Cord Cat6 3 Meter Belden', 'harga' => [45000, 65000]],
            ['nama' => 'Rack Server 6U Wall Mount', 'harga' => [850000, 1200000]],
            ['nama' => 'UPS APC 1200VA LCD', 'harga' => [1800000, 2400000]],
            ['nama' => 'Tang Crimping RJ45 Professional', 'harga' => [85000, 125000]],
        ],
    ];

    public function run(): void
    {
        $this->command->info('🚀 Memulai seeding dengan data realistis...');
        $this->command->newLine();

        // Cleanup - disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('pb_stok')->truncate();
        DB::table('stok_bagian')->truncate();
        DB::table('barang')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $allBagian = Bagian::all()->keyBy('nama');
        
        // Mapping kategori ke bagian yang bisa menerima
        // SEMUA BAGIAN HARUS DAPAT BARANG DARI KATEGORI INI
        $kategoriBagianMap = [
            'Alat Tulis' => 'all',
            'Kertas & Buku' => 'all',
            'Aksesoris Kantor' => 'all',
            'Kabel & Stopkontak' => 'all',
            'Lampu & Perlengkapan' => 'all',
            'Peralatan Instalasi' => 'all',
            'Alat Kebersihan' => 'all',
            'Bahan Pembersih' => 'all',
            'Perlengkapan Sanitasi' => 'all',
            'Perangkat Keras' => 'all',
            'Aksesoris Komputer' => 'all',
            'Jaringan & Server' => 'all',
        ];

        // Mapping kategori ke prefix kode unik
        $kategoriPrefixMap = [
            'Alat Tulis' => 'ATL',
            'Kertas & Buku' => 'KBK',
            'Aksesoris Kantor' => 'AKS',
            'Kabel & Stopkontak' => 'KST',
            'Lampu & Perlengkapan' => 'LMP',
            'Peralatan Instalasi' => 'INS',
            'Alat Kebersihan' => 'KBR',
            'Bahan Pembersih' => 'BPB',
            'Perlengkapan Sanitasi' => 'SNT',
            'Perangkat Keras' => 'PKS',
            'Aksesoris Komputer' => 'AKP',
            'Jaringan & Server' => 'JSV',
        ];

        foreach ($this->barangRealistis as $namaKategori => $barangList) {
            $kategori = Kategori::where('nama', $namaKategori)->first();
            
            if (!$kategori) {
                $this->command->warn("⚠️  Kategori '{$namaKategori}' tidak ditemukan, skip...");
                continue;
            }

            $this->command->info("📂 Kategori: {$namaKategori}");
            
            $bagianTarget = $kategoriBagianMap[$namaKategori] ?? [];
            if ($bagianTarget === 'all') {
                $bagianTarget = $allBagian->pluck('nama')->toArray();
            }

            foreach ($barangList as $index => $barangData) {
                // Generate kode barang unik menggunakan prefix yang sudah didefinisikan
                $kodeKategori = $kategoriPrefixMap[$namaKategori] ?? strtoupper(substr($namaKategori, 0, 3));
                $kodeBarang = $kodeKategori . sprintf('%03d', $index + 1);

                // Insert barang
                $barang = Barang::create([
                    'kode_barang' => $kodeBarang,
                    'nama_barang' => $barangData['nama'],
                    'id_kategori' => $kategori->id,
                    'satuan' => $this->getSatuan($barangData['nama']),
                ]);

                $this->command->info("  ✓ {$barangData['nama']} ({$kodeBarang})");

                // Distribusi ke bagian-bagian
                foreach ($bagianTarget as $namaBagian) {
                    $bagian = $allBagian->get($namaBagian);
                    if (!$bagian) continue;

                    // Generate harga unik per bagian (dalam range yang ditentukan)
                    $hargaMin = $barangData['harga'][0];
                    $hargaMax = $barangData['harga'][1];
                    $harga = $this->generateHargaUnik($barangData['nama'], $namaBagian, $hargaMin, $hargaMax);

                    // 1. Insert ke PB Stok
                    $stokPB = rand(50, 300);
                    DB::table('pb_stok')->insert([
                        'kode_barang' => $kodeBarang,
                        'bagian_id' => $bagian->id,
                        'stok' => $stokPB,
                        'harga' => $harga,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // 2. Insert ke stok_bagian (SINKRON harga)
                    $stokBagian = rand(5, 40);
                    DB::table('stok_bagian')->insert([
                        'bagian_id' => $bagian->id,
                        'kode_barang' => $kodeBarang,
                        'stok' => $stokBagian,
                        'harga' => $harga,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $this->command->info("    └─ {$namaBagian}: PB={$stokPB}, Bagian={$stokBagian}, Rp " . number_format($harga, 0, ',', '.'));
                }
            }
            
            $this->command->newLine();
        }

        // Testing scenarios
        $this->createTestingData();

        // Summary
        $this->showSummary();
    }

    private function getSatuan(string $namaBarang): string
    {
        $namaLower = strtolower($namaBarang);
        
        if (str_contains($namaLower, 'rim') || str_contains($namaLower, 'pack') || 
            str_contains($namaLower, 'box') || str_contains($namaLower, 'set')) {
            return 'Paket';
        }
        
        if (str_contains($namaLower, 'per meter') || str_contains($namaLower, 'meter')) {
            return 'Meter';
        }
        
        if (str_contains($namaLower, 'liter') || str_contains($namaLower, 'ml')) {
            return 'Liter';
        }
        
        if (str_contains($namaLower, 'kg') || str_contains($namaLower, 'gram')) {
            return 'Kg';
        }
        
        return 'Unit';
    }

    private function generateHargaUnik(string $namaBarang, string $namaBagian, int $min, int $max): int
    {
        // Base harga di tengah-tengah range
        $hargaDasar = ($min + $max) / 2;
        
        // Variasi per bagian ±15%
        $hash = crc32($namaBarang . $namaBagian);
        $variasiPersen = (($hash % 30) - 15) / 100; // -15% sampai +15%
        
        $hargaFinal = (int) round($hargaDasar * (1 + $variasiPersen));
        
        // Pastikan masih dalam range min-max
        $hargaFinal = max($min, min($max, $hargaFinal));
        
        return $hargaFinal;
    }

    private function createTestingData(): void
    {
        $this->command->info('📝 Membuat skenario testing...');

        // Stok habis di beberapa bagian
        $stokHabis = DB::table('stok_bagian')->inRandomOrder()->limit(8)->pluck('id');
        DB::table('stok_bagian')->whereIn('id', $stokHabis)->update(['stok' => 0]);

        // Stok rendah bagian
        $stokRendah = DB::table('stok_bagian')->inRandomOrder()->limit(15)->pluck('id');
        DB::table('stok_bagian')->whereIn('id', $stokRendah)->update(['stok' => rand(1, 5)]);

        // Stok rendah PB
        $pbRendah = DB::table('pb_stok')->inRandomOrder()->limit(10)->pluck('id');
        DB::table('pb_stok')->whereIn('id', $pbRendah)->update(['stok' => rand(5, 20)]);

        $this->command->newLine();
    }

    private function showSummary(): void
    {
        $this->command->info('✅ SEEDING SELESAI');
        $this->command->info('═══════════════════════════════════════');

        $totalBarang = Barang::count();
        $totalPB = DB::table('pb_stok')->count();
        $totalStokPB = DB::table('pb_stok')->sum('stok');
        $totalBagian = DB::table('stok_bagian')->count();
        $totalStokBagian = DB::table('stok_bagian')->sum('stok');

        $this->command->info("📦 Total Jenis Barang: {$totalBarang}");
        $this->command->info("📦 PB Stok: {$totalPB} item ({$totalStokPB} unit)");
        $this->command->info("🏢 Stok Bagian: {$totalBagian} item ({$totalStokBagian} unit)");
        $this->command->newLine();

        // Verifikasi per kategori
        $this->command->info('📊 DISTRIBUSI PER KATEGORI:');
        $distribusi = DB::table('barang')
            ->join('kategori', 'barang.id_kategori', '=', 'kategori.id')
            ->select('kategori.nama', DB::raw('COUNT(*) as jumlah'))
            ->groupBy('kategori.nama')
            ->get();

        foreach ($distribusi as $item) {
            $this->command->info("  {$item->nama}: {$item->jumlah} item");
        }

        $this->command->newLine();

        // Verifikasi per bagian
        $this->command->info('🏢 DISTRIBUSI PER BAGIAN:');
        $perBagian = DB::table('stok_bagian')
            ->join('bagian', 'stok_bagian.bagian_id', '=', 'bagian.id')
            ->select('bagian.nama', 
                     DB::raw('COUNT(DISTINCT kode_barang) as jenis'),
                     DB::raw('SUM(stok) as total_stok'))
            ->groupBy('bagian.nama')
            ->get();

        foreach ($perBagian as $item) {
            $this->command->info("  {$item->nama}: {$item->jenis} jenis ({$item->total_stok} unit)");
        }

        $this->command->newLine();

        // Verifikasi sinkronisasi harga
        $this->command->info('🔍 VERIFIKASI SINKRONISASI HARGA (Sample):');
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
                DB::raw('IF(pb_stok.harga = stok_bagian.harga, "✓", "✗") as status')
            )
            ->limit(5)
            ->get();

        foreach ($sample as $item) {
            $status = $item->status == '✓' ? '✓ SINKRON' : '✗ TIDAK SINKRON';
            $this->command->info("  {$item->nama_barang} ({$item->bagian})");
            $this->command->info("    PB: Rp " . number_format($item->harga_pb, 0, ',', '.') .
                " | Bagian: Rp " . number_format($item->harga_bagian, 0, ',', '.') .
                " → {$status}");
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
        
        $this->command->newLine();
        $this->command->info("🎉 Seeding berhasil dengan data realistis!");
    }
}