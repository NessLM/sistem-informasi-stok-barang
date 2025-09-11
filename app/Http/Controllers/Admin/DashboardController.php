<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\JenisBarang;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // SEMUA pakai admin.*
        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],

            [
                'label' => 'Data Keseluruhan',
                'icon'  => 'bi-card-list',
                'children' => [
                    ['label' => 'Gudang ATK',        'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.atk'],
                    ['label' => 'Gudang Listrik',    'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.listrik'],
                    ['label' => 'Gudang Kebersihan', 'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.kebersihan'],
                    ['label' => 'Gudang B Komputer', 'icon' => 'bi-grid', 'route' => 'admin.datakeseluruhan.komputer'],
                ]
            ],

            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'admin.riwayat.index'],
            ['label' => 'Laporan',        'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.dashboard'],
            ['label' => 'Data Pengguna',  'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];

        $summary = [
            'totalJenisBarang' => JenisBarang::count(),
            'totalBarang'      => Barang::count(),
        ];

        return view('staff.admin.dashboard', compact('menu', 'summary'));
    }
}
