@extends('layouts.theme')
@section('content')
<style>
    /* Responsive DTR Details Styles */
    
    /* Mobile responsive adjustments */
    @media (max-width: 767.98px) {
        .card-header h3 {
            font-size: 1.25rem;
        }
        
        .card-header > .d-flex {
            gap: 0.75rem !important;
        }
        
        .card-header .btn {
            font-size: 0.875rem;
            padding: 0.5rem 0.75rem;
        }
        
        /* Hide less important columns on mobile */
        .hide-mobile {
            display: none !important;
        }
        
        /* Stack info cards */
        .info-card {
            margin-bottom: 1rem;
        }
        
        .info-card h3 {
            font-size: 1.5rem;
        }
        
        .info-card p {
            font-size: 0.875rem;
        }
        
        /* Adjust table font sizes */
        .table {
            font-size: 0.8rem;
        }
        
        .table th,
        .table td {
            padding: 0.5rem 0.25rem;
        }
        
        /* Modal adjustments */
        .modal-dialog {
            margin: 0.5rem;
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .modal-footer .btn {
            flex: 1;
        }
    }
    
    @media (min-width: 768px) and (max-width: 991.98px) {
        /* Hide some columns on tablets */
        .hide-tablet {
            display: none !important;
        }
        
        .card-header h3 {
            font-size: 1.5rem;
        }
    }
    
    @media (max-width: 991.98px) {
        /* Adjust card layout for tablets and below */
        .stat-card-row > div {
            margin-bottom: 1rem;
        }
    }
    
    /* Back button styling */
    .btn-back-dtr {
        background: transparent;
        border: none;
        color: white;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-back-dtr:hover {
        background: rgba(255, 255, 255, 0.3);
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
        transform: translateY(-1px);
    }
</style>

    <div class="container-fluid">
        <!-- Success/Error Messages REMOVED (handled by toasts)-->
        {{-- Alerts removed --}}
        <!-- Report Header -->
        <div class="aa-card mb-4">
            <div class="card-header header-maroon">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 w-100">
                    <div>
                        <h3 class="fw-bold mb-0 text-white">
                            <i class="bi bi-file-earmark-text me-2 fs-4"></i>{{ $report->report_title }}
                        </h3>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button onclick="goBack()" class="btn btn-back-dtr" title="Go back to previous page">
                            <i class="bi bi-arrow-left me-1"></i>
                            <span class="d-none d-sm-inline">Back</span>
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-warning dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-download me-1"></i>
                                <span class="d-none d-sm-inline">Download</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="{{ route('dtr.download', [$report->report_id, 'pdf']) }}"><i class="bi bi-filetype-pdf me-2 text-danger"></i>PDF</a></li>
                                <li><a class="dropdown-item" href="{{ route('dtr.download', [$report->report_id, 'docx']) }}"><i class="bi bi-filetype-docx me-2 text-info"></i>DOCX</a></li>
                                <li><a class="dropdown-item" href="{{ route('dtr.download', [$report->report_id, 'csv']) }}"><i class="bi bi-filetype-csv me-2 text-success"></i>CSV</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center stat-card-row">
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-primary"><i class="bi bi-building me-2"></i>{{ $report->department_name }}</h3>
                            <p class="mb-0">Office</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-success"><i class="bi bi-calendar-check me-2"></i>{{ ucfirst($report->report_type) }}</h3>
                            <p class="mb-0">Report Type</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-info"><i class="bi bi-calendar-range me-2"></i>{{ $report->formatted_period }}</h3>
                            <p class="mb-0">Period</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-warning"><i class="bi bi-people me-2"></i>{{ $report->total_employees }}</h3>
                            <p class="mb-0">Total Employees</p>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-12 col-md-6 mb-2 mb-md-0">
                        <p class="mb-0"><i class="bi bi-person-badge me-2"></i><strong>Generated By:</strong> {{ $report->admin_name }}</p>
                    </div>
                    <div class="col-12 col-md-6">
                        <p class="mb-0"><i class="bi bi-clock me-2"></i><strong>Generated On:</strong> {{ $report->formatted_generated_on }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employee Summaries -->
        <div class="aa-card mb-4">
            <div class="card-header header-maroon">
                <h5 class="mb-0 text-white">
                    <i class="bi bi-people me-2"></i>Employee Summaries
                </h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th><i class="bi bi-person"></i><span class="d-none d-sm-inline"> Employee</span></th>
                                <th class="hide-mobile"><i class="bi bi-calendar-check"></i><span class="d-none d-md-inline"> Total Days</span></th>
                                <th><i class="bi bi-check-circle"></i><span class="d-none d-sm-inline"> Present</span></th>
                                <th class="hide-mobile"><i class="bi bi-x-circle"></i><span class="d-none d-md-inline"> Absent</span></th>
                                <th class="hide-mobile hide-tablet"><i class="bi bi-exclamation-triangle"></i><span class="d-none d-lg-inline"> Incomplete</span></th>
                                <th><i class="bi bi-clock"></i><span class="d-none d-sm-inline"> Hours</span></th>
                                <th class="hide-mobile hide-tablet"><i class="bi bi-stopwatch"></i><span class="d-none d-lg-inline"> Overtime</span></th>
                                <th class="hide-mobile hide-tablet"><i class="bi bi-graph-up"></i><span class="d-none d-lg-inline"> Avg/Day</span></th>
                                <th><i class="bi bi-percent"></i><span class="d-none d-sm-inline"> Rate</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report->summaries as $summary)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $summary->employee->full_name }}</div>
                                        <small class="text-muted d-none d-sm-inline">ID: {{ $summary->employee->employee_id }}</small>
                                    </td>
                                    <td class="hide-mobile">{{ $summary->total_days }}</td>
                                    <td>
                                        <span class="badge bg-success">{{ $summary->present_days }}</span>
                                    </td>
                                    <td class="hide-mobile">
                                        <span class="badge bg-danger">{{ $summary->absent_days }}</span>
                                    </td>
                                    <td class="hide-mobile hide-tablet">
                                        <span class="badge bg-info">{{ $summary->incomplete_days }}</span>
                                    </td>
                                    <td class="fw-bold text-success">{{ $summary->total_hours_formatted }}</td>
                                    <td class="text-warning hide-mobile hide-tablet">{{ $summary->overtime_hours_formatted }}</td>
                                    <td class="hide-mobile hide-tablet">{{ $summary->average_hours_formatted }}</td>
                                    <td>
                                        <span class="badge {{ $summary->attendance_status_class }}">
                                            {{ $summary->attendance_rate_percentage }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Attendance Records -->
        @foreach($report->summaries as $summary)
            <div class="aa-card mb-4">
                <div class="card-header header-maroon">
                    <h5 class="mb-0">
                        <i class="bi bi-person me-2"></i>{{ $summary->employee->full_name }} - Detailed Attendance
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th><i class="bi bi-calendar-date"></i><span class="d-none d-sm-inline"> Date</span></th>
                                    <th class="hide-mobile"><i class="bi bi-calendar-week"></i><span class="d-none d-md-inline"> Day</span></th>
                                    <th><i class="bi bi-arrow-right-circle"></i><span class="d-none d-sm-inline"> Time In</span></th>
                                    <th><i class="bi bi-arrow-left-circle"></i><span class="d-none d-sm-inline"> Time Out</span></th>
                                    <th><i class="bi bi-clock"></i><span class="d-none d-sm-inline"> Hours</span></th>
                                    <th class="hide-mobile hide-tablet"><i class="bi bi-stopwatch"></i><span class="d-none d-lg-inline"> OT</span></th>
                                    <th><i class="bi bi-flag"></i><span class="d-none d-sm-inline"> Status</span></th>
                                    <th class="hide-mobile"><i class="bi bi-chat-text"></i><span class="d-none d-md-inline"> Remarks</span></th>
                                    <th class="no-print hide-mobile"><i class="bi bi-gear"></i><span class="d-none d-md-inline"> Actions</span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report->details->where('employee_id', $summary->employee_id) as $detail)
                                    <tr>
                                        <td>
                                            <span class="d-none d-sm-inline">{{ $detail->formatted_date }}</span>
                                            <span class="d-sm-none">{{ \Carbon\Carbon::parse($detail->date)->format('M d') }}</span>
                                        </td>
                                        <td class="hide-mobile">{{ \Carbon\Carbon::parse($detail->date)->format('D') }}</td>
                                        <td>{{ $detail->formatted_time_in }}</td>
                                        <td>{{ $detail->formatted_time_out }}</td>
                                        <td class="fw-bold">{{ number_format($detail->total_hours, 2) }}</td>
                                        <td class="text-warning hide-mobile hide-tablet">{{ number_format($detail->overtime_hours, 2) }}</td>
                                        @php
                                            $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
                                            $ovKey = $summary->employee->employee_id.'|'.$dateKey;
                                            $ov = isset($overrides) ? ($overrides[$ovKey] ?? null) : null;
                                        @endphp
                                        <td>
                                            @if($ov)
                                                <span class="badge bg-secondary">Leave</span>
                                            @else
                                                <span class="badge {{ $detail->status_badge_class }}">{{ ucfirst($detail->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="hide-mobile">
                                            <small class="text-muted">@if($ov) {{ 'Leave'.($ov->remarks?': '.$ov->remarks:'') }} @else {{ $detail->remarks }} @endif</small>
                                        </td>
                                        <td class="no-print hide-mobile">
                                            <div class="btn-group btn-group-sm">
                                                @if($ov)
                                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ovModal_{{ $summary->employee->employee_id }}_{{ \Carbon\Carbon::parse($detail->date)->toDateString() }}" title="Edit Leave">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger remove-override-btn" data-report-id="{{ $report->report_id }}" data-employee-id="{{ $summary->employee->employee_id }}" data-date="{{ \Carbon\Carbon::parse($detail->date)->toDateString() }}" title="Remove Leave">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#ovModal_{{ $summary->employee->employee_id }}_{{ \Carbon\Carbon::parse($detail->date)->toDateString() }}" title="Mark as Leave">
                                                        <i class="bi bi-clipboard-plus"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @foreach($report->details->where('employee_id', $summary->employee_id) as $detail)
            @php 
                $dateKey = \Carbon\Carbon::parse($detail->date)->toDateString();
                $ovKey = $summary->employee->employee_id.'|'.$dateKey; 
                $ov = isset($overrides) ? ($overrides[$ovKey] ?? null) : null;
            @endphp
            <div class="modal fade" id="ovModal_{{ $summary->employee->employee_id }}_{{ $dateKey }}" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                        <div class="modal-header header-maroon d-flex justify-content-between align-items-center">
                            <h5 class="modal-title text-white mb-0">@if($ov) Edit Leave @else Mark as Leave @endif - {{ $summary->employee->full_name }} ({{ \Carbon\Carbon::parse($detail->date)->format('M d, Y') }})</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="{{ route('dtr.override.store') }}" method="POST">
                            @csrf
                            <div class="modal-body p-4" style="background: #fafbfc;">
                                <input type="hidden" name="report_id" value="{{ $report->report_id }}">
                                <input type="hidden" name="employee_id" value="{{ $summary->employee->employee_id }}">
                                <input type="hidden" name="date" value="{{ $dateKey }}">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold" style="color: var(--aa-maroon);">Remarks (optional)</label>
                                    <input type="text" name="remarks" class="form-control form-control-lg border-2" placeholder="Reason or notes" value="{{ $ov? $ov->remarks : '' }}" style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px;">
                                </div>
                                <div class="form-text">This overlay does not change stored attendance, only how this DTR shows/exports.</div>
                            </div>
                            <div class="modal-footer border-0 p-4" style="background: white;">
                                <button type="button" class="btn btn-lg px-4 me-2" data-bs-dismiss="modal" style="background: #f8f9fa; color: #6c757d; border: 2px solid #e5e7eb; border-radius: 8px; font-weight: 600;">Cancel</button>
                                <button type="submit" class="btn btn-lg px-4 fw-bold text-white" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); border: none; border-radius: 8px;">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        @endforeach

        <!-- Report Summary -->
        <div class="aa-card mb-4">
            <div class="card-header header-maroon">
                <h5 class="mb-0 text-white">
                    <i class="bi bi-graph-up me-2"></i>Report Summary
                </h5>
            </div>
            <div class="card-body">
                <div class="row text-center stat-card-row">
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-primary">{{ $report->total_employees }}</h3>
                            <p class="mb-0">Total Employees</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-success">{{ $report->total_days }}</h3>
                            <p class="mb-0">Total Days</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-info">{{ number_format($report->total_hours, 2) }}</h3>
                            <p class="mb-0">Total Hours</p>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-md-3 mb-3 mb-md-0">
                        <div class="info-card rounded p-3">
                            <h3 class="text-warning">{{ $report->summaries->avg('attendance_rate') ? number_format($report->summaries->avg('attendance_rate'), 1) : '0' }}%</h3>
                            <p class="mb-0">Avg Attendance Rate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layouts.toast-js')
    <script>
        /**
         * Smart back button functionality
         * Goes back to the previous page in history, or falls back to DTR history
         */
        function goBack() {
            // Check if there's a previous page in history
            if (window.history.length > 1 && document.referrer) {
                window.history.back();
            } else {
                // Fallback to DTR history if no history
                window.location.href = "{{ route('dtr.history') }}";
            }
        }
        
        /**
         * Handle remove leave override button clicks
         */
        document.addEventListener('click', function (event) {
            const target = event.target.closest('.remove-override-btn');
            if (!target) return;

            const reportId = target.getAttribute('data-report-id');
            const employeeId = target.getAttribute('data-employee-id');
            const dateStr = target.getAttribute('data-date');

            if (!confirm('Remove leave for ' + dateStr + '?')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = "{{ route('dtr.override.destroy') }}";

            const csrf = document.createElement('input');
            csrf.type = 'hidden';
            csrf.name = '_token';
            csrf.value = '{{ csrf_token() }}';
            form.appendChild(csrf);

            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'DELETE';
            form.appendChild(method);

            const rid = document.createElement('input');
            rid.type = 'hidden';
            rid.name = 'report_id';
            rid.value = reportId;
            form.appendChild(rid);

            const emp = document.createElement('input');
            emp.type = 'hidden';
            emp.name = 'employee_id';
            emp.value = employeeId;
            form.appendChild(emp);

            const date = document.createElement('input');
            date.type = 'hidden';
            date.name = 'date';
            date.value = dateStr;
            form.appendChild(date);

            document.body.appendChild(form);
            form.submit();
        });
        
        /**
         * Keyboard shortcuts
         * Escape: Go back
         */
        document.addEventListener('keydown', function(e) {
            // Back shortcut (Escape key) - but only if no modal is open
            if (e.key === 'Escape' && !document.querySelector('.modal.show')) {
                goBack();
            }
        });
    </script>
@endsection
