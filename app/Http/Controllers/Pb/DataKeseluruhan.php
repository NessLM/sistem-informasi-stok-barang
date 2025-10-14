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
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                }
                $q->with(['pbStok', 'pjStok']);
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

        // Filter barang hanya dari Gudang Utama
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
        $barangQuery = Barang::with(['kategori.gudang', 'pbStok', 'pjStok'])
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

        $barang = $barangQuery->limit(10)->get();

        $results = $barang->map(function ($item) use ($gudangId) {
            // Ambil stok dari tabel pj_stok berdasarkan id_gudang
            $pjStok = PjStok::where('kode_barang', $item->kode_barang)
                ->where('id_gudang', $gudangId)
                ->first();
            
            $stok = $pjStok ? $pjStok->stok : 0;

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
        })->filter(function ($item) {
            // Filter: hanya tampilkan barang yang stoknya > 0
            return $item['stok'] > 0;
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
     * Helper: Mendapatkan stok barang di gudang tertentu
     */
    private function getStokBarangGudang($kodeBarang, $gudangId)
    {
        if (!$gudangId) {
            return 0;
        }

        $stok = PjStok::where('kode_barang', $kodeBarang)
            ->where('id_gudang', $gudangId)
            ->first();

        return $stok ? $stok->stok : 0;
    }

    /**
     * Filter dan ambil barang dengan eager loading stok
     */
    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with(['kategori.gudang', 'pbStok', 'pjStok']);

        // Filter berdasarkan gudang
        if ($gudangId) {
            $query->whereHas('kategori', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });

            // Exclude barang dengan stok 0 di gudang tertentu menggunakan PjStok
            $query->whereHas('pjStok', function ($q) use ($gudangId) {
                $q->where('id_gudang', $gudangId)
                  ->where('stok', '>', 0);
            }, '>=', 1);
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

        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            $query->whereHas('pjStok', function ($q) use ($request, $gudangId) {
                if ($gudangId) {
                    $q->where('id_gudang', $gudangId);
                }
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