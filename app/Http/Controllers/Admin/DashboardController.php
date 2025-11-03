<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Barang;
use App\Models\Bagian;
use App\Models\PbStok;
use App\Models\StokBagian;
use App\Models\TransaksiBarangKeluar;
use App\Models\TransaksiDistribusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::adminMenu();

        // Ringkasan Default: Stok Global (PB Stok)
        $totalJenisBarang = $this->hitungJenisBarangGlobal();
        $totalBarang = PbStok::sum('stok');

        // Data bagian untuk dropdown filter (semua bagian)
        $bagians = Bagian::orderBy('nama')->get();

        /* =========================================================
         * GRAFIK PER BAGIAN
         * =========================================================
         * - Masuk: dari transaksi_distribusi (Stok Global → Bagian)
         * - Keluar: dari transaksi_barang_keluar (Bagian → Individu)
         */

        $bagianLabels = [];
        $masukData = [];
        $keluarData = [];

        foreach ($bagians as $bagian) {
            // Gunakan nama pendek untuk label
            $bagianLabels[] = $this->shortenBagianName($bagian->nama);

            // MASUK: dari transaksi_distribusi ke bagian ini
            $masuk = TransaksiDistribusi::where('bagian_id', $bagian->id)
                ->sum('jumlah');
            $masukData[] = (int) $masuk;

            // KELUAR: dari transaksi_barang_keluar dari bagian ini
            $keluar = TransaksiBarangKeluar::where('bagian_id', $bagian->id)
                ->sum('jumlah');
            $keluarData[] = (int) $keluar;
        }   

        /* =========================================================
         * GRAFIK PENGELUARAN PER TAHUN (DALAM TOTAL HARGA)
         * =========================================================
         * Perhitungan: SUM(transaksi_distribusi.jumlah * transaksi_distribusi.harga)
         * Harga sudah tersimpan di transaksi_distribusi saat distribusi
         */
        $currentYear = (int) date('Y');
        $years = range($currentYear - 9, $currentYear);
        $pengeluaranLabels = $years;

        $colorsForYears = [];
        $colorsForYearsOrdered = [];
        $totalsPerYear = [];

        foreach ($years as $y) {
            // Hitung total harga dari distribusi
            // Harga sudah ada di kolom transaksi_distribusi.harga
            $totalHarga = TransaksiDistribusi::whereYear('tanggal', $y)
                ->sum(DB::raw('jumlah * COALESCE(harga, 0)'));

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
            'bagians',
            'bagianLabels',
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
     * Filter Ringkasan berdasarkan bagian
     */
    private function filterRingkasanData($bagianNama)
    {
        // Special case: Stok Global (PB)
        if ($bagianNama === 'global' || $bagianNama === 'all') {
            $totalJenisBarang = $this->hitungJenisBarangGlobal();
            $totalBarang = PbStok::sum('stok');

            return response()->json([
                'totalJenisBarang' => (int) $totalJenisBarang,
                'totalBarang' => (int) $totalBarang
            ]);
        }

        // Cari bagian berdasarkan nama
        $bagian = Bagian::where('nama', $bagianNama)->first();

        if (!$bagian) {
            return response()->json([
                'totalJenisBarang' => 0,
                'totalBarang' => 0
            ]);
        }

        // Hitung jenis barang untuk bagian tertentu
        $totalJenisBarang = $this->hitungJenisBarangByBagian($bagian->id);

        // Hitung total stok barang untuk bagian tertentu
        $totalBarang = StokBagian::where('bagian_id', $bagian->id)->sum('stok');

        return response()->json([
            'totalJenisBarang' => (int) $totalJenisBarang,
            'totalBarang' => (int) $totalBarang
        ]);
    }

    /**
     * Hitung jenis barang unik dari stok global (pb_stok)
     * Pensil 1, Pensil 2 → dihitung sebagai 1 jenis (Pensil)
     */
    private function hitungJenisBarangGlobal()
    {
        $kodeBarangList = PbStok::where('stok', '>', 0)
            ->pluck('kode_barang')
            ->unique(); // Tambah unique karena sekarang bisa ada duplicate kode_barang

        if ($kodeBarangList->isEmpty()) {
            return 0;
        }

        // Ambil nama barang
        $namaBarangList = Barang::whereIn('kode_barang', $kodeBarangList)
            ->pluck('nama_barang');

        // Extract nama dasar (hilangkan angka di belakang)
        // Contoh: "Pensil 1" → "Pensil", "Pensil 2" → "Pensil"
        $namaUnik = $namaBarangList->map(function ($nama) {
            $nama = trim($nama);
            $namaBase = preg_replace('/\s+\d+$/', '', $nama);
            return empty($namaBase) ? $nama : $namaBase;
        })->unique()->filter();

        return $namaUnik->count();
    }

    /**
     * Hitung jenis barang unik berdasarkan bagian
     * Pensil 1, Pensil 2 → dihitung sebagai 1 jenis (Pensil)
     */
    private function hitungJenisBarangByBagian($bagianId)
    {
        $kodeBarangList = StokBagian::where('bagian_id', $bagianId)
            ->where('stok', '>', 0)
            ->pluck('kode_barang')
            ->unique();

        if ($kodeBarangList->isEmpty()) {
            return 0;
        }

        // Ambil nama barang
        $namaBarangList = Barang::whereIn('kode_barang', $kodeBarangList)
            ->pluck('nama_barang');

        // Extract nama dasar (hilangkan angka di belakang)
        // Contoh: "Pensil 1" → "Pensil", "Pensil 2" → "Pensil"
        $namaUnik = $namaBarangList->map(function ($nama) {
            $nama = trim($nama);
            $namaBase = preg_replace('/\s+\d+$/', '', $nama);
            return empty($namaBase) ? $nama : $namaBase;
        })->unique()->filter();

        return $namaUnik->count();
    }

    /**
     * Filter Bagian berdasarkan rentang waktu
     */
    private function filterBagianData($filter)
    {
        // Ambil semua bagian
        $bagians = Bagian::orderBy('nama')->get();

        $bagianLabels = $bagians->pluck('nama')->values();

        // Query dengan filter waktu
        $qMasuk = TransaksiDistribusi::query();
        $qKeluar = TransaksiBarangKeluar::query();

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

        // Hitung total per bagian
        $masukData = [];
        $keluarData = [];

        foreach ($bagians as $bagian) {
            $masuk = (clone $qMasuk)
                ->where('bagian_id', $bagian->id)
                ->sum('jumlah');
            $masukData[] = (int) $masuk;

            $keluar = (clone $qKeluar)
                ->where('bagian_id', $bagian->id)
                ->sum('jumlah');
            $keluarData[] = (int) $keluar;
        }

        return response()->json([
            'labels' => $bagianLabels,
            'masuk' => $masukData,
            'keluar' => $keluarData,
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    /**
     * Filter Pengeluaran per Tahun (dalam total harga)
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
            // Hitung total harga dari distribusi
            // Harga sudah ada di transaksi_distribusi.harga
            $totalHarga = TransaksiDistribusi::whereYear('tanggal', $y)
                ->sum(DB::raw('jumlah * COALESCE(harga, 0)'));

            $totals[] = (float) $totalHarga;
            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data' => $totals,
            'colors' => $colors,
        ]);
    }

    private function getColorForYear($year)
    {
        $palette = ['#8B5CF6', '#F87171', '#06B6D4', '#10B981', '#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int) $year) % count($palette);
        if ($idx < 0)
            $idx += count($palette);
        return $palette[$idx];
    }
    private function shortenBagianName($nama)
    {
        $shortNames = [
            'Tata Pemerintahan' => 'Tata Pemerintahan',
            'Kesejahteraan Rakyat & Kemasyarakatan' => 'Kesra',
            'Hukum & HAM' => 'Hukum & HAM',
            'ADM Pembangunan' => 'ADM Pembangunan',
            'Perekonomian' => 'Perekonomian',
            'ADM Pelayanan Pengadaan Barang & Jasa' => 'ADM Pengadaan',
            'Protokol' => 'Protokol',
            'Organisasi' => 'Organisasi',
            'Umum & Rumah Tangga' => 'Umum & RT',
            'Perencanaan & Keuangan' => 'Perencanaan'
        ];

        return $shortNames[$nama] ?? $nama;
    }

}