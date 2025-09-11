<?php

namespace App\Http\Controllers\Pj;
use App\Helpers\MenuHelper;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Menu sesuai mockup PJ
        $menu = MenuHelper::pjMenu();

        return view('staff.pj.dashboard', compact('menu'));
    }
}
