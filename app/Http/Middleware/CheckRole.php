<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        if (Auth::check() && !in_array(Auth::user()->role_id, [1, 5])) {
            if(Auth::user()->role_id == 3 && Auth::user()->jabatan_id == 13){
                 return redirect()->route('gm-dashboard.index');
            }
            if(Auth::user()->role_id == 3){
                return redirect()->route('form-lag-indicator.index');
            }
            if(Auth::user()->role_id == 4){
                return redirect()->route('verifikator-lag-indicator.index');
            }
            if(Auth::user()->role_id == 2){
                return redirect()->route('form-admin-lag-indicator.index');
            }
        }

        return $next($request);
    }
}
