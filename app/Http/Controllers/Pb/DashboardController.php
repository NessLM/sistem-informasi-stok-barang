<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Gudang;
use App\Models\Kategori;
use App\Models\PbStok;
use App\Models\PjStok;
use App\Models\TransaksiDistribusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::pbMenu();

        // Ringkasan (default: semua data) - FIXED: Ambil dari pb_stok
        $totalJenisBarang = $this->hitungJenisBarangGudangUtama();
        $totalBarang = PbStok::sum('stok'); // Stok Pengelola Barang

        /* =========================================================
         * GRAFIK BARANG KELUAR PER KATEGORI (DISTRIBUSI KE GUDANG)
         * =========================================================
         * Menampilkan total distribusi barang ke setiap gudang
         */

        $kategorMap = [
            'Gudang Utama' => 'G. Utama',
            'Gudang ATK' => 'G. ATK',
            'Gudang Listrik' => 'G. Listrik',
            'Gudang Kebersihan' => 'G. Kebersihan',
            'Gudang B Komputer' => 'G.B. Komputer'
        ];

        $keluarPerKategoriLabels = [];
        $keluarPerKategoriData = [];

        foreach ($kategorMap as $gudangName => $kategoriLabel) {
            $gudang = Gudang::where('nama', $gudangName)->first();

            if ($gudang) {
                // Total distribusi ke gudang ini
                $total = TransaksiDistribusi::where('id_gudang_tujuan', $gudang->id)
                    ->sum('jumlah');
            } else {
                $total = 0;
            }

            $keluarPerKategoriLabels[] = $kategoriLabel;
            $keluarPerKategoriData[] = (int) $total;
        }

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN (DALAM TOTAL HARGA)
         * =========================================================
         */
        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $pengeluaranLabels = $years;

        $colorsForYears = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear = [];

        foreach ($years as $y) {
            // Hitung total harga: SUM(transaksi_distribusi.jumlah * barang.harga_barang)
            $totalHarga = TransaksiDistribusi::join('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->whereYear('transaksi_distribusi.tanggal', $y)
                ->sum(DB::raw('transaksi_distribusi.jumlah * barang.harga_barang'));

            $totalsPerYear[] = (float) $totalHarga;
            $c = $this->getColorForYear($y);
            $colorsForYears[$y] = $c;
            $colorsForYearsOrdered[] = $c;
        }

        $pengeluaranData = [
            [
                'label' => 'Keluar',
                'data' => $totalsPerYear,
                'backgroundColor' => $colorsForYearsOrdered,
                'borderRadius' => 4,
            ]
        ];

        return view('staff.pb.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'keluarPerKategoriLabels',
            'keluarPerKategoriData',
            'pengeluaranLabels',
            'pengeluaranData',
            'years',
            'colorsForYears'
        ));
    }

    /* ==================== AJAX FILTER ==================== */

    public function filterData(Request $request)
    {
        $type = $request->query('type', 'kategori');
        $filter = $request->query('filter', 'all');

        // PB ga ada filter ringkasan, cuma kategori & pengeluaran
        if ($type === 'kategori') {
            return $this->filterKategoriData($filter);
        } else {
            return $this->filterPengeluaanData($filter);
        }
    }

    /**
     * Filter data ringkasan berdasarkan gudang
     * FIXED: Menggunakan pb_stok dan pj_stok
     */


    /**
     * Filter data kategori berdasarkan rentang waktu
     * FIXED: Menggunakan transaksi_distribusi
     */
    private function filterKategoriData($filter)
    {
        $kategorMap = [
            'Gudang Utama' => 'G. Utama',
            'Gudang ATK' => 'G. ATK',
            'Gudang Listrik' => 'G. Listrik',
            'Gudang Kebersihan' => 'G. Kebersihan',
            'Gudang B Komputer' => 'G.B. Komputer'
        ];

        $start = null;
        $end = null;

        // Filter berdasarkan waktu
        if ($filter === 'week') {
            $start = Carbon::now()->subWeek();
        } elseif ($filter === 'month') {
            $start = Carbon::now()->subMonth();
        } elseif ($filter === 'year') {
            $start = Carbon::now()->subYear();
        }
        $end = Carbon::now();

        $labels = [];
        $data = [];

        foreach ($kategorMap as $gudangName => $kategoriLabel) {
            $gudang = Gudang::where('nama', $gudangName)->first();

            if ($gudang) {
                // Query distribusi ke gudang ini
                $query = TransaksiDistribusi::where('id_gudang_tujuan', $gudang->id);

                // Tambahkan filter tanggal jika ada
                if ($start) {
                    $query->where('tanggal', '>=', $start);
                }

                $total = $query->sum('jumlah');
            } else {
                $total = 0;
            }

            $labels[] = $kategoriLabel;
            $data[] = (int) $total;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'colors' => [
                $this->getColorForKategori('Utama'),
                $this->getColorForKategori('ATK'),
                $this->getColorForKategori('Listrik'),
                $this->getColorForKategori('Kebersihan'),
                $this->getColorForKategori('Komputer')
            ],
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    /**
     * Filter data pengeluaran per tahun (dalam total harga)
     * FIXED: Menggunakan transaksi_distribusi
     */
    private function filterPengeluaranData($filter)
    {
        $currentYear = (int) date('Y');

        if ($filter === '5y')
            $years = range($currentYear - 4, $currentYear);
        elseif ($filter === '7y')
            $years = range($currentYear - 6, $currentYear);
        elseif ($filter === '10y')
            $years = range($currentYear - 10, $currentYear);
        else
            $years = range($currentYear - 9, $currentYear);

        $totals = [];
        $colors = [];

        foreach ($years as $y) {
            // Hitung total harga: SUM(jumlah * harga_barang)
            $totalHarga = TransaksiDistribusi::join('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->whereYear('transaksi_distribusi.tanggal', $y)
                ->sum(DB::raw('transaksi_distribusi.jumlah * barang.harga_barang'));

            $totals[] = (float) $totalHarga;
            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data' => $totals,
            'colors' => $colors,
        ]);
    }


    /**
     * Hitung jenis barang unik dari Gudang Utama (pb_stok)
     * Logika: ambil dari pb_stok → join ke barang → extract nama unik
     */
    private function hitungJenisBarangGudangUtama()
    {
        // Ambil kode_barang dari pb_stok yang stoknya > 0
        $kodeBarangList = PbStok::where('stok', '>', 0)
            ->pluck('kode_barang');

        // Kalau ga ada barang, return 0
        if ($kodeBarangList->isEmpty()) {
            return 0;
        }

        // Ambil nama barang dari kode_barang
        $namaBarangList = Barang::whereIn('kode_barang', $kodeBarangList)
            ->pluck('nama_barang');

        // Extract nama dasar (hilangkan angka di belakang)
        $namaUnik = $namaBarangList->map(function ($nama) {
            // Trim whitespace dulu
            $nama = trim($nama);

            // Hilangkan angka di akhir: "Pulpen 1" → "Pulpen"
            $namaBase = preg_replace('/\s+\d+$/', '', $nama);

            // Kalau kosong, return nama asli
            return empty($namaBase) ? $nama : $namaBase;
        })->unique()->filter();

        return $namaUnik->count();
    }
    /**
     * Warna untuk setiap kategori
     */
    private function getColorForKategori($kategori)
    {
        $colors = [
            'Utama' => '#6366F1',      // Indigo
            'ATK' => '#3B82F6',        // Biru
            'Listrik' => '#F59E0B',    // Kuning/Orange
            'Kebersihan' => '#10B981', // Hijau
            'Komputer' => '#8B5CF6'    // Ungu
        ];

        return $colors[$kategori] ?? '#6B7280';
    }

    /**
     * Warna untuk tahun
     */
    private function getColorForYear($year)
    {
        $palette = ['#8B5CF6', '#F87171', '#06B6D4', '#10B981', '#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int) $year) % count($palette);
        if ($idx < 0)
            $idx += count($palette);
        return $palette[$idx];
    }
}