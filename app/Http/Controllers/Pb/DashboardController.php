<?php

namespace App\Http\Controllers\Pb;
use App\Helpers\MenuHelper;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // Menu sesuai mockup PB
        $menu = MenuHelper::pbMenu();

        return view('staff.pb.dashboard', compact('menu'));
    }
}
