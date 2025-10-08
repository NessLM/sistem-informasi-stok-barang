<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Contoh pakai:
     * ->middleware('role:Admin')
     * ->middleware('role:Admin,Penanggung Jawab ATK')
     * ->middleware('role:Penanggung Jawab ATK','Penanggung Jawab Kebersihan') // juga didukung
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        // Flatten: dukung format 'A,B,C' dan 'A','B','C'
        $allowed = [];
        foreach ($roles as $arg) {
            foreach (explode(',', $arg) as $r) {
                $r = trim($r);
                if ($r !== '') {
                    $allowed[] = $r;
                }
            }
        }
        // unik + reindex
        $allowed = array_values(array_unique($allowed, SORT_STRING));

        // [DEBUG]
        if ($user && $user->role) {
            Log::info('Role Check', [
                'username'   => $user->username,
                'user_role'  => $user->role->nama,
                'allowed_roles' => $allowed,
                'is_allowed' => in_array($user->role->nama, $allowed, true)
            ]);
        }

        // Validasi user & relasi role
        if (! $user || ! $user->role) {
            Log::warning('Access Denied - No role', [
                'username'      => $user->username ?? 'guest',
                'user_role'     => $user->role->nama ?? 'no role',
                'allowed_roles' => $allowed,
                'url'           => $request->fullUrl(),
            ]);
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        // Perbandingan toleran (trim, case-insensitive, space-insensitive)
        $userRole  = $user->role->nama;
        $isAllowed = false;
        foreach ($allowed as $allowedRole) {
            $u = trim($userRole);
            $a = trim($allowedRole);
            if (
                $u === $a ||
                str_replace(' ', '', $u) === str_replace(' ', '', $a) ||
                strtolower($u) === strtolower($a)
            ) {
                $isAllowed = true;
                break;
            }
        }

        if (! $isAllowed) {
            Log::warning('Access Denied - Role mismatch', [
                'username'      => $user->username,
                'user_role'     => $userRole,
                'allowed_roles' => $allowed,
                'url'           => $request->fullUrl(),
            ]);
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
