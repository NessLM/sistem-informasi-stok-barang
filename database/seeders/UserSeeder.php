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
        // Get Role IDs - HANYA 3 ROLE
        $adminRole = Role::where('nama', 'Admin')->first()->id;
        $pbpenggunaRole = Role::where('nama', 'Pengurus Barang Pengguna')->first()->id;
        $pbpembantuRole = Role::where('nama', 'Pengurus Barang Pembantu')->first()->id;

        // Get Gudang IDs (jika ada)
        $gudangATK = Gudang::where('nama', 'LIKE', '%ATK%')->first();
        $gudangKebersihan = Gudang::where('nama', 'LIKE', '%Kebersihan%')->first();
        $gudangListrik = Gudang::where('nama', 'LIKE', '%Listrik%')->first();
        $gudangKomputer = Gudang::where('nama', 'LIKE', '%Komputer%')->orWhere('nama', 'LIKE', '%Bahan Komputer%')->first();

        // Get Bagian IDs (atau buat jika belum ada)
        $bagianGudang = Bagian::firstOrCreate(['nama' => 'Gudang']);
        $bagianTataPemerintahan = Bagian::firstOrCreate(['nama' => 'Tata Pemerintahan']);
        $bagianKesradanKemasyarakatan = Bagian::firstOrCreate(['nama' => 'Kesejahteraan Rakyat & Kemasyarakatan']);
        $bagianHukumdanHAM = Bagian::firstOrCreate(['nama' => 'Hukum & HAM']);
        $bagianPerekonomian = Bagian::firstOrCreate(['nama' => 'Perekonomian']);
        $bagianADMPembangunan = Bagian::firstOrCreate(['nama' => 'ADM Pembangunan']);
        $bagianADMPelayananPengadaanBarangdanJasa = Bagian::firstOrCreate(['nama' => 'ADM Pelayanan Pengadaan Barang & Jasa (LPSE)']);
        $bagianProtokol = Bagian::firstOrCreate(['nama' => 'Protokol']);
        $bagianOrganisasi = Bagian::firstOrCreate(['nama' => 'Organisasi']);
        $bagianUmum = Bagian::firstOrCreate(['nama' => 'Umum & Rumah Tangga']);
        $bagianPerencanaandanKeuangan = Bagian::firstOrCreate(['nama' => 'Perencanaan & Keuangan']);

        
        // Helper: simpan plaintext ke cache (terenkripsi) PERMANEN
        $putPlain = function (User $u, string $plain) {
            $key = "user:plainpwd:{$u->id}";
            Cache::forever($key, Crypt::encryptString($plain));
        };

        // 1. Admin
        $u = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'nama'     => 'Hiskawati, S.AP',
                'password' => 'admin-1234',
                'role_id'  => $adminRole,
                'bagian_id' => $bagianPerencanaandanKeuangan->id,
                'gudang_id' => null,
            ]
        );
        $putPlain($u, 'admin-1234');

        // 2. Pengurus Barang Pengguna
        $u = User::updateOrCreate(
            ['username' => 'pbp'],
            [
                'nama'     => 'Redha Efrida, A.Md',
                'password' => 'pbp-1234',
                'role_id'  => $pbpenggunaRole,
                'bagian_id' => $bagianOrganisasi->id,
                'gudang_id' => 1, // Gudang Utama
            ]
        );
        $putPlain($u, 'pbp-1234');

        // 3. PBP Bagian Tata Pemerintahan
        $u = User::updateOrCreate(
            ['username' => 'PBP-TataPemerintahan'],
            [
                'nama'     => 'Heni Handayani, SKM',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianTataPemerintahan->id,
                'gudang_id' => $gudangATK ? $gudangATK->id : 2,
            ]
        );
        $putPlain($u, 'user-1234');

        // 4. PBP Bagian Kesra dan Kemasyarakatan
        $u = User::updateOrCreate(
            ['username' => 'PBP-KesradanKemasyarakatan'],
            [
                'nama'     => 'Yuniarti',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianKesradanKemasyarakatan->id,
                'gudang_id' => $gudangKebersihan ? $gudangKebersihan->id : 4,
            ]
        );
        $putPlain($u, 'user-1234');

        // 5. PBP Bagian Hukum dan HAM
        $u = User::updateOrCreate(
            ['username' => 'PBP-HukumdanHAM'],
            [
                'nama'     => 'Sarkani',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianHukumdanHAM->id,
                'gudang_id' => $gudangListrik ? $gudangListrik->id : 5,
            ]
        );
        $putPlain($u, 'user-1234');

        // 6. PBP Bagian Perekonomian
        $u = User::updateOrCreate(
            ['username' => 'PBP-Perekonomian'],
            [
                'nama'     => 'Rindi',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianPerekonomian->id,
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'user-1234');

        // 7. PBP Bagian Adm. Pembangunan
        $u = User::updateOrCreate(
            ['username' => 'PBP-AdmPembangunan'],
            [
                'nama'     => 'Dwi Afriyanti, A.Md',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianADMPembangunan->id,
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'user-1234');

        // 8. PBP Bagian Adm. Pelayanan Pengadaan Barang dan Jasa
        $u = User::updateOrCreate(
            ['username' => 'PBP-AdmPelayananPengadaanBarangdanJasa'],
            [
                'nama'     => 'Dedi Irawan',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianADMPelayananPengadaanBarangdanJasa->id,
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'user-1234');

        // 9. PBP Bagian Protokol
        $u = User::updateOrCreate(
            ['username' => 'PBP-Protokol'],
            [
                'nama'     => 'Anisah, S.Kom',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianProtokol->id,
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'user-1234');

        // 10. PBP Bagian Organisasi
        $u = User::updateOrCreate(
            ['username' => 'PBP-Organisasi'],
            [
                'nama'     => 'Redha Efrida, A.Md',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianOrganisasi->id,
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'user-1234');

        // 11. PBP Bagian Umum dan Rumah Tangga
        $u = User::updateOrCreate(
            ['username' => 'PBP-UmumdanRumahTangga'],
            [
                'nama'     => 'Yerri Kurniawan',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianUmum->id,
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'user-1234');

        // 12. PBP Bagian Perencanaan dan Keuangan
        $u = User::updateOrCreate(
            ['username' => 'PBP-PerencanaandanKeuangan'],
            [
                'nama'     => 'Yulianti, S.TR.IP',
                'password' => 'user-1234',
                'role_id'  => $pbpembantuRole,
                'bagian_id' => $bagianPerencanaandanKeuangan->id,
                'gudang_id' => $gudangKomputer ? $gudangKomputer->id : 6,
            ]
        );
        $putPlain($u, 'user-1234');
    }
}