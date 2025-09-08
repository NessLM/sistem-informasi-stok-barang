<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Menu sesuai mockup PJ
        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],

            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',         'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
                ['label' => 'Gudang B Komputer',  'icon' => 'bi-grid', 'route' => 'staff.admin.dashboard'],
            ]],
            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'staff.admin.dashboard'],
        ];

        return view('staff.pj.dashboard', compact('menu'));
    }
}
