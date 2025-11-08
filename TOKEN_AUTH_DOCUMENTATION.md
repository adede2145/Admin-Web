# Token-Based Authentication for Local Registration Page

## Overview
This implementation provides secure, token-based authentication for the local fingerprint registration page, eliminating the need for separate login while maintaining RBAC (Role-Based Access Control).

## Architecture

### Flow Diagram
```
Hosted Admin Panel → Generate Token → Open Local Page with Token → Validate Token → Load Office Data → Register Employee
```

## Components

### 1. Backend Services

#### TokenService (`app/Services/TokenService.php`)
- **Purpose**: Generate and validate encrypted tokens
- **Token Contents**:
  - Admin ID
  - Role (admin/super_admin)
  - Office ID
  - Name and email
  - Expiration timestamp (matches Laravel session lifetime - default 2 hours)
  - Issued timestamp

**Methods**:
- `generateRegistrationToken($admin, $expiryMinutes = 5)` - Creates encrypted token
- `validateRegistrationToken($token)` - Validates and decodes token
- `getTokenExpiryMinutes($token)` - Gets remaining token validity

#### RegistrationTokenController (`app/Http/Controllers/Api/RegistrationTokenController.php`)
- **Purpose**: Handle token generation and validation via API

**Endpoints**:
1. `POST /api/generate-token` (authenticated)
   - Generates token for logged-in admin
   - Returns token with expiry matching session lifetime (default 2 hours)

2. `POST /api/validate-token` (public)
   - Validates token from local page
   - Returns admin details if valid

3. `GET /api/offices` (public with token)
   - Returns offices based on RBAC
   - Super Admin: All offices
   - Admin: Only their office

### 2. Frontend Integration

#### Sidebar Button (`resources/views/layouts/theme.blade.php`)
- Button: "Local Fingerprint Station"
- Action:
  1. Calls `/api/generate-token`
  2. Receives encrypted token
  3. Opens local page with token in URL
  4. Example: `http://localhost/.../register.html?token=abc123...`

#### Local Registration Page (`public/local_registration/register.html`)
- **Auto-detection**: Extracts token from URL parameter
- **Token Validation**: Validates with backend on page load
- **Office Loading**: Fetches offices based on admin role
- **Session Persistence**: Stores token in localStorage (optional)

## Security Features

### Token Security
1. **Encryption**: Uses Laravel's `Crypt` facade with AES-256
2. **Session-Based Expiry**: Token valid for same duration as admin session (default 2 hours)
3. **One-Time Use Recommended**: Token validates but doesn't invalidate (stateless)
4. **Secure Transmission**: Base64-encoded encrypted payload

### RBAC Enforcement
1. **Role Validation**: Token contains role information
2. **Office Filtering**: 
   - Super Admin sees all offices
   - Regular Admin sees only their office
3. **Backend Validation**: All API calls validate token server-side

### Best Practices Implemented
- ✅ No passwords in URL
- ✅ Encrypted token payload
- ✅ Short expiration time
- ✅ Server-side validation
- ✅ HTTPS recommended for production
- ✅ Token stored in memory (URL) and optionally localStorage

## API Endpoints

### 1. Generate Token
```http
POST /api/generate-token
Authorization: Bearer {sanctum-token}
Content-Type: application/json

Response:
{
  "success": true,
  "token": "eyJpdiI6Ij...",
  "expires_in": 5,
  "message": "Token generated successfully"
}
```

### 2. Validate Token
```http
POST /api/validate-token
Content-Type: application/json
Authorization: Bearer {registration-token}

Body:
{
  "token": "eyJpdiI6Ij..."
}

Response:
{
  "success": true,
  "admin": {
    "admin_id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "role": "admin",
    "office_id": 2
  },
  "message": "Token is valid"
}
```

### 3. Get Offices (with RBAC)
```http
GET /api/offices?token={registration-token}
Authorization: Bearer {registration-token}

Response:
{
  "success": true,
  "offices": [
    {
      "id": 1,
      "name": "Main Office"
    },
    {
      "id": 2,
      "name": "Branch Office"
    }
  ],
  "admin_role": "super_admin"
}
```

## Testing Guide

### Local Testing (Development)

