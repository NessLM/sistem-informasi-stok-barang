<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\StokBagian;
use Illuminate\Support\Facades\Schema; // dipakai di fix #2 di bawah
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
        $menu  = MenuHelper::pjMenu();
        $user  = Auth::user();

        $gudang     = $user->gudang_id ? Gudang::find($user->gudang_id) : null;
        $bagianUser = $user->bagian_id ? Bagian::find($user->bagian_id) : null;

        /* =================== MODE GUDANG (legacy) =================== */
        if ($gudang) {
            $kategori = Kategori::where('gudang_id', $gudang->id)
                ->with('gudang')->get();

            foreach ($kategori as $k) {
                $k->barang = DB::table('pj_stok')
                    ->join('barang', 'pj_stok.kode_barang', '=', 'barang.kode_barang')
                    ->where('pj_stok.id_gudang', $gudang->id)
                    ->where('pj_stok.id_kategori', $k->id)
                    ->where('pj_stok.stok', '>', 0)
                    ->select(
                        'barang.kode_barang as kode',
                        'barang.nama_barang as nama',
                        'barang.satuan',
                        'pj_stok.stok as stok_tersedia',
                        'barang.id_kategori'
                    )
                    ->orderBy('barang.nama_barang')
                    ->get()
                    ->map(fn($i) => (object)[
                        'kode' => $i->kode,
                        'nama' => $i->nama,
                        'satuan' => $i->satuan,
                        'stok_tersedia' => $i->stok_tersedia,
                        'id_kategori' => $i->id_kategori,
                    ]);
            }

            $selectedGudang = $gudang;
            // ⬇️ kirim VARIABEL bernama $bagian ke view
            $bagian = Bagian::whereNotIn('nama', ['Umum', 'Gudang', 'Operasional'])
                ->orderBy('nama')->get();

            $barang = $this->getFilteredBarang($request, $gudang->id);

            $lowThreshold = 10;
            $allRows = DB::table('pj_stok')
                ->join('barang', 'pj_stok.kode_barang', '=', 'barang.kode_barang')
                ->join('kategori', 'pj_stok.id_kategori', '=', 'kategori.id')
                ->where('pj_stok.id_gudang', $gudang->id)
                ->select('pj_stok.stok', 'barang.kode_barang', 'barang.nama_barang', 'barang.satuan', 'kategori.nama as kategori_nama')
                ->get();
            $countEmpty = $allRows->where('stok', 0)->count();
            $countLow   = $allRows->filter(fn($r) => $r->stok > 0 && $r->stok < $lowThreshold)->count();
            $countOk    = max($allRows->count() - $countEmpty - $countLow, 0);
            $ringkasanCounts = ['ok' => $countOk, 'low' => $countLow, 'empty' => $countEmpty];

            $barangHabis = $allRows->where('stok', 0)->map(fn($i) => (object)[
                'kode' => $i->kode_barang,
                'nama' => $i->nama_barang,
                'satuan' => $i->satuan,
                'stok_tersedia' => 0,
                'kategori' => (object)['nama' => $i->kategori_nama],
            ])->values();

            return view('staff.pj.datakeseluruhan', compact(
                'menu',
                'kategori',
                'barang',
                'selectedGudang',
                'bagian',
                'barangHabis',
                'lowThreshold',
                'ringkasanCounts'
            ));
        }

        /* =================== MODE BAGIAN (baru) =================== */
        if ($bagianUser) {
            $kategori = DB::table('stok_bagian')
                ->join('barang', 'stok_bagian.kode_barang', '=', 'barang.kode_barang')
                ->join('kategori', 'barang.id_kategori', '=', 'kategori.id')
                ->where('stok_bagian.bagian_id', $bagianUser->id)
                ->select('kategori.id', 'kategori.nama')
                ->distinct()->orderBy('kategori.nama')->get()
                ->map(function ($k) use ($bagianUser) {
                    $items = DB::table('stok_bagian')
                        ->join('barang', 'stok_bagian.kode_barang', '=', 'barang.kode_barang')
                        ->where('stok_bagian.bagian_id', $bagianUser->id)
                        ->where('barang.id_kategori', $k->id)
                        ->where('stok_bagian.stok', '>', 0)
                        ->select(
                            'barang.kode_barang as kode',
                            'barang.nama_barang as nama',
                            'barang.satuan',
                            'stok_bagian.stok as stok_tersedia'
                        )
                        ->orderBy('barang.nama_barang')
                        ->get()
                        ->map(fn($i) => (object)[
                            'kode' => $i->kode,
                            'nama' => $i->nama,
                            'satuan' => $i->satuan,
                            'stok_tersedia' => $i->stok_tersedia,
                            'id_kategori' => $k->id,
                        ]);
                    return (object)[
                        'id' => $k->id,
                        'nama' => $k->nama,
                        'barang' => $items,
                        'gudang' => (object)['nama' => 'Bagian ' . $bagianUser->nama],
                    ];
                });

            // spoof gudang utk blade & kirim $bagian (koleksi) ke view
            $selectedGudang = (object)['id' => null, 'nama' => 'Bagian ' . $bagianUser->nama];
            $bagian         = collect([$bagianUser]);

            $barang = collect([]);
            if ($request->hasAny(['search', 'kode', 'stok_min', 'stok_max', 'kategori_id', 'satuan'])) {
                $q = DB::table('stok_bagian')
                    ->join('barang', 'stok_bagian.kode_barang', '=', 'barang.kode_barang')
                    ->join('kategori', 'barang.id_kategori', '=', 'kategori.id')
                    ->where('stok_bagian.bagian_id', $bagianUser->id)
                    ->where('stok_bagian.stok', '>', 0);

                if ($request->filled('search')) {
                    $s = $request->search;
                    $q->where(function ($w) use ($s) {
                        $w->where('barang.nama_barang', 'like', "%{$s}%")
                            ->orWhere('barang.kode_barang', 'like', "%{$s}%");
                    });
                }
                if ($request->filled('kode'))        $q->where('barang.kode_barang', 'like', "%{$request->kode}%");
                if ($request->filled('stok_min'))    $q->where('stok_bagian.stok', '>=', (int)$request->stok_min);
                if ($request->filled('stok_max'))    $q->where('stok_bagian.stok', '<=', (int)$request->stok_max);
                if ($request->filled('kategori_id')) $q->where('barang.id_kategori', $request->kategori_id);
                if ($request->filled('satuan'))      $q->where('barang.satuan', $request->satuan);

                $barang = $q->select(
                    'barang.kode_barang as kode',
                    'barang.nama_barang as nama',
                    'barang.satuan',
                    'stok_bagian.stok as stok_tersedia',
                    'kategori.nama as kategori_nama',
                    'barang.id_kategori'
                )->orderBy('barang.nama_barang')->get()
                    ->map(fn($i) => (object)[
                        'id' => null,
                        'kode' => $i->kode,
                        'nama' => $i->nama,
                        'satuan' => $i->satuan,
                        'stok_tersedia' => $i->stok_tersedia,
                        'kategori' => (object)['nama' => $i->kategori_nama]
                    ]);
            }

            $lowThreshold = 10;
            $allRows = DB::table('stok_bagian')
                ->join('barang', 'stok_bagian.kode_barang', '=', 'barang.kode_barang')
                ->join('kategori', 'barang.id_kategori', '=', 'kategori.id')
                ->where('stok_bagian.bagian_id', $bagianUser->id)
                ->select('stok_bagian.stok', 'barang.kode_barang', 'barang.nama_barang', 'barang.satuan', 'kategori.nama as kategori_nama')
                ->get();
            $countEmpty = $allRows->where('stok', 0)->count();
            $countLow   = $allRows->filter(fn($r) => $r->stok > 0 && $r->stok < $lowThreshold)->count();
            $countOk    = max($allRows->count() - $countEmpty - $countLow, 0);
            $ringkasanCounts = ['ok' => $countOk, 'low' => $countLow, 'empty' => $countEmpty];

            $barangHabis = $allRows->where('stok', 0)->map(fn($i) => (object)[
                'kode' => $i->kode_barang,
                'nama' => $i->nama_barang,
                'satuan' => $i->satuan,
                'stok_tersedia' => 0,
                'kategori' => (object)['nama' => $i->kategori_nama],
            ])->values();

            return view('staff.pj.datakeseluruhan', compact(
                'menu',
                'kategori',
                'barang',
                'selectedGudang',
                'bagian',
                'barangHabis',
                'lowThreshold',
                'ringkasanCounts'
            ));
        }

        abort(403, 'Anda tidak memiliki akses ke bagian manapun.');
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
        Log::info('=== BARANG KELUAR REQUEST ===', ['kode_barang' => $kode_barang, 'all' => $request->all()]);

        // Validasi
        $validated = $request->validate([
            'jumlah'        => 'required|integer|min:1',
            'nama_penerima' => 'required|string|max:255',
            'tanggal'       => 'nullable|date',
            'bagian_id'     => 'nullable|exists:bagian,id', // required kalau user tidak punya bagian default
            'keterangan'    => 'nullable|string',
            'bukti'         => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        $user = Auth::user();

        // Tentukan bagian sumber: dari form (dropdown) atau default dari user
        $bagianId = $validated['bagian_id'] ?? $user->bagian_id;
        if (!$bagianId) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Bagian wajib',
                'message' => 'Pilih Bagian terlebih dahulu.'
            ]);
        }

        // Upload bukti (opsional)
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $file     = $request->file('bukti');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $buktiPath = $file->storeAs('bukti-barang-keluar', $fileName, 'public');
            Log::info('Bukti uploaded', ['path' => $buktiPath]);
        }

        DB::beginTransaction();
        try {
            $jumlah = (int) $validated['jumlah'];
            $tanggal = $validated['tanggal'] ?? now()->toDateString();

            $dataToInsert = [
                'kode_barang'   => $kode_barang,
                'user_id'       => $user->id,
                'bagian_id'     => $bagianId,
                'nama_penerima' => $validated['nama_penerima'],
                'jumlah'        => $jumlah,
                'tanggal'       => $tanggal,
                'keterangan'    => $validated['keterangan'] ?? null,
                'bukti'         => $buktiPath,
            ];

            $sisaStok = null;

            // ===== MODE GUDANG (legacy) → pakai pj_stok kalau memang masih ada tabelnya
            if ($user->gudang_id && Schema::hasTable('pj_stok')) {
                /** @var \App\Models\PjStok|null $pjStok */
                $pjStok = PjStok::where('kode_barang', $kode_barang)
                    ->where('id_gudang', $user->gudang_id)
                    ->lockForUpdate()
                    ->first();

                if (!$pjStok) {
                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Tidak Ditemukan',
                        'message' => 'Barang tidak ada di gudang Anda.'
                    ]);
                }
                if ($pjStok->stok < $jumlah) {
                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Tidak Cukup',
                        'message' => "Stok tersedia: {$pjStok->stok}"
                    ]);
                }

                $pjStok->decrement('stok', $jumlah);
                $sisaStok = $pjStok->stok;

                // Set id_gudang hanya kalau kolomnya memang ada
                if (Schema::hasColumn('transaksi_barang_keluar', 'id_gudang')) {
                    $dataToInsert['id_gudang'] = $user->gudang_id;
                }
            }
            // ===== MODE BAGIAN (baru) → pakai stok_bagian
            else {
                /** @var \App\Models\StokBagian|null $stokBagian */
                $stokBagian = StokBagian::where('kode_barang', $kode_barang)
                    ->where('bagian_id', $bagianId)
                    ->lockForUpdate()
                    ->first();

                if (!$stokBagian) {
                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Bagian Tidak Ditemukan',
                        'message' => 'Barang tidak ada di stok bagian terpilih.'
                    ]);
                }
                if ($stokBagian->stok < $jumlah) {
                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Tidak Cukup',
                        'message' => "Stok bagian tersedia: {$stokBagian->stok}"
                    ]);
                }

                $stokBagian->decrement('stok', $jumlah);
                $sisaStok = $stokBagian->stok;
            }

            // Simpan transaksi keluar
            $trx = TransaksiBarangKeluar::create($dataToInsert);

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => "Barang keluar dicatat. Sisa stok: {$sisaStok}"
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Barang keluar gagal', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error',
                'message' => $e->getMessage()
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
