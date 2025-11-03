@extends('layouts.theme')

@section('title', 'Attendance')

@section('content')
<style>
    .scroll-hide::-webkit-scrollbar {
        display: none;
    }

    /* Simple black checkbox styling for DTR modal */
    #generateDTRModal .form-check-input {
        border: 2px solid #000 !important;
        border-radius: 3px !important;
        width: 18px !important;
        height: 18px !important;
        transform: scale(1.1);
    }

    #generateDTRModal .form-check-input:checked {
        background-color: #000 !important;
        border-color: #000 !important;
    }

    #generateDTRModal .form-check-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(0, 0, 0, 0.25) !important;
    }

    .filter-card {
        border: 1px solid #e9ecef;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
        overflow: hidden;
    }

    .filter-card .card-body {
        background: #ffffff;
        padding: 1rem 1.5rem;
    }

    .filter-card .form-control,
    .filter-card .form-select {
        border-radius: 10px;
        padding: 0.6rem 0.9rem;
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

    /* Inline Filters Layout */
    .filters-inline {
        display: flex;
        flex-wrap: wrap; /* allow wrapping instead of horizontal scroll */
        gap: 12px 16px;
        align-items: flex-end;
        overflow: visible; /* prevent scrollbars */
        padding-bottom: 4px;
    }
    .filters-inline .filter-item {
        flex: 1 1 220px; /* grow/shrink with a sensible base */
        min-width: 200px;
    }
    /* Toolbar row shouldn't stretch tall */
    .filters-inline .filter-toolbar-row { flex: 0 0 100%; }
    .filters-inline .quick-filter-group { width: auto; }
    .filters-inline .quick-select { width: 260px; }
    @media (max-width: 1200px) {
        .filters-inline .filter-item { flex-basis: 260px; }
    }
    @media (max-width: 992px) {
        .filters-inline { gap: 10px 12px; }
        .filters-inline .filter-item { flex-basis: 300px; }
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

    /* Center pagination controls consistently (including after AJAX refresh) */
    #paginationContainer nav { display: flex; justify-content: center; }
    #paginationContainer .pagination { justify-content: center; }
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
        <div class="card-header header-maroon">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-white fw-bold">
                    <i class="bi bi-funnel me-2"></i>Filter Attendance Records
                </h6>
                <small class="text-white-50 ms-2">
                    @if(auth()->user()->role->role_name === 'super_admin')
                    System-wide access
                    @else
                    Limited to {{ auth()->user()->department->department_name ?? 'your office' }}
                    @endif
                </small>
            </div>
        </div>
        <div class="card-body">
            <form method="GET" class="filters-inline" id="attendanceFiltersForm">
                <!-- Date Range Filters -->
                <div class="filter-item">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar-date me-1"></i>From Date
                    </label>
                    <input type="date"
                        name="start_date"
                        class="form-control"
                        value="{{ request('start_date', now()->subDays(7)->format('Y-m-d')) }}"
                        max="{{ date('Y-m-d') }}">
                    <div class="form-text small">Default: 7 days ago</div>
                </div>

                <div class="filter-item">
                    <label class="form-label fw-semibold">
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
                <div class="filter-item">
                    <label class="form-label fw-semibold">
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
                            All in {{ auth()->user()->department->department_name ?? 'Office' }} ({{ $empOptions->count() }})
                            @endif
                        </option>
                        @foreach($empOptions as $emp)
                        <option value="{{ $emp->employee_id }}"
                            {{ (string)request('employee_id') === (string)$emp->employee_id ? 'selected' : '' }}>
                            {{ $emp->full_name }}
                            @if(auth()->user()->role->role_name === 'super_admin')
                            <span class="text-muted">({{ $emp->department->department_name ?? 'No Office' }})</span>
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

                <!-- Office Filter (Super Admin Only) -->
                @if(auth()->user()->role->role_name === 'super_admin')
                <div class="filter-item">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-building me-1"></i>Office
                    </label>
                    <select name="department_id" class="form-select" id="departmentFilter">
                        <option value="">All Offices ({{ $departments->count() }})</option>
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
                <div class="filter-item">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-gear me-1"></i>Method
                    </label>
                    <select name="login_method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="rfid" {{ request('login_method') == 'rfid' ? 'selected' : '' }}>
                            RFID
                        </option>
                        <option value="fingerprint" {{ request('login_method') == 'fingerprint' ? 'selected' : '' }}>
                            Fingerprint
                        </option>
                        <option value="manual" {{ request('login_method') == 'manual' ? 'selected' : '' }}>
                            Manual
                        </option>
                    </select>
                    <div class="form-text small" style="height: 16px; visibility: hidden;">placeholder</div>
                </div>

                <!-- RFID Status Filter -->
                <div class="filter-item" id="rfidStatusFilter" @if(!(request('login_method')==='rfid' || auth()->user()->role->role_name === 'super_admin')) style="display: none;" @endif>
                    <label class="form-label fw-semibold">
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
                    <div class="form-text small" style="height: 16px; visibility: hidden;">placeholder</div>
                </div>

                <!-- Centered Quick Filters + Actions Toolbar Row -->
                <div class="filter-item filter-toolbar-row" style="flex-basis: 100%;">
                    <div class="d-flex justify-content-center w-100">
                        <div class="quick-filter-group text-center">
                            <div class="quick-inline d-flex justify-content-center align-items-center gap-2">
                                <div class="quick-texts text-end" style="min-width: 160px;">
                                    <div class="fw-semibold" style="line-height: 1.1;">
                                        <i class="bi bi-lightning me-1"></i>Quick Filters
                                    </div>
                                </div>
                                <select class="form-select quick-select" id="quickDateFilter">
                                    <option value="">Custom Range</option>
                                    <option value="today">Today</option>
                                    <option value="yesterday">Yesterday</option>
                                    <option value="this_week">This Week</option>
                                    <option value="last_week">Last Week</option>
                                    <option value="this_month">This Month</option>
                                    <option value="last_30_days">Last 30 Days</option>
                                </select>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-warning text-dark fw-semibold" id="applyFiltersBtn">
                                        <i class="bi bi-search me-1"></i>Apply
                                    </button>
                                    <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-clockwise me-1"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </div>
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
                                    <th scope="col">Office</th>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white;">
                <h5 class="modal-title fw-bold d-flex align-items-center mb-0">
                    <i class="bi bi-file-earmark-text me-2 fs-5"></i>Generate DTR Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <form action="{{ route('attendance.dtr') }}" method="POST" data-dtr-form="true">
                @csrf
                <div class="modal-body p-4" style="background: #fafbfc;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-gear me-2 fs-6"></i>Report Type
                            </label>
                            <select name="report_type" class="form-select form-select-lg border-2" required
                                    style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                    onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                    onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                <option value="weekly">Weekly Report</option>
                                <option value="monthly">Monthly Report</option>
                                <option value="custom">Custom Period</option>
                            </select>
                        </div>

                        @if(auth()->user()->role->role_name === 'super_admin')
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-building me-2 fs-6"></i>Office
                            </label>
                            <select name="department_id" class="form-select form-select-lg border-2" id="dtrDepartmentSelect"
                                    style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                    onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                    onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                <option value="">All Offices</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-building me-2 fs-6"></i>Office
                            </label>
                            <input type="text" class="form-control form-control-lg border-2" value="{{ auth()->user()->department->department_name ?? 'N/A' }}" readonly
                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; background-color: #f8f9fa; color: #6c757d;">
                            <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                            <div class="form-text">You can only generate reports for your department</div>
                        </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-people me-2 fs-6"></i>Employees to include
                            </label>
                            <div class="border rounded p-3" style="max-height:220px;overflow:auto; background: #fff; border-color: #dee2e6 !important;">
                                <div class="form-check mb-3 p-2" style="background: #f8f9fa; border-radius: 6px;">
                                    <input class="form-check-input" type="checkbox" id="empSelectAll">
                                    <label class="form-check-label fw-semibold" for="empSelectAll" style="color: var(--aa-maroon);">
                                        <i class="bi bi-check-all me-2"></i>Select All Employees
                                    </label>
                                </div>
                                @foreach(($employeesForDTR ?? []) as $emp)
                                <div class="form-check mb-2 p-2" style="border-left: 3px solid #e9ecef; padding-left: 10px;">
                                    <input class="form-check-input emp-item" type="checkbox" name="employee_ids[]" value="{{ $emp->employee_id }}" id="emp_{{ $emp->employee_id }}">
                                    <label class="form-check-label" for="emp_{{ $emp->employee_id }}" style="font-size: 0.95rem;">
                                        <strong>{{ $emp->full_name }}</strong> 
                                        <span class="text-muted ms-1">({{ $emp->department->department_name ?? 'N/A' }})</span>
                                    </label>
                                </div>
                                @endforeach
                                @if(($employeesForDTR ?? [])->isEmpty())
                                <div class="text-center p-3 text-muted">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    No employees found for DTR generation.
                                </div>
                                @endif
                            </div>
                            <div class="form-text">Leave empty to include all employees in the selected department.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-calendar-date me-2 fs-6"></i>Start Date
                            </label>
                            <input type="date" name="start_date" class="form-control form-control-lg border-2" value="{{ now()->startOfMonth()->toDateString() }}" required
                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                   onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            <div class="form-text">Defaults to the first day of this month</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-calendar-check me-2 fs-6"></i>End Date
                            </label>
                            <input type="date" name="end_date" class="form-control form-control-lg border-2" value="{{ now()->toDateString() }}" required
                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                   onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            <div class="form-text">Defaults to today</div>
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
                        <i class="bi bi-file-earmark-text me-2"></i>Generate Report
                    </button>
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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white;">
                <h5 class="modal-title fw-bold d-flex align-items-center">
                    <i class="bi bi-pencil-square me-2 fs-5"></i>Edit Attendance Record
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
            </div>
            <form action="{{ route('attendance.update', $log->log_id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="background: #fafbfc;">
                    <div class="row g-4">
                        <!-- Employee (Read-only) -->
                        <div class="col-md-12">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-person-circle me-2 fs-6"></i>Employee
                            </label>
                            <input type="text" class="form-control form-control-lg border-2" 
                                   value="{{ $log->employee->full_name ?? 'N/A' }}" readonly
                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; background-color: #f8f9fa; color: #6c757d;">
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-calendar3 me-2 fs-6"></i>Date
                            </label>
                            <input type="date" name="date" class="form-control form-control-lg border-2"
                                   value="{{ \Carbon\Carbon::parse($log->time_in)->format('Y-m-d') }}" required
                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                   onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        </div>

                        <!-- Method -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-gear me-2 fs-6"></i>Method
                            </label>
                            <select name="method" class="form-select form-select-lg border-2" required
                                    style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                    onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                    onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                <option value="rfid" {{ $log->method === 'rfid' ? 'selected' : '' }}>RFID</option>
                                <option value="fingerprint" {{ $log->method === 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                                <option value="manual" {{ $log->method === 'manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                        </div>

                        <!-- Time In -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-arrow-right-circle me-2 fs-6"></i>Time In
                            </label>
                            <input type="time" name="time_in" class="form-control form-control-lg border-2"
                                   value="{{ \Carbon\Carbon::parse($log->time_in)->format('H:i') }}" required
                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                   onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                        </div>

                        <!-- Time Out -->
                        <div class="col-md-6">
                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                <i class="bi bi-arrow-left-circle me-2 fs-6"></i>Time Out
                            </label>
                            <input type="time" name="time_out" class="form-control form-control-lg border-2"
                                   value="{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('H:i') : '' }}"
                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                   onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                   onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            <div class="form-text d-flex align-items-center mt-2" style="color: #6c757d; font-size: 0.875rem;">
                                <i class="bi bi-info-circle me-2"></i>
                                Leave empty if employee hasn't timed out yet
                            </div>
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
                        <i class="bi bi-check-circle me-2"></i>Update Record
                    </button>
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

    // Add event listener for delete buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.delete-attendance-btn')) {
            e.preventDefault();
            const logId = e.target.closest('.delete-attendance-btn').dataset.logId;
            console.log('Delete button clicked, logId:', logId); // Debug log
            deleteAttendance(logId);
        }
    });

    // Add event listener for edit buttons - simplified approach
    document.addEventListener('click', function(e) {
        // Check if clicked element is an edit button or inside an edit button
        const editButton = e.target.closest('button[data-bs-toggle="modal"]');
        if (editButton) {
            console.log('Edit button clicked'); // Debug log
            // Let Bootstrap handle the modal opening naturally
            // Don't prevent default or interfere
        }
    });

    // Handle attendance edit forms with AJAX for better UX
    document.addEventListener('DOMContentLoaded', function() {
        
        // Handle attendance edit forms (exclude DTR generation form)
        document.querySelectorAll('form[action*="attendance"]:not([data-dtr-form])').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                
                // Debug: Log form data
                console.log('Form data being sent:');
                for (let [key, value] of formData.entries()) {
                    console.log(key + ': ' + value);
                }
                
                // Debug: Check specific time fields
                const timeInField = form.querySelector('input[name="time_in"]');
                const timeOutField = form.querySelector('input[name="time_out"]');
                console.log('Time In field value:', timeInField ? timeInField.value : 'NOT FOUND');
                console.log('Time Out field value:', timeOutField ? timeOutField.value : 'NOT FOUND');
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                    }
                })
                .then(response => response.text())
                .then(html => {
                    // Parse the response to extract messages
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Check for success message
                    const successAlert = doc.querySelector('.alert-success');
                    const errorAlert = doc.querySelector('.alert-danger');
                    
                    if (successAlert) {
                        const message = successAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                        showNotification('success', message);
                        
                        // Close modal and reload page after short delay
                        if (modal) modal.hide();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else if (errorAlert) {
                        const message = errorAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                        showNotification('error', message);
                    } else {
                        showNotification('success', 'Attendance record updated successfully!');
                        if (modal) modal.hide();
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred while updating attendance.');
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        });
    });

    function deleteAttendance(logId) {
        console.log('deleteAttendance called with logId:', logId); // Debug log
        
        // Store the logId for later use
        document.getElementById('confirmDeleteBtn').dataset.logId = logId;
        
        console.log('Stored logId in confirmDeleteBtn:', document.getElementById('confirmDeleteBtn').dataset.logId); // Debug log
        
        // Show the custom delete modal
        const modal = new bootstrap.Modal(document.getElementById('deleteAttendanceModal'));
        modal.show();
    }

    function confirmDeleteAttendance() {
        const logId = document.getElementById('confirmDeleteBtn').dataset.logId;
        console.log('confirmDeleteAttendance called with logId:', logId); // Debug log
        
        if (!logId) {
            console.error('No logId found in confirmDeleteBtn dataset');
            alert('Error: No attendance record ID found. Please try again.');
            return;
        }
        
        try {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("attendance.destroy", ":logId") }}'.replace(':logId', logId);
            
            console.log('Form action URL:', form.action); // Debug log

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
            
            console.log('Submitting delete form for logId:', logId); // Debug log
            form.submit();
        } catch (error) {
            console.error('Error deleting attendance record:', error);
            alert('An error occurred while trying to delete the attendance record. Please try again.');
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
                // Re-initialize delete button handlers after content is loaded
                initializeDTRDeleteButtons();
                // Convert pagination links for AJAX handling
                const paginationContainer = document.getElementById('dtrPaginationContainer');
                if (paginationContainer) {
                    const paginationLinks = paginationContainer.querySelectorAll('.pagination a');
                    
                    // Define the onclick handler function
                    function handlePaginationClick(originalHref) {
                        return function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            
                            console.log('Direct onclick handler triggered for:', originalHref);
                            
                            // Show loading state
                            const modalBody = document.getElementById('dtrHistoryModalBody');
                            const originalContent = modalBody.innerHTML;
                            modalBody.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-maroon" role="status"></div><div class="mt-2">Loading page...</div></div>';
                            
                            fetch(originalHref)
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.text();
                                })
                                .then(html => {
                                    modalBody.innerHTML = html;
                                    // Re-initialize any event handlers that might be needed
                                    if (typeof initializeDTRDeleteButtons === 'function') {
                                        initializeDTRDeleteButtons();
                                    }
                                    // Convert pagination links for AJAX handling (recursive)
                                    const newPaginationContainer = document.getElementById('dtrPaginationContainer');
                                    if (newPaginationContainer) {
                                        const newPaginationLinks = newPaginationContainer.querySelectorAll('.pagination a');
                                        newPaginationLinks.forEach(function(newLink) {
                                            if (newLink.href && !newLink.dataset.url) {
                                                const newOriginalHref = newLink.href;
                                                newLink.href = 'javascript:void(0)';
                                                newLink.dataset.url = newOriginalHref;
                                                newLink.style.cursor = 'pointer';
                                                newLink.onclick = handlePaginationClick(newOriginalHref);
                                            }
                                        });
                                    }
                                    console.log('DTR Pagination loaded successfully via direct onclick');
                                })
                                .catch(error => {
                                    console.error('Error loading pagination:', error);
                                    modalBody.innerHTML = originalContent;
                                    alert('Error loading page. Please try again.');
                                });
                            
                            return false;
                        };
                    }
                    
                    paginationLinks.forEach(function(link) {
                        if (link.href && !link.dataset.url) {
                            const originalHref = link.href;
                            link.href = 'javascript:void(0)';
                            link.dataset.url = originalHref;
                            link.style.cursor = 'pointer';
                            link.onclick = handlePaginationClick(originalHref);
                        }
                    });
                    console.log('Initial modal pagination links converted:', paginationLinks.length);
                }
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
        console.log('Showing notification:', type, message); // Debug log
        
        const toastEl = document.getElementById(type + 'Toast');
        const messageEl = document.getElementById(type + 'Message');

        if (!toastEl) {
            console.error('Toast element not found:', type + 'Toast');
            return;
        }
        
        if (!messageEl) {
            console.error('Message element not found:', type + 'Message');
            return;
        }

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
        
        console.log('Notification shown successfully');
    }

    // Show notifications based on session messages
    document.addEventListener('DOMContentLoaded', function() {
        const bladePayload = document.getElementById('bladePayload');
        console.log('BladePayload element:', bladePayload); // Debug log
        console.log('BladePayload dataset:', bladePayload?.dataset); // Debug log
        
        const flashSuccessMessage = bladePayload?.dataset.success || '';
        const flashErrorMessage = bladePayload?.dataset.error || '';
        
        console.log('Flash messages - Success:', flashSuccessMessage, 'Error:', flashErrorMessage); // Debug log
        
        if (flashSuccessMessage) {
            // Small delay to ensure page is fully loaded
            setTimeout(() => {
                showNotification('success', flashSuccessMessage);
            }, 500);
        }
        
        if (flashErrorMessage) {
            // Small delay to ensure page is fully loaded
            setTimeout(() => {
                showNotification('error', flashErrorMessage);
            }, 500);
        }
        
        // Test delete button functionality
        testDeleteButtons();
        
        // Initialize DTR delete buttons (for when modal is loaded)
        initializeDTRDeleteButtons();
    });
    
    // Test function to verify delete buttons are working
    function testDeleteButtons() {
        const deleteButtons = document.querySelectorAll('.delete-attendance-btn');
        console.log('Found delete buttons:', deleteButtons.length); // Debug log
        
        deleteButtons.forEach((btn, index) => {
            const logId = btn.dataset.logId;
            console.log(`Delete button ${index + 1}: logId = ${logId}`); // Debug log
            
            if (!logId) {
                console.warn(`Delete button ${index + 1} has no logId data attribute`);
            }
        });
        
        // Check if modal elements exist
        const deleteModal = document.getElementById('deleteAttendanceModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        
        console.log('Delete modal exists:', !!deleteModal); // Debug log
        console.log('Confirm button exists:', !!confirmBtn); // Debug log
        
        if (!deleteModal) {
            console.error('Delete modal not found!');
        }
        
        if (!confirmBtn) {
            console.error('Confirm delete button not found!');
        }
    }
    
    // DTR Delete Functions
    function initializeDTRDeleteButtons() {
        // Remove any existing event listeners to prevent duplicates
        document.querySelectorAll('.delete-dtr-report-btn').forEach(btn => {
            btn.removeEventListener('click', handleDTRDeleteClick);
        });
        
        // Add event listeners to DTR delete buttons
        document.querySelectorAll('.delete-dtr-report-btn').forEach(btn => {
            btn.addEventListener('click', handleDTRDeleteClick);
        });
        
        console.log('Initialized DTR delete buttons:', document.querySelectorAll('.delete-dtr-report-btn').length);
    }
    
    function handleDTRDeleteClick(e) {
        e.preventDefault();
        const reportId = e.target.closest('.delete-dtr-report-btn').dataset.reportId;
        const reportType = e.target.closest('.delete-dtr-report-btn').dataset.reportType;
        const generatedOn = e.target.closest('.delete-dtr-report-btn').dataset.generatedOn;
        
        console.log('DTR delete button clicked:', { reportId, reportType, generatedOn });
        deleteDTRReport(reportId, reportType, generatedOn);
    }
    
    function deleteDTRReport(reportId, reportType, generatedOn) {
        console.log('deleteDTRReport called:', { reportId, reportType, generatedOn });
        
        // Store the reportId for later use
        document.getElementById('confirmDeleteDTRReportBtn').dataset.reportId = reportId;
        
        // Update modal content with report-specific information
        document.getElementById('deleteDTRReportMessage').innerHTML = 
            `Are you sure you want to delete this <strong>${reportType}</strong> DTR report?<br><strong class="text-danger">This action cannot be undone.</strong>`;
        
        document.getElementById('deleteDTRReportWarning').textContent = 
            `This will permanently remove the DTR report from the system. Generated on: ${generatedOn}`;
        
        // Show the custom delete modal
        const modal = new bootstrap.Modal(document.getElementById('deleteDTRReportModal'));
        modal.show();
    }
    
    function confirmDeleteDTRReport() {
        const reportId = document.getElementById('confirmDeleteDTRReportBtn').dataset.reportId;
        console.log('confirmDeleteDTRReport called with reportId:', reportId);
        
        if (!reportId) {
            console.error('No reportId found in confirmDeleteDTRReportBtn dataset');
            alert('Error: No DTR report ID found. Please try again.');
            return;
        }
        
        try {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("dtr.delete", ":reportId") }}'.replace(':reportId', reportId);
            
            console.log('Form action URL:', form.action);

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
            
            console.log('Submitting delete form for reportId:', reportId);
            form.submit();
        } catch (error) {
            console.error('Error deleting DTR report:', error);
            alert('An error occurred while trying to delete the DTR report. Please try again.');
        }
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
            <div class="modal-header header-maroon d-flex justify-content-between align-items-center">
                <h5 class="modal-title text-white mb-0">
                    <i class="bi bi-camera-fill me-2"></i>RFID Scan Photo & Verification
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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

