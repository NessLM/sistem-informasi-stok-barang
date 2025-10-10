<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\JenisBarang;
use App\Models\Barang;

class GudangSeeder extends Seeder
{
    public function run(): void
    {
        // Daftar gudang dan kategori yang unik untuk masing-masing
        $gudangData = [
            'Gudang Utama' => [
                'Elektronik',
                'Peralatan Kantor',
                'Furnitur',
            ],
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

        // Daftar jenis barang contoh per kategori
        $jenisPerKategori = [
            'Elektronik' => ['Laptop', 'Printer', 'Monitor'],
            'Peralatan Kantor' => ['Stapler', 'Gunting', 'Penggaris'],
            'Furnitur' => ['Meja', 'Kursi', 'Lemari'],

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

        // Proses seeding
        foreach ($gudangData as $namaGudang => $kategoriList) {
            $gudang = Gudang::firstOrCreate(['nama' => $namaGudang]);

            foreach ($kategoriList as $kategoriNama) {
                $kategori = Kategori::firstOrCreate([
                    'nama' => $kategoriNama,
                    'gudang_id' => $gudang->id
                ]);

                $jenisList = $jenisPerKategori[$kategoriNama] ?? [];

                foreach ($jenisList as $jenisNama) {
                    $jenisBarang = JenisBarang::firstOrCreate([
                        'nama' => $jenisNama,
                        'kategori_id' => $kategori->id
                    ]);

                    // Barang contoh per jenis barang
                    for ($i = 1; $i <= 3; $i++) {
                        Barang::firstOrCreate(
                            [
                                'kode' => strtoupper(substr($jenisNama, 0, 2)) . str_pad($i, 3, '0', STR_PAD_LEFT)
                            ],
                            [
                                'nama' => $jenisNama . ' ' . $i,
                                'kategori_id' => $kategori->id,
                                'jenis_barang_id' => $jenisBarang->id,
                                'jumlah' => rand(5, 20),
                                'stok' => rand(5, 20),
                                'harga' => rand(100000, 5000000),
                                'satuan' => 'unit'
                            ]
                        );
                    }
                }
            }
        }
    }
}
