<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Models\BarangKeluar;
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

        // Dapatkan gudang user yang login
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

        // Check if download requested
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // Query dasar - hanya barang keluar dari gudang user
        $query = BarangKeluar::with([
            'barang.kategori',
            'gudang',
            'user'
        ])
            ->where('gudang_id', $userGudang->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan bagian
        if ($request->filled('bagian') && $request->bagian != 'Semua') {
            $query->where('bagian', $request->bagian);
        }

        // Filter berdasarkan periode
        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subYear());
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

        // Ambil data dan transform
        $riwayat = $query->get()->map(function ($item) {
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

        // Ambil list bagian untuk filter (dari barang keluar gudang ini)
        $bagianList = BarangKeluar::where('gudang_id', $userGudang->id)
            ->whereNotNull('bagian')
            ->where('bagian', '!=', '')
            ->select('bagian')
            ->distinct()
            ->orderBy('bagian')
            ->get()
            ->map(function ($item) {
                return (object) ['bagian' => $item->bagian];
            });

        // Untuk backward compatibility dengan view yang menggunakan gudangList
        $gudangList = collect([(object) ['gudang' => $userGudang->nama]]);

        return view('staff.pj.riwayat', compact('riwayat', 'bagianList', 'gudangList', 'menu', 'userGudang'));
    }

    public function downloadReport(Request $request)
    {
        // Dapatkan gudang user yang login
        $user = auth()->user();

        if (!$user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan.'
            ]);
        }

        $userGudang = $user->gudang;

        // Query dasar - hanya barang keluar dari gudang user
        $query = BarangKeluar::with([
            'barang.kategori',
            'gudang',
            'user'
        ])
            ->where('gudang_id', $userGudang->id)
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter berdasarkan bagian
        if ($request->filled('bagian') && $request->bagian != 'Semua') {
            $query->where('bagian', $request->bagian);
        }

        // Filter berdasarkan periode
        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $query->where('tanggal', '>=', Carbon::now()->subYear());
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

        // Ambil data
        $riwayatData = $query->get();

        $format = $request->download;

        // Siapkan filter untuk export
        $filter = [
            'gudang' => $userGudang->nama,
            'bagian' => $request->bagian ?? 'Semua',
            'periode' => $request->periode,
            'dari_tanggal' => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal
        ];

        if ($format == 'pdf') {
            // Transform data untuk PDF
            $riwayat = $riwayatData->map(function ($item) {
                return (object) [
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
                    'user' => optional($item->user)->nama ?? '-',
                ];
            });

            // Generate PDF
            $pdf = Pdf::loadView('staff.pj.riwayat-pdf', compact('riwayat', 'filter'))
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('Laporan_Riwayat_Barang_Keluar_' . date('Y-m-d_His') . '.pdf');
        }

        if ($format == 'excel') {
            // Download Excel
            return Excel::download(
                new RiwayatExportPj($riwayatData, $filter),
                'Laporan_Riwayat_Barang_Keluar_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        return redirect()->back()->with('toast', [
            'type' => 'error',
            'title' => 'Error!',
            'message' => 'Format tidak valid'
        ]);
    }
}