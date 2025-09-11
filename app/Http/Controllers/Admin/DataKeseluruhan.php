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
                ['label' => 'Gudang ATK',        'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.atk'],
                ['label' => 'Gudang Listrik',    'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.listrik'],
                ['label' => 'Gudang Kebersihan', 'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.kebersihan'],
                ['label' => 'Gudang B Komputer', 'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.komputer'],
            ]],
            ['label' => 'Riwayat',       'icon' => 'bi-clock-history', 'route' => 'admin.riwayat.index'],
            ['label' => 'Laporan',       'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.dashboard'],
            ['label' => 'Data Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];

        return view('staff.admin.datakeseluruhan', compact('kategori', 'menu'));
    }

    public function storeKategori(Request $request)
    {
        // Tabel: kategori (bukan kategoris)
        $request->validate([
            'nama' => 'required|string|max:255|unique:kategori,nama',
        ]);

        Kategori::create(['nama' => $request->nama]);

        return redirect()->route('admin.datakeseluruhan')
            ->with('success', 'Kategori berhasil ditambahkan!');
    }

    public function storeJenis(Request $request)
    {
        // Jika nanti ada kolom jenis, tambahkan rule & create di sini.
        return back()->with('info', 'Endpoint storeJenis belum diimplementasi.');
    }

    public function storeBarang(Request $request)
    {
        $request->validate([
            'kode'        => 'required|string|max:255',
            'nama'        => 'required|string|max:100|unique:barang,kode', // tabel barang
            'harga'       => 'nullable|numeric|min:0',
            'stok'        => 'nullable|integer|min:0',
            'satuan'      => 'nullable|string|max:50',
            'kategori_id' => 'required|exists:kategori,id',
        ]);

        Barang::create([
            'kode'        => $request->kode,
            'nama'        => $request->nama,
            'harga'       => $request->harga,
            'stok'        => $request->stok ?? 0,
            'satuan'      => $request->satuan,
            'kategori_id' => $request->kategori_id,
        ]);

        return redirect()->route('admin.datakeseluruhan')
            ->with('success', 'Barang berhasil ditambahkan!');
    }
}
