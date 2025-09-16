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
        $gudang = Gudang::create(['nama' => 'Gudang ATK']);
        $gudang = Gudang::create(['nama' => 'Gudang Listrik']);
        $gudang = Gudang::create(['nama' => 'Gudang Kebersihan']);
        $gudang = Gudang::create(['nama' => 'Gudang B Komputer']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik',
            'gudang_id' => $gudang->id
        ]);

        $jenisBarang = JenisBarang::create([
            'nama' => 'Laptop',
            'kategori_id' => $kategori->id
        ]);

        Barang::create([
            'nama' => 'Laptop Asus ROG',
            'kategori_id' => $kategori->id,
            'jenis_barang_id' => $jenisBarang->id, // ⬅️ WAJIB
            'jumlah' => 10,
            'kode' => 'LP001',
            'harga' => 15000000,
            'stok' => 10,
            'satuan' => 'unit'
        ]);
    }
}
