<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AnyRole
{
    public function handle($request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $user = Auth::user();
        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // bandingkan nama role dan id role sebagai string
        $ok = in_array($user->role?->nama, $roles, true);

        if ($ok) return $next($request);

        abort(404, 'Unauthorized');
    }
}
