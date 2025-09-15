<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class DataKeseluruhan extends Controller
{
    public function index(Request $request)
    {
        $menu   = MenuHelper::adminMenu();
        $search = $request->input('search');

        // Ambil kategori untuk tampilan grouped
        $kategori = Kategori::with(['barang' => function ($q) use ($search) {
            if ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            }
        }])->get();

        // Query flat barang untuk pencarian / filter / modal edit
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

        // Ambil hasil (selalu kirim $barang supaya blade tidak error)
        $barang = $query->get();

        return view('staff.admin.datakeseluruhan', compact('kategori', 'barang', 'menu'));
    }

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:kategori,nama',
        ]);

        Kategori::create([
            'nama' => $request->nama,
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
            'kode'        => $request->kode,
            'nama'        => $request->nama,
            'harga'       => $request->harga ?? 0,
            'stok'        => $request->stok ?? 0,
            'satuan'      => $request->satuan,
            'kategori_id' => $request->kategori_id,
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
}
