<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $role = Auth::user()->role->nama;

            return match ($role) {
                'Admin' => to_route('staff.admin.dashboard'),
                'Pengurus Barang Pengguna' => to_route('staff.pb.dashboard'),
                'Pengurus Barang Pembantu' => to_route('staff.pj.dashboard'),

                default => to_route('login')->withErrors([
                    'username' => 'Role tidak dikenali.'
                ]),
            };
        }

        return back()
            ->withErrors(['username' => 'Username atau password salah.'])
            ->onlyInput('username');
    }

    /**
     * Logout user
     */
    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return to_route('login');
    }
}
