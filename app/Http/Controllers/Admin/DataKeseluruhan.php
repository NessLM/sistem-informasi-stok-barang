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
        $menu = MenuHelper::adminMenu();
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
// === PERBAIKAN: Data Keseluruhan - isi ulang barang untuk gudang kecil ===
if (!$request->filled('gudang_id')) {
    $gudangUtama = Gudang::whereRaw('LOWER(nama) LIKE ?', ['%utama%'])->first();

    $kategori->each(function ($kat) use ($gudangUtama, $search) {
        $namaGudang = $kat->gudang->nama ?? '';
        $isUtama = stripos($namaGudang, 'utama') !== false;

        if ($isUtama) {
            // Gudang Utama: boleh dibiarkan (sudah eager load).
            // Opsional (rapihin): hanya tampilkan barang yang memang punya PB stok
            $barang = ($kat->barang ?? collect())->filter(fn($b) => !is_null($b->pbStok));
            $kat->setRelation('barang', $barang->values());
            return;
        }

        // Gudang kecil: ambil "kembaran" kategori di Gudang Utama (berdasar nama)
        $barang = collect();
        if ($gudangUtama) {
            $katUtama = Kategori::whereRaw('LOWER(nama) = ?', [strtolower($kat->nama)])
                ->where('gudang_id', $gudangUtama->id)
                ->first();

            if ($katUtama) {
                $barang = Barang::where('id_kategori', $katUtama->id)
                    ->when($search, function ($q) use ($search) {
                        $q->where(function ($qq) use ($search) {
                            $qq->where('nama_barang', 'like', "%{$search}%")
                               ->orWhere('kode_barang', 'like', "%{$search}%");
                        });
                    })
                    ->with([
                        'pbStok',
                        // PJ stok khusus gudang pemilik kategori kecil ini:
                        'pjStok' => function ($q) use ($kat) {
                            $q->where('id_gudang', $kat->gudang_id);
                        },
                        'kategori.gudang',
                    ])
                    ->orderBy('nama_barang')
                    ->get();
            }
        }

        // Timpa relasi agar Blade tetap pakai $k->barang
        $kat->setRelation('barang', $barang);
    });
}

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
            $g = Gudang::find($request->gudang_id);
            $isUtama = $g && stripos($g->nama, 'utama') !== false;
        
            if ($isUtama) {
                // Gudang Utama → pakai PB stok dan kategori memang milik Gudang Utama tsb
                $query->whereHas('pbStok')
                      ->whereHas('kategori', function ($q) use ($request) {
                          $q->where('gudang_id', $request->gudang_id);
                      });
            } else {
                // Gudang lain → barang yang punya PJ stok di gudang ini
                $query->whereHas('pjStok', function ($q) use ($request) {
                    $q->where('id_gudang', $request->gudang_id);
                });
                // catatan: jangan filter kategori by gudang untuk gudang non-utama
            }
        }
        

        // Filter tambahan
        if ($request->filled('kode')) {
            $query->where('kode_barang', 'like', "%{$request->kode}%");
        }

        // Filter stok berdasarkan PJ Stok
       // Filter stok: gabungkan PB & PJ kalau TIDAK memilih gudang
