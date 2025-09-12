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

    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $validated = $request->validate([
        'nama'     => 'required|string|max:255',
        'username' => 'required|string|max:255|unique:users,username,' . $user->id,
        'role'     => 'required|string|max:50',
        'bagian'   => 'nullable|string|max:255',
        'password' => 'nullable|string|min:6',
    ]);

    // kalau password kosong, jangan update
    if (empty($validated['password'])) {
        unset($validated['password']);
    }

    $user->update($validated);

    return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil diperbarui!');
}


public function destroy($id)
{
    $user = User::findOrFail($id);
    $user->delete();

    return redirect()->route('admin.users.index')->with('success', 'Pengguna berhasil dihapus!');
}


    // method resource lain (create/store/show/edit/update/destroy) isi sesuai kebutuhanmu
}
