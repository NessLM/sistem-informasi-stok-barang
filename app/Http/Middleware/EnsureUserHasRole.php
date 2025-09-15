<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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

        // Memastikan user login & punya relasi role yang valid
        if (! $user || ! $user->role || ! in_array($user->role->nama, $allowed, true)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
