<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TokenController extends Controller
{
    /**
     * Generate a temporary authentication token for local registration
     */
    public function generate(Request $request)
    {
        try {
            // Get authenticated user
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Generate a unique token
            $token = Str::random(64);
            
            // Store token in cache for 10 minutes with user information
            Cache::put("local_registration_token:{$token}", [
                'admin_id' => $user->admin_id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role->role_name ?? null,
                'department_id' => $user->department_id ?? null,
                'created_at' => now()
            ], now()->addMinutes(10));
            
            return response()->json([
                'success' => true,
                'token' => $token,
                'expires_in' => 600 // 10 minutes in seconds
            ]);
            
        } catch (\Exception $e) {
            Log::error('Token generation error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate token: ' . $e->getMessage()
            ], 500);
        }
    }
}
