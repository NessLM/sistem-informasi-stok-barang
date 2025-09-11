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

        // Eager load barang per kategori + filter jika ada pencarian
        $kategori = Kategori::with(['barang' => function ($q) use ($search) {
            if ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode', 'like', "%{$search}%");
            }
        }])->get();

        // NAMA RUTE konsisten pakai 'admin.*'
        $menu = MenuHelper::adminMenu();

        return view('staff.admin.datakeseluruhan', compact('kategori', 'menu', 'search'));
    }

    public function store(Request $request)
    {
        // Perhatikan nama tabel di rule: model Barang -> $table = 'barang'
        $request->validate([
            'kode'        => 'required|string|max:100|unique:barang,kode',
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

        return redirect()->route('admin.barang.index')
                         ->with('success', 'Barang berhasil ditambahkan!');
    }

    public function destroy($kode)
    {
        $barang = Barang::findOrFail($kode);
        $barang->delete();

        return redirect()->route('admin.barang.index')
                         ->with('success', 'Barang berhasil dihapus!');
    }
}