if ($request->filled('stok_min') || $request->filled('stok_max')) {
    $min = (int) ($request->stok_min ?? 0);
    $max = (int) ($request->stok_max ?? PHP_INT_MAX);

    if ($request->filled('gudang_id')) {
        $g = Gudang::find($request->gudang_id);
        $isUtama = $g && stripos($g->nama, 'utama') !== false;

        if ($isUtama) {
            // Hanya PB stok untuk Gudang Utama
            $query->whereHas('pbStok', function ($q) use ($min, $max) {
                $q->whereBetween('stok', [$min, $max]);
            })->whereHas('kategori', function ($q) use ($request) {
                $q->where('gudang_id', $request->gudang_id);
            });
        } else {
            // Hanya PJ stok untuk gudang non-utama tsb
            $query->whereHas('pjStok', function ($q) use ($min, $max, $request) {
                $q->where('id_gudang', $request->gudang_id)
                  ->whereBetween('stok', [$min, $max]);
            });
        }
    } else {
        // Data Keseluruhan → PB ATAU PJ masuk filter
        $query->where(function ($q) use ($min, $max) {
            $q->whereHas('pbStok', function ($qq) use ($min, $max) {
                $qq->whereBetween('stok', [$min, $max]);
            })->orWhereHas('pjStok', function ($qq) use ($min, $max) {
                $qq->whereBetween('stok', [$min, $max]);
            });
        });
    }
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
// Flatten per gudang untuk Data Keseluruhan (tanpa gudang_id)
$hasilCari = collect();
if (!$request->filled('gudang_id') && $barang->isNotEmpty()) {
    $hasilCari = $barang->flatMap(function ($b) {
        $rows = collect();

        // Baris PB (Gudang Utama)
        if ($b->pbStok) {
            $rows->push((object)[
                'b'        => $b, // referensi model Barang
                'stok'     => (int) ($b->pbStok->stok ?? 0),
                'gudang'   => $b->kategori->gudang->nama ?? 'Gudang Utama',
                'kategori' => $b->kategori->nama ?? '-',
            ]);
        }

        // Baris PJ (setiap gudang non-utama yang punya stok)
        foreach ($b->pjStok as $pj) {
            $rows->push((object)[
                'b'        => $b,
                'stok'     => (int) ($pj->stok ?? 0),
                'gudang'   => $pj->gudang->nama ?? '-',
                'kategori' => $b->kategori->nama ?? '-',
            ]);
        }

        return $rows;
    });
}

return view('staff.admin.datakeseluruhan', compact(
    'kategori', 'barang', 'menu', 'gudang', 'selectedGudang', 'hasilCari'
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


$isGudangUtama = stripos($selectedGudang->nama, 'utama') !== false;

if ($isGudangUtama) {
    $kategori = Kategori::with([
        'barang' => fn($q) => $q->whereHas('pbStok'),
        'barang.pbStok', 'barang.pjStok', 'gudang'
    ])->where('gudang_id', $selectedGudang->id)->get();
} else {
    // Ambil semua kategori milik gudang kecil ini
    $kategori = Kategori::with('gudang')
        ->where('gudang_id', $selectedGudang->id)
        ->get();

    // Cari Gudang Utama
    $gudangUtama = Gudang::whereRaw('LOWER(nama) LIKE ?', ['%utama%'])->first();

    // Untuk tiap kategori di gudang kecil, isi relasi 'barang' dari kategori kembaran di Gudang Utama
    $kategori->each(function ($kat) use ($selectedGudang, $gudangUtama) {
        $barang = collect();

        if ($gudangUtama) {
            // Temukan kategori "kembaran" di Gudang Utama (berdasar nama)
            $katUtama = Kategori::whereRaw('LOWER(nama) = ?', [strtolower($kat->nama)])
                ->where('gudang_id', $gudangUtama->id)
                ->first();

            if ($katUtama) {
                // Ambil SEMUA barang pada kategori Gudang Utama tsb,
                // plus attach pjStok untuk gudang kecil (kalau ada). Jika tidak ada → stok dianggap 0 di Blade.
                $barang = Barang::where('id_kategori', $katUtama->id)
                    ->with([
                        'pbStok',
                        'pjStok' => function ($q) use ($selectedGudang) {
                            $q->where('id_gudang', $selectedGudang->id);
                        },
                        'kategori.gudang',
                    ])
                    ->orderBy('nama_barang')
                    ->get();
            }
        }

        // Timpa relasi agar Blade tetap pakai $k->barang
        $kat->setRelation('barang', $barang);
        
    });
}



        // Inisialisasi collection barang kosong
        $barang = collect();

        // Jika ada filter/search, ambil data barang yang sesuai
        if ($this->hasAnyFilter($request)) {
            $barang = $this->getFilteredBarang($request, $selectedGudang->id);
        }

        return view('staff.admin.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'gudang',
            'selectedGudang'
        ))->with([
            'hasilCari' => collect(), // ← tambahkan ini
        ]);
        
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
            'nama' => 'required|string|max:255',
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
            'kode_barang' => 'required|string|max:255|unique:barang,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'harga_barang' => 'nullable|numeric|min:0',
            'satuan' => 'nullable|string|max:50',
            'id_kategori' => 'required|exists:kategori,id',
        ]);

        // Buat barang baru
        $barang = Barang::create([
            'kode_barang' => $request->kode_barang,
            'nama_barang' => $request->nama_barang,
            'harga_barang' => $request->harga_barang ?? 0,
            'satuan' => $request->satuan,
            'id_kategori' => $request->id_kategori,
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
            'kode_barang' => 'required|string|max:255|unique:barang,kode_barang,' . $barang->kode_barang . ',kode_barang',
            'nama_barang' => 'required|string|max:255',
            'harga_barang' => 'nullable|numeric|min:0',
            'satuan' => 'nullable|string|max:50',
            'id_kategori' => 'required|exists:kategori,id',
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
                    'kode_barang' => $request->kode_barang,
                    'nama_barang' => $request->nama_barang,
                    'harga_barang' => $request->harga_barang ?? 0,
                    'satuan' => $request->satuan,
                    'id_kategori' => $request->id_kategori,
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
                    'nama_barang' => $request->nama_barang,
                    'harga_barang' => $request->harga_barang ?? 0,
                    'satuan' => $request->satuan,
                    'id_kategori' => $request->id_kategori,
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

    public function destroyBarang($kode_barang)
    {
        DB::beginTransaction();
        try {
            // Cari barang berdasarkan kode_barang (primary key)
            $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();

            $namaBarang = $barang->nama_barang;

            // Hapus semua relasi transaksi terlebih dahulu
            $barang->transaksiBarangMasuk()->delete();
            $barang->transaksiDistribusi()->delete();
            $barang->transaksiBarangKeluar()->delete();

            // Hapus stok terkait
            $barang->pbStok()->delete();
            $barang->pjStok()->delete();

            // Hapus barang
            $barang->delete();

            DB::commit();

            return redirect()->route('admin.datakeseluruhan.index')
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => "Barang {$namaBarang} berhasil dihapus."
                ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index')
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Barang tidak ditemukan.'
                ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index')
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Gagal menghapus barang: ' . $e->getMessage()
                ]);
        }
    }


    public function destroyKategori($id)
    {
        DB::beginTransaction();

        try {
            $kategori = Kategori::findOrFail($id);
            $namaKategori = $kategori->nama;
            $gudangSaat = $kategori->gudang ?? null;
            $isGudangUtama = $gudangSaat && stripos(strtolower($gudangSaat->nama), 'utama') !== false;

            $this->deleteKategoriWithBarang($kategori);
            $deletedCount = 1;

            // Jika bukan gudang utama → hapus juga di Gudang Utama
            if ($gudangSaat && !$isGudangUtama) {
                $gudangUtama = Gudang::whereRaw('LOWER(nama) LIKE ?', ['%utama%'])->first();

                if ($gudangUtama) {
                    $kategoriUtama = Kategori::whereRaw('LOWER(nama) = ?', [strtolower($namaKategori)])
                        ->where('gudang_id', $gudangUtama->id)
                        ->first();

                    if ($kategoriUtama) {
                        $this->deleteKategoriWithBarang($kategoriUtama);
                        $deletedCount++;
                    }
                }
            }
            // Jika dari Gudang Utama → hapus juga semua kategori dengan nama sama di gudang lain
            elseif ($isGudangUtama) {
                $allGudangs = Gudang::whereRaw('LOWER(nama) NOT LIKE ?', ['%utama%'])->get();

                foreach ($allGudangs as $gudang) {
                    $kategoriGudang = Kategori::whereRaw('LOWER(nama) = ?', [strtolower($namaKategori)])
                        ->where('gudang_id', $gudang->id)
                        ->first();

                    if ($kategoriGudang) {
                        $this->deleteKategoriWithBarang($kategoriGudang);
                        $deletedCount++;
                    }
                }
            }

            DB::commit();

            $message = "Kategori '{$namaKategori}' berhasil dihapus";

            if ($deletedCount > 1) {
                if ($isGudangUtama) {
                    $others = $deletedCount - 1; // lakukan pengurangan di luar string
                    $message .= " dari Gudang Utama dan " . $others . " gudang lainnya.";
                } else {
                    $message .= " dari gudang ini dan Gudang Utama.";
                }
            } else {
                $message .= ".";
            }

            return redirect()->route('admin.datakeseluruhan.index')->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => $message,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index')->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Kategori tidak ditemukan.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index')->with('toast', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Gagal menghapus kategori: ' . $e->getMessage(),
            ]);
        }
    }


    /**
     * Helper method untuk menghapus kategori beserta semua barang dan transaksinya
     */
    private function deleteKategoriWithBarang(Kategori $kategori)
    {
        // Hapus stok untuk semua barang
        foreach ($kategori->barang as $barang) {
            // Hapus transaksi terlebih dahulu (foreign key constraint)
            if (method_exists($barang, 'transaksiBarangMasuk')) {
                $barang->transaksiBarangMasuk()->delete();
            }
            if (method_exists($barang, 'transaksiDistribusi')) {
                $barang->transaksiDistribusi()->delete();
            }
            if (method_exists($barang, 'transaksiBarangKeluar')) {
                $barang->transaksiBarangKeluar()->delete();
            }

            // Hapus stok PB dan PJ
            $barang->pbStok()->delete();
            $barang->pjStok()->delete();
        }

        // Hapus semua barang
        $kategori->barang()->delete();

        // Hapus kategori
        $kategori->delete();
    }

    /**
     * API untuk search suggestions dengan filter gudang
     */
    public function searchSuggestions(Request $request)
{
    $search   = $request->get('q', '');
    $gudangId = $request->get('gudang_id');

    if (mb_strlen($search) < 2) {
        return response()->json([]);
    }

    try {
        // Wajib: muat gudang pada pjStok biar bisa ambil nama gudang
        $barangQuery = Barang::with(['kategori.gudang', 'pbStok', 'pjStok.gudang'])
            ->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%");
            });

        // === MODE GUDANG TERTENTU (ADA gudang_id) → TETAP SPT BIASA ===
        if ($gudangId) {
            $g = Gudang::find($gudangId);
            $isUtama = $g && stripos($g->nama, 'utama') !== false;

            if ($isUtama) {
                $barangQuery->whereHas('pbStok')
                            ->whereHas('kategori', function ($q) use ($gudangId) {
                                $q->where('gudang_id', $gudangId);
                            });
            } else {
                $barangQuery->whereHas('pjStok', function ($q) use ($gudangId) {
                    $q->where('id_gudang', $gudangId);
                });
            }

            $suggestions = $barangQuery
                ->select('kode_barang', 'nama_barang', 'id_kategori')
                ->orderBy('nama_barang')
                ->limit(8)
                ->get()
                ->map(function ($barang) use ($gudangId, $isUtama, $g) {
                    $stok = 0;
                    $gudangNama = $barang->kategori->gudang->nama ?? '-';

                    if ($isUtama) {
                        $stok = (int) ($barang->pbStok->stok ?? 0);
                        $gudangNama = $g->nama;
                    } else {
                        $pjStok = $barang->pjStok()->where('id_gudang', $gudangId)->first();
                        $stok = (int) ($pjStok->stok ?? 0);
                        $gudangNama = $g->nama;
                    }

                    return [
                        'kode'         => $barang->kode_barang,
                        'nama'         => $barang->nama_barang,
                        'stok'         => $stok,
                        'kategori'     => $barang->kategori->nama ?? '-',
                        'gudang'       => $gudangNama,
                        'display'      => $barang->nama_barang . ' (' . $barang->kode_barang . ')',
                        'stock_status' => $stok === 0 ? 'empty' : ($stok < 10 ? 'low' : 'normal'),
                    ];
                });

            return response()->json($suggestions);
        }

        // === MODE DATA KESELURUHAN (TANPA gudang_id) → FLATTEN PER GUDANG ===
        $barangList = $barangQuery
            ->select('kode_barang', 'nama_barang', 'id_kategori')
            ->orderBy('nama_barang')
            ->limit(10) // ambil 10 barang; hasil flatten bisa > 10 baris
            ->get();

        $rows = collect();

        foreach ($barangList as $barang) {
            // PB (Gudang Utama) – jika ada
            if ($barang->pbStok) {
                $stokPB = (int) ($barang->pbStok->stok ?? 0);
                $rows->push([
                    'kode'         => $barang->kode_barang,
                    'nama'         => $barang->nama_barang,
                    'stok'         => $stokPB,
                    'kategori'     => $barang->kategori->nama ?? '-',
                    'gudang'       => $barang->kategori->gudang->nama ?? 'Gudang Utama',
                    'display'      => $barang->nama_barang . ' (' . $barang->kode_barang . ')',
                    'stock_status' => $stokPB === 0 ? 'empty' : ($stokPB < 10 ? 'low' : 'normal'),
                ]);
            }

            // Setiap gudang kecil (PJ)
            foreach ($barang->pjStok as $pj) {
                $stokPJ = (int) ($pj->stok ?? 0);
                $rows->push([
                    'kode'         => $barang->kode_barang,
                    'nama'         => $barang->nama_barang,
                    'stok'         => $stokPJ,
                    'kategori'     => $barang->kategori->nama ?? '-',
                    'gudang'       => $pj->gudang->nama ?? '-',
                    'display'      => $barang->nama_barang . ' (' . $barang->kode_barang . ')',
                    'stock_status' => $stokPJ === 0 ? 'empty' : ($stokPJ < 10 ? 'low' : 'normal'),
                ]);
            }
        }

        return response()->json($rows->take(12)->values());

    } catch (\Throwable $e) {
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
            'search',
            'kode',
            'stok_min',
            'stok_max',
            'kategori_id',
            'gudang_id',
            'satuan',
            'harga_min',
            'harga_max'
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
    /**
     * Ambil data barang berdasarkan filter dengan stok yang benar
     */
    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with([
            'kategori.gudang',
            'pbStok',
            'pjStok' => function ($q) use ($gudangId) {
                if ($gudangId) {
                    $q->where('id_gudang', $gudangId);
                }
                $q->with('gudang');
            }
        ]);

        // PERBAIKAN: Filter berdasarkan gudang - hanya barang yang punya stok di gudang tersebut
        if ($gudangId) {
            $gudangObj = Gudang::find($gudangId);
            $isGudangUtama = $gudangObj && stripos($gudangObj->nama, 'utama') !== false;

            if ($isGudangUtama) {
                // Untuk Gudang Utama: hanya barang yang punya PB Stok
                $query->whereHas('pbStok');
                // Filter kategori yang gudang_id nya Gudang Utama
                $query->whereHas('kategori', function ($q) use ($gudangId) {
                    $q->where('gudang_id', $gudangId);
                });
            } else {
                // Untuk gudang lain: hanya barang yang punya PJ Stok di gudang ini
                $query->whereHas('pjStok', function ($q) use ($gudangId) {
                    $q->where('id_gudang', $gudangId);
                });
                // JANGAN filter kategori by gudang_id karena barang bisa dari kategori Gudang Utama
                // tapi punya stok di gudang lain via pj_stok
            }
        }

        // Filter search (nama atau kode)
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

        // Filter stok
        if ($request->filled('stok_min') || $request->filled('stok_max')) {
            if ($gudangId) {
                $gudangObj = Gudang::find($gudangId);
                $isGudangUtama = $gudangObj && stripos($gudangObj->nama, 'utama') !== false;

                if ($isGudangUtama) {
                    $query->whereHas('pbStok', function ($q) use ($request) {
                        if ($request->filled('stok_min')) {
                            $q->where('stok', '>=', (int) $request->stok_min);
                        }
                        if ($request->filled('stok_max')) {
                            $q->where('stok', '<=', (int) $request->stok_max);
                        }
                    });
                } else {
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
            }
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