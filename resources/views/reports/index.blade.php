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

        <!-- Recent Reports -->
        <div class="aa-card">
            <div class="card-header header-maroon">
                <h4 class="card-title mb-0">
                    <i class="bi bi-clock-history me-2"></i>Recent Reports
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background: none;">
                            <tr>
                                <th style="color: var(--aa-maroon); padding: 0.75rem 1.25rem;"><i class="bi bi-hash me-1"></i>Report ID</th>
                                <th style="color: var(--aa-maroon); padding: 0.75rem 1.25rem;"><i class="bi bi-card-text me-1"></i>Title</th>
                                <th style="color: var(--aa-maroon); padding: 0.75rem 1.25rem;"><i class="bi bi-building me-1"></i>Department</th>
                                <th style="color: var(--aa-maroon); padding: 0.75rem 1.25rem;"><i class="bi bi-file-earmark-bar-graph me-1"></i>Type</th>
                                <th style="color: var(--aa-maroon); padding: 0.75rem 1.25rem;"><i class="bi bi-calendar-event me-1"></i>Generated</th>
                                <th style="color: var(--aa-maroon); padding: 0.75rem 1.25rem;"><i class="bi bi-gear me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(\App\Models\DTRReport::with(['admin', 'department'])->orderBy('generated_on', 'desc')->take(5)->get() as $report)
                                <tr>
                                    <td style="padding: 0.75rem 1.25rem; text-align: center;"><code class="text-primary">#{{ $report->report_id }}</code></td>
                                    <td style="padding: 0.75rem 1.25rem; text-align: left;">{{ $report->report_title }}</td>
                                    <td style="padding: 0.75rem 1.25rem; text-align: center;"><span class="badge bg-info">{{ $report->department->department_name ?? 'N/A' }}</span></td>
                                    <td style="padding: 0.75rem 1.25rem; text-align: center;"><span class="badge bg-secondary">{{ ucfirst($report->report_type) }}</span></td>
                                    <td style="padding: 0.75rem 1.25rem; text-align: center;">{{ $report->generated_on->format('M d, Y H:i') }}</td>
                                    <td style="padding: 0.75rem 1.25rem; text-align: center;">
                                        <a href="{{ route('dtr.details', $report->report_id) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                        No reports generated yet
                                        <br>
                                        <small>Generate your first report using the builder above</small>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
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
