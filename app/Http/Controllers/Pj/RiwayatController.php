<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
use App\Models\RiwayatBarang;
use App\Models\Gudang;
use App\Helpers\MenuHelper;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RiwayatExportPj;
use Carbon\Carbon;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::pjMenu();
        $user = auth()->user();

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
        // === BARANG DISTRIBUSI ===
        // =======================
        $riwayatDistribusiQuery = RiwayatBarang::with(['barang.kategori', 'barang.kategori.gudang'])
            ->where('jenis_transaksi', 'distribusi')
            ->where(function ($q) use ($userGudang) {
                $q->where('gudang_tujuan_id', $userGudang->id)
                    ->orWhereHas('barang.kategori', function ($sub) use ($userGudang) {
                        $sub->where('gudang_id', $userGudang->id);
                    });
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter periode untuk distribusi
        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $riwayatDistribusiQuery->where('tanggal', '>=', Carbon::now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $riwayatDistribusiQuery->where('tanggal', '>=', Carbon::now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $riwayatDistribusiQuery->where('tanggal', '>=', Carbon::now()->subYear());
                    break;
                case 'custom':
                    if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                        $riwayatDistribusiQuery->whereBetween('tanggal', [
                            $request->dari_tanggal,
                            $request->sampai_tanggal
                        ]);
                    }
                    break;
            }
        }

        // Ambil data distribusi
        $riwayatDistribusi = $riwayatDistribusiQuery->get()->map(function ($item) {
            return (object) [
                'id' => $item->id,
                'tanggal' => $item->tanggal,
                'waktu' => optional($item->created_at)->format('H:i:s'),
                'alur_barang' => 'Masuk',
                'gudang' => optional($item->barang->kategori->gudang)->nama ?? '-',
                'nama_barang' => optional($item->barang)->nama ?? '-',
                'kode_barang' => optional($item->barang)->kode ?? '-',
                'jumlah' => $item->jumlah,
                'bukti' => $item->bukti,
                'bukti_path' => $item->bukti ? asset('storage/bukti/' . $item->bukti) : null,
                'keterangan' => $item->keterangan ?? '-',
                'user' => optional($item->user)->nama ?? '-',
            ];
        });

        // ===================
        // === BARANG KELUAR ===
        // ===================
        $riwayatKeluarQuery = BarangKeluar::with(['barang.kategori', 'gudang', 'user'])
            ->where('gudang_id', $userGudang->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter bagian
        if ($request->filled('bagian') && $request->bagian != 'Semua') {
            $riwayatKeluarQuery->where('bagian', $request->bagian);
        }

        // Filter periode keluar
        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $riwayatKeluarQuery->where('tanggal', '>=', Carbon::now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $riwayatKeluarQuery->where('tanggal', '>=', Carbon::now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $riwayatKeluarQuery->where('tanggal', '>=', Carbon::now()->subYear());
                    break;
                case 'custom':
                    if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                        $riwayatKeluarQuery->whereBetween('tanggal', [
                            $request->dari_tanggal,
                            $request->sampai_tanggal
                        ]);
                    }
                    break;
            }
        }

        // Ambil data keluar
        $riwayatKeluar = $riwayatKeluarQuery->get()->map(function ($item) {
            return (object) [
                'id' => $item->id,
                'tanggal' => $item->tanggal,
                'waktu' => $item->created_at->format('H:i:s'),
                'alur_barang' => 'Keluar',
                'gudang' => optional($item->gudang)->nama ?? '-',
                'nama_barang' => optional($item->barang)->nama ?? '-',
                'kode_barang' => optional($item->barang)->kode ?? '-',
                'jumlah' => $item->jumlah,
                'bagian' => $item->bagian ?? '-',
                'nama_penerima' => $item->nama_penerima ?? '-',
                'keterangan' => $item->keterangan ?? '-',
                'bukti' => $item->bukti,
                'bukti_path' => $item->bukti ? asset('storage/' . $item->bukti) : null,
                'user' => optional($item->user)->nama ?? '-',
            ];
        });

        // List bagian untuk filter
        $bagianList = BarangKeluar::where('gudang_id', $userGudang->id)
            ->whereNotNull('bagian')
            ->select('bagian')->distinct()
            ->orderBy('bagian')
            ->get()
            ->map(fn($item) => (object) ['bagian' => $item->bagian]);

        $gudangList = collect([(object) ['gudang' => $userGudang->nama]]);

        // Gabungkan semua riwayat (Masuk dan Keluar)
        $riwayat = $riwayatDistribusi->merge($riwayatKeluar);

        return view('staff.pj.riwayat', compact('riwayat', 'bagianList', 'gudangList', 'menu', 'userGudang'));
    }

    // ============================================================
    // === FUNGSI DOWNLOAD REPORT (PDF & EXCEL)
    // ============================================================
    public function downloadReport(Request $request)
    {
        $user = auth()->user();

        if (!$user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan.'
            ]);
        }

        $userGudang = $user->gudang;

        $format = $request->download;
        $jenis = $request->jenis ?? 'semua'; // 'masuk', 'keluar', atau 'semua'

        // Query Barang Masuk (Distribusi)
        $riwayatDistribusiQuery = RiwayatBarang::with(['barang.kategori', 'barang.kategori.gudang'])
            ->where('jenis_transaksi', 'distribusi')
            ->where(function ($q) use ($userGudang) {
                $q->where('gudang_tujuan_id', $userGudang->id)
                    ->orWhereHas('barang.kategori', function ($sub) use ($userGudang) {
                        $sub->where('gudang_id', $userGudang->id);
                    });
            });

        // Query Barang Keluar
        $riwayatKeluarQuery = BarangKeluar::with(['barang.kategori', 'gudang', 'user'])
            ->where('gudang_id', $userGudang->id);

        // Filter periode
        if ($request->filled('periode')) {
            $from = null;
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $from = Carbon::now()->subWeek();
                    break;
                case '1_bulan_terakhir':
                    $from = Carbon::now()->subMonth();
                    break;
                case '1_tahun_terakhir':
                    $from = Carbon::now()->subYear();
                    break;
                case 'custom':
                    if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                        $riwayatDistribusiQuery->whereBetween('tanggal', [$request->dari_tanggal, $request->sampai_tanggal]);
                        $riwayatKeluarQuery->whereBetween('tanggal', [$request->dari_tanggal, $request->sampai_tanggal]);
                    }
                    break;
            }

            if ($from) {
                $riwayatDistribusiQuery->where('tanggal', '>=', $from);
                $riwayatKeluarQuery->where('tanggal', '>=', $from);
            }
        }

        // Ambil data sesuai jenis
        $riwayatData = collect();
        if ($jenis == 'masuk' || $jenis == 'semua') {
            $riwayatData = $riwayatData->merge($riwayatDistribusiQuery->get());
        }
        if ($jenis == 'keluar' || $jenis == 'semua') {
            $riwayatData = $riwayatData->merge($riwayatKeluarQuery->get());
        }

        // Format filter info
        $filter = [
            'gudang' => $userGudang->nama,
            'periode' => $request->periode,
            'dari_tanggal' => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal,
        ];

        // === PDF ===
        if ($format == 'pdf') {
            $pdf = Pdf::loadView('staff.pj.riwayat-pdf', compact('riwayatData', 'filter'))
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('Laporan_Riwayat_Barang_' . date('Y-m-d_His') . '.pdf');
        }

        // === EXCEL ===
        if ($format == 'excel') {
            return Excel::download(
                new RiwayatExportPj($riwayatData, $filter),
                'Laporan_Riwayat_Barang_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        return redirect()->back()->with('toast', [
            'type' => 'error',
            'title' => 'Error!',
            'message' => 'Format tidak valid.'
        ]);
    }
}
