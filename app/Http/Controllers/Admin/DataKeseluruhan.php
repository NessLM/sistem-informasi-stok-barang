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
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ['label' => 'Gudang Listrik', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
            ]],
            ['label' => 'Riwayat', 'icon' => 'bi-clock-history', 'route' => 'staff.admin.dashboard'],
        ];

        return view('staff.admin.datakeseluruhan', compact('kategori', 'menu'));
    }
}
