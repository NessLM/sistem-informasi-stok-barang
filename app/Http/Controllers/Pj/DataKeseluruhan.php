<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Gudang;
use App\Models\PjStok;
use App\Models\TransaksiBarangKeluar;
use App\Models\Bagian;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::pjMenu();
        $search = $request->input('search');
        $user = Auth::user();

        // Ambil gudang berdasarkan user yang login
        $gudangUser = $user->gudang;

        if (!$gudangUser) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan. Hubungi administrator.'
            ]);
        }

        // Filter kategori hanya dari gudang user
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                }
            },
            'gudang'
        ])->where('gudang_id', $gudangUser->id);

        $kategori = $kategoriQuery->get();

        $selectedGudang = $gudangUser;

        // Ambil semua bagian untuk dropdown
        $bagian = Bagian::orderBy('nama')->get();

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

        // Filter barang hanya dari gudang user
        $barang = $this->getFilteredBarang($request, $gudangUser->id);

        return view('staff.pj.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'selectedGudang',
            'bagian'
        ));
    }

    /**
     * Tampilkan data keseluruhan berdasarkan gudang (untuk admin)
     */
    public function byGudang(Request $request, $slug)
    {
        $menu = MenuHelper::adminMenu();
        $search = $request->input('search');

        // Cari gudang berdasarkan slug
        $gudang = $this->findGudangBySlug($slug);

        if (!$gudang) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Gudang tidak ditemukan.'
            ]);
        }

        // Filter kategori dari gudang yang dipilih
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                }
            },
            'gudang'
        ])->where('gudang_id', $gudang->id);

        $kategori = $kategoriQuery->get();

        $selectedGudang = $gudang;

        // Ambil semua bagian untuk dropdown
        $bagian = Bagian::orderBy('nama')->get();

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

        // Filter barang dari gudang yang dipilih
        $barang = $this->getFilteredBarang($request, $gudang->id);

        return view('staff.pj.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'selectedGudang',
            'bagian'
        ));
    }

    /**
     * Cari gudang berdasarkan slug
     */
    private function findGudangBySlug($slug)
    {
        $gudangs = Gudang::all();

        foreach ($gudangs as $gudang) {
            $gudangSlug = $this->createSlugFromGudangName($gudang->nama);
            if ($gudangSlug === $slug) {
                return $gudang;
            }
        }

        return null;
    }

    /**
     * Buat slug dari nama gudang (sama seperti di MenuHelper)
     */
    private function createSlugFromGudangName($gudangName)
    {
        $cleaned = preg_replace('/^gudang\s+/i', '', $gudangName);
        $slug = strtolower(str_replace([' ', '_'], '-', $cleaned));
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * API: Search suggestions untuk autocomplete
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        $user = Auth::user();

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        if (!$user->gudang_id) {
            return response()->json([]);
        }

        $barangQuery = Barang::with(['kategori.gudang'])
            ->where(function ($q) use ($query) {
                $q->where('nama_barang', 'like', "%{$query}%")
                    ->orWhere('kode_barang', 'like', "%{$query}%");
            })
            ->whereHas('kategori', function ($q) use ($user) {
                $q->where('gudang_id', $user->gudang_id);
            });

        $barang = $barangQuery->limit(10)->get();

        $results = $barang->map(function ($item) use ($user) {
            $pjStok = PjStok::where('kode_barang', $item->kode_barang)
                ->where('id_gudang', $user->gudang_id)
                ->first();

            $stok = $pjStok ? $pjStok->stok : 0;

            $stockStatus = 'available';
            if ($stok == 0) {
                $stockStatus = 'empty';
            } elseif ($stok <= 10) {
                $stockStatus = 'low';
            }

            return [
                'kode_barang' => $item->kode_barang,
                'nama' => $item->nama_barang,
                'kode' => $item->kode_barang,
                'stok' => $stok,
                'kategori' => $item->kategori->nama ?? '-',
                'gudang' => $item->kategori->gudang->nama ?? '-',
                'stock_status' => $stockStatus
            ];
        });

        return response()->json($results);
    }

    /**
     * Proses barang keluar - FIXED sesuai model
     */
    public function barangKeluar(Request $request, $kode_barang)
    {
        // Debug: Log semua data yang diterima dari request
        Log::info('=== BARANG KELUAR REQUEST ===');
        Log::info('All Request Data:', $request->all());
        Log::info('Kode Barang:', ['kode_barang' => $kode_barang]);
        Log::info('Bagian ID from request:', ['bagian_id' => $request->bagian_id]);

        // Validasi input
        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'nama_penerima' => 'required|string|max:255',
            'tanggal' => 'nullable|date',
            'bagian_id' => 'nullable|exists:bagian,id',
            'keterangan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        Log::info('Validated Data:', $validated);

        $user = Auth::user();

        // Cari barang berdasarkan kode_barang
        $barang = Barang::where('kode_barang', $kode_barang)->first();

        if (!$barang) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Barang tidak ditemukan.'
            ]);
        }

        // Cek apakah barang ada di gudang user
        if ($barang->kategori->gudang_id != $user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Barang tidak ditemukan di gudang Anda.'
            ]);
        }

        // Cek stok di PJ Stok
        $pjStok = PjStok::where('kode_barang', $kode_barang)
            ->where('id_gudang', $user->gudang_id)
            ->first();

        if (!$pjStok || $pjStok->stok < $request->jumlah) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Stok Tidak Cukup!',
                'message' => 'Stok barang di gudang Anda tidak mencukupi. Stok tersedia: ' . ($pjStok ? $pjStok->stok : 0)
            ]);
        }

        // Upload bukti jika ada
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $filename = time() . '_' . $file->getClientOriginalName();
            $buktiPath = $file->storeAs('bukti_barang_keluar', $filename, 'public');
            Log::info('Bukti uploaded:', ['path' => $buktiPath]);
        }

        // Proses bagian_id - pastikan null jika kosong
        $bagianId = $request->input('bagian_id');

        // Jika bagian_id adalah string kosong atau "null", set ke null
        if ($bagianId === '' || $bagianId === 'null' || empty($bagianId)) {
            $bagianId = null;
        } else {
            // Pastikan bagian_id adalah integer
            $bagianId = (int) $bagianId;

            // Double check apakah bagian benar-benar ada
            $bagianExists = Bagian::find($bagianId);
            if (!$bagianExists) {
                Log::warning('Bagian ID tidak ditemukan:', ['bagian_id' => $bagianId]);
                $bagianId = null;
            }
        }

        Log::info('Processed Bagian ID:', ['bagian_id' => $bagianId]);

        // Siapkan data untuk disimpan
        $dataToInsert = [
            'kode_barang' => $kode_barang,
            'id_gudang' => $user->gudang_id,
            'user_id' => $user->id,
            'bagian_id' => $bagianId,
            'nama_penerima' => $request->nama_penerima,
            'jumlah' => $request->jumlah,
            'tanggal' => $request->tanggal ?? now()->format('Y-m-d'),
            'keterangan' => $request->keterangan,
            'bukti' => $buktiPath,
        ];

        Log::info('Data to be inserted:', $dataToInsert);

        // Simpan data barang keluar menggunakan method prosesBarangKeluar
        try {
            $barangKeluar = TransaksiBarangKeluar::prosesBarangKeluar($dataToInsert);

            Log::info('Barang Keluar Created Successfully:', $barangKeluar->toArray());

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Barang keluar berhasil dicatat.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error creating Barang Keluar:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Filter barang berdasarkan kriteria
     */
    private function getFilteredBarang(Request $request, $gudangId)
    {
        $query = Barang::with(['kategori.gudang']);

        // Filter berdasarkan gudang user
        $query->whereHas('kategori', function ($q) use ($gudangId) {
            $q->where('gudang_id', $gudangId);
        });

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

        // Filter stok - menggunakan PjStok
        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            $query->whereHas('pjStok', function ($q) use ($request, $gudangId) {
                $q->where('id_gudang', $gudangId);
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