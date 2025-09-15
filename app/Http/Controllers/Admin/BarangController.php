<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;
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

        $menu = MenuHelper::adminMenu();

        return view('staff.admin.datakeseluruhan', compact('kategori', 'menu', 'search'));
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
        ]);

        return redirect()->route('admin.barang.index')
                         ->with('success', 'Barang berhasil ditambahkan!');
    }

    public function edit($id)
    {
        $barang = Barang::findOrFail($id);
        $kategori = Kategori::all();
        $menu = MenuHelper::adminMenu();

        return view('staff.admin.edit-barang', compact('barang', 'kategori', 'menu'));
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

        return redirect()->route('admin.barang.index')
                         ->with('success', 'Barang berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $barang = Barang::findOrFail($id);
        $barang->delete();

        return redirect()->route('admin.barang.index')
                         ->with('success', 'Barang berhasil dihapus!');
    }
}