<!-- Custom Delete Attendance Modal -->
<div class="modal fade" id="deleteAttendanceModal" tabindex="-1" aria-labelledby="deleteAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none;">
                <h5 class="modal-title" id="deleteAttendanceModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Attendance Record
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
                        Are you sure you want to delete this attendance record? 
                        <br><strong class="text-danger">This action cannot be undone.</strong>
                    </p>
                </div>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <small>This will permanently remove the attendance record from the system.</small>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDeleteAttendance()">
                    <i class="bi bi-trash me-1"></i>Delete Record
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Delete DTR Report Modal -->
<div class="modal fade" id="deleteDTRReportModal" tabindex="-1" aria-labelledby="deleteDTRReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none;">
                <h5 class="modal-title" id="deleteDTRReportModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete DTR Report
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
                    <p class="text-muted mb-0" id="deleteDTRReportMessage">
                        Are you sure you want to delete this DTR report? 
                        <br><strong class="text-danger">This action cannot be undone.</strong>
                    </p>
                </div>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <small id="deleteDTRReportWarning">This will permanently remove the DTR report from the system.</small>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteDTRReportBtn" onclick="confirmDeleteDTRReport()">
                    <i class="bi bi-trash me-1"></i>Delete Report
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

<style>
/* Ensure consistent alignment for filter elements */
/* Remove legacy grid helpers to avoid forced heights that created vertical scroll */
#attendanceFiltersForm .col-lg-2 { min-height: unset; display: initial; flex-direction: initial; }
#attendanceFiltersForm .form-label { min-height: unset; margin-bottom: 8px; }
#attendanceFiltersForm .form-control,
#attendanceFiltersForm .form-select { flex: initial; margin-bottom: 0; }
#attendanceFiltersForm .form-text { min-height: unset; margin-bottom: 0; }
#attendanceFiltersForm .d-grid { margin-top: 0; }
</style>