<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && 
            (auth()->user()->role->role_name === 'admin' || 
             auth()->user()->role->role_name === 'superadmin')) {
            return $next($request);
        }

        abort(403, 'Unauthorized action.');
    }
}
