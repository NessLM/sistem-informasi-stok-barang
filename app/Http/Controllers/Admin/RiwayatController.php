<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Barang;
use App\Models\TransaksiDistribusi;
use App\Models\TransaksiBarangKeluar;
use App\Models\Gudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RiwayatExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class RiwayatController extends Controller
{
    /**
     * Map Transaksi Distribusi (dari PB ke PJ) menjadi row "Masuk"
     * Gudang: Gudang Tujuan (PJ)
     * Bagian: Dari mana asalnya (PB/Gudang Utama)
     */
    private function mapDistribusiToAdminRow(TransaksiDistribusi $item)
    {
        return (object) [
            'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'       => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Masuk',
            'gudang'      => optional($item->gudangTujuan)->nama ?? '-',
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah'      => (int) ($item->jumlah ?? 0),
            'bagian'      => 'Pengelola Barang', // Distribusi dari PB
            'bukti'       => $item->bukti,
            'bukti_path'  => $item->bukti ? asset('storage/' . $item->bukti) : null,
        ];
    }

    /**
     * Map Transaksi Barang Keluar (dari PJ ke Individu) menjadi row "Keluar"
     * Gudang: Gudang asal (PJ)
     * Bagian: Unit/Bagian penerima individu
     */
    private function mapBarangKeluarToAdminRow(TransaksiBarangKeluar $item)
    {
        return (object) [
            'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'       => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Keluar',
            'gudang'      => optional($item->gudang)->nama ?? '-',
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah'      => (int) ($item->jumlah ?? 0),
            'bagian'      => optional($item->bagian)->nama ?? '-',
            'penerima'    => $item->nama_penerima ?? '-',
            'bukti'       => $item->bukti,
            'bukti_path'  => $item->bukti ? asset('storage/' . $item->bukti) : null,
        ];
    }

    // =====================================================================
    // INDEX - Menampilkan Riwayat Masuk & Keluar
    // =====================================================================
    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();

        // Jika ada request download, langsung lempar
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // Lookup id gudang untuk filter (berdasarkan nama)
        $gudangIdForFilter = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $gudangIdForFilter = Gudang::where('nama', $request->gudang)->value('id');
        }

        // ---------------------------------------------------------------
        // MASUK: Transaksi Distribusi (dari PB ke PJ)
        // ---------------------------------------------------------------
        $distribusiQuery = TransaksiDistribusi::with([
            'barang.kategori.gudang',
            'gudangTujuan',
            'user',
        ]);

        // Filter gudang (gudang tujuan)
        if ($gudangIdForFilter) {
            $distribusiQuery->where('id_gudang_tujuan', $gudangIdForFilter);
        }

        // Filter periode
        $this->applyPeriodeFilter($distribusiQuery, $request);

        $riwayatMasuk = $distribusiQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapDistribusiToAdminRow($item))
            ->values()
            ->toBase();

        // ---------------------------------------------------------------
        // KELUAR: Transaksi Barang Keluar (dari PJ)
        // ---------------------------------------------------------------
        $keluarQuery = TransaksiBarangKeluar::with([
            'barang.kategori',
            'gudang',
            'user',
            'bagian'
        ]);

        // Filter gudang
        if ($gudangIdForFilter) {
            $keluarQuery->where('id_gudang', $gudangIdForFilter);
        }

        // Filter periode
        $this->applyPeriodeFilter($keluarQuery, $request);

        $riwayatKeluar = $keluarQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapBarangKeluarToAdminRow($item))
            ->values()
            ->toBase();

        // ---------------------------------------------------------------
        // Filter "Alur Barang"
        // ---------------------------------------------------------------
        $riwayat = collect()->toBase();
        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            $riwayat = $request->alur_barang === 'Masuk' ? $riwayatMasuk : $riwayatKeluar;
        } else {
            $riwayat = $riwayatMasuk->concat($riwayatKeluar);
        }

        // Urut berdasarkan tanggal & waktu terbaru
        $riwayat = $riwayat->sortByDesc(function ($x) {
            return ($x->tanggal ?? '1970-01-01') . ' ' . ($x->waktu ?? '00:00:00');
        })->values();

        // ---------------------------------------------------------------
        // Daftar gudang untuk filter
        // ---------------------------------------------------------------
        $gudangList = Gudang::orderBy('nama')->get()
            ->map(fn($g) => (object) ['gudang' => $g->nama])
            ->values();

        return view('staff.admin.riwayat', compact('riwayat', 'menu', 'gudangList'));
    }

    // =====================================================================
    // DOWNLOAD REPORT (PDF/Excel)
    // =====================================================================
    public function downloadReport(Request $request)
    {
        // Lookup id gudang untuk filter
        $gudangIdForFilter = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $gudangIdForFilter = Gudang::where('nama', $request->gudang)->value('id');
        }

        // MASUK: Distribusi
        $distribusiQuery = TransaksiDistribusi::with([
            'barang.kategori.gudang',
            'gudangTujuan',
            'user',
        ]);

        if ($gudangIdForFilter) {
            $distribusiQuery->where('id_gudang_tujuan', $gudangIdForFilter);
        }

        // KELUAR: Barang Keluar
        $keluarQuery = TransaksiBarangKeluar::with([
            'barang.kategori',
            'gudang',
            'user',
            'bagian'
        ]);

        if ($gudangIdForFilter) {
            $keluarQuery->where('id_gudang', $gudangIdForFilter);
        }

        // Filter periode untuk keduanya
        $this->applyPeriodeFilter($distribusiQuery, $request);
        $this->applyPeriodeFilter($keluarQuery, $request);

        // Map ke shape admin & gabungkan
        $rowsMasuk  = $distribusiQuery->get()->map(fn($item) => $this->mapDistribusiToAdminRow($item))->values()->toBase();
        $rowsKeluar = $keluarQuery->get()->map(fn($item) => $this->mapBarangKeluarToAdminRow($item))->values()->toBase();

        $riwayat = collect()->toBase();
        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            $riwayat = $request->alur_barang === 'Masuk' ? $rowsMasuk : $rowsKeluar;
        } else {
            $riwayat = $rowsMasuk->concat($rowsKeluar);
        }

        // Urut berdasarkan tanggal terbaru
        $riwayat = $riwayat->sortByDesc(function ($x) {
            return ($x->tanggal ?? '1970-01-01') . ' ' . ($x->waktu ?? '00:00:00');
        })->values();

        $filter = [
            'alur_barang'    => $request->alur_barang,
            'gudang'         => $request->gudang,
            'periode'        => $request->periode,
            'dari_tanggal'   => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal,
        ];

        // Export
        if ($request->download === 'excel') {
            return Excel::download(
                new RiwayatExport($riwayat, $filter),
                'riwayat-barang-' . date('Y-m-d_His') . '.xlsx'
            );
        }

        if ($request->download === 'pdf') {
            $pdf = Pdf::loadView('staff.admin.riwayat-pdf', compact('riwayat', 'filter'))
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('riwayat-barang-' . date('Y-m-d_His') . '.pdf');
        }

        return back()->with('error', 'Format tidak valid');
    }

    // =====================================================================
    // HELPER: Apply Filter Periode
    // =====================================================================
    private function applyPeriodeFilter($query, Request $request)
    {
        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $query->whereDate('tanggal', '>=', Carbon::now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $query->whereDate('tanggal', '>=', Carbon::now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $query->whereDate('tanggal', '>=', Carbon::now()->subYear());
                    break;
                case 'custom':
                    if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                        $query->whereBetween('tanggal', [
                            $request->dari_tanggal,
                            $request->sampai_tanggal
                        ]);
                    }
                    break;
            }
        }
    }
}
