<?php

namespace App\Http\Controllers\Pj;

use App\Helpers\MenuHelper;
use App\Http\Controllers\Controller;
use App\Models\TransaksiBarangKeluar;
use App\Models\TransaksiDistribusi; // dipakai untuk "Masuk" di mode gudang (PB -> PJ)
use App\Models\Gudang;
use App\Models\Bagian;
use App\Models\PjStok;
use App\Models\StokBagian;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class DashboardController extends Controller
{
    /**
     * PJ Dashboard context-aware:
     * - punya gudang_id  -> mode gudang (legacy)
     * - punya bagian_id  -> mode bagian (baru)
     * - tidak ada keduanya -> 403 (bagian)
     */
    public function __invoke()
    {
        $menu   = MenuHelper::pjMenu();
        $user   = auth()->user();

        $gudang = $this->getGudangForUser($user);
        $bagian = $this->getBagianForUser($user);

        if (!$gudang && !$bagian) {
            abort(403, 'Anda tidak memiliki akses ke bagian manapun.');
        }

        $mode    = $gudang ? 'gudang' : 'bagian';
        $yearNow = (int) date('Y');

        if ($mode === 'gudang') {
            $contextForView = $gudang; // dipakai di blade sebagai $gudang->nama
            $pageTitle      = $this->getDashboardTitle($gudang->nama);

            // Ringkasan
            $totalJenisBarang = $this->hitungJenisBarangByGudang($gudang->id);
            $totalBarang      = PjStok::where('id_gudang', $gudang->id)->sum('stok');

            // ===== Grafik Bulanan (Jan–Des) =====
            [$monthlyLabels, $monthlyMasuk, $monthlyKeluar] = $this->buildMonthlyForGudang($gudang->id, $yearNow);
            $monthlyRangeText = 'Jan–Des ' . $yearNow;

            // ===== Pengeluaran per Tahun (default 9 tahun terakhir) =====
            [$years, $pengeluaranLabels, $pengeluaranData, $colorsForYears] =
                $this->buildPengeluaranPerTahunForGudang($gudang->id);
        } else {
            // MODE BAGIAN
            $contextForView   = (object) ['nama' => 'Bagian ' . $bagian->nama];
            $pageTitle        = 'Dashboard - Bagian ' . $bagian->nama;

            // Ringkasan (stok_bagian)
            $totalJenisBarang = $this->hitungJenisBarangByBagian($bagian->id);
            $totalBarang      = StokBagian::where('bagian_id', $bagian->id)->sum('stok');

            // ===== Grafik Bulanan (Jan–Des) =====
            // FIX: untuk BAGIAN, “Keluar” yang diambil dari TBK; “Masuk” = 0.
            [$monthlyLabels, $monthlyMasuk, $monthlyKeluar] = $this->buildMonthlyForBagian($bagian->id, $yearNow);
            $monthlyRangeText = 'Jan–Des ' . $yearNow;

            // ===== Pengeluaran per Tahun (default 9 tahun terakhir) =====
            [$years, $pengeluaranLabels, $pengeluaranData, $colorsForYears] =
                $this->buildPengeluaranPerTahunForBagian($bagian->id);
        }

        return view('staff.pj.dashboard', [
            'menu'              => $menu,
            'pageTitle'         => $pageTitle,
            'totalJenisBarang'  => $totalJenisBarang,
            'totalBarang'       => $totalBarang,
            'gudang'            => $contextForView,

            // === DATA BARU untuk grafik bulanan ===
            'monthlyLabels'     => $monthlyLabels,
            'monthlyMasuk'      => $monthlyMasuk,
            'monthlyKeluar'     => $monthlyKeluar,
            'monthlyRangeText'  => $monthlyRangeText,

            // === Data grafik tahunan (tetap) ===
            'years'             => $years,
            'pengeluaranLabels' => $pengeluaranLabels,
            'pengeluaranData'   => $pengeluaranData,
            'colorsForYears'    => $colorsForYears,
        ]);
    }

    /* ========================= AJAX FILTER ========================= */
    public function filterData(Request $request)
    {
        // DEFAULT sekarang: monthly (grafik bulanan); opsi lain 'pengeluaran'
        $type   = $request->query('type', 'monthly');  // 'monthly' | 'pengeluaran'
        // monthly: 'all' | '3m' | '5m'
        // pengeluaran: 'all' | '5y' | '7y' | '10y'
        $filter = $request->query('filter', 'all');

        $user   = auth()->user();
        $gudang = $this->getGudangForUser($user);
        $bagian = $this->getBagianForUser($user);

        if (!$gudang && !$bagian) {
            return response()->json(['error' => 'Bagian tidak ditemukan'], 404);
        }

        // ====== BULANAN ======
        if ($type === 'monthly') {
            return $this->filterMonthly($filter, $gudang, $bagian);
        }

        // ====== TAHUNAN ======
        if (!$gudang && $bagian) {
            return $this->filterPengeluaranDataForBagian($filter, $bagian);
        }
        return $this->filterPengeluaranDataForGudang($filter, $gudang);
    }

    /* ========================= BUILDERS ========================= */

    /** Label bulan pendek (Jan–Des) */
    private function monthLabels(): array
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    }

    /**
     * Grafik bulanan untuk MODE GUDANG:
     * - Masuk  = TransaksiDistribusi ke gudang ini (PB -> PJ)
     * - Keluar = TransaksiBarangKeluar dari gudang ini
     */
    private function buildMonthlyForGudang(int $gudangId, int $year): array
    {
        $labels = $this->monthLabels();
        $masuk  = array_fill(0, 12, 0);
        $keluar = array_fill(0, 12, 0);

        // MASUK (Distribusi ke gudang)
        $rowsMasuk = TransaksiDistribusi::selectRaw('MONTH(COALESCE(tanggal, created_at)) as m, SUM(jumlah) as total')
            ->where('id_gudang_tujuan', $gudangId)
            ->whereYear(DB::raw('COALESCE(tanggal, created_at)'), $year)
            ->groupBy('m')->pluck('total', 'm')->toArray();
        foreach ($rowsMasuk as $m => $t) $masuk[$m - 1] = (int) $t;

        // KELUAR (Keluar dari gudang)
        $rowsKeluar = TransaksiBarangKeluar::selectRaw('MONTH(COALESCE(tanggal, created_at)) as m, SUM(jumlah) as total')
            ->where('id_gudang', $gudangId)
            ->whereYear(DB::raw('COALESCE(tanggal, created_at)'), $year)
            ->groupBy('m')->pluck('total', 'm')->toArray();
        foreach ($rowsKeluar as $m => $t) $keluar[$m - 1] = (int) $t;

        return [$labels, $masuk, $keluar];
    }

    /**
     * Grafik bulanan untuk MODE BAGIAN:
     * **FIX**: Bagian cuma punya *Keluar* (asal dari TBK, field `bagian_id`).
     * - Keluar = SUM(TBK.jumlah) per bulan untuk bagian_id ini
     * - Masuk  = 0 (disediakan supaya dataset & legend kompatibel)
     */
    private function buildMonthlyForBagian(int $bagianId, int $year): array
    {
        $labels = $this->monthLabels();
        $masuk  = array_fill(0, 12, 0);
        $keluar = array_fill(0, 12, 0);

        // MASUK = Distribusi PB -> Bagian ini (tanpa join gudang)
        $rowsMasuk = TransaksiDistribusi::query()
            ->selectRaw('MONTH(COALESCE(tanggal, created_at)) as m, SUM(jumlah) as total')
            ->where('bagian_id', $bagianId)
            ->whereYear(DB::raw('COALESCE(tanggal, created_at)'), $year)
            ->groupBy('m')
            ->pluck('total', 'm')
            ->toArray();

        foreach ($rowsMasuk as $m => $t) {
            $masuk[max(1, min(12, (int)$m)) - 1] = (int) $t;
        }

        // KELUAR = TBK milik bagian ini (tetap)
        $rowsKeluar = TransaksiBarangKeluar::query()
            ->selectRaw('MONTH(COALESCE(tanggal, created_at)) as m, SUM(jumlah) as total')
            ->where('bagian_id', $bagianId)
            ->whereYear(DB::raw('COALESCE(tanggal, created_at)'), $year)
            ->groupBy('m')
            ->pluck('total', 'm')
            ->toArray();

        foreach ($rowsKeluar as $m => $t) {
            $keluar[max(1, min(12, (int)$m)) - 1] = (int) $t;
        }

        return [$labels, $masuk, $keluar];
    }



    // ====== (SISA) BUILDER BAR lama disimpan untuk kompatibilitas ======
    private function buildBagianChartForGudang(int $gudangId): array
    {
        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])->orderBy('id')->get();
        $labels = $allBagian->pluck('nama')->toArray();
        $data   = [];
        foreach ($allBagian as $b) {
            $total = TransaksiBarangKeluar::where('id_gudang', $gudangId)->where('bagian_id', $b->id)->sum('jumlah');
            $data[] = (int) $total;
        }
        return [$labels, $data];
    }

    private function buildBagianChartAllGudang(): array
    {
        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])->orderBy('id')->get();
        $labels = $allBagian->pluck('nama')->toArray();
        $data   = [];
        foreach ($allBagian as $b) {
            $total = TransaksiBarangKeluar::where('bagian_id', $b->id)->sum('jumlah');
            $data[] = (int) $total;
        }
        return [$labels, $data];
    }

    /** DEFAULT sekarang selalu 9 tahun terakhir (termasuk kalau 0) */
    private function buildPengeluaranPerTahunForGudang(int $gudangId): array
    {
        $currentYear = (int) date('Y');
        $years  = range($currentYear - 8, $currentYear); // 9 tahun terakhir

        $labels = $years;
        $totals = [];
        $colors = [];

        foreach ($years as $y) {
            $totals[] = (int) TransaksiBarangKeluar::where('id_gudang', $gudangId)
                ->whereYear('tanggal', $y)->sum('jumlah');
            $colors[$y] = $this->getColorForYear($y);
        }

        $dataset = [[
            'label' => 'Keluar',
            'data'  => $totals,
            'backgroundColor' => array_map(fn($yy) => $colors[$yy], $years),
            'borderRadius'    => 4,
        ]];

        return [$years, $labels, $dataset, $colors];
    }

    /** DEFAULT sekarang selalu 9 tahun terakhir (termasuk kalau 0) */
    private function buildPengeluaranPerTahunForBagian(int $bagianId): array
    {
        $currentYear = (int) date('Y');
        $years  = range($currentYear - 8, $currentYear); // 9 tahun terakhir

        $labels = $years;
        $totals = [];
        $colors = [];

        foreach ($years as $y) {
            $totals[] = (int) TransaksiBarangKeluar::where('bagian_id', $bagianId)
                ->whereYear('tanggal', $y)->sum('jumlah');
            $colors[$y] = $this->getColorForYear($y);
        }

        $dataset = [[
            'label' => 'Keluar',
            'data'  => $totals,
            'backgroundColor' => array_map(fn($yy) => $colors[$yy], $years),
            'borderRadius'    => 4,
        ]];

        return [$years, $labels, $dataset, $colors];
    }

    /* ========================= FILTER HELPERS ========================= */

    /** Filter untuk grafik BULANAN (window: all | 3m | 5m) */
    private function filterMonthly(string $filter, ?Gudang $gudang, ?Bagian $bagian)
    {
        $year = (int) date('Y');

        if ($gudang) {
            [$labels, $masuk, $keluar] = $this->buildMonthlyForGudang($gudang->id, $year);
        } else {
            [$labels, $masuk, $keluar] = $this->buildMonthlyForBagian($bagian->id, $year);
        }

        // window 3/5 bulan terakhir: contoh Nov -> Sep–Nov atau Jul–Nov
        $curr = (int) date('n'); // 1..12
        if (in_array($filter, ['3m', '5m'], true)) {
            $n     = $filter === '3m' ? 3 : 5;
            $start = max(1, $curr - $n + 1);
            $len   = min($n, $curr - $start + 1); // clamp ke awal tahun bila perlu

            $labels = array_slice($labels, $start - 1, $len);
            $masuk  = array_slice($masuk,  $start - 1, $len);
            $keluar = array_slice($keluar, $start - 1, $len);

            $rangeText = $labels[0] . '–' . end($labels) . ' ' . $year;
        } else {
            $rangeText = 'Jan–Des ' . $year;
        }

        return response()->json([
            'labels' => $labels,
            'masuk'  => $masuk,   // NOTE: untuk Bagian ini 0 semua (sesuai definisi di atas)
            'keluar' => $keluar,  // NOTE: untuk Bagian inilah yang terisi dari TBK
            'range'  => ['text' => $rangeText],
        ]);
    }

    // ====== FILTER TAHUNAN (tetap) ======
    private function filterPengeluaranDataForGudang(string $filter, Gudang $gudang)
    {
        $years  = $this->resolveYearRange($filter);
        $totals = [];
        $colors = [];

        foreach ($years as $y) {
            $totals[] = (int) TransaksiBarangKeluar::where('id_gudang', $gudang->id)
                ->whereYear('tanggal', $y)->sum('jumlah');
            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data'   => $totals,
            'colors' => $colors,
        ]);
    }

    private function filterPengeluaranDataForBagian(string $filter, Bagian $bagian)
    {
        $years  = $this->resolveYearRange($filter);
        $totals = [];
        $colors = [];

        foreach ($years as $y) {
            $totals[] = (int) TransaksiBarangKeluar::where('bagian_id', $bagian->id)
                ->whereYear('tanggal', $y)->sum('jumlah');
            $colors[$y] = $this->getColorForYear($y);
        }

        return response()->json([
            'labels' => $years,
            'data'   => $totals,
            'colors' => $colors,
        ]);
    }

    /* ========================= SMALL HELPERS ========================= */

    private function hitungJenisBarangByGudang(int $gudangId): int
    {
        $namaBarangList = DB::table('pj_stok')
            ->join('barang', 'pj_stok.kode_barang', '=', 'barang.kode_barang')
            ->where('pj_stok.id_gudang', $gudangId)
            ->where('pj_stok.stok', '>', 0)
            ->pluck('barang.nama');

        $namaUnik = collect($namaBarangList)->map(function ($nama) {
            $nama = preg_replace('/\(.*?\)/', '', $nama);
            $nama = preg_replace('/\s*-\s*\S+$/', '', $nama);
            return trim($nama);
        })->unique()->filter();

        return $namaUnik->count();
    }

    private function hitungJenisBarangByBagian(int $bagianId): int
    {
        return (int) StokBagian::where('bagian_id', $bagianId)
            ->distinct('kode_barang')
            ->count('kode_barang');
    }

    private function getGudangForUser($user): ?Gudang
    {
        if (!$user) return null;

        if ($user->gudang_id) {
            return Gudang::find($user->gudang_id);
        }
        // Backward-compat role lama → gudang
        $roleName = $user->role->nama ?? null;
        $mapping = [
            'Penanggung Jawab ATK'            => 'Gudang ATK',
            'Penanggung Jawab Kebersihan'     => 'Gudang Kebersihan',
            'Penanggung Jawab Listrik'        => 'Gudang Listrik',
            'Penanggung Jawab Bahan Komputer' => 'Gudang B Komputer',
        ];
        $gudangName = $mapping[$roleName] ?? null;
        if (!$gudangName) return null;

        return Gudang::where('nama', $gudangName)->first();
    }

    private function getBagianForUser($user): ?Bagian
    {
        if (!$user) return null;
        if ($user->bagian_id) {
            return Bagian::find($user->bagian_id);
        }
        return null;
    }

    private function getDashboardTitle(string $gudangName): string
    {
        return "Dashboard - {$gudangName}";
    }

    private function getColorForYear($year)
    {
        $palette = ['#8B5CF6', '#F87171', '#06B6D4', '#10B981', '#F59E0B'];
        $currentYear = (int) date('Y');
        $idx = ($currentYear - (int) $year) % count($palette);
        if ($idx < 0) $idx += count($palette);
        return $palette[$idx];
    }

    // Range tahun untuk filter pengeluaran chart
    private function resolveYearRange(string $filter): array
    {
        $currentYear = (int) date('Y');
        switch ($filter) {
            case '5y':
                return range($currentYear - 4, $currentYear);
            case '7y':
                return range($currentYear - 6, $currentYear);
            case '10y':
                return range($currentYear - 9, $currentYear);
            default:
                return range($currentYear - 8, $currentYear); // DEFAULT: 9 tahun terakhir
        }
    }
}
