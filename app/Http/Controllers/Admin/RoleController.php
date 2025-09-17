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
    try {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:roles,nama',
        ]);

        Role::create($validated);

        return $this->toastSuccess('Role berhasil ditambahkan.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        // ambil error pertama
        $errorMessage = $e->validator->errors()->first('nama');
        return $this->toastError('Gagal menambahkan role: Tidak dapat menggunakan nama role yang sama.')
                     ->withErrors($e->errors())
                     ->withInput();
    } catch (\Exception $e) {
        return $this->toastError('Terjadi kesalahan saat menambahkan role.');
    }
}

public function update(Request $request, Role $role)
{
    try {
        $validated = $request->validate([
            'nama' => 'required|string|max:255|unique:roles,nama,' . $role->id,
        ]);

        $role->update($validated);

        return $this->toastSuccess('Role berhasil diupdate.');
    } catch (\Illuminate\Validation\ValidationException $e) {
        $errorMessage = $e->validator->errors()->first('nama');
        return $this->toastError('Gagal mengupdate role: ' . $errorMessage)
                     ->withErrors($e->errors())
                     ->withInput();
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
