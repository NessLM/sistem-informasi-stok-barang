<?php

namespace App\Http\Controllers\Admin;

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
        $menu   = MenuHelper::adminMenu();
        $search = $request->input('search');

        // Ambil kategori + relasi barang
        $kategori = Kategori::with(['barang' => function ($q) use ($search) {
            if ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            }
        }])->get();

        // Ambil semua gudang (untuk form tambah kategori)
        $gudang = Gudang::all();

        // Validasi harga min max
        $request->validate([
            'harga_min' => 'nullable|numeric|min:0',
            'harga_max' => 'nullable|numeric|min:0',
        ]);

        if ($request->filled('harga_min') && $request->filled('harga_max') && $request->harga_min > $request->harga_max) {
            return back()->with('error', 'Harga minimum tidak boleh lebih besar dari harga maksimum');
        }

        // Query flat barang untuk pencarian/filter/modal edit
        $query = Barang::with('kategori');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            });
        }

        // Filter tambahan
        if ($request->filled('kode')) {
            $query->where('kode', 'like', "%{$request->kode}%");
        }
        if ($request->filled('stok_min')) {
            $query->where('stok', '>=', intval($request->stok_min));
        }
        if ($request->filled('stok_max')) {
            $query->where('stok', '<=', intval($request->stok_max));
        }
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        if ($request->filled('satuan')) {
            $query->where('satuan', $request->satuan);
        }
        if ($request->filled('nomor_awal')) {
            $query->where('id', '>=', intval($request->nomor_awal));
        }
        if ($request->filled('nomor_akhir')) {
            $query->where('id', '<=', intval($request->nomor_akhir));
        }
        if ($request->filled('harga_min') && $request->filled('harga_max')) {
    $query->whereBetween('harga', [
        floatval($request->harga_min),
        floatval($request->harga_max),
    ]);
} elseif ($request->filled('harga_min')) {
    $query->where('harga', '>=', floatval($request->harga_min));
} elseif ($request->filled('harga_max')) {
    $query->where('harga', '<=', floatval($request->harga_max));
}


        $barang = $query->get();

        return view('staff.admin.datakeseluruhan', compact('kategori', 'barang', 'menu', 'gudang'));
    }

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama'      => 'required|string|max:255|unique:kategori,nama',
            'gudang_id' => 'required|exists:gudang,id',
        ]);

        Kategori::create([
            'nama'      => $request->nama,
            'gudang_id' => $request->gudang_id,
        ]);

        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function storeBarang(Request $request)
    {
        $request->validate([
            'kode'        => 'required|string|max:255|unique:barang,kode',
            'nama'        => 'required|string|max:255',
            'harga'       => 'nullable|numeric|min:0',
            'stok'        => 'nullable|integer|min:0',
            'satuan'      => 'nullable|string|max:50',
            'kategori_id' => 'required|exists:kategori,id',
        ]);

        Barang::create([
            'kode'            => $request->kode,
            'nama'            => $request->nama,
            'harga'           => $request->harga ?? 0,
            'stok'            => $request->stok ?? 0,
            'satuan'          => $request->satuan,
            'kategori_id'     => $request->kategori_id,
            'jenis_barang_id' => 1, // default
        ]);

        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Barang berhasil ditambahkan!');
    }

    public function updateBarang(Request $request, $kode)
    {
        $barang = Barang::where('kode', $kode)->firstOrFail();

        $request->validate([
            'nama'        => 'required|string|max:255',
            'harga'       => 'nullable|numeric|min:0',
            'stok'        => 'nullable|integer|min:0',
            'satuan'      => 'nullable|string|max:50',
            'kategori_id' => 'required|exists:kategori,id',
        ]);

        $barang->update([
            'nama'        => $request->nama,
            'harga'       => $request->harga ?? 0,
            'stok'        => $request->stok ?? 0,
            'satuan'      => $request->satuan,
            'kategori_id' => $request->kategori_id,
        ]);

        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Barang berhasil diperbarui!');
    }

    public function destroyBarang($kode)
    {
        $barang = Barang::where('kode', $kode)->firstOrFail();
        $barang->delete();

        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Barang berhasil dihapus!');
    }

    public function destroyKategori($id)
    {
        $kategori = Kategori::findOrFail($id);

        // Hapus semua barang dalam kategori ini
        $kategori->barang()->delete();
        $kategori->delete();

        return redirect()->route('admin.datakeseluruhan')
                         ->with('success', 'Kategori berhasil dihapus!');
    }
}
