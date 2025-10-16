@extends('layouts.theme')

@section('title', 'Admin Panel')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold fs-2 mb-0">
            <i class="bi bi-shield-lock me-2 fs-4"></i>Admin Panel
        </h1>
        <span class="badge bg-primary fs-5">Super Admin</span>
    </div>

    <!-- Success/Error Messages REMOVED (use toast notification)-->
    {{-- Alerts removed --}}

    <div class="row align-items-stretch">
        <!-- Create Admin Form -->
        <div class="col-md-6 mb-4">
            <div class="aa-card h-100 shadow-sm">
                <div class="card-header header-maroon">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-person-plus me-2"></i>Create New Admin
                    </h4>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route('admin.users.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="bi bi-person me-2"></i>Username
                            </label>
                            <input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <span class="d-flex align-items-center">
                                    <i class="bi bi-lock me-2 text-muted"></i>
                                    Password
                                </span>
                            </label>
                            <input type="password" name="password" id="password" class="form-control" placeholder="Enter password"
                                required pattern="^[A-Z].*(?:[^A-Za-z0-9]).{0,}$" minlength="8">
                            <div class="form-text">Min 8 chars, start with capital, include a symbol.</div>
                        </div>

                        <!-- Department Filter for Employee Selection -->
                        <div class="mb-3">
                            <label for="filter_department" class="form-label">
                                <span class="d-flex align-items-center">
                                    <i class="bi bi-funnel me-2 text-muted"></i>
                                    Filter by Department
                                </span>
                            </label>
                            <select id="filter_department" class="form-select">
                                <option value="all">All Departments</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Filter employees by department</div>
                        </div>

                        <!-- Employee Selection -->
                        <div class="mb-3">
                            <label for="employee_search" class="form-label">
                                <span class="d-flex align-items-center">
                                    <i class="bi bi-person-workspace me-2 text-muted"></i>
                                    Select Employee
                                </span>
                            </label>
                            <div class="position-relative">
                                <input type="text"
                                    id="employee_search"
                                    class="form-control"
                                    placeholder="Search for employee to make admin..."
                                    autocomplete="off">
                                <input type="hidden" name="employee_id" id="selected_employee_id" required>
                                <div id="employee_search_results"
                                    class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm"
                                    style="display: none; z-index: 1000; max-height: 200px; overflow-y: auto;"></div>
                            </div>
                            <div class="form-text">
                                <span id="available_count">{{ $availableEmployees->count() }}</span> employees available (not already admins)
                            </div>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-plus-circle me-2"></i>Create Admin
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Login Attempts -->
        <div class="col-md-6 mb-4">
            <div class="aa-card h-100 shadow-sm">
                <div class="card-header header-maroon">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-shield-exclamation me-2"></i>Login Attempts (Last 24h)
                    </h5>
                </div>
                <div class="card-body p-4">
                    @php
                    $since = now()->subDay();
                    $attempts = \App\Models\AdminLoginAttempt::where('created_at', '>=', $since)
                    ->orderBy('created_at','desc')
                    ->get();
                    $grouped = $attempts->groupBy('username');
                    @endphp
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead style="background:transparent;">
                                <tr>
                                    <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-person me-1"></i>Username</th>
                                    <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-x-octagon me-1"></i>Failed</th>
                                    <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-clock-history me-1"></i>Last Attempt</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grouped as $uname => $rows)
                                <tr>
                                    <td class="py-3 px-4 fw-semibold">{{ $uname }}</td>
                                    <td class="py-3 px-4"><span class="badge bg-danger">{{ $rows->where('successful', false)->count() }}</span></td>
                                    <td class="py-3 px-4 text-muted">{{ optional($rows->first())->created_at }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-muted text-center py-3 px-4">No attempts in the last 24 hours</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Admin Stats in its own row -->
    <div class="row">
        <div class="col-12 mb-4">
            <div class="aa-card" style="min-height: 500px; box-shadow: 0 4px 24px rgba(0,0,0,0.12) !important;">
                <div class="card-header header-maroon">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>Admin Statistics
                    </h4>
                </div>
                <div class="card-body p-4">
                    @php
                    $superAdmins = collect($admins)->filter(fn($a) => $a->role->role_name === 'super_admin');
                    $deptAdmins = collect($admins)->filter(fn($a) => $a->role->role_name !== 'super_admin');
                    $latestAdmin = collect($admins)->sortByDesc('created_at')->first();
                    $longestAdmin = collect($admins)->sortBy('admin_id')->first();
                    $adminDeptCounts = collect($admins)->groupBy('username')->map(function($group) {
                    return $group->pluck('department_id')->filter()->unique()->count();
                    });
                    $mostDeptsAdmin = $adminDeptCounts->sortDesc()->keys()->first();
                    $mostDeptsCount = $adminDeptCounts->sortDesc()->first();
                    @endphp
                    <div class="row mb-4 text-center align-items-end justify-content-center g-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="aa-card p-4 h-100 d-flex flex-column align-items-center shadow-sm" style="background:#f5f6fa;">
                                <span class="display-1 fw-bold text-primary mb-1">
                                    <i class="bi bi-people me-2 text-primary" style="font-size:4rem;"></i>{{ count($admins) }}
                                </span>
                                <div class="fs-4 fw-bold text-primary mb-3">Total Admins</div>
                                <div class="bg-light rounded p-3 w-100 mt-auto">
                                    <div class="small text-muted mb-1"><i class="bi bi-person-plus me-2 text-primary"></i>Most Recent Admin</div>
                                    <div class="fs-5 fw-bold text-primary">{{ $latestAdmin->username ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="aa-card p-4 h-100 d-flex flex-column align-items-center shadow-sm" style="background:#f5f6fa;">
                                <span class="display-1 fw-bold text-danger mb-1">
                                    <i class="bi bi-shield-lock me-2 text-danger" style="font-size:4rem;"></i>{{ $superAdmins->count() }}
                                </span>
                                <div class="fs-4 fw-bold text-danger mb-3">Super Admins</div>
                                <div class="bg-light rounded p-3 w-100 mt-auto">
                                    <div class="small text-muted mb-1"><i class="bi bi-hourglass-split me-2 text-warning"></i>Longest Serving Admin</div>
                                    <div class="fs-5 fw-bold text-warning">{{ $longestAdmin->username ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="aa-card p-4 h-100 d-flex flex-column align-items-center shadow-sm" style="background:#f5f6fa;">
                                <span class="display-1 fw-bold text-warning mb-1">
                                    <i class="bi bi-building me-2 text-warning" style="font-size:4rem;"></i>{{ $deptAdmins->count() }}
                                </span>
                                <div class="fs-4 fw-bold text-warning mb-3">Department Admins</div>
                                <div class="bg-light rounded p-3 w-100 mt-auto">
                                    <div class="small text-muted mb-1"><i class="bi bi-collection me-2 text-danger"></i>Admin with Most Departments</div>
                                    <div class="fs-5 fw-bold text-danger">{{ $mostDeptsAdmin ?? 'N/A' }}@if($mostDeptsCount) <span class="fs-6 text-muted">({{ $mostDeptsCount }})</span>@endif</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @php
                    $uniqueDepartments = $deptAdmins->pluck('department_id')->unique()->filter()->count();
                    $mostCommonDeptId = $deptAdmins->pluck('department_id')->filter()->countBy()->sortDesc()->keys()->first();
                    $mostCommonDept = $departments->firstWhere('department_id', $mostCommonDeptId);
                    $adminsNoDept = $admins->filter(fn($a) => !$a->department_id)->count();
                    $since30 = now()->subDays(30);
                    $loginAttempts = \App\Models\AdminLoginAttempt::where('created_at', '>=', $since30)->get();
                    $failedAttempts = $loginAttempts->where('successful', false);
                    $mostFailed = $failedAttempts->groupBy('username')->sortByDesc(fn($g) => $g->count())->keys()->first();
                    $mostFailedCount = $failedAttempts->groupBy('username')->sortByDesc(fn($g) => $g->count())->first()?->count() ?? 0;
                    $mostAttemptsAdmin = $loginAttempts->groupBy('username')->sortByDesc(fn($g) => $g->count())->keys()->first();
                    $mostAttemptsCount = $loginAttempts->groupBy('username')->sortByDesc(fn($g) => $g->count())->first()?->count() ?? 0;
                    @endphp
                    <div class="row g-3 mt-4">
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded shadow-sm h-100 d-flex align-items-center">
                                <i class="bi bi-diagram-3 fs-2 text-warning me-3"></i>
                                <div>
                                    <div class="fw-bold fs-5">{{ $uniqueDepartments }}</div>
                                    <div class="small text-muted">Departments with Admins</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded shadow-sm h-100 d-flex align-items-center">
                                <i class="bi bi-building fs-2 text-danger me-3"></i>
                                <div>
                                    <div class="fw-bold fs-6">Most Common Dept:</div>
                                    <div class="fw-semibold">{{ $mostCommonDept?->department_name ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded shadow-sm h-100 d-flex align-items-center">
                                <i class="bi bi-person-lines-fill fs-2 text-primary me-3"></i>
                                <div>
                                    <div class="fw-bold fs-6">Most Login Attempts (30d):</div>
                                    <div class="fw-semibold">{{ $mostAttemptsAdmin ?? 'N/A' }} <span class="text-muted">({{ $mostAttemptsCount }})</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded shadow-sm h-100 d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle fs-2 text-danger me-3"></i>
                                <div>
                                    <div class="fw-bold fs-6">Most Failed Logins:</div>
                                    <div class="fw-semibold">{{ $mostFailed ?? 'N/A' }} <span class="text-muted">({{ $mostFailedCount }})</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3 bg-light rounded shadow-sm h-100 d-flex align-items-center">
                                <i class="bi bi-fingerprint fs-2 text-warning me-3"></i>
                                <div>
                                    <div class="fw-bold fs-5">{{ $loginAttempts->count() }}</div>
                                    <div class="small text-muted">Login Attempts (30d)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin List -->
    <div class="aa-card shadow-sm">
        <div class="card-header header-maroon">
            <h4 class="card-title mb-0">
                <i class="bi bi-people me-2"></i>Manage Admins
            </h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead style="background:transparent;">
                        <tr>
                            <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-hash me-1"></i>ID</th>
                            <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-person me-1"></i>Username</th>
                            <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-shield-lock me-1"></i>Role</th>
                            <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-building me-1"></i>Department</th>
                            <th class="py-3 px-2 text-center" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-gear me-1"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($admins as $admin)
                        <tr>
                            <td class="py-3 px-4">#{{ $admin->admin_id }}</td>
                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center">
                                    @if($admin->employee)
                                    <div class="employee-avatar me-2" style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #8B0000, #A52A2A); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.7rem;">
                                        {{ strtoupper(substr($admin->employee->full_name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="fw-semibold">{{ $admin->username }}</div>
                                        <small class="text-muted">{{ $admin->employee->full_name }} (ID: {{ $admin->employee->employee_id }})</small>
                                    </div>
                                    @elseif($admin->role && $admin->role->role_name === 'super_admin')
                                    <i class="bi bi-shield-lock me-2 text-warning"></i>
                                    <div>
                                        <div class="fw-semibold">{{ $admin->username }}</div>
                                        <small class="text-warning">Super Admin (No employee record needed)</small>
                                    </div>
                                    @else
                                    <i class="bi bi-person me-2 text-muted"></i>
                                    <div>
                                        <div class="fw-semibold">{{ $admin->username }}</div>
                                        <small class="text-danger">No employee linked</small>
                                    </div>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="badge bg-{{ $admin->role->role_name === 'super_admin' ? 'danger' : 'secondary' }}">
                                    {{ ucfirst(str_replace('_', ' ', $admin->role->role_name)) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                @if($admin->department)
                                <span class="badge bg-info">
                                    {{ $admin->department->department_name }}
                                </span>
                                @else
                                <span class="text-muted">All Departments</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-center">
                                @if($admin->role->role_name !== 'super_admin')
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAdminModal_{{ $admin->admin_id }}">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger delete-admin-btn" data-admin-id="{{ $admin->admin_id }}">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @else
                                <span class="text-muted">Protected</span>
                                @endif
                            </td>
                        </tr>
                        <!-- Edit Admin Modal -->
                        <div class="modal fade" id="editAdminModal_{{ $admin->admin_id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                                    <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white;">
                                        <h5 class="modal-title fw-bold d-flex align-items-center">
                                            <i class="bi bi-person-gear me-2 fs-5"></i>Edit Admin #{{ $admin->admin_id }}
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
                                    </div>
                                    <form action="{{ route('admin.users.update', $admin->admin_id) }}" method="POST">
                                        @csrf
                                        @method('PUT')
                                        <div class="modal-body p-4" style="background: #fafbfc;">
                                            <div class="mb-4">
                                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                                    <i class="bi bi-person-circle me-2 fs-6"></i>Username
                                                </label>
                                                <input type="text" name="username" class="form-control form-control-lg border-2" 
                                                       value="{{ $admin->username }}" required
                                                       style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                                       onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                                    <i class="bi bi-shield-lock me-2 fs-6"></i>New Password <span class="text-muted fw-normal">(optional)</span>
                                                </label>
                                                <input type="password" name="password" class="form-control form-control-lg border-2" 
                                                       placeholder="Leave blank to keep current"
                                                       style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                                       onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                                <div class="form-text d-flex align-items-center mt-2" style="color: #6c757d; font-size: 0.875rem;">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    Min 6 characters. Leave blank to keep existing password.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-0 p-4" style="background: white;">
                                            <button type="button" class="btn btn-lg px-4 me-2" data-bs-dismiss="modal"
                                                    style="background: #f8f9fa; color: #6c757d; border: 2px solid #e5e7eb; border-radius: 8px; font-weight: 600; transition: all 0.3s ease;"
                                                    onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#dee2e6'"
                                                    onmouseout="this.style.background='#f8f9fa'; this.style.borderColor='#e5e7eb'">
                                                <i class="bi bi-x-circle me-2"></i>Cancel
                                            </button>
                                            <button type="submit" class="btn btn-lg px-4 fw-bold text-white"
                                                    style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); border: none; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(86, 0, 0, 0.3);"
                                                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(86, 0, 0, 0.4)'"
                                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(86, 0, 0, 0.3)'">
                                                <i class="bi bi-check-circle me-2"></i>Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete Admin Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-labelledby="deleteAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none;">
                <h5 class="modal-title" id="deleteAdminModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Admin
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-trash text-danger" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                    <h5 class="text-danger mb-3">Confirm Deletion</h5>
                    <p class="text-muted mb-0">
                        Are you sure you want to delete this admin? 
                        <br><strong class="text-danger">This action cannot be undone.</strong>
                    </p>
                </div>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>This will permanently remove admin access from the system.</small>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteAdminBtn" onclick="confirmDeleteAdmin()">
                    <i class="bi bi-trash me-1"></i>Delete Admin
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Employee data for JavaScript -->
<script type="application/json" id="employeeData">
    @if(isset($availableEmployees) && $availableEmployees->count() > 0)[
        @foreach($availableEmployees as $index => $emp) {
            "employee_id": {{ $emp->employee_id }},
            "full_name": "{{ addslashes($emp->full_name) }}",
            "department": "{{ addslashes($emp->department->department_name ?? 'No Department') }}",
            "department_id": {{ $emp->department_id ?? 'null' }},
            "employment_type": "{{ addslashes(ucfirst(str_replace('_', ' ', $emp->employment_type))) }}"
        }@if($index < $availableEmployees->count() - 1),@endif
        @endforeach
    ]
    @else
        []
    @endif
</script>

<style>
    .employee-search-item {
        transition: background-color 0.2s ease;
        cursor: pointer;
    }

    .employee-search-item:hover {
        background-color: #f8f9fa !important;
    }

    .employee-search-item:active {
        background-color: #e9ecef !important;
    }

    #employee_search_results {
        border-top: none !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    #employee_search:focus {
        border-color: #8B0000;
        box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
    }

    mark {
        background-color: #fff3cd;
        padding: 0 2px;
        border-radius: 2px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeEmployeeSearch();
        initializeDeleteAdminModal();
    });

    // Add event listener for delete admin buttons
    function initializeDeleteAdminModal() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('.delete-admin-btn')) {
                e.preventDefault();
                const adminId = e.target.closest('.delete-admin-btn').dataset.adminId;
                deleteAdmin(adminId);
            }
        });
    }

    function deleteAdmin(adminId) {
        // Store the adminId for later use
        document.getElementById('confirmDeleteAdminBtn').dataset.adminId = adminId;
        
        // Show the custom delete modal
        const modal = new bootstrap.Modal(document.getElementById('deleteAdminModal'));
        modal.show();
    }

    function confirmDeleteAdmin() {
        const adminId = document.getElementById('confirmDeleteAdminBtn').dataset.adminId;
        
        try {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.users.destroy", ":adminId") }}'.replace(':adminId', adminId);

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            form.submit();
        } catch (error) {
            console.error('Error deleting admin:', error);
            alert('An error occurred while trying to delete the admin. Please try again.');
        }
    }

    function initializeEmployeeSearch() {
        const searchInput = document.getElementById('employee_search');
        const searchResults = document.getElementById('employee_search_results');
        const hiddenEmployeeId = document.getElementById('selected_employee_id');
        const departmentFilter = document.getElementById('filter_department');
        const availableCountSpan = document.getElementById('available_count');
        const employeeDataElement = document.getElementById('employeeData');

        if (!searchInput || !searchResults || !employeeDataElement) return;

        const allEmployees = JSON.parse(employeeDataElement.textContent);
        let filteredEmployees = allEmployees;
        let selectedEmployee = null;

        // Department filter change handler
        departmentFilter.addEventListener('change', function() {
            const deptId = this.value;
            if (deptId === 'all') {
                filteredEmployees = allEmployees;
            } else {
                filteredEmployees = allEmployees.filter(emp => emp.department_id == deptId);
            }

            // Update available count
            availableCountSpan.textContent = filteredEmployees.length;

            // Clear search and selection
            searchInput.value = '';
            hiddenEmployeeId.value = '';
            selectedEmployee = null;
            searchResults.style.display = 'none';
        });

        // Search functionality
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            const results = filteredEmployees.filter(emp =>
                emp.full_name.toLowerCase().includes(query) ||
                emp.department.toLowerCase().includes(query) ||
                emp.employment_type.toLowerCase().includes(query)
            );

            displaySearchResults(results, query);
        });

        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });

        // Focus to show recent searches or all if empty
        searchInput.addEventListener('focus', function() {
            if (this.value.length === 0) {
                displaySearchResults(filteredEmployees.slice(0, 8), '');
            }
        });

        function displaySearchResults(results, query) {
            if (results.length === 0) {
                searchResults.innerHTML = '<div class="p-3 text-muted text-center">No employees found</div>';
                searchResults.style.display = 'block';
                return;
            }

            let html = '';
            results.slice(0, 8).forEach(emp => {
                html += `
                        <div class="employee-search-item p-3 border-bottom" data-employee-id="${emp.employee_id}">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">${highlightMatch(emp.full_name, query)}</div>
                                    <small class="text-muted">
                                        ${highlightMatch(emp.department, query)} • ${highlightMatch(emp.employment_type, query)}
                                    </small>
                                </div>
                                <small class="text-muted">ID: ${emp.employee_id}</small>
                            </div>
                        </div>
                    `;
            });

            if (results.length > 8) {
                html += '<div class="p-2 text-center text-muted small">Showing first 8 results</div>';
            }

            searchResults.innerHTML = html;
            searchResults.style.display = 'block';

            // Add click handlers
            searchResults.querySelectorAll('.employee-search-item').forEach(item => {
                item.addEventListener('click', function() {
                    const employeeId = this.dataset.employeeId;
                    selectedEmployee = filteredEmployees.find(emp => emp.employee_id == employeeId);

                    if (selectedEmployee) {
                        searchInput.value = selectedEmployee.full_name + ' (' + selectedEmployee.department + ')';
                        hiddenEmployeeId.value = selectedEmployee.employee_id;
                        searchResults.style.display = 'none';
                    }
                });
            });
        }

        function highlightMatch(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        }

        // Clear selection when input is manually cleared
        searchInput.addEventListener('keyup', function() {
            if (this.value.trim() === '') {
                hiddenEmployeeId.value = '';
                selectedEmployee = null;
            }
        });
    }
</script>
@include('layouts.toast-js')
@endsection