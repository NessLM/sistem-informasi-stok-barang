<?php
// database/seeders/RiwayatSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Riwayat;
use Carbon\Carbon;

class RiwayatSeeder extends Seeder
{
    public function run()
    {
        // NOTE: kalau mau mulai dari kosong setiap seed, buka komentar ini:
        // Riwayat::truncate();

        $data = [];

        // ===== Pool data acuan (sesuai pola contohmu) =====
        $bagians = [
            'Bagian Tata Pemerintahan',
            'Bagian Kesejahteraan Rakyat',
            'Bagian Keuangan',
            'Bagian Hukum',
            'Bagian ADM Pembangunan',
            'Bagian Perekonomian',
            'Bagian Pengadaan',
            'Bagian Protokol',
            'Bagian Organisasi',
            'Bagian Umum',            // akan ter‐exclude di grafik per bagian
            'Bagian Perencanaan',
            'Bagian Komunikasi',
        ];

        $gudangs = ['ATK', 'Listrik', 'Kebersihan', 'Komputer'];

        $barangPool = [
            'Boldliner','Buku Folio','Amplop','Lampu 20 Watt','Kertas A4',
            'Tinta Printer','Stopmap','Mouse','Keyboard','Kabel LAN',
            'Spidol','Pulpen','Stabilo','Toner Printer','Baterai'
        ];

        // ===== Generate data acak lintas tahun (lebih dari cukup untuk test) =====
        // NOTE: ubah $totalRandomRecord kalau mau lebih/kurang banyak
        $totalRandomRecord = 160;
        $buktiCounter = 1;

        for ($i = 0; $i < $totalRandomRecord; $i++) {

            // Tahun acak 2016–2025
            $year   = random_int(2016, 2025);
            $month  = random_int(1, 12);
            $day    = random_int(1, 28); // aman untuk semua bulan
            $tanggal = Carbon::create($year, $month, $day)->format('Y-m-d');

            // Waktu acak (08:00–17:59)
            $waktu = sprintf('%02d:%02d:00', random_int(8, 17), random_int(0, 59));

            // Gudang, Barang, Bagian acak
            $gudang      = $gudangs[array_rand($gudangs)];
            $namaBarang  = $barangPool[array_rand($barangPool)];
            $bagian      = $bagians[array_rand($bagians)];

            // NOTE: 75% Keluar, 25% Masuk (biar grafik Keluar dominan)
            $alur = (mt_rand(1, 100) <= 75) ? 'Keluar' : 'Masuk';

            // NOTE: Untuk transaksi Masuk, kadang tanpa bagian (meniru contohmu)
            if ($alur === 'Masuk' && mt_rand(1, 100) <= 35) {
                $bagian = '';
            }

            $jumlah = random_int(1, 30);
            $bukti  = 'bukti' . ($alur === 'Masuk' ? '_masuk_' : '_keluar_') . ($buktiCounter++) . '.png';

            $data[] = [
                'tanggal'     => $tanggal,
                'waktu'       => $waktu,
                'gudang'      => $gudang,
                'nama_barang' => $namaBarang,
                'jumlah'      => $jumlah,
                'bagian'      => $bagian,
                'bukti'       => $bukti,
                'alur_barang' => $alur,
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        // ===== Data “pasti” dalam 1 minggu terakhir (agar filter mingguan terlihat) =====
        // NOTE: tiga bagian ini akan selalu muncul non-zero saat pilih "1 Minggu Terakhir"
        $recent = [
            ['bagian' => 'Bagian Hukum',     'gudang' => 'ATK',    'nama' => 'Boldliner',     'jumlah' => 7,  'days_ago' => 2,  'waktu' => '10:20:00'],
            ['bagian' => 'Bagian Keuangan',  'gudang' => 'ATK',    'nama' => 'Buku Folio',    'jumlah' => 5,  'days_ago' => 3,  'waktu' => '13:01:00'],
            ['bagian' => 'Bagian Pengadaan', 'gudang' => 'Listrik','nama' => 'Lampu 20 Watt','jumlah' => 10, 'days_ago' => 5,  'waktu' => '11:20:00'],
        ];

        foreach ($recent as $i => $r) {
            $data[] = [
                'tanggal'     => Carbon::now()->subDays($r['days_ago'])->format('Y-m-d'),
                'waktu'       => $r['waktu'],
                'gudang'      => $r['gudang'],
                'nama_barang' => $r['nama'],
                'jumlah'      => $r['jumlah'],
                'bagian'      => $r['bagian'],
                'bukti'       => 'bukti_recent_' . ($i + 1) . '.png',
                'alur_barang' => 'Keluar',
                'created_at'  => now(),
                'updated_at'  => now(),
            ];
        }

        // ===== Insert batch =====
        // NOTE: kalau data sangat besar, bisa di-chunk. Untuk 100–500 row langsung insert aman.
        Riwayat::insert($data);
    }
}
// <?php
// // database/seeders/RiwayatSeeder.php

// namespace Database\Seeders;

// use Illuminate\Database\Seeder;
// use App\Models\Riwayat;
// use Carbon\Carbon;

// class RiwayatSeeder extends Seeder
// {
//     public function run()
//     {
//         $data = [
//             [
//                 'tanggal' => '2025-09-10',
//                 'waktu' => '10:20:00',
//                 'gudang' => 'ATK',
//                 'nama_barang' => 'Boldliner',
//                 'jumlah' => 20,
//                 'bagian' => 'Bagian Hukum',
//                 'bukti' => 'bukti1.png',
//                 'alur_barang' => 'Keluar',
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'tanggal' => '2025-08-10',
//                 'waktu' => '13:01:00',
//                 'gudang' => 'ATK',
//                 'nama_barang' => 'Buku Folio',
//                 'jumlah' => 5,
//                 'bagian' => 'Bagian Keuangan',
//                 'bukti' => 'bukti2.png',
//                 'alur_barang' => 'Keluar',
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'tanggal' => '2025-08-11',
//                 'waktu' => '14:00:00',
//                 'gudang' => 'ATK',
//                 'nama_barang' => 'Amplop',
//                 'jumlah' => 10,
//                 'bagian' => '',
//                 'bukti' => 'bukti3.png',
//                 'alur_barang' => 'Masuk',
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//             [
//                 'tanggal' => '2025-08-11',
//                 'waktu' => '11:20:00',
//                 'gudang' => 'Listrik',
//                 'nama_barang' => 'Lampu 20 Watt',
//                 'jumlah' => 10,
//                 'bagian' => 'Bagian Pengadaan',
//                 'bukti' => 'bukti4.png',
//                 'alur_barang' => 'Keluar',
//                 'created_at' => now(),
//                 'updated_at' => now(),
//             ],
//         ];

//         Riwayat::insert($data);
//     }
// }