1. **Start Laravel Development Server**
   ```bash
   cd c:\xampp\htdocs\Admin-Web
   php artisan serve
   ```

2. **Login to Admin Panel**
   - Navigate to: `http://localhost:8000/login`
   - Login with admin or super_admin credentials

3. **Click "Local Fingerprint Station"**
   - Button in sidebar
   - Should open: `http://localhost/Admin-Web/public/local_registration/register.html?token=...`

4. **Verify Token Auto-Load**
   - Check browser console for token validation
   - Office dropdown should populate automatically
   - Should see welcome message with your name

5. **Test RBAC**
   - **As Super Admin**: Should see all offices in dropdown
   - **As Regular Admin**: Should see only your assigned office

### Production Testing

1. **Update URLs in Code**
   - In `theme.blade.php`: Update `localUrl` to your production URL
   - In `register.html`: Set default `backendApiUrl` to production

2. **Test Token Generation**
   ```bash
   # Via browser console after clicking button
   console.log('Token:', new URLSearchParams(window.location.search).get('token'));
   ```

3. **Test Token Validation**
   ```bash
   # Via curl
   curl -X POST https://your-domain.com/api/validate-token \
     -H "Content-Type: application/json" \
     -d '{"token": "your-token-here"}'
   ```

4. **Test Office Fetching**
   ```bash
   curl -X GET "https://your-domain.com/api/offices?token=your-token-here" \
     -H "Authorization: Bearer your-token-here"
   ```

## Troubleshooting

### Token Not Generating
- Check if admin is authenticated (`auth:sanctum`)
- Verify admin role is 'admin' or 'super_admin'
- Check browser console for errors

### Token Validation Failing
- Token may have expired (matches session expiry - default 2 hours)
- Check Laravel logs: `storage/logs/laravel.log`
- Verify APP_KEY is set in `.env`

### Offices Not Loading
- Check token is being sent in Authorization header
- Verify department_id exists for admin
- Check CORS settings if hosted on different domain

### CORS Issues (Production)
Add to `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['http://localhost', 'https://your-local-ip'],
'allowed_headers' => ['*'],
'supports_credentials' => true,
```

## Configuration

### Token Expiry Time
Modify in `config/session.php` to change session and token lifetime:
```php
'lifetime' => env('SESSION_LIFETIME', 120), // Default 120 minutes (2 hours)
```

Or modify in controller for a different token expiry:
```php
$token = $this->tokenService->generateRegistrationToken($admin, 240); // 4 hours
```

### Local Page URL
Update in `theme.blade.php`:
```javascript
const localUrl = 'http://your-local-machine-ip/Admin-Web/public/local_registration/register.html';
```

### Backend URL (Local Page)
Update default in `register.html`:
```javascript
value="https://your-production-domain.com"
```

## Security Considerations

### Production Deployment
1. **Use HTTPS**: Always use HTTPS for hosted backend
2. **Session Lifetime**: Default 2 hours is reasonable, adjust in `config/session.php` if needed
3. **IP Whitelisting**: Restrict API access to known IPs
4. **Rate Limiting**: Add rate limiting to token generation
5. **Audit Logging**: Log all token generations and validations

### Token Storage
- **URL Parameter**: Temporary, cleared on page close
- **localStorage**: Persists across sessions (use with caution)
- **Recommendation**: Clear token after successful registration

## Advantages of This Approach

✅ **No Separate Login**: Admins don't need to login twice  
✅ **Secure**: Encrypted tokens with session-based expiry  
✅ **RBAC Enforced**: Role-based office filtering  
✅ **Stateless**: No server-side session management  
✅ **User-Friendly**: One-click access from admin panel  
✅ **Flexible**: Works with local and remote registration pages  
✅ **Long-Lived**: Token lasts as long as admin session (default 2 hours)  

## Future Enhancements

1. **Token Revocation**: Add token blacklist for one-time use
2. **WebSocket Notifications**: Real-time status updates
3. **QR Code Generation**: Generate QR code with token for mobile access
4. **Multi-Device Support**: Allow token use on multiple devices
5. **Extended Validation**: Add device fingerprinting

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Verify database connections
4. Check CORS and firewall settings
