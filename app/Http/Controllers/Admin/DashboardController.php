<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\JenisBarang;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Menu sesuai mockup Admin
        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],

            [
                'label' => 'Data Keseluruhan',
                'icon' => 'bi-card-list',
                'children' => [
                    ['label' => 'Gudang ATK', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                    ['label' => 'Gudang Listrik', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                    ['label' => 'Gudang Kebersihan', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                    ['label' => 'Gudang B Komputer', 'icon' => 'bi-grid', 'route' => 'staff.admin.datakeseluruhan'],
                ]
            ],

            ['label' => 'Riwayat', 'icon' => 'bi-clock-history', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Laporan', 'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Data Pengguna', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];

        // Ringkasan data
        $summary = [
            'totalJenisBarang' => JenisBarang::count(),
            'totalBarang' => Barang::count(),
        ];

        return view('staff.admin.dashboard', compact('menu', 'summary'));
    }
}



