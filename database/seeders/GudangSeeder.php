<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\Kategori;
// REMOVED: use App\Models\JenisBarang;
use App\Models\Barang;
use App\Models\PbStok;
use App\Models\PjStok;

class GudangSeeder extends Seeder
{
    public function run(): void
    {
        // Daftar kategori per gudang
        $gudangData = [
            'Gudang Utama' => [], // nanti diisi semua kategori
            'Gudang ATK' => [
                'Alat Tulis',
                'Kertas & Buku',
                'Aksesoris Kantor',
            ],
            'Gudang Listrik' => [
                'Kabel & Stopkontak',
                'Lampu & Perlengkapan',
                'Peralatan Instalasi',
            ],
            'Gudang Kebersihan' => [
                'Alat Kebersihan',
                'Bahan Pembersih',
                'Perlengkapan Sanitasi',
            ],
            'Gudang B Komputer' => [
                'Perangkat Keras',
                'Aksesoris Komputer',
                'Jaringan & Server',
            ],
        ];

        // Gabungkan semua kategori selain Gudang Utama
        $allKategori = collect($gudangData)
            ->except('Gudang Utama')
            ->flatten()
            ->unique()
            ->values()
            ->toArray();

        // Masukkan semua kategori gabungan ke Gudang Utama
        $gudangData['Gudang Utama'] = $allKategori;

        // Jenis barang per kategori (sekarang langsung untuk barang, bukan jenis)
        $jenisPerKategori = [
            'Alat Tulis' => ['Pulpen', 'Pensil', 'Spidol'],
            'Kertas & Buku' => ['Kertas A4', 'Buku Catatan', 'Map Dokumen'],
            'Aksesoris Kantor' => ['Binder', 'Clip', 'Lakban'],

            'Kabel & Stopkontak' => ['Kabel Listrik', 'Stopkontak', 'Colokan'],
            'Lampu & Perlengkapan' => ['Lampu LED', 'Bohlam', 'Lampu TL'],
            'Peralatan Instalasi' => ['Obeng', 'Tang', 'Tespen'],

            'Alat Kebersihan' => ['Sapu', 'Pel', 'Lap'],
            'Bahan Pembersih' => ['Sabun', 'Detergen', 'Cairan Pembersih'],
            'Perlengkapan Sanitasi' => ['Ember', 'Tempat Sampah', 'Sarung Tangan'],

            'Perangkat Keras' => ['Motherboard', 'Processor', 'RAM'],
            'Aksesoris Komputer' => ['Mouse', 'Keyboard', 'Headset'],
            'Jaringan & Server' => ['Router', 'Switch', 'Kabel LAN'],
        ];

        // Fungsi untuk menentukan satuan yang sesuai
        $getSatuan = function ($namaBarang) {
            $nama = strtolower($namaBarang);

            return match (true) {
                str_contains($nama, 'pulpen'),
                str_contains($nama, 'pensil'),
                str_contains($nama, 'spidol'),
                str_contains($nama, 'mouse'),
                str_contains($nama, 'keyboard'),
                str_contains($nama, 'headset'),
                str_contains($nama, 'bohlam'),
                str_contains($nama, 'lampu'),
                str_contains($nama, 'obeng'),
                str_contains($nama, 'tang'),
                str_contains($nama, 'tespen'),
                str_contains($nama, 'colokan'),
                str_contains($nama, 'stopkontak'),
                str_contains($nama, 'router'),
                str_contains($nama, 'switch'),
                str_contains($nama, 'ram') => 'Pcs',

                str_contains($nama, 'kertas'),
                str_contains($nama, 'buku'),
                str_contains($nama, 'map'),
                str_contains($nama, 'clip'),
                str_contains($nama, 'lakban'),
                str_contains($nama, 'binder') => 'Pack',

                str_contains($nama, 'sabun'),
                str_contains($nama, 'detergen'),
                str_contains($nama, 'cairan') => 'Box',

                str_contains($nama, 'kabel'),
                str_contains($nama, 'jaringan'),
                str_contains($nama, 'server') => 'Rim',

                str_contains($nama, 'sapu'),
                str_contains($nama, 'pel'),
                str_contains($nama, 'lap'),
                str_contains($nama, 'ember'),
                str_contains($nama, 'sampah'),
                str_contains($nama, 'sarung tangan'),
                str_contains($nama, 'motherboard'),
                str_contains($nama, 'processor') => 'Unit',

                default => 'Pcs',
            };
        };

        // Proses seeding
        foreach ($gudangData as $namaGudang => $kategoriList) {
            $gudang = Gudang::firstOrCreate(['nama' => $namaGudang]);

            foreach ($kategoriList as $kategoriNama) {
                $kategori = Kategori::firstOrCreate([
                    'nama' => $kategoriNama,
                    'gudang_id' => $gudang->id,
                ]);

                $jenisList = $jenisPerKategori[$kategoriNama] ?? [];

                foreach ($jenisList as $jenisNama) {
                    // REMOVED: JenisBarang creation - langsung buat barang saja

                    for ($i = 1; $i <= 3; $i++) {
                        $namaBarang = $jenisNama . ' ' . $i;
                        $satuan = $getSatuan($namaBarang);
                        $kodeBarang = strtoupper(substr($jenisNama, 0, 2)) . str_pad($i, 3, '0', STR_PAD_LEFT);

                        // Create barang tanpa referensi ke jenis_barang
                        $barang = Barang::firstOrCreate(
                            ['kode_barang' => $kodeBarang],
                            [
                                'nama_barang' => $namaBarang,
                                'id_kategori' => $kategori->id,
                                'harga_barang' => rand(100000, 5000000),
                                'satuan' => $satuan,
                            ]
                        );

                        // Create initial stock for PB (Pengelola Barang)
                        PbStok::firstOrCreate(
                            ['kode_barang' => $barang->kode_barang],
                            ['stok' => rand(5, 200)]
                        );

                        // Create initial stock for PJ (Penanggung Jawab) per gudang
                        PjStok::firstOrCreate(
                            [
                                'kode_barang' => $barang->kode_barang,
                                'id_gudang' => $gudang->id,
                            ],
                            [
                                'id_kategori' => $kategori->id,
                                'stok' => rand(0, 50),
                            ]
                        );
                    }
                }
            }
        }
    }
}