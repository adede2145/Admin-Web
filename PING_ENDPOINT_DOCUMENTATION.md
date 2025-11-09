# Local Registration Server Ping Detection

This document explains how the ping detection system works for the local fingerprint registration server.

## Overview

The system allows the hosted Laravel app (on Linode) to detect if the local fingerprint registration server is accessible before attempting to open the registration page. This prevents CORS errors and provides a better user experience.

## How It Works

### 1. **Client-Side Detection (Browser-Based)**

When a user clicks "Register Employee" in the hosted Laravel app, the browser performs a health check to the local server:

```javascript
// Check if local server is running at http://127.0.0.1:18426
const response = await fetch('http://127.0.0.1:18426/ping', {
    method: 'GET',
    cache: 'no-cache'
});
```

### 2. **Ping Endpoints**

The local server can respond through multiple endpoints:

#### Option A: `register.html?ping=true`
- URL: `http://127.0.0.1:18426/register.html?ping=true`
- The register.html page detects the `ping=true` parameter and displays a simple status page
- Returns HTTP 200 with a visual confirmation

#### Option B: `ping.html` (Dedicated Endpoint)
- URL: `http://127.0.0.1:18426/ping.html`
- A dedicated lightweight page just for health checks
- Returns HTTP 200 with status information

#### Option C: Fallback to Device Bridge
- URL: `http://127.0.0.1:18426/api/health/ping`
- Uses the Device Bridge's existing health endpoint
- Returns HTTP 200 with JSON response

### 3. **Response Flow**

```
User clicks "Register Employee"
    ↓
Browser pings local server (127.0.0.1:18426)
    ↓
    ├─ Server responds (HTTP 200)
    │   ↓
    │   Generate token from Laravel
    │   ↓
    │   Open registration page in new tab
    │   (http://127.0.0.1:18426/register.html?token=...&backend=...)
    │
    └─ Server doesn't respond (timeout/error)
        ↓
        Show modal: "Registration Server Not Running"
        ↓
        Provide instructions to start Device Bridge
```

## Implementation Details

### In `register.html`

Added at the beginning of the JavaScript section:

```javascript
// PING ENDPOINT HANDLER
(function() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('ping') === 'true') {
        // Display status page
        document.title = 'PING_OK';
        
        // Add verification meta tag
        const meta = document.createElement('meta');
        meta.name = 'local-registration-status';
        meta.content = 'active';
        document.head.appendChild(meta);
        
        // Show status message
        document.body.innerHTML = `... status page HTML ...`;
        
        // Stop further execution
        throw new Error('PING_OK');
    }
})();
```

### In `theme.blade.php` (Laravel Layout)

The existing implementation in the "Register Employee" button handler:

```javascript
async function checkLocalServerHealth() {
    return new Promise((resolve) => {
        let resolved = false;
        const timeout = setTimeout(() => {
            if (!resolved) {
                resolved = true;
                resolve(false);
            }
        }, 3000); // 3 second timeout

        // Try HTTP fetch request
        fetch('http://127.0.0.1:18426/ping', {
            method: 'GET',
            cache: 'no-cache'
        })
        .then(response => {
            clearTimeout(timeout);
            if (!resolved) {
                resolved = true;
                resolve(response.ok);
            }
        })
        .catch(error => {
            clearTimeout(timeout);
            if (!resolved) {
                resolved = true;
                resolve(false); // Server not accessible
            }
        });
    });
}
```

## Testing the Ping Endpoint

### Test 1: Direct Browser Access
1. Start your Device Bridge/local server
2. Open browser and navigate to: `http://127.0.0.1:18426/register.html?ping=true`
3. You should see a green status page saying "Registration Server Active"

### Test 2: From JavaScript Console
```javascript
// In browser console:
fetch('http://127.0.0.1:18426/register.html?ping=true')
    .then(r => console.log('Server is running:', r.ok))
    .catch(e => console.log('Server not accessible:', e));
```

### Test 3: Using cURL (Command Line)
```bash
curl -v http://127.0.0.1:18426/register.html?ping=true
# Should return HTTP 200 with HTML content
```

## Error Handling

### If Ping Fails:
1. Modal appears with error message
2. Instructions provided to user:
   - Ensure Device Bridge is installed
   - Launch the Device Bridge application
   - Wait for registration server to start
   - Check firewall settings for port 18426
   - Try again

### If Ping Succeeds:
1. Token is generated from Laravel backend
2. Registration page opens in new tab with token and backend URL
3. User can proceed with fingerprint registration

## Port Configuration

- Default port: **18426**
- Fallback ports checked by bridge: 18420, 18421, 18422, 18423, 18424
- All endpoints must be accessible at `http://127.0.0.1:[PORT]`

## Security Considerations

1. **Local-Only Access**: The ping endpoint only works on localhost (127.0.0.1)
2. **No Sensitive Data**: Ping endpoint doesn't expose any sensitive information
3. **Token-Based Auth**: Actual registration still requires a valid token from Laravel backend
4. **Time-Limited Tokens**: Tokens expire after use or timeout

## Advantages of This Approach

✅ **No CORS Issues**: Ping is done from browser to localhost
✅ **Better UX**: User gets immediate feedback if server isn't running
✅ **Clear Instructions**: Modal provides steps to fix the issue
✅ **Automatic Detection**: No manual configuration needed
✅ **Fallback Support**: Multiple detection methods (HTTP, image loading)
✅ **Works with Hosted App**: Hosted Laravel app can safely check local server

## Files Modified

1. `public/local_registration/register.html` - Added ping handler
2. `public/local_registration/ping.html` - New dedicated ping endpoint
3. `resources/views/layouts/theme.blade.php` - Already has ping detection (no changes needed)

## Troubleshooting

### Ping not working?

**Check 1**: Is the Device Bridge running?
```bash
# Should see the service running on port 18426
```

**Check 2**: Firewall blocking the port?
```bash
# Windows: Check Windows Defender Firewall
# Allow inbound connections on port 18426
```

**Check 3**: Wrong port?
```javascript
// Check if bridge is running on a different port
// Try: http://127.0.0.1:18420, 18421, 18422, etc.
```

**Check 4**: Browser cache?
```javascript
// The fetch uses cache: 'no-cache' but try clearing browser cache
```

## Future Enhancements

- [ ] Add WebSocket support for real-time status
- [ ] Store preferred port in localStorage
- [ ] Auto-reconnect if server restarts
- [ ] Show server version in ping response
- [ ] Add health metrics (uptime, last scan time, etc.)
