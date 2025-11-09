<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'AUTO AUDIT') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --aa-maroon:#560000; --aa-maroon-dark:#3a0000; --aa-yellow:#ffc107; --aa-sidebar:#450000; }
        body { background:#d1d5db; }
        .aa-app { min-height:100vh; display:flex; flex-direction:column; }
        .aa-main-content { display:flex; flex:1; }
        /* Sidebar: widened */
        .aa-sidebar { width:300px; background:var(--aa-sidebar); color:#fff; position:sticky; top:0; height:calc(100vh - 56px); box-shadow: 2px 0 8px rgba(0,0,0,0.15); }
        .aa-topbar .logo { font-weight:700; letter-spacing:.5px; font-size:1.35rem; color:#fff; }
        .aa-topbar .logo i { font-size:1.5rem; }
        /* Sidebar nav: icon beside text (row), slightly right-shifted for center appearance, yellow icons */
        .aa-nav .nav-link { color:#ffdfe0; padding:.75rem 1.5rem .75rem 1rem; width:100%; display:flex; align-items:center; justify-content:flex-start; flex-direction:row; text-align:left; gap:.5rem; font-weight:400; line-height:1.2; }
        .aa-nav .nav-link i { color: var(--aa-yellow); font-size:1.25rem; margin-right:.25rem; }
        .aa-nav .nav-link.active, .aa-nav .nav-link:hover { background:#cc0000; color:#fff; }
        .aa-content { flex:1; display:flex; flex-direction:column; min-width:0; height:calc(100vh - 56px); overflow-y:auto; }
        .aa-topbar { height:56px; background:var(--aa-maroon); color:#fff; border-bottom:1px solid #3d0a0a; display:flex; align-items:center; padding:0 1rem; gap:1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        /* Search bar: slightly longer and modern (less rounded) */
        .aa-search { flex:1; max-width:560px; }
        .aa-search .input-group { border-radius:8px; overflow:hidden; background:#ffffff; border:1px solid rgba(255,255,255,.25); box-shadow: inset 0 0 0 1px rgba(0,0,0,0.03), 0 1px 2px rgba(0,0,0,.04); }
        .aa-search .input-group .form-control { height:36px; padding:.375rem .75rem; font-size:.95rem; border:0; background:transparent; color:#212529; }
        .aa-search .input-group .form-control::placeholder { color:#6c757d; }
        .aa-search .input-group .input-group-text { height:36px; padding:.375rem .5rem; font-size:1rem; border:0; background:transparent; color:#6c757d; }
        .aa-card { background:#fff; border:1px solid #e5e7eb; border-radius:.5rem; box-shadow: 0 8px 32px rgba(0,0,0,0.15), 0 2px 8px rgba(0,0,0,0.1); }
        .aa-card .card-body { padding: 1.5rem; }
        .badge-aa { background:var(--aa-yellow); color:#3d0a0a; }
        /* Card headers: taller color bars, larger titles and icons */
        .header-yellow { background:var(--aa-yellow) !important; color:#3d0a0a !important; }
        .header-maroon { background:var(--aa-maroon) !important; color:#fff !important; }
        .aa-card .card-header { padding:.9rem 1.25rem; min-height:56px; display:flex; align-items:center; }
        /* Table header styling with transparent background and red icons */
        .table thead th { background: transparent !important; color: var(--aa-maroon) !important; border-bottom: none !important; font-weight: 600; }
        .table thead th i { color: var(--aa-maroon) !important; margin-right: 0.5rem; }
        .aa-card .card-header .card-title { font-size:1.15rem; font-weight:700; }
        .aa-card .card-header .card-title i { font-size:1.25rem; }
        @media (max-width: 992px) {
            .aa-sidebar { position:fixed; transform:translateX(-100%); transition:transform .2s ease; z-index:1040; }
            .aa-sidebar.show { transform:translateX(0); }
        }
    </style>
</head>
<body>
<div class="aa-app">
    <header class="aa-topbar">
        <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
        <span class="logo d-none d-md-block"><i class="bi bi-journal-text me-2"></i>AUTO AUDIT</span>
        <div class="ms-auto d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-link text-white text-decoration-none d-flex align-items-center dropdown-toggle p-0" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="rounded-circle me-2" style="width:32px;height:32px;background:var(--aa-yellow);"></div>
                    <span class="small d-none d-sm-inline">{{ auth()->user()->username ?? 'User' }}</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li class="px-3 py-2 text-muted small">Signed in as<br><span class="fw-semibold text-dark">{{ auth()->user()->username ?? 'User' }}</span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" class="px-3 py-1">
                            @csrf
                            <button type="submit" class="dropdown-item d-flex align-items-center"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <div class="aa-main-content">
        <aside id="aaSidebar" class="aa-sidebar">
            <nav class="aa-nav nav flex-column py-2">
                <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a class="nav-link {{ request()->is('attendance') ? 'active' : '' }}" href="{{ route('attendance.index') }}"><i class="bi bi-clock-history"></i> Attendance Log</a>
                <a class="nav-link {{ request()->is('reports*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-graph-up"></i> Generate Reports</a>
                <a class="nav-link {{ request()->is('employees') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people"></i> Manage Employees</a>
                @if(auth()->check() && auth()->user()->role && in_array(auth()->user()->role->role_name, ['admin','super_admin']))
                    <a class="nav-link" href="#" id="openLocalRegistrationBtn"><i class="bi bi-fingerprint"></i> Register Employee <i class="bi bi-box-arrow-up-right ms-1" style="font-size: 0.75rem;"></i></a>
                @endif
                <a class="nav-link {{ request()->is('audit-logs*') ? 'active' : '' }}" href="{{ route('audit.index') }}" id="recentAuditsLink">
                    <i class="bi bi-clock-history"></i> Recent Audits
                    @php
                        $admin = auth()->user();
                        $unreadAuditLogs = \App\Models\AuditLog::forAdmin($admin)
                            ->where('created_at', '>=', now()->subDays(7))
                            ->get()
                            ->filter(function($log) use ($admin) {
                                return $log->isUnreadBy($admin->admin_id);
                            });
                        $unreadCount = $unreadAuditLogs->count();
                    @endphp
                    @if($unreadCount > 0)
                        <span class="badge bg-danger text-white ms-2" id="auditBadge">{{ $unreadCount }}</span>
                    @endif
                </a>
                @if(auth()->check() && auth()->user()->role && auth()->user()->role->role_name === 'super_admin')
                    <a class="nav-link {{ request()->is('admin-panel') ? 'active' : '' }}" href="{{ route('admin.panel') }}"><i class="bi bi-shield-lock"></i> Manage Admins</a>
                    <a class="nav-link {{ request()->is('departments*') ? 'active' : '' }}" href="{{ route('departments.index') }}"><i class="bi bi-building"></i> Manage Offices</a>
                    <a class="nav-link {{ request()->is('kiosks*') ? 'active' : '' }}" href="{{ route('kiosks.index') }}"><i class="bi bi-display"></i> Manage Kiosks</a>
                @endif
            </nav>
        </aside>
        <main class="aa-content">
        <div class="container-fluid py-3">
            @include('layouts.toast')
            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Handle Recent Audits notification behavior
    document.addEventListener('DOMContentLoaded', function() {
        const recentAuditsLink = document.getElementById('recentAuditsLink');
        const auditBadge = document.getElementById('auditBadge');
        
        if (recentAuditsLink && auditBadge) {
            recentAuditsLink.addEventListener('click', function(e) {
                // Mark audits as read when clicking the link
                fetch('{{ route("audit.mark-read") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Hide the badge after marking as read
                        auditBadge.style.display = 'none';
                    }
                })
                .catch(error => console.error('Error marking audits as read:', error));
            });
        }
    });

    function toggleSidebar(){
        document.getElementById('aaSidebar').classList.toggle('show');
    }

    // Function to check if Device Bridge is running using Image technique (works with CORS)
    function checkDeviceBridge() {
        return new Promise((resolve) => {
            const img = new Image();
            const timeout = setTimeout(() => {
                img.src = ''; // Cancel the request
                resolve(false);
            }, 3000); // 3 second timeout

            img.onload = () => {
                clearTimeout(timeout);
                resolve(true);
            };

            img.onerror = () => {
                clearTimeout(timeout);
                // Check if it's a CORS error (which means server is running)
                // or a network error (server not running)
                fetch('http://127.0.0.1:18426/health', { 
                    method: 'HEAD',
                    mode: 'no-cors',
                    cache: 'no-cache'
                })
                .then(() => resolve(true))
                .catch(() => resolve(false));
            };

            // Try to load a favicon or health endpoint
            img.src = 'http://127.0.0.1:18426/favicon.ico?' + Date.now();
        });
    }

    // Alternative method: Use fetch with no-cors mode
    async function checkDeviceBridgeAlt() {
        try {
            // Use no-cors mode - if server exists, fetch won't throw error
            await fetch('http://127.0.0.1:18426/health', {
                method: 'HEAD',
                mode: 'no-cors',
                cache: 'no-cache',
                signal: AbortSignal.timeout(3000)
            });
            return true;
        } catch (error) {
            if (error.name === 'AbortError') {
                // Timeout - server might be running but slow
                return true;
            }
            return false;
        }
    }

    // Combined check using both methods
    async function isDeviceBridgeRunning() {
        try {
            // Try primary method
            const check1 = await checkDeviceBridge();
            if (check1) return true;

            // Try alternative method
            const check2 = await checkDeviceBridgeAlt();
            return check2;
        } catch (error) {
            console.log('Device Bridge check error:', error);
            return false;
        }
    }

    // Show notification modal
    function showBridgeNotification() {
        // Create modal if it doesn't exist
        let modal = document.getElementById('bridgeNotificationModal');
        if (!modal) {
            const modalHtml = `
                <div class="modal fade" id="bridgeNotificationModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header bg-danger text-white">
                                <h5 class="modal-title">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Device Bridge Not Running
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="mb-3">The Device Bridge service is not running or not reachable on your local machine.</p>
                                <p class="mb-3"><strong>Please ensure:</strong></p>
                                <ul>
                                    <li>Device Bridge application is installed on your computer</li>
                                    <li>The service is running on port 18426</li>
                                    <li>Your firewall or antivirus is not blocking port 18426</li>
                                    <li>You're accessing this from the same machine where Device Bridge is installed</li>
                                </ul>
                                <div class="alert alert-info mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Note:</strong> The Device Bridge must be running on your local machine to register employees with biometric devices.
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="retryBridgeBtn">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Retry Connection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHtml);
            modal = document.getElementById('bridgeNotificationModal');
            
            // Add retry functionality
            document.getElementById('retryBridgeBtn').addEventListener('click', async function() {
                const btn = this;
                const originalHtml = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Checking...';
                btn.disabled = true;
                
                const isRunning = await isDeviceBridgeRunning();
                
                if (isRunning) {
                    bootstrap.Modal.getInstance(modal).hide();
                    // Trigger registration again
                    document.getElementById('openLocalRegistrationBtn').click();
                } else {
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                    alert('Device Bridge is still not reachable. Please start the service and try again.');
                }
            });
        }
        
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }

    // Handle Local Registration Station button
    const localRegistrationBtn = document.getElementById('openLocalRegistrationBtn');
    if (localRegistrationBtn) {
        localRegistrationBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Show loading indicator
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Checking connection...';
            this.style.pointerEvents = 'none';
            
            try {
                // Check if Device Bridge is running
                const bridgeRunning = await isDeviceBridgeRunning();
                
                if (!bridgeRunning) {
                    // Show notification that bridge is not running
                    showBridgeNotification();
                    this.innerHTML = originalHtml;
                    this.style.pointerEvents = 'auto';
                    return;
                }
                
                // Update loading text
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Generating token...';
                
                // Get CSRF token from meta tag
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                // Generate token from backend (uses session auth, no bearer token needed)
                const response = await fetch('/api/generate-token', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin' // Important for session cookies
                });
                
                const data = await response.json();
                
                if (data.success && data.token) {
                    // Use local embedded Python web server (port 18426)
                    const backendUrl = window.location.origin;
                    
                    // Local registration server (runs alongside Device Bridge)
                    const registrationUrl = `http://127.0.0.1:18426/register.html?token=${encodeURIComponent(data.token)}&backend=${encodeURIComponent(backendUrl)}`;
                    
                    console.log('Opening local registration with:', {
                        url: registrationUrl,
                        backend: backendUrl,
                        token: data.token.substring(0, 20) + '...'
                    });
                    
                    // Open in new window
                    const newWindow = window.open(registrationUrl, '_blank', 'width=1200,height=800');
                    
                    if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                        alert('Popup blocked! Please allow popups for this site and try again.');
                    }
                    
                    // Reset button
                    setTimeout(() => {
                        this.innerHTML = originalHtml;
                        this.style.pointerEvents = 'auto';
                    }, 1000);
                } else {
                    alert('Failed to generate token: ' + (data.message || 'Unknown error'));
                    this.innerHTML = originalHtml;
                    this.style.pointerEvents = 'auto';
                }
            } catch (error) {
                console.error('Token generation error:', error);
                alert('Failed to generate token. Please try again.');
                this.innerHTML = originalHtml;
                this.style.pointerEvents = 'auto';
            }
        });
    }
</script>
</body>
</html>


