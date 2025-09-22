<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
use App\Models\Gudang;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        // Ambil kategori + barang (filter kalau ada search)
        $kategori = Kategori::with(['barang' => function ($q) use ($search) {
            if ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            }
        }])->get();

        // Ambil semua gudang biar dropdown muncul
        $gudang = Gudang::all();
        $barang = collect(); // Initialize empty collection for search results

        $menu = MenuHelper::adminMenu();

        return view('staff.admin.datakeseluruhan', compact('kategori', 'menu', 'search', 'gudang', 'barang'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'kode'        => 'required|string|max:100|unique:barang,kode',
            'nama'        => 'required|string|max:255|unique:barang,nama',
            'harga'       => 'nullable|numeric|min:0',
            'stok'        => 'nullable|integer|min:0',
            'satuan'      => 'nullable|string|max:50',
            'kategori_id' => 'required|exists:kategori,id',
        ], [
            'kode.unique' => 'Kode barang sudah digunakan!',
            'nama.unique' => 'Nama barang sudah ada, silakan gunakan nama lain!',
        ]);

        Barang::create([
            'kode'        => $request->kode,
            'nama'        => $request->nama,
            'harga'       => $request->harga ?? 0,
            'stok'        => $request->stok ?? 0,
            'satuan'      => $request->satuan,
            'kategori_id' => $request->kategori_id,
            'jenis_barang_id' => 1, // default
        ]);

        // ✅ FIXED: Route name sesuai web.php
        return redirect()->route('admin.datakeseluruhan.index')
                         ->with('success', 'Barang berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $barang   = Barang::findOrFail($id);
        $kategori = Kategori::all();
        $gudang   = Gudang::all();
        $menu     = MenuHelper::adminMenu();

        return view('staff.admin.edit-barang', compact('barang', 'kategori', 'menu', 'gudang'));
    }

    public function update(Request $request, $id)
    {
        $barang = Barang::findOrFail($id);

        $request->validate([
            'kode'        => 'required|string|max:100|unique:barang,kode,' . $barang->id,
            'nama'        => 'required|string|max:255|unique:barang,nama,' . $barang->id,
            'harga'       => 'nullable|numeric|min:0',
            'stok'        => 'nullable|integer|min:0',
            'satuan'      => 'nullable|string|max:50',
            'kategori_id' => 'required|exists:kategori,id',
        ], [
            'kode.unique' => 'Kode barang sudah digunakan!',
            'nama.unique' => 'Nama barang sudah ada, silakan gunakan nama lain!',
        ]);

        $barang->update([
            'kode'        => $request->kode,
            'nama'        => $request->nama,
            'harga'       => $request->harga ?? 0,
            'stok'        => $request->stok ?? 0,
            'satuan'      => $request->satuan,
            'kategori_id' => $request->kategori_id,
        ]);

        // ✅ FIXED: Route name sesuai web.php
        return redirect()->route('admin.datakeseluruhan.index')
                         ->with('success', 'Barang berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $barang = Barang::findOrFail($id);
        $barang->delete();

        // ✅ FIXED: Route name sesuai web.php
        return redirect()->route('admin.datakeseluruhan.index')
                         ->with('success', 'Barang berhasil dihapus!');
    }

public function search(Request $request)
{
    $q        = $request->get('q');
    $gudangId = $request->get('gudang_id');

    $barang = Barang::with(['kategori.gudang'])
        ->whereHas('kategori', function ($sub) use ($gudangId) {
            if ($gudangId) {
                $sub->where('gudang_id', $gudangId);
            }
        })
        ->when($q, function ($query) use ($q) {
            $query->where(function ($q2) use ($q) {
                $q2->where('nama', 'like', "%{$q}%")
                   ->orWhere('kode', 'like', "%{$q}%");
            });
        })
        ->get();

    return response()->json($barang->map(function ($b) {
        return [
            'id'           => $b->id,
            'nama'         => $b->nama,
            'kode'         => $b->kode,
            'stok'         => $b->stok,
            'kategori'     => $b->kategori->nama ?? '-',
            'gudang'       => $b->kategori->gudang->nama ?? '-',
            'stock_status' => $b->stok == 0 ? 'empty' : ($b->stok < 5 ? 'low' : 'available'),
        ];
    }));
}





}