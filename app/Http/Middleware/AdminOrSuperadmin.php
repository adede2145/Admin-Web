<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOrSuperadmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(403, 'Access denied. Authentication required.');
        }

        $admin = auth()->user();
        
        // Check if the authenticated user is either admin or super_admin
        if (!$admin->role || !in_array($admin->role->role_name, ['admin', 'super_admin'])) {
            abort(403, 'Access denied. Admin privileges required.');
        }

        return $next($request);
    }
}
