<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Menu sesuai mockup PB
        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],

            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',         'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang Listrik',     'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang Kebersihan',  'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang B Komputer',  'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
            ]],

            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'staff.admin.dashboard'],
            ['label' => 'Laporan',        'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'staff.admin.dashboard'],
        ];

        return view('staff.pb.dashboard', compact('menu'));
    }
}
