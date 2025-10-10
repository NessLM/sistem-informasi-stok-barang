<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Gudang;
use App\Models\Bagian;
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

        // Get Gudang IDs
        $gudangATK = Gudang::where('nama', 'LIKE', '%ATK%')->first();
        $gudangKebersihan = Gudang::where('nama', 'LIKE', '%Kebersihan%')->first();
        $gudangListrik = Gudang::where('nama', 'LIKE', '%Listrik%')->first();
        $gudangKomputer = Gudang::where('nama', 'LIKE', '%Komputer%')->orWhere('nama', 'LIKE', '%Bahan Komputer%')->first();

        // Get Bagian IDs (atau buat jika belum ada)
        $bagianUmum = Bagian::firstOrCreate(['nama' => 'Umum']);
        $bagianGudang = Bagian::firstOrCreate(['nama' => 'Gudang']);
        $bagianOperasional = Bagian::firstOrCreate(['nama' => 'Operasional']);

        // Helper: simpan plaintext ke cache (terenkripsi) PERMANEN
        $putPlain = function (User $u, string $plain) {
            $key = "user:plainpwd:{$u->id}";
            Cache::forever($key, Crypt::encryptString($plain));
        };

        // Admin
        $u = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama'     => 'Administrator',
                'password' => 'admin-1234',
                'role_id'  => $adminRole,
                'bagian_id' => $bagianUmum->id, // UBAH dari 'bagian' ke 'bagian_id'
                'gudang_id' => null,
            ]
        );
        $putPlain($u, 'admin-1234');

        // Pengelola Barang
        $u = User::updateOrCreate(
            ['username' => 'pb'],
            [
                'nama'     => 'Pengelola Barang',
                'password' => 'pb-1234',
                'role_id'  => $pbRole,
                'bagian_id' => $bagianGudang->id, // UBAH dari 'bagian' ke 'bagian_id'
                'gudang_id' => 1, // Gudang Utama
            ]
        );
        $putPlain($u, 'pb-1234');

        // PJ ATK
        $u = User::updateOrCreate(
            ['username' => 'PJ-ATK'],
            [
                'nama'     => 'Penanggung Jawab ATK',
                'password' => 'atk-1234',
                'role_id'  => $atkRole,
                'bagian_id' => $bagianOperasional->id, // UBAH dari 'bagian' ke 'bagian_id'
                'gudang_id' => $gudangATK ? $gudangATK->id : 2,
            ]
        );
        $putPlain($u, 'atk-1234');

        // PJ Kebersihan
        $u = User::updateOrCreate(
            ['username' => 'PJ-Kebersihan'],
            [
                'nama'     => 'Penanggung Jawab Kebersihan',
                'password' => 'kebersihan-1234',
                'role_id'  => $cleanRole,
                'bagian_id' => $bagianOperasional->id, // UBAH dari 'bagian' ke 'bagian_id'
                'gudang_id' => $gudangKebersihan ? $gudangKebersihan->id : 4,
            ]
        );
        $putPlain($u, 'kebersihan-1234');

        // PJ Listrik
        $u = User::updateOrCreate(
            ['username' => 'PJ-Listrik'],
            [
                'nama'     => 'Penanggung Jawab Listrik',
                'password' => 'listrik-1234',
                'role_id'  => $elecRole,
                'bagian_id' => $bagianOperasional->id, // UBAH dari 'bagian' ke 'bagian_id'
                'gudang_id' => $gudangListrik ? $gudangListrik->id : 5,
            ]
        );
        $putPlain($u, 'listrik-1234');

        // PJ Bahan Komputer
        $u = User::updateOrCreate(
            ['username' => 'PJ-Bahan_Komputer'],
            [
                'nama'     => 'Penanggung Jawab Bahan Komputer',
                'password' => 'komputer-1234',
                'role_id'  => $compRole,
                'bagian_id' => $bagianOperasional->id, // UBAH dari 'bagian' ke 'bagian_id'
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'komputer-1234');
    }
}