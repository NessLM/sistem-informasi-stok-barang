<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
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
        $menu = MenuHelper::pbMenu();

        // Ringkasan (default: semua data)
        $totalJenisBarang = JenisBarang::count();
        $totalBarang = Barang::sum('stok');

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
         * GRAFIK PENGELUARAN PER TAHUN (DIUBAH SEPERTI ADMIN)
         * =========================================================
         */
        $currentYear        = (int) date('Y');
        $years              = range($currentYear - 9, $currentYear);
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

        return view('staff.pb.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'keluarPerKategoriLabels',
            'keluarPerKategoriData',
            'pengeluaranLabels',
            'pengeluaranData',
            'years',
            'colorsForYears'
        ));
    }

    /* ==================== AJAX FILTER ==================== */
    
    public function filterData(Request $request)
    {
        $type = $request->query('type', 'kategori');
        $filter = $request->query('filter', 'all');

        if ($type === 'ringkasan') {
            return $this->filterRingkasanData($filter);
        } elseif ($type === 'kategori') {
            return $this->filterKategoriData($filter);
        } else {
            return $this->filterPengeluaranData($filter);
        }
    }

    // Filter data ringkasan berdasarkan gudang
    private function filterRingkasanData($filter)
    {
        if ($filter === 'all') {
            $totalJenisBarang = JenisBarang::count();
            $totalBarang = Barang::sum('stok');
        } else {
            // Map filter ke ID gudang berdasarkan nama gudang
            $gudangMap = [
                'gudang-utama' => 'Gudang Utama',
                'gudang-atk' => 'Gudang ATK', 
                'gudang-listrik' => 'Gudang Listrik',
                'gudang-kebersihan' => 'Gudang Kebersihan',
                'gudang-b-komputer' => 'Gudang B Komputer'
            ];

            $gudangNama = $gudangMap[$filter] ?? null;
            
            if ($gudangNama) {
                // Ambil ID gudang berdasarkan nama
                $gudang = Gudang::where('nama', $gudangNama)->first();
                
                if ($gudang) {
                    // Hitung berdasarkan kategori yang berada di gudang tersebut
                    $totalJenisBarang = JenisBarang::whereHas('kategori', function ($query) use ($gudang) {
                        $query->where('gudang_id', $gudang->id);
                    })->count();

                    $totalBarang = Barang::whereHas('kategori', function ($query) use ($gudang) {
                        $query->where('gudang_id', $gudang->id);
                    })->sum('stok');
                } else {
                    $totalJenisBarang = 0;
                    $totalBarang = 0;
                }
            } else {
                $totalJenisBarang = 0;
                $totalBarang = 0;
            }
        }

        return response()->json([
            'totalJenisBarang' => $totalJenisBarang,
            'totalBarang' => $totalBarang
        ]);
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

    // [DIUBAH] Filter data pengeluaran per tahun seperti admin
    private function filterPengeluaranData($filter)
    {
        $currentYear = (int) date('Y');

        if ($filter === '5y')      $years = range($currentYear - 4,  $currentYear);
        elseif ($filter === '7y')  $years = range($currentYear - 6,  $currentYear);
        elseif ($filter === '10y') $years = range($currentYear - 9,  $currentYear);
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

    // [DITAMBAH] Method untuk warna tahun seperti admin
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