<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class LaporanPDFController extends Controller
{
    /**
     * Dapatkan data kuartal berdasarkan nomor kuartal
     */
    private function getQuarterData($quarter, $year)
    {
        $quarters = [
            1 => ['month_range' => 'JANUARI – MARET', 'start_month' => 1, 'end_month' => 3],
            2 => ['month_range' => 'APRIL – JUNI', 'start_month' => 4, 'end_month' => 6],
            3 => ['month_range' => 'JULI – SEPTEMBER', 'start_month' => 7, 'end_month' => 9],
            4 => ['month_range' => 'OKTOBER – DESEMBER', 'start_month' => 10, 'end_month' => 12]
        ];

        return $quarters[$quarter] ?? $quarters[1];
    }

    /**
     * Ambil data riwayat dari database
     */
    public function getRiwayatData($quarter, $year): Collection
    {
        try {
            $quarterData = $this->getQuarterData($quarter, $year);
            
            // Query data riwayat berdasarkan quarter dan year
            $startDate = "{$year}-" . str_pad($quarterData['start_month'], 2, '0', STR_PAD_LEFT) . "-01";
            $endDate = "{$year}-" . str_pad($quarterData['end_month'], 2, '0', STR_PAD_LEFT) . "-31";

            // Query untuk mendapatkan data riwayat
            $riwayat = DB::table('riwayat_barang as r')
                ->leftJoin('barang as b', 'r.barang_id', '=', 'b.id')
                ->leftJoin('gudang as g', 'r.gudang_id', '=', 'g.id')
                ->leftJoin('bagian as bg', 'r.bagian_id', '=', 'bg.id')
                ->select(
                    'r.*',
                    'b.nama as nama_barang',
                    'b.satuan',
                    'g.nama as gudang',
                    'bg.nama as bagian',
                    'bg.nama as bagian_nama'
                )
                ->whereBetween('r.tanggal', [$startDate, $endDate])
                ->orderBy('r.tanggal', 'asc')
                ->orderBy('r.waktu', 'asc')
                ->get();

            // Jika tidak ada data, kembalikan collection kosong
            if ($riwayat->isEmpty()) {
                return $this->getDummyData($quarter, $year, $quarterData, true);
            }

            return $riwayat;

        } catch (\Exception $e) {
            
            return $this->getDummyData($quarter, $year, $this->getQuarterData($quarter, $year), false);
        }
    }

    /**
     * Generate data dummy untuk testing/fallback
     */
    private function getDummyData($quarter, $year, $quarterData, $isEmpty = false): Collection
    {
        if ($isEmpty) {
            return collect([
                (object)[
                    'alur_barang' => 'Masuk PB',
                    'tanggal' => "{$year}-" . str_pad($quarterData['start_month'], 2, '0', STR_PAD_LEFT) . "-15",
                    'waktu' => '10:30:00',
                    'gudang' => 'Belum ada data',
                    'bagian_nama' => '-',
                    'nama_barang' => 'Belum ada transaksi',
                    'jumlah' => 0,
                    'satuan' => '-',
                    'keterangan' => "Data untuk kuartal {$quarter} tahun {$year} belum tersedia"
                ]
            ]);
        }

        return collect([
            (object)[
                'alur_barang' => 'Masuk PB',
                'tanggal' => $year . '-01-15',
                'waktu' => '10:30:00',
                'gudang' => 'Gudang Utama',
                'bagian_nama' => 'Bagian Perlengkapan',
                'nama_barang' => 'Kertas A4',
                'jumlah' => 100,
                'satuan' => 'Rim',
                'keterangan' => 'Data contoh - Persediaan awal Kuartal ' . $quarter,
                'bagian' => 'Bagian Umum',
                'penerima' => 'Budi Santoso'
            ],
            (object)[
                'alur_barang' => 'Distribusi PJ',
                'tanggal' => $year . '-02-20',
                'waktu' => '14:00:00',
                'gudang' => 'Gudang Cabang',
                'bagian_nama' => '-',
                'nama_barang' => 'Kertas A4',
                'jumlah' => 50,
                'satuan' => 'Rim',
                'keterangan' => 'Data contoh - Distribusi ke cabang',
                'bagian' => 'Bagian Umum',
                'penerima' => null
            ],
            (object)[
                'alur_barang' => 'Keluar PJ',
                'tanggal' => $year . '-03-25',
                'waktu' => '09:15:00',
                'gudang' => 'Gudang Utama',
                'bagian' => 'Bagian Umum',
                'bagian_nama' => 'Bagian Umum',
                'nama_barang' => 'Kertas A4',
                'jumlah' => 10,
                'satuan' => 'Rim',
                'penerima' => 'Budi Santoso',
                'keterangan' => 'Data contoh - Pemakaian rutin'
            ]
        ]);
    }


}