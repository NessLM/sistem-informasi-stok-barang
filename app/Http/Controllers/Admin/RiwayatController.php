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

class RiwayatController extends Controller
{
    /**
     * Adapt satu baris RiwayatBarang ke shape yang dipakai view/ekspor admin lama.
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

    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();

        // jika ada request download, langsung lempar ke handler download
        if ($request->has('download')) {
            return $this->downloadReport($request);
        }

        // ====== QUERY DASAR (riwayat_barang) ======
        $query = RiwayatBarang::with([
            'barang.kategori.gudang',
            'kategoriAsal',
            'kategoriTujuan',
            'gudangTujuan',
            'user',
        ])->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');

        // Filter alur: "Masuk" → jenis_transaksi=masuk, "Keluar" → keluar|distribusi
        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            if ($request->alur_barang === 'Masuk') {
                $query->where('jenis_transaksi', 'masuk');
            } else {
                $query->whereIn('jenis_transaksi', ['keluar', 'distribusi']);
            }
        }

        // Filter gudang: cocokkan nama/slug pada gudang sumber ATAU gudang tujuan (untuk distribusi)
        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $g = $request->gudang;
            $query->where(function ($q) use ($g) {
                $q->whereHas('barang.kategori.gudang', function ($qq) use ($g) {
                    $qq->where('nama', $g)->orWhere('slug', $g);
                })->orWhereHas('gudangTujuan', function ($qq) use ($g) {
                    $qq->where('nama', $g)->orWhere('slug', $g);
                });
            });
        }

        // Filter periode (pakai kolom 'tanggal' kalau ada; fallback ke created_at)
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

        // Ambil data & mapping ke shape lama
        $riwayat = $query->get()->map(fn ($rb) => $this->mapRbToAdminRow($rb));

        // Daftar gudang unik untuk filter (gabung gudang sumber & tujuan)
        $gudangList = RiwayatBarang::with('barang.kategori.gudang', 'gudangTujuan')
            ->get()
            ->flatMap(function ($rb) {
                return array_filter([
                    optional(optional($rb->barang->kategori)->gudang)->nama,
                    optional($rb->gudangTujuan)->nama,
                ]);
            })
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($nama) => (object) ['gudang' => $nama]);

        return view('staff.admin.riwayat', compact('riwayat', 'menu', 'gudangList'));
    }

    /**
     * Handler unduhan PDF/Excel – tetap kompatibel dengan RiwayatExport & view PDF lama.
     */
    public function downloadReport(Request $request)
    {
        $query = RiwayatBarang::with([
            'barang.kategori.gudang',
            'kategoriAsal',
            'kategoriTujuan',
            'gudangTujuan',
            'user',
        ])->orderBy('tanggal', 'desc')->orderBy('created_at', 'desc');

        if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
            if ($request->alur_barang === 'Masuk') {
                $query->where('jenis_transaksi', 'masuk');
            } else {
                $query->whereIn('jenis_transaksi', ['keluar', 'distribusi']);
            }
        }

        if ($request->filled('gudang') && $request->gudang !== 'Semua') {
            $g = $request->gudang;
            $query->where(function ($q) use ($g) {
                $q->whereHas('barang.kategori.gudang', fn ($qq) => $qq->where('nama', $g)->orWhere('slug', $g))
                  ->orWhereHas('gudangTujuan', fn ($qq) => $qq->where('nama', $g)->orWhere('slug', $g));
            });
        }

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

        $data    = $query->get();
        $riwayat = $data->map(fn ($rb) => $this->mapRbToAdminRow($rb));

        $filter = [
            'alur_barang'   => $request->alur_barang,
            'gudang'        => $request->gudang,
            'periode'       => $request->periode,
            'dari_tanggal'  => $request->dari_tanggal,
            'sampai_tanggal'=> $request->sampai_tanggal,
        ];

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
     * (Opsional) Simpan riwayat baru langsung ke riwayat_barang.
     * Sesuaikan dengan field form yang kamu miliki.
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
