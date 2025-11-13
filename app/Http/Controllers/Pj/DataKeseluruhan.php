<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\StokBagian;
use Illuminate\Support\Facades\Schema;
use App\Models\Gudang;
use App\Models\PjStok;
use App\Models\TransaksiBarangKeluar;
use App\Models\TransaksiDistribusi;
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
     */
    public function index(Request $request)
    {
        $menu  = MenuHelper::pjMenu();
        $user  = Auth::user();

        $gudang     = $user->gudang_id ? Gudang::find($user->gudang_id) : null;
        $bagianUser = $user->bagian_id ? Bagian::find($user->bagian_id) : null;

        /* =================== MODE GUDANG (legacy) =================== */
        if ($gudang) {
            // Ambil SEMUA kategori yang ada di gudang ini
            $kategori = Kategori::where('gudang_id', $gudang->id)
                ->with('gudang')
                ->orderBy('nama')
                ->get();

            // Load barang untuk setiap kategori (stok > 0)
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

            // Ambil data barang masuk dari transaksi_distribusi dengan status
            $barangMasuk = DB::table('transaksi_distribusi')
                ->join('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->where('transaksi_distribusi.bagian_id', $gudang->id)
                ->orderBy('transaksi_distribusi.tanggal', 'desc')
                ->select(
                    'transaksi_distribusi.id',
                    'transaksi_distribusi.tanggal',
                    'barang.kode_barang',
                    'barang.nama_barang',
                    'transaksi_distribusi.jumlah',
                    'barang.satuan',
                    'transaksi_distribusi.keterangan',
                    'transaksi_distribusi.bukti',
                    DB::raw("COALESCE(transaksi_distribusi.status_konfirmasi, 'pending') as status_konfirmasi")
                )
                ->limit(50)
                ->get();

            return view('staff.pj.datakeseluruhan', compact(
                'menu',
                'kategori',
                'barang',
                'selectedGudang',
                'bagian',
                'barangHabis',
                'barangMasuk',
                'lowThreshold',
                'ringkasanCounts'
            ));
        }

        /* =================== MODE BAGIAN =================== */
        if ($bagianUser) {
            // Ambil SEMUA kategori yang ada di sistem (bukan hanya yang memiliki stok)
            $kategori = Kategori::orderBy('nama')
                ->get()
                ->map(function ($k) use ($bagianUser) {
                    // Load barang yang ada di stok bagian untuk kategori ini
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

            // Spoof gudang untuk blade & kirim $bagian (koleksi) ke view
            $selectedGudang = (object)['id' => null, 'nama' => 'Bagian ' . $bagianUser->nama];
            $bagian         = collect([$bagianUser]);

            // Handle pencarian/filter
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

            // Hitung ringkasan
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

            // Ambil data barang masuk dari transaksi_distribusi untuk bagian dengan status
            $barangMasuk = DB::table('transaksi_distribusi')
                ->join('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->where('transaksi_distribusi.bagian_id', $bagianUser->id)
                ->orderBy('transaksi_distribusi.tanggal', 'desc')
                ->select(
                    'transaksi_distribusi.id',
                    'transaksi_distribusi.tanggal',
                    'barang.kode_barang',
                    'barang.nama_barang',
                    'transaksi_distribusi.jumlah',
                    'barang.satuan',
                    'transaksi_distribusi.keterangan',
                    'transaksi_distribusi.bukti',
                    DB::raw("COALESCE(transaksi_distribusi.status_konfirmasi, 'pending') as status_konfirmasi")
                )
                ->limit(50)
                ->get();

            return view('staff.pj.datakeseluruhan', compact(
                'menu',
                'kategori',
                'barang',
                'selectedGudang',
                'bagian',
                'barangHabis',
                'barangMasuk',
                'lowThreshold',
                'ringkasanCounts'
            ));
        }

        abort(403, 'Anda tidak memiliki akses ke bagian manapun.');
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

        // MODE GUDANG
        if ($user->gudang_id) {
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
                ->get();
        }
        // MODE BAGIAN
        elseif ($user->bagian_id) {
            $results = DB::table('stok_bagian')
                ->join('barang', 'stok_bagian.kode_barang', '=', 'barang.kode_barang')
                ->join('kategori', 'barang.id_kategori', '=', 'kategori.id')
                ->join('bagian', 'stok_bagian.bagian_id', '=', 'bagian.id')
                ->where('stok_bagian.bagian_id', $user->bagian_id)
                ->where('stok_bagian.stok', '>', 0)
                ->where(function ($q) use ($query) {
                    $q->where('barang.nama_barang', 'like', "%{$query}%")
                        ->orWhere('barang.kode_barang', 'like', "%{$query}%");
                })
                ->select(
                    'barang.kode_barang',
                    'barang.nama_barang as nama',
                    'stok_bagian.stok',
                    'kategori.nama as kategori',
                    'bagian.nama as gudang'
                )
                ->limit(10)
                ->get();
        } else {
            return response()->json([]);
        }

        $mapped = $results->map(function ($item) {
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

        return response()->json($mapped);
    }

    /**
     * Proses barang keluar
     */
    public function barangKeluar(Request $request, $kode_barang)
    {
        Log::info('=== BARANG KELUAR REQUEST ===', ['kode_barang' => $kode_barang, 'all' => $request->all()]);

        $validated = $request->validate([
            'jumlah'        => 'required|integer|min:1',
            'nama_penerima' => 'required|string|max:255',
            'tanggal'       => 'nullable|date',
            'bagian_id'     => 'nullable|exists:bagian,id',
            'keterangan'    => 'nullable|string',
            'bukti'         => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        $user = Auth::user();
        $bagianId = $validated['bagian_id'] ?? $user->bagian_id;
        
        if (!$bagianId) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Bagian wajib',
                'message' => 'Pilih Bagian terlebih dahulu.'
            ]);
        }

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

            // MODE GUDANG
            if ($user->gudang_id && Schema::hasTable('pj_stok')) {
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

                if (Schema::hasColumn('transaksi_barang_keluar', 'id_gudang')) {
                    $dataToInsert['id_gudang'] = $user->gudang_id;
                }
            }
            // MODE BAGIAN
            else {
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

            TransaksiBarangKeluar::create($dataToInsert);

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
     * Konfirmasi barang masuk ke stok_bagian
     */
    public function konfirmasiBarangMasuk($id)
    {
        Log::info('=== KONFIRMASI BARANG MASUK REQUEST ===', ['transaksi_id' => $id]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // Ambil data transaksi distribusi
            $transaksi = DB::table('transaksi_distribusi')
                ->where('id', $id)
                ->first();

            if (!$transaksi) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Data Tidak Ditemukan',
                    'message' => 'Transaksi distribusi tidak ditemukan.'
                ]);
            }

            // Validasi: Hanya bisa konfirmasi barang ke gudang/bagian yang sesuai
            if ($user->gudang_id && $transaksi->bagian_id != $user->gudang_id) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Akses Ditolak',
                    'message' => 'Anda tidak memiliki akses untuk mengkonfirmasi barang ini.'
                ]);
            }

            if ($user->bagian_id && $transaksi->bagian_id != $user->bagian_id) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Akses Ditolak',
                    'message' => 'Anda tidak memiliki akses untuk mengkonfirmasi barang ini.'
                ]);
            }

            // Cek apakah sudah dikonfirmasi
            $statusKonfirmasi = $transaksi->status_konfirmasi ?? 'pending';
            if ($statusKonfirmasi === 'confirmed') {
                return back()->with('toast', [
                    'type' => 'warning',
                    'title' => 'Sudah Dikonfirmasi',
                    'message' => 'Barang ini sudah dikonfirmasi sebelumnya.'
                ]);
            }

            $kodeBarang = $transaksi->kode_barang;
            $jumlah = $transaksi->jumlah;

            // MODE GUDANG - Tambahkan ke pj_stok
            if ($user->gudang_id && Schema::hasTable('pj_stok')) {
                // Ambil data barang untuk mendapatkan id_kategori
                $barang = DB::table('barang')
                    ->where('kode_barang', $kodeBarang)
                    ->first();

                if (!$barang) {
                    throw new \Exception('Data barang tidak ditemukan.');
                }

                // Cek apakah sudah ada di pj_stok
                $pjStok = PjStok::where('kode_barang', $kodeBarang)
                    ->where('id_gudang', $user->gudang_id)
                    ->lockForUpdate()
                    ->first();

                if ($pjStok) {
                    // Jika sudah ada, tambahkan stok
                    $pjStok->increment('stok', $jumlah);
                } else {
                    // Jika belum ada, buat record baru
                    PjStok::create([
                        'kode_barang' => $kodeBarang,
                        'id_gudang' => $user->gudang_id,
                        'id_kategori' => $barang->id_kategori,
                        'stok' => $jumlah,
                    ]);
                }

                $namaLokasi = 'Gudang';
            }
            // MODE BAGIAN - Tambahkan ke stok_bagian
            else if ($user->bagian_id) {
                // Cek apakah sudah ada di stok_bagian
                $stokBagian = StokBagian::where('kode_barang', $kodeBarang)
                    ->where('bagian_id', $user->bagian_id)
                    ->lockForUpdate()
                    ->first();

                if ($stokBagian) {
                    // Jika sudah ada, tambahkan stok
                    $stokBagian->increment('stok', $jumlah);
                } else {
                    // Jika belum ada, buat record baru
                    StokBagian::create([
                        'kode_barang' => $kodeBarang,
                        'bagian_id' => $user->bagian_id,
                        'stok' => $jumlah,
                    ]);
                }

                $bagianData = Bagian::find($user->bagian_id);
                $namaLokasi = 'Bagian ' . ($bagianData->nama ?? '');
            } else {
                throw new \Exception('User tidak memiliki akses gudang atau bagian.');
            }

            // Update status konfirmasi di transaksi_distribusi
            DB::table('transaksi_distribusi')
                ->where('id', $id)
                ->update([
                    'status_konfirmasi' => 'confirmed',
                    'confirmed_at' => now(),
                    'confirmed_by' => $user->id,
                    'updated_at' => now(),
                ]);

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => "Barang berhasil dikonfirmasi masuk ke {$namaLokasi}. Jumlah: {$jumlah}"
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Konfirmasi barang masuk gagal', [
                'err' => $e->getMessage(), 
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Gagal mengkonfirmasi barang: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Kembalikan barang ke PB Stok
     */
    public function kembalikanBarang($id)
    {
        Log::info('=== KEMBALIKAN BARANG REQUEST ===', ['transaksi_id' => $id]);

        $user = Auth::user();

        DB::beginTransaction();
        try {
            // Ambil data transaksi distribusi
            $transaksi = DB::table('transaksi_distribusi')
                ->where('id', $id)
                ->first();

            if (!$transaksi) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Data Tidak Ditemukan',
                    'message' => 'Transaksi distribusi tidak ditemukan.'
                ]);
            }

            // Validasi: Hanya bisa mengembalikan barang ke gudang/bagian yang sesuai
            if ($user->gudang_id && $transaksi->bagian_id != $user->gudang_id) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Akses Ditolak',
                    'message' => 'Anda tidak memiliki akses untuk mengembalikan barang ini.'
                ]);
            }

            if ($user->bagian_id && $transaksi->bagian_id != $user->bagian_id) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Akses Ditolak',
                    'message' => 'Anda tidak memiliki akses untuk mengembalikan barang ini.'
                ]);
            }

            // Cek apakah sudah dikonfirmasi
            $statusKonfirmasi = $transaksi->status_konfirmasi ?? 'pending';
            if ($statusKonfirmasi === 'confirmed') {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Tidak Bisa Dikembalikan',
                    'message' => 'Barang yang sudah dikonfirmasi tidak bisa dikembalikan. Silakan gunakan fitur Barang Keluar.'
                ]);
            }

            $kodeBarang = $transaksi->kode_barang;
            $jumlah = $transaksi->jumlah;

            // Ambil data barang untuk mendapatkan id_kategori
            $barang = DB::table('barang')
                ->where('kode_barang', $kodeBarang)
                ->first();

            if (!$barang) {
                throw new \Exception('Data barang tidak ditemukan.');
            }

            // MODE GUDANG - Kembalikan ke pj_stok
            if ($user->gudang_id && Schema::hasTable('pj_stok')) {
                // Cek apakah sudah ada di pj_stok
                $pjStok = PjStok::where('kode_barang', $kodeBarang)
                    ->where('id_gudang', $user->gudang_id)
                    ->lockForUpdate()
                    ->first();

                if ($pjStok) {
                    // Jika sudah ada, tambahkan stok
                    $pjStok->increment('stok', $jumlah);
                } else {
                    // Jika belum ada, buat record baru
                    PjStok::create([
                        'kode_barang' => $kodeBarang,
                        'id_gudang' => $user->gudang_id,
                        'id_kategori' => $barang->id_kategori,
                        'stok' => $jumlah,
                    ]);
                }

                $namaLokasi = 'Gudang';
            }
            // MODE BAGIAN - Kembalikan ke pb_stok (TIDAK perlu kurangi stok_bagian karena belum dikonfirmasi)
            else if ($user->bagian_id) {
                // Cek apakah sudah ada di pb_stok
                $pbStok = DB::table('pb_stok')
                    ->where('kode_barang', $kodeBarang)
                    ->where('bagian_id', $user->bagian_id)
                    ->lockForUpdate()
                    ->first();

                if ($pbStok) {
                    // Jika sudah ada, tambahkan stok
                    DB::table('pb_stok')
                        ->where('kode_barang', $kodeBarang)
                        ->where('bagian_id', $user->bagian_id)
                        ->increment('stok', $jumlah);
                } else {
                    // Jika belum ada, buat record baru
                    DB::table('pb_stok')->insert([
                        'kode_barang' => $kodeBarang,
                        'bagian_id' => $user->bagian_id,
                        'stok' => $jumlah,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $bagianData = Bagian::find($user->bagian_id);
                $namaLokasi = 'PB ' . ($bagianData->nama ?? '');
            } else {
                throw new \Exception('User tidak memiliki akses gudang atau bagian.');
            }

            // Hapus transaksi distribusi setelah berhasil dikembalikan
            DB::table('transaksi_distribusi')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => "Barang berhasil dikembalikan ke {$namaLokasi}. Jumlah: {$jumlah}"
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Kembalikan barang gagal', [
                'err' => $e->getMessage(), 
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Gagal mengembalikan barang: ' . $e->getMessage()
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