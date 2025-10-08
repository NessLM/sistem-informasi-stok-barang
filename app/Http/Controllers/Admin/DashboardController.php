<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\RiwayatBarang;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::adminMenu();

        // Ringkasan (default: semua gudang)
        $totalJenisBarang = JenisBarang::count();
        $totalBarang = Barang::sum('stok');

        // Data gudang untuk dropdown filter
        $gudangs = Gudang::orderBy('nama')->get();

        /* =========================================================
         * GRAFIK PER BAGIAN (KATEGORI TUJUAN) - HANYA KELUAR
         * =========================================================
         * Dari riwayat_barang:
         * - jenis_transaksi IN ('keluar', 'distribusi')
         * - Grouping by kategori_tujuan->nama (sebagai "bagian")
         * - Exclude "Umum" jika ada
         */
        $bagianRows = RiwayatBarang::select('kategori_tujuan_id', DB::raw('SUM(jumlah) AS total'))
            ->whereIn('jenis_transaksi', ['keluar', 'distribusi'])
            ->whereNotNull('kategori_tujuan_id')
            ->groupBy('kategori_tujuan_id')
            ->with('kategoriTujuan')
            ->get();

        // Map ke nama kategori dan exclude "Umum"
        $bagianData = $bagianRows->map(function ($row) {
            $namaKategori = optional($row->kategoriTujuan)->nama ?? 'Tidak Diketahui';
            return [
                'bagian' => $namaKategori,
                'total' => (int) $row->total
            ];
        })
            ->filter(function ($item) {
                // Exclude "Umum" (case-insensitive)
                return !preg_match('/umum/i', $item['bagian']);
            })
            ->sortBy('bagian') // Sort alfabetis
            ->values();

        // Extract labels dan data
        $bagianLabels = $bagianData->pluck('bagian')->values();
        $keluarData = $bagianData->pluck('total')->values();

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN (DALAM TOTAL HARGA)
         * =========================================================
         * Perhitungan: SUM(riwayat_barang.jumlah * barang.harga)
         * Dari riwayat_barang dengan jenis_transaksi = 'distribusi'
         */
        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $pengeluaranLabels = $years;

        $colorsForYears = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear = [];

        foreach ($years as $y) {
            $totalHarga = RiwayatBarang::where('riwayat_barang.jenis_transaksi', 'distribusi')
                ->join('barang', 'riwayat_barang.barang_id', '=', 'barang.id')
                ->whereYear('riwayat_barang.tanggal', $y)
                ->sum(DB::raw('riwayat_barang.jumlah * barang.harga'));

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

        return view('staff.admin.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'gudangs',
            'bagianLabels',
            'keluarData',
            'pengeluaranLabels',
            'pengeluaranData',
            'years',
            'colorsForYears'
        ));
    }

    /* ==================== AJAX FILTER ==================== */
    public function filterData(Request $request)
    {
        $type = $request->query('type', 'bagian');
        $filter = $request->query('filter', 'all');

        if ($type === 'ringkasan') {
            return $this->filterRingkasanData($filter);
        }

        return $type === 'bagian'
            ? $this->filterBagianData($filter)
            : $this->filterPengeluaranData($filter);
    }

    /**
     * Filter Ringkasan berdasarkan gudang
     * Tetap menggunakan Barang dan JenisBarang (master data)
     */
    private function filterRingkasanData($gudangFilter)
    {
        if ($gudangFilter === 'all') {
            // Semua gudang
            $totalJenisBarang = JenisBarang::count();
            $totalBarang = Barang::sum('stok');
        } else {
            // Filter berdasarkan gudang tertentu
            $gudang = Gudang::where('nama', $gudangFilter)->first();

            if (!$gudang) {
                return response()->json([
                    'totalJenisBarang' => 0,
                    'totalBarang' => 0
                ]);
            }

            // Hitung total jenis barang berdasarkan gudang
            $totalJenisBarang = JenisBarang::whereHas('kategori', function ($query) use ($gudang) {
                $query->where('gudang_id', $gudang->id);
            })->count();

            // Hitung total barang berdasarkan gudang
            $totalBarang = Barang::whereHas('kategori', function ($query) use ($gudang) {
                $query->where('gudang_id', $gudang->id);
            })->sum('stok');
        }

        return response()->json([
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    /**
     * Filter Bagian berdasarkan rentang waktu
     * Dari riwayat_barang dengan kategori_tujuan sebagai "bagian"
     */
    private function filterBagianData($filter)
    {
        // Ambil semua kategori yang pernah jadi tujuan (baseline labels)
        $allTimeKategori = RiwayatBarang::whereIn('jenis_transaksi', ['keluar', 'distribusi'])
            ->whereNotNull('kategori_tujuan_id')
            ->select('kategori_tujuan_id')
            ->groupBy('kategori_tujuan_id')
            ->with('kategoriTujuan')
            ->get()
            ->map(fn($row) => optional($row->kategoriTujuan)->nama ?? 'Tidak Diketahui')
            ->filter(fn($nama) => !preg_match('/umum/i', $nama))
            ->unique()
            ->sort()
            ->values();

        // Query dengan filter waktu
        $q = RiwayatBarang::whereIn('jenis_transaksi', ['keluar', 'distribusi'])
            ->whereNotNull('kategori_tujuan_id');

        $start = null;
        $end = null;

        if ($filter === 'week') {
            $start = Carbon::now()->subWeek();
            $q->where('tanggal', '>=', $start);
        } elseif ($filter === 'month') {
            $start = Carbon::now()->subMonth();
            $q->where('tanggal', '>=', $start);
        } elseif ($filter === 'year') {
            $start = Carbon::now()->subYear();
            $q->where('tanggal', '>=', $start);
        }
        $end = Carbon::now();

        // Hitung total per kategori
        $keluarData = [];
        foreach ($allTimeKategori as $kategoriNama) {
            // Cari kategori_id dari nama
            $kategoriIds = \App\Models\Kategori::where('nama', $kategoriNama)
                ->pluck('id')
                ->toArray();

            $total = (clone $q)
                ->whereIn('kategori_tujuan_id', $kategoriIds)
                ->sum('jumlah');

            $keluarData[] = (int) $total;
        }

        return response()->json([
            'labels' => $allTimeKategori,
            'keluar' => $keluarData,
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    /**
     * Filter Pengeluaran per Tahun (dalam total harga)
     * Dari riwayat_barang
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
            // Hitung total harga: SUM(jumlah * harga)
            $totalHarga = RiwayatBarang::where('riwayat_barang.jenis_transaksi', 'distribusi')
                ->join('barang', 'riwayat_barang.barang_id', '=', 'barang.id')
                ->whereYear('riwayat_barang.tanggal', $y)
                ->sum(DB::raw('riwayat_barang.jumlah * barang.harga'));

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
     * Generate warna untuk tahun
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