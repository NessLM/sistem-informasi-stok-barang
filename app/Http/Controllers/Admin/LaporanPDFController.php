<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon; // ⬅️ TAMBAHKAN BARIS INI

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
 * Rekap per kategori untuk tabel "Nilai Harga Persediaan" (halaman 3)
 *
 * - Pemasukan: dari transaksi_barang_masuk (jumlah * harga)
 * - Pengeluaran: dari transaksi_barang_keluar (jumlah * harga)
 * - Stock Opname Terupdate: dari stok_bagian (stok * harga) AKUMULATIF sampai akhir triwulan
 */
public function getRekapKategoriTriwulan(int $quarter, int $year): Collection
{
    $quarterData = $this->getQuarterData($quarter, $year);
    $startMonth  = $quarterData['start_month'];
    $endMonth    = $quarterData['end_month'];

    // Range tanggal triwulan
    $startDate = Carbon::create($year, $startMonth, 1)->startOfDay()->toDateString();
    $endDate   = Carbon::create($year, $endMonth, 1)->endOfMonth()->toDateString();
    
    // Tentukan akhir triwulan untuk filter stok akumulatif
    $endDateTime = Carbon::create($year, $endMonth, 1)->endOfMonth()->endOfDay();

    // === 1) PEMASUKAN (transaksi_barang_masuk) ===
    $pemasukanRaw = DB::table('transaksi_barang_masuk as tm')
        ->join('barang as b', 'tm.kode_barang', '=', 'b.kode_barang')
        ->join('kategori as k', 'b.id_kategori', '=', 'k.id')
        ->selectRaw('
        k.id   as kategori_id,
        k.nama as kategori,
        MONTH(tm.tanggal) as bulan,
        SUM(tm.jumlah * COALESCE(tm.harga, 0)) as total
    ')
        ->whereBetween('tm.tanggal', [$startDate, $endDate])
        ->groupBy('kategori_id', 'kategori', 'bulan')
        ->get();

    // === 2) PENGELUARAN (transaksi_barang_keluar) ===
    $pengeluaranRaw = DB::table('transaksi_barang_keluar as tk')
        ->join('barang as b', 'tk.kode_barang', '=', 'b.kode_barang')
        ->join('kategori as k', 'b.id_kategori', '=', 'k.id')
        ->selectRaw('
        k.id   as kategori_id,
        k.nama as kategori,
        MONTH(tk.tanggal) as bulan,
        SUM(tk.jumlah * COALESCE(tk.harga, 0)) as total
    ')
        ->whereBetween('tk.tanggal', [$startDate, $endDate])
        ->groupBy('kategori_id', 'kategori', 'bulan')
        ->get();

    // === 3) STOCK OPNAME TERUPDATE (stok_bagian) - AKUMULATIF sampai akhir triwulan ===
    $stokRaw = DB::table('stok_bagian as sb')
        ->join('barang as b', 'sb.kode_barang', '=', 'b.kode_barang')
        ->join('kategori as k', 'b.id_kategori', '=', 'k.id')
        ->selectRaw('
        k.id   as kategori_id,
        k.nama as kategori,
        SUM(sb.stok * COALESCE(sb.harga, 0)) as total
    ')
        ->where('sb.stok', '>', 0) // Hanya stok positif
        // Filter: stok yang created_at/updated_at <= akhir triwulan
        ->where(function($query) use ($endDateTime) {
            $query->where('sb.created_at', '<=', $endDateTime)
                  ->orWhere('sb.updated_at', '<=', $endDateTime);
        })
        ->groupBy('kategori_id', 'kategori')
        ->get();

    // === 4) Gabung ke struktur final ===
    $data = [];

    // Helper inisialisasi slot kategori
    $initKategori = function (int $id, string $nama) use (&$data) {
        if (!isset($data[$id])) {
            $data[$id] = [
                'kategori_id'  => $id,
                'kategori'     => $nama,
                'pemasukan'    => ['m1' => 0, 'm2' => 0, 'm3' => 0],
                'pengeluaran'  => ['m1' => 0, 'm2' => 0, 'm3' => 0],
                'stock_opname' => 0,
            ];
        }
    };

    // Map pemasukan per bulan (m1,m2,m3 = bulan ke-1,2,3 di triwulan)
    foreach ($pemasukanRaw as $row) {
        $pos = (int) $row->bulan - $startMonth; // 0,1,2 dalam triwulan
        if ($pos < 0 || $pos > 2) {
            continue;
        }
        $key = 'm' . ($pos + 1);

        $initKategori($row->kategori_id, $row->kategori);
        $data[$row->kategori_id]['pemasukan'][$key] = (float) $row->total;
    }

    // Map pengeluaran per bulan
    foreach ($pengeluaranRaw as $row) {
        $pos = (int) $row->bulan - $startMonth;
        if ($pos < 0 || $pos > 2) {
            continue;
        }
        $key = 'm' . ($pos + 1);

        $initKategori($row->kategori_id, $row->kategori);
        $data[$row->kategori_id]['pengeluaran'][$key] = (float) $row->total;
    }

    // Stock opname terupdate (akumulatif sampai akhir triwulan)
    foreach ($stokRaw as $row) {
        $initKategori($row->kategori_id, $row->kategori);
        $data[$row->kategori_id]['stock_opname'] = (float) $row->total;
    }

    // Kembalikan sebagai collection, diurutkan nama kategori
    return collect($data)
        ->sortBy('kategori')
        ->values();
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



    /**
     * Ambil data stock opname AKUMULATIF per triwulan
     * Mengambil semua stok yang ada sampai triwulan tersebut
     */
    public function getStockOpnameData($quarter, $year): Collection
    {
        try {
            $quarterData = $this->getQuarterData($quarter, $year);
            $endMonth = $quarterData['end_month'];

            // Ambil stok sampai akhir triwulan ini (akumulatif)
            $endDate = Carbon::create($year, $endMonth, 1)->endOfMonth()->endOfDay();

            $data = DB::table('stok_bagian as sb')
                ->join('barang as b', 'sb.kode_barang', '=', 'b.kode_barang')
                ->join('kategori as k', 'b.id_kategori', '=', 'k.id')
                ->select(
                    'k.id as kategori_id',
                    'k.nama as kategori_nama',
                    'b.kode_barang',
                    'b.nama_barang',
                    'b.satuan',
                    'sb.harga',
                    DB::raw('SUM(sb.stok) as total_stok'),
                    DB::raw('SUM(sb.stok * COALESCE(sb.harga, 0)) as total_harga')
                )
                ->where('sb.stok', '>', 0)
                // Filter: stok yang created_at/updated_at <= akhir triwulan
                // (opsional, jika ingin benar-benar sesuai periode)
                ->where(function ($query) use ($endDate) {
                    $query->where('sb.created_at', '<=', $endDate)
                        ->orWhere('sb.updated_at', '<=', $endDate);
                })
                ->groupBy('k.id', 'k.nama', 'b.kode_barang', 'b.nama_barang', 'b.satuan', 'sb.harga')
                ->orderBy('k.nama')
                ->orderBy('b.nama_barang')
                ->orderBy('sb.harga')
                ->get();

            // Sisanya sama seperti sebelumnya...
            if ($data->isEmpty()) {
                return collect();
            }

            $grouped = $data->groupBy('kategori_id');
            $result = collect();

            foreach ($grouped as $kategoriId => $items) {
                $firstItem = $items->first();
                $totalVolume = $items->sum('total_stok');
                $totalHarga = $items->sum('total_harga');

                $result->push((object)[
                    'is_header' => true,
                    'kode_barang' => '',
                    'uraian' => strtoupper($firstItem->kategori_nama),
                    'volume' => $totalVolume,
                    'satuan' => '',
                    'harga' => '',
                    'jumlah_harga' => $totalHarga
                ]);

                foreach ($items as $item) {
                    $result->push((object)[
                        'is_header' => false,
                        'kode_barang' => $item->kode_barang,
                        'uraian' => $item->nama_barang,
                        'volume' => $item->total_stok,
                        'satuan' => $item->satuan,
                        'harga' => $item->harga,
                        'jumlah_harga' => $item->total_harga
                    ]);
                }
            }

            return $result;
        } catch (\Exception $e) {
            return collect();
        }
    }
}
