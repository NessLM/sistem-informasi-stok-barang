<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            GudangSeeder::class,
            StokGudangSeeder::class,
            UserSeeder::class
        ]);
    }
}
