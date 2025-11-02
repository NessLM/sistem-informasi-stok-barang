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

        // Jika ada filter/search
        if ($this->hasAnyFilter($request)) {
            $query = Barang::with(['kategori', 'pbStok.bagian', 'stokBagian.bagian']);

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nama_barang', 'like', "%{$search}%")
                        ->orWhere('kode_barang', 'like', "%{$search}%");
                });
            }

            // Filter kategori
            if ($request->filled('kategori_id')) {
                $query->where('id_kategori', $request->kategori_id);
            }

            // Filter bagian
            if ($request->filled('bagian_id')) {
                $bagianId = $request->bagian_id;

                // Filter barang yang ada di pb_stok atau stok_bagian untuk bagian ini
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
                    // Filter stok bagian tertentu
                    $query->whereHas('stokBagian', function ($q) use ($min, $max, $request) {
                        $q->where('bagian_id', $request->bagian_id)
                            ->whereBetween('stok', [$min, $max]);
                    });
                } else {
                    // Filter stok pusat (PB)
                    $query->whereHas('pbStok', function ($q) use ($min, $max) {
                        $q->whereBetween('stok', [$min, $max]);
                    });
                }
            }

            // Filter harga
            if ($request->filled('harga_min') || $request->filled('harga_max')) {
                if ($request->filled('bagian_id')) {
                    // Harga dari stok_bagian
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
                    // Harga dari pb_stok
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

            // Flatten hasil untuk tampilan
            $hasilCari = $barang->flatMap(function ($b) use ($request) {
                $rows = collect();

                if ($request->filled('bagian_id')) {
                    // Tampilkan hanya bagian yang dipilih
                    $bagianId = (int) $request->bagian_id;

                    // Cek di pb_stok
                    $pbStok = $b->pbStok->where('bagian_id', $bagianId)->first();
                    if ($pbStok) {
                        $rows->push((object) [
                            'b' => $b,
                            'stok' => (int) ($pbStok->stok ?? 0),
                            'bagian' => $pbStok->bagian->nama ?? '-',
                            'lokasi' => 'Gudang Utama',
                            'kategori' => $b->kategori->nama ?? '-',
                            'harga' => $pbStok->harga ?? 0,
                        ]);
                    }

                    // Cek di stok_bagian
                    $stokBagian = $b->stokBagian->where('bagian_id', $bagianId)->first();
                    if ($stokBagian) {
                        $rows->push((object) [
                            'b' => $b,
                            'stok' => (int) ($stokBagian->stok ?? 0),
                            'bagian' => $stokBagian->bagian->nama ?? '-',
                            'lokasi' => 'Bagian',
                            'kategori' => $b->kategori->nama ?? '-',
                            'harga' => $stokBagian->harga ?? 0,
                        ]);
                    }
                } else {
                    // Tampilkan semua dari pb_stok
                    foreach ($b->pbStok as $pb) {
                        $rows->push((object) [
                            'b' => $b,
                            'stok' => (int) ($pb->stok ?? 0),
                            'bagian' => $pb->bagian->nama ?? '-',
                            'lokasi' => 'Gudang Utama',
                            'kategori' => $b->kategori->nama ?? '-',
                            'harga' => $pb->harga ?? 0,
                        ]);
                    }

                    // Tampilkan semua dari stok_bagian
                    foreach ($b->stokBagian as $sb) {
                        $rows->push((object) [
                            'b' => $b,
                            'stok' => (int) ($sb->stok ?? 0),
                            'bagian' => $sb->bagian->nama ?? '-',
                            'lokasi' => 'Bagian',
                            'kategori' => $b->kategori->nama ?? '-',
                            'harga' => $sb->harga ?? 0,
                        ]);
                    }
                }

                return $rows;
            });
        }

        return view('staff.admin.datakeseluruhan', compact(
            'kategori',
            'menu',
            'bagian',
            'hasilCari'
        ));
    }

    private function hasAnyFilter(Request $request)
    {
        $filterFields = [
            'search',
            'kode',
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

        return redirect()->route('admin.datakeseluruhan.index')
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

        // Buat barang baru
        $barang = Barang::create([
            'kode_barang' => $request->kode_barang,
            'nama_barang' => $request->nama_barang,
            'satuan' => $request->satuan,
            'id_kategori' => $request->id_kategori,
        ]);

        return redirect()->route('admin.datakeseluruhan.index')
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

        $barang->update([
            'kode_barang' => $request->kode_barang,
            'nama_barang' => $request->nama_barang,
            'satuan' => $request->satuan,
            'id_kategori' => $request->id_kategori,
        ]);

        return redirect()->route('admin.datakeseluruhan.index')
            ->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Barang berhasil diperbarui.'
            ]);
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

            return redirect()->route('admin.datakeseluruhan.index')
                ->with('toast', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => "Barang {$namaBarang} berhasil dihapus."
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

            // Hapus semua barang dalam kategori
            foreach ($kategori->barang as $barang) {
                $barang->pbStok()->delete();
                $barang->stokBagian()->delete();
            }

            $kategori->barang()->delete();
            $kategori->delete();

            DB::commit();

            return redirect()->route('admin.datakeseluruhan.index')->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => "Kategori '{$namaKategori}' berhasil dihapus.",
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.datakeseluruhan.index')->with('toast', [
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

        $barangList = Barang::with(['kategori', 'pbStok.bagian', 'stokBagian.bagian'])
            ->where(function ($q) use ($search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_barang', 'like', "%{$search}%");
            })
            ->limit(10)
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