<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'AUTO AUDIT') }}</title>
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
                <a class="nav-link {{ request()->is('reports*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-graph-up"></i> Reports</a>
                <a class="nav-link {{ request()->is('employees') ? 'active' : '' }}" href="{{ route('employees.index') }}"><i class="bi bi-people"></i> Employees</a>
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
                    <a class="nav-link {{ request()->is('departments*') ? 'active' : '' }}" href="{{ route('departments.index') }}"><i class="bi bi-building"></i> Manage Departments</a>
                @endif
            </nav>
        </aside>
        <main class="aa-content">
        <div class="container-fluid py-3">
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
</script>
</body>
</html>


