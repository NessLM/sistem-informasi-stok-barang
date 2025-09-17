<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\JenisBarang;
use App\Models\Barang;

class GudangSeeder extends Seeder
{
    public function run(): void
    {
        $gudang = Gudang::create(['nama' => 'Gudang Utama']);

        // Cek / buat kategori
        $kategori = Kategori::firstOrCreate(
            [
                'nama' => 'Elektronik',
                'gudang_id' => $gudang->id
            ],
            [
                'nama' => 'Elektronik',
                'gudang_id' => $gudang->id
            ]
        );

        // Cek / buat jenis barang
        $jenisBarang = JenisBarang::firstOrCreate(
            [
                'nama' => 'Laptop',
                'kategori_id' => $kategori->id
            ],
            [
                'nama' => 'Laptop',
                'kategori_id' => $kategori->id
            ]
        );

        // Cek / buat barang
        Barang::firstOrCreate(
            ['kode' => 'LP001'], // gunakan kode sebagai unique
            [
                'nama' => 'Laptop Asus ROG',
                'kategori_id' => $kategori->id,
                'jenis_barang_id' => $jenisBarang->id,
                'jumlah' => 10,
                'harga' => 15000000,
                'stok' => 10,
                'satuan' => 'unit'
            ]
        );
    }
}
