<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Bagian;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\TransaksiDistribusi;
use App\Models\TransaksiBarangKeluar;
use App\Models\TransaksiBarangMasuk;
use App\Models\Gudang;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RiwayatExport;
use Barryvdh\DomPDF\Facade\Pdf;

class RiwayatController extends Controller
{
    /**
     * Map Transaksi Barang Masuk (Admin ke PB/Pusat)
     * Label “Gudang Utama” dipertahankan sesuai permintaan.
     */
    private function mapBarangMasukToAdminRow(TransaksiBarangMasuk $item)
    {
        return (object) [
            'tanggal' => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu' => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Masuk PB',
            'gudang' => 'Gudang Utama',
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah' => (int) ($item->jumlah ?? 0),
            'satuan' => optional($item->barang)->satuan ?? '-',
            'bukti' => $item->bukti,
            // PERBAIKAN: Tambahkan pengecekan apakah file exist
            'bukti_path' => $item->bukti
                ? asset('storage/' . str_replace('\\', '/', $item->bukti))
                : null,

            'keterangan' => $item->keterangan ?? 'Barang masuk'
        ];
    }

    /** Map Distribusi (PB -> Bagian) — kolom “gudang” diisi nama bagian tujuan (kompatibel blade) */
    private function mapDistribusiBagianRow($r)
    {
        return (object) [
            'tanggal' => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu' => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Distribusi PJ',
            'gudang' => optional($item->gudangTujuan)->nama ?? '-',
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah' => (int) ($item->jumlah ?? 0),
            'satuan' => optional($item->barang)->satuan ?? '-',
            'bukti' => $item->bukti,
            // PERBAIKAN: Tambahkan pengecekan apakah file exist
            'bukti_path' => $item->bukti
                ? asset('storage/' . str_replace('\\', '/', $item->bukti))
                : null,

            'keterangan' => $item->keterangan ?? 'Distribusi barang'
        ];
    }

