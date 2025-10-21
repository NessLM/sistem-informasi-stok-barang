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

        // Gudang Utama sebagai default
        $gudangUtama = Gudang::where('nama', 'Gudang Utama')->first();

        // Ringkasan (default: Gudang Utama)
        $totalJenisBarang = $this->hitungJenisBarangByGudang($gudangUtama->id);
        $totalBarang = PbStok::sum('stok'); // Stok dari Pengelola Barang (Gudang Utama)

        // Data gudang untuk dropdown filter
        $gudangs = Gudang::orderBy('nama')->get();

        /* =========================================================
         * GRAFIK PER GUDANG (HANYA GUDANG PJ)
         * =========================================================
         * - Masuk: dari transaksi_distribusi (PB → PJ)
         * - Keluar: dari transaksi_barang_keluar (PJ → Bagian)
         */

        // Ambil gudang PJ (exclude Gudang Utama)
        $gudangPj = Gudang::where('nama', '!=', 'Gudang Utama')
            ->orderBy('nama')
            ->get();

        $gudangLabels = $gudangPj->pluck('nama')->values();
        $masukData = [];
        $keluarData = [];

        foreach ($gudangPj as $gudang) {
            // MASUK: dari transaksi_distribusi ke gudang ini
            $masuk = TransaksiDistribusi::where('id_gudang_tujuan', $gudang->id)
                ->sum('jumlah');
            $masukData[] = (int) $masuk;

            // KELUAR: dari transaksi_barang_keluar dari gudang ini
            $keluar = TransaksiBarangKeluar::where('id_gudang', $gudang->id)
                ->whereNotNull('bagian_id')
                ->sum('jumlah');
            $keluarData[] = (int) $keluar;
        }

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
            'gudangUtama',
            'gudangLabels',
            'masukData',
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
        $type = $request->query('type', 'gudang');
        $filter = $request->query('filter', 'all');

        if ($type === 'ringkasan') {
            return $this->filterRingkasanData($filter);
        }

        return $type === 'gudang'
            ? $this->filterGudangData($filter)
            : $this->filterPengeluaranData($filter);
    }

    /**
     * Filter Ringkasan berdasarkan gudang
     * FIXED: Hitung jenis barang unik dari tabel barang
     */
    private function filterRingkasanData($gudangNama)
    {
        $gudang = Gudang::where('nama', $gudangNama)->first();

        if (!$gudang) {
            return response()->json([
                'totalJenisBarang' => 0,
                'totalBarang' => 0
            ]);
        }

        // Hitung total jenis barang berdasarkan gudang
        $totalJenisBarang = $this->hitungJenisBarangByGudang($gudang->id);

        // Hitung total barang
        if ($gudang->nama === 'Gudang Utama') {
            // Gudang Utama = ambil dari PB Stok
            $totalBarang = PbStok::sum('stok');
        } else {
            // Gudang lain = ambil dari PJ Stok
            $totalBarang = PjStok::where('id_gudang', $gudang->id)->sum('stok');
        }

        return response()->json([
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    /**
     * Hitung jenis barang unik berdasarkan gudang
     * Logika: 
     * - Gudang Utama: ambil dari pb_stok → join ke barang → extract nama unik
     * - Gudang Kecil: ambil dari pj_stok → join ke barang → extract nama unik
     */
    private function hitungJenisBarangByGudang($gudangId)
    {
        $gudang = Gudang::find($gudangId);

        if (!$gudang) {
            return 0;
        }

        // Gudang Utama = ambil dari pb_stok
        if ($gudang->nama === 'Gudang Utama') {
            $kodeBarangList = PbStok::where('stok', '>', 0)
                ->pluck('kode_barang');
        } else {
            // Gudang Kecil = ambil dari pj_stok berdasarkan id_gudang
            $kodeBarangList = PjStok::where('id_gudang', $gudangId)
                ->where('stok', '>', 0)
                ->pluck('kode_barang');
        }

        // Kalau ga ada barang, return 0
        if ($kodeBarangList->isEmpty()) {
            return 0;
        }

        // Ambil nama barang dari kode_barang
        $namaBarangList = Barang::whereIn('kode_barang', $kodeBarangList)
            ->pluck('nama_barang');

        // Extract nama dasar (hilangkan angka di belakang)
        $namaUnik = $namaBarangList->map(function ($nama) {
            // Trim whitespace dulu
            $nama = trim($nama);

            // Hilangkan angka di akhir: "Pulpen 1" → "Pulpen", "Buku Catatan 2" → "Buku Catatan"
            // Pattern: spasi + angka di akhir string
            $namaBase = preg_replace('/\s+\d+$/', '', $nama);

            // Kalau masih kosong atau sama, return nama asli
            return empty($namaBase) ? $nama : $namaBase;
        })->unique()->filter(); // filter() untuk buang yang kosong

        return $namaUnik->count();
    }

    /**
     * Filter Gudang berdasarkan rentang waktu
     * FIXED: Dari transaksi_distribusi (masuk) & transaksi_barang_keluar (keluar)
     */
    private function filterGudangData($filter)
    {
        // Ambil semua gudang PJ (exclude Gudang Utama)
        $gudangPj = Gudang::where('nama', '!=', 'Gudang Utama')
            ->orderBy('nama')
            ->get();

        $gudangLabels = $gudangPj->pluck('nama')->values();

        // Query dengan filter waktu
        $qMasuk = TransaksiDistribusi::query();
        $qKeluar = TransaksiBarangKeluar::whereNotNull('bagian_id');

        $start = null;
        $end = null;

        if ($filter === 'week') {
            $start = Carbon::now()->subWeek();
            $qMasuk->where('tanggal', '>=', $start);
            $qKeluar->where('tanggal', '>=', $start);
        } elseif ($filter === 'month') {
            $start = Carbon::now()->subMonth();
            $qMasuk->where('tanggal', '>=', $start);
            $qKeluar->where('tanggal', '>=', $start);
        } elseif ($filter === 'year') {
            $start = Carbon::now()->subYear();
            $qMasuk->where('tanggal', '>=', $start);
            $qKeluar->where('tanggal', '>=', $start);
        }
        $end = Carbon::now();

        // Hitung total per gudang
        $masukData = [];
        $keluarData = [];

        foreach ($gudangPj as $gudang) {
            // MASUK: dari transaksi_distribusi
            $masuk = (clone $qMasuk)
                ->where('id_gudang_tujuan', $gudang->id)
                ->sum('jumlah');
            $masukData[] = (int) $masuk;

            // KELUAR: dari transaksi_barang_keluar
            $keluar = (clone $qKeluar)
                ->where('id_gudang', $gudang->id)
                ->sum('jumlah');
            $keluarData[] = (int) $keluar;
        }

        return response()->json([
            'labels' => $gudangLabels,
            'masuk' => $masukData,
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