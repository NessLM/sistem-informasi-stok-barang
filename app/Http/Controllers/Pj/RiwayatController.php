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

    public function downloadReport(Request $request)
{
    $user   = $request->user();
    $gudang = $user->gudang_id ? $user->gudang : null;
    $bagian = $user->bagian_id ? Bagian::find($user->bagian_id) : null;

    if (!$gudang && !$bagian) {
        return back()->with('toast', [
            'type' => 'error',
            'title' => 'Error!',
            'message' => 'Anda belum memiliki bagian/gudang yang ditugaskan.'
        ]);
    }

    if ($gudang) {
        // MODE GUDANG
        $distribusiQuery = TransaksiDistribusi::with(['barang.kategori', 'gudangTujuan', 'user'])
            ->where('id_gudang_tujuan', $gudang->id);
        $keluarQuery = TransaksiBarangKeluar::with(['barang.kategori', 'gudang', 'bagian', 'user'])
            ->where('id_gudang', $gudang->id);

        if ($request->filled('bagian') && $request->bagian != 'Semua') {
            $keluarQuery->where('bagian_id', $request->bagian);
        }
        $this->applyPeriodeFilter($distribusiQuery, $request);
        $this->applyPeriodeFilter($keluarQuery, $request);

        $rowsMasuk  = $distribusiQuery->get()->map(fn($x) => $this->mapDistribusiToPjRow($x))->values()->toBase();
        $rowsKeluar = $keluarQuery->get()->map(fn($x) => $this->mapBarangKeluarToPjRow($x))->values()->toBase();

        // TAMBAHAN: Ambil stok untuk gudang (jika diperlukan)
        $stokBagian = collect(); // Kosong untuk mode gudang

        $filter = [
            'gudang' => $gudang->nama,
            'alur_barang' => $request->alur_barang,
            'bagian' => $request->bagian,
            'periode' => $request->periode,
            'dari_tanggal' => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal,
        ];
    } else {
        // MODE BAGIAN
        $distribusiBagianQuery = TransaksiDistribusi::with(['barang.kategori', 'gudangTujuan', 'user'])
            ->where('bagian_id', $bagian->id);
        $this->applyPeriodeFilter($distribusiBagianQuery, $request);
        
        $keluarBagianQuery = TransaksiBarangKeluar::with(['barang.kategori', 'gudang', 'bagian', 'user'])
            ->where('bagian_id', $bagian->id);
        $this->applyPeriodeFilter($keluarBagianQuery, $request);

        $rowsMasuk  = $distribusiBagianQuery->get()
            ->map(fn($x) => $this->mapDistribusiToPjRow($x))
            ->values()
            ->toBase();
            
        $rowsKeluar = $keluarBagianQuery->get()
            ->map(fn($x) => $this->mapBarangKeluarToPjRow($x))
            ->values()
            ->toBase();

        // TAMBAHAN: Ambil stok bagian dengan informasi lengkap
        $stokBagian = \App\Models\StokBagian::with(['barang.kategori', 'bagian'])
            ->where('bagian_id', $bagian->id)
            ->where('stok', '>', 0)
            ->orderBy('kode_barang')
            ->get()
            ->map(function($stok) {
                return (object)[
                    'kode_barang' => $stok->kode_barang,
                    'nama_barang' => $stok->barang->nama_barang ?? '-',
                    'kategori' => $stok->barang->kategori->nama ?? '-',
                    'satuan' => $stok->barang->satuan ?? '-',
                    'stok' => $stok->stok,
                    'harga' => $stok->harga,
                ];
            });

        $filter = [
            'gudang' => 'Bagian ' . $bagian->nama,
            'alur_barang' => $request->alur_barang,
            'bagian' => $bagian->id,
            'periode' => $request->periode,
            'dari_tanggal' => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal,
        ];
    }

    // Gabung sesuai filter
    $riwayat = collect()->toBase();
    if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
        $riwayat = $request->alur_barang === 'Masuk' ? $rowsMasuk : $rowsKeluar;
    } else {
        $riwayat = $rowsMasuk->concat($rowsKeluar)->sortByDesc(function ($x) {
            return ($x->tanggal ?? '1970-01-01') . ' ' . ($x->waktu ?? '00:00:00');
        })->values();
    }

    $format = $request->download;

    if ($format == 'pdf') {
        $pdf = Pdf::loadView('staff.pj.riwayat-pdf', compact('riwayat', 'filter', 'stokBagian'))
            ->setPaper('a4', 'landscape')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true);
        return $pdf->download('Laporan_Riwayat_Barang_PJ_' . date('Y-m-d_His') . '.pdf');
    }

    if ($format == 'excel') {
        return Excel::download(
            new RiwayatExportPj($riwayat, $filter, $stokBagian),
            'Laporan_Riwayat_Barang_PJ_' . date('Y-m-d_His') . '.xlsx'
        );
    }

    return redirect()->back()->with('toast', [
        'type' => 'error',
        'title' => 'Error!',
        'message' => 'Format tidak valid.'
    ]);
}
    // === mapper Distribusi -> "Masuk" (tetap) ===
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

    // === mapper TBK -> "Keluar" (dipakai di mode Gudang & Bagian) ===
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

    // (Tidak dipakai lagi sesuai requirement baru — dibiarkan agar perubahan minimal)
    private function mapBarangKeluarToBagianMasuk(TransaksiBarangKeluar $item)
    {
        $row = $this->mapBarangKeluarToPjRow($item);
        $row->alur_barang = 'Masuk';
        return $row;
    }

    public function index(Request $request)
    {
        $menu   = MenuHelper::pjMenu();
        $user   = $request->user();

        $gudang = $user->gudang_id ? $user->gudang : null;
        $bagian = $user->bagian_id ? Bagian::find($user->bagian_id) : null;

        if (!$gudang && !$bagian) {
            abort(403, 'Anda tidak memiliki akses ke bagian manapun.');
        }

        // DOWNLOAD diarahkan ke method terpisah
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // ===================== MODE GUDANG (legacy) =====================
        if ($gudang) {
            $distribusiQuery = TransaksiDistribusi::with([
                'barang.kategori',
                'gudangTujuan',
                'user'
            ])->where('id_gudang_tujuan', $gudang->id);
            $this->applyPeriodeFilter($distribusiQuery, $request);

            $riwayatMasuk = $distribusiQuery->orderBy('tanggal', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()->map(fn($x) => $this->mapDistribusiToPjRow($x))
                ->values()->toBase();

            $keluarQuery = TransaksiBarangKeluar::with([
                'barang.kategori',
                'gudang',
                'bagian',
                'user'
            ])->where('id_gudang', $gudang->id);

            if ($request->filled('bagian') && $request->bagian != 'Semua') {
                $keluarQuery->where('bagian_id', $request->bagian);
            }
            $this->applyPeriodeFilter($keluarQuery, $request);

            $riwayatKeluar = $keluarQuery->orderBy('tanggal', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()->map(fn($x) => $this->mapBarangKeluarToPjRow($x))
                ->values()->toBase();

            $riwayat = $this->mixByAlur($request, $riwayatMasuk, $riwayatKeluar);

            $bagianList = Bagian::orderBy('nama')->get()
                ->map(fn($b) => (object)['id' => $b->id, 'nama' => $b->nama])->toBase();
            $gudangList = collect([(object)['gudang' => $gudang->nama]]);
            $userGudang = $gudang; // untuk blade

            return view('staff.pj.riwayat', compact('riwayat', 'bagianList', 'gudangList', 'menu', 'userGudang'));
        }

        // ===================== MODE BAGIAN (baru) =====================
        // Sesuai requirement: Bagian hanya "KELUAR".
        // ===================== MODE BAGIAN (baru) =====================
        // MASUK: ambil dari TransaksiDistribusi by bagian_id (tanpa join gudang)
        $distribusiBagianQuery = TransaksiDistribusi::with(['barang.kategori', 'user'])
            ->where('bagian_id', $bagian->id);
        $this->applyPeriodeFilter($distribusiBagianQuery, $request);

        $rowsMasuk = $distribusiBagianQuery->get()
            ->map(fn($x) => $this->mapDistribusiToPjRow($x))
            ->values()
            ->toBase();

        $riwayatMasuk = $distribusiBagianQuery->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($x) => $this->mapDistribusiToPjRow($x))
            ->values()
            ->toBase();

        // KELUAR: tetap dari TBK milik bagian ini
        $keluarBagianQuery = TransaksiBarangKeluar::with([
            'barang.kategori',
            'gudang',
            'bagian',
            'user'
        ])->where('bagian_id', $bagian->id);
        $this->applyPeriodeFilter($keluarBagianQuery, $request);

        $riwayatKeluar = $keluarBagianQuery->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($x) => $this->mapBarangKeluarToPjRow($x))
            ->values()
            ->toBase();

        $riwayat = $this->mixByAlur($request, $riwayatMasuk, $riwayatKeluar);


        // Dropdown filter: hanya bagian user, label gudang diganti "Bagian ..."
        $bagianList = collect([(object)['id' => $bagian->id, 'nama' => $bagian->nama]])->toBase();
        $gudangList = collect([(object)['gudang' => 'Bagian ' . $bagian->nama]]);
        $userGudang = (object)['id' => null, 'nama' => 'Bagian ' . $bagian->nama];

        return view('staff.pj.riwayat', compact('riwayat', 'bagianList', 'gudangList', 'menu', 'userGudang'));
    }

    // helper untuk gabung riwayat sesuai filter "alur_barang"
    private function mixByAlur(Request $request, $riwayatMasuk, $riwayatKeluar)
    {
        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            return $request->alur_barang === 'Masuk' ? $riwayatMasuk : $riwayatKeluar;
        }
        return $riwayatMasuk->concat($riwayatKeluar)
            ->sortByDesc(fn($x) => ($x->tanggal ?? '1970-01-01') . ' ' . ($x->waktu ?? '00:00:00'))
            ->values();
    }

    // === helper filter periode (tetap) ===
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
