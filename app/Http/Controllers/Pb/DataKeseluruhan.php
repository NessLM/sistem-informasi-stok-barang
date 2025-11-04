<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Bagian;
use App\Models\PbStok;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\DB;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::pbMenu();

        // Ambil semua kategori
        $kategori = Kategori::all();

        // Ambil semua bagian untuk filter
        $bagian = Bagian::all();

        // Jika ada filter/search, tampilkan hasil pencarian
        $pbStokData = null;
        if ($this->hasFilter($request)) {
            $pbStokData = $this->getFilteredPbStok($request);
        }

        return view('staff.pb.datakeseluruhan', compact(
            'kategori',
            'bagian',
            'pbStokData',
            'menu'
        ));
    }

    /**
     * API: Search suggestions untuk autocomplete
     * IMPROVED: Sekarang bisa search berdasarkan nama bagian juga!
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $queryLower = strtolower($query);

        // Query pb_stok dengan relasi barang, kategori, dan bagian
        $pbStok = PbStok::with(['barang.kategori', 'bagian'])
            ->where(function ($q) use ($query, $queryLower) {
                // Search di nama barang atau kode barang
                $q->whereHas('barang', function ($subQ) use ($query) {
                    $subQ->where('nama_barang', 'like', "%{$query}%")
                        ->orWhere('kode_barang', 'like', "%{$query}%");
                })
                    // ATAU search di nama bagian
                    ->orWhereHas('bagian', function ($subQ) use ($query) {
                    $subQ->where('nama', 'like', "%{$query}%");
                });
            })
            ->limit(15) // Naikkan limit karena bisa lebih banyak hasil
            ->get();

        $results = $pbStok->map(function ($item) use ($queryLower) {
            // Tentukan status stok
            $stockStatus = 'available';
            if ($item->stok == 0) {
                $stockStatus = 'empty';
            } elseif ($item->stok <= 10) {
                $stockStatus = 'low';
            }

            // Highlight match type
            $matchType = 'barang'; // default
            $namaBagian = $item->bagian->nama ?? '-';

            if (stripos($namaBagian, $queryLower) !== false) {
                $matchType = 'bagian';
            }

            return [
                'id' => $item->id,
                'nama' => $item->barang->nama_barang ?? '-',
                'kode' => $item->kode_barang,
                'stok' => $item->stok,
                'harga' => 'Rp ' . number_format($item->harga ?? 0, 0, ',', '.'),
                'kategori' => $item->barang->kategori->nama ?? '-',
                'bagian' => $namaBagian,
                'stock_status' => $stockStatus,
                'match_type' => $matchType // untuk styling
            ];
        })->values();

        return response()->json($results);
    }

    /**
     * Cek apakah ada filter yang aktif
     */
    private function hasFilter(Request $request)
    {
        return $request->filled('search') ||
            $request->filled('kode') ||
            $request->filled('stok_min') ||
            $request->filled('stok_max') ||
            $request->filled('kategori_id') ||
            $request->filled('bagian_id') ||
            $request->filled('harga_min') ||
            $request->filled('harga_max');
    }

    /**
     * Filter dan ambil data pb_stok dengan eager loading
     */
    private function getFilteredPbStok(Request $request)
    {
        // Validasi input harga
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
            return collect(); // Return empty collection
        }

        // Query pb_stok dengan relasi
        $query = PbStok::with(['barang.kategori', 'bagian']);

        // Filter berdasarkan search (nama, kode barang, atau nama bagian)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // Search di barang
                $q->whereHas('barang', function ($subQ) use ($search) {
                    $subQ->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                })
                    // ATAU search di bagian
                    ->orWhereHas('bagian', function ($subQ) use ($search) {
                        $subQ->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        // Filter berdasarkan kode barang
        if ($request->filled('kode')) {
            $query->where('kode_barang', 'like', "%{$request->kode}%");
        }

        // Filter berdasarkan stok
        if ($request->filled('stok_min')) {
            $query->where('stok', '>=', (int) $request->stok_min);
        }
        if ($request->filled('stok_max')) {
            $query->where('stok', '<=', (int) $request->stok_max);
        }

        // Filter berdasarkan kategori
        if ($request->filled('kategori_id')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('id_kategori', $request->kategori_id);
            });
        }

        // Filter berdasarkan bagian
        if ($request->filled('bagian_id')) {
            $query->where('bagian_id', $request->bagian_id);
        }

        // Filter berdasarkan harga
        if ($request->filled('harga_min')) {
            $query->where('harga', '>=', (float) $request->harga_min);
        }
        if ($request->filled('harga_max')) {
            $query->where('harga', '<=', (float) $request->harga_max);
        }

        return $query->get();
    }
}