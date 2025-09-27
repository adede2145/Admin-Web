<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            abort(403, 'Access denied. Authentication required.');
        }

        $admin = auth()->user();
        
        // Check if the authenticated user is a super admin
        if (!$admin->role || $admin->role->role_name !== 'super_admin') {
            abort(403, 'Access denied. Only Super Admins can perform this action.');
        }

        return $next($request);
    }
}
