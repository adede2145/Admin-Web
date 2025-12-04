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

            /* DTR modal styling (match attendance page) */
            #generateDTRModal .form-check {
                padding-left: 0;
            }
            #generateDTRModal .form-check-input {
                border: 2px solid #000 !important;
                border-radius: 3px !important;
                width: 18px !important;
                height: 18px !important;
                transform: scale(1.1);
                margin-left: 0 !important;
                position: static !important;
            }
            #generateDTRModal .modal-dialog {
                max-width: 540px;
            }
            #generateDTRModal .modal-content {
                min-height: 460px;
                display: flex;
                flex-direction: column;
            }
            #generateDTRModal .modal-body {
                flex: 1 1 auto;
                overflow-y: auto;
                overflow-x: hidden;
            }
            @media (max-width: 768px) {
                #generateDTRModal .modal-dialog {
                    max-width: 100%;
                    width: 100%;
                    margin: 0.5rem;
                }
                #generateDTRModal .modal-content {
                    height: calc(100vh - 1rem);
                    min-height: calc(100vh - 1rem);
                }
                #generateDTRModal .modal-body {
                    padding: 1rem 1.25rem;
                    max-height: none;
                }
                #generateDTRModal .modal-footer {
                    position: sticky;
                    bottom: 0;
                    background: white;
                    z-index: 5;
                }
            }
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

        <!-- Generate DTR Modal (Attendance-style) -->
        <div class="modal fade" id="generateDTRModal" tabindex="-1" aria-labelledby="generateDTRModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white;">
                        <h5 class="modal-title fw-bold d-flex align-items-center mb-0" id="generateDTRModalLabel">
                            <i class="bi bi-file-earmark-text me-2 fs-5"></i>Generate Monthly DTR Report
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
                    </div>
                    <form action="{{ route('attendance.dtr') }}" method="POST" id="dtrForm" data-dtr-form="true">
                        @csrf
                        <div class="modal-body p-4" style="background-color: #f8f9fa;">
                            
                            @php
                                $admin = auth()->user();
                                $allEmploymentTypes = ['full_time', 'part_time', 'cos', 'admin', 'faculty with designation'];
                                
                                if ($admin->isSuperAdmin()) {
                                    $accessibleTypes = $allEmploymentTypes;
                                } else {
                                    $accessibleTypes = $admin->employment_type_access ?? [];
                                }
                                
                                $showEmploymentTypeFilter = count($accessibleTypes) > 1;
                                
                                // Map employment types to readable labels
                                $employmentTypeLabels = [
                                    'full_time' => 'Full-Time',
                                    'part_time' => 'Part-Time',
                                    'cos' => 'COS',
                                    'admin' => 'Admin',
                                    'faculty with designation' => 'Faculty'
                                ];
                            @endphp
                            
                            <div class="row g-3 mb-3">
                                @if($showEmploymentTypeFilter)
                                <div class="col-md-6">
                                    <label for="dtr_employment_type" class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-people me-2 fs-6"></i>Employment Type
                                    </label>
                                    <select class="form-select form-select-lg border-2" id="dtr_employment_type" name="employment_type" required
                                            style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <option value="">Select Employment Type</option>
                                        @foreach($accessibleTypes as $type)
                                            <option value="{{ $type }}">{{ $employmentTypeLabels[$type] ?? ucfirst($type) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @else
                                    @php
                                        $singleType = !empty($accessibleTypes) ? $accessibleTypes[0] : null;
                                    @endphp
                                    @if($singleType)
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                                <i class="bi bi-people me-2 fs-6"></i>Employment Type
                                            </label>
                                            <input type="text" class="form-control form-control-lg border-2" value="{{ $employmentTypeLabels[$singleType] ?? ucfirst($singleType) }}" readonly
                                                   style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; background-color: #f8f9fa; color: #6c757d;">
                                            <input type="hidden" name="employment_type" value="{{ $singleType }}">
                                        </div>
                                    @endif
                                @endif
                                
                                <div class="col-md-6">
                                    <label for="dtr_month_picker" class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-calendar me-2 fs-6"></i>Select Month
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control form-control-lg border-2" id="dtr_month_picker" placeholder="Click to select month" required readonly
                                               style="border-color: #e5e7eb; border-radius: 8px 0 0 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease; background-color: white; cursor: pointer;"
                                               onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <span class="input-group-text" style="background: var(--aa-maroon); color: white; border-color: var(--aa-maroon); cursor: pointer;" id="dtr_month_picker_btn">
                                            <i class="bi bi-calendar3"></i>
                                        </span>
                                    </div>
                                    <input type="hidden" name="start_date" id="dtr_start_date" required>
                                    <input type="hidden" name="end_date" id="dtr_end_date" required>
                                    <div class="form-text"><i class="bi bi-info-circle me-1"></i>Select the month for the DTR report</div>
                                </div>
                            </div>

                            <!-- Global employee search (filters list below) -->
                            <div class="mb-3">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="bi bi-search"></i>
                                    </span>
                                    <input type="text"
                                           class="form-control border-start-0 ps-0"
                                           id="employeeSearch"
                                           placeholder="Search employee..."
                                           autocomplete="off">
                                </div>
                            </div>
                            
                            <input type="hidden" name="report_type" value="monthly">

                            <div class="mb-3">
                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                    <i class="bi bi-people me-2 fs-6"></i>Employees to Include
                                </label>
                                
                                <!-- Select All + list container -->
                                <div class="bg-white p-2 border rounded-top border-bottom-0">
                                    <div class="form-check d-flex align-items-center mb-2 p-2" style="background: #f8f9fa; border-radius: 6px;">
                                        <input class="form-check-input" type="checkbox" id="empSelectAll">
                                        <label class="form-check-label fw-semibold ms-2" for="empSelectAll" style="color: var(--aa-maroon);">
                                            <i class="bi bi-check-all me-2"></i>Select All Employees
                                        </label>
                                    </div>
                                </div>

                                <div class="card rounded-top-0">
                                    <div class="card-body p-0" style="max-height: 250px; min-height: 200px; overflow-y: auto;">
                                        <ul class="list-group list-group-flush" id="employeeList">
                                            @foreach(($employeesForDTR ?? []) as $employee)
                                                <li class="list-group-item"
                                                    data-employment-type="{{ $employee->employment_type ?? '' }}">
                                                    <div class="form-check d-flex align-items-center">
                                                        <input class="form-check-input emp-item" type="checkbox" name="employee_ids[]" value="{{ $employee->id ?? $employee->employee_id }}" id="emp_dtr_{{ $employee->id ?? $employee->employee_id }}">
                                                        <label class="form-check-label w-100 ms-2" for="emp_dtr_{{ $employee->id ?? $employee->employee_id }}" style="font-size: 0.95rem;">
                                                            <strong>{{ $employee->full_name }}</strong>
                                                            @if($employee->department)
                                                                <small class="text-muted ms-1">
                                                                    ({{ $employee->department->department_name ?? 'N/A' }})
                                                                    @if(!empty($employee->employment_type))
                                                                        · {{ ucwords(str_replace('_', ' ', $employee->employment_type)) }}
                                                                    @endif
                                                                </small>
                                                            @endif
                                                        </label>
                                                    </div>
                                                </li>
                                            @endforeach
                                            @if(($employeesForDTR ?? [])->isEmpty())
                                            <li class="list-group-item text-center text-muted">
                                                <i class="bi bi-exclamation-triangle me-2"></i>
                                                No employees found for DTR generation.
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="modal-footer border-0 p-3 p-md-4" style="background: white;">
                            <div class="d-flex gap-2 flex-column flex-sm-row w-100">
                                <button type="button" class="btn btn-lg px-4 flex-fill" data-bs-dismiss="modal"
                                        style="background: #f8f9fa; color: #6c757d; border: 2px solid #e5e7eb; border-radius: 8px; font-weight: 600; transition: all 0.3s ease;"
                                        onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#dee2e6'"
                                        onmouseout="this.style.background='#f8f9fa'; this.style.borderColor='#e5e7eb'">
                                    <i class="bi bi-x-circle me-2"></i>Cancel
                                </button>
                                <button type="submit" class="btn btn-lg px-4 fw-bold text-white flex-fill"
                                        style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); border: none; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(86, 0, 0, 0.3);"
                                        onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(86, 0, 0, 0.4)'"
                                        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(86, 0, 0, 0.3)'">
                                    <i class="bi bi-file-earmark-text me-2"></i><span class="d-none d-sm-inline">Generate Report</span><span class="d-sm-none">Generate</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- DTR History Modal -->
        <div class="modal fade" id="dtrHistoryModal" tabindex="-1">
            <div class="modal-dialog modal-xl modal-dialog-scrollable">
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

        // Make functions globally available
        window.initializeDTRDeleteButtons = initializeDTRDeleteButtons;
        window.deleteDTRReport = deleteDTRReport;
        window.confirmDeleteDTRReport = confirmDeleteDTRReport;

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

        // (Legacy report-type date adjustment & search for old modal removed; 
        //  new DTR modal uses month picker + employee filters handled in attendance-style script.)
    </script>
    <!-- Custom Month/Year Picker + Employee Filters for DTR Modal (same as attendance page) -->
    <script>
    // Initialize Month/Year Picker for DTR Modal
    document.addEventListener('DOMContentLoaded', function() {
        const pickerInput = document.getElementById('dtr_month_picker');
        if (!pickerInput) return;
        
        function createMonthYearPicker() {
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth();
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                               'July', 'August', 'September', 'October', 'November', 'December'];
            pickerInput.value = monthNames[currentMonth] + ' ' + currentYear;
            updateHiddenDates(currentYear, currentMonth);
            
            const overlay = document.createElement('div');
            overlay.id = 'monthYearPickerOverlay';
            overlay.style.cssText = `
                position: fixed;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 12px 30px rgba(0,0,0,0.25);
                padding: 15px;
                z-index: 2000;
                display: none;
                width: 320px;
            `;
            
            const yearSelect = document.createElement('select');
            yearSelect.className = 'form-select mb-3';
            yearSelect.style.cssText = 'border-color: var(--aa-maroon);';
            for (let y = currentYear; y >= currentYear - 5; y--) {
                const option = document.createElement('option');
                option.value = y;
                option.textContent = y;
                if (y === currentYear) option.selected = true;
                yearSelect.appendChild(option);
            }
            
            const monthGrid = document.createElement('div');
            monthGrid.style.cssText = 'display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;';
            
            monthNames.forEach((monthName, index) => {
                const monthBtn = document.createElement('button');
                monthBtn.type = 'button';
                monthBtn.className = 'btn btn-sm';
                monthBtn.textContent = monthName.substring(0, 3);
                monthBtn.style.cssText = `
                    padding: 10px;
                    border: 1px solid #e5e7eb;
                    background: white;
                    transition: all 0.2s ease;
                `;
                
                if (index === currentMonth && parseInt(yearSelect.value) === currentYear) {
                    monthBtn.style.background = 'var(--aa-maroon)';
                    monthBtn.style.color = 'white';
                    monthBtn.style.fontWeight = 'bold';
                }
                
                monthBtn.addEventListener('mouseenter', function() {
                    if (!this.disabled) {
                        this.style.background = 'var(--aa-maroon)';
                        this.style.color = 'white';
                    }
                });
                monthBtn.addEventListener('mouseleave', function() {
                    if (!this.disabled && !(index === currentMonth && parseInt(yearSelect.value) === currentYear)) {
                        this.style.background = 'white';
                        this.style.color = 'black';
                    }
                });
                
                monthBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const selectedYear = parseInt(yearSelect.value);
                    const selectedMonth = index;
                    if (selectedYear === currentYear && selectedMonth > currentMonth) return;
                    pickerInput.value = monthName + ' ' + selectedYear;
                    updateHiddenDates(selectedYear, selectedMonth);
                    overlay.style.display = 'none';
                });
                
                monthGrid.appendChild(monthBtn);
            });
            
            yearSelect.addEventListener('change', function() {
                const selectedYear = parseInt(this.value);
                Array.from(monthGrid.children).forEach((btn, index) => {
                    if (selectedYear === currentYear && index > currentMonth) {
                        btn.disabled = true;
                        btn.style.opacity = '0.5';
                        btn.style.cursor = 'not-allowed';
                    } else {
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.style.cursor = 'pointer';
                    }
                    if (index === currentMonth && selectedYear === currentYear) {
                        btn.style.background = 'var(--aa-maroon)';
                        btn.style.color = 'white';
                    } else if (!btn.disabled) {
                        btn.style.background = 'white';
                        btn.style.color = 'black';
                    }
                });
            });
            
            overlay.appendChild(yearSelect);
            overlay.appendChild(monthGrid);
            document.body.appendChild(overlay);
            
            function toggleOverlay(e) {
                e.stopPropagation();
                const rect = pickerInput.getBoundingClientRect();
                let top = rect.bottom + 6;
                let left = rect.left;
                const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
                const overlayWidth = 320;
                if (left + overlayWidth + 10 > viewportWidth) {
                    left = viewportWidth - overlayWidth - 10;
                }
                const viewportHeight = window.innerHeight || document.documentElement.clientHeight;
                const overlayHeight = 260;
                if (top + overlayHeight + 10 > viewportHeight) {
                    top = rect.top - overlayHeight - 6;
                }
                overlay.style.top = `${top}px`;
                overlay.style.left = `${left}px`;
                overlay.style.display = overlay.style.display === 'none' ? 'block' : 'none';
            }
            
            pickerInput.addEventListener('click', toggleOverlay);
            const pickerBtn = document.getElementById('dtr_month_picker_btn');
            if (pickerBtn) pickerBtn.addEventListener('click', toggleOverlay);
            
            document.addEventListener('click', function(e) {
                if (!overlay.contains(e.target) && e.target !== pickerInput && e.target !== pickerBtn && !(pickerBtn && pickerBtn.contains(e.target))) {
                    overlay.style.display = 'none';
                }
            });
        }
        
        function updateHiddenDates(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const firstDayStr = year + '-' + String(month + 1).padStart(2, '0') + '-01';
            const lastDayStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(lastDay.getDate()).padStart(2, '0');
            const startInput = document.getElementById('dtr_start_date');
            const endInput = document.getElementById('dtr_end_date');
            if (startInput) startInput.value = firstDayStr;
            if (endInput) endInput.value = lastDayStr;
        }
        
        createMonthYearPicker();
        
        // Employment type + search filtering & Select All
        const selectAllCheckbox = document.getElementById('empSelectAll');
        const employeeCheckboxes = document.querySelectorAll('.emp-item');
        const employmentTypeSelect = document.getElementById('dtr_employment_type');
        const employeeSearchInput = document.getElementById('employeeSearch');
        const employeeListItems = document.querySelectorAll('#employeeList li');

        function applyEmployeeFilters() {
            const selectedType = employmentTypeSelect ? employmentTypeSelect.value : '';
            const searchTerm = employeeSearchInput ? employeeSearchInput.value.toLowerCase().trim() : '';

            employeeListItems.forEach(li => {
                const empType = (li.dataset.employmentType || '').toLowerCase();
                const label = li.querySelector('label');
                const text = (label ? label.textContent : '').toLowerCase();

                const typeMatches = !selectedType || empType === selectedType.toLowerCase();
                const searchMatches = !searchTerm || text.includes(searchTerm);

                li.style.display = (typeMatches && searchMatches) ? '' : 'none';
            });

            if (selectAllCheckbox && employeeCheckboxes.length > 0) {
                const visibleCheckboxes = Array.from(employeeCheckboxes).filter(cb =>
                    cb.closest('li').style.display !== 'none'
                );
                const checkedCount = visibleCheckboxes.filter(cb => cb.checked).length;
                selectAllCheckbox.checked = visibleCheckboxes.length > 0 && checkedCount === visibleCheckboxes.length;
            }
        }

        if (selectAllCheckbox && employeeCheckboxes.length > 0) {
            selectAllCheckbox.addEventListener('change', function() {
                employeeCheckboxes.forEach(checkbox => {
                    if (checkbox.closest('li').style.display !== 'none') {
                        checkbox.checked = this.checked;
                    }
                });
            });

            employeeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    applyEmployeeFilters();
                });
            });
        }

        if (employmentTypeSelect) {
            employmentTypeSelect.addEventListener('change', function() {
                if (selectAllCheckbox) selectAllCheckbox.checked = false;
                employeeCheckboxes.forEach(cb => cb.checked = false);
                applyEmployeeFilters();
            });
        }

        if (employeeSearchInput) {
            employeeSearchInput.addEventListener('keyup', function() {
                applyEmployeeFilters();
            });
        }

        applyEmployeeFilters();
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
