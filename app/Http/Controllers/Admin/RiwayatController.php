<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\Barang;
use App\Models\TransaksiDistribusi;
use App\Models\TransaksiBarangKeluar;
use App\Models\TransaksiBarangMasuk;
use App\Models\Bagian; // PERUBAHAN: Gunakan Bagian bukan Gudang

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
     * Map Transaksi Barang Masuk (Admin ke PB/Gudang Utama)
     */
    private function mapBarangMasukToAdminRow(TransaksiBarangMasuk $item)
    {
        return (object) [
            'tanggal' => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu' => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Masuk',
            'gudang' => 'Pengurus Barang Pembantu', // PERUBAHAN: Sesuaikan terminologi
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah' => (int) ($item->jumlah ?? 0),
            'satuan' => optional($item->barang)->satuan ?? '-',
            'bukti' => $item->bukti,
            'bukti_path' => $item->bukti && Storage::exists('public/' . $item->bukti)
                ? asset('storage/' . str_replace('\\', '/', $item->bukti))
                : null,
            'keterangan' => $item->keterangan ?? 'Barang masuk'
        ];
    }

    private function mapDistribusiToAdminRow(TransaksiDistribusi $item)
    {
        return (object) [
            'tanggal' => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu' => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Distribusi',
            'gudang' => optional($item->bagian)->nama ?? '-', // PERUBAHAN: Gunakan bagian
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah' => (int) ($item->jumlah ?? 0),
            'satuan' => optional($item->barang)->satuan ?? '-',
            'bukti' => $item->bukti,
            'bukti_path' => $item->bukti && Storage::exists('public/' . $item->bukti)
                ? asset('storage/' . str_replace('\\', '/', $item->bukti))
                : null,
            'keterangan' => $item->keterangan ?? 'Distribusi barang'
        ];
    }

    private function mapBarangKeluarToAdminRow(TransaksiBarangKeluar $item)
    {
        return (object) [
            'tanggal' => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu' => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Keluar',
            'gudang' => optional($item->bagian)->nama ?? '-', // PERUBAHAN: Gunakan bagian langsung
            'nama_barang' => optional($item->barang)->nama_barang ?? '-',
            'jumlah' => (int) ($item->jumlah ?? 0),
            'satuan' => optional($item->barang)->satuan ?? '-',
            'bagian' => optional($item->bagian)->nama ?? '-',
            'penerima' => $item->nama_penerima ?? '-',
            'bukti' => $item->bukti,
            'bukti_path' => $item->bukti && Storage::exists('public/' . $item->bukti)
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

        // PERUBAHAN: Filter berdasarkan bagian_id
        $bagianIdForFilter = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $bagianIdForFilter = Bagian::where('nama', $request->gudang)->value('id');
        }

        // ===== TABEL 1: Barang Masuk (Admin -> PB) =====
        $barangMasukQuery = TransaksiBarangMasuk::with(['barang.kategori', 'user']);
        $this->applyPeriodeFilter($barangMasukQuery, $request);

        $riwayatBarangMasuk = $barangMasukQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapBarangMasukToAdminRow($item))
            ->values()
            ->toBase();

        // ===== TABEL 2: Distribusi (PB -> PJ) =====
        $distribusiQuery = TransaksiDistribusi::with(['barang.kategori', 'bagian', 'user']);

        if ($bagianIdForFilter) {
            $distribusiQuery->where('bagian_id', $bagianIdForFilter);
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
        $keluarQuery = TransaksiBarangKeluar::with(['barang.kategori', 'bagian', 'user']);

        if ($bagianIdForFilter) {
            $keluarQuery->where('bagian_id', $bagianIdForFilter);
        }

        $this->applyPeriodeFilter($keluarQuery, $request);

        $riwayatBarangKeluar = $keluarQuery
            ->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => $this->mapBarangKeluarToAdminRow($item))
            ->values()
            ->toBase();

        // PERUBAHAN: Gunakan Bagian untuk dropdown filter
        $gudangList = Bagian::orderBy('nama')->get()
            ->map(fn($b) => (object) ['gudang' => $b->nama])
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
        // PERUBAHAN: Filter berdasarkan bagian_id
        $bagianIdForFilter = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $bagianIdForFilter = Bagian::where('nama', $request->gudang)->value('id');
        }

        // Barang Masuk
        $barangMasukQuery = TransaksiBarangMasuk::with(['barang.kategori', 'user']);
        $this->applyPeriodeFilter($barangMasukQuery, $request);
        $rowsBarangMasuk = $barangMasukQuery->get()->map(fn($item) => $this->mapBarangMasukToAdminRow($item))->values()->toBase();

        // Distribusi
        $distribusiQuery = TransaksiDistribusi::with(['barang.kategori', 'bagian', 'user']);
        if ($bagianIdForFilter) {
            $distribusiQuery->where('bagian_id', $bagianIdForFilter);
        }
        $this->applyPeriodeFilter($distribusiQuery, $request);
        $rowsDistribusi = $distribusiQuery->get()->map(fn($item) => $this->mapDistribusiToAdminRow($item))->values()->toBase();

        // Barang Keluar
        $keluarQuery = TransaksiBarangKeluar::with(['barang.kategori', 'bagian', 'user']);
        if ($bagianIdForFilter) {
            $keluarQuery->where('bagian_id', $bagianIdForFilter);
        }
        $this->applyPeriodeFilter($keluarQuery, $request);
        $rowsBarangKeluar = $keluarQuery->get()->map(fn($item) => $this->mapBarangKeluarToAdminRow($item))->values()->toBase();

        // Gabungkan semua data
        $riwayat = $rowsBarangMasuk->concat($rowsDistribusi)->concat($rowsBarangKeluar);

        $riwayat = $riwayat->sortByDesc(function ($x) {
            return ($x->tanggal ?? '1970-01-01') . ' ' . ($x->waktu ?? '00:00:00');
        })->values();

        $filter = [
            'gudang' => $request->gudang,
            'periode' => $request->periode,
            'dari_tanggal' => $request->dari_tanggal,
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
                ->setPaper('a4', 'landscape')
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