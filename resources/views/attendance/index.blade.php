@extends('layouts.theme')
@section('content')
<style>
    .scroll-hide::-webkit-scrollbar {
        display: none;
    }

    .filter-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        overflow: hidden;
    }

    .filter-card .card-body {
        background: #ffffff;
        padding: 0.001rem 1.5rem 1rem 1.5rem;
    }

    .filter-card .form-control,
    .filter-card .form-select {
        border-radius: 10px;
        padding: 1.4rem 1.3rem 1rem 1.3rem;
    }

    .filter-card .row {
        margin: 10 -0.5rem;
    }

    .filter-card .row>* {
        padding: 0 0.5rem;
    }

    .filter-card .form-label {
        font-weight: 600;
        color: #495057;
    }

    /* Enhanced Table Styling */
    .attendance-table {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: none;
    }

    .attendance-table thead th {
        background: linear-gradient(135deg, #8B0000, #A52A2A);
        color: white;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        border: none;
        padding: 1rem 0.75rem;
        vertical-align: middle;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .attendance-table tbody tr {
        border-bottom: 1px solid #f1f3f4;
        transition: all 0.2s ease;
    }

    .attendance-table tbody tr:hover {
        background-color: #f8f9fa;
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .attendance-table tbody td {
        padding: 1rem 0.75rem;
        vertical-align: middle;
        border: none;
        font-size: 0.9rem;
    }

    /* Enhanced Badge Styling */
    .badge {
        font-size: 0.8rem;
        padding: 0.4rem 0.7rem;
        border-radius: 8px;
        font-weight: 500;
    }

    /* Employee Avatar Styling */
    .employee-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #8B0000, #A52A2A);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    /* Photo View Button Styling */
    .photo-view-btn {
        border-radius: 8px;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .photo-view-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
    }

    /* Action Buttons Styling */
    .action-buttons .btn {
        margin: 0 0.1rem;
        border-radius: 8px;
        transition: all 0.2s ease;
        font-weight: 500;
    }

    .action-buttons .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* Time display styling */
    .time-info {
        font-weight: 600;
        font-size: 0.95rem;
    }

    .time-date {
        font-size: 0.8rem;
        opacity: 0.7;
    }

    /* Verification badge improvements */
    .verification-badge {
        border-radius: 20px;
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 500;
    }

    /* Kiosk badge styling */
    .kiosk-badge {
        background: linear-gradient(135deg, #6c757d, #495057);
        border: none;
        border-radius: 20px;
        color: white;
        padding: 0.25rem 0.75rem;
        font-size: 0.8rem;
        font-weight: 500;
    }
</style>
<div class="container-fluid">

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-clock me-2 fs-4"></i>Attendance Management
            </h1>
            @if(auth()->user()->role->role_name !== 'super_admin')
            <p class="text-muted mb-0 fs-5">
                <i class="bi bi-building me-1"></i>
                Viewing data for: <strong>{{ auth()->user()->department->department_name ?? 'N/A' }}</strong>
            </p>
            @endif
        </div>
        <div>

            <button type="button" class="btn btn-danger me-2" id="openDTRHistoryModal">
                <i class="bi bi-clock-history me-2"></i>DTR History
            </button>
            <button class="btn btn-warning text-dark fw-semibold me-2" data-bs-toggle="modal" data-bs-target="#generateDTRModal">
                <i class="bi bi-file-earmark-text me-2"></i>Generate DTR Report
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="aa-card mb-3 filter-card">
        <div class="card-header bg-light">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <i class="bi bi-funnel me-2"></i>Filter Attendance Records
                </h6>
                <small class="text-muted">
                    @if(auth()->user()->role->role_name === 'super_admin')
                    System-wide access
                    @else
                    Limited to {{ auth()->user()->department->department_name ?? 'your department' }}
                    @endif
                </small>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end" id="attendanceFiltersForm">
                <!-- Date Range Filters -->
                <div class="col-lg-2 col-md-3">
                    <label class="form-label">
                        <i class="bi bi-calendar-date me-1"></i>From Date
                    </label>
                    <input type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ request('start_date', now()->subDays(7)->format('Y-m-d')) }}"
                        max="{{ date('Y-m-d') }}">
                    <div class="form-text small">Default: 7 days ago</div>
                </div>

                <div class="col-lg-2 col-md-3">
                    <label class="form-label">
                        <i class="bi bi-calendar-check me-1"></i>To Date
                    </label>
                    <input type="date"
                        name="end_date"
                        class="form-control"
                        value="{{ request('end_date', date('Y-m-d')) }}"
                        max="{{ date('Y-m-d') }}">
                    <div class="form-text small">Default: Today</div>
                </div>

                <!-- Employee Filter -->
                <div class="col-lg-3 col-md-4">
                    <label class="form-label">
                        <i class="bi bi-person me-1"></i>Employee
                    </label>
                    @php
                    $empQuery = \App\Models\Employee::with('department');
                    if (auth()->user()->role->role_name !== 'super_admin') {
                    $empQuery->where('department_id', auth()->user()->department_id);
                    }
                    $empOptions = $empQuery->orderBy('full_name')->get();
                    @endphp
                    <select name="employee_id" class="form-select">
                        <option value="">
                            @if(auth()->user()->role->role_name === 'super_admin')
                            All Employees ({{ $empOptions->count() }})
                            @else
                            All in {{ auth()->user()->department->department_name ?? 'Department' }} ({{ $empOptions->count() }})
                            @endif
                        </option>
                        @foreach($empOptions as $emp)
                        <option value="{{ $emp->employee_id }}"
                            {{ (string)request('employee_id') === (string)$emp->employee_id ? 'selected' : '' }}>
                            {{ $emp->full_name }}
                            @if(auth()->user()->role->role_name === 'super_admin')
                            <span class="text-muted">({{ $emp->department->department_name ?? 'No Dept' }})</span>
                            @endif
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text small">
                        @if($empOptions->count() === 0)
                        <span class="text-warning">No employees found</span>
                        @else
                        {{ $empOptions->count() }} employee(s) available
                        @endif
                    </div>
                </div>

                <!-- Department Filter (Super Admin Only) -->
                @if(auth()->user()->role->role_name === 'super_admin')
                <div class="col-lg-2 col-md-3">
                    <label class="form-label">
                        <i class="bi bi-building me-1"></i>Department
                    </label>
                    <select name="department_id" class="form-select" id="departmentFilter">
                        <option value="">All Departments ({{ $departments->count() }})</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->department_id }}"
                            {{ request('department_id') == $dept->department_id ? 'selected' : '' }}>
                            {{ $dept->department_name }}
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text small">System-wide access</div>
                </div>
                @endif

                <!-- Method Filter -->
                <div class="col-lg-2 col-md-3">
                    <label class="form-label">
                        <i class="bi bi-gear me-1"></i>Method
                    </label>
                    <select name="login_method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="rfid" {{ request('login_method') == 'rfid' ? 'selected' : '' }}>
                            <i class="bi bi-credit-card"></i> RFID
                        </option>
                        <option value="fingerprint" {{ request('login_method') == 'fingerprint' ? 'selected' : '' }}>
                            <i class="bi bi-fingerprint"></i> Fingerprint
                        </option>
                        <option value="manual" {{ request('login_method') == 'manual' ? 'selected' : '' }}>
                            <i class="bi bi-person-gear"></i> Manual
                        </option>
                    </select>
                </div>

                <!-- RFID Status Filter (Only show if RFID method selected or super admin) -->
                @if(auth()->user()->role->role_name === 'super_admin' || request('login_method') === 'rfid')
                <div class="col-lg-2 col-md-3" id="rfidStatusFilter" @if(!(request('login_method')==='rfid' || auth()->user()->role->role_name === 'super_admin')) style="display: none;" @endif>
                    <label class="form-label">
                        <i class="bi bi-shield-check me-1"></i>RFID Status
                    </label>
                    <select name="rfid_status" class="form-select">
                        <option value="">All RFID Status</option>
                        <option value="pending" {{ request('rfid_status') == 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="verified" {{ request('rfid_status') == 'verified' ? 'selected' : '' }}>
                            Verified
                        </option>
                        <option value="rejected" {{ request('rfid_status') == 'rejected' ? 'selected' : '' }}>
                            Rejected
                        </option>
                    </select>
                </div>
                @endif

                <!-- Quick Date Presets -->
                <div class="col-lg-2 col-md-3">
                    <label class="form-label">
                        <i class="bi bi-lightning me-1"></i>Quick Filters
                    </label>
                    <select class="form-select" id="quickDateFilter">
                        <option value="">Custom Range</option>
                        <option value="today">Today</option>
                        <option value="yesterday">Yesterday</option>
                        <option value="this_week">This Week</option>
                        <option value="last_week">Last Week</option>
                        <option value="this_month">This Month</option>
                        <option value="last_30_days">Last 30 Days</option>
                    </select>
                    <div class="form-text small">Quick date ranges</div>
                </div>

                <!-- Action Buttons -->
                <div class="col-lg-1 col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-warning text-dark fw-semibold" id="applyFiltersBtn">
                            <i class="bi bi-search me-2"></i>Apply
                        </button>
                        <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>

            <!-- Active Filters Display -->
            @php
            $activeFilters = [];
            $defaultStartDate = now()->subDays(7)->format('Y-m-d');
            $defaultEndDate = date('Y-m-d');

            // Date filters
            if (request('start_date') && request('start_date') !== $defaultStartDate) {
            $activeFilters[] = [
            'label' => 'From: ' . \Carbon\Carbon::parse(request('start_date'))->format('M d, Y'),
            'type' => 'date',
            'removable' => true
            ];
            }
            if (request('end_date') && request('end_date') !== $defaultEndDate) {
            $activeFilters[] = [
            'label' => 'To: ' . \Carbon\Carbon::parse(request('end_date'))->format('M d, Y'),
            'type' => 'date',
            'removable' => true
            ];
            }

            // Employee filter
            if (request('employee_id')) {
            $emp = \App\Models\Employee::find(request('employee_id'));
            $activeFilters[] = [
            'label' => 'Employee: ' . ($emp ? $emp->full_name : 'Unknown'),
            'type' => 'employee',
            'removable' => true
            ];
            }

            // Department filter (Super Admin only)
            if (request('department_id') && auth()->user()->role->role_name === 'super_admin') {
            $dept = \App\Models\Department::find(request('department_id'));
            $activeFilters[] = [
            'label' => 'Department: ' . ($dept ? $dept->department_name : 'Unknown'),
            'type' => 'department',
            'removable' => true
            ];
            }

            // Method filter
            if (request('login_method')) {
            $methodIcons = [
            'rfid' => 'bi-credit-card',
            'fingerprint' => 'bi-fingerprint',
            'manual' => 'bi-person-gear'
            ];
            $icon = $methodIcons[request('login_method')] ?? 'bi-gear';
            $activeFilters[] = [
            'label' => 'Method: ' . ucfirst(request('login_method')),
            'type' => 'method',
            'icon' => $icon,
            'removable' => true
            ];
            }

            // RFID Status filter
            if (request('rfid_status')) {
            $statusEmojis = [
            'pending' => '',
            'verified' => '',
            'rejected' => ''
            ];
            $emoji = $statusEmojis[request('rfid_status')] ?? '';
            $activeFilters[] = [
            'label' => $emoji . ' RFID: ' . ucfirst(request('rfid_status')),
            'type' => 'rfid_status',
            'removable' => true
            ];
            }

            // Role-based access indicator
            if (auth()->user()->role->role_name !== 'super_admin') {
            $activeFilters[] = [
            'label' => 'Scope: ' . (auth()->user()->department->department_name ?? 'Department'),
            'type' => 'scope',
            'removable' => false
            ];
            }
            @endphp

            @if(count($activeFilters) > 0)
            <div class="mt-3 pt-3 border-top">
                <div class="d-flex align-items-center flex-wrap">
                    <span class="text-muted small me-3 mb-2">
                        <i class="bi bi-funnel-fill me-1"></i>Active Filters:
                    </span>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($activeFilters as $filter)
                        <span class="badge {{ $filter['removable'] ? 'bg-primary' : 'bg-secondary' }} d-flex align-items-center">
                            @if(isset($filter['icon']))
                            <i class="bi {{ $filter['icon'] }} me-1"></i>
                            @endif
                            {{ $filter['label'] }}
                            @if($filter['removable'])
                            <button type="button" class="btn-close btn-close-white ms-2"
                                style="font-size: 0.6em;"
                                @php $filterType=$filter['type']; @endphp
                                onclick='removeFilter("{{ $filterType }}")'
                                title="Remove this filter"></button>
                            @endif
                        </span>
                        @endforeach

                        @if(collect($activeFilters)->where('removable', true)->count() > 1)
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearAllFilters()">
                            <i class="bi bi-x-circle me-1"></i>Clear All
                        </button>
                        @endif
                    </div>
                </div>

                <!-- Filter Summary Stats -->
                <div class="mt-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Showing
                        <strong>{{ $attendanceLogs->total() ?? 0 }}</strong>
                        attendance record(s)
                        @if(auth()->user()->role->role_name !== 'super_admin')
                        from {{ auth()->user()->department->department_name ?? 'your department' }}
                        @endif
                        @if(request('start_date') || request('end_date'))
                        for the selected date range
                        @endif
                    </small>
                </div>
            </div>
            @endif
        </div>
    </div>

    @php
    $selId = request('employee_id') ?: optional($attendanceLogs->first())->employee_id;
    $selEmp = $selId ? \App\Models\Employee::with('department')->find($selId) : null;
    $sd = request('start_date', date('Y-m-d'));
    $ed = request('end_date', date('Y-m-d'));
    $empLogQ = $selId ? \App\Models\AttendanceLog::where('employee_id', $selId)->whereBetween('time_in', [\Carbon\Carbon::parse($sd)->startOfDay(), \Carbon\Carbon::parse($ed)->endOfDay()]) : \App\Models\AttendanceLog::whereRaw('1=0');
    $daysPresent = (clone $empLogQ)->selectRaw('DATE(time_in) d')->groupBy('d')->get()->count();
    $lateArrivals = (clone $empLogQ)->whereTime('time_in','>','08:00:00')->count();
    $lastLog = (clone $empLogQ)->latest('time_in')->first();

    // Right summary numbers (today)
    $today = \Carbon\Carbon::today();
    $roleScoped = \App\Models\AttendanceLog::whereDate('time_in', $today);
    if (auth()->user()->role->role_name !== 'super_admin') {
    $roleScoped->whereHas('employee', function($q){ $q->where('department_id', auth()->user()->department_id); });
    }
    $presentToday = (clone $roleScoped)->distinct('employee_id')->count('employee_id');
    $totalEmployeesToday = \App\Models\Employee::when(auth()->user()->role->role_name !== 'super_admin', function($q){ $q->where('department_id', auth()->user()->department_id); })->count();
    $attendanceRate = $totalEmployeesToday ? round(($presentToday / $totalEmployeesToday) * 100) : 0;
    $timedOutToday = (clone $roleScoped)->whereNotNull('time_out')->count();
    $latestTimeInLog = (clone $roleScoped)->orderByDesc('time_in')->first();
    $latestTimeIn = $latestTimeInLog ? \Carbon\Carbon::parse($latestTimeInLog->time_in)->format('h:i A') : 'N/A';
    @endphp

    <div class="row">
        <div class="col-lg-9">
            <div class="aa-card" style="min-height: 500px;">
                <div class="card-header header-maroon">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-list-ul me-2"></i>Attendance Records
                    </h4>
                </div>
                <div class="card-body p-4">
                    <!-- Auto-refresh status indicator -->
                    <div id="refreshStatus" class="alert alert-info mb-3">
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        <span>Auto-refreshing every 10 seconds...</span>
                        <span class="float-end">Last updated: <span id="lastUpdated">--</span></span>
                    </div>

                    <div id="attendance-logs-table" class="table-responsive scroll-hide" style="max-height: 580px; overflow-y: auto; -ms-overflow-style: none; scrollbar-width: none;">
                        <table class="table table-hover attendance-table">
                            <thead>
                                <tr>
                                    <th scope="col">ID</th>
                                    <th scope="col">Employee</th>
                                    @if(auth()->user()->role->role_name === 'super_admin')
                                    <th scope="col">Department</th>
                                    @endif
                                    <th scope="col">Time In</th>
                                    <th scope="col">Time Out</th>
                                    <th scope="col">Method</th>
                                    <th scope="col">Reason</th>
                                    <th scope="col">Photo</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Kiosk</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTableBody">
                                @include('components.attendance-table-rows', ['attendanceLogs' => $attendanceLogs])
                            </tbody>
                        </table>
                    </div>

                    <div id="paginationContainer">
                        @if($attendanceLogs->hasPages())
                        <div class="d-flex justify-content-center mt-3">
                            {{ $attendanceLogs->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="aa-card" style="min-height: 500px;">
                <div class="card-header header-maroon">
                    <h5 class="mb-0"><i class="bi bi-clipboard-data me-2"></i>Summary Today</h5>
                </div>
                <div class="card-body text-center p-4">
                    <div class="fw-semibold mb-2 fs-5"><i class="bi bi-person-check me-2"></i>Total Present</div>
                    <div class="display-1 fw-bold" style="color:var(--aa-maroon)">{{ $presentToday }}</div>
                    <div class="text-muted small mb-4">Today</div>
                    <div class="fw-semibold mb-2 fs-5"><i class="bi bi-graph-up me-2"></i>Attendance Rate</div>
                    <div class="display-1 fw-bold" style="color:var(--aa-maroon)">{{ $attendanceRate }}%</div>
                    <div class="text-muted small">Today</div>
                    <div class="fw-semibold mb-2 fs-5 mt-4"><i class="bi bi-clock me-2"></i>Latest Time In</div>
                    <div class="fw-bold fs-2" style="color:var(--aa-maroon)">{{ $latestTimeIn }}</div>
                    <div class="text-muted small">Today</div>
                    <div class="fw-semibold mb-2 fs-5 mt-4"><i class="bi bi-clock-history me-2"></i>Timed Out</div>
                    <div class="display-1 fw-bold" style="color:var(--aa-maroon)">{{ $timedOutToday }}</div>
                    <div class="text-muted small">Today</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generate DTR Modal -->
<div class="modal fade" id="generateDTRModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header header-maroon d-flex justify-content-between align-items-center">
                <h5 class="modal-title text-white mb-0">
                    <i class="bi bi-file-earmark-text me-2"></i>Generate DTR Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('attendance.dtr') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Report Type</label>
                        <select name="report_type" class="form-select" required>
                            <option value="weekly">Weekly Report</option>
                            <option value="monthly">Monthly Report</option>
                            <option value="custom">Custom Period</option>
                        </select>
                    </div>
                    @if(auth()->user()->role->role_name === 'super_admin')
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select" id="dtrDepartmentSelect">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->department->department_name ?? 'N/A' }}" readonly>
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <div class="form-text">You can only generate reports for your department</div>
                    </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Employees to include</label>
                        <div class="border rounded p-2" style="max-height:220px;overflow:auto;">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="empSelectAll">
                                <label class="form-check-label" for="empSelectAll">Select All</label>
                            </div>
                            @foreach(($employeesForDTR ?? []) as $emp)
                            <div class="form-check">
                                <input class="form-check-input emp-item" type="checkbox" name="employee_ids[]" value="{{ $emp->employee_id }}" id="emp_{{ $emp->employee_id }}">
                                <label class="form-check-label" for="emp_{{ $emp->employee_id }}">
                                    {{ $emp->full_name }} <span class="text-muted">({{ $emp->department->department_name ?? 'N/A' }})</span>
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div class="form-text">Leave empty to include all employees in the selected department.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}" required>
                        <div class="form-text">Defaults to the first day of this month</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        <div class="form-text">Defaults to today</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning text-dark fw-semibold">Generate Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- DTR History Modal -->
<div class="modal fade" id="dtrHistoryModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-body" id="dtrHistoryModalBody">
                <div class="text-center p-5">
                    <div class="spinner-border text-maroon" role="status"></div>
                    <div class="mt-3">Loading DTR history...</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Attendance Modals -->
@foreach($attendanceLogs as $log)
<div class="modal fade" id="editAttendance{{ $log->log_id }}" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Attendance Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('attendance.update', $log->log_id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <input type="text" class="form-control" value="{{ $log->employee->full_name ?? 'N/A' }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control"
                            value="{{ \Carbon\Carbon::parse($log->time_in)->format('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Time In</label>
                        <input type="time" name="time_in" class="form-control"
                            value="{{ \Carbon\Carbon::parse($log->time_in)->format('H:i') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Time Out</label>
                        <input type="time" name="time_out" class="form-control"
                            value="{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('H:i') : '' }}">
                        <div class="form-text">Leave empty if employee hasn't timed out yet</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Method</label>
                        <select name="method" class="form-select" required>
                            <option value="rfid" {{ $log->method === 'rfid' ? 'selected' : '' }}>RFID</option>
                            <option value="fingerprint" {{ $log->method === 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                            <option value="manual" {{ $log->method === 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<div id="bladePayload" class="d-none"
    data-has-report="{{ session('generated_report_id') ? '1' : '0' }}"
    data-success="{{ session('success') ? e(session('success')) : '' }}"
    data-error="{{ session('error') ? e(session('error')) : '' }}">
</div>

<script>
    // Auto-refresh functionality - automatically starts on page load
    let autoRefreshInterval = null;
    const refreshIntervalSeconds = 10; // Fixed 10 second interval

    document.addEventListener('DOMContentLoaded', function() {
        const lastUpdatedSpan = document.getElementById('lastUpdated');
        const refreshStatus = document.getElementById('refreshStatus');

        // Initialize enhanced filters
        initializeFilters();

        // Auto-start refresh when page loads
        startAutoRefresh();

        function startAutoRefresh() {
            // Start the interval
            autoRefreshInterval = setInterval(refreshAttendanceData, refreshIntervalSeconds * 1000);

            // Initial refresh after 2 seconds to let page load
            setTimeout(refreshAttendanceData, 2000);
        }

        function refreshAttendanceData() {
            const currentUrl = new URL(window.location);
            const params = new URLSearchParams(currentUrl.search);

            fetch('{{ route("api.attendance.logs") }}?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    // Update table body
                    document.getElementById('attendanceTableBody').innerHTML = data.html;

                    // Update pagination
                    document.getElementById('paginationContainer').innerHTML = data.pagination;

                    // Update last updated time
                    lastUpdatedSpan.textContent = new Date().toLocaleTimeString();

                    // Add subtle animation to indicate refresh
                    const tableBody = document.getElementById('attendanceTableBody');
                    tableBody.style.opacity = '0.7';
                    setTimeout(() => {
                        tableBody.style.opacity = '1';
                    }, 200);

                    // Ensure status shows success
                    refreshStatus.classList.remove('alert-warning');
                    refreshStatus.classList.add('alert-info');
                })
                .catch(error => {
                    console.error('Error refreshing attendance data:', error);
                    // Show error in status
                    refreshStatus.classList.remove('alert-info');
                    refreshStatus.classList.add('alert-warning');
                    refreshStatus.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Error refreshing data. Retrying in 10 seconds...';

                    // Reset status after 3 seconds
                    setTimeout(() => {
                        refreshStatus.classList.remove('alert-warning');
                        refreshStatus.classList.add('alert-info');
                        refreshStatus.innerHTML = '<i class="bi bi-arrow-clockwise me-2"></i><span>Auto-refreshing every 10 seconds...</span><span class="float-end">Last updated: <span id="lastUpdated">--</span></span>';
                    }, 3000);
                });
        }
    });

    // Enhanced Filter Functions
    function initializeFilters() {
        // Quick date filter functionality
        const quickDateFilter = document.getElementById('quickDateFilter');
        const startDateInput = document.querySelector('input[name="start_date"]');
        const endDateInput = document.querySelector('input[name="end_date"]');
        const methodSelect = document.querySelector('select[name="login_method"]');
        const rfidStatusFilter = document.getElementById('rfidStatusFilter');

        if (quickDateFilter) {
            quickDateFilter.addEventListener('change', function() {
                const today = new Date();
                let startDate, endDate;

                switch (this.value) {
                    case 'today':
                        startDate = endDate = formatDate(today);
                        break;
                    case 'yesterday':
                        const yesterday = new Date(today);
                        yesterday.setDate(yesterday.getDate() - 1);
                        startDate = endDate = formatDate(yesterday);
                        break;
                    case 'this_week':
                        const startOfWeek = new Date(today);
                        startOfWeek.setDate(today.getDate() - today.getDay());
                        startDate = formatDate(startOfWeek);
                        endDate = formatDate(today);
                        break;
                    case 'last_week':
                        const lastWeekEnd = new Date(today);
                        lastWeekEnd.setDate(today.getDate() - today.getDay() - 1);
                        const lastWeekStart = new Date(lastWeekEnd);
                        lastWeekStart.setDate(lastWeekEnd.getDate() - 6);
                        startDate = formatDate(lastWeekStart);
                        endDate = formatDate(lastWeekEnd);
                        break;
                    case 'this_month':
                        const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                        startDate = formatDate(startOfMonth);
                        endDate = formatDate(today);
                        break;
                    case 'last_30_days':
                        const thirtyDaysAgo = new Date(today);
                        thirtyDaysAgo.setDate(today.getDate() - 30);
                        startDate = formatDate(thirtyDaysAgo);
                        endDate = formatDate(today);
                        break;
                    default:
                        return; // Don't change dates for custom range
                }

                if (startDate && endDate) {
                    startDateInput.value = startDate;
                    endDateInput.value = endDate;
                }
            });
        }

        // Method filter change handler for RFID status visibility
        if (methodSelect && rfidStatusFilter) {
            methodSelect.addEventListener('change', function() {
                if (this.value === 'rfid') {
                    rfidStatusFilter.style.display = '';
                } else {
                    rfidStatusFilter.style.display = 'none';
                    // Clear RFID status when method is not RFID
                    const rfidStatusSelect = rfidStatusFilter.querySelector('select[name="rfid_status"]');
                    if (rfidStatusSelect) {
                        rfidStatusSelect.value = '';
                    }
                }
            });
        }

        // Department filter for employee options (Super Admin only)
        const departmentFilter = document.getElementById('departmentFilter');
        const employeeSelect = document.querySelector('select[name="employee_id"]');

        if (departmentFilter && employeeSelect) {
            departmentFilter.addEventListener('change', function() {
                const selectedDept = this.value;
                const options = employeeSelect.querySelectorAll('option');

                options.forEach(option => {
                    if (option.value === '') {
                        // Keep "All Employees" option
                        option.style.display = '';
                        return;
                    }

                    if (!selectedDept) {
                        // Show all employees when no department selected
                        option.style.display = '';
                    } else {
                        // Show/hide based on department match in option text
                        const optionText = option.textContent;
                        const deptName = this.options[this.selectedIndex].textContent;
                        option.style.display = optionText.includes(deptName) ? '' : 'none';
                    }
                });

                // Reset employee selection when department changes
                employeeSelect.value = '';
            });
        }

        // Form validation before submit
        const filtersForm = document.getElementById('attendanceFiltersForm');
        if (filtersForm) {
            filtersForm.addEventListener('submit', function(e) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);

                if (startDate > endDate) {
                    e.preventDefault();
                    showNotification('error', 'Start date cannot be later than end date.');
                    return false;
                }

                // Add loading state to apply button
                const applyBtn = document.getElementById('applyFiltersBtn');
                if (applyBtn) {
                    applyBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Applying...';
                    applyBtn.disabled = true;
                }
            });
        }
    }

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    // Filter management functions
    function removeFilter(filterType) {
        const currentUrl = new URL(window.location);
        const params = new URLSearchParams(currentUrl.search);

        switch (filterType) {
            case 'date':
                params.delete('start_date');
                params.delete('end_date');
                break;
            case 'employee':
                params.delete('employee_id');
                break;
            case 'department':
                params.delete('department_id');
                break;
            case 'method':
                params.delete('login_method');
                break;
            case 'rfid_status':
                params.delete('rfid_status');
                break;
        }

        // Redirect to URL without the removed filter
        window.location.href = currentUrl.pathname + '?' + params.toString();
    }

    function clearAllFilters() {
        window.location.href = '{{ route("attendance.index") }}';
    }

    document.getElementById('empSelectAll')?.addEventListener('change', function(e) {
        document.querySelectorAll('.emp-item').forEach(cb => {
            cb.checked = e.target.checked;
        });
    });

    // Filter employee checklist by department selection (super admin only)
    document.getElementById('dtrDepartmentSelect')?.addEventListener('change', function() {
        const deptId = this.value;
        document.querySelectorAll('.emp-item').forEach(cb => {
            const label = document.querySelector('label[for="' + cb.id + '"]');
            const text = label ? label.textContent : '';
            // Expect department name in parentheses in label; simple client-side filter
            const show = !deptId || text.includes('(') ? text.includes('(' + this.options[this.selectedIndex].text + ')') : true;
            cb.closest('.form-check').style.display = show ? '' : 'none';
        });
    });

    function deleteAttendance(logId) {
        if (confirm('Are you sure you want to delete this attendance record?')) {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("attendance.destroy", ":logId") }}'.replace(':logId', logId);

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
        }
    }

    // RFID Verification Functions
    function approveRfid(logId) {
        // Store the logId for later use
        document.getElementById('confirmApprovalBtn').dataset.logId = logId;

        // Show the approval modal
        const modal = new bootstrap.Modal(document.getElementById('rfidApprovalModal'));
        modal.show();
    }

    function rejectRfid(logId) {
        // Store the logId for later use
        document.getElementById('confirmRejectionBtn').dataset.logId = logId;

        // Clear any previous reason text
        document.getElementById('rejectionReason').value = '';
        document.getElementById('rejectionReason').classList.remove('is-invalid');

        // Show the rejection modal
        const modal = new bootstrap.Modal(document.getElementById('rfidRejectionModal'));
        modal.show();
    }

    document.getElementById('openDTRHistoryModal').addEventListener('click', function() {
        var modal = new bootstrap.Modal(document.getElementById('dtrHistoryModal'));
        document.getElementById('dtrHistoryModalBody').innerHTML = '<div class="text-center p-5"><div class="spinner-border text-maroon" role="status"></div><div class="mt-3">Loading DTR history...</div></div>';
        fetch("{{ route('dtr.history.modal') }}")
            .then(response => response.text())
            .then(html => {
                document.getElementById('dtrHistoryModalBody').innerHTML = html;
            });
        modal.show();
    });

    // Auto-show modal when page loads if report was generated
    const bladePayload = document.getElementById('bladePayload');
    const hasGeneratedReport = bladePayload?.dataset.hasReport === '1';
    if (hasGeneratedReport) {
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('generatedReportModal'));
            modal.show();
        });
    }

    // Cool notification functions
    function showNotification(type, message) {
        const toastEl = document.getElementById(type + 'Toast');
        const messageEl = document.getElementById(type + 'Message');

        if (!toastEl || !messageEl) return;

        messageEl.textContent = message;

        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });

        toast.show();

        // Add animation effect
        toastEl.style.transform = 'translateX(100%)';
        setTimeout(() => {
            toastEl.style.transition = 'transform 0.3s ease-in-out';
            toastEl.style.transform = 'translateX(0)';
        }, 100);
    }

    // Show notifications based on session messages
    const flashSuccessMessage = bladePayload?.dataset.success || '';
    const flashErrorMessage = bladePayload?.dataset.error || '';
    if (flashSuccessMessage) {
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('success', flashSuccessMessage);
        });
    }
    if (flashErrorMessage) {
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('error', flashErrorMessage);
        });
    }

    // Photo viewing functions with RFID verification
    function showAttendancePhoto(logId, employeeName, dateTime) {
        const modal = new bootstrap.Modal(document.getElementById('photoModal'));
        const photoImage = document.getElementById('photoImage');
        const photoEmployeeName = document.getElementById('photoEmployeeName');
        const photoDateTime = document.getElementById('photoDateTime');
        const downloadBtn = document.getElementById('downloadPhotoBtn');
        const photoError = document.getElementById('photoError');
        const photoContent = document.getElementById('photoContent');
        const loadingSpinner = document.getElementById('photoLoadingSpinner');
        const rfidReasonSection = document.getElementById('rfidReasonSection');
        const rfidReasonText = document.getElementById('rfidReasonText');
        const verificationStatusSection = document.getElementById('verificationStatusSection');
        const verificationStatus = document.getElementById('verificationStatus');
        const verificationButtons = document.getElementById('verificationButtons');

        // Set employee info
        photoEmployeeName.textContent = employeeName;
        photoDateTime.textContent = 'Scanned on ' + dateTime;

        // Show loading state
        loadingSpinner.classList.remove('d-none');
        photoError.classList.add('d-none');
        photoContent.style.opacity = '0.5';

        // Set photo source and download link (cache-bust to avoid stale cached bytes)
        const baseUrl = '{{ route("attendance.photo", ":logId") }}'.replace(':logId', logId);
        const photoUrl = baseUrl + '?t=' + Date.now();
        photoImage.src = photoUrl;
        downloadBtn.href = photoUrl;
        downloadBtn.download = 'attendance_photo_' + logId + '_' + employeeName.replace(/\s+/g, '_') + '.jpg';

        // Fetch RFID verification data
        fetch('/api/attendance/' + logId + '/verification-data', {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.method === 'rfid') {
                    // Show RFID reason
                    if (data.rfid_reason) {
                        rfidReasonText.textContent = data.rfid_reason;
                        rfidReasonSection.classList.remove('d-none');
                    } else {
                        rfidReasonSection.classList.add('d-none');
                    }

                    // Show verification status
                    verificationStatus.innerHTML = data.verification_badge;
                    verificationStatusSection.classList.remove('d-none');

                    // Show verification buttons if pending
                    if (data.verification_status === 'pending') {
                        verificationButtons.classList.remove('d-none');
                        // Store logId for modal functions
                        verificationButtons.dataset.logId = logId;
                    } else {
                        verificationButtons.classList.add('d-none');
                    }
                } else {
                    rfidReasonSection.classList.add('d-none');
                    verificationStatusSection.classList.add('d-none');
                    verificationButtons.classList.add('d-none');
                }
            })
            .catch(error => {
                console.error('Error fetching verification data:', error);
                rfidReasonSection.classList.add('d-none');
                verificationStatusSection.classList.add('d-none');
                verificationButtons.classList.add('d-none');
            });

        modal.show();
    }

    // Modal verification functions
    function approveFromModal() {
        const logId = document.getElementById('verificationButtons').dataset.logId;
        const modal = bootstrap.Modal.getInstance(document.getElementById('photoModal'));
        modal.hide();
        approveRfid(logId);
    }

    function rejectFromModal() {
        const logId = document.getElementById('verificationButtons').dataset.logId;
        const modal = bootstrap.Modal.getInstance(document.getElementById('photoModal'));
        modal.hide();
        rejectRfid(logId);
    }

    function hidePhotoLoading() {
        const loadingSpinner = document.getElementById('photoLoadingSpinner');
        const photoContent = document.getElementById('photoContent');

        loadingSpinner.classList.add('d-none');
        photoContent.style.opacity = '1';
    }

    function showPhotoError() {
        const loadingSpinner = document.getElementById('photoLoadingSpinner');
        const photoError = document.getElementById('photoError');
        const photoContent = document.getElementById('photoContent');

        loadingSpinner.classList.add('d-none');
        photoError.classList.remove('d-none');
        photoContent.style.opacity = '1';
    }

    // Add hover effect for clickable badges
    document.addEventListener('DOMContentLoaded', function() {
        const style = document.createElement('style');
        style.textContent = `
                .clickable-badge:hover {
                    transform: scale(1.05);
                    transition: transform 0.2s ease-in-out;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                }
                .clickable-badge {
                    transition: transform 0.2s ease-in-out;
                }
            `;
        document.head.appendChild(style);
    });

    // RFID Modal Event Handlers
    document.addEventListener('DOMContentLoaded', function() {
        // Approval modal confirmation handler
        document.getElementById('confirmApprovalBtn').addEventListener('click', function() {
            const logId = this.dataset.logId;
            const modal = bootstrap.Modal.getInstance(document.getElementById('rfidApprovalModal'));
            modal.hide();

            // Submit the approval form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("attendance.approve", ":logId") }}'.replace(':logId', logId);

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            form.appendChild(csrfToken);
            document.body.appendChild(form);
            form.submit();
        });

        // Rejection modal confirmation handler
        document.getElementById('confirmRejectionBtn').addEventListener('click', function() {
            const logId = this.dataset.logId;
            const reasonTextarea = document.getElementById('rejectionReason');
            const reason = reasonTextarea.value.trim();

            // Validate that reason is provided
            if (!reason) {
                reasonTextarea.classList.add('is-invalid');
                reasonTextarea.focus();
                return;
            }

            const modal = bootstrap.Modal.getInstance(document.getElementById('rfidRejectionModal'));
            modal.hide();

            // Submit the rejection form
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("attendance.reject", ":logId") }}'.replace(':logId', logId);

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const reasonField = document.createElement('input');
            reasonField.type = 'hidden';
            reasonField.name = 'rejection_reason';
            reasonField.value = reason;

            form.appendChild(csrfToken);
            form.appendChild(reasonField);
            document.body.appendChild(form);
            form.submit();
        });

        // Remove validation styling when user starts typing
        document.getElementById('rejectionReason').addEventListener('input', function() {
            this.classList.remove('is-invalid');
        });

        // Photo button click handler - moved outside of nested DOMContentLoaded
        document.addEventListener('click', function(e) {
            if (e.target.closest('.photo-view-btn')) {
                const button = e.target.closest('.photo-view-btn');
                const logId = button.dataset.logId;
                const employeeName = button.dataset.employeeName;
                const timeIn = button.dataset.timeIn;

                showAttendancePhoto(logId, employeeName, timeIn);
            }
        });
    });
