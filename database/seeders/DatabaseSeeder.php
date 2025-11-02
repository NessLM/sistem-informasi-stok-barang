<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            KategoriSeeder::class,
            PbPjStokSeeder::class,
            UserSeeder::class,
        ]);
    }
}
