<?php

namespace App\Http\Controllers\Pj;

use App\Helpers\MenuHelper;
use App\Http\Controllers\Controller;
use App\Models\TransaksiBarangKeluar;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Gudang;
use App\Models\Bagian;
use App\Models\PjStok;
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

        // FIXED: Hitung jenis barang berdasarkan kategori di gudang
        $totalJenisBarang = JenisBarang::whereHas('kategori', function ($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->count();

        // FIXED: Ambil total stok dari pj_stok untuk gudang ini
        $totalBarang = PjStok::where('id_gudang', $gudang->id)->sum('stok');

        /* =========================================================
         * GRAFIK PER BAGIAN - BARANG KELUAR DARI GUDANG INI
         * =========================================================
         * Menggunakan transaksi_barang_keluar
         */

        // Ambil semua bagian dari database (exclude bagian internal)
        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
            ->orderBy('id')
            ->get();
        $bagianLabels = $allBagian->pluck('nama')->toArray();

        // Hitung data keluar untuk setiap bagian
        $keluarData = [];
        foreach ($allBagian as $bagian) {
            $total = TransaksiBarangKeluar::where('id_gudang', $gudang->id)
                ->where('bagian_id', $bagian->id)
                ->sum('jumlah');

            $keluarData[] = (int) $total;
        }

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN
         * =========================================================
         * Total barang keluar per tahun dari gudang ini
         */
        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $pengeluaranLabels = $years;

        $colorsForYears = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear = [];

        foreach ($years as $y) {
            $totalsPerYear[] = (int) TransaksiBarangKeluar::where('id_gudang', $gudang->id)
                ->whereYear('tanggal', $y)
                ->sum('jumlah');

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
     * Dapatkan gudang berdasarkan user yang login
     * IMPROVED: Menggunakan gudang_id dari users table
     */
    private function getGudangForUser($user)
    {
        // Cek apakah user memiliki gudang_id
        if ($user->gudang_id) {
            return Gudang::find($user->gudang_id);
        }

        // Fallback: mapping berdasarkan role (untuk backward compatibility)
        $roleName = $user->role->nama ?? null;

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

    /**
     * Generate judul dashboard berdasarkan nama gudang
     */
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

    /**
     * Filter Ringkasan berdasarkan gudang
     * FIXED: Menggunakan pj_stok
     */
    private function filterRingkasanData($gudangFilter, $gudang)
    {
        // Untuk PJ, gudang sudah tetap (tidak berubah)
        $totalJenisBarang = JenisBarang::whereHas('kategori', function ($query) use ($gudang) {
            $query->where('gudang_id', $gudang->id);
        })->count();

        // FIXED: Typo 'kategari' -> 'kategori' dan gunakan pj_stok
        $totalBarang = PjStok::where('id_gudang', $gudang->id)->sum('stok');

        return response()->json([
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    /**
     * Filter data per bagian dengan rentang waktu
     * FIXED: Menggunakan transaksi_barang_keluar
     */
    private function filterBagianData($filter, $gudang)
    {
        // Ambil semua bagian (exclude internal)
        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
            ->orderBy('id')
            ->get();
        $bagianLabels = $allBagian->pluck('nama')->toArray();

        $start = null;
        $end = null;

        // Tentukan rentang waktu filter
        if ($filter === 'week') {
            $start = Carbon::now()->subWeek();
        } elseif ($filter === 'month') {
            $start = Carbon::now()->subMonth();
        } elseif ($filter === 'year') {
            $start = Carbon::now()->subYear();
        }
        $end = Carbon::now();

        // Hitung data keluar untuk setiap bagian dengan filter waktu
        $keluarData = [];
        foreach ($allBagian as $bagian) {
            $q = TransaksiBarangKeluar::where('id_gudang', $gudang->id)
                ->where('bagian_id', $bagian->id);

            // Terapkan filter waktu jika ada
            if ($start) {
                $q->where('tanggal', '>=', $start)
                    ->where('tanggal', '<=', $end);
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

    /**
     * Filter Pengeluaran per Tahun
     * FIXED: Menggunakan transaksi_barang_keluar
     */
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
            $totals[] = (int) TransaksiBarangKeluar::where('id_gudang', $gudang->id)
                ->whereYear('tanggal', $y)
                ->sum('jumlah');

            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data' => $totals,
            'colors' => $colors,
        ]);
    }

    /**
     * Generate warna untuk tahun
     */
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