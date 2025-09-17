<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Helpers\MenuHelper;

// === [BARU] Import untuk solusi ini ===
use Illuminate\Support\Facades\Hash;   // verifikasi plaintext ↔ hash (safety)
use Illuminate\Support\Facades\Cache;  // simpan plaintext permanen (bukan DB)
use Illuminate\Support\Facades\Crypt;  // enkripsi/dekripsi plaintext di cache

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->get();
        $roles = Role::all();
        $menu  = MenuHelper::adminMenu();

        /**
         * [DIUBAH PENTING]
         * - JANGAN kirim hash $2y$... ke DOM.
         * - Jika ada plaintext di cache → tampilkan plaintext.
         * - Jika tidak ada → tampil "(disembunyikan)".
         * - Tidak ada TTL & tidak dihapus saat dibaca (persist).
         */
        $users->each(function ($u) {
            $hash = (string) $u->getOriginal('password'); // hash mentah dari DB

            $cacheKey = "user:plainpwd:{$u->id}";
            $plainFromCache = null;

            if ($enc = Cache::get($cacheKey)) {
                try {
                    $tmp = Crypt::decryptString($enc);          // decrypt dari cache
                    if (Hash::check($tmp, $hash)) {             // safety: cocok dg hash sekarang?
                        $plainFromCache = $tmp;
                    } else {
                        Cache::forget($cacheKey);               // hash berubah → buang cache lama
                    }
                } catch (\Throwable $e) {
                    // corrupt → abaikan
                }
            }

            // Kalau cache ada → tampil plaintext; kalau tidak → "(disembunyikan)"
            $safeText = $plainFromCache ?: '(plain cache nya belum ada, seeder ulang)';

            // Timpa NILAI MENTAH 'password' AGAR Blade lama tetap jalan tanpa bocor hash
            $attr = $u->getAttributes();
            $attr['password'] = $safeText;
            $u->setRawAttributes($attr, true); // ❗ hanya untuk view; JANGAN $u->save()
        });

        return view('staff.admin.data_pengguna', compact('users', 'roles', 'menu'));
    }

    /**
     * [DIUBAH] Setelah create user: simpan plaintext ke cache PERMANEN (Cache::forever).
     * Jika kamu sudah punya store() sendiri, salin blok Cache::forever(...) ke method-mu
     * setelah $user->save().
     */
    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'nama'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username',
            'role_id'  => 'required|exists:roles,id',
            'bagian'   => 'nullable|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $user = new User();
        $user->nama     = $validated['nama'];
        $user->username = $validated['username'];
        $user->role_id  = $validated['role_id'];
        $user->bagian   = $validated['bagian'] ?? null;
        $user->password = $validated['password']; // auto-hash oleh cast
        $user->save();

        // simpan plaintext di cache
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
            'message' => 'Terjadi kesalahan: Tidak dapat menggunakan username yang sama.'
        ]);
    }
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    try {
        $validated = $request->validate([
            'nama'     => 'required|string|max:255',
            'username' => 'required|string|max:100|unique:users,username,' . $user->id,
            'role_id'  => 'required|exists:roles,id',
            'bagian'   => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
        ]);

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
            'message' => 'Terjadi kesalahan: Tidak dapat menggunakan username yang sama.' 
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
