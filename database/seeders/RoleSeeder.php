<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->delete();
        DB::table('roles')->insert([
            ['nama' => 'Admin'],
            ['nama' => 'Pengurus Barang Pengguna'],
            ['nama' => 'Pengurus Barang Pembantu'],
        ]);
    }
}
