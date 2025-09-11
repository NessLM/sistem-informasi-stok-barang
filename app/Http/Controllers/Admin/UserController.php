<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        $menu = MenuHelper::adminMenu();

        return view('staff.admin.admin-datapengguna', compact('users', 'menu'));
    }

    // method resource lain (create/store/show/edit/update/destroy) isi sesuai kebutuhanmu
}
