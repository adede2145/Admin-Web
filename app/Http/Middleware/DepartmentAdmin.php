<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DepartmentAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        
        // Debug information
        Log::info('DepartmentAdmin Middleware Debug', [
            'user_id' => $user->admin_id,
            'username' => $user->username,
            'role_name' => $user->role->role_name ?? 'no role',
            'department_id' => $user->department_id,
            'is_super_admin' => $user->role->role_name === 'super_admin',
            'is_dept_admin' => $user->role->role_name === 'admin' && $user->department_id
        ]);
        
        // Check if user is either super admin or department admin
        if ($user->role->role_name === 'super_admin' || 
            ($user->role->role_name === 'admin' && $user->department_id)) {
            return $next($request);
        }

        // Log the specific reason for denial
        Log::error('DepartmentAdmin Middleware Access Denied', [
            'user_id' => $user->admin_id,
            'username' => $user->username,
            'role_name' => $user->role->role_name ?? 'no role',
            'department_id' => $user->department_id,
            'url' => $request->url(),
            'reason' => 'User does not have super_admin role or admin role with department_id'
        ]);

        abort(403, 'Access denied. Only Super Admins or Department Admins can perform this action. Your role: ' . ($user->role->role_name ?? 'no role') . ', Department: ' . ($user->department_id ?? 'none'));
    }
}
