<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kategori;

class DataKeseluruhan extends Controller
{
    public function index()
    {
        // Ambil kategori beserta jenis barang dan barangnya
        $kategori = Kategori::with('jenisBarang.barang')->get();

        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',         'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang Listrik',     'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang Kebersihan',  'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang B Komputer',  'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
            ]],
            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'admin.dashboard'],
            ['label' => 'Laporan',        'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.dashboard'],
            ['label' => 'Data Pengguna',  'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];

        return view('staff.admin.datakeseluruhan', compact('kategori', 'menu'));
    }
}
