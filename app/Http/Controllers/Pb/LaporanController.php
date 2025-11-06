<?php

namespace App\Http\Controllers\Pb;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;

class LaporanController extends Controller{
    // SEMUA pakai pb.*
    public function __invoke()
    {
        // SEMUA pakai [pb].*
        $menu = MenuHelper::pbMenu();

        return view('staff.pb.laporan', compact('menu'));
    }
}