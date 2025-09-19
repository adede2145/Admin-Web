<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Admin;
use App\Models\AdminLoginAttempt;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        // Get the admin with the given username
        $admin = Admin::where('username', trim($credentials['username']))->first();

        if ($admin) {
            // Check if the password matches using PHP SHA-256 (hex) and timing-safe compare
            $computed = hash('sha256', $credentials['password']);
            if (hash_equals(strtolower($admin->password_hash), strtolower($computed))) {
                Auth::login($admin);
                // log successful attempt
                AdminLoginAttempt::create([
                    'admin_id' => $admin->admin_id,
                    'username' => $admin->username,
                    'successful' => true,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string)$request->header('User-Agent'), 0, 1000),
                    'created_at' => now(),
                ]);
                return redirect()->intended(route('dashboard'));
            }
        }
        // log failed attempt
        AdminLoginAttempt::create([
            'admin_id' => $admin->admin_id ?? null,
            'username' => trim($credentials['username']),
            'successful' => false,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string)$request->header('User-Agent'), 0, 1000),
            'created_at' => now(),
        ]);

        return back()->withErrors([
            'username' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
