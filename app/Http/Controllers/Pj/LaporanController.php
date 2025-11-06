<?php

namespace App\Http\Controllers\Pj;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;

class LaporanController extends Controller{
    // SEMUA pakai pj.*
    public function __invoke()
    {
        // SEMUA pakai pj.*
        $menu = MenuHelper::pjMenu();

        return view('staff.pj.laporan', compact('menu'));

    }
}