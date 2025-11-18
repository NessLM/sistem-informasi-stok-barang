<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Bagian;
use App\Models\PbStok;
use App\Models\StokBagian;
use App\Models\TransaksiBarangMasuk;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::pbMenu();
        $activeTab = $request->get('tab', 'data-keseluruhan');
        $kategori = Kategori::all();
        $bagian = Bagian::all();

        // Hasil search / filter
        $barangData = collect(); // Data Barang Gudang Utama (PbStok)
        $stokBagianData = collect(); // Stok Per Bagian (StokBagian)
        $barangMasukData = collect(); // Kelola Barang Masuk (Barang)

        if ($activeTab === 'data-keseluruhan') {
            // TAB 1 → search + filter untuk Distribusi & Stok Per Bagian
            if ($this->hasFilter($request)) {
                $barangData = $this->getFilteredPbStok($request);
                $stokBagianData = $this->getFilteredStokBagian($request);
            }
        } elseif ($activeTab === 'distribusi') {
            // TAB 2 → hanya search text untuk Kelola Barang Masuk
            if ($request->filled('search')) {
                $barangMasukData = $this->getSearchBarangMasuk($request);
            }
        }

        return view('staff.pb.datakeseluruhan', compact(
            'kategori',
            'bagian',
            'barangData',
            'stokBagianData',
            'barangMasukData',
            'activeTab',
            'menu'
        ));
    }

    /**
     * ✅ UPDATE/EDIT PB STOK (Harga & Bagian)
     */
    public function updatePbStok(Request $request, $id)
    {
        $request->validate([
            'harga' => 'required|numeric|min:0',
            'bagian_id' => 'required|exists:bagian,id',
        ]);

        DB::beginTransaction();
        try {
            $pbStok = PbStok::findOrFail($id);
            
            // Simpan data lama untuk log
            $oldHarga = $pbStok->harga;
            $oldBagianId = $pbStok->bagian_id;

            // Update harga dan bagian
            $pbStok->update([
                'harga' => $request->harga,
                'bagian_id' => $request->bagian_id,
            ]);

            DB::commit();

            $barang = $pbStok->barang;
            $bagian = $pbStok->bagian;

            return redirect()
                ->route('pb.datakeseluruhan.index', ['tab' => 'data-keseluruhan'])
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => "Data barang {$barang->nama_barang} berhasil diupdate. Harga: Rp " . number_format($request->harga, 0, ',', '.') . ", Bagian: {$bagian->nama}",
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()
                ->back()
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ])
                ->withInput();
        }
    }

    /**
     * ✅ PROSES BARANG MASUK
     */
    public function storeBarangMasuk(Request $request, $kodeBarang)
    {
        $request->validate([
            'bagian_id' => 'required|exists:bagian,id',
            'jumlah' => 'required|integer|min:1',
            'harga' => 'required|numeric|min:0',
            'tanggal' => 'nullable|date',
            'keterangan' => 'nullable|string|max:500',
            'bukti' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Pastikan barang ada
        $barang = Barang::where('kode_barang', $kodeBarang)->firstOrFail();

        DB::beginTransaction();
        try {
            // Upload bukti jika ada
            $buktiPath = null;
            if ($request->hasFile('bukti')) {
                $buktiPath = $request->file('bukti')->store('bukti-barang-masuk', 'public');
            }

            // Simpan transaksi barang masuk
            TransaksiBarangMasuk::create([
                'kode_barang' => $kodeBarang,
                'jumlah' => $request->jumlah,
                'harga' => $request->harga,
                'tanggal' => $request->tanggal ?? now()->toDateString(),
                'user_id' => auth()->id(),
                'keterangan' => $request->keterangan,
                'bukti' => $buktiPath,
            ]);

            // Update / buat pb_stok
            $pbStok = PbStok::firstOrCreate(
                [
                    'kode_barang' => $kodeBarang,
                    'bagian_id' => $request->bagian_id,
                ],
                [
                    'stok' => 0,
                    'harga' => $request->harga,
                ]
            );

            // Tambah stok & update harga
            $pbStok->increment('stok', $request->jumlah);
            $pbStok->update(['harga' => $request->harga]);

            DB::commit();

            return redirect()
                ->route('pb.datakeseluruhan.index', ['tab' => 'distribusi'])
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => "Barang {$barang->nama_barang} berhasil dimasukkan sebanyak {$request->jumlah} {$barang->satuan}",
                ]);
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($buktiPath) && Storage::disk('public')->exists($buktiPath)) {
                Storage::disk('public')->delete($buktiPath);
            }

            return redirect()
                ->back()
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                ])
                ->withInput();
        }
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

        if ($tab === 'data-keseluruhan') {
            // TAB 1 → Data Keseluruhan & Distribusi Barang
            $pbStok = PbStok::with(['barang.kategori', 'bagian'])
                ->where(function ($q) use ($query) {
                    $q->whereHas('barang', function ($sub) use ($query) {
                        $sub->where('nama_barang', 'like', "%{$query}%")
                            ->orWhere('kode_barang', 'like', "%{$query}%");
                    })
                        ->orWhereHas('barang.kategori', function ($sub) use ($query) {
                            $sub->where('nama', 'like', "%{$query}%");
                        })
                        ->orWhereHas('bagian', function ($sub) use ($query) {
                            $sub->where('nama', 'like', "%{$query}%");
                        });
                })
                ->limit(15)
                ->get();

            $results = $pbStok->map(function ($item) {
                $stok = $item->stok ?? 0;
                $stockStatus = $stok == 0 ? 'empty'
                    : ($stok <= 10 ? 'low' : 'available');

                return [
                    'id' => $item->id,
                    'nama' => $item->barang->nama_barang ?? '-',
                    'kode' => $item->kode_barang,
                    'stok' => $stok,
                    'satuan' => $item->barang->satuan ?? '',
                    'harga' => 'Rp ' . number_format($item->harga ?? 0, 0, ',', '.'),
                    'kategori' => $item->barang->kategori->nama ?? '-',
                    'bagian' => $item->bagian->nama ?? '-',
                    'stock_status' => $stockStatus,
                ];
            })->values();
        } else {
            // TAB 2 → Kelola Barang Masuk
            $barang = Barang::with(['kategori', 'pbStok'])
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
                $stockStatus = $totalStok == 0 ? 'empty'
                    : ($totalStok <= 10 ? 'low' : 'available');

                $harga = $item->harga_barang
                    ?? optional($item->pbStok->first())->harga
                    ?? 0;

                return [
                    'id' => $item->kode_barang,
                    'nama' => $item->nama_barang,
                    'kode' => $item->kode_barang,
                    'stok' => $totalStok,
                    'satuan' => $item->satuan,
                    'harga' => 'Rp ' . number_format($harga, 0, ',', '.'),
                    'kategori' => $item->kategori->nama ?? '-',
                    'bagian' => 'Gudang Utama',
                    'stock_status' => $stockStatus,
                ];
            })->values();
        }

        return response()->json($results);
    }

    /**
     * Cek apakah ada filter apapun di TAB 1
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
     * ✅ Filter untuk Data Barang Gudang Utama (Distribusi)
     */
    private function getFilteredPbStok(Request $request)
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
            (float) $request->harga_min > (float) $request->harga_max
        ) {
            return collect();
        }

        $query = PbStok::with(['barang.kategori', 'bagian']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('barang', function ($sub) use ($search) {
                    $sub->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                })
                    ->orWhereHas('barang.kategori', function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('bagian', function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('kategori_id')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('id_kategori', $request->kategori_id);
            });
        }

        if ($request->filled('satuan')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('satuan', $request->satuan);
            });
        }

        if ($request->filled('stok_min')) {
            $query->where('stok', '>=', (int) $request->stok_min);
        }
        if ($request->filled('stok_max')) {
            $query->where('stok', '<=', (int) $request->stok_max);
        }

        if ($request->filled('harga_min')) {
            $query->where('harga', '>=', (float) $request->harga_min);
        }
        if ($request->filled('harga_max')) {
            $query->where('harga', '<=', (float) $request->harga_max);
        }

        return $query->orderBy('kode_barang')->get();
    }

    /**
     * ✅ Filter untuk "Stok Per Bagian - View Only"
     */
    private function getFilteredStokBagian(Request $request)
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
            (float) $request->harga_min > (float) $request->harga_max
        ) {
            return collect();
        }

        $query = StokBagian::with(['barang.kategori', 'bagian'])
            ->select('stok_bagian.*')
            ->distinct();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('barang', function ($sub) use ($search) {
                    $sub->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                })
                    ->orWhereHas('barang.kategori', function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('bagian', function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('kategori_id')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('id_kategori', $request->kategori_id);
            });
        }

        if ($request->filled('satuan')) {
            $query->whereHas('barang', function ($q) use ($request) {
                $q->where('satuan', $request->satuan);
            });
        }

        if ($request->filled('stok_min')) {
            $query->where('stok', '>=', (int) $request->stok_min);
        }
        if ($request->filled('stok_max')) {
            $query->where('stok', '<=', (int) $request->stok_max);
        }

        if ($request->filled('harga_min')) {
            $query->where('harga', '>=', (float) $request->harga_min);
        }
        if ($request->filled('harga_max')) {
            $query->where('harga', '<=', (float) $request->harga_max);
        }

        return $query->orderBy('kode_barang')
            ->orderBy('batch_number')
            ->get()
            ->unique(function ($item) {
                return $item->kode_barang . '-' . $item->bagian_id . '-' . $item->batch_number;
            });
    }

    /**
     * ✅ Search sederhana untuk TAB "Kelola Barang Masuk"
     */
    private function getSearchBarangMasuk(Request $request)
    {
        $search = trim($request->get('search', ''));

        if ($search === '') {
            return collect();
        }

        return Barang::with(['kategori', 'pbStok'])
            ->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($sub) use ($search) {
                        $sub->where('nama', 'like', "%{$search}%");
                    });
            })
            ->orderBy('nama_barang')
            ->get();
    }
}