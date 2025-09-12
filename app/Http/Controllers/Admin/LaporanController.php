<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;

class LaporanController extends Controller{
    // SEMUA pakai admin.*
    public function __invoke()
    {
        // SEMUA pakai admin.*
        $menu = MenuHelper::adminMenu();

        return view('staff.admin.laporan', compact('menu'));
    }
}