    /** Map Keluar (Bagian/PJ -> Individu) — kolom “gudang” = bagian asal */
    private function mapKeluarBagianRow($r)
    {
        return (object) [
            'tanggal' => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu' => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Keluar PJ',
            'gudang' => optional($item->gudang)->nama ?? '-',
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah' => (int) ($item->jumlah ?? 0),
            'satuan' => optional($item->barang)->satuan ?? '-',
            'bagian' => optional($item->bagian)->nama ?? '-',
            'penerima' => $item->nama_penerima ?? '-',
            'bukti' => $item->bukti,
            // PERBAIKAN: Tambahkan pengecekan apakah file exist
            'bukti_path' => $item->bukti
                ? asset('storage/' . str_replace('\\', '/', $item->bukti))
                : null,


            'keterangan' => $item->keterangan ?? 'Barang keluar'
        ];
    }

    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();

        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        $gudangIdForFilter = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $gudangIdForFilter = Gudang::where('nama', $request->gudang)->value('id');
        }

        // ===== TABEL 1: Barang Masuk (Admin -> PB) =====
        $barangMasukQuery = TransaksiBarangMasuk::with(['barang.kategori', 'user']);
        $this->applyPeriodeFilter($barangMasukQuery, $request);

        $riwayatBarangMasuk = $bmQ
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($item) => $this->mapBarangMasukToAdminRow($item))
            ->values()
            ->toBase();

        // ===== TABEL 2: Distribusi (PB -> PJ) =====
        $distribusiQuery = TransaksiDistribusi::with(['barang.kategori.gudang', 'gudangTujuan', 'user']);

        if ($gudangIdForFilter) {
            $distribusiQuery->where('id_gudang_tujuan', $gudangIdForFilter);
        }

        $this->applyPeriodeFilter($distribusiQuery, $request);

        $riwayatDistribusi = $distribusiQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapDistribusiToAdminRow($item))
            ->values()
            ->toBase();

        // ===== TABEL 3: Barang Keluar (PJ -> Individu) =====
        $keluarQuery = TransaksiBarangKeluar::with(['barang.kategori', 'gudang', 'user', 'bagian']);

        if ($gudangIdForFilter) {
            $keluarQuery->where('id_gudang', $gudangIdForFilter);
        }

        $this->applyPeriodeFilter($keluarQuery, $request);

            $riwayatBarangKeluar = $kq
                ->orderBy('tanggal', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function (TransaksiBarangKeluar $item) {
                    return (object) [
                        'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
                        'waktu'       => optional($item->created_at)->format('H:i:s'),
                        'alur_barang' => 'Keluar PJ',
                        'gudang'      => optional($item->gudang)->nama ?? '-',
                        'nama_barang' => optional($item->barang)->nama_barang ?? '-',
                        'jumlah'      => (int) ($item->jumlah ?? 0),
                        'satuan'      => optional($item->barang)->satuan ?? '-',
                        'bagian'      => optional($item->bagian)->nama ?? '-',
                        'penerima'    => $item->nama_penerima ?? '-',
                        'bukti'       => $item->bukti,
                        'bukti_path'  => $item->bukti ? asset('storage/' . str_replace('\\', '/', $item->bukti)) : null,
                        'keterangan'  => $item->keterangan ?? 'Barang keluar',
                    ];
                })
                ->values()
                ->toBase();
        } else {
            // Cabang baru (bagian-only)
            $kq = DB::table('transaksi_barang_keluar')
                ->leftJoin('bagian', 'transaksi_barang_keluar.bagian_id', '=', 'bagian.id')
                ->leftJoin('barang', 'transaksi_barang_keluar.kode_barang', '=', 'barang.kode_barang')
                ->select(
                    'transaksi_barang_keluar.*',
                    DB::raw('bagian.nama as bagian_nama'),
                    DB::raw('barang.nama_barang as nama_barang'),
                    DB::raw('barang.satuan as satuan')
                );

            if ($bagianIdForFilter) {
                $kq->where('transaksi_barang_keluar.bagian_id', $bagianIdForFilter);
            }
            $this->applyPeriodeFilter($kq, $request);

            $riwayatBarangKeluar = $kq
                ->orderBy('transaksi_barang_keluar.tanggal', 'desc')
                ->orderBy('transaksi_barang_keluar.created_at', 'desc')
                ->get()
                ->map(fn ($r) => $this->mapKeluarBagianRow($r))
                ->values()
                ->toBase();
        }

        $gudangList = Gudang::orderBy('nama')->get()
            ->map(fn($g) => (object) ['gudang' => $g->nama])
            ->values();

        return view('staff.admin.riwayat', compact(
            'riwayatBarangMasuk',
            'riwayatDistribusi',
            'riwayatBarangKeluar',
            'menu',
            'gudangList'
        ));
    }

    public function downloadReport(Request $request)
    {
        $gudangIdForFilter = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $gudangIdForFilter = Gudang::where('nama', $request->gudang)->value('id');
        }

        // Barang Masuk
        $bmQ = TransaksiBarangMasuk::with(['barang.kategori', 'user']);
        $this->applyPeriodeFilter($bmQ, $request);
        $rowsMasuk = $bmQ->get()->map(fn ($x) => $this->mapBarangMasukToAdminRow($x))->values()->toBase();

        // Distribusi
        $distribusiQuery = TransaksiDistribusi::with(['barang.kategori.gudang', 'gudangTujuan', 'user']);
        if ($gudangIdForFilter) {
            $distribusiQuery->where('id_gudang_tujuan', $gudangIdForFilter);
        }
        $this->applyPeriodeFilter($distribusiQuery, $request);
        $rowsDistribusi = $distribusiQuery->get()->map(fn($item) => $this->mapDistribusiToAdminRow($item))->values()->toBase();

        // Barang Keluar
        $keluarQuery = TransaksiBarangKeluar::with(['barang.kategori', 'gudang', 'user', 'bagian']);
        if ($gudangIdForFilter) {
            $keluarQuery->where('id_gudang', $gudangIdForFilter);
        }
        $this->applyPeriodeFilter($keluarQuery, $request);
        $rowsBarangKeluar = $keluarQuery->get()->map(fn($item) => $this->mapBarangKeluarToAdminRow($item))->values()->toBase();

        // Gabungkan & urutkan
        $riwayat = $rowsMasuk->concat($rowsDistribusi)->concat($rowsKeluar);
        $riwayat = $riwayat->sortByDesc(function ($x) {
            return ($x->tanggal ?? '1970-01-01') . ' ' . ($x->waktu ?? '00:00:00');
        })->values();

        $filter = [
            'gudang'         => $request->gudang, // label filter (sekarang nama Bagian)
            'periode'        => $request->periode,
            'dari_tanggal'   => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal,
        ];

        if ($request->download === 'excel') {
            return Excel::download(
                new RiwayatExport($riwayat, $filter),
                'riwayat-barang-' . date('Y-m-d_His') . '.xlsx'
            );
        }

        if ($request->download === 'pdf') {
            $pdf = Pdf::loadView('staff.admin.riwayat-pdf', compact('riwayat', 'filter'))
                ->setPaper('a4', 'portrait') // PERBAIKAN: Ubah ke portrait untuk PDF
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('riwayat-barang-' . date('Y-m-d_His') . '.pdf');
        }

        return back()->with('error', 'Format tidak valid');
    }

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
                        $query->whereBetween('tanggal', [$request->dari_tanggal, $request->sampai_tanggal]);
                    }
                    break;
            }
        }
    }
}