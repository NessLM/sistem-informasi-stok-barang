<?php

namespace App\Http\Controllers\Admin;

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
        $menu = MenuHelper::adminMenu();

        // Hitung total jenis barang dan total barang
        $totalJenisBarang = JenisBarang::count();
        $totalBarang = Barang::sum('stok');

        // Daftar 7 bagian yang diminta
        $bagianList = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat', 
            'Hukum',
            'ADM Pembangunan',
            'Perekonomian',
            'Pengadaan',
            'Protokol'
        ];

        // Data untuk grafik per bagian
        $bagianLabels = $bagianList;
        $keluarData = [];
        $masukData = [];

        foreach ($bagianList as $bagian) {
            // Ambil data dari riwayat berdasarkan nama bagian
            $keluar = Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Keluar')
                ->sum('jumlah');
            
            $masuk = Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                ->where('alur_barang', 'Masuk')
                ->sum('jumlah');
            
            $keluarData[] = $keluar;
            $masukData[] = $masuk;
        }

        // Data untuk grafik pengeluaran per waktu (3 tahun terakhir)
        $pengeluaranLabels = $bagianList;
        $pengeluaranData = [];

        $currentYear = date('Y');
        $years = [$currentYear - 2, $currentYear - 1, $currentYear];
        
        // Generate colors for each year
        $colorsForYears = [];
        foreach ($years as $year) {
            $colorsForYears[$year] = $this->getColorForYear($year);
            
            $yearData = [];
            
            foreach ($bagianList as $bagian) {
                $data = Riwayat::where('bagian', 'like', '%' . $bagian . '%')
                    ->where('alur_barang', 'Keluar')
                    ->whereYear('tanggal', $year)
                    ->sum('jumlah');
                
                $yearData[] = $data;
            }
            
            $pengeluaranData[] = [
                'label' => (string)$year,
                'data' => $yearData,
                'backgroundColor' => $colorsForYears[$year]
            ];
        }

        return view('staff.admin.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'bagianLabels',
            'keluarData',
            'masukData',
            'pengeluaranLabels',
            'pengeluaranData',
            'years',
            'colorsForYears'
        ));
    }

    public function filterData(Request $request)
    {
        $type = $request->input('type');
        $filter = $request->input('filter');
        
        if ($type === 'bagian') {
            return $this->filterBagianData($filter);
        } else {
            return $this->filterPengeluaranData($filter);
        }
    }

    private function filterBagianData($filter)
    {
        $bagianList = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat', 
            'Hukum',
            'ADM Pembangunan',
            'Perekonomian',
            'Pengadaan',
            'Protokol'
        ];

        $keluarData = [];
        $masukData = [];
        
        $query = Riwayat::query();
        
        // Terapkan filter berdasarkan waktu
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
            
            $keluarData[] = $keluar;
            $masukData[] = $masuk;
        }

        return response()->json([
            'keluar' => $keluarData,
            'masuk' => $masukData
        ]);
    }

    private function filterPengeluaranData($filter)
    {
        $bagianList = [
            'Tata Pemerintahan',
            'Kesejahteraan Rakyat', 
            'Hukum',
            'ADM Pembangunan',
            'Perekonomian',
            'Pengadaan',
            'Protokol'
        ];

        $currentYear = date('Y');
        $years = [$currentYear - 2, $currentYear - 1, $currentYear];
        $result = [];
        
        $baseQuery = Riwayat::where('alur_barang', 'Keluar');
        
        // Terapkan filter berdasarkan waktu
        if ($filter === 'week') {
            $baseQuery->where('tanggal', '>=', Carbon::now()->subWeek());
        } elseif ($filter === 'month') {
            $baseQuery->where('tanggal', '>=', Carbon::now()->subMonth());
        } elseif ($filter === 'year') {
            $baseQuery->where('tanggal', '>=', Carbon::now()->subYear());
        }

        foreach ($years as $year) {
            $yearData = [];
            
            foreach ($bagianList as $bagian) {
                $data = (clone $baseQuery)->where('bagian', 'like', '%' . $bagian . '%')
                    ->whereYear('tanggal', $year)
                    ->sum('jumlah');
                
                $yearData[] = $data;
            }
            
            $result[] = [
                'label' => $year,
                'data' => $yearData,
                'backgroundColor' => $this->getColorForYear($year)
            ];
        }

        return response()->json($result);
    }

    private function getColorForYear($year)
    {
        $colors = [
            '#8B5CF6', // Ungu
            '#F87171', // Merah
            '#06B6D4', // Biru
            '#10B981', // Hijau
            '#F59E0B', // Kuning
        ];
        
        $currentYear = date('Y');
        $index = $currentYear - $year;
        
        return $colors[$index] ?? $colors[0];
    }
}