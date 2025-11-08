<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TokenService
{
    /**
     * Generate a secure token for local registration page access
     * 
     * @param \App\Models\Admin $admin
     * @param int $expiryMinutes
     * @return string
     */
    public function generateRegistrationToken($admin, $expiryMinutes = 5)
    {
        $payload = [
            'admin_id' => $admin->admin_id,
            'role' => $admin->role->role_name,
            'office_id' => $admin->department_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'expires_at' => Carbon::now()->addMinutes($expiryMinutes)->timestamp,
            'issued_at' => Carbon::now()->timestamp,
        ];

        // Encrypt the payload
        $encrypted = Crypt::encryptString(json_encode($payload));

        return base64_encode($encrypted);
    }

    /**
     * Validate and decode a registration token
     * 
     * @param string $token
     * @return array|null
     */
    public function validateRegistrationToken($token)
    {
        try {
            // Decode and decrypt
            $encrypted = base64_decode($token);
            $decrypted = Crypt::decryptString($encrypted);
            $payload = json_decode($decrypted, true);

            // Debug logging
            $currentTime = Carbon::now()->timestamp;
            $expiresAt = $payload['expires_at'] ?? null;
            $issuedAt = $payload['issued_at'] ?? null;
            
            Log::info('Token validation debug:', [
                'current_time' => $currentTime,
                'current_datetime' => Carbon::now()->toDateTimeString(),
                'expires_at' => $expiresAt,
                'expires_datetime' => $expiresAt ? Carbon::createFromTimestamp($expiresAt)->toDateTimeString() : null,
                'issued_at' => $issuedAt,
                'issued_datetime' => $issuedAt ? Carbon::createFromTimestamp($issuedAt)->toDateTimeString() : null,
                'is_expired' => $expiresAt ? ($currentTime > $expiresAt) : true,
                'time_until_expiry' => $expiresAt ? ($expiresAt - $currentTime) : null,
            ]);

            // Check if token has expired
            if (!isset($payload['expires_at']) || Carbon::now()->timestamp > $payload['expires_at']) {
                Log::warning('Token expired or missing expiry');
                return null;
            }

            // Validate required fields
            Log::info('Token payload fields:', [
                'has_admin_id' => isset($payload['admin_id']),
                'has_role' => isset($payload['role']),
                'has_office_id' => isset($payload['office_id']),
                'admin_id_value' => $payload['admin_id'] ?? 'missing',
                'role_value' => $payload['role'] ?? 'missing',
                'office_id_value' => $payload['office_id'] ?? 'missing',
                'all_keys' => array_keys($payload),
            ]);
            
            // Check required fields (office_id can be null for Super Admin)
            if (!isset($payload['admin_id']) || !isset($payload['role'])) {
                Log::warning('Token missing admin_id or role');
                return null;
            }
            
            // office_id must exist as a key (but can be null for Super Admin)
            if (!array_key_exists('office_id', $payload)) {
                Log::warning('Token missing office_id key');
                return null;
            }

            Log::info('Token validated successfully');
            return $payload;
        } catch (\Exception $e) {
            Log::error('Token validation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get token expiry time in minutes
     * 
     * @param string $token
     * @return int|null
     */
    public function getTokenExpiryMinutes($token)
    {
        $payload = $this->validateRegistrationToken($token);
        
        if (!$payload) {
            return null;
        }

        $expiresAt = Carbon::createFromTimestamp($payload['expires_at']);
        $now = Carbon::now();

        return $now->diffInMinutes($expiresAt, false);
    }
}
