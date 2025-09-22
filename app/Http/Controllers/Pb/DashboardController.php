<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Riwayat;
use App\Models\Barang;
use App\Models\JenisBarang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::pbMenu();

        // Ringkasan
        $totalJenisBarang = JenisBarang::count();
        $totalBarang      = Barang::sum('stok');

        /* =========================================================
         * GRAFIK BARANG KELUAR PER KATEGORI (default: semua data)
         * =========================================================
         */
        $kategorMap = [
            'ATK' => 'G. ATK',
            'Kebersihan' => 'G. Kebersihan', 
            'Listrik' => 'G. Listrik',
            'Komputer' => 'G.B. Komputer'
        ];

        $keluarPerKategoriLabels = [];
        $keluarPerKategoriData = [];

        // Ambil semua data (tanpa filter waktu)
        foreach ($kategorMap as $gudangName => $kategoriLabel) {
            $total = Riwayat::where('alur_barang', 'Keluar')
                ->where('gudang', $gudangName)
                ->sum('jumlah');
            
            $keluarPerKategoriLabels[] = $kategoriLabel;
            $keluarPerKategoriData[] = (int) $total;
        }

        /* =========================================================
         * GRAFIK BARANG MASUK DAN KELUAR (2021-2025)  
         * =========================================================
         */
        $years = [2021, 2022, 2023, 2024, 2025];
        $masukData = [];
        $keluarData = [];
        
        foreach ($years as $year) {
            $masukData[] = (int) Riwayat::where('alur_barang', 'Masuk')
                ->whereYear('tanggal', $year)->sum('jumlah');
            
            $keluarData[] = (int) Riwayat::where('alur_barang', 'Keluar')
                ->whereYear('tanggal', $year)->sum('jumlah');
        }

        $masukKeluarData = [
            [
                'label' => 'Barang Masuk',
                'data' => $masukData,
                'backgroundColor' => '#22C55E',
                'borderRadius' => 4,
            ],
            [
                'label' => 'Barang Keluar', 
                'data' => $keluarData,
                'backgroundColor' => '#EF4444',
                'borderRadius' => 4,
            ]
        ];

        return view('staff.pb.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'keluarPerKategoriLabels',
            'keluarPerKategoriData',
            'years',
            'masukKeluarData'
        ));
    }

    /* ==================== AJAX FILTER ==================== */
    
    public function filterData(Request $request)
    {
        $type   = $request->query('type', 'kategori');
        $filter = $request->query('filter', 'all');

        return $type === 'kategori'
            ? $this->filterKategoriData($filter)
            : $this->filterMasukKeluarData($filter);
    }

    // Filter data kategori berdasarkan rentang waktu
    private function filterKategoriData($filter)
    {
        $kategorMap = [
            'ATK' => 'G. ATK',
            'Kebersihan' => 'G. Kebersihan',
            'Listrik' => 'G. Listrik', 
            'Komputer' => 'G.B. Komputer'
        ];

        $q = Riwayat::query()->where('alur_barang', 'Keluar');
        
        $start = null; 
        $end = null;
        
        // Filter berdasarkan waktu
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

        $labels = [];
        $data = [];

        foreach ($kategorMap as $gudangName => $kategoriLabel) {
            $total = (clone $q)
                ->where('gudang', $gudangName)
                ->sum('jumlah');
            
            $labels[] = $kategoriLabel;
            $data[] = (int) $total;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'colors' => [
                $this->getColorForKategori('ATK'),
                $this->getColorForKategori('Kebersihan'),
                $this->getColorForKategori('Listrik'),
                $this->getColorForKategori('Komputer')
            ],
            // Info rentang untuk badge (null => "Semua Data")
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    // Filter data masuk keluar berdasarkan rentang tahun
    private function filterMasukKeluarData($filter)
    {
        $currentYear = (int) date('Y');

        if ($filter === '3y')      $years = range($currentYear - 2, $currentYear);
        elseif ($filter === '5y')  $years = [2021, 2022, 2023, 2024, 2025];
        elseif ($filter === '7y')  $years = range($currentYear - 6, $currentYear);
        else                       $years = [2021, 2022, 2023, 2024, 2025]; // default

        $masukData = [];
        $keluarData = [];
        
        foreach ($years as $year) {
            $masukData[] = (int) Riwayat::where('alur_barang', 'Masuk')
                ->whereYear('tanggal', $year)->sum('jumlah');
            
            $keluarData[] = (int) Riwayat::where('alur_barang', 'Keluar')
                ->whereYear('tanggal', $year)->sum('jumlah');
        }

        $datasets = [
            [
                'label' => 'Barang Masuk',
                'data' => $masukData,
                'backgroundColor' => '#22C55E',
                'borderRadius' => 4,
            ],
            [
                'label' => 'Barang Keluar',
                'data' => $keluarData,
                'backgroundColor' => '#EF4444', 
                'borderRadius' => 4,
            ]
        ];

        return response()->json([
            'labels' => $years,
            'datasets' => $datasets,
        ]);
    }

    // Warna untuk setiap kategori
    private function getColorForKategori($kategori)
    {
        $colors = [
            'ATK' => '#3B82F6',      // Biru
            'Kebersihan' => '#10B981', // Hijau
            'Listrik' => '#F59E0B',    // Kuning
            'Komputer' => '#8B5CF6'    // Ungu
        ];
        
        return $colors[$kategori] ?? '#6B7280';
    }
}