<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Models\TransaksiDistribusi;
use App\Models\TransaksiBarangKeluar;
use App\Models\Gudang;
use App\Models\Bagian;
use App\Helpers\MenuHelper;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RiwayatExportPj;
use Carbon\Carbon;


class RiwayatController extends Controller
{
    /**
     * Map Transaksi Distribusi (dari PB ke PJ) menjadi row "Masuk"
     */
    private function mapDistribusiToPjRow(TransaksiDistribusi $item)
    {
        return (object) [
            'id'          => $item->id,
            'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'       => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Masuk',
            'gudang'      => optional($item->gudangTujuan)->nama ?? '-',
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'kode_barang' => optional($item->barang)->kode_barang ?? '-',
            'jumlah'      => (int) ($item->jumlah ?? 0),
            'satuan'      => optional($item->barang)->satuan ?? '-',
            'keterangan'  => $item->keterangan ?? 'Barang masuk',
            'bukti'       => $item->bukti,
            'bukti_path'  => $item->bukti ? asset('storage/' . $item->bukti) : null,
            'user'        => optional($item->user)->nama ?? '-',
        ];
    }

    /**
     * Map Transaksi Barang Keluar (dari PJ ke Individu) menjadi row "Keluar"
     */
    private function mapBarangKeluarToPjRow(TransaksiBarangKeluar $item)
    {
        return (object) [
            'id'            => $item->id,
            'tanggal'       => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'         => optional($item->created_at)->format('H:i:s'),
            'alur_barang'   => 'Keluar',
            'gudang'        => optional($item->gudang)->nama ?? '-',
            'nama_barang'   => optional($item->barang)->nama_barang ?? '-',
            'kode_barang'   => optional($item->barang)->kode_barang ?? '-',
            'jumlah'        => (int) ($item->jumlah ?? 0),
            'satuan'        => optional($item->barang)->satuan ?? '-',
            'bagian'        => optional($item->bagian)->nama ?? '-',
            'nama_penerima' => $item->nama_penerima ?? '-',
            'keterangan'    => $item->keterangan ?? 'Barang keluar',
            'bukti'         => $item->bukti,
            'bukti_path'    => $item->bukti ? asset('storage/' . $item->bukti) : null,
            'user'          => optional($item->user)->nama ?? '-',
        ];
    }

    public function index(Request $request)
    {
        $menu = MenuHelper::pjMenu();
        $user = $request->user();

        // Validasi apakah user memiliki gudang
        if (!$user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan.'
            ]);
        }

        $userGudang = $user->gudang;

        // Jika ada request download
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // =======================
        // === MASUK: Transaksi Distribusi (dari PB ke PJ) ===
        // =======================
        $distribusiQuery = TransaksiDistribusi::with([
            'barang.kategori',
            'gudangTujuan',
            'user'
        ])
            ->where('id_gudang_tujuan', $userGudang->id);

        // Filter periode untuk distribusi
        $this->applyPeriodeFilter($distribusiQuery, $request);

        $riwayatMasuk = $distribusiQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapDistribusiToPjRow($item))
            ->values()
            ->toBase();

        // ===================
        // === KELUAR: Transaksi Barang Keluar (dari PJ ke Individu) ===
        // ===================
        $keluarQuery = TransaksiBarangKeluar::with([
            'barang.kategori',
            'gudang',
            'bagian',
            'user'
        ])
            ->where('id_gudang', $userGudang->id);

        // Filter bagian
        if ($request->filled('bagian') && $request->bagian != 'Semua') {
            $keluarQuery->where('bagian_id', $request->bagian);
        }

        // Filter periode untuk barang keluar
        $this->applyPeriodeFilter($keluarQuery, $request);

        $riwayatKeluar = $keluarQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapBarangKeluarToPjRow($item))
            ->values()
            ->toBase();

        // ===============================================
        // === Filter "Alur Barang" (Masuk / Keluar / Semua)
        // ===============================================
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

        // List bagian untuk filter
        $bagianList = Bagian::orderBy('nama')->get()
            ->map(fn($item) => (object) ['id' => $item->id, 'nama' => $item->nama])
            ->values()
            ->toBase();

        $gudangList = collect([(object) ['gudang' => $userGudang->nama]]);

        return view('staff.pj.riwayat', compact('riwayat', 'bagianList', 'gudangList', 'menu', 'userGudang'));
    }

    // ============================================================
    // === FUNGSI DOWNLOAD REPORT (PDF & EXCEL)
    // ============================================================
    public function downloadReport(Request $request)
    {
        $user = $request->user();

        if (!$user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan.'
            ]);
        }

        $userGudang = $user->gudang;

        // Query Distribusi (Masuk)
        $distribusiQuery = TransaksiDistribusi::with([
            'barang.kategori',
            'gudangTujuan',
            'user'
        ])
            ->where('id_gudang_tujuan', $userGudang->id);

        // Query Barang Keluar
        $keluarQuery = TransaksiBarangKeluar::with([
            'barang.kategori',
            'gudang',
            'bagian',
            'user'
        ])
            ->where('id_gudang', $userGudang->id);

        // Filter bagian untuk barang keluar
        if ($request->filled('bagian') && $request->bagian != 'Semua') {
            $keluarQuery->where('bagian_id', $request->bagian);
        }

        // Filter periode untuk keduanya
        $this->applyPeriodeFilter($distribusiQuery, $request);
        $this->applyPeriodeFilter($keluarQuery, $request);

        // Map data
        $rowsMasuk  = $distribusiQuery->get()
            ->map(fn($item) => $this->mapDistribusiToPjRow($item))
            ->values()
            ->toBase();

        $rowsKeluar = $keluarQuery->get()
            ->map(fn($item) => $this->mapBarangKeluarToPjRow($item))
            ->values()
            ->toBase();

        // Gabungkan berdasarkan filter alur barang
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

        // Format filter info
        $filter = [
            'gudang'         => $userGudang->nama,
            'alur_barang'    => $request->alur_barang,
            'bagian'         => $request->bagian,
            'periode'        => $request->periode,
            'dari_tanggal'   => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal,
        ];

        $format = $request->download;

        // === PDF ===
        if ($format == 'pdf') {
            $pdf = Pdf::loadView('staff.pj.riwayat-pdf', compact('riwayat', 'filter'))
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('Laporan_Riwayat_Barang_PJ_' . date('Y-m-d_His') . '.pdf');
        }

        // === EXCEL ===
        if ($format == 'excel') {
            return Excel::download(
                new RiwayatExportPj($riwayat, $filter),
                'Laporan_Riwayat_Barang_PJ_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        return redirect()->back()->with('toast', [
            'type' => 'error',
            'title' => 'Error!',
            'message' => 'Format tidak valid.'
        ]);
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
