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
         * GRAFIK BARANG KELUAR PER KATEGORI (2021-2025)
         * =========================================================
         * Menampilkan data keluar per kategori barang untuk 5 tahun terakhir
         */
        $years = [2021, 2022, 2023, 2024, 2025];
        
        // Mapping kategori dengan nama yang akan ditampilkan
        $kategorMap = [
            'ATK' => 'G. ATK',
            'Kebersihan' => 'G. Kebersihan', 
            'Listrik' => 'G. Listrik',
            'Komputer' => 'G.B. Komputer'
        ];

        $keluarPerKategori = [];
        
        foreach ($kategorMap as $kategoriKey => $kategoriLabel) {
            $dataPerTahun = [];
            foreach ($years as $year) {
                $total = Riwayat::join('barang', 'riwayat.barang_id', '=', 'barang.id')
                    ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
                    ->join('kategori', 'jenis_barang.kategori_id', '=', 'kategori.id')
                    ->where('riwayat.alur_barang', 'Keluar')
                    ->where('kategori.nama', $kategoriKey)
                    ->whereYear('riwayat.tanggal', $year)
                    ->sum('riwayat.jumlah');
                
                $dataPerTahun[] = (int) $total;
            }
            
            $keluarPerKategori[] = [
                'label' => $kategoriLabel,
                'data' => $dataPerTahun,
                'backgroundColor' => $this->getColorForKategori($kategoriKey),
                'borderRadius' => 4,
            ];
        }

        /* =========================================================
         * GRAFIK BARANG MASUK DAN KELUAR (2021-2025)  
         * =========================================================
         */
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
            'years',
            'keluarPerKategori',
            'masukKeluarData'
        ));
    }

    /* ==================== AJAX FILTER ==================== */
    
    public function filterData(Request $request)
    {
        $type   = $request->query('type', 'kategori');
        $filter = $request->query('filter', '5y');

        return $type === 'kategori'
            ? $this->filterKategoriData($filter)
            : $this->filterMasukKeluarData($filter);
    }

    // Filter data kategori berdasarkan rentang tahun
    private function filterKategoriData($filter)
    {
        $currentYear = (int) date('Y');
        
        if ($filter === '3y')      $years = range($currentYear - 2, $currentYear);
        elseif ($filter === '5y')  $years = [2021, 2022, 2023, 2024, 2025];
        elseif ($filter === '7y')  $years = range($currentYear - 6, $currentYear);
        else                       $years = [2021, 2022, 2023, 2024, 2025]; // default

        $kategorMap = [
            'ATK' => 'G. ATK',
            'Kebersihan' => 'G. Kebersihan',
            'Listrik' => 'G. Listrik', 
            'Komputer' => 'G.B. Komputer'
        ];

        $datasets = [];
        
        foreach ($kategorMap as $kategoriKey => $kategoriLabel) {
            $dataPerTahun = [];
            foreach ($years as $year) {
                $total = Riwayat::join('barang', 'riwayat.barang_id', '=', 'barang.id')
                    ->join('jenis_barang', 'barang.jenis_barang_id', '=', 'jenis_barang.id')
                    ->join('kategori', 'jenis_barang.kategori_id', '=', 'kategori.id')
                    ->where('riwayat.alur_barang', 'Keluar')
                    ->where('kategori.nama', $kategoriKey)
                    ->whereYear('riwayat.tanggal', $year)
                    ->sum('riwayat.jumlah');
                
                $dataPerTahun[] = (int) $total;
            }
            
            $datasets[] = [
                'label' => $kategoriLabel,
                'data' => $dataPerTahun,
                'backgroundColor' => $this->getColorForKategori($kategoriKey),
                'borderRadius' => 4,
            ];
        }

        return response()->json([
            'labels' => $years,
            'datasets' => $datasets,
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