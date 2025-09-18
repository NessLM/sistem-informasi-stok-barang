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
        $daftarGudang = [
            'Gudang Utama',
            'Gudang ATK',
            'Gudang Listrik',
            'Gudang Kebersihan',
            'Gudang B Komputer'
        ];

        foreach ($daftarGudang as $namaGudang) {
            $gudang = Gudang::firstOrCreate(['nama' => $namaGudang]);

            // beberapa kategori per gudang
            $kategoriList = [
                'Elektronik',
                'Peralatan',
                'Bahan Habis Pakai',
                'Furnitur'
            ];

            foreach ($kategoriList as $kategoriNama) {
                $kategori = Kategori::firstOrCreate([
                    'nama' => $kategoriNama,
                    'gudang_id' => $gudang->id
                ]);

                // jenis barang untuk setiap kategori
                $jenisList = [
                    'Laptop',
                    'Printer',
                    'Kabel',
                    'Meja',
                    'Kursi'
                ];

                foreach ($jenisList as $jenisNama) {
                    $jenisBarang = JenisBarang::firstOrCreate([
                        'nama' => $jenisNama,
                        'kategori_id' => $kategori->id
                    ]);

                    // barang contoh per jenis barang
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
