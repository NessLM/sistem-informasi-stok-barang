<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        // menu sidebar sama formatnya dengan DashboardController
        $menu = [
            ['label' => 'Dashboard', 'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
            ['label' => 'Data Keseluruhan', 'icon' => 'bi-card-list', 'children' => [
                ['label' => 'Gudang ATK',         'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
                ['label' => 'Gudang Listrik',     'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
                ['label' => 'Gudang Kebersihan',  'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
                ['label' => 'Gudang B Komputer',  'icon' => 'bi-grid', 'route' => 'admin.dashboard'],
            ]],
            ['label' => 'Riwayat',        'icon' => 'bi-clock-history', 'route' => 'admin.dashboard'],
            ['label' => 'Laporan',        'icon' => 'bi-file-earmark-bar-graph-fill', 'route' => 'admin.dashboard'],
            ['label' => 'Data Pengguna',  'icon' => 'bi-people', 'route' => 'admin.users.index'],
        ];

        return view('staff.admin.admin-datapengguna', compact('users', 'menu'));

    }
}
