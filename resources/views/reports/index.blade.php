@extends('layouts.theme')

@section('title', 'Reports')

@section('content')
    <div class="container-fluid">
        @php $period = request('period', 'week'); @endphp
        <!-- Success/Error Messages REMOVED (now handled by toasts)-->
        {{-- Alert banners removed --}}
        <!-- Header (no surrounding card) -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0"><i class="bi bi-graph-up me-2"></i>Reports</h4>
            <div class="d-flex align-items-center gap-2">
                <span class="text-muted small">Select Period</span>
                <select class="form-select form-select-sm" id="reportsPeriod">
                    <option value="week" {{ $period==='week' ? 'selected' : '' }}>Last 7 Days</option>
                    <option value="month" {{ $period==='month' ? 'selected' : '' }}>This Month</option>
                    <option value="quarter" {{ $period==='quarter' ? 'selected' : '' }}>Last 90 Days</option>
                </select>
            </div>
        </div>

        @php
            $today = \Carbon\Carbon::today();
            $start = match($period){
                'month' => $today->copy()->startOfMonth(),
                'quarter' => $today->copy()->subDays(89),
                default => $today->copy()->subDays(6),
            };
            $end = $today->copy();
            // Build date buckets
            $dates = [];
            for ($d = $start->copy(); $d <= $end; $d->addDay()) { $dates[$d->toDateString()] = 0; }
            $query = \App\Models\AttendanceLog::query()->whereBetween('time_in', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
            if (auth()->user()->role->role_name !== 'super_admin') {
                $query->whereHas('employee', function($q){ $q->where('department_id', auth()->user()->department_id); });
            }
            $daily = $query->clone()->selectRaw('DATE(time_in) as d, COUNT(*) as c')->groupBy('d')->orderBy('d')->pluck('c','d')->toArray();
            foreach($daily as $d => $c){ if(isset($dates[$d])) $dates[$d] = (int)$c; }
            $labels = array_map(fn($k)=>\Carbon\Carbon::parse($k)->format('M d'), array_keys($dates));
            $attendanceData = array_values($dates);
            $rfidCount = $query->clone()->where('method','rfid')->count();
            $fpCount = $query->clone()->where('method','fingerprint')->count();
        @endphp

        <!-- Quick Stats (move above charts) -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="aa-card" style="background: rgb(177, 12, 12); color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" style="color: #fff;">{{ \App\Models\Employee::count() }}</h4>
                                <p class="mb-0" style="color: #fff;">Total Employees</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-people display-6" style="color:#fff"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="aa-card" style="background: rgb(177, 12, 12); color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" style="color: #fff;">{{ \App\Models\Department::count() }}</h4>
                                <p class="mb-0" style="color: #fff;">Departments</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-building display-6" style="color:#fff"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="aa-card" style="background: rgb(177, 12, 12); color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" style="color: #fff;">{{ \App\Models\AttendanceLog::count() }}</h4>
                                <p class="mb-0" style="color: #fff;">Attendance Records</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-clock-history display-6" style="color:#fff"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="aa-card" style="background: rgb(177, 12, 12); color: #fff; box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between">
                            <div>
                                <h4 class="mb-0" style="color: #fff;">{{ \App\Models\DTRReport::count() }}</h4>
                                <p class="mb-0" style="color: #fff;">Generated Reports</p>
                            </div>
                            <div class="align-self-center">
                                <i class="bi bi-file-earmark-text display-6" style="color:#fff"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-8 mb-3">
                <div class="aa-card h-100" style="box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                    <div class="card-header header-maroon">
                        <h4 class="mb-0"><i class="bi bi-bar-chart-steps me-2"></i>Attendance Chart</h4>
                    </div>
                    <div class="card-body p-4">
                        <canvas id="attendanceBar" height="140"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mb-3">
                <div class="aa-card h-100" style="box-shadow: 0 4px 24px rgba(0,0,0,0.12);">
                    <div class="card-header header-maroon">
                        <h4 class="mb-0"><i class="bi bi-activity me-2"></i>Login Method Chart</h4>
                    </div>
                    <div class="card-body p-4 d-flex align-items-center justify-content-center">
                        @if(($rfidCount + $fpCount) > 0)
                            <div style="width: 500px; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                <canvas id="methodBar" height="300" style="display: block; margin: 100 auto;"></canvas>
                            </div>
                        @else
                            <div class="text-muted">No data for selected period</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Types Tiles (all trigger modal) -->
        <div class="row mb-4">
            @foreach($reportTypes as $key => $name)
                <div class="col-md-4 mb-3">
                    <div class="aa-card h-100">
                        <div class="card-body p-4 d-flex align-items-center">
                            <div class="me-3" style="width:48px;height:48px;border-radius:8px;background:{{ in_array($key,['attendance_summary','department_comparison']) ? 'var(--aa-yellow)' : 'var(--aa-maroon)' }};display:flex;align-items:center;justify-content:center;color:{{ in_array($key,['attendance_summary','department_comparison']) ? '#3d0a0a' : '#fff' }};">
                                <i class="bi {{ match($key){
                                    'attendance_summary' => 'bi-graph-up',
                                    'employee_performance' => 'bi-person-check',
                                    'department_comparison' => 'bi-building',
                                    'overtime_analysis' => 'bi-clock-history',
                                    'absenteeism_report' => 'bi-person-x',
                                    default => 'bi-gear'
                                } }}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $name }}</div>
                                <div class="text-muted small">Report type</div>
                            </div>
                            <button class="btn btn-outline-dark btn-sm" onclick="selectReportType('{{ $key }}')" data-bs-toggle="modal" data-bs-target="#reportBuilderModal">Generate</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Report Builder Modal (custom removed) -->
        <div class="modal fade" id="reportBuilderModal" tabindex="-1" aria-labelledby="reportBuilderModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
              <div class="modal-header header-maroon">
                <h4 class="modal-title" id="reportBuilderModalLabel"><i class="bi bi-gear me-2"></i>Build Report</h4>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body p-3">
                <form action="{{ route('reports.generate') }}" method="POST" id="reportForm">
                  @csrf
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">Report Type</label>
                        <select name="report_type" class="form-select" required id="modalReportType">
                          <option value="">Select Report Type</option>
                          @foreach($reportTypes as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">Export Format</label>
                        <select name="export_format" class="form-select">
                          <option value="">View in Browser</option>
                          <option value="excel">Excel (.xlsx)</option>
                          <option value="pdf">PDF</option>
                          <option value="csv">CSV</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date', now()->startOfMonth()->toDateString()) }}" required>
                        <div class="form-text">Defaults to the first day of this month</div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date', now()->toDateString()) }}" required>
                      </div>
                    </div>
                  </div>
                  @if(auth()->user()->role->role_name === 'super_admin')
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">Department</label>
                        <select name="department_id" class="form-select">
                          <option value="">All Departments</option>
                          @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                  @else
                  <div class="row">
                    <div class="col-md-6">
                      <div class="mb-2">
                        <label class="form-label">Department</label>
                        <input type="text" class="form-control" value="{{ auth()->user()->department->department_name ?? 'N/A' }}" readonly>
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                      </div>
                    </div>
                  </div>
                  @endif
                  <div class="d-flex justify-content-end mt-2">
                    <button type="submit" class="btn btn-warning text-dark fw-semibold">
                      <i class="bi bi-file-earmark-text me-2"></i>Generate Report
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <!-- DTR quick actions styles -->
        <style>
            .aa-fab-container { position: fixed; right: 24px; bottom: 24px; z-index: 1050; display: flex; gap: 12px; align-items: center; }
            .aa-fab-btn { border: none; border-radius: 9999px; padding: 0.85rem 1.15rem; font-weight: 700; letter-spacing: .2px; box-shadow: 0 10px 24px rgba(0,0,0,.18); transform: translateY(0); transition: transform .15s ease, box-shadow .2s ease, filter .2s ease; }
            .aa-fab-btn i { font-size: 1rem; }
            .aa-fab-btn:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(0,0,0,.24); filter: brightness(1.03); }
            .aa-fab-btn:active { transform: translateY(0); box-shadow: 0 8px 18px rgba(0,0,0,.18); }
            .aa-fab-history { color:#fff; background: linear-gradient(135deg, #dc3545, #a5111f); }
            .aa-fab-generate { color:#2e1f00; background: linear-gradient(135deg, #ffcf33, #ffb300); }
            /* Attention animations */
            @keyframes aaWiggle { 0% { transform: translateY(0) rotate(0); } 8% { transform: translateY(-2px) rotate(-2deg); } 16% { transform: translateY(0) rotate(2deg); } 24% { transform: translateY(-2px) rotate(-1.5deg); } 32% { transform: translateY(0) rotate(1.5deg); } 40% { transform: translateY(-1px) rotate(-1deg); } 48% { transform: translateY(0) rotate(0.8deg); } 56% { transform: translateY(-1px) rotate(-0.6deg); } 64% { transform: translateY(0) rotate(0.4deg); } 100% { transform: translateY(0) rotate(0); } }
            @keyframes aaGlowPulse { 0% { box-shadow: 0 10px 24px rgba(0,0,0,.18), 0 0 0 0 rgba(247, 201, 72, 0.0); } 50% { box-shadow: 0 14px 32px rgba(0,0,0,.24), 0 0 18px 6px rgba(255, 191, 0, 0.35); } 100% { box-shadow: 0 10px 24px rgba(0,0,0,.18), 0 0 0 0 rgba(247, 201, 72, 0.0); } }
            .aa-attention-wiggle { animation: aaWiggle 3.5s ease-in-out infinite; animation-delay: .8s; }
            .aa-attention-glow { animation: aaGlowPulse 2.8s ease-in-out infinite; }
            .aa-fab-btn:hover { animation-play-state: paused; }
            @media (prefers-reduced-motion: reduce) { .aa-attention-wiggle, .aa-attention-glow { animation: none; } }
            @media (max-width: 768px){ .aa-fab-container { right: 16px; bottom: 16px; gap: 8px; } .aa-fab-btn { padding: .7rem .95rem; font-weight: 600; } .aa-fab-btn span { display:none; } }
        </style>

        <!-- DTR quick actions (floating) -->
        <div class="aa-fab-container">
            <button type="button" class="aa-fab-btn aa-fab-history aa-attention-wiggle" id="openDTRHistoryModal" title="View DTR History">
                <i class="bi bi-clock-history me-2"></i><span>DTR History</span>
            </button>
            <button class="aa-fab-btn aa-fab-generate aa-attention-glow" data-bs-toggle="modal" data-bs-target="#generateDTRModal" title="Generate DTR Report">
                <i class="bi bi-file-earmark-text me-2"></i><span>Generate DTR Report</span>
            </button>
        </div>

        <!-- Generate DTR Modal (copied from attendance) -->
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
                                    <select name="report_type" id="dtrReportType" class="form-select form-select-lg border-2" required
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
                                        <i class="bi bi-building me-2 fs-6"></i>Department
                                    </label>
                                    <select name="department_id" class="form-select form-select-lg border-2" id="dtrDepartmentSelect"
                                            style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <option value="">All Departments</option>
                                        @foreach($departments as $dept)
                                        <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @else
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-building me-2 fs-6"></i>Department
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
                                    <input type="date" name="start_date" id="dtrStartDate" class="form-control form-control-lg border-2" value="{{ now()->startOfMonth()->toDateString() }}" required
                                           style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                           onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                           onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                    <div class="form-text" id="dtrStartDateHelp">Defaults to the first day of this month</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-calendar-check me-2 fs-6"></i>End Date
                                    </label>
                                    <input type="date" name="end_date" id="dtrEndDate" class="form-control form-control-lg border-2" value="{{ now()->toDateString() }}" required
                                           style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                           onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                           onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                    <div class="form-text" id="dtrEndDateHelp">Defaults to today</div>
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
    </div>

    <script>
        // DTR History modal loader with AJAX pagination (mirrors attendance)
        document.getElementById('openDTRHistoryModal')?.addEventListener('click', function() {
            var modal = new bootstrap.Modal(document.getElementById('dtrHistoryModal'));
            const modalBody = document.getElementById('dtrHistoryModalBody');
            modalBody.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-maroon" role="status"></div><div class="mt-3">Loading DTR history...</div></div>';

            function convertPaginationLinks(container) {
                if (!container) return;
                const links = container.querySelectorAll('.pagination a');

                function handlePaginationClick(originalHref) {
                    return function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        e.stopImmediatePropagation();

                        const originalContent = modalBody.innerHTML;
                        modalBody.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-maroon" role="status"></div><div class="mt-2">Loading page...</div></div>';

                        fetch(originalHref)
                            .then(response => { if (!response.ok) throw new Error('Network error'); return response.text(); })
                            .then(html => {
                                modalBody.innerHTML = html;
                                if (typeof initializeDTRDeleteButtons === 'function') {
                                    initializeDTRDeleteButtons();
                                }
                                convertPaginationLinks(document.getElementById('dtrPaginationContainer'));
                            })
                            .catch(error => {
                                console.error('Error loading pagination:', error);
                                modalBody.innerHTML = originalContent;
                                alert('Error loading page. Please try again.');
                            });
                        return false;
                    };
                }

                links.forEach(function(link) {
                    if (link.href && !link.dataset.url) {
                        const href = link.href;
                        link.href = 'javascript:void(0)';
                        link.dataset.url = href;
                        link.style.cursor = 'pointer';
                        link.onclick = handlePaginationClick(href);
                    }
                });
            }

            fetch("{{ route('dtr.history.modal') }}")
                .then(response => response.text())
                .then(html => {
                    modalBody.innerHTML = html;
                    if (typeof initializeDTRDeleteButtons === 'function') {
                        initializeDTRDeleteButtons();
                    }
                    convertPaginationLinks(document.getElementById('dtrPaginationContainer'));
                })
                .catch(() => {
                    modalBody.innerHTML = '<div class="text-center p-5 text-danger">Failed to load history.</div>';
                });
            modal.show();
        });

        // Match attendance modal helpers
        document.getElementById('empSelectAll')?.addEventListener('change', function(e) {
            document.querySelectorAll('.emp-item').forEach(cb => { cb.checked = e.target.checked; });
        });
        document.getElementById('dtrDepartmentSelect')?.addEventListener('change', function() {
            const deptId = this.value;
            document.querySelectorAll('.emp-item').forEach(cb => {
                const label = document.querySelector('label[for="' + cb.id + '"]');
                const text = label ? label.textContent : '';
                const show = !deptId || (text.includes('(') ? text.includes('(' + this.options[this.selectedIndex].text + ')') : true);
                cb.closest('.form-check').style.display = show ? '' : 'none';
            });
        });
        function selectReportType(type) {
            // Set the report type in the modal
            document.getElementById('modalReportType').value = type;
            // Show the modal (Bootstrap 5)
            var modal = new bootstrap.Modal(document.getElementById('reportBuilderModal'));
            modal.show();
        }
        // Robust cleanup: always restore scrolling after modal closes
        document.getElementById('reportBuilderModal').addEventListener('hidden.bs.modal', function () {
            setTimeout(function() {
                document.body.classList.remove('modal-open');
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.style.overflow = '';
            }, 10);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        // Period change
        document.getElementById('reportsPeriod')?.addEventListener('change', function(){
            const params = new URLSearchParams(window.location.search);
            params.set('period', this.value);
            window.location.search = params.toString();
        });

        // DTR Report Type Date Adjustment Script (same as attendance page)
        document.addEventListener('DOMContentLoaded', function() {
            const dtrReportType = document.getElementById('dtrReportType');
            const dtrStartDate = document.getElementById('dtrStartDate');
            const dtrEndDate = document.getElementById('dtrEndDate');
            const dtrStartDateHelp = document.getElementById('dtrStartDateHelp');
            const dtrEndDateHelp = document.getElementById('dtrEndDateHelp');
            
            if (dtrReportType && dtrStartDate && dtrEndDate) {
                // Function to format date as YYYY-MM-DD
                function formatDate(date) {
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${year}-${month}-${day}`;
                }
                
                // Function to get start of week (Monday)
                function getStartOfWeek(date) {
                    const d = new Date(date);
                    const day = d.getDay();
                    const diff = d.getDate() - day + (day === 0 ? -6 : 1); // Adjust when day is Sunday
                    return new Date(d.setDate(diff));
                }
                
                // Function to get end of week (Sunday)
                function getEndOfWeek(date) {
                    const d = new Date(date);
                    const day = d.getDay();
                    const diff = d.getDate() + (7 - day) % 7;
                    return new Date(d.setDate(diff));
                }
                
                // Function to update dates based on report type
                function updateDatesForReportType() {
                    const reportType = dtrReportType.value;
                    const today = new Date();
                    let startDate, endDate, startHelp, endHelp;
                    
                    if (reportType === 'weekly') {
                        // Set to current week (Monday to Sunday)
                        startDate = getStartOfWeek(today);
                        endDate = getEndOfWeek(today);
                        startHelp = 'Start of current week (Monday)';
                        endHelp = 'End of current week (Sunday)';
                    } else if (reportType === 'monthly') {
                        // Set to current month (1st to last day)
                        startDate = new Date(today.getFullYear(), today.getMonth(), 1);
                        endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                        startHelp = 'First day of current month';
                        endHelp = 'Last day of current month';
                    } else {
                        // Custom - don't auto-adjust
                        return;
                    }
                    
                    dtrStartDate.value = formatDate(startDate);
                    dtrEndDate.value = formatDate(endDate);
                    
                    if (dtrStartDateHelp) dtrStartDateHelp.textContent = startHelp;
                    if (dtrEndDateHelp) dtrEndDateHelp.textContent = endHelp;
                }
                
                // Listen for report type changes
                dtrReportType.addEventListener('change', updateDatesForReportType);
                
                // Initialize dates when modal opens
                const generateDTRModal = document.getElementById('generateDTRModal');
                if (generateDTRModal) {
                    generateDTRModal.addEventListener('shown.bs.modal', function() {
                        updateDatesForReportType();
                    });
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        (function(){
            if (typeof Chart === 'undefined') return;
            const ab = document.getElementById('attendanceBar');
            if (ab){
                new Chart(ab, { type: 'bar', data: { labels: {!! json_encode($labels) !!}, datasets: [{ label: 'Logs', data: {!! json_encode($attendanceData) !!}, backgroundColor: '#f7c948', borderWidth: 0, borderRadius: 4 }] }, options: { plugins: { legend: { display: false } }, scales: { x: { grid: { display:false } }, y: { beginAtZero:true, grid: { color:'#eee' } } } } });
            }
            const mb = document.getElementById('methodBar');
            if (mb){
                new Chart(mb, {
                    type: 'bar',
                    data: {
                        labels: ['RFID','Fingerprint'],
                        datasets: [{
                            data: [{{ $rfidCount }}, {{ $fpCount }}],
                            backgroundColor: ['#c21807','#f7c948'], // RFID: red, Fingerprint: yellow
                            borderWidth: 0,
                            borderRadius: 6
                        }]
                    },
                    options: {
                        plugins: { legend: { display: false } },
                        indexAxis: 'y',
                        scales: {
                            x: { beginAtZero:true, grid: { color:'#eee' } },
                            y: { grid: { display:false } }
                        }
                    }
                });
            }
        })();
    </script>
    @include('layouts.toast-js')
@endsection
