<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\RiwayatBarang;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Gudang;
use App\Models\Kategori;
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
         * MASALAH YANG DITEMUKAN:
         * 1. Query menggunakan whereHas pada relasi barang.kategori_id
         *    padahal di riwayat_barang sudah ada kolom kategori_asal_id
         * 2. Mapping gudang tidak lengkap (tidak ada Gudang Utama)
         * 3. Data di database menunjukkan kategori_asal_id = 1 (Gudang Utama)
         *    tapi tidak ada di mapping
         */

        // Mapping yang lengkap sesuai data di database
        $kategorMap = [
            'Gudang Utama' => 'G. Utama',           // ID: 1
            'Gudang ATK' => 'G. ATK',               // ID: 2
            'Gudang Listrik' => 'G. Listrik',       // ID: 3
            'Gudang Kebersihan' => 'G. Kebersihan', // ID: 4
            'Gudang B Komputer' => 'G.B. Komputer'  // ID: 5
        ];

        $keluarPerKategoriLabels = [];
        $keluarPerKategoriData = [];

        foreach ($kategorMap as $gudangName => $kategoriLabel) {
            $gudang = Gudang::where('nama', $gudangName)->first();

            if ($gudang) {
                // Ambil semua kategori dari gudang ini
                $kategoriIds = Kategori::where('gudang_id', $gudang->id)->pluck('id')->toArray();

                // PERBAIKAN: Query langsung ke kategori_asal_id
                // Karena di riwayat_barang sudah ada kolom kategori_asal_id
                // Tidak perlu pakai whereHas ke barang
                $total = RiwayatBarang::where('jenis_transaksi', 'distribusi')
                    // ✅ BENAR - Melihat gudang tujuan
                    ->where('gudang_tujuan_id', $gudang->id)
                    ->sum('jumlah');
            } else {
                $total = 0;
            }

            $keluarPerKategoriLabels[] = $kategoriLabel;
            $keluarPerKategoriData[] = (int) $total;
        }

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN (DALAM TOTAL HARGA)
         * =========================================================
         */
        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $pengeluaranLabels = $years;

        $colorsForYears = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear = [];

        foreach ($years as $y) {
            // Hitung total harga: SUM(riwayat_barang.jumlah * barang.harga)
            $totalHarga = RiwayatBarang::where('riwayat_barang.jenis_transaksi', 'distribusi')
                ->join('barang', 'riwayat_barang.barang_id', '=', 'barang.id')
                ->whereYear('riwayat_barang.tanggal', $y)
                ->sum(DB::raw('riwayat_barang.jumlah * barang.harga'));

            $totalsPerYear[] = (float) $totalHarga;
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
            // Map filter ke nama gudang
            $gudangMap = [
                'gudang-utama' => 'Gudang Utama',
                'gudang-atk' => 'Gudang ATK',
                'gudang-listrik' => 'Gudang Listrik',
                'gudang-kebersihan' => 'Gudang Kebersihan',
                'gudang-b-komputer' => 'Gudang B Komputer'
            ];

            $gudangNama = $gudangMap[$filter] ?? null;

            if ($gudangNama) {
                // Ambil gudang berdasarkan nama
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
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    // Filter data kategori berdasarkan rentang waktu
    private function filterKategoriData($filter)
    {
        // PERBAIKAN: Tambahkan Gudang Utama
        $kategorMap = [
            'Gudang Utama' => 'G. Utama',
            'Gudang ATK' => 'G. ATK',
            'Gudang Kebersihan' => 'G. Kebersihan',
            'Gudang Listrik' => 'G. Listrik',
            'Gudang B Komputer' => 'G.B. Komputer'
        ];

        $start = null;
        $end = null;

        // Filter berdasarkan waktu
        if ($filter === 'week') {
            $start = Carbon::now()->subWeek();
        } elseif ($filter === 'month') {
            $start = Carbon::now()->subMonth();
        } elseif ($filter === 'year') {
            $start = Carbon::now()->subYear();
        }
        $end = Carbon::now();

        $labels = [];
        $data = [];

        foreach ($kategorMap as $gudangName => $kategoriLabel) {
            // Cari ID gudang
            $gudang = Gudang::where('nama', $gudangName)->first();

            if ($gudang) {
                // Ambil kategori dari gudang
                $kategoriIds = Kategori::where('gudang_id', $gudang->id)->pluck('id')->toArray();

                // PERBAIKAN: Query langsung ke kategori_asal_id
                $query = RiwayatBarang::where('jenis_transaksi', 'distribusi')
                    ->whereIn('kategori_asal_id', $kategoriIds);

                // Tambahkan filter tanggal jika ada
                if ($start) {
                    $query->where('tanggal', '>=', $start);
                }

                $total = $query->sum('jumlah');
            } else {
                $total = 0;
            }

            $labels[] = $kategoriLabel;
            $data[] = (int) $total;
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'colors' => [
                $this->getColorForKategori('Utama'),
                $this->getColorForKategori('ATK'),
                $this->getColorForKategori('Kebersihan'),
                $this->getColorForKategori('Listrik'),
                $this->getColorForKategori('Komputer')
            ],
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    // Filter data pengeluaran per tahun (dalam total harga)
    private function filterPengeluaranData($filter)
    {
        $currentYear = (int) date('Y');

        if ($filter === '5y')
            $years = range($currentYear - 4, $currentYear);
        elseif ($filter === '7y')
            $years = range($currentYear - 6, $currentYear);
        elseif ($filter === '10y')
            $years = range($currentYear - 10, $currentYear);
        else
            $years = range($currentYear - 9, $currentYear);

        $totals = [];
        $colors = [];

        foreach ($years as $y) {
            // Hitung total harga: SUM(jumlah * harga)
            $totalHarga = RiwayatBarang::where('riwayat_barang.jenis_transaksi', 'distribusi')
                ->join('barang', 'riwayat_barang.barang_id', '=', 'barang.id')
                ->whereYear('riwayat_barang.tanggal', $y)
                ->sum(DB::raw('riwayat_barang.jumlah * barang.harga'));

            $totals[] = (float) $totalHarga;
            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data' => $totals,
            'colors' => $colors,
        ]);
    }

    // Warna untuk setiap kategori
    private function getColorForKategori($kategori)
    {
        $colors = [
            'Utama' => '#6366F1',    // Indigo
            'ATK' => '#3B82F6',      // Biru
            'Kebersihan' => '#10B981', // Hijau
            'Listrik' => '#F59E0B',    // Kuning
            'Komputer' => '#8B5CF6'    // Ungu
        ];

        return $colors[$kategori] ?? '#6B7280';
    }

    // Method untuk warna tahun
    private function getColorForYear($year)
    {
        $palette = ['#8B5CF6', '#F87171', '#06B6D4', '#10B981', '#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int) $year) % count($palette);
        if ($idx < 0)
            $idx += count($palette);
        return $palette[$idx];
    }
}