<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\Kategori;   // 🔹 ubah: pakai Kategori
use Illuminate\Http\Request;

class BarangController extends Controller
{
    // helper untuk menu sidebar (supaya tidak duplikasi)
    protected function sidebarMenu()
    {
        return [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',        'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang Listrik',    'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang Kebersihan', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang B Komputer', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
            ]],
            ['label' => 'Riwayat', 'icon' => 'bi-clock-history', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Laporan', 'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Data Pengguna', 'icon' => 'bi-people', 'route' => 'staff.admin.dashboard'],
        ];
    }

    // form tambah barang
    public function create()
    {
        $menu = $this->sidebarMenu();
        $kategori = Kategori::all(); // 🔹 ubah: ambil kategori, bukan jenis barang
        return view('staff.admin.create', compact('kategori', 'menu'));
    }

    // simpan barang
    public function store(Request $request)
    {
        $request->validate([
            'nama'        => 'required|string|max:255',
            'kode'        => 'required|string|max:50',
            'harga'       => 'required|numeric',
            'stok'        => 'required|integer',
            'satuan'      => 'required|string|max:50',
            'kategori_id' => 'required|exists:kategori,id', // 🔹 ubah: validasi kategori_id
        ]);

        Barang::create($request->only(['nama','kode','harga','stok','satuan','kategori_id'])); // 🔹 simpan kategori_id

        // redirect kembali ke form create
        return redirect()->route('barang.create')->with('success', 'Barang berhasil ditambahkan!');
    }

    // contoh index juga kirim menu
    public function index()
    {
        $menu = $this->sidebarMenu();
        $barang = Barang::with('kategori')->get(); // 🔹 ubah: relasi kategori, bukan jenisBarang
        return view('staff.admin.index', compact('barang', 'menu'));
    }

    // edit/update/destroy => kalau render view, jangan lupa kirim $menu juga
}
