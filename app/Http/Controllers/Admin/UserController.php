<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        // Mengambil semua user dengan role dan mengurutkan
        $users = User::with('role')
            ->leftJoin('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.*')
            ->orderByRaw(
                "
            CASE 
                WHEN users.id = ? THEN 0 
                WHEN roles.nama = 'Admin' THEN 1 
                ELSE 2 
            END
        ",
                [Auth::id()]
            )
            ->orderBy('roles.nama')
            ->orderBy('users.nama')
            ->get();

        $roles = Role::all();
        $menu  = MenuHelper::adminMenu();

        $users->each(function ($u) {
            $hash = (string) $u->getOriginal('password');
            $cacheKey = "user:plainpwd:{$u->id}";
            $plainFromCache = null;

            if ($enc = Cache::get($cacheKey)) {
                try {
                    $tmp = Crypt::decryptString($enc);
                    if (Hash::check($tmp, $hash)) {
                        $plainFromCache = $tmp;
                    } else {
                        Cache::forget($cacheKey);
                    }
                } catch (\Throwable $e) {
                    // corrupt → abaikan
                }
            }

            $safeText = $plainFromCache ?: '(plain cache nya belum ada, seeder ulang)';
            $attr = $u->getAttributes();
            $attr['password'] = $safeText;
            $u->setRawAttributes($attr, true);
        });

        return view('staff.admin.data_pengguna', compact('users', 'roles', 'menu'));
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama'     => 'required|string|max:255',
                'username' => 'required|string|max:100|unique:users,username',
                'role_id'  => 'required|exists:roles,id',
                'bagian'   => 'required|string|max:255',
                'password' => [
                    'required',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
                ],
            ], [
                'password.regex' => 'Password harus mengandung huruf dan angka.',
            ]);

            if ($validator->fails()) {
                return back()->withInput()->withErrors($validator)->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Username sudah digunakan.'
                ]);
            }

            $validated = $validator->validated();

            $user = new User();
            $user->nama     = $validated['nama'];
            $user->username = $validated['username'];
            $user->role_id  = $validated['role_id'];
            $user->bagian   = $validated['bagian'] ?? null;
            $user->password = $validated['password'];
            $user->save();

            $cacheKey = "user:plainpwd:{$user->id}";
            Cache::forever($cacheKey, Crypt::encryptString($validated['password']));

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => 'Pengguna berhasil dibuat.'
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        try {
            $validator = Validator::make($request->all(), [
                'nama'     => 'required|string|max:255',
                'username' => 'required|string|max:100|unique:users,username,' . $user->id,
                'role_id'  => 'required|exists:roles,id',
                'bagian'   => 'required|string|max:255',
                'password' => [
                    'nullable',
                    'string',
                    'min:8',
                    'regex:/^(?=.*[a-zA-Z])(?=.*\d).+$/'
                ],
            ], [
                'password.regex' => 'Password harus mengandung huruf dan angka',
            ]);

            if ($validator->fails()) {
                return back()->withInput()->withErrors($validator)->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal',
                    'message' => 'Username tidak boleh sama.'
                ]);
            }

            $validated = $validator->validated();

            $user->nama     = $validated['nama'];
            $user->username = $validated['username'];
            $user->role_id  = $validated['role_id'];
            $user->bagian   = $validated['bagian'] ?? $user->bagian;

            if (!empty($validated['password'])) {
                $user->password = $validated['password'];
                $cacheKey = "user:plainpwd:{$user->id}";
                Cache::forever($cacheKey, Crypt::encryptString($validated['password']));
            }

            $user->save();

            return back()->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => 'Pengguna berhasil diperbarui.'
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('toast', [
                'type' => 'error',
                'title' => 'Gagal',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }


    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // 🔒 Cegah admin menghapus dirinya sendiri
        if ((int) $user->id === (int) Auth::id() && optional($user->role)->nama === 'Admin') {
            return redirect()->route('admin.users.index')
                ->with('toast', [
                    'type' => 'error',
                    'title' => 'Gagal',
                    'message' => 'Admin tidak bisa menghapus dirinya sendiri.'
                ]);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('toast', [
                'type' => 'success',
                'title' => 'Berhasil',
                'message' => 'Pengguna berhasil dihapus!'
            ]);
    }
}
