<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Kamu bisa override via .env bila mau
        $adminName  = env('ADMIN_NAME', 'Administrator');
        $adminUser  = env('ADMIN_USERNAME', 'admin');
        $adminPass  = env('ADMIN_PASSWORD', 'admin-1234');

        $pbName     = env('PB_NAME', 'Pengelola Barang');
        $pbUser     = env('PB_USERNAME', 'pb');
        $pbPass     = env('PB_PASSWORD', 'pb-1234');

        $pjName1     = env('PJ_NAME1', 'Penanggung Jawab1');
        $pjUser1    = env('PJ_USERNAME', 'pj1');
        $pjPass1     = env('PJ_PASSWORD', 'pj1-1234');

        $pjName2     = env('PJ_NAME2', 'Penanggung Jawab2');
        $pjUser2     = env('PJ_USERNAME', 'pj2');
        $pjPass2     = env('PJ_PASSWORD', 'pj2-1234');

        $pjName3     = env('PJ_NAME3', 'Penanggung Jawab3');
        $pjUser3    = env('PJ_USERNAME', 'pj3');
        $pjPass3     = env('PJ_PASSWORD', 'pj3-1234');

        $pjName4     = env('PJ_NAME4', 'Penanggung Jawab4');
        $pjUser4     = env('PJ_USERNAME', 'pj4');
        $pjPass4     = env('PJ_PASSWORD', 'pj4-1234');

        // Admin
        User::updateOrCreate(
            ['username' => $adminUser],
            [
                'nama'    => $adminName,
                'password'=> $adminPass,          // akan di-hash oleh casts('password' => 'hashed')
                // 'password'=> Hash::make($adminPass), // pakai ini kalau tidak pakai casts
                'role'    => 'Admin',
                'bagian'  => 'Umum',
            ]
        );

        // Pengelola Barang (PB)
        User::updateOrCreate(
            ['username' => $pbUser],
            [
                'nama'    => $pbName,
                'password'=> $pbPass,             // atau Hash::make($pbPass)
                'role'    => 'Pengelola Barang',
                'bagian'  => 'Gudang',
            ]
        );

        // Penanggung Jawab (PJ1)
        User::updateOrCreate(
            ['username' => $pjUser1],
            [
                'nama'    => $pjName1,
                'password'=> $pjPass1,             // atau Hash::make($pjPass)
                'role'    => 'Penanggung Jawab',
                'bagian'  => 'Operasional',
            ]
        );

        // Penanggung Jawab (PJ2)
        User::updateOrCreate(
            ['username' => $pjUser2],
            [
                'nama'    => $pjName2,
                'password'=> $pjPass2,             // atau Hash::make($pjPass)
                'role'    => 'Penanggung Jawab',
                'bagian'  => 'Operasional',
            ]
        );

        // Penanggung Jawab (PJ3)
        User::updateOrCreate(
            ['username' => $pjUser3],
            [
                'nama'    => $pjName3,
                'password'=> $pjPass3,             // atau Hash::make($pjPass)
                'role'    => 'Penanggung Jawab',
                'bagian'  => 'Operasional',
            ]
        );

        // Penanggung Jawab (PJ4)
        User::updateOrCreate(
            ['username' => $pjUser4],
            [
                'nama'    => $pjName4,
                'password'=> $pjPass4,             // atau Hash::make($pjPass)
                'role'    => 'Penanggung Jawab',
                'bagian'  => 'Operasional',
            ]
        );
    }
}
