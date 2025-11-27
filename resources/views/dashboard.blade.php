@extends('layouts.theme')

@section('title', 'Dashboard')

@section('content')
@include('layouts.toast-js')
<style>
    .scroll-hide {
        max-height: 520px;
        overflow-y: auto;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .scroll-hide::-webkit-scrollbar {
        display: none;
    }

    /* Responsive Card Heights */
    .card-summary { min-height: 200px; }
    .card-chart { 
        min-height: 540px;
        display: flex;
        flex-direction: column;
    }
    .card-chart .card-body {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    .card-activity { min-height: 720px; }

    /* Pie Chart Responsive Sizing */
    .card-chart-body {
        min-height: 480px;
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    #pieChartContainer {
        width: 100%;
        max-width: 480px;
        aspect-ratio: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
    }

    @media (max-width: 1199px) {
        #pieChartContainer { max-width: 380px; }
    }

    @media (max-width: 991px) {
        .card-activity { min-height: 500px; }
        #pieChartContainer { max-width: 450px; }
    }

    @media (max-width: 767px) {
        .card-summary { min-height: auto; }
        .card-chart { min-height: auto; }
        .card-chart .card-body { min-height: auto; }
        .card-chart-body { min-height: 350px; }
        .card-activity { min-height: auto; }
        .display-3 { font-size: 2.5rem; } /* Fallback if clamp isn't enough */
        #pieChartContainer { max-width: 350px; }
    }

    /* Compact Pagination */
    .pagination-compact .pagination {
        margin-bottom: 0;
    }
    .pagination-compact .page-link {
        padding: 0.375rem 0.625rem;
        font-size: 0.8125rem;
        min-width: 35px;
        text-align: center;
    }
    .pagination-compact .page-item {
        margin: 0 1px;
    }
    .pagination-compact .page-item:first-child .page-link,
    .pagination-compact .page-item:last-child .page-link {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    @media (max-width: 1366px) {
        .pagination-compact .page-link {
            padding: 0.3rem 0.5rem;
            font-size: 0.75rem;
            min-width: 32px;
        }
    }
</style>
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3 gap-3">
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
        <div class="d-flex flex-wrap align-items-center gap-3">
            @if(auth()->user()->role->role_name === 'super_admin')
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-dark dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    All Employees
                </button>
                <ul class="dropdown-menu border-0 shadow rounded-3">
                    <li><a class="dropdown-item" href="#">All Employees</a></li>
                    <li><a class="dropdown-item" href="#">By Office</a></li>
                </ul>
            </div>
            @else
            <div class="btn btn-sm btn-outline-dark" style="cursor: default;">
                {{ auth()->user()->department->department_name ?? 'Department' }} Employees
            </div>
            @endif
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Select Period</span>
                @php $period = request('period', 'week'); @endphp
                <select class="form-select form-select-sm" id="dashPeriod">
                    <option value="today" {{ $period==='today' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $period==='week' ? 'selected' : '' }}>Last 7 Days</option>
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
            <div class="aa-card card-summary" style="box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
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
                        <div class="col-6 col-md-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-success mb-2" style="font-size: clamp(2rem, 4vw, 4.5rem);">
                                <i class="bi bi-people-fill me-2"></i>{{ $totalEmployees }}
                            </div>
                            <div class="fs-4 text-muted" style="font-size: clamp(1rem, 2vw, 1.5rem) !important;">
                                @if($isSuper)
                                Total Employees
                                @else
                                Department Employees
                                @endif
                            </div>
                        </div>
                        <div class="col-6 col-md-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-info mb-2" style="font-size: clamp(2rem, 4vw, 4.5rem);">
                                <i class="bi bi-building me-2"></i>{{ $totalDepartments }}
                            </div>
                            <div class="fs-4 text-muted" style="font-size: clamp(1rem, 2vw, 1.5rem) !important;">
                                @if($isSuper)
                                Departments
                                @else
                                Your Department
                                @endif
                            </div>
                        </div>
                        <div class="col-6 col-md-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-warning mb-2" style="font-size: clamp(2rem, 4vw, 4.5rem);">
                                <i class="bi bi-graph-up-arrow me-2"></i>{{ $attendanceRate }}%
                            </div>
                            <div class="fs-4 text-muted" style="font-size: clamp(1rem, 2vw, 1.5rem) !important;">Present Rate</div>
                        </div>
                        <div class="col-6 col-md-3 d-flex flex-column align-items-center justify-content-center">
                            <div class="display-3 fw-bold text-primary mb-2" style="font-size: clamp(2rem, 4vw, 4.5rem);">
                                <i class="bi bi-pc-display-horizontal me-2"></i>{{ $onlineKiosks }}
                            </div>
                            <div class="fs-4 text-muted" style="font-size: clamp(1rem, 2vw, 1.5rem) !important;">Online Kiosks</div>
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
            <div class="aa-card card-chart h-100" style="box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
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
                <div class="card-body position-relative d-flex flex-column" style="padding: 0;">
                    <div class="position-absolute" style="top:.5rem; right:.75rem; z-index: 10;">
                        <div class="small text-muted">
                            <span class="me-3"><i class="bi bi-square-fill" style="color:#c21807"></i> RFID</span>
                            <span><i class="bi bi-square-fill" style="color:#f7c948"></i> Fingerprint</span>
                        </div>
                    </div>
                    <div class="card-chart-body d-flex justify-content-center align-items-center" style="padding: 2rem 1rem; position: relative; flex: 1;">
                        <div id="pieChartContainer">
                            <canvas id="loginPie" style="display: block; width: 100%; height: 100%;"></canvas>
                        </div>
                        <div id="noDataMessage" class="text-muted position-absolute w-100 h-100 d-none align-items-center justify-content-center" style="top: 0; left: 0;">
                            <span class="fs-4 fw-semibold">No data for selected period</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <!-- Recent Activity -->
            <div class="aa-card card-activity h-100" style="position: relative; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
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
                <div class="card-footer bg-white border-0 p-2 position-absolute w-100" style="left:0; bottom:0; z-index:2;">
                    <div class="pagination-compact d-flex justify-content-center align-items-center">
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
        var noDataMsg = document.getElementById('noDataMessage');
        var chartContainer = document.getElementById('pieChartContainer');
        
        if (!canvas || typeof Chart === 'undefined') return;
        
        // Check if there's any data
        if (rfid === 0 && fp === 0) {
            // Hide chart, show no data message
            if (chartContainer) chartContainer.classList.add('d-none');
            if (noDataMsg) {
                noDataMsg.classList.remove('d-none');
                noDataMsg.classList.add('d-flex');
            }
            if (pieChartInstance) {
                pieChartInstance.destroy();
                pieChartInstance = null;
            }
            return;
        }
        
        // Show chart, hide no data message
        if (chartContainer) chartContainer.classList.remove('d-none');
        if (noDataMsg) {
            noDataMsg.classList.add('d-none');
            noDataMsg.classList.remove('d-flex');
        }
        
        if (pieChartInstance) {
            pieChartInstance.destroy();
        }
        pieChartInstance = new Chart(canvas, {
            type: 'pie',
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
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.parsed || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = ((value / total) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
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
        
        // Fetch data for the currently selected period on load
        var initialPeriod = sel ? sel.value : 'week';
        fetch(`/dashboard/piechart-data?period=${initialPeriod}`)
            .then(res => res.json())
            .then(data => {
                renderPieChart(data.rfidCount, data.fpCount);
            })
            .catch(err => {
                console.error('Error fetching chart data:', err);
            });
        
        if (sel) {
            sel.addEventListener('change', function() {
                var period = this.value;
                fetch(`/dashboard/piechart-data?period=${period}`)
                    .then(res => res.json())
                    .then(data => {
                        renderPieChart(data.rfidCount, data.fpCount);
                    })
                    .catch(err => {
                        console.error('Error fetching chart data:', err);
                    });
            });
        }
    });
</script>
@endsection