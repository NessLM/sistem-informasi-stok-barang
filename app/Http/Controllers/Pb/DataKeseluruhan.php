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

        // Validasi barang exists
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

            // Update atau create pb_stok
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

            // Tambah stok
            $pbStok->increment('stok', $request->jumlah);

            // Update harga (ambil harga terbaru dari barang masuk)
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
                $stockStatus = $totalStok == 0 ? 'empty' : ($totalStok <= 10 ? 'low' : 'available');

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
                $stockStatus = $item->stok == 0 ? 'empty' : ($item->stok <= 10 ? 'low' : 'available');

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

        if ($request->filled('kategori_id')) {
            $query->where('id_kategori', $request->kategori_id);
        }

        if ($request->filled('satuan')) {
            $query->where('satuan', $request->satuan);
        }

        if ($request->filled('harga_min')) {
            $query->where('harga_barang', '>=', (float) $request->harga_min);
        }
        if ($request->filled('harga_max')) {
            $query->where('harga_barang', '<=', (float) $request->harga_max);
        }

        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            $stokMin = $request->filled('stok_min') ? (int) $request->stok_min : 0;
            $stokMax = $request->filled('stok_max') ? (int) $request->stok_max : PHP_INT_MAX;

            $query->whereHas('pbStok', function ($q) use ($stokMin, $stokMax) {
            })->get()->filter(function ($barang) use ($stokMin, $stokMax) {
                $totalStok = $barang->pbStok->sum('stok');
                return $totalStok >= $stokMin && $totalStok <= $stokMax;
            });
        }

        return $query->get();
    }
}