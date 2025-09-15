<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

// === [BARU] Import utk cache plaintext permanen ===
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

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

        // Helper [DIUBAH]: simpan plaintext ke cache (terenkripsi) PERMANEN
        $putPlain = function (User $u, string $plain) {
            $key = "user:plainpwd:{$u->id}";
            Cache::forever($key, Crypt::encryptString($plain)); // ⬅️ permanen
        };

        // Admin
        $u = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama'     => 'Administrator',
                'password' => 'admin-1234',   // casts 'hashed' → auto-hash saat save
                'role_id'  => $adminRole,
                'bagian'   => 'Umum',
            ]
        );
        $putPlain($u, 'admin-1234'); // [BARU]

        // Pengelola Barang
        $u = User::updateOrCreate(
            ['username' => 'pb'],
            [
                'nama'     => 'Pengelola Barang',
                'password' => 'pb-1234',
                'role_id'  => $pbRole,
                'bagian'   => 'Gudang',
            ]
        );
        $putPlain($u, 'pb-1234'); // [BARU]

        // PJ ATK
        $u = User::updateOrCreate(
            ['username' => 'PJ-ATK'],
            [
                'nama'     => 'Penanggung Jawab ATK',
                'password' => 'atk-1234',
                'role_id'  => $atkRole,
                'bagian'   => 'Operasional',
            ]
        );
        $putPlain($u, 'atk-1234'); // [BARU]

        // PJ Kebersihan
        $u = User::updateOrCreate(
            ['username' => 'PJ-Kebersihan'],
            [
                'nama'     => 'Penanggung Jawab Kebersihan',
                'password' => 'kebersihan-1234',
                'role_id'  => $cleanRole,
                'bagian'   => 'Operasional',
            ]
        );
        $putPlain($u, 'kebersihan-1234'); // [BARU]

        // PJ Listrik
        $u = User::updateOrCreate(
            ['username' => 'PJ-Listrik'],
            [
                'nama'     => 'Penanggung Jawab Listrik',
                'password' => 'listrik-1234',
                'role_id'  => $elecRole,
                'bagian'   => 'Operasional',
            ]
        );
        $putPlain($u, 'listrik-1234'); // [BARU]

        // PJ Bahan Komputer
        $u = User::updateOrCreate(
            ['username' => 'PJ-Bahan_Komputer'],
            [
                'nama'     => 'Penanggung Jawab Bahan Komputer',
                'password' => 'komputer-1234',
                'role_id'  => $compRole,
                'bagian'   => 'Operasional',
            ]
        );
        $putPlain($u, 'komputer-1234'); // [BARU]
    }
}
