<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            GudangSeeder::class,      // Buat gudang, kategori, jenis barang, dan barang
            PbPjStokSeeder::class,    // Buat stok PB dan PJ
            UserSeeder::class,
        ]);
    }
}