</script>

<!-- Cool Toast Notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
    <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span id="successMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>

    <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span id="errorMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>

    <div id="infoToast" class="toast align-items-center text-white bg-info border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi bi-info-circle-fill me-2"></i>
                <span id="infoMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<!-- Photo Viewer Modal with RFID Verification -->
<div class="modal fade" id="photoModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-camera-fill me-2"></i>RFID Scan Photo & Verification
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="photoLoadingSpinner" class="d-none">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="text-muted">Loading photo...</p>
                </div>
                <div id="photoContent">
                    <div class="mb-3">
                        <h6 id="photoEmployeeName" class="text-primary"></h6>
                        <small id="photoDateTime" class="text-muted"></small>
                    </div>
                    <div class="photo-container" style="max-height: 500px; overflow: hidden;">
                        <img id="photoImage" src="" alt="RFID Scan Photo"
                            class="img-fluid rounded shadow"
                            style="max-width: 100%; height: auto;"
                            onload="hidePhotoLoading()"
                            onerror="showPhotoError()">
                    </div>
                    <div id="photoError" class="d-none">
                        <i class="bi bi-exclamation-triangle display-4 text-warning mb-3"></i>
                        <p class="text-muted">Failed to load photo</p>
                    </div>

                    <!-- RFID Reason Display -->
                    <div id="rfidReasonSection" class="mt-3 p-3 border rounded bg-light">
                        <h6 class="text-muted mb-2">
                            <i class="bi bi-info-circle me-1"></i>RFID Reason
                        </h6>
                        <p id="rfidReasonText" class="mb-0"></p>
                    </div>

                    <!-- Verification Status Display -->
                    <div id="verificationStatusSection" class="mt-3">
                        <h6 class="text-muted mb-2">
                            <i class="bi bi-shield-check me-1"></i>Verification Status
                        </h6>
                        <div id="verificationStatus"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a id="downloadPhotoBtn" href="#" class="btn btn-info" download>
                    <i class="bi bi-download me-2"></i>Download Photo
                </a>

                <!-- Verification Buttons (only show for pending RFID records) -->
                <div id="verificationButtons" class="d-none">
                    <button type="button" class="btn btn-success" onclick="approveFromModal()">
                        <i class="bi bi-check-circle me-2"></i>Approve
                    </button>
                    <button type="button" class="btn btn-danger" onclick="rejectFromModal()">
                        <i class="bi bi-x-circle me-2"></i>Reject
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal for Generated DTR Report -->
@if(session('generated_report_id'))
<div class="modal fade" id="generatedReportModal" tabindex="-1" aria-labelledby="generatedReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header header-maroon d-flex justify-content-between align-items-center">
                <h5 class="modal-title text-white mb-0" id="generatedReportModalLabel">
                    <i class="bi bi-check-circle me-2"></i>DTR Report Generated Successfully!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="bi bi-info-circle me-2"></i>
                    Your DTR report has been generated successfully! Report ID: <strong>#{{ session('generated_report_id') }}</strong>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="bi bi-eye display-4 text-primary mb-3"></i>
                                <h5>View Report Details</h5>
                                <p class="text-muted">Review the complete report with all employee attendance details</p>
                                <a href="{{ route('dtr.details', session('generated_report_id')) }}" class="btn btn-primary">
                                    <i class="bi bi-eye me-2"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="bi bi-download display-4 text-success mb-3"></i>
                                <h5>Download Report</h5>
                                <p class="text-muted">Download the report in your preferred format</p>
                                <div class="btn-group">
                                    <a href="{{ route('dtr.download', ['id' => session('generated_report_id'), 'format' => 'html']) }}" class="btn btn-success"><i class="bi bi-filetype-html me-2"></i>HTML</a>
                                    <a href="{{ route('dtr.download', ['id' => session('generated_report_id'), 'format' => 'pdf']) }}" class="btn btn-danger"><i class="bi bi-filetype-pdf me-2"></i>PDF</a>
                                    <a href="{{ route('dtr.download', ['id' => session('generated_report_id'), 'format' => 'excel']) }}" class="btn btn-warning"><i class="bi bi-file-earmark-excel me-2"></i>Excel</a>
                                    <a href="{{ route('dtr.download', ['id' => session('generated_report_id'), 'format' => 'csv']) }}" class="btn btn-secondary"><i class="bi bi-filetype-csv me-2"></i>CSV</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning text-dark fw-semibold" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

