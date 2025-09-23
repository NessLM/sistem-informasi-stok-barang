<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use App\Models\Gudang;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu   = MenuHelper::pbMenu(); // ✅ menu khusus PB
        $search = $request->input('search');

        $kategoriQuery = Kategori::with([
            'barang' => function ($q) use ($search) {
                if ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                      ->orWhere('kode', 'like', "%{$search}%");
                }
            },
            'gudang'
        ]);

        if ($request->filled('gudang_id')) {
            $kategoriQuery->where('gudang_id', $request->gudang_id);
        }

        $kategori = $kategoriQuery->get();
        $gudang   = Gudang::all();

        $selectedGudang = $request->filled('gudang_id')
            ? Gudang::find($request->gudang_id)
            : null;

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

        $barang = $this->getFilteredBarang($request, $request->gudang_id);

        return view('staff.pb.datakeseluruhan', compact(
            'kategori',
            'barang',
            'menu',
            'gudang',
            'selectedGudang'
        ));
    }

    // ... sisanya sama persis seperti versi admin
    // tinggal ganti semua route/view ke prefix pb

    private function getFilteredBarang(Request $request, $gudangId = null)
    {
        $query = Barang::with(['kategori.gudang']);
        
        if ($gudangId) {
            $query->whereHas('kategori', function($q) use ($gudangId) {
                $q->where('gudang_id', $gudangId);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kode')) {
            $query->where('kode', 'like', "%{$request->kode}%");
        }

        if ($request->filled('stok_min')) {
            $query->where('stok', '>=', (int) $request->stok_min);
        }
        if ($request->filled('stok_max')) {
            $query->where('stok', '<=', (int) $request->stok_max);
        }

        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }

        if ($request->filled('satuan')) {
            $query->where('satuan', $request->satuan);
        }

        if ($request->filled('harga_min') && $request->filled('harga_max')) {
            $query->whereBetween('harga', [
                (float) $request->harga_min,
                (float) $request->harga_max,
            ]);
        } elseif ($request->filled('harga_min')) {
            $query->where('harga', '>=', (float) $request->harga_min);
        } elseif ($request->filled('harga_max')) {
            $query->where('harga', '<=', (float) $request->harga_max);
        }

        if ($request->filled('nomor_awal')) {
            $query->where('id', '>=', (int) $request->nomor_awal);
        }
        if ($request->filled('nomor_akhir')) {
            $query->where('id', '<=', (int) $request->nomor_akhir);
        }

        return $query->get();
    }

    public function distribusiBarang(Request $request)
{
    $request->validate([
        'barang_id' => 'required|exists:barang,id',
        'gudang_asal_id' => 'required|exists:gudang,id',
        'gudang_tujuan_id' => 'required|exists:gudang,id|different:gudang_asal_id',
        'jumlah' => 'required|integer|min:1',
    ]);

    $barang = Barang::with('kategori')->findOrFail($request->barang_id);

    // pastikan barang ada di gudang asal
    if ($barang->kategori->gudang_id != $request->gudang_asal_id) {
        return back()->withErrors(['barang' => 'Barang tidak ada di gudang asal']);
    }

    if ($barang->stok < $request->jumlah) {
        return back()->withErrors(['stok' => 'Stok tidak mencukupi di gudang asal']);
    }

    // kurangi stok gudang asal
    $barang->decrement('stok', $request->jumlah);

    // tambah stok di gudang tujuan
    $barangTujuan = Barang::where('kode', $barang->kode)
        ->whereHas('kategori', function($q) use ($request) {
            $q->where('gudang_id', $request->gudang_tujuan_id);
        })
        ->first();

    if ($barangTujuan) {
        $barangTujuan->increment('stok', $request->jumlah);
    } else {
        // jika belum ada barang tsb di gudang tujuan, buat baru
        $newKategori = Kategori::firstOrCreate([
            'nama' => $barang->kategori->nama,
            'gudang_id' => $request->gudang_tujuan_id,
        ]);

        Barang::create([
            'kode' => $barang->kode,
            'nama' => $barang->nama,
            'harga' => $barang->harga,
            'stok' => $request->jumlah,
            'satuan' => $barang->satuan,
            'kategori_id' => $newKategori->id,
            'jenis_barang_id' => $barang->jenis_barang_id,
        ]);
    }

    // catat distribusi
    \App\Models\Distribusi::create([
        'barang_id' => $barang->id,
        'user_asal_id' => auth()->id(),
        'user_tujuan_id' => null, // kalau gudang tujuan bukan user, bisa null
        'jumlah' => $request->jumlah,
        'tanggal' => now(),
    ]);

    return back()->with('success', 'Distribusi berhasil dilakukan');
}

public function barangMasuk(Request $request)
{
    $request->validate([
        'barang_id' => 'required|exists:barang,id',
        'gudang_id' => 'required|exists:gudang,id',
        'jumlah' => 'required|integer|min:1',
    ]);

    $barang = Barang::with('kategori')->findOrFail($request->barang_id);

    // tambah stok ke gudang penerima
    if ($barang->kategori->gudang_id == $request->gudang_id) {
        $barang->increment('stok', $request->jumlah);
    } else {
        $kategori = Kategori::firstOrCreate([
            'nama' => $barang->kategori->nama,
            'gudang_id' => $request->gudang_id,
        ]);

        Barang::create([
            'kode' => $barang->kode,
            'nama' => $barang->nama,
            'harga' => $barang->harga,
            'stok' => $request->jumlah,
            'satuan' => $barang->satuan,
            'kategori_id' => $kategori->id,
            'jenis_barang_id' => $barang->jenis_barang_id,
        ]);
    }

    // catat log barang masuk
    \App\Models\Distribusi::create([
        'barang_id' => $barang->id,
        'user_asal_id' => null,
        'user_tujuan_id' => auth()->id(),
        'jumlah' => $request->jumlah,
        'tanggal' => now(),
    ]);

    return back()->with('success', 'Barang masuk berhasil dicatat');
}

}
