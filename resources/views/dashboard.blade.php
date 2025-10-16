@extends('layouts.theme')

@section('title', 'Dashboard')

@section('content')
@include('layouts.toast-js')
<style>
    .scroll-hide {
        max-height: 520px;
        overflow-y: auto;
    }

    .scroll-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .scroll-hide::-webkit-scrollbar {
        display: none;
    }
</style>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-house text-muted fs-4"></i>
            <span class="fw-bold fs-2">Home Dashboard</span>
            @if(auth()->user()->role->role_name !== 'super_admin')
            <div class="ms-3">
                <span class="badge bg-info fs-6">
                    <i class="bi bi-building me-1"></i>{{ auth()->user()->department->department_name ?? 'N/A' }}
                </span>
            </div>
            @endif
        </div>
        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    @if(auth()->user()->role->role_name === 'super_admin')
                    All Employees
                    @else
                    {{ auth()->user()->department->department_name ?? 'Department' }} Employees
                    @endif
                </button>
                <ul class="dropdown-menu border-0 shadow rounded-3">
                    @if(auth()->user()->role->role_name === 'super_admin')
                    <li><a class="dropdown-item" href="#">All Employees</a></li>
                    <li><a class="dropdown-item" href="#">By Department</a></li>
                    @else
                    <li><a class="dropdown-item" href="#">Department Employees</a></li>
                    <li>
                        <h6 class="dropdown-header">{{ auth()->user()->department->department_name ?? 'N/A' }}</h6>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Select Period</span>
                @php $period = request('period', 'today'); @endphp
                <select class="form-select form-select-sm" id="dashPeriod">
                    <option value="today" {{ $period==='today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $period==='week' ? 'selected' : 'selected' }}>Last 7 Days</option>
                    <option value="month" {{ $period==='month' ? 'selected' : '' }}>This Month</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Stats Overview (Present, Late, Absent) -->
    @php
    $today = \Carbon\Carbon::today();

    // Role-based data scoping - Define this first
    $isSuper = auth()->user()->role->role_name === 'super_admin';
    $userDeptId = auth()->user()->department_id;

    // Stats: always for today with role-based scoping
    $logQueryToday = \App\Models\AttendanceLog::query()->whereDate('time_in', $today);
    if (!$isSuper) {
    $logQueryToday->whereHas('employee', function($q) use ($userDeptId){ $q->where('department_id', $userDeptId); });
    }
    $presentCount = (clone $logQueryToday)->distinct('employee_id')->count('employee_id');
    $lateCount = (clone $logQueryToday)->whereTime('time_in', '>', '08:00:00')->count();

    // Employee base query with role scoping
    $employeeBase = \App\Models\Employee::query();
    if (!$isSuper) {
    $employeeBase->where('department_id', $userDeptId);
    }
    $totalEmployeesToday = $employeeBase->count();
    $absentCount = max($totalEmployeesToday - $presentCount, 0);

    // RFID pending verifications count (role-scoped)
    $pendingRfidQuery = \App\Models\AttendanceLog::where('method', 'rfid')->where('verification_status', 'pending');
    if (!$isSuper) {
    $pendingRfidQuery->whereHas('employee', function($q) use ($userDeptId){ $q->where('department_id', $userDeptId); });
    }
    $pendingRfidCount = $pendingRfidQuery->count();

    // Pie chart period query with role scoping
    $start = $today; $end = $today->copy();
    if ($period === 'week') { $start = $today->copy()->subDays(6); }
    if ($period === 'month') { $start = $today->copy()->startOfMonth(); }
    $chartLogQuery = \App\Models\AttendanceLog::query()
    ->whereBetween('time_in', [$start->startOfDay(), $end->endOfDay()]);
    if (!$isSuper) {
    $chartLogQuery->whereHas('employee', function($q) use ($userDeptId){ $q->where('department_id', $userDeptId); });
    }
    $rfidCount = (clone $chartLogQuery)->where('method','rfid')->count();
    $fpCount = (clone $chartLogQuery)->where('method','fingerprint')->count();
    @endphp
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="aa-card h-100" style="background:rgb(177, 12, 12); color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div class="text-start">
                        <div class="display-1 fw-bold" style="color:#fff">{{ $presentCount }}</div>
                        <div class="small" style="color:#fff">
                            @if($isSuper)
                            PRESENT TODAY
                            @else
                            PRESENT TODAY
                            @endif
                        </div>
                    </div>
                    <i class="bi bi-person-check display-3 ms-3" style="color:#fff;"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="aa-card h-100" style="background: rgb(177, 12, 12);  color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div class="text-start">
                        <div class="display-1 fw-bold" style="color:#fff">{{ $absentCount }}</div>
                        <div class="small" style="color:#fff">
                            @if($isSuper)
                            ABSENT TODAY
                            @else
                            ABSENT TODAY
                            @endif
                        </div>
                    </div>
                    <i class="bi bi-person-x display-3 ms-3" style="color:#fff;"></i>
                </div>
            </div>
        </div>
        @if($pendingRfidCount > 0)
        <div class="col-md-4 mb-3">
            <div class="aa-card h-100" style="background: rgb(255, 193, 7); color: #000; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                <div class="card-body d-flex align-items-center justify-content-between p-4">
                    <div class="text-start">
                        <div class="display-1 fw-bold" style="color:#000">{{ $pendingRfidCount }}</div>
                        <div class="small fw-bold" style="color:#000">
                            RFID VERIFICATIONS PENDING
                        </div>
                        <a href="{{ route('attendance.index', ['rfid_status' => 'pending', 'login_method' => 'rfid']) }}"
                            class="btn btn-dark btn-sm mt-2">
                            <i class="bi bi-eye me-1"></i>Review
                        </a>
                    </div>
                    <i class="bi bi-exclamation-triangle display-3 ms-3" style="color:#000;"></i>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Today's Summary (move this above the charts) -->
    @php
    // Employee statistics based on role (using already defined $isSuper and $userDeptId)
    if ($isSuper) {
    $totalEmployees = \App\Models\Employee::count();
    $totalDepartments = \App\Models\Department::count();
    } else {
    // Department admin sees only their department's employees
    $totalEmployees = \App\Models\Employee::where('department_id', $userDeptId)->count();
    $totalDepartments = 1; // Only their own department
    }

    // Kiosk statistics - Super admin sees all, department admin sees department-specific
    if ($isSuper) {
    $totalKiosks = \App\Models\Kiosk::count();
    $activeKiosks = \App\Models\Kiosk::where('is_active', 1)->count();
    $onlineKiosks = \App\Models\Kiosk::online()->count();
    $offlineKiosks = \App\Models\Kiosk::offline()->count();
    } else {
    // Assuming kiosks have department_id or are linked to departments
    $totalKiosks = \App\Models\Kiosk::count(); // For now, show all kiosks
    $activeKiosks = \App\Models\Kiosk::where('is_active', 1)->count();
    $onlineKiosks = \App\Models\Kiosk::online()->count();
    $offlineKiosks = \App\Models\Kiosk::offline()->count();
    }

    // Calculate rates based on scoped data (use totalEmployeesToday for accurate daily calculations)
    $attendanceRate = $totalEmployeesToday > 0 ? round(($presentCount / $totalEmployeesToday) * 100, 1) : 0;
    $lateRate = $totalEmployeesToday > 0 ? round(($lateCount / $totalEmployeesToday) * 100, 1) : 0;
    $absentRate = $totalEmployeesToday > 0 ? round(($absentCount / $totalEmployeesToday) * 100, 1) : 0;
    @endphp
    <div class="row mb-4">
        <div class="col-12">
            <div class="aa-card" style="min-height: 200px; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                <div class="card-header header-yellow">
                    <h4 class="card-title mb-0">
                        <i class="bi bi-graph-up me-2"></i>
                        @if($isSuper)
                        System-wide Summary
                        @else
                        {{ auth()->user()->department->department_name ?? 'Department' }} Summary
                        @endif
                    </h4>
                    @if(!$isSuper)
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        You are viewing data specific to your department only
                    </small>
                    @endif
                </div>
                <div class="card-body p-4">
                    <div class="row text-center align-items-center justify-content-center g-4">
                        <!-- Key Statistics -->
                        <div class="col-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-success mb-2">
                                <i class="bi bi-people-fill me-2"></i>{{ $totalEmployees }}
                            </div>
                            <div class="fs-4 text-muted">
                                @if($isSuper)
                                Total Employees
                                @else
                                Department Employees
                                @endif
                            </div>
                        </div>
                        <div class="col-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-info mb-2">
                                <i class="bi bi-building me-2"></i>{{ $totalDepartments }}
                            </div>
                            <div class="fs-4 text-muted">
                                @if($isSuper)
                                Departments
                                @else
                                Your Department
                                @endif
                            </div>
                        </div>
                        <div class="col-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-warning mb-2">
                                <i class="bi bi-graph-up-arrow me-2"></i>{{ $attendanceRate }}%
                            </div>
                            <div class="fs-4 text-muted">Present Rate</div>
                        </div>
                        <div class="col-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-primary mb-2">
                                <i class="bi bi-pc-display-horizontal me-2"></i>{{ $onlineKiosks }}
                            </div>
                            <div class="fs-4 text-muted">Online Kiosks</div>
                            <div class="small text-muted">{{ $totalKiosks }} Total</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart + Recent Activity Row -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="aa-card h-100" style="min-height: 360px; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                <div class="card-header header-maroon">
                    <h4 class="mb-0">
                        <i class="bi bi-pie-chart me-2"></i>
                        @if($isSuper)
                        Login Method Distribution (System-wide)
                        @else
                        Login Method Distribution ({{ auth()->user()->department->department_name ?? 'Department' }})
                        @endif
                    </h4>
                </div>
                <div class="card-body position-relative">
                    <div class="position-absolute" style="top:.5rem; right:.75rem;">
                        <div class="small text-muted">
                            <span class="me-3"><i class="bi bi-square-fill" style="color:#c21807"></i> RFID</span>
                            <span><i class="bi bi-square-fill" style="color:#f7c948"></i> Fingerprint</span>
                        </div>
                    </div>
                    <div class="text-center d-flex justify-content-center align-items-center" style="min-height: 400px;">
                        @if(($rfidCount + $fpCount) > 0)
                        <div
                            style="width: 550px; height: 550px; display: flex; align-items: center; justify-content: center; margin: 0 auto; margin-top: 50px; margin-bottom: 0px;"
                            id="pieChartContainer">
                            <canvas id="loginPie" width="650" height="650" style="display: block; margin: 0 auto;"></canvas>
                        </div>
                        @else
                        <div class="text-muted d-flex align-items-center justify-content-center w-100" style="min-height: 350px;">
                            <span class="fs-1 fw-semibold">No data for selected period</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <!-- Recent Activity -->
            <div class="aa-card h-100" style="min-height: 720px; position: relative; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                <div class="card-header header-maroon">
                    <h1 class="card-title mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        @if($isSuper)
                        Recent Activity (All)
                        @else
                        Recent Activity ({{ auth()->user()->department->department_name ?? 'Department' }})
                        @endif
                    </h1>
                </div>
                <div class="card-body p-4" style="padding-bottom: 4.5rem !important;">
                    <div class="table-responsive scroll-hide">
                        <table class="table table-hover">
                            <thead style="background: #fff; position: sticky; top: 0; z-index: 10;">
                                <tr>
                                    <th scope="col" style="color: var(--aa-maroon); font-weight: 700;"><i class="bi bi-person me-1"></i>Employee</th>
                                    <th scope="col" style="color: var(--aa-maroon); font-weight: 700;"><i class="bi bi-clock me-1"></i>Time In</th>
                                    <th scope="col" style="color: var(--aa-maroon); font-weight: 700;"><i class="bi bi-fingerprint me-1"></i>Login Method</th>

                                </tr>
                            </thead>
                            <tbody>
                                @php
                                if (auth()->user()->role->role_name === 'super_admin') {
                                $recentLogs = \App\Models\AttendanceLog::with(['employee'])
                                ->latest('time_in')->paginate(8);
                                } else {
                                $recentLogs = \App\Models\AttendanceLog::with(['employee'])
                                ->whereHas('employee', function($q){ $q->where('department_id', auth()->user()->department_id); })
                                ->latest('time_in')->paginate(8);
                                }
                                @endphp
                                @forelse($recentLogs as $log)
                                <tr>
                                    <td>{{ $log->employee->full_name ?? 'N/A' }}</td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}
                                        <div class="text-muted small">{{ \Carbon\Carbon::parse($log->time_in)->format('M d') }}</div>
                                    </td>
                                    <td>{{ ucfirst($log->method) }}</td>

                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No attendance logs found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($recentLogs->hasPages())
                <div class="card-footer bg-white border-0 p-3 position-absolute w-100" style="left:0; bottom:0; z-index:2;">
                    <div class="d-flex justify-content-center m-0">
                        {{ $recentLogs->links('pagination::bootstrap-5') }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    let pieChartInstance = null;

    function renderPieChart(rfid, fp) {
        var canvas = document.getElementById('loginPie');
        if (!canvas || typeof Chart === 'undefined') return;
        if (pieChartInstance) {
            pieChartInstance.destroy();
        }
        pieChartInstance = new Chart(canvas, {
            type: 'pie', // Force pie chart
            data: {
                labels: ['RFID', 'Fingerprint'],
                datasets: [{
                    data: [rfid, fp],
                    backgroundColor: ['#c21807', '#f7c948'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                animations: {
                    numbers: {
                        type: 'number',
                        duration: 1200,
                        easing: 'easeOutQuart'
                    }
                }
            }
        });
    }
    document.addEventListener('DOMContentLoaded', function() {
        var sel = document.getElementById('dashPeriod');
        var canvas = document.getElementById('loginPie');
        // Always fetch and render for 'week' on load
        fetch(`/dashboard/piechart-data?period=week`)
            .then(res => res.json())
            .then(data => {
                renderPieChart(data.rfidCount, data.fpCount);
            });
        if (sel) {
            sel.addEventListener('change', function() {
                var period = this.value;
                fetch(`/dashboard/piechart-data?period=${period}`)
                    .then(res => res.json())
                    .then(data => {
                        renderPieChart(data.rfidCount, data.fpCount);
                    });
            });
        }
    });
</script>
@endsection