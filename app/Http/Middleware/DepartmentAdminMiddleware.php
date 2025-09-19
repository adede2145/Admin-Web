<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Allow super admins and department admins
        $user = auth()->user();
        if ($user->role->role_name === 'super_admin' || $user->role->role_name === 'department_admin') {
            return $next($request);
        }

        // Redirect unauthorized users
        return redirect()->route('dashboard')->with('error', 'You do not have permission to access this feature.');
    }
}
