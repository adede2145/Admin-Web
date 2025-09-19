@extends('layouts.theme')
@section('content')
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-shield-lock me-2 fs-4"></i>Admin Panel
            </h1>
            <span class="badge bg-primary fs-5">Super Admin</span>
        </div>

        <!-- Success / Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

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

                            <div class="mb-3">
                                <label for="department_id" class="form-label">
                                    <span class="d-flex align-items-center">
                                        <i class="bi bi-building me-2 text-muted"></i>
                                        Department
                                    </span>
                                </label>
                                <select name="department_id" id="department_id" class="form-select" required>
                                    <option value="">Select Department</option>
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Department admins can only access their assigned department's data</div>
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
                                            <i class="bi bi-person me-2 text-muted"></i>
                                            {{ $admin->username }}
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
                                            <form action="{{ route('admin.users.destroy', $admin->admin_id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this admin?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted">Protected</span>
                                        @endif
                                    </td>
                                </tr>
                                <!-- Edit Admin Modal -->
                                <div class="modal fade" id="editAdminModal_{{ $admin->admin_id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Edit Admin #{{ $admin->admin_id }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('admin.users.update', $admin->admin_id) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Username</label>
                                                        <input type="text" name="username" class="form-control" value="{{ $admin->username }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">New Password (optional)</label>
                                                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current">
                                                        <div class="form-text">Min 6 chars. Leave blank to keep existing password.</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Save Changes</button>
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
@endsection
