<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Http\Controllers\Traits\ToastResponse; // <-- import

class RoleController extends Controller
{
    use ToastResponse;

    public function index()
    {
        $roles = Role::all();
        return view('admin.roles.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:roles,nama',
        ]);

        try {
            Role::create($request->only('nama'));
            return $this->toastSuccess('Role berhasil ditambahkan.');
        } catch (\Exception $e) {
            return $this->toastError('Terjadi kesalahan saat menambahkan role.');
        }
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'nama' => 'required|string|max:255|unique:roles,nama,' . $role->id,
        ]);

        try {
            $role->update($request->only('nama'));
            return $this->toastSuccess('Role berhasil diupdate.');
        } catch (\Exception $e) {
            return $this->toastError('Terjadi kesalahan saat mengupdate role.');
        }
    }

    public function destroy(Role $role)
    {
        try {
            $role->delete();
            return $this->toastSuccess('Role berhasil dihapus.');
        } catch (\Exception $e) {
            return $this->toastError('Terjadi kesalahan saat menghapus role.');
        }
    }
}
