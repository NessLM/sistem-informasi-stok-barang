<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Bagian;
use App\Models\PbStok;
use App\Models\StokBagian;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\DB;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu = MenuHelper::adminMenu();
        $search = $request->input('search');

        // Tentukan tab aktif
        $activeTab = $request->input('tab', 'data-keseluruhan');

        // Ambil kategori + relasi barang
        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                }
                $q->with(['pbStok.bagian', 'stokBagian.bagian']);
            }
        ]);

        $kategori = $kategoriQuery->get();
        $bagian = Bagian::all();
        $hasilCari = collect();

        // Jika ada filter/search (hanya untuk tab data-keseluruhan)
        if ($activeTab === 'data-keseluruhan' && $this->hasAnyFilter($request)) {
            $query = Barang::with(['kategori', 'pbStok.bagian', 'stokBagian.bagian']);

            if ($search) {
                $kw = mb_strtolower($search);

                $query->where(function ($q) use ($search, $kw) {
                    // Nama & Kode
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%")

                        // + Kategori
                        ->orWhereHas('kategori', function ($qq) use ($search) {
                            $qq->where('nama', 'like', "%{$search}%");
                        })

                        // + Bagian (baik yang di PB maupun di stok_bagian)
                        ->orWhereHas('pbStok.bagian', function ($qq) use ($search) {
                            $qq->where('nama', 'like', "%{$search}%");
                        })
                        ->orWhereHas('stokBagian.bagian', function ($qq) use ($search) {
                            $qq->where('nama', 'like', "%{$search}%");
                        });

                    // + Lokasi sebagai keyword
                    if (str_contains($kw, 'gudang utama') || str_contains($kw, 'gudang') || str_contains($kw, 'pusat') || $kw === 'pb') {
                        $q->orWhereHas('pbStok', function () { /* match lokasi Gudang Utama */
                        });
                    }
                    if (str_contains($kw, 'bagian') || $kw === 'pj') {
                        $q->orWhereHas('stokBagian', function () { /* match lokasi Bagian */
                        });
                    }
                });
            }


            // Filter kategori
            if ($request->filled('kategori_id')) {
                $query->where('id_kategori', $request->kategori_id);
            }

            // Filter bagian
            if ($request->filled('bagian_id')) {
                $bagianId = $request->bagian_id;

                $query->where(function ($q) use ($bagianId) {
                    $q->whereHas('pbStok', function ($q2) use ($bagianId) {
                        $q2->where('bagian_id', $bagianId);
                    })->orWhereHas('stokBagian', function ($q2) use ($bagianId) {
                        $q2->where('bagian_id', $bagianId);
                    });
                });
            }

            // Filter stok
            if ($request->filled('stok_min') || $request->filled('stok_max')) {
                $min = (int) ($request->stok_min ?? 0);
                $max = (int) ($request->stok_max ?? PHP_INT_MAX);

                if ($request->filled('bagian_id')) {
                    $query->whereHas('stokBagian', function ($q) use ($min, $max, $request) {
                        $q->where('bagian_id', $request->bagian_id)
                            ->whereBetween('stok', [$min, $max]);
                    });
                } else {
                    $query->whereHas('pbStok', function ($q) use ($min, $max) {
                        $q->whereBetween('stok', [$min, $max]);
                    });
                }
            }

            // Filter harga
            if ($request->filled('harga_min') || $request->filled('harga_max')) {
                if ($request->filled('bagian_id')) {
                    $query->whereHas('stokBagian', function ($q) use ($request) {
                        $q->where('bagian_id', $request->bagian_id);
                        if ($request->filled('harga_min')) {
                            $q->where('harga', '>=', (float) $request->harga_min);
                        }
                        if ($request->filled('harga_max')) {
                            $q->where('harga', '<=', (float) $request->harga_max);
                        }
                    });
                } else {
                    $query->whereHas('pbStok', function ($q) use ($request) {
                        if ($request->filled('harga_min')) {
                            $q->where('harga', '>=', (float) $request->harga_min);
                        }
                        if ($request->filled('harga_max')) {
                            $q->where('harga', '<=', (float) $request->harga_max);
                        }
                    });
                }
            }

            // Filter satuan
            if ($request->filled('satuan')) {
                $query->where('satuan', $request->satuan);
            }

            $barang = $query->get();

            // ---- bantuan filter berdasar teks pencarian (bagian & lokasi) ----
            $kw = mb_strtolower($search ?? '');

            // deteksi keyword lokasi
            $matchGudangUtama = str_contains($kw, 'gudang utama') || str_contains($kw, 'gudang') || str_contains($kw, 'pusat') || $kw === 'pb';
            $matchLokasiBagian = str_contains($kw, 'bagian') || $kw === 'pj';

            // deteksi nama bagian dari teks (pakai koleksi $bagian yang sudah di-load di atas)
            $bagianIdsFromSearch = [];
            if (!empty($search)) {
                $bagianIdsFromSearch = $bagian
                    ->filter(fn($bg) => stripos($bg->nama, $search) !== false)
                    ->pluck('id')
                    ->toArray();
            }


            // Flatten hasil untuk tampilan
            $hasilCari = $barang->flatMap(function ($b) use ($request, $bagianIdsFromSearch, $matchGudangUtama, $matchLokasiBagian) {
                $rows = collect();

                if ($request->filled('bagian_id')) {
                    // MODE: ada filter bagian_id eksplisit (tetap seperti semula)
                    $bagianId = (int) $request->bagian_id;

                    $pbStok = $b->pbStok->where('bagian_id', $bagianId)->first();
                    if ($pbStok) {
                        $rows->push((object) [
                            'b'       => $b,
                            'stok'    => (int) ($pbStok->stok ?? 0),
                            'bagian'  => $pbStok->bagian->nama ?? '-',
                            'lokasi'  => 'Gudang Utama',
                            'kategori'=> $b->kategori->nama ?? '-',
                            'harga'   => $pbStok->harga ?? 0,
                        ]);
                    }

                    $stokBagian = $b->stokBagian->where('bagian_id', $bagianId)->first();
                    if ($stokBagian) {
                        $rows->push((object) [
                            'b'       => $b,
                            'stok'    => (int) ($stokBagian->stok ?? 0),
                            'bagian'  => $stokBagian->bagian->nama ?? '-',
                            'lokasi'  => 'Bagian',
                            'kategori'=> $b->kategori->nama ?? '-',
                            'harga'   => $stokBagian->harga ?? 0,
                        ]);
                    }
                } else {
                    // MODE: tidak ada bagian_id eksplisit
                    $filterByBagian = !empty($bagianIdsFromSearch);
                    $lokasiPBOnly = !$filterByBagian && $matchGudangUtama && !$matchLokasiBagian;
                    $lokasiBagianOnly = !$filterByBagian && $matchLokasiBagian && !$matchGudangUtama;

                    // PB (Gudang Utama)
                    if (!$lokasiBagianOnly) {
                        foreach ($b->pbStok as $pb) {
                            if ($filterByBagian && !in_array($pb->bagian_id, $bagianIdsFromSearch)) {
                                continue; // kalau user ketik nama bagian, tampilkan hanya bagian tsb
                            }
                            $rows->push((object) [
                                'b'       => $b,
                                'stok'    => (int) ($pb->stok ?? 0),
                                'bagian'  => $pb->bagian->nama ?? '-',
                                'lokasi'  => 'Gudang Utama',
                                'kategori'=> $b->kategori->nama ?? '-',
                                'harga'   => $pb->harga ?? 0,
                            ]);
                        }
                    }

                    // Stok Bagian
                    if (!$lokasiPBOnly) {
                        foreach ($b->stokBagian as $sb) {
                            if ($filterByBagian && !in_array($sb->bagian_id, $bagianIdsFromSearch)) {
                                continue;
                            }
                            $rows->push((object) [
                                'b'       => $b,
                                'stok'    => (int) ($sb->stok ?? 0),
                                'bagian'  => $sb->bagian->nama ?? '-',
                                'lokasi'  => 'Bagian',
                                'kategori'=> $b->kategori->nama ?? '-',
                                'harga'   => $sb->harga ?? 0,
                            ]);
                        }
                    }
                }

                return $rows;
            });

        }

        return view('staff.admin.datakeseluruhan', compact(
            'kategori',
            'menu',
            'bagian',
            'hasilCari',
            'activeTab'
        ));
    }

    private function hasAnyFilter(Request $request)
    {
        $filterFields = [
            'search',
            'stok_min',
            'stok_max',
            'kategori_id',
            'bagian_id',
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

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:kategori,nama',
        ]);

        Kategori::create([
            'nama' => $request->nama,
        ]);

        return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
            ->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Kategori berhasil ditambahkan.'
            ]);
    }

    public function storeBarang(Request $request)
    {
        $request->validate([
            'kode_barang' => 'required|string|max:255|unique:barang,kode_barang',
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'id_kategori' => 'required|exists:kategori,id',
        ]);

        Barang::create([
            'kode_barang' => $request->kode_barang,
            'nama_barang' => $request->nama_barang,
            'satuan' => $request->satuan,
            'id_kategori' => $request->id_kategori,
        ]);

        return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
            ->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Barang berhasil ditambahkan.'
            ]);
    }

    public function updateBarang(Request $request, $kode)
    {
        $barang = Barang::where('kode_barang', $kode)->firstOrFail();

        $request->validate([
            'kode_barang' => 'required|string|max:255|unique:barang,kode_barang,' . $barang->kode_barang . ',kode_barang',
            'nama_barang' => 'required|string|max:255',
            'satuan' => 'nullable|string|max:50',
            'id_kategori' => 'required|exists:kategori,id',
        ]);

        DB::beginTransaction();
        try {
            $oldKode = $barang->kode_barang;
            $newKode = $request->kode_barang;

            // Update data barang (tanpa kode barang dulu)
            $barang->nama_barang = $request->nama_barang;
            $barang->satuan = $request->satuan;
            $barang->id_kategori = $request->id_kategori;

            // Jika kode barang berubah
            if ($oldKode !== $newKode) {
                // Update foreign key di tabel terkait menggunakan raw query
                DB::statement('UPDATE pb_stok SET kode_barang = ? WHERE kode_barang = ?', [$newKode, $oldKode]);
                DB::statement('UPDATE stok_bagian SET kode_barang = ? WHERE kode_barang = ?', [$newKode, $oldKode]);

                // Sekarang update kode_barang di tabel barang
                $barang->kode_barang = $newKode;
            }

            $barang->save();

            DB::commit();

            return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => 'Barang berhasil diperbarui.'
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Gagal memperbarui barang: ' . $e->getMessage()
                ]);
        }
    }

    public function destroyBarang($kode_barang)
    {
        DB::beginTransaction();
        try {
            $barang = Barang::where('kode_barang', $kode_barang)->firstOrFail();
            $namaBarang = $barang->nama_barang;

            // Hapus semua relasi
            $barang->pbStok()->delete();
            $barang->stokBagian()->delete();
            $barang->delete();

            DB::commit();

            return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => "Barang {$namaBarang} berhasil dihapus."
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
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

            // Hapus semua barang dalam kategori
            foreach ($kategori->barang as $barang) {
                $barang->pbStok()->delete();
                $barang->stokBagian()->delete();
            }

            $kategori->barang()->delete();
            $kategori->delete();

            DB::commit();

            return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => "Kategori '{$namaKategori}' berhasil dihapus.",
                ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index', ['tab' => 'barang-kategori'])
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Gagal menghapus kategori: ' . $e->getMessage(),
                ]);
        }
    }

    public function checkKodeBarang(Request $request)
    {
        $kode = $request->get('kode');
        $currentKode = $request->get('current_kode');

        if (!$kode) {
            return response()->json(['available' => false, 'message' => 'Kode tidak boleh kosong']);
        }

        $query = Barang::where('kode_barang', $kode);

        if ($currentKode) {
            $query->where('kode_barang', '!=', $currentKode);
        }

        $exists = $query->exists();

        return response()->json([
            'available' => !$exists,
            'message' => $exists ? 'Kode barang sudah digunakan' : 'Kode barang tersedia'
        ]);
    }

    public function searchSuggestions(Request $request)
    {
        $search = $request->get('q', '');

        if (mb_strlen($search) < 2) {
            return response()->json([]);
        }

        $kw = mb_strtolower($search);

        $barangList = Barang::with(['kategori', 'pbStok.bagian', 'stokBagian.bagian'])
            ->where(function ($q) use ($search, $kw) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%")
                    // + Kategori
                    ->orWhereHas('kategori', function ($qq) use ($search) {
                        $qq->where('nama', 'like', "%{$search}%");
                    })
                    // + Bagian
                    ->orWhereHas('pbStok.bagian', function ($qq) use ($search) {
                        $qq->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('stokBagian.bagian', function ($qq) use ($search) {
                        $qq->where('nama', 'like', "%{$search}%");
                    });

                // + Lokasi keyword
                if (str_contains($kw, 'gudang utama') || str_contains($kw, 'gudang') || str_contains($kw, 'pusat') || $kw === 'pb') {
                    $q->orWhereHas('pbStok', function () {});
                }
                if (str_contains($kw, 'bagian') || $kw === 'pj') {
                    $q->orWhereHas('stokBagian', function () {});
                }
            })
            ->limit(25)
            ->get();


        $rows = collect();

        foreach ($barangList as $barang) {
            // Tampilkan dari pb_stok
            foreach ($barang->pbStok as $pb) {
                $stok = (int) ($pb->stok ?? 0);
                $rows->push([
                    'kode' => $barang->kode_barang,
                    'nama' => $barang->nama_barang,
                    'stok' => $stok,
                    'kategori' => $barang->kategori->nama ?? '-',
                    'bagian' => $pb->bagian->nama ?? '-',
                    'lokasi' => 'Gudang Utama',
                    'display' => $barang->nama_barang . ' (' . $barang->kode_barang . ') - ' . ($pb->bagian->nama ?? '-'),
                    'stock_status' => $stok === 0 ? 'empty' : ($stok < 10 ? 'low' : 'normal'),
                ]);
            }

            // Tampilkan dari stok_bagian
            foreach ($barang->stokBagian as $sb) {
                $stok = (int) ($sb->stok ?? 0);
                $rows->push([
                    'kode' => $barang->kode_barang,
                    'nama' => $barang->nama_barang,
                    'stok' => $stok,
                    'kategori' => $barang->kategori->nama ?? '-',
                    'bagian' => $sb->bagian->nama ?? '-',
                    'lokasi' => 'Bagian',
                    'display' => $barang->nama_barang . ' (' . $barang->kode_barang . ') - ' . ($sb->bagian->nama ?? '-'),
                    'stock_status' => $stok === 0 ? 'empty' : ($stok < 10 ? 'low' : 'normal'),
                ]);
            }
        }

        return response()->json($rows->take(15)->values());
    }
}
