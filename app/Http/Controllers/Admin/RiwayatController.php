<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;
use App\Models\RiwayatBarang;
use App\Models\Barang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\RiwayatExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

// [CHANGED] Tambahan import agar Admin bisa ambil sumber kedua & bantu filter gudang
use App\Models\BarangKeluar;   // sumber "Keluar" (mirip PJ)
use App\Models\Gudang;         // untuk lookup id gudang dari nama/slug & build gudangList

class RiwayatController extends Controller
{
    /**
     * (ASLI) Mapper lama Admin berbasis satu tabel riwayat_barang.
     * DIBIARKAN agar tidak menghapus hal yg tidak perlu.
     * Saat ini tidak dipakai di index()/downloadReport() yg baru.
     */
    private function mapRbToAdminRow(RiwayatBarang $item)
    {
        $alur = $item->jenis_transaksi === 'masuk' ? 'Masuk' : 'Keluar';

        // gudang: sumber dari barang->kategori->gudang; untuk distribusi fallback ke gudang tujuan
        $gudangSumber     = optional(optional($item->barang->kategori)->gudang)->nama;
        $gudangDistribusi = optional($item->gudangTujuan)->nama;

        return (object) [
            'tanggal'       => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'         => optional($item->created_at)->format('H:i:s'),
            'alur_barang'   => $alur,
            'gudang'        => $alur === 'Keluar' ? ($gudangDistribusi ?: $gudangSumber) : ($gudangSumber ?: '-'),
            'nama_barang'   => optional($item->barang)->nama ?? '-',
            'jumlah'        => (int) $item->jumlah,
            // di skema lama 'bagian' ~ unit/kategori tujuan
            'bagian'        => optional($item->kategoriTujuan)->nama ?? '-',
            'bukti'         => $item->bukti,
            'bukti_path'    => $item->bukti ?: null,
        ];
    }

    // =====================================================================
    // [CHANGED] Tambah 2 mapper baru — menyamakan source & shape dengan PJ
    // =====================================================================

    /**
     * Distribusi dari riwayat_barang → ditampilkan sebagai "Masuk" (mirip PJ).
     */
    private function mapDistribusiToAdminRow(RiwayatBarang $item)
    {
        return (object) [
            'tanggal'     => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'       => optional($item->created_at)->format('H:i:s'),
            'alur_barang' => 'Masuk',
            'gudang'      => optional(optional($item->barang->kategori)->gudang)->nama ?? '-',
            'nama_barang' => optional($item->barang)->nama ?? '-',
            'jumlah'      => (int) ($item->jumlah ?? 0),
            // riwayat_barang tidak punya 'bagian' langsung → pakai kategori tujuan bila ada
            'bagian'      => optional($item->kategoriTujuan)->nama ?? '-',
            'bukti'       => $item->bukti,
            'bukti_path'  => $item->bukti ? asset('storage/bukti/' . $item->bukti) : null,
        ];
    }

    /**
     * Transaksi keluar dari tabel barang_keluar → "Keluar" (mirip PJ).
     */
    private function mapBkToAdminRow(BarangKeluar $item)
    {
        return (object) [
            'tanggal'       => $item->tanggal ?? optional($item->created_at)->toDateString(),
            'waktu'         => optional($item->created_at)->format('H:i:s'),
            'alur_barang'   => 'Keluar',
            'gudang'        => optional($item->gudang)->nama ?? '-',
            'nama_barang'   => optional($item->barang)->nama ?? '-',
            'jumlah'        => (int) ($item->jumlah ?? 0),
            'bagian'        => optional($item->bagian)->nama ?? '-',
            'bukti'         => $item->bukti,
            'bukti_path'    => $item->bukti ? asset('storage/' . $item->bukti) : null,
        ];
    }

    // =====================================================================
    // INDEX
    // =====================================================================
    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();

