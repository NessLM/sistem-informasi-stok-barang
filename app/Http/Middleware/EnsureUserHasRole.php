<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * 
     * Contoh penggunaan:
     * ->middleware('role:Admin')
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
        if (! $user || ! $user->role) {
            Log::warning('Access Denied - No role', [
                'username' => $user ? $user->username : 'guest',
                'user_role' => $user && $user->role ? $user->role->nama : 'no role',
                'allowed_roles' => $allowed,
                'url' => $request->fullUrl()
            ]);
            
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        // Perbaikan: Gunakan perbandingan yang lebih toleran
        $userRole = $user->role->nama;
        $isAllowed = false;

        foreach ($allowed as $allowedRole) {
            // Normalisasi string untuk perbandingan yang lebih akurat
            $normalizedUserRole = trim($userRole);
            $normalizedAllowedRole = trim($allowedRole);
            
            // Periksa kecocokan dengan beberapa variasi
            if ($normalizedUserRole === $normalizedAllowedRole ||
                str_replace(' ', '', $normalizedUserRole) === str_replace(' ', '', $normalizedAllowedRole) ||
                strtolower($normalizedUserRole) === strtolower($normalizedAllowedRole)) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            // [DEBUG] Log saat ditolak
            Log::warning('Access Denied - Role mismatch', [
                'username' => $user->username,
                'user_role' => $userRole,
                'allowed_roles' => $allowed,
                'url' => $request->fullUrl()
            ]);
            
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}