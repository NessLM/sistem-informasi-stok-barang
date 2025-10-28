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
use Illuminate\Support\Facades\DB;

class DataKeseluruhan extends Controller
{
    /**
     * Halaman Data Keseluruhan (PJ)
     *
     * - Menampilkan kategori + barang dengan stok > 0 (seperti sebelumnya).
     * - Tambahan: Ringkasan ketersediaan & tabel "Barang Habis" (stok = 0).
     */
    public function index(Request $request)
    {
        $menu = MenuHelper::pjMenu();
        $search = $request->input('search');
        $user = Auth::user();

        // Cek gudang user
        if (!$user->gudang_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Anda belum memiliki gudang yang ditugaskan. Hubungi administrator.'
            ]);
        }

        $gudangUser = Gudang::find($user->gudang_id);
        if (!$gudangUser) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error!',
                'message' => 'Gudang tidak ditemukan.'
            ]);
        }

        // Ambil kategori berdasarkan gudang
        $kategori = Kategori::where('gudang_id', $gudangUser->id)
            ->with('gudang')
            ->get();

        // CARA BARU: Ambil barang langsung dari pj_stok (hanya stok > 0, sesuai tampilan sebelumnya)
        foreach ($kategori as $k) {
            $k->barang = DB::table('pj_stok')
                ->join('barang', 'pj_stok.kode_barang', '=', 'barang.kode_barang')
                ->where('pj_stok.id_gudang', $gudangUser->id)
                ->where('pj_stok.id_kategori', $k->id)
                ->where('pj_stok.stok', '>', 0)
                ->select(
                    'barang.kode_barang as kode',
                    'barang.nama_barang as nama',
                    'barang.satuan',
                    'pj_stok.stok',
                    'barang.id_kategori'
                )
                ->get()
                ->map(function ($item) {
                    // Bentuk objek sederhana untuk Blade
                    return (object) [
                        'kode' => $item->kode,
                        'nama' => $item->nama,
                        'satuan' => $item->satuan,
                        'stok_tersedia' => $item->stok,
                        'id_kategori' => $item->id_kategori
                    ];
                });
        }

        $selectedGudang = $gudangUser;
        $bagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
            ->orderBy('nama')
            ->get();

        // Validasi filter harga (jika sewaktu-waktu dipakai)
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

        // Filter barang untuk search (tetap stok > 0)
        $barang = $this->getFilteredBarang($request, $gudangUser->id);

        /**
         * ===========================
         * TAMBAHAN: Ringkasan & Habis
         * ===========================
         */
        $lowThreshold = 10; // ambang "menipis" ditampilkan di badge

        // Ambil semua rows pj_stok untuk gudang ini (untuk ringkasan)
        $allRows = DB::table('pj_stok')
            ->join('barang', 'pj_stok.kode_barang', '=', 'barang.kode_barang')
            ->join('kategori', 'pj_stok.id_kategori', '=', 'kategori.id')
            ->where('pj_stok.id_gudang', $gudangUser->id)
            ->select('pj_stok.stok', 'barang.kode_barang', 'barang.nama_barang', 'barang.satuan', 'kategori.nama as kategori_nama')
            ->get();

        // Hitung ringkasan
        $countEmpty = $allRows->where('stok', 0)->count();
        $countLow = $allRows->filter(fn($r) => $r->stok > 0 && $r->stok < $lowThreshold)->count();
        $countOk = max($allRows->count() - $countEmpty - $countLow, 0);
        $ringkasanCounts = ['ok' => $countOk, 'low' => $countLow, 'empty' => $countEmpty];

        // Kumpulkan barang habis (stok = 0) untuk tabel baru
        $barangHabis = $allRows->where('stok', 0)->map(function ($item) {
            return (object) [
                'kode' => $item->kode_barang,
                'nama' => $item->nama_barang,
                'satuan' => $item->satuan,
                'stok_tersedia' => 0,
                'kategori' => (object) ['nama' => $item->kategori_nama],
            ];
        })->values();

        // Debug log
        Log::info('=== PJ DataKeseluruhan Debug ===', [
            'user_id' => $user->id,
            'user_name' => $user->nama,
            'gudang_id' => $gudangUser->id,
            'gudang_nama' => $gudangUser->nama,
            'total_kategori' => $kategori->count(),
            'kategori_details' => $kategori->map(function ($k) {
                return [
                    'id' => $k->id,
                    'nama' => $k->nama,
                    'jumlah_barang' => $k->barang->count(),
                    'barang' => $k->barang->pluck('nama')->toArray()
                ];
            })->toArray(),
            'total_barang_filtered' => $barang->count(),
            'ringkasan' => $ringkasanCounts,
            'barang_habis_count' => $barangHabis->count(),
        ]);

        return view('staff.pj.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'selectedGudang',
            'bagian',
            // tambahan:
            'barangHabis',
            'lowThreshold',
            'ringkasanCounts'
        ));
    }

    /**
     * API: Search suggestions untuk autocomplete
     * - Hanya mengembalikan stok > 0 (agar tombol "Barang Keluar" valid).
     */
    /**
     * API: Search suggestions untuk autocomplete
     * - Hanya mengembalikan stok > 0 (agar tombol "Barang Keluar" valid).
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

        // Query dengan JOIN pj_stok - PERBAIKAN: langsung join gudang dari pj_stok
        $results = DB::table('pj_stok')
            ->join('barang', 'pj_stok.kode_barang', '=', 'barang.kode_barang')
            ->join('kategori', 'pj_stok.id_kategori', '=', 'kategori.id')
            ->join('gudang', 'pj_stok.id_gudang', '=', 'gudang.id')
            ->where('pj_stok.id_gudang', $user->gudang_id)
            ->where('pj_stok.stok', '>', 0)
            ->where(function ($q) use ($query) {
                $q->where('barang.nama_barang', 'like', "%{$query}%")
                    ->orWhere('barang.kode_barang', 'like', "%{$query}%");
            })
            ->select(
                'barang.kode_barang',
                'barang.nama_barang as nama',
                'pj_stok.stok',
                'kategori.nama as kategori',
                'gudang.nama as gudang'
            )
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $stockStatus = 'available';
                if ($item->stok == 0) {
                    $stockStatus = 'empty';
                } elseif ($item->stok <= 10) {
                    $stockStatus = 'low';
                }

                return [
                    'kode_barang' => $item->kode_barang,
                    'nama' => $item->nama,
                    'kode' => $item->kode_barang,
                    'stok' => $item->stok,
                    'kategori' => $item->kategori,
                    'gudang' => $item->gudang,
                    'stock_status' => $stockStatus
                ];
            });

        return response()->json($results);
    }

    /**
     * Proses barang keluar
     */
    public function barangKeluar(Request $request, $kode_barang)
    {
        Log::info('=== BARANG KELUAR REQUEST ===');
        Log::info('All Request Data:', $request->all());
        Log::info('Kode Barang:', ['kode_barang' => $kode_barang]);

        // Validasi input
        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'nama_penerima' => 'required|string|max:255',
            'tanggal' => 'nullable|date',
            'bagian_id' => 'nullable|exists:bagian,id',
            'keterangan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

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

        // Cek stok di PJ Stok
        $pjStok = PjStok::where('kode_barang', $kode_barang)
            ->where('id_gudang', $user->gudang_id)
            ->first();

        Log::info('Stok Check:', [
            'kode_barang' => $kode_barang,
            'gudang_id' => $user->gudang_id,
            'stok_found' => $pjStok ? 'Yes' : 'No',
            'stok_value' => $pjStok ? $pjStok->stok : 0,
            'requested_qty' => $request->jumlah
        ]);

        if (!$pjStok) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Stok Tidak Ditemukan!',
                'message' => 'Barang tidak ditemukan di gudang Anda.'
            ]);
        }

        if ($pjStok->stok < $request->jumlah) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Stok Tidak Cukup!',
                'message' => "Stok barang di gudang Anda tidak mencukupi. Stok tersedia: {$pjStok->stok}"
            ]);
        }

        // Upload bukti jika ada
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');

            // Generate nama file yang unik dan konsisten
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Simpan dengan nama yang sudah ditentukan
            $buktiPath = $file->storeAs('bukti-barang-keluar', $fileName, 'public');

            Log::info('Bukti uploaded:', [
                'path' => $buktiPath,
                'filename' => $fileName
            ]);
        }

        // Proses bagian_id (boleh null)
        $bagianId = $request->input('bagian_id');
        if ($bagianId === '' || $bagianId === 'null' || empty($bagianId)) {
            $bagianId = null;
        } else {
            $bagianId = (int) $bagianId;
            $bagianExists = Bagian::find($bagianId);
            if (!$bagianExists) {
                Log::warning('Bagian ID tidak ditemukan:', ['bagian_id' => $bagianId]);
                $bagianId = null;
            }
        }

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

        // Simpan data barang keluar
        try {
            DB::beginTransaction();

            $barangKeluar = TransaksiBarangKeluar::create($dataToInsert);

            // Kurangi stok di pj_stok
            $pjStok->kurangiStok($request->jumlah);

            DB::commit();

            Log::info('Barang Keluar Created Successfully:', [
                'transaksi_id' => $barangKeluar->id,
                'stok_baru' => $pjStok->stok
            ]);

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Barang keluar berhasil dicatat. Stok tersisa: {$pjStok->stok}"
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

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
     * Filter barang berdasarkan kriteria (stok > 0)
     */
    private function getFilteredBarang(Request $request, $gudangId)
    {
        if (!$request->hasAny(['search', 'kode', 'stok_min', 'stok_max', 'kategori_id', 'satuan'])) {
            return collect([]);
        }

        $query = DB::table('pj_stok')
            ->join('barang', 'pj_stok.kode_barang', '=', 'barang.kode_barang')
            ->join('kategori', 'pj_stok.id_kategori', '=', 'kategori.id')
            ->where('pj_stok.id_gudang', $gudangId)
            ->where('pj_stok.stok', '>', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('barang.nama_barang', 'like', "%{$search}%")
                    ->orWhere('barang.kode_barang', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kode')) {
            $query->where('barang.kode_barang', 'like', "%{$request->kode}%");
        }

        if ($request->filled('stok_min')) {
            $query->where('pj_stok.stok', '>=', (int) $request->stok_min);
        }

        if ($request->filled('stok_max')) {
            $query->where('pj_stok.stok', '<=', (int) $request->stok_max);
        }

        if ($request->filled('kategori_id')) {
            $query->where('pj_stok.id_kategori', $request->kategori_id);
        }

        if ($request->filled('satuan')) {
            $query->where('barang.satuan', $request->satuan);
        }

        return $query->select(
            'barang.kode_barang as kode',
            'barang.nama_barang as nama',
            'barang.satuan',
            'pj_stok.stok as stok_tersedia',
            'kategori.nama as kategori_nama',
            'pj_stok.id_kategori'
        )
            ->get()
            ->map(function ($item) {
                return (object) [
                    'id' => null,
                    'kode' => $item->kode,
                    'nama' => $item->nama,
                    'satuan' => $item->satuan,
                    'stok_tersedia' => $item->stok_tersedia,
                    'kategori' => (object) ['nama' => $item->kategori_nama]
                ];
            });
    }
}
