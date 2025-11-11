<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'AUTO AUDIT') }}</title>
    
    <!-- Preload critical CSS -->
    <link rel="preload" as="style" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="preload" as="style" href="{{ asset('css/bootstrap-icons-full.css') }}">
    
    <!-- Bootstrap CSS - LOCAL -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Bootstrap Icons - LOCAL (COMPLETE) -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons-full.css') }}">
    <style>
        :root { --aa-maroon:#560000; --aa-maroon-dark:#3a0000; --aa-yellow:#ffc107; --aa-sidebar:#450000; }
        body { background:#d1d5db; }
        .aa-app { min-height:100vh; display:flex; flex-direction:column; }
        .aa-main-content { display:flex; flex:1; }
        /* Sidebar: widened */
        .aa-sidebar { width:300px; background:var(--aa-sidebar); color:#fff; position:sticky; top:0; height:calc(100vh - 56px); box-shadow: 2px 0 8px rgba(0,0,0,0.15); }
        .aa-topbar .logo { font-weight:700; letter-spacing:.5px; font-size:1.35rem; color:#fff; }
        .aa-topbar .logo i { font-size:1.5rem; }
        /* Sidebar nav: icon beside text (row), uniform padding and spacing */
        .aa-nav .nav-link { color:#ffdfe0; padding:.75rem 1.5rem !important; width:100%; display:flex; align-items:center; justify-content:flex-start; flex-direction:row; text-align:left; gap:.75rem; font-weight:400; line-height:1.2; margin:0 !important; }
        .aa-nav .nav-link i { color: var(--aa-yellow); font-size:1.25rem; margin:0 !important; flex-shrink:0; width:1.25rem; }
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

<!-- Bootstrap JS Bundle - LOCAL -->
<script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>
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

    // Handle Local Registration Station button
    const localRegistrationBtn = document.getElementById('openLocalRegistrationBtn');
    if (localRegistrationBtn) {
        let registrationWindow = null; // Track the registration window
        
        localRegistrationBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            // Always show the setup reminder modal
            showSetupReminderModal(async () => {
                await openRegistrationPage();
            });

            async function openRegistrationPage() {
                try {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch('/api/generate-token', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json',
                            'X-REQUESTED-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin'
                    });

                    const data = await response.json();

                    if (data.success && data.token) {
                        const backendUrl = window.location.origin;
                        const registrationUrl = `http://127.0.0.1:18426/register.html?token=${encodeURIComponent(data.token)}&backend=${encodeURIComponent(backendUrl)}`;
                        registrationWindow = window.open(registrationUrl, '_blank');
                        
                        // If popup was blocked or failed to open, show a message
                        if (!registrationWindow || registrationWindow.closed || typeof registrationWindow.closed === 'undefined') {
                            alert('Registration window was blocked. Please ensure:\n\n1. The Device Bridge is running on your local computer\n2. Pop-up blocker is disabled for this site\n3. You allow the registration window to open');
                        }
                    } else {
                        alert('Failed to generate token: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Error generating token:', error);
                    alert('Failed to generate token. Please try again.');
                }
            }
        });
    }

    function showSetupReminderModal(continueCallback) {
        // Create simple informational modal
        const modalHtml = `
            <div class="modal fade" id="setupReminderModal" tabindex="-1" aria-labelledby="setupReminderModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-info text-white">
                            <h5 class="modal-title" id="setupReminderModalLabel">
                                <i class="bi bi-info-circle me-2"></i>Before You Continue
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-primary mb-3">
                                <h6 class="alert-heading"><i class="bi bi-fingerprint me-2"></i>Device Bridge Required</h6>
                                <p class="mb-0">The Device Bridge application must be running on <strong>your local computer</strong> to register employees using the fingerprint scanner.</p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="bi bi-check-circle-fill text-success me-2"></i>Make sure:</h6>
                                <ul class="mb-0">
                                    <li>Device Bridge is installed and running on your computer</li>
                                    <li>Fingerprint scanner is connected</li>

                                </ul>
                            </div>
                            <div class="alert alert-light border mb-0">
                                <small class="text-muted">
                                    <i class="bi bi-lightbulb me-1"></i>
                                    The registration interface will open at <code>http://127.0.0.1:18426</code>
                                </small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="continueBtn">
                                <i class="bi bi-arrow-right-circle me-2"></i>Continue
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        const existingModal = document.getElementById('setupReminderModal');
        if (existingModal) {
            existingModal.remove();
        }
        
        // Add modal to body
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        // Show modal
        const modalElement = document.getElementById('setupReminderModal');
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
        
        // Add event listener for "Continue" button
        const continueBtn = document.getElementById('continueBtn');
        if (continueBtn && continueCallback) {
            continueBtn.addEventListener('click', function() {
                modal.hide();
                continueCallback();
            });
        }
        
        // Clean up modal after it's hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }
</script>
</body>
</html>
