<?php

namespace App\Http\Controllers\Pj;

use App\Helpers\MenuHelper;
use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
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
        $gudang = $this->getGudangForUser(auth()->user());

        if (!$gudang) {
            abort(403, 'Anda tidak memiliki akses ke gudang manapun.');
        }

        $pageTitle = $this->getDashboardTitle($gudang->nama);

        $totalJenisBarang = JenisBarang::whereHas('kategori', function ($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->count();

        $totalBarang = Barang::whereHas('kategori', function ($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->sum('stok');

        /* =========================================================
         * GRAFIK PER BAGIAN - 10 BAGIAN TETAP (TIDAK DARI DATABASE)
         * ========================================================= */

        // Daftar 10 bagian yang TETAP (tanpa prefix "Bagian")
        $bagianLabels = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat dan Kemasyarakatan',
            'Hukum dan HAM',
            'Adm Pembangunan',
            'Perekonomian',
            'Adm Pelayanan Pengadaan Barang dan Jasa',
            'Protokol',
            'Organisasi',
            'Umum dan Rumah Tangga',
            'Perencanaan dan Keuangan'
        ];

        // Hitung data keluar untuk setiap bagian dari tabel barang_keluars
        $keluarData = [];
        foreach ($bagianLabels as $label) {
            $total = BarangKeluar::where('gudang_id', $gudang->id)
                ->where(function ($q) use ($label) {
                    $q->where('bagian', 'LIKE', "%{$label}%")
                        ->orWhere('bagian', 'LIKE', "%Bagian {$label}%");
                })
                ->sum('jumlah');

            $keluarData[] = (int) $total;
        }

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN
         * ========================================================= */
        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $pengeluaranLabels = $years;

        $colorsForYears = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear = [];
        foreach ($years as $y) {
            $totalsPerYear[] = (int) BarangKeluar::where('gudang_id', $gudang->id)
                ->whereYear('tanggal', $y)->sum('jumlah');
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
        $type = $request->query('type', 'bagian');
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
        $totalJenisBarang = JenisBarang::whereHas('kategori', function ($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->count();

        $totalBarang = Barang::whereHas('kategori', function ($query) use ($gudang) {
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
    // Daftar 10 bagian yang TETAP
    $bagianLabels = [
        'Tata Pemerintahan',
        'Kesejahteraan Rakyat dan Kemasyarakatan',
        'Hukum dan HAM',
        'Adm Pembangunan',
        'Perekonomian',
        'Adm Pelayanan Pengadaan Barang dan Jasa',
        'Protokol',
        'Organisasi',
        'Umum dan Rumah Tangga',
        'Perencanaan dan Keuangan'
    ];

    $start = null;
    $end = null;
    
    // Tentukan rentang waktu filter
    if ($filter === 'week') {
        $start = Carbon::now()->subWeek();
        $end = Carbon::now();
    } elseif ($filter === 'month') {
        $start = Carbon::now()->subMonth();
        $end = Carbon::now();
    } elseif ($filter === 'year') {
        $start = Carbon::now()->subYear();
        $end = Carbon::now();
    }

    // Hitung data keluar untuk setiap bagian dengan filter waktu
    $keluarData = [];
    foreach ($bagianLabels as $label) {
        $q = BarangKeluar::where('gudang_id', $gudang->id)
            ->where(function($query) use ($label) {
                $query->where('bagian', 'LIKE', "%{$label}%")
                      ->orWhere('bagian', 'LIKE', "%Bagian {$label}%");
            });
        
        // Terapkan filter waktu jika ada
        if ($start && $end) {
            $q->whereBetween('tanggal', [$start, $end]);
        }
        
        $total = $q->sum('jumlah');
        $keluarData[] = (int) $total;
    }

    return response()->json([
        'labels' => $bagianLabels,
        'keluar' => $keluarData,
        'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
    ]);
}
    // Filter Pengeluaran per Tahun
    private function filterPengeluaranData($filter, $gudang)
    {
        $currentYear = (int) date('Y');

        if ($filter === '5y')
            $years = range($currentYear - 4, $currentYear);
        elseif ($filter === '7y')
            $years = range($currentYear - 6, $currentYear);
        elseif ($filter === '10y')
            $years = range($currentYear - 9, $currentYear);
        else
            $years = range($currentYear - 9, $currentYear);

        $totals = [];
        $colors = [];
        foreach ($years as $y) {
            $totals[] = (int) BarangKeluar::where('gudang_id', $gudang->id)
                ->whereYear('tanggal', $y)->sum('jumlah');
            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data' => $totals,
            'colors' => $colors,
        ]);
    }

    private function getColorForYear($year)
    {
        // palet stabil; otomatis berulang
        $palette = ['#8B5CF6', '#F87171', '#06B6D4', '#10B981', '#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int) $year) % count($palette);
        if ($idx < 0)
            $idx += count($palette);
        return $palette[$idx];
    }
}