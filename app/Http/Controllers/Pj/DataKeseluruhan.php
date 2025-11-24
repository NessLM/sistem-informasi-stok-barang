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
        $menu = MenuHelper::pjMenu();
        $user = Auth::user();

        $gudang = $user->gudang_id ? Gudang::find($user->gudang_id) : null;
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
                    ->map(fn($i) => (object) [
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
                ->select(
                    'pj_stok.stok',
                    DB::raw('NULL as harga'), // kalau nanti pj_stok punya kolom harga, ganti jadi 'pj_stok.harga'
                    'barang.kode_barang',
                    'barang.nama_barang',
                    'barang.satuan',
                    'kategori.nama as kategori_nama'
                )
                ->get();

            $countEmpty = $allRows->where('stok', 0)->count();
            $countLow = $allRows->filter(fn($r) => $r->stok > 0 && $r->stok < $lowThreshold)->count();
            $countOk = max($allRows->count() - $countEmpty - $countLow, 0);
            $ringkasanCounts = ['ok' => $countOk, 'low' => $countLow, 'empty' => $countEmpty];

            $barangHabis = $allRows->where('stok', 0)->map(fn($i) => (object) [
                'kode' => $i->kode_barang,
                'nama' => $i->nama_barang,
                'satuan' => $i->satuan,
                'stok_tersedia' => 0,
                'kategori' => (object) ['nama' => $i->kategori_nama],
                'harga' => $i->harga, // di mode gudang bakal null, gapapa, nanti di Blade ditampilkan "-"
            ])->values();

            // Ambil data barang masuk dari transaksi_distribusi dengan status dan harga dari pb_stok
            // Ambil data barang masuk dari transaksi_distribusi dengan status dan harga
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
                    DB::raw("COALESCE(transaksi_distribusi.harga, 0) as harga"),
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
                            'stok_bagian.stok as stok_tersedia',
                            'stok_bagian.harga'
                        )
                        ->orderBy('barang.nama_barang')
                        ->get()
                        ->map(fn($i) => (object) [
                            'kode' => $i->kode,
                            'nama' => $i->nama,
                            'satuan' => $i->satuan,
                            'stok_tersedia' => $i->stok_tersedia,
                            'id_kategori' => $k->id,
                            'harga' => $i->harga ?? 0,
                        ]);

                    return (object) [
                        'id' => $k->id,
                        'nama' => $k->nama,
                        'barang' => $items,
                        'gudang' => (object) ['nama' => 'Bagian ' . $bagianUser->nama],
                    ];
                });

            // Spoof gudang untuk blade & kirim $bagian (koleksi) ke view
            $selectedGudang = (object) ['id' => null, 'nama' => 'Bagian ' . $bagianUser->nama];
            $bagian = collect([$bagianUser]);

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
                if ($request->filled('kode'))
                    $q->where('barang.kode_barang', 'like', "%{$request->kode}%");
                if ($request->filled('stok_min'))
                    $q->where('stok_bagian.stok', '>=', (int) $request->stok_min);
                if ($request->filled('stok_max'))
                    $q->where('stok_bagian.stok', '<=', (int) $request->stok_max);
                if ($request->filled('kategori_id'))
                    $q->where('barang.id_kategori', $request->kategori_id);
                if ($request->filled('satuan'))
                    $q->where('barang.satuan', $request->satuan);

                $barang = $q->select(
                    'barang.kode_barang as kode',
                    'barang.nama_barang as nama',
                    'barang.satuan',
                    'stok_bagian.stok as stok_tersedia',
                    'kategori.nama as kategori_nama',
                    'barang.id_kategori'
                )->orderBy('barang.nama_barang')->get()
                    ->map(fn($i) => (object) [
                        'id' => null,
                        'kode' => $i->kode,
                        'nama' => $i->nama,
                        'satuan' => $i->satuan,
                        'stok_tersedia' => $i->stok_tersedia,
                        'kategori' => (object) ['nama' => $i->kategori_nama]
                    ]);
            }

            // Hitung ringkasan
            // Hitung ringkasan
            $lowThreshold = 10;
            $allRows = DB::table('stok_bagian')
                ->join('barang', 'stok_bagian.kode_barang', '=', 'barang.kode_barang')
                ->join('kategori', 'barang.id_kategori', '=', 'kategori.id')
                ->where('stok_bagian.bagian_id', $bagianUser->id)
                ->select(
                    'stok_bagian.stok',
                    'stok_bagian.harga',
                    'barang.kode_barang',
                    'barang.nama_barang',
                    'barang.satuan',
                    'kategori.nama as kategori_nama'
                )
                ->get();

            $countEmpty = $allRows->where('stok', 0)->count();
            $countLow = $allRows->filter(fn($r) => $r->stok > 0 && $r->stok < $lowThreshold)->count();
            $countOk = max($allRows->count() - $countEmpty - $countLow, 0);
            $ringkasanCounts = ['ok' => $countOk, 'low' => $countLow, 'empty' => $countEmpty];

            $barangHabis = $allRows->where('stok', 0)->map(fn($i) => (object) [
                'kode' => $i->kode_barang,
                'nama' => $i->nama_barang,
                'satuan' => $i->satuan,
                'stok_tersedia' => 0,
                'kategori' => (object) ['nama' => $i->kategori_nama],
                'harga' => $i->harga, // ini yang bakal beda per-batch
            ])->values();

            // Ambil data barang masuk dari transaksi_distribusi untuk bagian dengan status
            $barangMasuk = DB::table('transaksi_distribusi')
                ->join('barang', 'transaksi_distribusi.kode_barang', '=', 'barang.kode_barang')
                ->where('transaksi_distribusi.bagian_id', $bagianUser->id)
                ->orderBy('transaksi_distribusi.created_at', 'desc')
                ->select(
                    'transaksi_distribusi.id',
                    'transaksi_distribusi.created_at as tanggal',
                    'barang.kode_barang',
                    'barang.nama_barang',
                    'transaksi_distribusi.jumlah',
                    'barang.satuan',
                    'transaksi_distribusi.keterangan',
                    'transaksi_distribusi.bukti',
                    DB::raw("COALESCE(transaksi_distribusi.harga, 0) as harga"),
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
    /**
     * Proses barang keluar dengan FIFO untuk multiple harga
     */
    /**
     * Proses barang keluar dengan pilihan harga spesifik
     */
    public function barangKeluar(Request $request, $kode_barang)
    {
        Log::info('=== BARANG KELUAR REQUEST ===', ['kode_barang' => $kode_barang, 'all' => $request->all()]);

        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'nama_penerima' => 'required|string|max:255',
            'tanggal' => 'nullable|date',
            'bagian_id' => 'nullable|exists:bagian,id',
            'keterangan' => 'nullable|string',
            'bukti' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
            'harga_dipilih' => 'nullable|numeric|min:0',
        ]);

        // ✅ DEBUG: Cek apakah harga_dipilih diterima
        Log::info('Request validation passed', [
            'harga_dipilih' => $validated['harga_dipilih'] ?? 'TIDAK ADA',
            'all_request' => $request->all()
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
            $file = $request->file('bukti');
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $buktiPath = $file->storeAs('bukti-barang-keluar', $fileName, 'public');
        }

        DB::beginTransaction();
        try {
            $jumlahDiminta = (int) $validated['jumlah'];
            $tanggal = $validated['tanggal'] ?? now()->toDateString();
            $hargaDipilih = $validated['harga_dipilih'] ?? null;

            $dataToInsert = [
                'kode_barang' => $kode_barang,
                'user_id' => $user->id,
                'bagian_id' => $bagianId,
                'nama_penerima' => $validated['nama_penerima'],
                'jumlah' => $jumlahDiminta,
                'tanggal' => $tanggal,
                'keterangan' => $validated['keterangan'] ?? null,
                'bukti' => $buktiPath,
            ];

            $sisaStok = null;

            // MODE GUDANG (pj_stok)
            if ($user->gudang_id && Schema::hasTable('pj_stok')) {
                $totalStok = PjStok::where('kode_barang', $kode_barang)
                    ->where('id_gudang', $user->gudang_id)
                    ->sum('stok');

                if ($totalStok < $jumlahDiminta) {
                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Tidak Cukup',
                        'message' => "Stok tersedia: {$totalStok}, diminta: {$jumlahDiminta}"
                    ]);
                }

                $stokBatches = PjStok::where('kode_barang', $kode_barang)
                    ->where('id_gudang', $user->gudang_id)
                    ->where('stok', '>', 0)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                if ($stokBatches->isEmpty()) {
                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Tidak Ditemukan',
                        'message' => 'Barang tidak ada di gudang Anda.'
                    ]);
                }

                $sisaJumlah = $jumlahDiminta;
                foreach ($stokBatches as $batch) {
                    if ($sisaJumlah <= 0)
                        break;

                    if ($batch->stok >= $sisaJumlah) {
                        $batch->decrement('stok', $sisaJumlah);
                        Log::info("Mengurangi batch ID {$batch->id}: {$sisaJumlah} unit");
                        $sisaJumlah = 0;
                    } else {
                        $sisaJumlah -= $batch->stok;
                        Log::info("Menghabiskan batch ID {$batch->id}: {$batch->stok} unit");
                        $batch->update(['stok' => 0]);
                    }
                }

                $sisaStok = PjStok::where('kode_barang', $kode_barang)
                    ->where('id_gudang', $user->gudang_id)
                    ->sum('stok');

                if (Schema::hasColumn('transaksi_barang_keluar', 'id_gudang')) {
                    $dataToInsert['id_gudang'] = $user->gudang_id;
                }
            }
            // MODE BAGIAN (stok_bagian) - ✅ FIX: HANYA ambil batch yang dipilih
            else {
                Log::info('MODE BAGIAN - Barang Keluar', [
                    'bagian_id' => $bagianId,
                    'harga_dipilih' => $hargaDipilih
                ]);

                // ✅ FIX: Jika user pilih harga tertentu, HANYA proses batch dengan harga tersebut
                $queryBatch = StokBagian::where('kode_barang', $kode_barang)
                    ->where('bagian_id', $bagianId)
                    ->where('stok', '>', 0);

                if ($hargaDipilih !== null) {
                    // ✅ KUNCI: Filter HANYA batch dengan harga yang dipilih
                    $queryBatch->where('harga', $hargaDipilih);
                }

                // Ambil batch (jika ada multiple dengan harga sama, pakai FIFO)
                $stokBatches = $queryBatch
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                Log::info('Batch ditemukan', [
                    'jumlah' => $stokBatches->count(),
                    'batch_details' => $stokBatches->map(fn($b) => [
                        'id' => $b->id,
                        'harga' => $b->harga,
                        'stok' => $b->stok,
                        'batch_number' => $b->batch_number
                    ])->toArray()
                ]);

                if ($stokBatches->isEmpty()) {
                    $pesan = $hargaDipilih !== null
                        ? "Barang dengan harga Rp " . number_format($hargaDipilih, 0, ',', '.') . " tidak ditemukan atau stok habis."
                        : "Stok barang tidak ditemukan.";

                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Tidak Ditemukan',
                        'message' => $pesan
                    ]);
                }

                // Cek total stok tersedia dari batch yang dipilih
                $totalStokTersedia = $stokBatches->sum('stok');
                if ($totalStokTersedia < $jumlahDiminta) {
                    $pesan = $hargaDipilih !== null
                        ? "Stok dengan harga Rp " . number_format($hargaDipilih, 0, ',', '.') . " hanya: {$totalStokTersedia}, diminta: {$jumlahDiminta}"
                        : "Stok tersedia: {$totalStokTersedia}, diminta: {$jumlahDiminta}";

                    return back()->with('toast', [
                        'type' => 'error',
                        'title' => 'Stok Tidak Cukup',
                        'message' => $pesan
                    ]);
                }

                // ✅ PROSES: Kurangi stok dari batch yang dipilih (FIFO jika multiple)
                $sisaJumlah = $jumlahDiminta;
                $totalHarga = 0;
                $detailBatch = [];

                foreach ($stokBatches as $batch) {
                    if ($sisaJumlah <= 0)
                        break;

                    $jumlahDiambil = min($sisaJumlah, $batch->stok);

                    $totalHarga += ($jumlahDiambil * $batch->harga);
                    $detailBatch[] = [
                        'batch_id' => $batch->id,
                        'batch_number' => $batch->batch_number,
                        'harga' => $batch->harga,
                        'jumlah' => $jumlahDiambil
                    ];

                    $batch->decrement('stok', $jumlahDiambil);

                    Log::info("✅ Mengurangi batch", [
                        'batch_id' => $batch->id,
                        'harga' => $batch->harga,
                        'jumlah_diambil' => $jumlahDiambil,
                        'sisa_stok_batch' => $batch->fresh()->stok
                    ]);

                    $sisaJumlah -= $jumlahDiambil;
                }

                // Simpan detail untuk tracking
                $hargaRataRata = $jumlahDiminta > 0 ? $totalHarga / $jumlahDiminta : 0;
                $dataToInsert['harga_satuan'] = $hargaRataRata;
                $dataToInsert['total_harga'] = $totalHarga;
                $dataToInsert['detail_batch'] = json_encode($detailBatch);

                // Total sisa stok (semua batch)
                $sisaStok = StokBagian::where('kode_barang', $kode_barang)
                    ->where('bagian_id', $bagianId)
                    ->sum('stok');

                Log::info("Summary Barang Keluar", [
                    'total_harga' => $totalHarga,
                    'harga_rata_rata' => $hargaRataRata,
                    'detail_batch' => $detailBatch,
                    'sisa_stok_total' => $sisaStok
                ]);
            }

            TransaksiBarangKeluar::create($dataToInsert);

            DB::commit();

            $pesanSukses = "Barang keluar berhasil. Sisa stok: {$sisaStok}";
            if ($hargaDipilih !== null) {
                $pesanSukses .= " (Harga: Rp " . number_format($hargaDipilih, 0, ',', '.') . ")";
            }

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => $pesanSukses
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Barang keluar gagal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Gagal memproses barang keluar: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * ✅ TAMBAHKAN METHOD INI DI DataKeseluruhan.php
     * Kembalikan barang dari stok_bagian ke pb_stok
     */
    public function kembalikanKePbStok(Request $request, $kode_barang)
    {
        Log::info('=== KEMBALIKAN KE PB STOK ===', [
            'kode_barang' => $kode_barang,
            'request' => $request->all()
        ]);

        $validated = $request->validate([
            'jumlah' => 'required|integer|min:1',
            'harga' => 'nullable|numeric|min:0',
            'keterangan' => 'nullable|string|max:500',
            'bukti' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ]);

        $user = Auth::user();

        // ✅ Hanya bisa dilakukan oleh user dengan bagian_id (PJ Bagian)
        if (!$user->bagian_id) {
            return back()->with('toast', [
                'type' => 'error',
                'title' => 'Akses Ditolak',
                'message' => 'Hanya PJ Bagian yang dapat mengembalikan barang ke PB Stok.'
            ]);
        }

        $bagianId = $user->bagian_id;
        $jumlahKembalikan = (int) $validated['jumlah'];
        $hargaBarang = $validated['harga'] ?? null;

        // Upload bukti jika ada
        $buktiPath = null;
        if ($request->hasFile('bukti')) {
            $file = $request->file('bukti');
            $fileName = time() . '_kembalikan_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $buktiPath = $file->storeAs('bukti-kembalikan', $fileName, 'public');
        }

        DB::beginTransaction();
        try {
            // ✅ 1. Ambil stok dari stok_bagian (batch dengan harga yang sesuai)
            $queryStok = StokBagian::where('kode_barang', $kode_barang)
                ->where('bagian_id', $bagianId)
                ->where('stok', '>', 0);

            // Jika harga spesifik, filter berdasarkan harga
            if ($hargaBarang !== null) {
                $queryStok->where('harga', $hargaBarang);
            }

            $stokBatches = $queryStok
                ->orderBy('created_at', 'asc') // FIFO
                ->lockForUpdate()
                ->get();

            if ($stokBatches->isEmpty()) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Stok Tidak Ditemukan',
                    'message' => 'Barang tidak tersedia di stok bagian Anda.'
                ]);
            }

            // Cek total stok tersedia
            $totalStokTersedia = $stokBatches->sum('stok');
            if ($totalStokTersedia < $jumlahKembalikan) {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Stok Tidak Cukup',
                    'message' => "Stok tersedia: {$totalStokTersedia}, diminta: {$jumlahKembalikan}"
                ]);
            }

            // ✅ 2. Kurangi stok dari stok_bagian (FIFO)
            $sisaKembalikan = $jumlahKembalikan;
            $detailBatch = [];

            foreach ($stokBatches as $batch) {
                if ($sisaKembalikan <= 0)
                    break;

                $jumlahDiambil = min($sisaKembalikan, $batch->stok);

                $detailBatch[] = [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'harga' => $batch->harga,
                    'jumlah' => $jumlahDiambil
                ];

                // Kurangi stok
                $batch->decrement('stok', $jumlahDiambil);

                Log::info('✅ Mengurangi stok_bagian', [
                    'batch_id' => $batch->id,
                    'jumlah_diambil' => $jumlahDiambil,
                    'sisa_stok' => $batch->fresh()->stok
                ]);

                $sisaKembalikan -= $jumlahDiambil;
            }

            // ✅ 3. Kembalikan ke pb_stok dengan batch yang sesuai
            // Proses per batch yang dikembalikan
            foreach ($detailBatch as $detail) {
                $hargaBatch = $detail['harga'];
                $jumlahBatch = $detail['jumlah'];

                // Cek apakah sudah ada di pb_stok dengan harga yang sama
                $pbStok = DB::table('pb_stok')
                    ->where('kode_barang', $kode_barang)
                    ->where('bagian_id', $bagianId)
                    ->where('harga', $hargaBatch)
                    ->lockForUpdate()
                    ->first();

                if ($pbStok) {
                    // Tambahkan ke batch yang sudah ada
                    DB::table('pb_stok')
                        ->where('id', $pbStok->id)
                        ->increment('stok', $jumlahBatch);

                    Log::info('✅ Stok ditambahkan ke pb_stok existing', [
                        'pb_stok_id' => $pbStok->id,
                        'jumlah_tambah' => $jumlahBatch,
                        'harga' => $hargaBatch
                    ]);
                } else {
                    // Buat batch baru di pb_stok
                    $batchNumber = 'RETURN-' . now()->format('YmdHis') . '-' . uniqid();

                    DB::table('pb_stok')->insert([
                        'kode_barang' => $kode_barang,
                        'bagian_id' => $bagianId,
                        'batch_number' => $batchNumber,
                        'stok' => $jumlahBatch,
                        'harga' => $hargaBatch,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('✅ Batch baru dibuat di pb_stok', [
                        'batch_number' => $batchNumber,
                        'jumlah' => $jumlahBatch,
                        'harga' => $hargaBatch
                    ]);
                }
            }

            // ✅ 4. Catat ke log/transaksi jika diperlukan (opsional)
            // Anda bisa menambahkan tabel transaksi_kembalikan_pb untuk audit trail

            DB::commit();

            // Get bagian name
            $bagianData = Bagian::find($bagianId);
            $namaBagian = $bagianData->nama ?? 'Unknown';

            Log::info('✅ Pengembalian ke PB Stok berhasil', [
                'kode_barang' => $kode_barang,
                'jumlah' => $jumlahKembalikan,
                'bagian' => $namaBagian
            ]);

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => "Barang berhasil dikembalikan ke PB Stok {$namaBagian}. Jumlah: {$jumlahKembalikan}"
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Kembalikan ke PB Stok gagal', [
                'error' => $e->getMessage(),
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
            $hargaBarang = $transaksi->harga ?? 0;

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
                Log::info('Processing MODE BAGIAN', [
                    'kode_barang' => $kodeBarang,
                    'bagian_id' => $user->bagian_id,
                    'harga' => $hargaBarang,
                    'jumlah' => $jumlah
                ]);

                // Cek apakah sudah ada record dengan harga yang sama
                $stokBagian = StokBagian::where('kode_barang', $kodeBarang)
                    ->where('bagian_id', $user->bagian_id)
                    ->where('harga', $hargaBarang)
                    ->lockForUpdate()
                    ->first();

                if ($stokBagian) {
                    // JIKA SUDAH ADA dengan harga yang sama: TAMBAH stok
                    Log::info('Stok dengan harga sama ditemukan, menambah stok', [
                        'stok_bagian_id' => $stokBagian->id,
                        'stok_lama' => $stokBagian->stok,
                        'jumlah_tambah' => $jumlah
                    ]);

                    $stokBagian->increment('stok', $jumlah);

                    Log::info('Stok berhasil ditambahkan', [
                        'stok_baru' => $stokBagian->fresh()->stok
                    ]);
                } else {
                    // JIKA BELUM ADA dengan harga tersebut: BUAT record baru
                    Log::info('Stok dengan harga sama tidak ditemukan, membuat record baru');

                    // Generate batch number unik untuk tracking
                    $batchNumber = 'BATCH-' . now()->format('YmdHis') . '-' . $transaksi->id;

                    $newStok = StokBagian::create([
                        'kode_barang' => $kodeBarang,
                        'bagian_id' => $user->bagian_id,
                        'batch_number' => $batchNumber,
                        'stok' => $jumlah,
                        'harga' => $hargaBarang,
                    ]);

                    Log::info('Record stok baru berhasil dibuat', [
                        'stok_bagian_id' => $newStok->id,
                        'batch_number' => $batchNumber
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

            Log::info('Konfirmasi barang masuk berhasil', [
                'transaksi_id' => $id,
                'lokasi' => $namaLokasi,
                'jumlah' => $jumlah
            ]);

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => "Barang berhasil dikonfirmasi masuk ke {$namaLokasi}. Jumlah: {$jumlah}"
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Konfirmasi barang masuk gagal', [
                'transaksi_id' => $id,
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
        Log::info('=== KEMBALIKAN BARANG ===', ['transaksi_id' => $id]);

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

            // Validasi akses
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

            // Cek status konfirmasi
            $statusKonfirmasi = $transaksi->status_konfirmasi ?? 'pending';
            if ($statusKonfirmasi === 'confirmed') {
                return back()->with('toast', [
                    'type' => 'error',
                    'title' => 'Tidak Bisa Dikembalikan',
                    'message' => 'Barang yang sudah dikonfirmasi tidak bisa dikembalikan.'
                ]);
            }

            $kodeBarang = $transaksi->kode_barang;
            $jumlah = $transaksi->jumlah;
            $hargaBarang = $transaksi->harga ?? 0;
            $bagianIdTransaksi = $transaksi->bagian_id; // ✅ KUNCI: Bagian tujuan distribusi

            Log::info('Data transaksi', [
                'kode_barang' => $kodeBarang,
                'jumlah' => $jumlah,
                'harga' => $hargaBarang,
                'bagian_id' => $bagianIdTransaksi
            ]);

            // Ambil data barang
            $barang = DB::table('barang')
                ->where('kode_barang', $kodeBarang)
                ->first();

            if (!$barang) {
                throw new \Exception('Data barang tidak ditemukan.');
            }

            // ✅ FIX: Kembalikan ke pb_stok dengan bagian_id yang SAMA seperti saat distribusi

            // Cek apakah sudah ada di pb_stok dengan bagian_id dan harga yang sama
            $pbStok = DB::table('pb_stok')
                ->where('kode_barang', $kodeBarang)
                ->where('bagian_id', $bagianIdTransaksi) // ✅ KUNCI: Bagian yang sama
                ->where('harga', $hargaBarang) // ✅ KUNCI: Harga yang sama
                ->lockForUpdate()
                ->first();

            if ($pbStok) {
                // ✅ Jika ada, tambahkan stok ke record yang sama
                DB::table('pb_stok')
                    ->where('id', $pbStok->id)
                    ->increment('stok', $jumlah);

                Log::info('✅ Stok ditambahkan ke pb_stok existing', [
                    'pb_stok_id' => $pbStok->id,
                    'stok_baru' => $pbStok->stok + $jumlah
                ]);
            } else {
                // ✅ Jika tidak ada, buat record baru di pb_stok
                DB::table('pb_stok')->insert([
                    'kode_barang' => $kodeBarang,
                    'bagian_id' => $bagianIdTransaksi, // ✅ KUNCI: Kembalikan ke bagian asal
                    'stok' => $jumlah,
                    'harga' => $hargaBarang,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info('✅ Record baru dibuat di pb_stok', [
                    'kode_barang' => $kodeBarang,
                    'bagian_id' => $bagianIdTransaksi,
                    'stok' => $jumlah,
                    'harga' => $hargaBarang
                ]);
            }

            // Ambil nama bagian untuk pesan
            $bagianData = Bagian::find($bagianIdTransaksi);
            $namaLokasi = 'PB ' . ($bagianData->nama ?? 'Unknown');

            // Hapus transaksi distribusi
            DB::table('transaksi_distribusi')
                ->where('id', $id)
                ->delete();

            DB::commit();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => "Barang dikembalikan ke {$namaLokasi}. Jumlah: {$jumlah}, Harga: Rp " . number_format($hargaBarang, 0, ',', '.')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Kembalikan barang gagal', [
                'error' => $e->getMessage(),
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
