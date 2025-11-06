<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Bagian;
use App\Models\PbStok;
use App\Models\StokBagian;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\DB;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::pbMenu();
        
        // Ambil tab aktif
        $activeTab = $request->get('tab', 'data-keseluruhan');

        // Ambil semua kategori
        $kategori = Kategori::all();

        // Ambil semua bagian untuk filter
        $bagian = Bagian::all();

        // Data untuk tab Data Keseluruhan
        $barangData = null;
        if ($activeTab === 'data-keseluruhan') {
            if ($this->hasFilter($request)) {
                $barangData = $this->getFilteredBarang($request);
            }
        }

        return view('staff.pb.datakeseluruhan', compact(
            'kategori',
            'bagian',
            'barangData',
            'activeTab',
            'menu'
        ));
    }

    /**
     * API: Search suggestions untuk autocomplete
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        $tab = $request->get('tab', 'data-keseluruhan');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $queryLower = strtolower($query);

        if ($tab === 'data-keseluruhan') {
            // Search dari tabel Barang
            $barang = Barang::with(['kategori', 'pbStok.bagian'])
                ->where(function ($q) use ($query) {
                    $q->where('nama_barang', 'like', "%{$query}%")
                      ->orWhere('kode_barang', 'like', "%{$query}%");
                })
                ->orWhereHas('kategori', function ($q) use ($query) {
                    $q->where('nama', 'like', "%{$query}%");
                })
                ->limit(15)
                ->get();

            $results = $barang->map(function ($item) {
                $totalStok = $item->pbStok->sum('stok');
                
                $stockStatus = 'available';
                if ($totalStok == 0) {
                    $stockStatus = 'empty';
                } elseif ($totalStok <= 10) {
                    $stockStatus = 'low';
                }

                return [
                    'id' => $item->kode_barang,
                    'nama' => $item->nama_barang,
                    'kode' => $item->kode_barang,
                    'stok' => $totalStok,
                    'satuan' => $item->satuan,
                    'harga' => 'Rp ' . number_format($item->harga_barang ?? 0, 0, ',', '.'),
                    'kategori' => $item->kategori->nama ?? '-',
                    'bagian' => 'Semua Bagian',
                    'stock_status' => $stockStatus,
                ];
            })->values();

        } else {
            // Search dari PbStok untuk tab distribusi
            $pbStok = PbStok::with(['barang.kategori', 'bagian'])
                ->where(function ($q) use ($query) {
                    $q->whereHas('barang', function ($subQ) use ($query) {
                        $subQ->where('nama_barang', 'like', "%{$query}%")
                            ->orWhere('kode_barang', 'like', "%{$query}%");
                    })
                    ->orWhereHas('bagian', function ($subQ) use ($query) {
                        $subQ->where('nama', 'like', "%{$query}%");
                    });
                })
                ->limit(15)
                ->get();

            $results = $pbStok->map(function ($item) {
                $stockStatus = 'available';
                if ($item->stok == 0) {
                    $stockStatus = 'empty';
                } elseif ($item->stok <= 10) {
                    $stockStatus = 'low';
                }

                return [
                    'id' => $item->id,
                    'nama' => $item->barang->nama_barang ?? '-',
                    'kode' => $item->kode_barang,
                    'stok' => $item->stok,
                    'harga' => 'Rp ' . number_format($item->harga ?? 0, 0, ',', '.'),
                    'kategori' => $item->barang->kategori->nama ?? '-',
                    'bagian' => $item->bagian->nama ?? '-',
                    'stock_status' => $stockStatus,
                ];
            })->values();
        }

        return response()->json($results);
    }

    /**
     * Cek apakah ada filter yang aktif
     */
    private function hasFilter(Request $request)
    {
        return $request->filled('search') ||
            $request->filled('stok_min') ||
            $request->filled('stok_max') ||
            $request->filled('kategori_id') ||
            $request->filled('satuan') ||
            $request->filled('harga_min') ||
            $request->filled('harga_max');
    }

    /**
     * Filter dan ambil data BARANG untuk tab Data Keseluruhan
     */
    private function getFilteredBarang(Request $request)
    {
        $request->validate([
            'harga_min' => 'nullable|numeric|min:0',
            'harga_max' => 'nullable|numeric|min:0',
            'stok_min' => 'nullable|integer|min:0',
            'stok_max' => 'nullable|integer|min:0',
        ]);

        if (
            $request->filled('harga_min') &&
            $request->filled('harga_max') &&
            $request->harga_min > $request->harga_max
        ) {
            return collect();
        }

        $query = Barang::with(['kategori', 'pbStok.bagian']);

        // Filter berdasarkan search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhereHas('kategori', function ($subQ) use ($search) {
                      $subQ->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        // Filter berdasarkan kategori
        if ($request->filled('kategori_id')) {
            $query->where('id_kategori', $request->kategori_id);
        }

        // Filter berdasarkan satuan
        if ($request->filled('satuan')) {
            $query->where('satuan', $request->satuan);
        }

        // Filter berdasarkan harga
        if ($request->filled('harga_min')) {
            $query->where('harga_barang', '>=', (float) $request->harga_min);
        }
        if ($request->filled('harga_max')) {
            $query->where('harga_barang', '<=', (float) $request->harga_max);
        }

        // Filter berdasarkan stok total dari pb_stok
        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            $stokMin = $request->filled('stok_min') ? (int) $request->stok_min : 0;
            $stokMax = $request->filled('stok_max') ? (int) $request->stok_max : PHP_INT_MAX;
            
            $query->whereHas('pbStok', function ($q) use ($stokMin, $stokMax) {
                // Filter berdasarkan total stok
            })->get()->filter(function ($barang) use ($stokMin, $stokMax) {
                $totalStok = $barang->pbStok->sum('stok');
                return $totalStok >= $stokMin && $totalStok <= $stokMax;
            });
        }

        return $query->get();
    }
}