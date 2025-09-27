<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            abort(403, 'Access denied. Authentication required.');
        }

        $admin = auth()->user();
        
        // Check if the authenticated user is a super admin (note: role_name should be 'super_admin' not 'superadmin')
        if (!$admin->role || $admin->role->role_name !== 'super_admin') {
            abort(403, 'Access denied. Only Super Admins can perform this action.');
        }

        return $next($request);
    }
}
