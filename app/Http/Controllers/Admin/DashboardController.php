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

        // === 9 label tetap untuk Grafik Per Bagian ===
        // [FIX] Masukkan "Keuangan" dan tetap 9 item (keluarkan "Organisasi").
        $bagianList = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat',
            'Keuangan',            // [FIX] baru
            'Hukum',
            'ADM Pembangunan',
            'Perekonomian',
            'Pengadaan',
            'Protokol',
            'Umum',
        ];

        // === Grafik Per Bagian (Keluar & Masuk) ===
        $bagianLabels = $bagianList;
        $keluarData   = [];
        $masukData    = [];

        foreach ($bagianList as $bagian) {
            $keluar = Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Keluar')
                ->sum('jumlah');

            $masuk = Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Masuk')
                ->sum('jumlah');

            $keluarData[] = (int) $keluar; // jika tak ada → 0
            $masukData[]  = (int) $masuk;  // jika tak ada → 0
        }

        // === Grafik Pengeluaran per Tahun (HANYA Keluar, sumbu-X = Tahun) ===
        $currentYear = (int) date('Y');

        // [FIX] "Semua" = 2016..tahun-ini (contoh 2016–2025 jika sekarang 2025)
        $pengeluaranLabels = range($currentYear - 9, $currentYear);

        // total Keluar semua bagian per tahun (untuk tiap label tahun)
        $totalsPerYear = [];
        foreach ($pengeluaranLabels as $year) {
            $sum = Riwayat::where('alur_barang', 'Keluar')
                ->whereYear('tanggal', $year)
                ->sum('jumlah');
            $totalsPerYear[] = (int) $sum;
        }

        // Dataset tunggal "Keluar" agar batang lebar dan jelas
        $pengeluaranData = [
            [
                'label'           => 'Keluar',
                'data'            => $totalsPerYear,
                'backgroundColor' => '#8B5CF6',
                'borderRadius'    => 4,
            ]
        ];

        return view('staff.admin.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'bagianLabels',
            'keluarData',
            'masukData',
            'pengeluaranLabels',
            'pengeluaranData'
        ));
    }

    public function filterData(Request $request)
    {
        $type   = $request->input('type');
        $filter = $request->input('filter');

        if ($type === 'bagian') {
            return $this->filterBagianData($filter);
        }
        return $this->filterPengeluaranData($filter);
    }

    private function filterBagianData($filter)
    {
        // [Sama seperti di atas: 9 label tetap]
        $bagianList = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat',
            'Keuangan',
            'Hukum',
            'ADM Pembangunan',
            'Perekonomian',
            'Pengadaan',
            'Protokol',
            'Umum',
        ];

        $keluarData = [];
        $masukData  = [];

        $query = Riwayat::query();

        // Filter waktu (tetap week/month/year untuk grafik per bagian)
        if ($filter === 'week') {
            $query->where('tanggal', '>=', Carbon::now()->subWeek());
        } elseif ($filter === 'month') {
            $query->where('tanggal', '>=', Carbon::now()->subMonth());
        } elseif ($filter === 'year') {
            $query->where('tanggal', '>=', Carbon::now()->subYear());
        }

        foreach ($bagianList as $bagian) {
            $keluar = (clone $query)->where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Keluar')
                ->sum('jumlah');

            $masuk = (clone $query)->where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Masuk')
                ->sum('jumlah');

            $keluarData[] = (int) $keluar;
            $masukData[]  = (int) $masuk;
        }

        return response()->json([
            'keluar' => $keluarData,
            'masuk'  => $masukData,
        ]);
    }

    private function filterPengeluaranData($filter)
    {
        $currentYear = (int) date('Y');

        // [FIX] Filter berdasarkan rentang TAHUN
        if ($filter === '5y') {
            $years = range($currentYear - 4, $currentYear);
        } elseif ($filter === '7y') {
            $years = range($currentYear - 6, $currentYear);
        } elseif ($filter === '10y') {
            $years = range($currentYear - 9, $currentYear);
        } else {
            // "Semua" → sama dengan 2016..tahun-ini (10 tahun inklusif)
            $years = range($currentYear - 9, $currentYear);
        }

        $totals = [];
        foreach ($years as $year) {
            $sum = Riwayat::where('alur_barang', 'Keluar')
                ->whereYear('tanggal', $year)
                ->sum('jumlah');
            $totals[] = (int) $sum;
        }

        // [FIX] Kembalikan shape baru: labels = tahun, data = total
        return response()->json([
            'labels' => $years,
            'data'   => $totals,
        ]);
    }
}
