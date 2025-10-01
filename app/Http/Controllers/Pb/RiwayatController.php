<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\RiwayatBarang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RiwayatController extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::pbMenu();

        try {
            $query = RiwayatBarang::with([
                'barang.kategori.gudang',
                'kategoriAsal.gudang',
                'kategoriTujuan.gudang',
                'gudangTujuan',
                'barangTujuan',
                'user'
            ]);

            // Filter Alur Barang (Masuk/Keluar)
            if ($request->filled('alur_barang') && $request->alur_barang !== 'Semua') {
                if ($request->alur_barang === 'Masuk') {
                    $query->where('jenis_transaksi', 'masuk');
                } elseif ($request->alur_barang === 'Keluar') {
                    $query->where('jenis_transaksi', 'distribusi');
                }
            }

            // Filter Gudang
            if ($request->filled('gudang') && $request->gudang !== 'Semua') {
                $query->whereHas('barang.kategori.gudang', function($q) use ($request) {
                    $q->where('nama', $request->gudang);
                });
            }

            // Filter Periode
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

            // Urutkan berdasarkan tanggal terbaru
            $riwayatData = $query->orderBy('tanggal', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('Total riwayat ditemukan: ' . $riwayatData->count());

            // Transform data untuk kompatibilitas dengan view
            $riwayat = $riwayatData->map(function($item) {
                return (object)[
                    'id' => $item->id,
                    'tanggal' => $item->tanggal->format('Y-m-d'),
                    'waktu' => $item->created_at->format('H:i:s'),
                    'gudang' => $item->barang->kategori->gudang->nama ?? '-',
                    'nama_barang' => $item->barang->nama ?? '-',
                    'jumlah' => $item->jumlah,
                    'alur_barang' => $item->jenis_transaksi === 'masuk' ? 'Masuk' : 'Keluar',
                    'bukti' => $item->bukti ? basename($item->bukti) : null,
                    'bukti_path' => $item->bukti,
                    'kategori_asal' => $item->kategoriAsal->nama ?? '-',
                    'kategori_tujuan' => $item->kategoriTujuan->nama ?? '-',
                    'gudang_tujuan' => $item->gudangTujuan->nama ?? '-',
                    'keterangan' => $item->keterangan,
                    'stok_sebelum' => $item->stok_sebelum,
                    'stok_sesudah' => $item->stok_sesudah,
                    'user' => $item->user->name ?? '-',
                ];
            });

            // Daftar gudang untuk filter
            $gudangList = Gudang::select('nama as gudang')
                ->orderBy('nama')
                ->get();

            return view('staff.pb.riwayat', compact('riwayat', 'menu', 'gudangList'));

        } catch (\Exception $e) {
            Log::error('Error loading riwayat: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return view('staff.pb.riwayat', [
                'riwayat' => collect([]),
                'menu' => $menu,
                'gudangList' => collect([])
            ])->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Gagal memuat riwayat: ' . $e->getMessage()
            ]);
        }
    }
}