<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu   = MenuHelper::pbMenu();
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

        // Filter kategori hanya dari Gudang Utama
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%");
                }
            },
            'gudang'
        ])->where('gudang_id', $gudangUtama->id);

        $kategori = $kategoriQuery->get();
        
        // Ambil semua gudang untuk dropdown distribusi
        $gudang = Gudang::all();
        
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

    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with(['kategori.gudang']);
        
        // Paksa filter ke gudang tertentu (Gudang Utama)
        if ($gudangId) {
            $query->whereHas('kategori', function($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', "%{$request->kode}%");
        }

        if ($request->filled('stok_min')) {
            $query->where('stok', '>=', (int) $request->stok_min);
        }
        if ($request->filled('stok_max')) {
            $query->where('stok', '<=', (int) $request->stok_max);
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