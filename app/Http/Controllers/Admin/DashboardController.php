<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Riwayat;
use App\Models\Barang;
use App\Models\JenisBarang;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::adminMenu();

        // Ringkasan
        $totalJenisBarang = JenisBarang::count();
        $totalBarang      = Barang::sum('stok');

        // === 8 label tetap untuk Grafik Per Bagian (tanpa "Umum") ===
        // [FIX] Hilangkan "Umum" dan pertahankan "Keuangan"
        $bagianList = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat',
            'Keuangan',
            'Hukum',
            'ADM Pembangunan',
            'Perekonomian',
            'Pengadaan',
            'Protokol',
        ];

        // === Grafik Per Bagian: hanya KELUAR ===
        $bagianLabels = $bagianList;
        $keluarData   = [];
        foreach ($bagianList as $bagian) {
            $keluarData[] = (int) Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Keluar')
                ->sum('jumlah'); // jika tidak ada → 0
        }

        // === Grafik Pengeluaran per Tahun (tetap seperti sebelumnya) ===
        $currentYear        = (int) date('Y');
        $years              = range($currentYear - 9, $currentYear); // default "Semua" 10 th inklusif
        $pengeluaranLabels  = $years;

        $colorsForYears        = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear         = [];
        foreach ($years as $y) {
            $totalsPerYear[] = (int) Riwayat::where('alur_barang', 'Keluar')
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

        return view('staff.admin.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'bagianLabels',
            'keluarData',
            'pengeluaranLabels',
            'pengeluaranData',
            'years',
            'colorsForYears'
        ));
    }

    // ==================== AJAX FILTER ====================

    public function filterData(Request $request)
    {
        $type   = $request->query('type', 'bagian');
        $filter = $request->query('filter', 'all');

        return $type === 'bagian'
            ? $this->filterBagianData($filter)
            : $this->filterPengeluaranData($filter);
    }

    // [FIX] Filter untuk Grafik Per Bagian → hanya kembalikan 'keluar'
    private function filterBagianData($filter)
    {
        $bagianList = [
            'Tata Pemerintahan','Kesejahteraan Rakyat','Keuangan','Hukum',
            'ADM Pembangunan','Perekonomian','Pengadaan','Protokol',
        ];

        $keluarData = [];
        $q = Riwayat::query();

        if ($filter === 'week')  { $q->where('tanggal', '>=', Carbon::now()->subWeek());  }
        if ($filter === 'month') { $q->where('tanggal', '>=', Carbon::now()->subMonth()); }
        if ($filter === 'year')  { $q->where('tanggal', '>=', Carbon::now()->subYear());  }
        // 'all' → tanpa filter tanggal

        foreach ($bagianList as $bagian) {
            $keluarData[] = (int) (clone $q)->where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang','Keluar')->sum('jumlah');
        }

        return response()->json([
            'keluar' => $keluarData
        ]);
    }

    // (Tidak diubah) Filter untuk Pengeluaran per Tahun
    private function filterPengeluaranData($filter)
    {
        $currentYear = (int) date('Y');

        if     ($filter === '5y')  { $years = range($currentYear - 4, $currentYear); }
        elseif ($filter === '7y')  { $years = range($currentYear - 6, $currentYear); }
        elseif ($filter === '10y') { $years = range($currentYear - 10, $currentYear); }
        else                       { $years = range($currentYear - 9, $currentYear); } // Semua

        $totals = []; $colors = [];
        foreach ($years as $y) {
            $totals[]   = (int) Riwayat::where('alur_barang','Keluar')
                ->whereYear('tanggal',$y)->sum('jumlah');
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
        $palette = ['#8B5CF6','#F87171','#06B6D4','#10B981','#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int)$year) % count($palette);
        if ($idx < 0) $idx += count($palette);
        return $palette[$idx];
    }
}
