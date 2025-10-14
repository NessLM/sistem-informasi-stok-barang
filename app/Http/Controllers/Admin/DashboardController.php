<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Models\Gudang;
use App\Models\PbStok;
use App\Models\PjStok;
use App\Models\TransaksiBarangKeluar;
use App\Models\TransaksiDistribusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::adminMenu();

        // Ringkasan (default: semua gudang) - FIXED: Ambil dari pb_stok
        $totalJenisBarang = JenisBarang::count();
        $totalBarang = PbStok::sum('stok'); // Stok dari Pengelola Barang

        // Data gudang untuk dropdown filter
        $gudangs = Gudang::orderBy('nama')->get();

        /* =========================================================
         * GRAFIK PER BAGIAN (KATEGORI TUJUAN) - HANYA KELUAR
         * =========================================================
         * Dari transaksi_barang_keluar
         * - Grouping by bagian_id
         * - Exclude "Umum" dan "Gudang" dan "Operasional"
         */
        $bagianRows = TransaksiBarangKeluar::select('bagian_id', DB::raw('SUM(jumlah) AS total'))
            ->whereNotNull('bagian_id')
            ->groupBy('bagian_id')
            ->with('bagian')
            ->get();

        // Map ke nama bagian dan exclude bagian tertentu
        $bagianData = $bagianRows->map(function ($row) {
            $namaBagian = optional($row->bagian)->nama ?? 'Tidak Diketahui';
            return [
                'bagian' => $namaBagian,
                'total' => (int) $row->total
            ];
        })
            ->filter(function ($item) {
                // Exclude bagian tertentu (case-insensitive)
                return !preg_match('/^(umum|gudang|operasional)$/i', $item['bagian']);
            })
            ->sortBy('bagian')
            ->values();

        $bagianLabels = $bagianData->pluck('bagian')->values();
        $keluarData = $bagianData->pluck('total')->values();

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN (DALAM TOTAL HARGA)
         * =========================================================
         * Perhitungan: SUM(transaksi_distribusi.jumlah * barang.harga_barang)
         */
        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $pengeluaranLabels = $years;

        $colorsForYears = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear = [];

        foreach ($years as $y) {
            $totalHarga = TransaksiDistribusi::join('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->whereYear('transaksi_distribusi.tanggal', $y)
                ->sum(DB::raw('transaksi_distribusi.jumlah * barang.harga_barang'));

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

        return view('staff.admin.dashboard', compact(
            'menu',
            'totalJenisBarang',
            'totalBarang',
            'gudangs',
            'bagianLabels',
            'keluarData',
            'pengeluaranLabels',
            'pengeluaranData',
            'years',
            'colorsForYears'
        ));
    }

    /* ==================== AJAX FILTER ==================== */
    public function filterData(Request $request)
    {
        $type = $request->query('type', 'bagian');
        $filter = $request->query('filter', 'all');

        if ($type === 'ringkasan') {
            return $this->filterRingkasanData($filter);
        }

        return $type === 'bagian'
            ? $this->filterBagianData($filter)
            : $this->filterPengeluaranData($filter);
    }

    /**
     * Filter Ringkasan berdasarkan gudang
     * FIXED: Menggunakan pb_stok dan pj_stok
     */
    private function filterRingkasanData($gudangFilter)
    {
        if ($gudangFilter === 'all') {
            // Semua gudang - ambil dari PB Stok (stok pusat)
            $totalJenisBarang = JenisBarang::count();
            $totalBarang = PbStok::sum('stok');
        } else {
            // Filter berdasarkan gudang tertentu
            $gudang = Gudang::where('nama', $gudangFilter)->first();

            if (!$gudang) {
                return response()->json([
                    'totalJenisBarang' => 0,
                    'totalBarang' => 0
                ]);
            }

            // Hitung total jenis barang berdasarkan gudang
            $totalJenisBarang = JenisBarang::whereHas('kategori', function ($query) use ($gudang) {
                $query->where('gudang_id', $gudang->id);
            })->count();

            // Hitung total barang dari PJ Stok untuk gudang tertentu
            $totalBarang = PjStok::where('id_gudang', $gudang->id)->sum('stok');
        }

        return response()->json([
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    /**
     * Filter Bagian berdasarkan rentang waktu
     * FIXED: Dari transaksi_barang_keluar dengan bagian
     */
    private function filterBagianData($filter)
    {
        // Ambil semua bagian yang pernah menerima barang (baseline labels)
        $allTimeBagian = TransaksiBarangKeluar::whereNotNull('bagian_id')
            ->select('bagian_id')
            ->groupBy('bagian_id')
            ->with('bagian')
            ->get()
            ->map(fn($row) => optional($row->bagian)->nama ?? 'Tidak Diketahui')
            ->filter(fn($nama) => !preg_match('/^(umum|gudang|operasional)$/i', $nama))
            ->unique()
            ->sort()
            ->values();

        // Query dengan filter waktu
        $q = TransaksiBarangKeluar::whereNotNull('bagian_id');

        $start = null;
        $end = null;

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

        // Hitung total per bagian
        $keluarData = [];
        foreach ($allTimeBagian as $bagianNama) {
            // Cari bagian_id dari nama
            $bagianIds = \App\Models\Bagian::where('nama', $bagianNama)
                ->pluck('id')
                ->toArray();

            $total = (clone $q)
                ->whereIn('bagian_id', $bagianIds)
                ->sum('jumlah');

            $keluarData[] = (int) $total;
        }

        return response()->json([
            'labels' => $allTimeBagian,
            'keluar' => $keluarData,
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    /**
     * Filter Pengeluaran per Tahun (dalam total harga)
     * FIXED: Dari transaksi_distribusi
     */
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
            // Hitung total harga: SUM(jumlah * harga_barang)
            $totalHarga = TransaksiDistribusi::join('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->whereYear('transaksi_distribusi.tanggal', $y)
                ->sum(DB::raw('transaksi_distribusi.jumlah * barang.harga_barang'));

            $totals[] = (float) $totalHarga;
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