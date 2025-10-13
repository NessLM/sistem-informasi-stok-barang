<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\StokGudang;
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

        // Filter kategori hanya dari Gudang Utama dengan eager load barang dan stokGudang
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                }
                // Eager load stokGudang untuk setiap barang
                $q->with('stokGudang');
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

        $barangQuery = Barang::with(['kategori.gudang', 'stokGudang'])
            ->where(function ($q) use ($query) {
                $q->where('nama', 'like', "%{$query}%")
                    ->orWhere('kode', 'like', "%{$query}%");
            });

        // Filter by gudang jika ada
        if ($gudangId) {
            $barangQuery->whereHas('kategori', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });
        }

        $barang = $barangQuery->limit(10)->get();

        $results = $barang->map(function ($item) use ($gudangId) {
            // Ambil stok dari stok_gudang
            $stok = $this->getStokBarangGudang($item->id, $gudangId);

            // Tentukan status stok
            $stockStatus = 'available';
            if ($stok == 0) {
                $stockStatus = 'empty';
            } elseif ($stok <= 10) {
                $stockStatus = 'low';
            }

            return [
                'id' => $item->id,
                'nama' => $item->nama,
                'kode' => $item->kode,
                'stok' => $stok,
                'kategori' => $item->kategori->nama ?? '-',
                'gudang' => $item->kategori->gudang->nama ?? '-',
                'stock_status' => $stockStatus
            ];
        });

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
    private function getStokBarangGudang($barangId, $gudangId)
    {
        if (!$gudangId) {
            return 0;
        }

        $stok = StokGudang::where('barang_id', $barangId)
            ->where('gudang_id', $gudangId)
            ->first();

        return $stok ? $stok->stok : 0;
    }

    /**
     * Filter dan ambil barang dengan eager loading stok
     */
    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with(['kategori.gudang', 'stokGudang']);

        // Filter berdasarkan gudang
        if ($gudangId) {
            $query->whereHas('kategori', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });

            // Exclude barang dengan stok 0 di gudang tertentu
            $query->whereHas('stokGudang', function ($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId)
                  ->where('stok', '>', 0);
            }, '>', 0);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', "%{$request->kode}%");
        }

        // Filter stok berdasarkan StokGudang
        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            $query->whereHas('stokGudang', function ($q) use ($request, $gudangId) {
                if ($gudangId) {
                    $q->where('gudang_id', $gudangId);
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
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('satuan')) {
            $query->where('satuan', $request->satuan);
        }

        if ($request->filled('harga_min') && $request->filled('harga_max')) {
            $query->whereBetween('harga', [
                (float) $request->harga_min,
                (float) $request->harga_max,
            ]);
        } elseif ($request->filled('harga_min')) {
            $query->where('harga', '>=', (float) $request->harga_min);
        } elseif ($request->filled('harga_max')) {
            $query->where('harga', '<=', (float) $request->harga_max);
        }

        if ($request->filled('nomor_awal')) {
            $query->where('id', '>=', (int) $request->nomor_awal);
        }
        if ($request->filled('nomor_akhir')) {
            $query->where('id', '<=', (int) $request->nomor_akhir);
        }

        return $query->get();
    }
}