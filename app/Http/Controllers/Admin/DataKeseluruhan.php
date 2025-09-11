<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;
use App\Models\Barang;
use Illuminate\Http\Request;

class DataKeseluruhan extends Controller
{
    public function index()
    {
        $kategori = Kategori::with('barang')->get();

        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang Listrik', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang Kebersihan', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang B Komputer', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
            ]],
            ['label' => 'Riwayat', 'icon' => 'bi-clock-history', 'route' => 'admin.dashboard'],
            ['label' => 'Laporan', 'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.dashboard'],
            ['label' => 'Data Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];

        return view('staff.admin.datakeseluruhan', compact('kategori', 'menu'));
    }

    public function storeKategori(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:kategoris,nama',
        ]);

        Kategori::create(['nama' => $request->nama]);

        return redirect()->route('staff.admin.datakeseluruhan')
            ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function storeBarang(Request $request)
{
    $request->validate([
        'nama'        => 'required|string|max:255',   // ✅ pakai nama
        'kode'        => 'required|string|max:100|unique:barangs,kode',
        'harga'       => 'nullable|numeric|min:0',
        'stok'        => 'nullable|integer|min:0',
        'satuan'      => 'nullable|string|max:50',
        'kategori_id' => 'required|exists:kategoris,id',
    ]);

    Barang::create([
    'kode'        => $request->kode,
    'nama'        => $request->nama,
    'harga'       => $request->harga,
    'stok'        => $request->stok ?? 0,   // ✅ default 0 kalau null
    'satuan'      => $request->satuan,
    'kategori_id' => $request->kategori_id,
]);


    return redirect()->route('staff.admin.datakeseluruhan')
        ->with('success', 'Barang berhasil ditambahkan!');
}

}
