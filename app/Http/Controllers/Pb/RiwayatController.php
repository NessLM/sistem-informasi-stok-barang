<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\TransaksiBarangMasuk;
use App\Models\TransaksiDistribusi;
use App\Models\Gudang;
use App\Helpers\MenuHelper;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RiwayatExportPb;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RiwayatController extends Controller
{
    /**
     * Map Transaksi Barang Masuk (dari Admin ke PB) menjadi row "Masuk"
     */
    private function mapBarangMasukToPbRow(TransaksiBarangMasuk $item)
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
            'tanggal'       => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'         => optional($item->created_at)->format('H:i:s'),
            'alur_barang'   => 'Masuk',
            'gudang'        => 'Gudang Utama', // Barang masuk ke PB (Gudang Utama)
            'bagian_nama' => $bagianNama,
            'nama_barang'   => $item->__barang_nama ?? (optional($item->barang)->nama_barang ?? '-'),
            'jumlah'        => (int) ($item->jumlah ?? 0),
            'satuan'        => $item->__barang_satuan ?? (optional($item->barang)->satuan ?? '-'),
            'keterangan'  => $item->keterangan ?? 'Barang masuk',
            'bukti'         => $item->bukti,
            'bukti_path'    => $item->bukti ? asset('storage/' . $item->bukti) : null,
            // Tambahkan properti untuk konsistensi dengan view
            'kategori_asal' => 'Admin', // Barang berasal dari Admin
            'kategori_tujuan' => 'Gudang Utama', // Barang menuju ke Gudang Utama
        ];
    }

    /**
     * Map Transaksi Distribusi (dari PB ke PJ) menjadi row "Keluar"
     */
    private function mapDistribusiToPbRow(TransaksiDistribusi $item)
    {
        return (object) [
            'tanggal'         => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'           => optional($item->created_at)->format('H:i:s'),
            'alur_barang'     => 'Keluar',
            'gudang'          => 'Gudang Utama', // Selalu dari Gudang Utama (PB)
            'nama_barang'     => optional($item->barang)->nama_barang ?? '-',
            'jumlah'          => (int) ($item->jumlah ?? 0),
            'satuan'          => optional($item->barang)->satuan ?? '-',
            'gudang_tujuan' => $item->__bagian_nama ?? '-',   // <- pasti isi dari join
            'keterangan'      => $item->keterangan ?? 'Barang Keluar',
            'bukti'           => $item->bukti,
            'bukti_path'      => $item->bukti ? asset('storage/' . $item->bukti) : null,
            // Tambahkan properti untuk konsistensi dengan view
            'kategori_asal'   => 'Gudang Utama', // Selalu dari Gudang Utama
            'kategori_tujuan' => optional($item->gudangTujuan)->nama ?? '-', // Sama dengan gudang_tujuan
        ];
    }


    public function index(Request $request)
    {
        $menu = MenuHelper::pbMenu();

        // Check if download requested
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // ---------------------------------------------------------------
        // MASUK: Transaksi Barang Masuk (dari Admin ke PB)
        // ---------------------------------------------------------------
        $barangMasukQuery = TransaksiBarangMasuk::with([
            'barang.kategori',
            'user'
        ]);

        // Filter periode untuk barang masuk
        $this->applyPeriodeFilter($barangMasukQuery, $request);

        $riwayatMasuk = $barangMasukQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapBarangMasukToPbRow($item))
            ->values()
            ->toBase();

        // ---------------------------------------------------------------
        // KELUAR: Transaksi Distribusi (dari PB ke PJ)
        // ---------------------------------------------------------------
        $distribusiQuery = TransaksiDistribusi::query()
            ->leftJoin('bagian as bg', 'bg.id', '=', 'transaksi_distribusi.bagian_id')
            ->leftJoin('barang as b', 'b.kode_barang', '=', 'transaksi_distribusi.kode_barang')
            ->select([
                'transaksi_distribusi.*',
                DB::raw('bg.nama as __bagian_nama'),
                DB::raw('b.satuan as __barang_satuan'),
                DB::raw('b.nama_barang as __barang_nama'),
            ]);

        // Filter gudang tujuan
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $distribusiQuery->whereHas('gudangTujuan', function ($q) use ($request) {
                $q->where('nama', $request->gudang);
            });
        }

        // Filter periode untuk distribusi
        $this->applyPeriodeFilter($distribusiQuery, $request);

        $riwayatKeluar = $distribusiQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapDistribusiToPbRow($item))
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
        // Ambil list gudang untuk filter (hanya gudang tujuan dari distribusi)
        // ---------------------------------------------------------------
        $gudangList = TransaksiDistribusi::with('gudangTujuan')
            ->get()
            ->pluck('gudangTujuan.nama')
            ->unique()
            ->filter()
            ->map(function ($nama) {
                return (object) ['gudang' => $nama];
            })
            ->values();

        return view('staff.pb.riwayat', compact('riwayat', 'gudangList', 'menu'));
    }

    public function downloadReport(Request $request)
    {
        // ---------------------------------------------------------------
        // MASUK: Transaksi Barang Masuk
        // ---------------------------------------------------------------
        $barangMasukQuery = TransaksiBarangMasuk::with([
            'barang.kategori',
            'user',
            'bagian' // TAMBAHKAN INI
        ]);

        // Filter periode
        $this->applyPeriodeFilter($barangMasukQuery, $request);

        // ---------------------------------------------------------------
        // KELUAR: Transaksi Distribusi - GUNAKAN QUERY YANG SAMA DENGAN WEB
        // ---------------------------------------------------------------
        $distribusiQuery = TransaksiDistribusi::query()
            ->leftJoin('bagian as bg', 'bg.id', '=', 'transaksi_distribusi.bagian_id')
            ->leftJoin('barang as b', 'b.kode_barang', '=', 'transaksi_distribusi.kode_barang')
            ->select([
                'transaksi_distribusi.*',
                DB::raw('bg.nama as __bagian_nama'),
                DB::raw('b.satuan as __barang_satuan'),
                DB::raw('b.nama_barang as __barang_nama'),
            ]);

        // Filter gudang tujuan
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $distribusiQuery->whereHas('gudangTujuan', function ($q) use ($request) {
                $q->where('nama', $request->gudang);
            });
        }

        // Filter periode
        $this->applyPeriodeFilter($distribusiQuery, $request);

        // Map ke format PB - GUNAKAN METHOD YANG SAMA
        $rowsMasuk = $barangMasukQuery->get()
            ->map(fn($item) => $this->mapBarangMasukToPbRow($item))
            ->values()
            ->toBase();

        $rowsKeluar = $distribusiQuery->get()
            ->map(fn($item) => $this->mapDistribusiToPbRow($item))
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

        // Siapkan filter untuk export
        $filter = [
            'alur_barang'    => $request->alur_barang,
            'gudang'         => $request->gudang,
            'periode'        => $request->periode,
            'dari_tanggal'   => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal
        ];

        $format = $request->download;

        if ($format == 'pdf') {
            // Generate PDF
            $pdf = Pdf::loadView('staff.pb.riwayat-pdf', compact('riwayat', 'filter'))
                ->setPaper('a4', 'portrait') // UBAH KE PORTRAIT
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('Laporan_Riwayat_Barang_PB_' . date('Y-m-d_His') . '.pdf');
        }

        if ($format == 'excel') {
            // Download Excel
            return Excel::download(
                new RiwayatExportPb($riwayat, $filter),
                'Laporan_Riwayat_Barang_PB_' . date('Y-m-d_His') . '.xlsx'
            );
        }

        return redirect()->back()->with('error', 'Format tidak valid');
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
