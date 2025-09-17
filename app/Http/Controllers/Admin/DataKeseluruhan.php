<?php

namespace App\Http\Controllers\Admin;

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
        $menu   = MenuHelper::adminMenu();
        $search = $request->input('search');

        // Ambil kategori + relasi barang + gudang
        $kategori = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%");
                }
            },
            'gudang'
        ])->get();

        $gudang = Gudang::all();

        // Validasi harga min / max
        $request->validate([
            'harga_min' => 'nullable|numeric|min:0',
            'harga_max' => 'nullable|numeric|min:0',
        ]);

        if ($request->filled('harga_min') && $request->filled('harga_max') && $request->harga_min > $request->harga_max) {
            return back()->withErrors([
                'harga_min' => 'Harga minimum tidak boleh lebih besar dari harga maksimum'
            ]);
        }

        // Query barang
        $query = Barang::with('kategori');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        // Filter tambahan
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
        if ($request->filled('nomor_awal')) {
            $query->where('id', '>=', (int) $request->nomor_awal);
        }
        if ($request->filled('nomor_akhir')) {
            $query->where('id', '<=', (int) $request->nomor_akhir);
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

        $barang = $query->get();

        // ✅ View dengan semua data
        return view('staff.admin.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'gudang'
        ));
    }

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama'      => 'required|string|max:255|unique:kategori,nama',
            'gudang_id' => 'required|exists:gudang,id', // validasi tabel gudang
        ]);

        Kategori::create($request->only(['nama', 'gudang_id']));

        // ✅ FIXED: Gunakan route name yang benar
        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function storeBarang(Request $request)
    {
        $request->validate([
            'kode'        => 'required|string|max:255|unique:barang,kode',
            'nama'        => 'required|string|max:255|unique:barang,nama',
            'harga'       => 'nullable|numeric|min:0',
            'satuan'      => 'nullable|string|max:50',
            'kategori_id' => 'required|exists:kategori,id',
        ]);

        Barang::create([
            'kode'            => $request->kode,
            'nama'            => $request->nama,
            'harga'           => $request->harga ?? 0,
            'stok'            => 0, // default
            'satuan'          => $request->satuan,
            'kategori_id'     => $request->kategori_id,
            'jenis_barang_id' => 1, // default
        ]);

        // ✅ FIXED: Gunakan route name yang benar
        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Barang berhasil ditambahkan!');
    }

    public function updateBarang(Request $request, $kode)
    {
        $barang = Barang::where('kode', $kode)->firstOrFail();

        $request->validate([
            'nama'        => 'required|string|max:255|unique:barang,nama,' . $barang->id,
            'harga'       => 'nullable|numeric|min:0',
            'satuan'      => 'nullable|string|max:50',
            'kategori_id' => 'required|exists:kategori,id',
        ]);

        $barang->update($request->only(['nama', 'harga', 'stok', 'satuan', 'kategori_id']));

        // ✅ FIXED: Gunakan route name yang benar
        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Barang berhasil diperbarui!');
    }

    public function destroyBarang($kode)
    {
        $barang = Barang::where('kode', $kode)->firstOrFail();
        $barang->delete();

        // ✅ FIXED: Gunakan route name yang benar
        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Barang berhasil dihapus!');
    }

    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        // Hapus semua barang dalam kategori
        $kategori->barang()->delete();
        $kategori->delete();

        // ✅ FIXED: Gunakan route name yang benar
        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Kategori berhasil dihapus!');
    }

    /**
     * API untuk search suggestions
     */
    public function searchSuggestions(Request $request)
    {
        $search = $request->get('q', '');
        
        // Debug log
        \Log::info('Search API called', ['query' => $search]);
        
        if (strlen($search) < 2) {
            return response()->json([]);
        }

        try {
            $suggestions = Barang::with('kategori')
                ->where(function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                          ->orWhere('kode', 'like', "%{$search}%");
                })
                ->select('id', 'nama', 'kode', 'stok', 'kategori_id')
                ->limit(8)
                ->get()
                ->map(function ($barang) {
                    return [
                        'id' => $barang->id,
                        'nama' => $barang->nama,
                        'kode' => $barang->kode,
                        'stok' => $barang->stok,
                        'kategori' => $barang->kategori->nama ?? '-',
                        'display' => $barang->nama . ' (' . $barang->kode . ')',
                        'stock_status' => $barang->stok == 0 ? 'empty' : ($barang->stok < 10 ? 'low' : 'normal')
                    ];
                });

            \Log::info('Search results', ['count' => $suggestions->count(), 'results' => $suggestions]);

            return response()->json($suggestions);
            
        } catch (\Exception $e) {
            \Log::error('Search API error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Search failed'], 500);
        }
    }
}