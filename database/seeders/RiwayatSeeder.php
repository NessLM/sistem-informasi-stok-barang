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
        $data = [
            [
                'tanggal' => '2025-09-10',
                'waktu' => '10:20:00',
                'nama_barang' => 'Boldliner',
                'jumlah' => 20,
                'bagian' => 'Bagian Hukum',
                'bukti' => true,
                'alur_barang' => 'Keluar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tanggal' => '2025-08-10',
                'waktu' => '13:01:00',
                'nama_barang' => 'Buku Folio',
                'jumlah' => 5,
                'bagian' => 'Bagian Keuangan',
                'bukti' => true,
                'alur_barang' => 'Keluar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tanggal' => '2025-08-11',
                'waktu' => '14:00:00',
                'nama_barang' => 'Amplop',
                'jumlah' => 10,
                'bagian' => 'Bagian Umum',
                'bukti' => true,
                'alur_barang' => 'Masuk',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tanggal' => '2025-08-11',
                'waktu' => '11:20:00',
                'nama_barang' => 'Amplop',
                'jumlah' => 10,
                'bagian' => 'Bagian Pengadaan',
                'bukti' => true,
                'alur_barang' => 'Keluar',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        Riwayat::insert($data);
    }
}