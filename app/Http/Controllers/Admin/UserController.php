<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;   // 🔥 perlu import
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

class UserController extends Controller
{
    public function index()
    {
        // 🔥 load relasi role + ambil semua roles buat select option
        $users = User::with('role')->get();
        $roles = Role::all();

        $menu = MenuHelper::adminMenu();

        return view('staff.admin.admin-datapengguna', compact('users', 'roles', 'menu'));
    }

    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    // 🔥 Kalau admin sedang edit dirinya sendiri
    if ($user->id === auth()->id() && $user->role->nama === 'Admin') {
        $validated = $request->validate([
            'nama'     => 'required|string|max:255',
            'bagian'   => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
                         ->with('success', 'Admin berhasil diperbarui!');
    }

    // 🔥 User biasa
    $validated = $request->validate([
        'nama'     => 'required|string|max:255',
        'username' => 'required|string|max:255|unique:users,username,' . $user->id,
        'role_id'  => 'required|exists:roles,id',
        'bagian'   => 'nullable|string|max:255',
        'password' => 'nullable|string|min:6',
    ]);

    if (empty($validated['password'])) {
        unset($validated['password']);
    }

    $user->update($validated);

    return redirect()->route('admin.users.index')
                     ->with('success', 'Pengguna berhasil diperbarui!');
}

public function destroy($id)
{
    $user = User::findOrFail($id);

    // 🔥 Cegah admin menghapus dirinya sendiri
    if ($user->id === auth()->id() && $user->role->nama === 'Admin') {
        return redirect()->route('admin.users.index')
                         ->withErrors(['msg' => 'Admin tidak bisa menghapus dirinya sendiri.']);
    }

    $user->delete();

    return redirect()->route('admin.users.index')
                     ->with('success', 'Pengguna berhasil dihapus!');
}

}
