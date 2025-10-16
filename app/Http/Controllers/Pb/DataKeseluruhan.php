<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PbStok;
use App\Models\PjStok;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::pbMenu();
        $search = $request->input('search');

        // Ambil Gudang Utama
        $gudangUtama = Gudang::where('nama', 'Gudang Utama')
            ->orWhere('nama', 'LIKE', '%Utama%')
            ->first();

        if (!$gudangUtama) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Gudang Utama tidak ditemukan. Pastikan ada gudang dengan nama "Gudang Utama" di database.'
            ]);
        }

        // Filter kategori hanya dari Gudang Utama dengan eager load barang dan stok
        // PERUBAHAN: Hapus whereHas pbStok agar barang stok 0 juga tampil
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                }
                // Eager load pbStok untuk mendapatkan stok PB
                $q->with(['pbStok']);
            },
            'gudang'
        ])->where('gudang_id', $gudangUtama->id);

        $kategori = $kategoriQuery->get();

        // Ambil semua gudang KECUALI Gudang Utama untuk dropdown distribusi
        $gudang = Gudang::where('id', '!=', $gudangUtama->id)->get();

        $selectedGudang = $gudangUtama;

        $request->validate([
            'harga_min' => 'nullable|numeric|min:0',
            'harga_max' => 'nullable|numeric|min:0',
        ]);

        if (
            $request->filled('harga_min') &&
            $request->filled('harga_max') &&
            $request->harga_min > $request->harga_max
        ) {
            return back()->withErrors([
                'harga_min' => 'Harga minimum tidak boleh lebih besar dari harga maksimum'
            ]);
        }

        // Filter barang hanya dari Gudang Utama menggunakan pb_stok
        $barang = $this->getFilteredBarang($request, $gudangUtama->id);

        return view('staff.pb.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'gudang',
            'selectedGudang'
        ));
    }

    /**
     * API: Search suggestions untuk autocomplete
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        $gudangId = $request->get('gudang_id');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Jika tidak ada gudang_id, ambil Gudang Utama sebagai default
        if (!$gudangId) {
            $gudangUtama = Gudang::where('nama', 'Gudang Utama')
                ->orWhere('nama', 'LIKE', '%Utama%')
                ->first();
            $gudangId = $gudangUtama ? $gudangUtama->id : null;
        }

        // Query barang dengan relasi kategori dan gudang
        // Untuk PB, ambil dari pb_stok
        $barangQuery = Barang::with(['kategori.gudang', 'pbStok'])
            ->where(function ($q) use ($query) {
                $q->where('nama_barang', 'like', "%{$query}%")
                    ->orWhere('kode_barang', 'like', "%{$query}%");
            });

        // Filter by gudang jika ada
        if ($gudangId) {
            $barangQuery->whereHas('kategori', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });
        }

        // PERUBAHAN: Hapus whereHas pbStok agar barang stok 0 juga tampil
        $barang = $barangQuery->limit(10)->get();

        $results = $barang->map(function ($item) {
            // Ambil stok dari tabel pb_stok
            $stok = $item->pbStok ? $item->pbStok->stok : 0;

            // Tentukan status stok
            $stockStatus = 'available';
            if ($stok == 0) {
                $stockStatus = 'empty';
            } elseif ($stok <= 10) {
                $stockStatus = 'low';
            }

            return [
                'id' => $item->kode_barang,
                'nama' => $item->nama_barang,
                'kode' => $item->kode_barang,
                'stok' => $stok,
                'kategori' => $item->kategori->nama ?? '-',
                'gudang' => $item->kategori->gudang->nama ?? '-',
                'stock_status' => $stockStatus
            ];
        })->values();

        return response()->json($results);
    }

    /**
     * API: Get kategori by gudang ID
     */
    public function getKategoriByGudang($gudangId)
    {
        try {
            $kategori = Kategori::where('gudang_id', $gudangId)
                ->orderBy('nama', 'asc')
                ->get(['id', 'nama']);

            return response()->json($kategori);
        } catch (\Exception $e) {
            \Log::error('Error fetching kategori by gudang: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal memuat kategori',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper: Mendapatkan stok barang di pb_stok
     */
    private function getStokBarangPb($kodeBarang)
    {
        $stok = PbStok::where('kode_barang', $kodeBarang)->first();
        return $stok ? $stok->stok : 0;
    }

    /**
     * Filter dan ambil barang dengan eager loading stok dari pb_stok
     */
    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with(['kategori.gudang', 'pbStok']);

        // Filter berdasarkan gudang
        if ($gudangId) {
            $query->whereHas('kategori', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });

            // PERUBAHAN: Hapus whereHas pbStok agar barang stok 0 juga tampil
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kode')) {
            $query->where('kode_barang', 'like', "%{$request->kode}%");
        }

        // Filter stok berdasarkan pb_stok
        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            $query->whereHas('pbStok', function ($q) use ($request) {
                if ($request->filled('stok_min')) {
                    $q->where('stok', '>=', (int) $request->stok_min);
                }
                if ($request->filled('stok_max')) {
                    $q->where('stok', '<=', (int) $request->stok_max);
                }
            });
        }

        if ($request->filled('kategori_id')) {
            $query->where('id_kategori', $request->kategori_id);
        }

        if ($request->filled('satuan')) {
            $query->where('satuan', $request->satuan);
        }

        if ($request->filled('harga_min') && $request->filled('harga_max')) {
            $query->whereBetween('harga_barang', [
                (float) $request->harga_min,
                (float) $request->harga_max,
            ]);
        } elseif ($request->filled('harga_min')) {
            $query->where('harga_barang', '>=', (float) $request->harga_min);
        } elseif ($request->filled('harga_max')) {
            $query->where('harga_barang', '<=', (float) $request->harga_max);
        }

        return $query->get();
    }
}