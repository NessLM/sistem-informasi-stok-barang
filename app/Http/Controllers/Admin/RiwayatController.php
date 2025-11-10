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
use App\Models\Gudang; // aman walau tabel gudang sudah dihapus, tidak dipakai saat $useGudang=false

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

        // Debug: cek data yang ada
        $bagianNama = '-';

        // Cek berbagai kemungkinan sumber data bagian
        if ($item->relationLoaded('bagian') && $item->bagian) {
            $bagianNama = $item->bagian->nama;
        }
        // Jika ada kolom bagian_id, load manual
        elseif ($item->bagian_id) {
            $bagian = \App\Models\Bagian::find($item->bagian_id);
            $bagianNama = $bagian ? $bagian->nama : '-';
        }
        // Jika tidak ada data bagian sama sekali
        else {
            $bagianNama = 'Tidak ada data bagian';
        }

        return (object) [
            'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'       => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Masuk PB',
            'gudang'      => 'Gudang Utama',
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah'      => (int) ($item->jumlah ?? 0),
            'satuan'      => optional($item->barang)->satuan ?? '-',
            'bukti'       => $item->bukti,
            'bukti_path'  => $item->bukti ? asset('storage/' . str_replace('\\', '/', $item->bukti)) : null,
            'keterangan'  => $item->keterangan ?? 'Barang masuk',
            'bagian_nama' => $bagianNama,
        ];
    }

    /** Map Distribusi (PB -> Bagian) — kolom “gudang” diisi nama bagian tujuan (kompatibel blade) */
    private function mapDistribusiBagianRow($r)
    {
        return (object) [
            'tanggal'     => $r->tanggal ?? \Carbon\Carbon::parse($r->created_at)->toDateString(),
            'waktu'       => \Carbon\Carbon::parse($r->created_at)->format('H:i:s'),
            'alur_barang' => 'Distribusi PJ',
            'gudang'      => $r->bagian_nama ?? '-', // tampil di UI sebagai “Bagian Tujuan”
            'nama_barang' => $r->nama_barang ?? '-',
            'jumlah'      => (int) ($r->jumlah ?? 0),
            'satuan'      => $r->satuan ?? '-',
            'bukti'       => $r->bukti,
            'bukti_path'  => $r->bukti ? asset('storage/' . str_replace('\\', '/', $r->bukti)) : null,
            'keterangan'  => $r->keterangan ?? 'Distribusi barang',
        ];
    }

    /** Map Keluar (Bagian/PJ -> Individu) — kolom “gudang” = bagian asal */
    private function mapKeluarBagianRow($r)
    {
        return (object) [
            'tanggal'     => $r->tanggal ?? \Carbon\Carbon::parse($r->created_at)->toDateString(),
            'waktu'       => \Carbon\Carbon::parse($r->created_at)->format('H:i:s'),
            'alur_barang' => 'Keluar PJ',
            'gudang'      => $r->bagian_nama ?? '-', // tampil di UI sebagai “Bagian Asal”
            'nama_barang' => $r->nama_barang ?? '-',
            'jumlah'      => (int) ($r->jumlah ?? 0),
            'satuan'      => $r->satuan ?? '-',
            'bagian'      => $r->bagian_nama ?? '-', // kolom “Bagian” tetap ada di tabel
            'penerima'    => $r->nama_penerima ?? '-',
            'bukti'       => $r->bukti,
            'bukti_path'  => $r->bukti ? asset('storage/' . str_replace('\\', '/', $r->bukti)) : null,
            'keterangan'  => $r->keterangan ?? 'Barang keluar',
        ];
    }

    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();

        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // Deteksi apakah tabel gudang masih ada (biar struktur lama tetap aman)
        $useGudang = Schema::hasTable('gudang');

        // Ambil ID filter dari input “gudang” (sebenarnya daftar Bagian sekarang)
        $gudangIdForFilter = null; // mode lama
        $bagianIdForFilter = null; // mode baru (bagian-only)

        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            if ($useGudang) {
                $gudangIdForFilter = Gudang::where('nama', $request->gudang)->value('id');
            } else {
                $bagianIdForFilter = Bagian::where('nama', $request->gudang)->value('id');
            }
        }

        /** 1) Barang Masuk (Admin -> PB) */
        $bmQ = TransaksiBarangMasuk::with(['barang.kategori', 'user', 'bagian']); // Tambahkan 'bagian' di sini
        $this->applyPeriodeFilter($bmQ, $request);

        $riwayatBarangMasuk = $bmQ
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapBarangMasukToAdminRow($item))
            ->values()
            ->toBase();

        /** 2) Distribusi (PB -> Bagian) */
        if ($useGudang && Schema::hasColumn('transaksi_distribusi', 'id_gudang_tujuan')) {
            // Cabang lama (masih pakai tabel gudang) — dibiarkan untuk kompatibilitas
            $dq = TransaksiDistribusi::with(['barang.kategori.gudang', 'gudangTujuan', 'user']);
            if ($gudangIdForFilter) {
                $dq->where('id_gudang_tujuan', $gudangIdForFilter);
            }
            $this->applyPeriodeFilter($dq, $request);

            // Map lama (nama gudang)
            $riwayatDistribusi = $dq
                ->orderBy('tanggal', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function (TransaksiDistribusi $item) {
                    return (object) [
                        'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
                        'waktu'       => optional($item->created_at)->format('H:i:s'),
                        'alur_barang' => 'Distribusi PJ',
                        'gudang'      => optional($item->gudangTujuan)->nama ?? '-',
                        'nama_barang' => optional($item->barang)->nama_barang ?? '-',
                        'jumlah'      => (int) ($item->jumlah ?? 0),
                        'satuan'      => optional($item->barang)->satuan ?? '-',
                        'bukti'       => $item->bukti,
                        'bukti_path'  => $item->bukti ? asset('storage/' . str_replace('\\', '/', $item->bukti)) : null,
                        'keterangan'  => $item->keterangan ?? 'Distribusi barang',
                    ];
                })
                ->values()
                ->toBase();
        } else {
            // Cabang baru (bagian-only)
            $dq = DB::table('transaksi_distribusi')
                ->leftJoin('bagian', 'transaksi_distribusi.bagian_id', '=', 'bagian.id')
                ->leftJoin('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->select(
                    'transaksi_distribusi.*',
                    DB::raw('bagian.nama as bagian_nama'),
                    DB::raw('barang.nama_barang as nama_barang'),
                    DB::raw('barang.satuan as satuan')
                );

            if ($bagianIdForFilter) {
                $dq->where('transaksi_distribusi.bagian_id', $bagianIdForFilter);
            }
            $this->applyPeriodeFilter($dq, $request);

            $riwayatDistribusi = $dq
                ->orderBy('transaksi_distribusi.tanggal', 'desc')
                ->orderBy('transaksi_distribusi.created_at', 'desc')
                ->get()
                ->map(fn($r) => $this->mapDistribusiBagianRow($r))
                ->values()
                ->toBase();
        }

        /** 3) Keluar (PJ -> Individu) */
        if ($useGudang && Schema::hasColumn('transaksi_barang_keluar', 'id_gudang')) {
            // Cabang lama — kompatibilitas
            $kq = TransaksiBarangKeluar::with(['barang.kategori', 'gudang', 'user', 'bagian']);
            if ($gudangIdForFilter) {
                $kq->where('id_gudang', $gudangIdForFilter);
            }
            $this->applyPeriodeFilter($kq, $request);

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
                ->map(fn($r) => $this->mapKeluarBagianRow($r))
                ->values()
                ->toBase();
        }

        /** Dropdown filter: sekarang dari tabel Bagian (label tetap pakai variabel $gudangList) */
        if ($useGudang) {
            $gudangList = Gudang::orderBy('nama')->get()
                ->map(fn($g) => (object) ['gudang' => $g->nama])
                ->values();
        } else {
            $gudangList = Bagian::orderBy('nama')->get()
                ->map(fn($b) => (object) ['gudang' => $b->nama])
                ->values();
        }

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
        // Sama seperti index(): sekarang pakai Bagian saat tabel gudang sudah tidak ada.
        $useGudang = Schema::hasTable('gudang');

        $gudangIdForFilter = null;
        $bagianIdForFilter = null;

        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            if ($useGudang) {
                $gudangIdForFilter = Gudang::where('nama', $request->gudang)->value('id');
            } else {
                $bagianIdForFilter = Bagian::where('nama', $request->gudang)->value('id');
            }
        }

        // Barang Masuk
        $bmQ = TransaksiBarangMasuk::with(['barang.kategori', 'user']);
        $this->applyPeriodeFilter($bmQ, $request);
        $rowsMasuk = $bmQ->get()->map(fn($x) => $this->mapBarangMasukToAdminRow($x))->values()->toBase();

        // Distribusi
        if ($useGudang && Schema::hasColumn('transaksi_distribusi', 'id_gudang_tujuan')) {
            $dq = TransaksiDistribusi::with(['barang.kategori.gudang', 'gudangTujuan', 'user']);
            if ($gudangIdForFilter) {
                $dq->where('id_gudang_tujuan', $gudangIdForFilter);
            }
            $this->applyPeriodeFilter($dq, $request);
            $rowsDistribusi = $dq->get()->map(function (TransaksiDistribusi $item) {
                return (object) [
                    'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
                    'waktu'       => optional($item->created_at)->format('H:i:s'),
                    'alur_barang' => 'Distribusi PJ',
                    'gudang'      => optional($item->gudangTujuan)->nama ?? '-',
                    'nama_barang' => optional($item->barang)->nama_barang ?? '-',
                    'jumlah'      => (int) ($item->jumlah ?? 0),
                    'satuan'      => optional($item->barang)->satuan ?? '-',
                    'bukti'       => $item->bukti,
                    'bukti_path'  => $item->bukti ? asset('storage/' . str_replace('\\', '/', $item->bukti)) : null,
                    'keterangan'  => $item->keterangan ?? 'Distribusi barang',
                ];
            })->values()->toBase();
        } else {
            $dq = DB::table('transaksi_distribusi')
                ->leftJoin('bagian', 'transaksi_distribusi.bagian_id', '=', 'bagian.id')
                ->leftJoin('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->select(
                    'transaksi_distribusi.*',
                    DB::raw('bagian.nama as bagian_nama'),
                    DB::raw('barang.nama_barang as nama_barang'),
                    DB::raw('barang.satuan as satuan')
                );
            if ($bagianIdForFilter) {
                $dq->where('transaksi_distribusi.bagian_id', $bagianIdForFilter);
            }
            $this->applyPeriodeFilter($dq, $request);
            $rowsDistribusi = collect($dq->get())->map(fn($r) => $this->mapDistribusiBagianRow($r))->values()->toBase();
        }

        // Keluar
        if ($useGudang && Schema::hasColumn('transaksi_barang_keluar', 'id_gudang')) {
            $kq = TransaksiBarangKeluar::with(['barang.kategori', 'gudang', 'user', 'bagian']);
            if ($gudangIdForFilter) {
                $kq->where('id_gudang', $gudangIdForFilter);
            }
            $this->applyPeriodeFilter($kq, $request);
            $rowsKeluar = $kq->get()->map(function (TransaksiBarangKeluar $item) {
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
            })->values()->toBase();
        } else {
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
            $rowsKeluar = collect($kq->get())->map(fn($r) => $this->mapKeluarBagianRow($r))->values()->toBase();
        }

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
                ->setPaper('a4', 'portrait')
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
