<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole  = Role::where('nama', 'Admin')->first()->id;
        $pbRole     = Role::where('nama', 'Pengelola Barang')->first()->id;
        $atkRole    = Role::where('nama', 'Penanggung Jawab ATK')->first()->id;
        $cleanRole  = Role::where('nama', 'Penanggung Jawab Kebersihan')->first()->id;
        $elecRole   = Role::where('nama', 'Penanggung Jawab Listrik')->first()->id;
        $compRole   = Role::where('nama', 'Penanggung Jawab Bahan Komputer')->first()->id;

        // Admin
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama'     => 'Administrator',
                'password' => 'admin-1234',
                'role_id'  => $adminRole,
                'bagian'   => 'Umum',
            ]
        );

        // Pengelola Barang
        User::updateOrCreate(
            ['username' => 'pb'],
            [
                'nama'     => 'Pengelola Barang',
                'password' => 'pb-1234',
                'role_id'  => $pbRole,
                'bagian'   => 'Gudang',
            ]
        );

        // PJ ATK
        User::updateOrCreate(
            ['username' => 'PJ-ATK'],
            [
                'nama'     => 'Penanggung Jawab ATK',
                'password' => 'atk-1234',
                'role_id'  => $atkRole,
                'bagian'   => 'Operasional',
            ]
        );

        // PJ Kebersihan
        User::updateOrCreate(
            ['username' => 'PJ-Kebersihan'],
            [
                'nama'     => 'Penanggung Jawab Kebersihan',
                'password' => 'kebersihan-1234',
                'role_id'  => $cleanRole,
                'bagian'   => 'Operasional',
            ]
        );

        // PJ Listrik
        User::updateOrCreate(
            ['username' => 'PJ-Listrik'],
            [
                'nama'     => 'Penanggung Jawab Listrik',
                'password' => 'listrik-1234',
                'role_id'  => $elecRole,
                'bagian'   => 'Operasional',
            ]
        );

        // PJ Bahan Komputer
        User::updateOrCreate(
            ['username' => 'PJ-Bahan_Komputer'],
            [
                'nama'     => 'Penanggung Jawab Bahan Komputer',
                'password' => 'komputer-1234',
                'role_id'  => $compRole,
                'bagian'   => 'Operasional',
            ]
        );
    }
}
