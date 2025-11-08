# Local Registration Integration - Changes Summary

## Overview
Successfully configured the system to allow local registration page (served by embedded Python web server on port 18426) to communicate with the hosted backend application.

## Architecture

### Components:
1. **Hosted Web App** - Laravel application (your domain/IP)
2. **Fingerprint Device Bridge** - C# Windows Service (port 18420) - handles fingerprint device
3. **Local Web Server** - Embedded Python HTTP server (port 18426) - serves registration page
4. **Registration Page** - HTML/JavaScript page for employee registration and fingerprint capture

### Flow:
```
User clicks "Register Employee" 
    → Backend generates token 
    → Opens http://127.0.0.1:18426/register.html?token=xyz&backend=https://yourapp.com
    → Page communicates with:
        - Device Bridge (127.0.0.1:18420) for fingerprints
        - Backend API for saving data
```

## Changes Made

### 1. API Route Added (`routes/api.php`)
- Added `/api/generate-token` endpoint for generating temporary authentication tokens
- Route is protected by Sanctum authentication (requires logged-in admin)

### 2. New Controller (`app/Http/Controllers/Api/TokenController.php`)
- Created `TokenController` to handle token generation
- Generates 64-character random tokens
- Stores tokens in cache for 10 minutes with admin details
- Returns token to client for use in local registration page

### 3. CORS Middleware (`app/Http/Middleware/Cors.php`)
- Created custom CORS middleware to handle file:// protocol requests
- Allows local HTML files to communicate with backend API
- Handles preflight OPTIONS requests
- Sets appropriate CORS headers for cross-origin requests

### 4. Kernel Update (`app/Http/Kernel.php`)
- Registered CORS middleware in API middleware group
- Ensures all API routes support cross-origin requests

### 5. CORS Config Update (`config/cors.php`)
- Changed `supports_credentials` from `false` to `true`
- Allows cookies and authentication headers in CORS requests

### 6. Theme Layout Update (`resources/views/layouts/theme.blade.php`)
- Updated "Register Employee" button click handler
- Now generates token from backend before opening local registration
- Opens local file with `file://` protocol
- Passes both `token` and `backend` URL parameters
- Example: `file:///C:/Program Files.../register.html?token=xyz&backend=http://yourapp.com`

### 7. Local Registration Files Updated
Files updated:
- `C:\Program Files (x86)\Tresmongos\Fingerprint Device Bridge\local_registration\register.html`
- `public/local_registration/register.html`
- `resources/views/employees/register.html`

Changes:
- `getBackendUrl()` now reads backend URL from URL parameter
- Priority: URL parameter > localStorage > hardcoded default
- Stores backend URL in localStorage for persistence
- Automatically uses the correct backend based on where it was opened from

## How It Works

### Flow:
1. **User clicks "Register Employee"** on hosted web app
2. **Backend generates token** via `/api/generate-token` API
3. **Token stored in cache** for 10 minutes with admin details
4. **Opens local web server** using `http://127.0.0.1:18426/register.html` with parameters:
   - `token`: Authentication token
   - `backend`: Backend URL (e.g., `http://localhost/Admin-Web/public` or `https://yourapp.com`)
5. **Local registration page**:
   - Reads token and backend URL from URL parameters
   - Connects to local fingerprint bridge (127.0.0.1:18420)
   - Sends registration data to backend using the token
   - Backend validates token and processes registration

### For Edit Fingerprints:
1. **User clicks "Edit Fingerprints"** on employee details
2. **Backend generates token** (60 minute expiry for edit mode)
3. **Redirects to** `http://127.0.0.1:18426/register.html` with parameters:
   - `mode=edit`
   - `employee_id`: Employee ID to edit
   - `token`: Authentication token
   - `backend`: Backend URL

### Security:
- Tokens expire after 10 minutes
- Tokens are single-use (can be configured)
- CORS middleware validates origins
- Sanctum authentication protects token generation endpoint

### Deployment:
- Works on localhost and production
- Backend URL automatically detected from where the button was clicked
- No hardcoded URLs in local registration file
- Supports multiple environments (dev, staging, production)

## Testing Instructions

### After pulling changes:

1. **Clear caches:**
```bash
php artisan route:clear
php artisan cache:clear
php artisan config:clear
php artisan optimize
```

2. **Test locally:**
   - Login to admin panel
   - Click "Register Employee" in sidebar
   - Should open local file from Fingerprint Device Bridge installation
   - Check browser console for connection logs

3. **Test on hosted server:**
   - Deploy changes to production
   - Login to admin panel
   - Click "Register Employee"
   - Should open local file with production backend URL

4. **Verify token generation:**
   - Open browser DevTools > Network tab
   - Click "Register Employee"
   - Should see POST request to `/api/generate-token`
   - Should return `{success: true, token: "...", expires_in: 600}`

## Troubleshooting

### "Failed to generate token: Route not found"
- Run: `php artisan route:clear`
- Check: `php artisan route:list | grep generate-token`

### "CORS policy error"
- Verify CORS middleware is registered in Kernel.php
- Check browser console for specific CORS error
- Ensure `supports_credentials: true` in cors.php

### "Token validation failed"
- Check token hasn't expired (10 minutes)
- Verify cache is working: `php artisan cache:clear`
- Check backend URL is correct in browser console

### Local file won't open
- Verify file path: `C:\Program Files (x86)\Tresmongos\Fingerprint Device Bridge\local_registration\register.html`
- Check browser console for file:// protocol errors
- Some browsers block file:// by default - try Chrome/Edge

## Files Modified
1. `routes/api.php` - Added token generation endpoint
2. `app/Http/Controllers/Api/TokenController.php` (new) - Token generation logic
3. `app/Http/Middleware/Cors.php` (new) - CORS middleware for cross-origin requests
4. `app/Http/Kernel.php` - Registered CORS middleware
5. `config/cors.php` - Enabled credentials support
6. `resources/views/layouts/theme.blade.php` - Updated to open http://127.0.0.1:18426
7. `app/Http/Controllers/EmployeeController.php` - Updated edit fingerprints redirect
8. `public/local_registration/register.html` - Updated to accept backend URL parameter
9. `resources/views/employees/register.html` - Updated to accept backend URL parameter
10. `start_webserver.py` (new) - Python script for local web server (port 18426)

## Embedded Python Web Server

### Why Port 18426?
- Port 18420: Fingerprint Device Bridge API
- Port 18426: Local Registration Web Server

### Python Script Features:
- ✅ Serves files from `local_registration` folder
- ✅ CORS enabled for remote backend communication
- ✅ Automatic port fallback (18426-18430)
- ✅ Logging to `webserver.log`
- ✅ Handles GET, POST, OPTIONS requests
- ✅ No external dependencies (uses Python stdlib)

### Installation with Device Bridge:
Copy `start_webserver.py` to Device Bridge installation folder:
```
C:\Program Files (x86)\Tresmongos\Fingerprint Device Bridge\
├── DeviceBridge.exe
├── python-embed\
│   └── python.exe (embedded Python)
├── start_webserver.py (this file)
├── local_registration\
│   └── register.html
└── webserver.log (created automatically)
```

## Notes
- Local registration file must be installed with Fingerprint Device Bridge
- Backend URL is automatically detected from hosted app
- Tokens are temporary and expire after 10 minutes
- CORS is configured to allow file:// protocol communication
