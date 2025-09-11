<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JenisBarang;

class JenisBarangSeeder extends Seeder
{
    public function run(): void
    {
        JenisBarang::create([
            'nama' => 'Laptop',
            'kategori_id' => 1
        ]);

        JenisBarang::create([
            'nama' => 'Printer',
            'kategori_id' => 1
        ]);
    }
}
