<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Barang;
use App\Models\Bagian;
use App\Models\Kategori;
use App\Models\PbStok;
use App\Models\TransaksiDistribusi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $menu = MenuHelper::pbMenu();

        // Ringkasan - Total Jenis Barang dan Total Stok dari pb_stok
        $totalJenisBarang = $this->hitungJenisBarangDariPbStok();
        $totalBarang = PbStok::sum('stok');

        /* =========================================================
         * GRAFIK BARANG KELUAR PER BAGIAN (DISTRIBUSI KE BAGIAN)
         * Mengambil semua bagian dari database secara dinamis
         * =========================================================
         */
        $keluarPerKategoriLabels = [];
        $keluarPerKategoriData = [];

        // Ambil semua bagian dari database
        $allBagian = Bagian::orderBy('id')->get();

        foreach ($allBagian as $bagian) {
            // Hitung total distribusi ke bagian ini
            $total = TransaksiDistribusi::where('bagian_id', $bagian->id)
                ->sum('jumlah');

            // Singkat nama bagian jika terlalu panjang
            $labelName = $this->shortenBagianName($bagian->nama);

            $keluarPerKategoriLabels[] = $labelName;
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
            // Hitung total harga dari transaksi_distribusi
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

        if ($type === 'kategori') {
            return $this->filterKategoriData($filter);
        } else {
            return $this->filterPengeluaranData($filter);
        }
    }

    /**
     * Filter data kategori berdasarkan rentang waktu
     * Mengambil semua bagian dari database secara dinamis
     */
    private function filterKategoriData($filter)
    {
        $start = null;
        $end = null;

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
        $colors = [];

        // Ambil semua bagian dari database
        $allBagian = Bagian::orderBy('id')->get();

        foreach ($allBagian as $bagian) {
            $query = TransaksiDistribusi::where('bagian_id', $bagian->id);

            if ($start) {
                $query->where('tanggal', '>=', $start);
            }

            $total = $query->sum('jumlah');

            // Singkat nama bagian jika terlalu panjang
            $labelName = $this->shortenBagianName($bagian->nama);

            $labels[] = $labelName;
            $data[] = (int) $total;
            $colors[] = $this->getColorForIndex(count($labels) - 1);
        }

        return response()->json([
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
            'range' => $start ? ['start' => $start->toDateString(), 'end' => $end->toDateString()] : null,
        ]);
    }

    /**
     * Filter data pengeluaran per tahun
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

    /**
     * Hitung jenis barang unik dari pb_stok
     * Pensil 1, Pensil 2, Pensil 3 = 1 jenis (Pensil)
     * Pulpen 1, Pulpen 2 = 1 jenis (Pulpen)
     */
    private function hitungJenisBarangDariPbStok()
    {
        // Ambil semua kode_barang unik dari pb_stok yang stoknya > 0
        $kodeBarangList = PbStok::where('stok', '>', 0)
            ->distinct()
            ->pluck('kode_barang');

        if ($kodeBarangList->isEmpty()) {
            return 0;
        }

        // Ambil nama barang dari tabel barang berdasarkan kode_barang
        $namaBarangList = Barang::whereIn('kode_barang', $kodeBarangList)
            ->pluck('nama_barang');

        // Extract nama dasar (hilangkan angka di belakang)
        // "Pulpen 1" → "Pulpen"
        // "Pensil 2" → "Pensil"
        // "Buku Catatan 3" → "Buku Catatan"
        $namaUnik = $namaBarangList->map(function ($nama) {
            $nama = trim($nama);

            // Hilangkan angka di akhir nama
            // Pattern: hapus spasi + angka di akhir string
            $namaBase = preg_replace('/\s+\d+$/', '', $nama);

            // Jika hasil kosong, return nama asli
            return empty($namaBase) ? $nama : $namaBase;
        })
            ->unique()  // Ambil nilai unik
            ->filter()  // Hapus nilai kosong
            ->values(); // Reset index

        return $namaUnik->count();
    }

    /**
     * Singkat nama bagian untuk label grafik
     */
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

    /**
     * Warna berdasarkan index (untuk bagian dinamis)
     */
    private function getColorForIndex($index)
    {
        $colors = [
            '#3B82F6', // Biru
            '#10B981', // Hijau
            '#F59E0B', // Orange
            '#8B5CF6', // Ungu
            '#EF4444', // Merah
            '#06B6D4', // Cyan
            '#EC4899', // Pink
            '#F97316', // Orange terang
            '#14B8A6', // Teal
            '#A855F7', // Purple
            '#FB923C', // Orange muda
            '#34D399', // Hijau muda
        ];

        return $colors[$index % count($colors)];
    }

    /**
     * Warna untuk tahun
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