<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class EnsureUserHasRole
{
    /**
     * Middleware role-based.
     * Contoh: ->middleware('role:Admin')
     * Bisa multiple: ->middleware('role:Admin,Penanggung Jawab ATK')
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();
        $allowed = array_map('trim', explode(',', $roles));

        // [DEBUG] Log untuk debugging
        if ($user && $user->role) {
            Log::info('Role Check', [
                'username' => $user->username,
                'user_role' => $user->role->nama,
                'user_role_length' => strlen($user->role->nama),
                'allowed_roles' => $allowed,
                'is_allowed' => in_array($user->role->nama, $allowed, true)
            ]);
        }

        // Memastikan user login & punya relasi role yang valid
        if (! $user || ! $user->role || ! in_array($user->role->nama, $allowed, true)) {
            // [DEBUG] Log saat ditolak
            Log::warning('Access Denied', [
                'username' => $user ? $user->username : 'guest',
                'user_role' => $user && $user->role ? $user->role->nama : 'no role',
                'allowed_roles' => $allowed,
                'url' => $request->fullUrl()
            ]);
            
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}