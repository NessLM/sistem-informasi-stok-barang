<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Riwayat;            // biarkan tetap dipakai bagian lain
use App\Models\RiwayatBarang;      // ⬅️ ditambahkan untuk grafik per tahun (pakai harga)
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // dipakai untuk agregasi (SUM(jumlah*harga))
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::adminMenu();

        // Ringkasan (default: semua gudang)
        $totalJenisBarang = JenisBarang::count();
        $totalBarang      = Barang::sum('stok');

        // Data gudang untuk dropdown filter
        $gudangs = Gudang::orderBy('nama')->get();

        /* =========================================================
         * GRAFIK PER BAGIAN  (HANYA KELUAR, TANPA DEFAULT, EXCLUDE "Umum")
         * =========================================================
         * - Ambil hanya bagian yang benar-benar punya transaksi Keluar
         * - Urut alfabet biar stabil
         * - "Umum" dikecualikan
         */
        $bagianRows = Riwayat::select('bagian', DB::raw('SUM(jumlah) AS total'))
            ->where('alur_barang', 'Keluar')
            ->where('bagian', 'NOT LIKE', '%Umum%')
            ->groupBy('bagian')
            ->orderBy('bagian')
            ->get();

        // [FIX LABEL] hapus prefix "Bagian " (case-insensitive)
        $bagianLabels = $bagianRows->pluck('bagian')
            ->map(fn($b) => preg_replace('/^\s*Bagian\s+/i', '', $b))
            ->values();
        $keluarData   = $bagianRows->pluck('total')->map(fn($v) => (int)$v)->values();

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN (VERSI HARGA, MIRIP PB)
         * =========================================================
         * Perhitungan: SUM(riwayat_barang.jumlah * barang.harga) per tahun
         * Sumber data: riwayat_barang (jenis_transaksi = 'distribusi') JOIN barang
         */
        $currentYear        = (int) date('Y');
        $years              = range($currentYear - 9, $currentYear);
        $pengeluaranLabels  = $years;

        $colorsForYears        = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear         = [];

        foreach ($years as $y) {
            $totalHarga = RiwayatBarang::where('riwayat_barang.jenis_transaksi', 'distribusi')
                ->join('barang', 'riwayat_barang.barang_id', '=', 'barang.id')
                ->whereYear('riwayat_barang.tanggal', $y)
                ->sum(DB::raw('riwayat_barang.jumlah * barang.harga'));

            $totalsPerYear[] = (float) $totalHarga;

            $c = $this->getColorForYear($y);
            $colorsForYears[$y]      = $c;
            $colorsForYearsOrdered[] = $c;
        }

        $pengeluaranData = [[
            'label'           => 'Keluar',
            'data'            => $totalsPerYear,
            'backgroundColor' => $colorsForYearsOrdered,
            'borderRadius'    => 4,
        ]];

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
        $type   = $request->query('type', 'bagian');
        $filter = $request->query('filter', 'all');

        if ($type === 'ringkasan') {
            return $this->filterRingkasanData($filter);
        }

        return $type === 'bagian'
            ? $this->filterBagianData($filter)
            : $this->filterPengeluaranData($filter);
    }

    // [NEW] Filter Ringkasan berdasarkan gudang
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
            $totalJenisBarang = JenisBarang::whereHas('kategori', function($query) use ($gudang) {
                $query->where('gudang_id', $gudang->id);
            })->count();

            // Hitung total barang berdasarkan gudang
            $totalBarang = Barang::whereHas('kategori', function($query) use ($gudang) {
                $query->where('gudang_id', $gudang->id);
            })->sum('stok');
        }

        return response()->json([
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    // [CHANGE] Per Bagian → kirim juga rentang tanggal untuk badge
    private function filterBagianData($filter)
    {
        // baseline labels (supaya batang tidak hilang)
        $allTimeLabels = Riwayat::query()
            ->where('alur_barang', 'Keluar')
            ->where('bagian', 'NOT LIKE', '%Umum%')
            ->select('bagian')
            ->groupBy('bagian')
            ->orderBy('bagian')
            ->pluck('bagian')
            ->values();

        $q = Riwayat::query()->where('alur_barang', 'Keluar');

        $start = null; $end = null; // [NEW] untuk badge
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

        $keluarData = [];
        foreach ($allTimeLabels as $bagian) {
            $total = (clone $q)
                ->where('bagian', 'LIKE', '%' . $bagian . '%')
                ->sum('jumlah');
            $keluarData[] = (int) $total;
        }

        return response()->json([
            // label tampilan (hapus prefix "Bagian ")
            'labels' => collect($allTimeLabels)->map(
                fn($b) => preg_replace('/^\s*Bagian\s+/i', '', $b)
            )->values(),
            'keluar' => $keluarData,
            // [NEW] info rentang untuk badge (null => "Semua Data")
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    // (tetap) Filter Pengeluaran per Tahun — masih versi jumlah barang
    // (kalau mau diseragamkan ke "harga", tinggal adapt serupa bagian __invoke)
    private function filterPengeluaranData($filter)
    {
        $currentYear = (int) date('Y');

        if ($filter === '5y')      $years = range($currentYear - 4,  $currentYear);
        elseif ($filter === '7y')  $years = range($currentYear - 6,  $currentYear);
        elseif ($filter === '10y') $years = range($currentYear - 10, $currentYear);
        else                       $years = range($currentYear - 9,  $currentYear);

        $totals = [];
        $colors = [];
        foreach ($years as $y) {
            $totals[]   = (int) Riwayat::where('alur_barang', 'Keluar')
                ->whereYear('tanggal', $y)->sum('jumlah');
            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data'   => $totals,
            'colors' => $colors,
        ]);
    }

    private function getColorForYear($year)
    {
        // palet stabil; otomatis berulang
        $palette = ['#8B5CF6', '#F87171', '#06B6D4', '#10B981', '#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int)$year) % count($palette);
        if ($idx < 0) $idx += count($palette);
        return $palette[$idx];
    }
}
