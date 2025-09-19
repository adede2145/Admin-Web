@extends('layouts.theme')
@section('content')
    <style>
        .scroll-hide::-webkit-scrollbar { display: none; }
        .filter-card { border:1px solid #e9ecef; border-radius:12px; box-shadow:0 6px 18px rgba(0,0,0,.06); overflow:hidden; }
        .filter-card .card-body { background:#ffffff; padding:0.001rem 1.5rem 1rem 1.5rem; }
        .filter-card .form-control, .filter-card .form-select { border-radius:10px; padding:1.4rem 1.3rem 1rem 1.3rem; }
        .filter-card .row { margin:10 -0.5rem; }
        .filter-card .row > * { padding:0 0.5rem; }
        .filter-card .form-label { font-weight:600; color:#495057; }
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
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">From</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">To</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date', date('Y-m-d')) }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Employee</label>
                        @php
                            $empQuery = \App\Models\Employee::query();
                            if (auth()->user()->role->role_name !== 'super_admin') {
                                $empQuery->where('department_id', auth()->user()->department_id);
                            }
                            $empOptions = $empQuery->orderBy('full_name')->get();
                        @endphp
                        <select name="employee_id" class="form-select">
                            <option value="">All Employees</option>
                            @foreach($empOptions as $emp)
                                <option value="{{ $emp->employee_id }}" {{ (string)request('employee_id') === (string)$emp->employee_id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(auth()->user()->role->role_name === 'super_admin')
                    <div class="col-md-2">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">All Departments</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->department_id }}" {{ request('department_id') == $dept->department_id ? 'selected' : '' }}>
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <div class="col-md-2">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->department->department_name ?? 'N/A' }}" readonly>
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                        <div class="form-text small">Your department only</div>
                    </div>
                    @endif
                    <div class="col-md-1">
                        <label class="form-label">Method</label>
                        <select name="login_method" class="form-select">
                            <option value="">All</option>
                            <option value="rfid" {{ request('login_method') == 'rfid' ? 'selected' : '' }}>RFID</option>
                            <option value="fingerprint" {{ request('login_method') == 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                            <option value="manual" {{ request('login_method') == 'manual' ? 'selected' : '' }}>Manual</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                            <option value="on_time" {{ request('status') == 'on_time' ? 'selected' : '' }}>On Time</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning text-dark fw-semibold">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-x-circle me-2"></i>Clear
                            </a>
                        </div>
                    </div>
                </form>
                
                <!-- Active Filters Display -->
                @php
                    $activeFilters = [];
                    if (request('start_date') && request('start_date') !== date('Y-m-d')) {
                        $activeFilters[] = 'From: ' . \Carbon\Carbon::parse(request('start_date'))->format('M d, Y');
                    }
                    if (request('end_date') && request('end_date') !== date('Y-m-d')) {
                        $activeFilters[] = 'To: ' . \Carbon\Carbon::parse(request('end_date'))->format('M d, Y');
                    }
                    if (request('employee_id')) {
                        $emp = \App\Models\Employee::find(request('employee_id'));
                        $activeFilters[] = 'Employee: ' . ($emp ? $emp->full_name : 'Unknown');
                    }
                    if (request('department_id')) {
                        $dept = \App\Models\Department::find(request('department_id'));
                        $activeFilters[] = 'Department: ' . ($dept ? $dept->department_name : 'Unknown');
                    }
                    if (request('login_method')) {
                        $activeFilters[] = 'Method: ' . ucfirst(request('login_method'));
                    }
                    if (request('status')) {
                        $activeFilters[] = 'Status: ' . ucfirst(str_replace('_', ' ', request('status')));
                    }
                @endphp
                
                @if(count($activeFilters) > 0)
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center">
                        <span class="text-muted small me-2">
                            <i class="bi bi-funnel me-1"></i>Filters Applied:
                        </span>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($activeFilters as $filter)
                                <span class="badge bg-primary">{{ $filter }}</span>
                            @endforeach
                        </div>
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
                            <table class="table table-hover">
                                <thead style="background: #fff; position: sticky; top: 0; z-index: 10;">
                                    <tr>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">ID</th>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">Employee</th>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">Department</th>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">Time In</th>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">Time Out</th>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">Method</th>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">Kiosk</th>
                                        <th scope="col" style="color: var(--aa-maroon); font-weight: 700;">Actions</th>
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
    
    <script>
        // Auto-refresh functionality - automatically starts on page load
        let autoRefreshInterval = null;
        const refreshIntervalSeconds = 10; // Fixed 10 second interval

        document.addEventListener('DOMContentLoaded', function() {
            const lastUpdatedSpan = document.getElementById('lastUpdated');
            const refreshStatus = document.getElementById('refreshStatus');

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

        document.getElementById('empSelectAll')?.addEventListener('change', function(){
            document.querySelectorAll('.emp-item').forEach(cb => { cb.checked = event.target.checked; });
        });
        
        // Filter employee checklist by department selection (super admin only)
        document.getElementById('dtrDepartmentSelect')?.addEventListener('change', function(){
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
                form.action = '/attendance/' + logId;
                
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
        @if(session('generated_report_id'))
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('generatedReportModal'));
            modal.show();
        });
        @endif

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
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('success', '{{ session('success') }}');
            });
        @endif
        
        @if(session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('error', '{{ session('error') }}');
            });
        @endif

        // Photo viewing functions
        function showAttendancePhoto(logId, employeeName, dateTime) {
            const modal = new bootstrap.Modal(document.getElementById('photoModal'));
            const photoImage = document.getElementById('photoImage');
            const photoEmployeeName = document.getElementById('photoEmployeeName');
            const photoDateTime = document.getElementById('photoDateTime');
            const downloadBtn = document.getElementById('downloadPhotoBtn');
            const photoError = document.getElementById('photoError');
            const photoContent = document.getElementById('photoContent');
            const loadingSpinner = document.getElementById('photoLoadingSpinner');

            // Set employee info
            photoEmployeeName.textContent = employeeName;
            photoDateTime.textContent = 'Scanned on ' + dateTime;

            // Show loading state
            loadingSpinner.classList.remove('d-none');
            photoError.classList.add('d-none');
            photoContent.style.opacity = '0.5';

            // Set photo source and download link
            const photoUrl = '/attendance/' + logId + '/photo';
            photoImage.src = photoUrl;
            downloadBtn.href = photoUrl;
            downloadBtn.download = 'attendance_photo_' + logId + '_' + employeeName.replace(/\s+/g, '_') + '.jpg';

            modal.show();
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

    <!-- Photo Viewer Modal -->
    <div class="modal fade" id="photoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-camera-fill me-2"></i>RFID Scan Photo
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
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a id="downloadPhotoBtn" href="#" class="btn btn-primary" download>
                        <i class="bi bi-download me-2"></i>Download Photo
                    </a>
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
@endsection