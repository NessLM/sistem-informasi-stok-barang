<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Models\JenisBarang;
use App\Helpers\MenuHelper;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // SEMUA pakai admin.*
        $menu = MenuHelper::adminMenu();

        $summary = [
            'totalJenisBarang' => JenisBarang::count(),
            'totalBarang'      => Barang::count(),
        ];

        return view('staff.admin.dashboard', compact('menu', 'summary'));
    }
}
