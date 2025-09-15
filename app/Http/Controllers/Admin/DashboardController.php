<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\MenuHelper;

class DashboardController extends Controller
{
    public function __invoke()
    {
        // SEMUA pakai admin.*
        $menu = MenuHelper::adminMenu();

        return view('staff.admin.dashboard', compact('menu'));
    }
}
