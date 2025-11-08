# Embedded Python Web Server Setup for Device Bridge

## Overview
This document explains how to bundle an embedded Python web server with the Fingerprint Device Bridge installation.

## Why Embedded Python?

### Problem:
- Browser blocks `file://` protocol from accessing remote APIs (CORS)
- Cannot open local HTML files from hosted web applications
- Need to access both local fingerprint device AND remote backend

### Solution:
- Bundle portable Python with Device Bridge installer
- Start lightweight HTTP server on `http://127.0.0.1:18426`
- Serve registration page through localhost (no CORS issues)
- No Python installation required on client machines

## File Structure

```
C:\Program Files (x86)\Tresmongos\Fingerprint Device Bridge\
├── DeviceBridge.exe                    # Main application
├── python-embed\                       # Embedded Python (portable)
│   ├── python.exe
│   ├── python311.dll
│   ├── python311.zip
│   └── ...other python files (~10MB)
├── start_webserver.py                  # Web server script
├── local_registration\                 # Web files
│   ├── register.html
│   └── ...other assets
└── webserver.log                       # Server logs (auto-created)
```

## Download Embedded Python

### Option 1: Direct Download
1. Visit: https://www.python.org/downloads/windows/
2. Download: **Windows embeddable package (64-bit)**
3. Example: `python-3.11.7-embed-amd64.zip` (~10MB)
4. Extract to `python-embed` folder

### Option 2: Direct Link
```
https://www.python.org/ftp/python/3.11.7/python-3.11.7-embed-amd64.zip
```

### Option 3: Latest Version
```bash
# Using PowerShell
Invoke-WebRequest -Uri "https://www.python.org/ftp/python/3.11.7/python-3.11.7-embed-amd64.zip" -OutFile "python-embed.zip"
Expand-Archive -Path "python-embed.zip" -DestinationPath "python-embed"
```

## C# Integration Code

Add this class to your Device Bridge project:

```csharp
using System;
using System.Diagnostics;
using System.IO;
using System.Threading;

namespace FingerprintDeviceBridge
{
    public class LocalWebServer
    {
        private Process pythonProcess;
        private string pythonPath;
        private string scriptPath;
        private bool isRunning;

        public LocalWebServer()
        {
            string appDir = AppDomain.CurrentDomain.BaseDirectory;
            pythonPath = Path.Combine(appDir, "python-embed", "python.exe");
            scriptPath = Path.Combine(appDir, "start_webserver.py");
        }

        public void Start()
        {
            try
            {
                if (!File.Exists(pythonPath))
                {
                    Logger.LogError($"Embedded Python not found at: {pythonPath}");
                    return;
                }

                if (!File.Exists(scriptPath))
                {
                    Logger.LogError($"Web server script not found at: {scriptPath}");
                    return;
                }

                pythonProcess = new Process
                {
                    StartInfo = new ProcessStartInfo
                    {
                        FileName = pythonPath,
                        Arguments = $"\"{scriptPath}\"",
                        UseShellExecute = false,
                        CreateNoWindow = true,
                        RedirectStandardOutput = true,
                        RedirectStandardError = true,
                        WorkingDirectory = AppDomain.CurrentDomain.BaseDirectory
                    }
                };

                pythonProcess.OutputDataReceived += (sender, args) =>
                {
                    if (!string.IsNullOrEmpty(args.Data))
                        Logger.Log($"WebServer: {args.Data}");
                };

                pythonProcess.ErrorDataReceived += (sender, args) =>
                {
                    if (!string.IsNullOrEmpty(args.Data))
                        Logger.LogError($"WebServer Error: {args.Data}");
                };

                pythonProcess.Start();
                pythonProcess.BeginOutputReadLine();
                pythonProcess.BeginErrorReadLine();

                isRunning = true;
                Logger.Log("Local Web Server started on http://127.0.0.1:18426");

                Thread.Sleep(1000);
            }
            catch (Exception ex)
            {
                Logger.LogError($"Failed to start Local Web Server: {ex.Message}");
            }
        }

        public void Stop()
        {
            try
            {
                if (pythonProcess != null && !pythonProcess.HasExited)
                {
                    pythonProcess.Kill();
                    pythonProcess.WaitForExit(3000);
                    pythonProcess.Dispose();
                    Logger.Log("Local Web Server stopped");
                }
                isRunning = false;
            }
            catch (Exception ex)
            {
                Logger.LogError($"Error stopping Local Web Server: {ex.Message}");
            }
        }

        public bool IsRunning()
        {
            return isRunning && pythonProcess != null && !pythonProcess.HasExited;
        }
    }
}
```

