# Token-Based Authentication - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Verify Files Are In Place
All files have been created/updated:
- ✅ `app/Services/TokenService.php` - Token generation and validation
- ✅ `app/Http/Controllers/Api/RegistrationTokenController.php` - API endpoints
- ✅ `routes/api.php` - API routes added
- ✅ `resources/views/layouts/theme.blade.php` - Sidebar button updated
- ✅ `public/local_registration/register.html` - Token handling added

### Step 2: Test the System

#### Option A: Manual Testing (Recommended)
1. **Start your Laravel server**
   ```bash
   php artisan serve
   # Or if using XAMPP, just access http://localhost/Admin-Web/public
   ```

2. **Login to Admin Panel**
   - Go to: `http://localhost/Admin-Web/public/login`
   - Login with admin or super_admin account

3. **Click "Local Fingerprint Station" in Sidebar**
   - Should automatically generate token
   - Opens local registration page with token in URL
   - Offices should load automatically

4. **Verify RBAC**
   - As **Super Admin**: See all offices
   - As **Admin**: See only your office

#### Option B: Automated Testing
1. **Open Test Suite**
   ```
   http://localhost/Admin-Web/public/test-token-auth.html
   ```

2. **Get Your Sanctum Token**
   - Login to admin panel
   - Open Browser DevTools (F12)
   - Go to: Application → Cookies
   - Copy the value of `laravel_session` or `XSRF-TOKEN`

3. **Run Tests**
   - Paste your Sanctum token
   - Click "Run Complete Test"
   - All tests should pass ✅

### Step 3: Production Deployment

1. **Update Backend URL** in `theme.blade.php`:
   ```javascript
   const localUrl = 'http://YOUR-LOCAL-MACHINE-IP/Admin-Web/public/local_registration/register.html';
   ```

2. **Update Default Backend** in `register.html`:
   ```html
   <input type="text" id="backendApiUrl" value="https://your-production-domain.com">
   ```

3. **Configure CORS** (if needed) in `config/cors.php`:
   ```php
   'paths' => ['api/*'],
   'allowed_origins' => ['http://localhost', 'http://192.168.*'],
   ```

## 📋 How It Works

### Flow Diagram
```
User clicks "Local Fingerprint Station"
    ↓
Backend generates encrypted token (5 min expiry)
    ↓
Opens local page: register.html?token=abc123...
    ↓
Local page validates token with backend
    ↓
Backend returns admin info (name, role, office)
    ↓
Local page fetches offices based on role
    ↓
User registers employee with fingerprint
```

## 🔐 Security Features

- ✅ **Encrypted Tokens**: AES-256 encryption via Laravel Crypt
- ✅ **Short Expiry**: 5-minute validity (configurable)
- ✅ **RBAC Enforced**: Role-based office filtering
- ✅ **No Password in URL**: Token is encrypted payload
- ✅ **Server-side Validation**: All token checks happen on backend

## 🧪 Testing Checklist

- [ ] Token generates when clicking sidebar button
- [ ] Local page opens with token in URL
- [ ] Token auto-validates on page load
- [ ] Offices load based on admin role
- [ ] Super admin sees all offices
- [ ] Regular admin sees only their office
- [ ] Token expires after 5 minutes
- [ ] Expired token shows error message
- [ ] Employee registration works with token auth

## 🐛 Troubleshooting

### Token Not Generating
**Problem**: Clicking button shows error or no token  
**Solution**:
- Check if logged in as admin/super_admin
- Verify `auth:sanctum` middleware is working
- Check Laravel logs: `storage/logs/laravel.log`

### Token Validation Fails
**Problem**: "Invalid or expired token" message  
**Solution**:
- Check if APP_KEY is set in `.env`
- Verify token hasn't expired (5 minutes)
- Check backend URL is correct
- Verify Laravel encryption is working

### Offices Not Loading
**Problem**: Dropdown shows "Connection failed"  
**Solution**:
- Check backend API is accessible
- Verify token is being sent in Authorization header
- Check admin has department_id assigned
- Review browser console for CORS errors

### CORS Errors (Different Domains)
**Problem**: "CORS policy blocked" in console  
**Solution**:
Update `config/cors.php`:
```php
'paths' => ['api/*'],
'allowed_origins' => ['*'], // Or specific origins
'allowed_methods' => ['*'],
'allowed_headers' => ['*'],
```

## 📞 API Endpoints Quick Reference

### 1. Generate Token (Requires Auth)
```http
POST /api/generate-token
Authorization: Bearer {sanctum_token}

Response: { "success": true, "token": "...", "expires_in": 5 }
```

### 2. Validate Token (Public)
```http
POST /api/validate-token
Authorization: Bearer {registration_token}
Body: { "token": "..." }

Response: { "success": true, "admin": {...} }
```

### 3. Get Offices (Public with Token)
```http
GET /api/offices?token={registration_token}
Authorization: Bearer {registration_token}

Response: { "success": true, "offices": [...], "admin_role": "..." }
```

## 🎯 Next Steps

1. ✅ Test locally (this guide)
2. ✅ Verify RBAC works correctly
3. ✅ Test token expiration
4. ⏭️ Deploy to production
5. ⏭️ Update URLs for production
6. ⏭️ Configure HTTPS
7. ⏭️ Add rate limiting (optional)
8. ⏭️ Add audit logging (optional)

## 📚 Documentation

- **Full Documentation**: `TOKEN_AUTH_DOCUMENTATION.md`
- **Test Suite**: `public/test-token-auth.html`
- **Local Registration**: `public/local_registration/register.html`

## 💡 Tips

- **Token Expiry**: Adjust in `TokenService.php` or controller
- **URL Configuration**: Stored in localStorage for convenience
- **Testing**: Use test suite for automated verification
- **Production**: Always use HTTPS for hosted backend
- **Security**: Consider shorter expiry time for high-security environments

## ✨ Features

✅ No separate login required  
✅ Secure token-based auth  
✅ RBAC enforced  
✅ Auto-office loading  
✅ 5-minute token expiry  
✅ One-click access  
✅ Works with existing fingerprint system  

---

**Need Help?** Check `TOKEN_AUTH_DOCUMENTATION.md` for detailed information.