        // [CHANGED] Jika ada request download, langsung lempar — (tetap sama)
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // [CHANGED] Lookup id gudang untuk filter barang_keluar (berdasarkan nama/slug)
        $gudangIdForKeluar = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $gudangIdForKeluar = Gudang::where('nama', $request->gudang)
                ->orWhere('slug', $request->gudang)
                ->value('id');
        }

        // ---------------------------------------------------------------
        // [CHANGED] MASUK (Ambil dari RIWAYAT_BARANG jenis 'distribusi')
        // ---------------------------------------------------------------
        $rbQuery = RiwayatBarang::with([
                'barang.kategori.gudang',
                'kategoriTujuan',
                'gudangTujuan',
                'user',
            ])
            ->where('jenis_transaksi', 'distribusi');

        // Filter gudang (nama/slug) → match gudang sumber ATAU tujuan
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $g = $request->gudang;
            $rbQuery->where(function ($q) use ($g) {
                $q->whereHas('barang.kategori.gudang', function ($qq) use ($g) {
                    $qq->where('nama', $g)->orWhere('slug', $g);
                })->orWhereHas('gudangTujuan', function ($qq) use ($g) {
                    $qq->where('nama', $g)->orWhere('slug', $g);
                });
            });
        }

        // Filter periode
        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $rbQuery->whereDate('tanggal', '>=', Carbon::now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $rbQuery->whereDate('tanggal', '>=', Carbon::now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $rbQuery->whereDate('tanggal', '>=', Carbon::now()->subYear());
                    break;
                case 'custom':
                    if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                        $rbQuery->whereBetween('tanggal', [$request->dari_tanggal, $request->sampai_tanggal]);
                    }
                    break;
            }
        }

        $riwayatMasuk = $rbQuery->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($rb) => $this->mapDistribusiToAdminRow($rb))
            ->values()
            ->toBase(); // base collection → concat aman

        // ---------------------------------------------------------------
        // [CHANGED] KELUAR (Ambil dari BARANG_KELUAR)
        // ---------------------------------------------------------------
        $bkQuery = BarangKeluar::with(['barang.kategori', 'gudang', 'user', 'bagian']);

        if ($gudangIdForKeluar) {
            $bkQuery->where('gudang_id', $gudangIdForKeluar);
        }

        // Filter periode
        if ($request->filled('periode')) {
            switch ($request->periode) {
                case '1_minggu_terakhir':
                    $bkQuery->whereDate('tanggal', '>=', Carbon::now()->subWeek());
                    break;
                case '1_bulan_terakhir':
                    $bkQuery->whereDate('tanggal', '>=', Carbon::now()->subMonth());
                    break;
                case '1_tahun_terakhir':
                    $bkQuery->whereDate('tanggal', '>=', Carbon::now()->subYear());
                    break;
                case 'custom':
                    if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                        $bkQuery->whereBetween('tanggal', [$request->dari_tanggal, $request->sampai_tanggal]);
                    }
                    break;
            }
        }

        $riwayatKeluar = $bkQuery->orderBy('tanggal', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($bk) => $this->mapBkToAdminRow($bk))
            ->values()
            ->toBase();

        // ---------------------------------------------------------------
        // [CHANGED] Filter "Alur Barang" ala PJ:
        //   - Masuk  → hanya distribusi (riwayatMasuk)
        //   - Keluar → hanya barang_keluar (riwayatKeluar)
        //   - Semua  → gabungan
        // ---------------------------------------------------------------
        $riwayat = collect()->toBase();
        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            $riwayat = $request->alur_barang === 'Masuk' ? $riwayatMasuk : $riwayatKeluar;
        } else {
            $riwayat = $riwayatMasuk->concat($riwayatKeluar);
        }

        // Urut terbaru
        $riwayat = $riwayat->sortByDesc(fn($x) => ($x->tanggal ?? '1970-01-01') . ' ' . ($x->waktu ?? '00:00:00'))
                           ->values();

        // ---------------------------------------------------------------
        // [CHANGED] Daftar gudang untuk filter — ambil dari tabel Gudang
        //   Supaya mencakup gudang yang punya data di barang_keluar saja.
        // ---------------------------------------------------------------
        $gudangList = Gudang::orderBy('nama')->get()
            ->map(fn($g) => (object) ['gudang' => $g->nama])
            ->values();

        return view('staff.admin.riwayat', compact('riwayat', 'menu', 'gudangList'));
    }

    // =====================================================================
    // DOWNLOAD (PDF/Excel) — disamakan dengan logika index() di atas
    // =====================================================================
    public function downloadReport(Request $request)
    {
        // [CHANGED] Lookup id gudang untuk barang_keluar
        $gudangIdForKeluar = null;
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $gudangIdForKeluar = Gudang::where('nama', $request->gudang)
                ->orWhere('slug', $request->gudang)
                ->value('id');
        }

        // Distribusi (Masuk)
        $rbQuery = RiwayatBarang::with([
                'barang.kategori.gudang',
                'kategoriTujuan',
                'gudangTujuan',
                'user',
            ])
            ->where('jenis_transaksi', 'distribusi');

        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $g = $request->gudang;
            $rbQuery->where(function ($q) use ($g) {
                $q->whereHas('barang.kategori.gudang', fn($qq) => $qq->where('nama', $g)->orWhere('slug', $g))
                  ->orWhereHas('gudangTujuan', fn($qq) => $qq->where('nama', $g)->orWhere('slug', $g));
            });
        }

        // Keluar
        $bkQuery = BarangKeluar::with(['barang.kategori', 'gudang', 'user', 'bagian']);

        if ($gudangIdForKeluar) {
            $bkQuery->where('gudang_id', $gudangIdForKeluar);
        }

        // Filter periode untuk keduanya
        if ($request->filled('periode')) {
            $applyPeriod = function ($q) use ($request) {
                switch ($request->periode) {
                    case '1_minggu_terakhir':
                        $q->whereDate('tanggal', '>=', Carbon::now()->subWeek()); break;
                    case '1_bulan_terakhir':
                        $q->whereDate('tanggal', '>=', Carbon::now()->subMonth()); break;
                    case '1_tahun_terakhir':
                        $q->whereDate('tanggal', '>=', Carbon::now()->subYear()); break;
                    case 'custom':
                        if ($request->filled('dari_tanggal') && $request->filled('sampai_tanggal')) {
                            $q->whereBetween('tanggal', [$request->dari_tanggal, $request->sampai_tanggal]);
                        }
                        break;
                }
            };
            $applyPeriod($rbQuery);
            $applyPeriod($bkQuery);
        }

        // Map ke shape admin & gabungkan (mirip index)
        $rowsMasuk  = $rbQuery->get()->map(fn($rb) => $this->mapDistribusiToAdminRow($rb))->values()->toBase();
        $rowsKeluar = $bkQuery->get()->map(fn($bk) => $this->mapBkToAdminRow($bk))->values()->toBase();

        $riwayat = collect()->toBase();
        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            $riwayat = $request->alur_barang === 'Masuk' ? $rowsMasuk : $rowsKeluar;
        } else {
            $riwayat = $rowsMasuk->concat($rowsKeluar);
        }

        $filter = [
            'alur_barang'    => $request->alur_barang,
            'gudang'         => $request->gudang,
            'periode'        => $request->periode,
            'dari_tanggal'   => $request->dari_tanggal,
            'sampai_tanggal' => $request->sampai_tanggal,
        ];

        // Export
        if ($request->download === 'excel') {
            return Excel::download(new RiwayatExport($riwayat, $filter), 'riwayat-barang-'.date('Y-m-d_His').'.xlsx');
        }

        if ($request->download === 'pdf') {
            $pdf = Pdf::loadView('staff.admin.riwayat-pdf', compact('riwayat', 'filter'))
                ->setPaper('a4', 'landscape')
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', true);

            return $pdf->download('riwayat-barang-'.date('Y-m-d_His').'.pdf');
        }

        return back()->with('error', 'Format tidak valid');
    }

    /**
     * (ASLI) Simpan riwayat baru langsung ke riwayat_barang.
     * Bagian ini TIDAK diubah.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'barang_id'          => 'required|exists:barang,id',
            'jenis_transaksi'    => 'required|in:masuk,keluar,distribusi',
            'jumlah'             => 'required|integer|min:1',
            'kategori_asal_id'   => 'nullable|exists:kategori,id',
            'kategori_tujuan_id' => 'nullable|exists:kategori,id',
            'gudang_tujuan_id'   => 'nullable|exists:gudang,id',
            'keterangan'         => 'nullable|string',
            'bukti'              => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($request->hasFile('bukti')) {
            $validated['bukti'] = $request->file('bukti')->store('bukti', 'public'); // simpan path
        }

        // Hitung stok sebelum/sesudah (kalau kolom tersedia dan kamu ingin update stok)
        $barang       = Barang::find($validated['barang_id']);
        $stokSebelum  = $barang->stok ?? null;
        $stokSesudah  = $stokSebelum;

        if (!is_null($stokSebelum)) {
            if ($validated['jenis_transaksi'] === 'masuk') {
                $stokSesudah = $stokSebelum + (int) $validated['jumlah'];
            } else {
                $stokSesudah = max(0, $stokSebelum - (int) $validated['jumlah']);
            }
        }

        $payload = array_merge($validated, [
            'stok_sebelum' => $stokSebelum,
            'stok_sesudah' => $stokSesudah,
            'user_id'      => Auth::id(),
            // Kalau kamu punya kolom tanggal/waktu khusus:
            // 'tanggal'   => now()->toDateString(),
            // 'waktu'     => now()->format('H:i:s'),
        ]);

        RiwayatBarang::create($payload);

        // (opsional) update stok master barang
        if (!is_null($stokSebelum) && isset($barang)) {
            $barang->update(['stok' => $stokSesudah]);
        }

        return redirect()->route('admin.riwayat.index')
            ->with('success', 'Riwayat berhasil disimpan');
    }
}
