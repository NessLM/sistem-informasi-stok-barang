<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PbStok;
use App\Models\PjStok;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\DB; // TAMBAHKAN INI

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
                    $q->where('nama_barang', 'like', "%{$search}%")
                      ->orWhere('kode_barang', 'like', "%{$search}%");
                }
                $q->with(['pbStok', 'pjStok']); // Eager load stok
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

        // Query barang dengan stok
        $query = Barang::with(['kategori.gudang', 'pbStok', 'pjStok']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan gudang yang dipilih
        if ($request->filled('gudang_id')) {
            $query->whereHas('kategori', function($q) use ($request) {
                $q->where('gudang_id', $request->gudang_id);
            });
        }

        // Filter tambahan
        if ($request->filled('kode')) {
            $query->where('kode_barang', 'like', "%{$request->kode}%");
        }
        
        // Filter stok berdasarkan PJ Stok
        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            $query->whereHas('pjStok', function ($q) use ($request) {
                if ($request->filled('gudang_id')) {
                    $q->where('id_gudang', $request->gudang_id);
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
        
        // Ambil kategori dari gudang yang dipilih dengan stok
        $kategori = Kategori::with(['barang.pbStok', 'barang.pjStok', 'gudang'])
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

    /**
     * Alias untuk method gudang() - untuk konsistensi dengan route
     */
    public function byGudang($slug, Request $request)
    {
        return $this->gudang($slug, $request);
    }

    public function show($id)
    {
        $menu = MenuHelper::adminMenu();

        // Ambil gudang beserta kategori & barangnya
        $gudangTerpilih = Gudang::with('kategori.barang.pbStok', 'kategori.barang.pjStok')->findOrFail($id);

        $gudang = Gudang::all();
        $kategori = $gudangTerpilih->kategori;
        $barang = $kategori->flatMap->barang;

        return view('staff.admin.datakeseluruhan', compact(
            'menu',
            'gudang',
            'kategori',
            'barang',
            'gudangTerpilih'
        ));
    }

    /**
     * Simpan kategori baru dengan auto-sync ke Gudang Utama
     */
    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama'      => 'required|string|max:255',
            'gudang_id' => 'required|exists:gudang,id',
        ]);

        DB::beginTransaction();
        try {
            // Cari Gudang Utama (case insensitive)
            $gudangUtama = Gudang::whereRaw('LOWER(nama) LIKE ?', ['%utama%'])->first();

            $gudangTerpilih = Gudang::find($request->gudang_id);
            
            // Cek apakah kategori sudah ada di gudang yang dipilih
            $existingKategori = Kategori::where('nama', $request->nama)
                                        ->where('gudang_id', $request->gudang_id)
                                        ->first();
            
            if ($existingKategori) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Kategori dengan nama ini sudah ada di gudang yang dipilih.'
                ]);
            }
            
            // Buat kategori di gudang yang dipilih
            $kategori = Kategori::create([
                'nama' => $request->nama,
                'gudang_id' => $request->gudang_id
            ]);

            // Jika gudang yang dipilih BUKAN Gudang Utama, buat kategori duplikat di Gudang Utama
            if ($gudangUtama && $gudangTerpilih->id != $gudangUtama->id) {
                // Cek apakah kategori sudah ada di Gudang Utama
                $existingKategoriUtama = Kategori::where('nama', $request->nama)
                                                  ->where('gudang_id', $gudangUtama->id)
                                                  ->first();
                
                if (!$existingKategoriUtama) {
                    Kategori::create([
                        'nama' => $request->nama,
                        'gudang_id' => $gudangUtama->id
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('admin.datakeseluruhan.index')
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => 'Kategori berhasil ditambahkan' . 
                        ($gudangUtama && $gudangTerpilih->id != $gudangUtama->id 
                            ? ' dan otomatis disinkronkan ke Gudang Utama.' 
                            : '.')
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Gagal menambahkan kategori: ' . $e->getMessage()
            ]);
        }
    }

    public function storeBarang(Request $request)
    {
        $request->validate([
            'kode_barang'  => 'required|string|max:255|unique:barang,kode_barang',
            'nama_barang'  => 'required|string|max:255',
            'harga_barang' => 'nullable|numeric|min:0',
            'satuan'       => 'nullable|string|max:50',
            'id_kategori'  => 'required|exists:kategori,id',
        ]);

        // Buat barang baru
        $barang = Barang::create([
            'kode_barang'  => $request->kode_barang,
            'nama_barang'  => $request->nama_barang,
            'harga_barang' => $request->harga_barang ?? 0,
            'satuan'       => $request->satuan,
            'id_kategori'  => $request->id_kategori,
        ]);

        // Buat stok PB default (stok pusat)
        PbStok::create([
            'kode_barang' => $barang->kode_barang,
            'stok' => 0
        ]);

        // Buat stok PJ untuk gudang kategori
        $kategori = Kategori::find($request->id_kategori);
        if ($kategori && $kategori->gudang_id) {
            PjStok::create([
                'kode_barang' => $barang->kode_barang,
                'id_gudang' => $kategori->gudang_id,
                'id_kategori' => $request->id_kategori,
                'stok' => 0
            ]);
        }

        return redirect()->route('admin.datakeseluruhan.index')
                        ->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Barang berhasil ditambahkan.'
        ]);
    }

    public function updateBarang(Request $request, $kode)
    {
        // Cari barang berdasarkan primary key (kode_barang)
        $barang = Barang::where('kode_barang', $kode)->firstOrFail();

        $request->validate([
            'kode_barang'  => 'required|string|max:255|unique:barang,kode_barang,' . $barang->kode_barang . ',kode_barang',
            'nama_barang'  => 'required|string|max:255',
            'harga_barang' => 'nullable|numeric|min:0',
            'satuan'       => 'nullable|string|max:50',
            'id_kategori'  => 'required|exists:kategori,id',
        ], [
            'kode_barang.unique' => 'Kode barang sudah digunakan!',
            'kode_barang.required' => 'Kode barang harus diisi!',
        ]);

        // Simpan kategori dan kode lama untuk pengecekan
        $kategoriLama = $barang->id_kategori;
        $kodeLama = $barang->kode_barang;
        $kodeBaruDibuat = false;

        DB::beginTransaction();
        try {
            // Jika kode barang berubah, perlu handle relasi
            if ($kodeLama !== $request->kode_barang) {
                // Buat barang baru dengan kode baru
                $barangBaru = Barang::create([
                    'kode_barang'  => $request->kode_barang,
                    'nama_barang'  => $request->nama_barang,
                    'harga_barang' => $request->harga_barang ?? 0,
                    'satuan'       => $request->satuan,
                    'id_kategori'  => $request->id_kategori,
                ]);
                $kodeBaruDibuat = true;

                // Transfer PB Stok
                $pbStokLama = PbStok::where('kode_barang', $kodeLama)->first();
                if ($pbStokLama) {
                    PbStok::create([
                        'kode_barang' => $request->kode_barang,
                        'stok' => $pbStokLama->stok
                    ]);
                }

                // Transfer PJ Stok
                $pjStokLama = PjStok::where('kode_barang', $kodeLama)->get();
                foreach ($pjStokLama as $pj) {
                    PjStok::create([
                        'kode_barang' => $request->kode_barang,
                        'id_gudang' => $pj->id_gudang,
                        'id_kategori' => $request->id_kategori,
                        'stok' => $pj->stok
                    ]);
                }

                // PERBAIKAN: Periksa apakah class ada sebelum update
                // Jika class tidak ada, skip tahap ini
                try {
                    if (class_exists('App\Models\TransaksiBarangMasuk')) {
                        \App\Models\TransaksiBarangMasuk::where('kode_barang', $kodeLama)
                            ->update(['kode_barang' => $request->kode_barang]);
                    }
                    
                    if (class_exists('App\Models\TransaksiDistribusi')) {
                        \App\Models\TransaksiDistribusi::where('kode_barang', $kodeLama)
                            ->update(['kode_barang' => $request->kode_barang]);
                    }
                    
                    if (class_exists('App\Models\TransaksiBarangKeluar')) {
                        \App\Models\TransaksiBarangKeluar::where('kode_barang', $kodeLama)
                            ->update(['kode_barang' => $request->kode_barang]);
                    }
                } catch (\Exception $e) {
                    // Log jika ada error saat update transaksi
                    \Log::warning('Error updating transaksi: ' . $e->getMessage());
                }

                // Hapus data lama
                $pbStokLama?->delete();
                PjStok::where('kode_barang', $kodeLama)->delete();
                $barang->delete();

                $barang = $barangBaru;
            } else {
                // Jika kode tidak berubah, update biasa
                $barang->update([
                    'nama_barang'  => $request->nama_barang,
                    'harga_barang' => $request->harga_barang ?? 0,
                    'satuan'       => $request->satuan,
                    'id_kategori'  => $request->id_kategori,
                ]);
            }

            // Update PJ Stok jika kategori berubah
            if ($kategoriLama != $request->id_kategori) {
                $gudangLama = Kategori::find($kategoriLama)->gudang_id ?? null;
                $gudangBaru = Kategori::find($request->id_kategori)->gudang_id ?? null;
                
                if ($gudangLama && $gudangBaru && $gudangLama != $gudangBaru) {
                    // Update gudang di semua PJ Stok yang terkait
                    PjStok::where('kode_barang', $barang->kode_barang)
                          ->where('id_gudang', $gudangLama)
                          ->update([
                              'id_gudang' => $gudangBaru,
                              'id_kategori' => $request->id_kategori
                          ]);
                } else {
                    // Hanya update id_kategori jika gudang sama
                    PjStok::where('kode_barang', $barang->kode_barang)
                          ->update(['id_kategori' => $request->id_kategori]);
                }
            }

            DB::commit();

            return redirect()->route('admin.datakeseluruhan.index')
                             ->with('toast', [
                'type' => 'success',
                'title' => 'Update Sukses!',
                'message' => $kodeBaruDibuat 
                    ? 'Barang berhasil diperbarui dengan kode baru.' 
                    : 'Barang berhasil diperbarui.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Gagal memperbarui barang: ' . $e->getMessage()
            ])->withInput();
        }
    }

    public function destroyBarang($id)
    {
        // Support both kode_barang and id for backwards compatibility
        $barang = Barang::where('id', $id)
                       ->orWhere('kode_barang', $id)
                       ->firstOrFail();
        
        // Hapus stok terkait
        $barang->pbStok()->delete();
        $barang->pjStok()->delete();
        
        $namaBarang = $barang->nama_barang;
        $barang->delete();

        return redirect()->route('admin.datakeseluruhan.index')
                        ->with('toast', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => "Barang {$namaBarang} berhasil dihapus."
        ]);
    }

    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        // Hapus stok untuk semua barang dalam kategori
        foreach ($kategori->barang as $barang) {
            $barang->pbStok()->delete();
            $barang->pjStok()->delete();
        }

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
     * API untuk search suggestions dengan filter gudang
     */
    public function searchSuggestions(Request $request)
    {
        $search = $request->get('q', '');
        $gudangId = $request->get('gudang_id');

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        try {
            $barangQuery = Barang::with(['kategori.gudang', 'pbStok', 'pjStok'])
                ->where(function ($query) use ($search) {
                    $query->where('nama_barang', 'like', "%{$search}%")
                          ->orWhere('kode_barang', 'like', "%{$search}%");
                });

            // Filter berdasarkan gudang jika ada
            if ($gudangId) {
                $barangQuery->whereHas('kategori', function($q) use ($gudangId) {
                    $q->where('gudang_id', $gudangId);
                });
            }

            $suggestions = $barangQuery->select('kode_barang', 'nama_barang', 'id_kategori')
                ->limit(8)
                ->get()
                ->map(function ($barang) use ($gudangId) {
                    // Ambil stok dari gudang yang dipilih
                    $stok = 0;
                    if ($gudangId) {
                        $pjStok = $barang->pjStok()
                            ->where('id_gudang', $gudangId)
                            ->first();
                        $stok = $pjStok ? $pjStok->stok : 0;
                    } else {
                        // Total stok dari semua gudang PJ
                        $stok = $barang->pjStok()->sum('stok');
                    }

                    return [
                        'kode' => $barang->kode_barang,
                        'nama' => $barang->nama_barang,
                        'stok' => $stok,
                        'kategori' => $barang->kategori->nama ?? '-',
                        'gudang' => $barang->kategori->gudang->nama ?? '-', 
                        'display' => $barang->nama_barang . ' (' . $barang->kode_barang . ')',
                        'stock_status' => $stok == 0 ? 'empty' : ($stok < 10 ? 'low' : 'normal')
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
            'harga_min', 'harga_max'
        ];
        
        foreach ($filterFields as $field) {
            if ($request->filled($field)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Ambil data barang berdasarkan filter dengan PJ Stok
     */
    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with(['kategori.gudang', 'pbStok', 'pjStok']);
        
        // Filter berdasarkan gudang jika disediakan
        if ($gudangId) {
            $query->whereHas('kategori', function($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });
        }
        
        // Filter search (nama atau kode)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });
        }
        
        if ($request->filled('kode')) {
            $query->where('kode_barang', 'like', "%{$request->kode}%");
        }
        
        // Filter stok berdasarkan PJ Stok
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