<!-- RFID Approval Confirmation Modal -->
<div class="modal fade" id="rfidApprovalModal" tabindex="-1" aria-labelledby="rfidApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rfidApprovalModalLabel">
                    <i class="fas fa-check-circle text-success me-2"></i>Approve RFID Attendance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-question-circle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="mb-3">Confirm RFID Attendance Approval</h6>
                    <p class="text-muted">Are you sure you want to approve this RFID attendance record? This action cannot be undone.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApprovalBtn">
                    <i class="fas fa-check me-1"></i>Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- RFID Rejection Modal -->
<div class="modal fade" id="rfidRejectionModal" tabindex="-1" aria-labelledby="rfidRejectionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rfidRejectionModalLabel">
                    <i class="fas fa-times-circle text-danger me-2"></i>Reject RFID Attendance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="text-center mb-3">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 3rem;"></i>
                    </div>
                    <h6 class="text-center mb-3">Reject RFID Attendance Record</h6>
                    <p class="text-muted text-center mb-3">Please provide a reason for rejecting this RFID attendance record:</p>
                </div>
                <form id="rfidRejectionForm">
                    <div class="form-group">
                        <label for="rejectionReason" class="form-label">Rejection Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="rejectionReason" name="rejection_reason" rows="3"
                            placeholder="Please explain why this attendance record is being rejected..." required></textarea>
                        <div class="invalid-feedback">
                            Please provide a reason for rejection.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectionBtn">
                    <i class="fas fa-times me-1"></i>Reject
                </button>
            </div>
        </div>
    </div>
</div>

@endsection