## Integrate with Windows Service

```csharp
public class DeviceBridgeService : ServiceBase
{
    private LocalWebServer webServer;

    protected override void OnStart(string[] args)
    {
        Logger.Log("Device Bridge Service starting...");
        
        // Start Device Bridge API (port 18420)
        // ...your existing code...
        
        // Start Local Web Server (port 18426)
        webServer = new LocalWebServer();
        webServer.Start();
        
        Logger.Log("All services started successfully");
    }

    protected override void OnStop()
    {
        Logger.Log("Device Bridge Service stopping...");
        
        // Stop Local Web Server
        webServer?.Stop();
        
        // Stop Device Bridge API
        // ...your existing code...
        
        Logger.Log("All services stopped");
    }
}
```

## Installer Configuration

### Inno Setup Example:

```ini
[Files]
; Embedded Python
Source: "python-embed\*"; DestDir: "{app}\python-embed"; Flags: ignoreversion recursesubdirs

; Web server script
Source: "start_webserver.py"; DestDir: "{app}"; Flags: ignoreversion

; Local registration files
Source: "local_registration\*"; DestDir: "{app}\local_registration"; Flags: ignoreversion recursesubdirs

[Run]
; No additional installation steps needed!
```

### NSIS Example:

```nsis
Section "Install"
    SetOutPath "$INSTDIR"
    
    ; Copy embedded Python
    File /r "python-embed"
    
    ; Copy web server script
    File "start_webserver.py"
    
    ; Copy local registration files
    File /r "local_registration"
SectionEnd
```

## Testing

### Manual Test:
1. Open Command Prompt in installation directory
2. Run: `python-embed\python.exe start_webserver.py`
3. Open browser: `http://127.0.0.1:18426/register.html`
4. Should see registration page
5. Press Ctrl+C to stop

### Automated Test:
1. Start Device Bridge service
2. Check `webserver.log` for startup message
3. Test URL: `http://127.0.0.1:18426/register.html`
4. Stop Device Bridge service
5. Verify server stops cleanly

## Troubleshooting

### Server won't start
- Check `webserver.log` for errors
- Verify `python-embed\python.exe` exists
- Verify `start_webserver.py` exists
- Check if ports 18426-18430 are available
- Run manually to see error messages

### CORS errors
- Verify `Access-Control-Allow-Origin: *` header in response
- Check browser console for specific error
- Ensure server is running on `127.0.0.1` (not `localhost`)

### Can't access from hosted app
- Verify server is running: `http://127.0.0.1:18426/register.html`
- Check Windows Firewall (should allow localhost)
- Test from same machine as Device Bridge

### Port already in use
- Server automatically tries ports 18426-18430
- Check `webserver.log` for actual port used
- Update backend code if different port is used

## Advantages

✅ **No Installation Required** - Python is embedded, no setup needed
✅ **Portable** - Everything is self-contained in one folder
✅ **Small Size** - Only ~10MB additional installer size
✅ **Automatic** - Starts with Device Bridge service
✅ **No Conflicts** - Won't interfere with system Python
✅ **Cross-Version** - Works on any Windows version
✅ **Offline** - No internet required after installation
✅ **Easy Updates** - Just replace files, no uninstall/reinstall

## Size Impact

- Embedded Python: ~10 MB
- start_webserver.py: ~3 KB
- **Total additional size: ~10 MB**

This is minimal compared to the benefits of zero-configuration deployment!

## Support

If you encounter issues:
1. Check `webserver.log` in installation directory
2. Test manual start: `python-embed\python.exe start_webserver.py`
3. Verify file structure matches documentation
4. Check Windows Event Viewer for service errors
