<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\RiwayatBarang;
use App\Models\Gudang;
use App\Helpers\MenuHelper;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        // Check if download requested
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // Query dasar
        $query = RiwayatBarang::with([
            'barang.kategori.gudang',
            'kategoriAsal',
            'kategoriTujuan',
            'gudangTujuan',
            'user'
        ])->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');

        // Filter berdasarkan alur barang
        if ($request->filled('alur_barang') && $request->alur_barang != 'Semua') {
            if ($request->alur_barang == 'Masuk') {
                $query->where('jenis_transaksi', 'masuk');
            } else {
                $query->where('jenis_transaksi', 'distribusi');
            }
        }

        // Filter berdasarkan gudang
        if ($request->filled('gudang') && $request->gudang != 'Semua') {
            $query->whereHas('barang.kategori.gudang', function ($q) use ($request) {
                $q->where('nama', $request->gudang);
            });
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
                'tanggal' => $item->tanggal,
                'waktu' => $item->created_at->format('H:i:s'),
                'alur_barang' => $item->jenis_transaksi == 'masuk' ? 'Masuk' : 'Keluar',
                'gudang' => optional(optional($item->barang->kategori)->gudang)->nama ?? '-',
                'nama_barang' => optional($item->barang)->nama ?? '-',
                'jumlah' => $item->jumlah,
                'kategori_asal' => optional($item->kategoriAsal)->nama ?? '-',
                'kategori_tujuan' => optional($item->kategoriTujuan)->nama ?? '-',
                'gudang_tujuan' => optional($item->gudangTujuan)->nama ?? '-',
                'bukti' => $item->bukti,
                'bukti_path' => $item->bukti ? $item->bukti : null,
            ];
        });

        // Ambil list gudang untuk filter
        $gudangList = RiwayatBarang::with('barang.kategori.gudang')
            ->get()
            ->pluck('barang.kategori.gudang.nama')
            ->unique()
            ->filter()
            ->map(function ($nama) {
                return (object) ['gudang' => $nama];
            })
            ->values();

        // Menu untuk sidebar/navigation
        $menu = $this->getMenu();

        return view('staff.pb.riwayat', compact('riwayat', 'gudangList', 'menu'));
    }

    /**
     * Get menu items untuk navigation
     */
    private function getMenu()
    {
        return [
            [
                'label' => 'Dashboard',
                'route' => 'pb.dashboard',
                'icon' => 'bi-speedometer2'
            ],
            [
                'label' => 'Riwayat',
                'route' => 'pb.riwayat.index',
                'icon' => 'bi-clock-history'
            ],
            [
                'label' => 'Data Keseluruhan',
                'route' => 'pb.datakeseluruhan.index',
                'icon' => 'bi-box-seam'
            ],
        ];
    }

    public function downloadReport(Request $request)
    {
        // Query dasar (sama dengan index)
        $query = RiwayatBarang::with([
            'barang.kategori.gudang',
            'kategoriAsal',
            'kategoriTujuan',
            'gudangTujuan',
            'user'
        ])->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');

        // Filter berdasarkan alur barang
        if ($request->filled('alur_barang') && $request->alur_barang != 'Semua') {
            if ($request->alur_barang == 'Masuk') {
                $query->where('jenis_transaksi', 'masuk');
            } else {
                $query->where('jenis_transaksi', 'distribusi');
            }
        }

        // Filter berdasarkan gudang
        if ($request->filled('gudang') && $request->gudang != 'Semua') {
            $query->whereHas('barang.kategori.gudang', function ($q) use ($request) {
                $q->where('nama', $request->gudang);
            });
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
                'tanggal' => $item->tanggal,
                'waktu' => $item->created_at->format('H:i:s'),
                'alur_barang' => $item->jenis_transaksi == 'masuk' ? 'Masuk' : 'Keluar',
                'gudang' => optional(optional($item->barang->kategori)->gudang)->nama ?? '-',
                'nama_barang' => optional($item->barang)->nama ?? '-',
                'jumlah' => $item->jumlah,
                'kategori_asal' => optional($item->kategoriAsal)->nama ?? '-',
                'kategori_tujuan' => optional($item->kategoriTujuan)->nama ?? '-',
                'gudang_tujuan' => optional($item->gudangTujuan)->nama ?? '-',
                'bukti' => $item->bukti,
                'bukti_path' => $item->bukti ? $item->bukti : null,
            ];
        });

        $format = $request->download;

        if ($format == 'pdf') {
            // Generate PDF
            $pdf = Pdf::loadView('staff.pb.riwayat-pdf', compact('riwayat'))
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('Laporan_Riwayat_Barang_' . date('Y-m-d_His') . '.pdf');
        }

        if ($format == 'excel') {
            // Implementasi Excel jika diperlukan
            return $this->downloadExcel($riwayat);
        }

        return redirect()->back()->with('error', 'Format tidak valid');
    }

    private function downloadExcel($riwayat)
    {
        // Implementasi download Excel menggunakan Laravel Excel
        // Jika belum install: composer require maatwebsite/excel
        
        return redirect()->back()->with('info', 'Fitur Excel sedang dalam pengembangan');
    }
}