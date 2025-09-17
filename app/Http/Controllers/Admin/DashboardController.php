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

        // ==== 9 label tetap untuk "Grafik Per Bagian" ====
        // [FIX] pastikan "Keuangan" ada dan tetap 9 slot
        $bagianList = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat',
            'Keuangan',            // [FIX]
            'Hukum',
            'ADM Pembangunan',
            'Perekonomian',
            'Pengadaan',
            'Protokol',
            'Umum',
        ];

        // ==== Data Grafik Per Bagian (Keluar & Masuk) ====
        $bagianLabels = $bagianList;
        $keluarData   = [];
        $masukData    = [];
        foreach ($bagianList as $bagian) {
            $keluar = Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Keluar')->sum('jumlah');
            $masuk  = Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Masuk')->sum('jumlah');
            $keluarData[] = (int)$keluar;   // bisa 0
            $masukData[]  = (int)$masuk;    // bisa 0
        }

        // ==== Data "Pengeluaran per Tahun" (X = tahun, hanya Keluar) ====
        $currentYear        = (int) date('Y');
        $years              = range($currentYear - 9, $currentYear); // 2016..tahun-ini saat 2025
        $pengeluaranLabels  = $years;

        // warna per tahun + total per tahun
        $colorsForYears           = [];
        $colorsForYearsOrdered    = [];
        $totalsPerYear            = [];
        foreach ($years as $y) {
            $sum = Riwayat::where('alur_barang', 'Keluar')
                    ->whereYear('tanggal', $y)->sum('jumlah');
            $totalsPerYear[] = (int)$sum;

            $c = $this->getColorForYear($y);
            $colorsForYears[$y]        = $c;
            $colorsForYearsOrdered[]   = $c;
        }

        // 1 dataset; warna per-bar mengikuti tahun
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
            'keluarData', 'masukData',
            'pengeluaranLabels', 'pengeluaranData',
            'years', 'colorsForYears' // untuk legend tahun awal
        ));
    }

    public function filterData(Request $request)
    {
        $type   = $request->input('type');
        $filter = $request->input('filter');

        if ($type === 'bagian') return $this->filterBagianData($filter);
        return $this->filterPengeluaranData($filter);
    }

    private function filterBagianData($filter)
    {
        // sama dengan di __invoke
        $bagianList = [
            'Tata Pemerintahan','Kesejahteraan Rakyat','Keuangan','Hukum',
            'ADM Pembangunan','Perekonomian','Pengadaan','Protokol','Umum',
        ];

        $keluarData = []; $masukData = [];
        $q = Riwayat::query();

        // [FIX] filter periode berfungsi
        if ($filter === 'week')  $q->where('tanggal', '>=', Carbon::now()->subWeek());
        if ($filter === 'month') $q->where('tanggal', '>=', Carbon::now()->subMonth());
        if ($filter === 'year')  $q->where('tanggal', '>=', Carbon::now()->subYear());
        // 'all' -> tanpa where tanggal

        foreach ($bagianList as $bagian) {
            $keluar = (clone $q)->where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang','Keluar')->sum('jumlah');
            $masuk  = (clone $q)->where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang','Masuk')->sum('jumlah');
            $keluarData[] = (int)$keluar;
            $masukData[]  = (int)$masuk;
        }

        return response()->json(['keluar'=>$keluarData, 'masuk'=>$masukData]);
    }

    private function filterPengeluaranData($filter)
    {
        $currentYear = (int) date('Y');

        // [FIX] filter rentang tahun
        if ($filter === '5y')      $years = range($currentYear - 4, $currentYear);
        elseif ($filter === '7y')  $years = range($currentYear - 6, $currentYear);
        elseif ($filter === '10y') $years = range($currentYear - 9, $currentYear);
        else                       $years = range($currentYear - 9, $currentYear); // "Semua"

        $totals = []; $colors = [];
        foreach ($years as $y) {
            $totals[]    = (int) Riwayat::where('alur_barang','Keluar')
                              ->whereYear('tanggal', $y)->sum('jumlah');
            $colors[$y]  = $this->getColorForYear($y);
        }

        // [FIX] kirim labels + data + warna per tahun
        return response()->json([
            'labels' => $years,
            'data'   => $totals,
            'colors' => $colors,
        ]);
    }

    private function getColorForYear($year)
    {
        // palet looping (stabil)
        $palette = ['#8B5CF6','#F87171','#06B6D4','#10B981','#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int)$year) % count($palette);
        if ($idx < 0) $idx += count($palette);
        return $palette[$idx];
    }
}
