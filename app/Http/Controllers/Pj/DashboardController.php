<?php

namespace App\Http\Controllers\Pj;

use App\Helpers\MenuHelper;
use App\Http\Controllers\Controller;
use App\Models\Riwayat;
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
        $menu = MenuHelper::pjMenu();

        // Dapatkan gudang berdasarkan role user yang login
        $gudang = $this->getGudangForUser(auth()->user());

        if (!$gudang) {
            abort(403, 'Anda tidak memiliki akses ke gudang manapun.');
        }

        // Format judul dashboard berdasarkan gudang
        $pageTitle = $this->getDashboardTitle($gudang->nama);

        // Ringkasan: total jenis barang dan total barang hanya untuk gudang ini
        $totalJenisBarang = JenisBarang::whereHas('kategori', function($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->count();

        $totalBarang = Barang::whereHas('kategori', function($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->sum('stok');

        /* =========================================================
         * GRAFIK PER BAGIAN  (HANYA KELUAR, TANPA DEFAULT, EXCLUDE "Umum")
         * =========================================================
         * - Ambil hanya bagian yang benar-benar punya transaksi Keluar dari gudang ini
         * - Urut alfabet biar stabil
         * - "Umum" dikecualikan
         */
        $bagianRows = Riwayat::select('bagian', DB::raw('SUM(jumlah) AS total'))
            ->where('alur_barang', 'Keluar')
            ->where('gudang', $gudang->nama) // Hanya riwayat dari gudang ini
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
         * GRAFIK PENGELUARAN PER TAHUN
         * ========================================================= */
        $currentYear        = (int) date('Y');
        $years              = range($currentYear - 9, $currentYear);
        $pengeluaranLabels  = $years;

        $colorsForYears        = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear         = [];
        foreach ($years as $y) {
            $totalsPerYear[] = (int) Riwayat::where('alur_barang', 'Keluar')
                ->where('gudang', $gudang->nama) // Hanya riwayat dari gudang ini
                ->whereYear('tanggal', $y)->sum('jumlah');
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

        return view('staff.pj.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'gudang',
            'bagianLabels',
            'keluarData',
            'pengeluaranLabels',
            'pengeluaranData',
            'years',
            'colorsForYears',
            'pageTitle'
        ));
    }

    /**
     * Dapatkan gudang berdasarkan role user.
     */
    private function getGudangForUser($user)
    {
        $roleName = $user->role->nama;

        $mapping = [
            'Penanggung Jawab ATK' => 'Gudang ATK',
            'Penanggung Jawab Kebersihan' => 'Gudang Kebersihan',
            'Penanggung Jawab Listrik' => 'Gudang Listrik',
            'Penanggung Jawab Bahan Komputer' => 'Gudang B Komputer',
        ];

        $gudangName = $mapping[$roleName] ?? null;

        if (!$gudangName) {
            return null;
        }

        return Gudang::where('nama', $gudangName)->first();
    }

    private function getDashboardTitle($gudangName)
    {
        $mapping = [
            'Gudang ATK' => 'G. ATK',
            'Gudang Kebersihan' => 'G. Kebersihan',
            'Gudang Listrik' => 'G. Listrik',
            'Gudang B Komputer' => 'G. B. Komputer',
        ];

        $gudangShort = $mapping[$gudangName] ?? $gudangName;


        return "Dashboard Penanggung Jawab {$gudangShort}";
    }

    /* ==================== AJAX FILTER ==================== */
    public function filterData(Request $request)
    {
        $type   = $request->query('type', 'bagian');
        $filter = $request->query('filter', 'all');

        // Dapatkan gudang untuk user yang login
        $gudang = $this->getGudangForUser(auth()->user());

        if (!$gudang) {
            return response()->json(['error' => 'Gudang tidak ditemukan'], 404);
        }

        if ($type === 'ringkasan') {
            return $this->filterRingkasanData($filter, $gudang);
        }

        return $type === 'bagian'
            ? $this->filterBagianData($filter, $gudang)
            : $this->filterPengeluaranData($filter, $gudang);
    }

    // Filter Ringkasan berdasarkan gudang
    private function filterRingkasanData($gudangFilter, $gudang)
    {
        // Untuk PJ, gudangFilter sebenarnya tidak digunakan karena gudang sudah tetap.
        $totalJenisBarang = JenisBarang::whereHas('kategori', function($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->count();

        $totalBarang = Barang::whereHas('kategori', function($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->sum('stok');

        return response()->json([
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    // Per Bagian → kirim juga rentang tanggal untuk badge
    private function filterBagianData($filter, $gudang)
    {
        // baseline labels (supaya batang tidak hilang)
        $allTimeLabels = Riwayat::query()
            ->where('alur_barang', 'Keluar')
            ->where('gudang', $gudang->nama) // Hanya dari gudang ini
            ->where('bagian', 'NOT LIKE', '%Umum%')
            ->select('bagian')
            ->groupBy('bagian')
            ->orderBy('bagian')
            ->pluck('bagian')
            ->values();

        $q = Riwayat::query()->where('alur_barang', 'Keluar')->where('gudang', $gudang->nama);

        $start = null; $end = null; // untuk badge
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
            // info rentang untuk badge (null => "Semua Data")
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    // Filter Pengeluaran per Tahun
    private function filterPengeluaranData($filter, $gudang)
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
                ->where('gudang', $gudang->nama) // Hanya dari gudang ini
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