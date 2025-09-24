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
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%");
                }
            },
            'gudang'
        ]);

        // Filter kategori berdasarkan gudang yang dipilih
        if ($request->filled('gudang_id')) {
            $kategoriQuery->where('gudang_id', $request->gudang_id);
        }

        $kategori = $kategoriQuery->get();
        $gudang = Gudang::all();

        // Tentukan gudang yang sedang dipilih (jika ada)
        $selectedGudang = null;
        if ($request->filled('gudang_id')) {
            $selectedGudang = Gudang::find($request->gudang_id);
        }

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
$query = Barang::with('kategori.gudang'); // Sertakan relasi gudang

if ($search) {
    $query->where(function ($q) use ($search) {
        $q->where('nama', 'like', "%{$search}%")
          ->orWhere('kode', 'like', "%{$search}%");
    });
}

// ✅ FILTER berdasarkan gudang yang dipilih
if ($request->filled('gudang_id')) {
    $query->whereHas('kategori', function($q) use ($request) {
        $q->where('gudang_id', $request->gudang_id);
    });
}


        // Filter tambahan
        if ($request->filled('gudang_id')) {
            $query->whereHas('kategori', function($q) use ($request) {
                $q->where('gudang_id', $request->gudang_id);
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

        return view('staff.admin.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'gudang',
            'selectedGudang'
        ));
    }

    /**
     * Menampilkan data gudang spesifik berdasarkan slug
     */
    public function gudang($slug, Request $request)
    {
        $menu = MenuHelper::adminMenu();

        // Konversi slug ke nama gudang menggunakan helper
        $gudangName = MenuHelper::slugToGudangName($slug);
        
        // Cari gudang berdasarkan nama (case insensitive)
        $selectedGudang = Gudang::whereRaw('LOWER(nama) LIKE ?', ['%' . strtolower($gudangName) . '%'])->first();
        
        if (!$selectedGudang) {
            abort(404, "Gudang dengan slug '{$slug}' tidak ditemukan");
        }
        
        // Ambil semua gudang untuk dropdown/filter
        $gudang = Gudang::all();
        
        // Ambil kategori dari gudang yang dipilih
        $kategori = Kategori::with(['barang', 'gudang'])
                      ->where('gudang_id', $selectedGudang->id)
                      ->get();
        
        // Inisialisasi collection barang kosong
        $barang = collect();
        
        // Jika ada filter/search, ambil data barang yang sesuai
        if ($this->hasAnyFilter($request)) {
            $barang = $this->getFilteredBarang($request, $selectedGudang->id);
        }
        
        // Return view yang sama dengan index
        return view('staff.admin.datakeseluruhan', compact(
            'kategori', 
            'barang', 
            'menu',
            'gudang', 
            'selectedGudang'
        ));
    }

    public function show($id)
    {
        $menu = \App\Helpers\MenuHelper::adminMenu();

        // Ambil gudang beserta kategori & barangnya
        $gudangTerpilih = Gudang::with('kategori.barang')->findOrFail($id);

        $gudang = Gudang::all(); // supaya tetap muncul di modal tambah kategori
        $kategori = $gudangTerpilih->kategori; // kategori hanya dari gudang ini
        $barang = $kategori->flatMap->barang; // barang dari semua kategori di gudang ini

        return view('staff.admin.datakeseluruhan', compact(
            'menu',
            'gudang',
            'kategori',
            'barang',
            'gudangTerpilih'
        ));
    }

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama'      => 'required|string|max:255|unique:kategori,nama',
            'gudang_id' => 'required|exists:gudang,id',
        ]);

        Kategori::create($request->only(['nama', 'gudang_id']));

        return redirect()->route('admin.datakeseluruhan.index')
                         ->with('toast', [
        'type' => 'success',
        'title' => 'Berhasil!',
        'message' => 'Kategori berhasil ditambahkan.'
    ]);
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

        return redirect()->route('admin.datakeseluruhan.index')
                        ->with('toast', [
        'type' => 'success',
        'title' => 'Berhasil!',
        'message' => 'Barang berhasil ditambahkan.'
    ]);
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

        return redirect()->route('admin.datakeseluruhan.index')
                         ->with('toast', [
        'type' => 'success',
        'title' => 'Update Sukses!',
        'message' => 'Barang berhasil diperbarui.'
    ]);
    }

    public function destroyBarang($id)
    {
        $barang = Barang::where('id', $id)->firstOrFail();
        $barang->delete();

        return redirect()->route('admin.datakeseluruhan.index')
                        ->with('toast', [
        'type' => 'success',
        'title' => 'Berhasil!',
        'message' => "Barang {$barang->nama} berhasil dihapus."
    ]);
    }

    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        // Hapus semua barang dalam kategori
        $kategori->barang()->delete();
        $kategori->delete();

        return redirect()->route('admin.datakeseluruhan.index')
                         ->with('toast', [
        'type' => 'success',
        'title' => 'Berhasil!',
        'message' => 'Kategori Berhasil Dihapus.'
    ]);
    }

    /**
     * API untuk search suggestions dengan filter gudang yang BENAR
     */
    public function searchSuggestions(Request $request)
    {
        $search = $request->get('q', '');
        $gudangId = $request->get('gudang_id'); // ambil gudang_id dari request

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        try {
            $suggestions = Barang::with(['kategori.gudang'])
                ->where(function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%")
                          ->orWhere('kode', 'like', "%{$search}%");
                })
                // ✅ PERBAIKAN UTAMA: Filter berdasarkan gudang jika ada
                ->when($gudangId, function ($query, $gudangId) {
                    $query->whereHas('kategori', function($q) use ($gudangId) {
                        $q->where('gudang_id', $gudangId);
                    });
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
                        'gudang' => $barang->kategori->gudang->nama ?? '-', 
                        'display' => $barang->nama . ' (' . $barang->kode . ')',
                        'stock_status' => $barang->stok == 0 ? 'empty' : ($barang->stok < 10 ? 'low' : 'normal')
                    ];
                });

            \Log::info('Search suggestions', [
                'query' => $search,
                'gudang_id' => $gudangId,
                'results' => $suggestions->count(),
                'first_result_gudang' => $suggestions->first()['gudang'] ?? null
            ]);

            return response()->json($suggestions);
            
        } catch (\Exception $e) {
            \Log::error('Search API error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Search failed'], 500);
        }
    }

    /**
     * Cek apakah ada filter yang aktif
     */
    private function hasAnyFilter(Request $request)
    {
        $filterFields = [
            'search', 'kode', 'stok_min', 'stok_max', 
            'kategori_id', 'gudang_id', 'satuan', 
            'nomor_awal', 'nomor_akhir', 'harga_min', 'harga_max'
        ];
        
        foreach ($filterFields as $field) {
            if ($request->filled($field)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Ambil data barang berdasarkan filter
     */
    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with(['kategori.gudang']);
        
        // ✅ PERBAIKAN: Filter berdasarkan gudang jika disediakan
        if ($gudangId) {
            $query->whereHas('kategori', function($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });
        }
        
        // Filter search (nama atau kode)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }
        
        // Filter kode spesifik
        if ($request->filled('kode')) {
            $query->where('kode', 'like', "%{$request->kode}%");
        }
        
        // Filter stok minimum
        if ($request->filled('stok_min')) {
            $query->where('stok', '>=', (int) $request->stok_min);
        }
        
        // Filter stok maksimum
        if ($request->filled('stok_max')) {
            $query->where('stok', '<=', (int) $request->stok_max);
        }
        
        // Filter kategori
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        // Filter satuan
        if ($request->filled('satuan')) {
            $query->where('satuan', $request->satuan);
        }
        
        // Filter harga
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
        
        // Filter range ID/nomor
        if ($request->filled('nomor_awal')) {
            $query->where('id', '>=', (int) $request->nomor_awal);
        }
        
        if ($request->filled('nomor_akhir')) {
            $query->where('id', '<=', (int) $request->nomor_akhir);
        }
        
        return $query->get();
    }
}