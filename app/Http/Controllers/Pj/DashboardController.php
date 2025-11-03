<?php

namespace App\Http\Controllers\Pj;

use App\Helpers\MenuHelper;
use App\Http\Controllers\Controller;
use App\Models\TransaksiBarangKeluar;
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

        $mode = $gudang ? 'gudang' : 'bagian';

        if ($mode === 'gudang') {
            $contextForView = $gudang; // dipakai di blade sebagai $gudang->nama
            $pageTitle      = $this->getDashboardTitle($gudang->nama);

            // ringkasan
            $totalJenisBarang = $this->hitungJenisBarangByGudang($gudang->id);
            $totalBarang      = PjStok::where('id_gudang', $gudang->id)->sum('stok');

            // grafik per bagian (gudang ini saja)
            [$bagianLabels, $keluarData] = $this->buildBagianChartForGudang($gudang->id);

            // pengeluaran per tahun (gudang ini)
            [$years, $pengeluaranLabels, $pengeluaranData, $colorsForYears] =
                $this->buildPengeluaranPerTahunForGudang($gudang->id);

        } else {
            // MODE BAGIAN (PBP)
            $contextForView   = (object) ['nama' => 'Bagian ' . $bagian->nama]; // agar blade tetap aman
            $pageTitle        = 'Dashboard - Bagian ' . $bagian->nama;

            // ringkasan (stok_bagian)
            $totalJenisBarang = $this->hitungJenisBarangByBagian($bagian->id);
            $totalBarang      = StokBagian::where('bagian_id', $bagian->id)->sum('stok');

            // grafik per bagian (semua gudang digabung)
            [$bagianLabels, $keluarData] = $this->buildBagianChartAllGudang();

            // pengeluaran per tahun untuk bagian user (lintas gudang)
            [$years, $pengeluaranLabels, $pengeluaranData, $colorsForYears] =
                $this->buildPengeluaranPerTahunForBagian($bagian->id);
        }

        return view('staff.pj.dashboard', [
            'menu'              => $menu,
            'pageTitle'         => $pageTitle,
            'totalJenisBarang'  => $totalJenisBarang,
            'totalBarang'       => $totalBarang,
            'gudang'            => $contextForView, // blade pakai $gudang->nama
            'bagianLabels'      => $bagianLabels,
            'keluarData'        => $keluarData,
            'years'             => $years,
            'pengeluaranLabels' => $pengeluaranLabels,
            'pengeluaranData'   => $pengeluaranData,
            'colorsForYears'    => $colorsForYears,
        ]);
    }

    /* ========================= AJAX FILTER ========================= */
    public function filterData(Request $request)
    {
        $type   = $request->query('type', 'bagian');    // 'bagian' | 'pengeluaran'
        $filter = $request->query('filter', 'all');     // 'all' | 'week' | 'month' | 'year' | '5y' | '7y' | '10y'

        $user   = auth()->user();
        $gudang = $this->getGudangForUser($user);
        $bagian = $this->getBagianForUser($user);

        if (!$gudang && !$bagian) {
            return response()->json(['error' => 'Bagian tidak ditemukan'], 404);
        }

        // MODE BAGIAN
        if (!$gudang && $bagian) {
            if ($type === 'bagian') {
                return $this->filterBagianDataAllGudang($filter);
            }
            return $this->filterPengeluaranDataForBagian($filter, $bagian);
        }

        // MODE GUDANG (legacy)
        if ($type === 'bagian') {
            return $this->filterBagianDataForGudang($filter, $gudang);
        }
        return $this->filterPengeluaranDataForGudang($filter, $gudang);
    }

    /* ========================= BUILDERS ========================= */

    private function buildBagianChartForGudang(int $gudangId): array
    {
        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
            ->orderBy('id')->get();

        $labels = $allBagian->pluck('nama')->toArray();
        $data   = [];

        foreach ($allBagian as $b) {
            $total = TransaksiBarangKeluar::where('id_gudang', $gudangId)
                ->where('bagian_id', $b->id)
                ->sum('jumlah');
            $data[] = (int) $total;
        }
        return [$labels, $data];
    }

    // Untuk mode BAGIAN: agregasi semua gudang
    private function buildBagianChartAllGudang(): array
    {
        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
            ->orderBy('id')->get();

        $labels = $allBagian->pluck('nama')->toArray();
        $data   = [];

        foreach ($allBagian as $b) {
            $total = TransaksiBarangKeluar::where('bagian_id', $b->id)->sum('jumlah');
            $data[] = (int) $total;
        }
        return [$labels, $data];
    }

    private function buildPengeluaranPerTahunForGudang(int $gudangId): array
    {
        $currentYear = (int) date('Y');

        $years = TransaksiBarangKeluar::selectRaw('YEAR(tanggal) as y')
            ->where('id_gudang', $gudangId)
            ->groupBy('y')->orderBy('y')->pluck('y')->toArray();
        if (empty($years)) $years = [$currentYear];

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

    private function buildPengeluaranPerTahunForBagian(int $bagianId): array
    {
        $currentYear = (int) date('Y');

        $years = TransaksiBarangKeluar::selectRaw('YEAR(tanggal) as y')
            ->where('bagian_id', $bagianId)
            ->groupBy('y')->orderBy('y')->pluck('y')->toArray();
        if (empty($years)) $years = [$currentYear];

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

    private function filterBagianDataForGudang(string $filter, Gudang $gudang)
    {
        [$start, $end] = $this->resolveDateRange($filter);

        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
            ->orderBy('id')->get();

        $labels = $allBagian->pluck('nama')->toArray();
        $data   = [];

        foreach ($allBagian as $b) {
            $q = TransaksiBarangKeluar::where('id_gudang', $gudang->id)
                ->where('bagian_id', $b->id);
            if ($start && $end) $q->whereBetween('tanggal', [$start, $end]);
            $data[] = (int) $q->sum('jumlah');
        }

        return response()->json([
            'labels' => $labels,
            'keluar' => $data,
            'range'  => $start && $end ? ['start' => $start, 'end' => $end] : null,
        ]);
    }

    private function filterBagianDataAllGudang(string $filter)
    {
        [$start, $end] = $this->resolveDateRange($filter);

        $allBagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
            ->orderBy('id')->get();

        $labels = $allBagian->pluck('nama')->toArray();
        $data   = [];

        foreach ($allBagian as $b) {
            $q = TransaksiBarangKeluar::where('bagian_id', $b->id);
            if ($start && $end) $q->whereBetween('tanggal', [$start, $end]);
            $data[] = (int) $q->sum('jumlah');
        }

        return response()->json([
            'labels' => $labels,
            'keluar' => $data,
            'range'  => $start && $end ? ['start' => $start, 'end' => $end] : null,
        ]);
    }

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

    // Range waktu untuk filter grafik per bagian
    private function resolveDateRange(string $filter): array
    {
        $today = now()->startOfDay();
        switch ($filter) {
            case 'week':
                return [$today->copy()->subDays(6)->toDateString(), $today->toDateString()];
            case 'month':
                return [$today->copy()->subDays(29)->toDateString(), $today->toDateString()];
            case 'year':
                return [$today->copy()->startOfYear()->toDateString(), $today->copy()->endOfYear()->toDateString()];
            default:
                return [null, null];
        }
    }

    // Range tahun untuk filter pengeluaran chart
    private function resolveYearRange(string $filter): array
    {
        $currentYear = (int) date('Y');
        switch ($filter) {
            case '5y':  return range($currentYear - 4, $currentYear);
            case '7y':  return range($currentYear - 6, $currentYear);
            case '10y': return range($currentYear - 9, $currentYear);
            default:
                $years = TransaksiBarangKeluar::selectRaw('YEAR(tanggal) as y')
                    ->groupBy('y')->orderBy('y')->pluck('y')->toArray();
                return !empty($years) ? $years : [$currentYear];
        }
    }
}
