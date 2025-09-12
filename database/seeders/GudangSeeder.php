<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\Barang;

class GudangSeeder extends Seeder
{
    public function run(): void
    {
        $gudang = Gudang::create(['nama' => 'Gudang Utama']);

        $kategori = Kategori::create([
            'nama' => 'Elektronik',
            'gudang_id' => $gudang->id
        ]);

        Barang::create([
            'nama' => 'Laptop',
            'kategori_id' => $kategori->id,
            'jumlah' => 10,
            'kode' => 'LP001',
            'harga' => 15000000,
            'stok' => 10,
            'satuan' => 'unit'
        ]);
    }
}
