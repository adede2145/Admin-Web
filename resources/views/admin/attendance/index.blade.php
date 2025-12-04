@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/style.css">
@endpush

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Attendance Logs</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('attendance.index') }}" method="GET" class="row g-3 mb-4">
                @if(auth()->user()->isSuperAdmin())
                <div class="col-md-3">
                    <label for="department_id" class="form-label">Office</label>
                    <select name="department_id" id="department_id" class="form-select">
                        <option value="">All Offices</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}" {{ request('department_id') == $dept->department_id ? 'selected' : '' }}>
                                {{ $dept->department_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-3">
                    <label for="period" class="form-label">Period</label>
                    <select name="period" id="period" class="form-select">
                        <option value="all" {{ request('period') == 'all' ? 'selected' : '' }}>All Time</option>
                        <option value="weekly" {{ request('period') == 'weekly' ? 'selected' : '' }}>Last Week</option>
                        <option value="monthly" {{ request('period') == 'monthly' ? 'selected' : '' }}>Last Month</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="method" class="form-label">Method</label>
                    <select name="method" id="method" class="form-select">
                        <option value="">All Methods</option>
                        <option value="rfid" {{ request('method') == 'rfid' ? 'selected' : '' }}>RFID</option>
                        <option value="fingerprint" {{ request('method') == 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Office</th>
                            <th>Timestamp</th>
                            <th>Method</th>
                            <th>Kiosk</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($logs as $log)
                            <tr>
                                <td>{{ $log->employee->full_name }}</td>
                                <td>{{ $log->employee->department->department_name }}</td>
                                <td>{{ $log->time_in->format('M d, Y h:i A') }}</td>
                                <td>
                                    <span class="badge bg-{{ $log->method == 'rfid' ? 'info' : 'success' }}">
                                        {{ ucfirst($log->method) }}
                                    </span>
                                </td>
                                <td>{{ $log->kiosk->location }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->withQueryString()->links() }}
            </div>

            <div class="mt-4">
                <h5>Generate DTR Report</h5>
                <form action="{{ route('attendance.dtr') }}" method="POST" class="row g-3" id="dtrGenerateForm">
                    @csrf
                    
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
                    
                    @if($showEmploymentTypeFilter)
                    <div class="col-md-3">
                        <label for="employment_type" class="form-label">Employment Type</label>
                        <select name="employment_type" id="employment_type" class="form-select" required>
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
                            <div class="col-md-3">
                                <label class="form-label">Employment Type</label>
                                <div class="form-control-plaintext">
                                    <strong>{{ $employmentTypeLabels[$singleType] ?? ucfirst($singleType) }}</strong>
                                </div>
                                <input type="hidden" name="employment_type" value="{{ $singleType }}">
                            </div>
                        @else
                            <div class="col-md-3">
                                <label for="employment_type" class="form-label">Employment Type</label>
                                <select name="employment_type" id="employment_type" class="form-select" required>
                                    <option value="">Select Employment Type</option>
                                    @foreach($allEmploymentTypes as $type)
                                        <option value="{{ $type }}">{{ $employmentTypeLabels[$type] ?? ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                    @endif

                    <div class="col-md-3">
                        <label for="month_picker" class="form-label">Month</label>
                        <input type="text" class="form-control" id="month_picker" placeholder="Select month" required readonly>
                        <input type="hidden" name="start_date" id="start_date" required>
                        <input type="hidden" name="end_date" id="end_date" required>
                    </div>

                    <input type="hidden" name="report_type" value="monthly">

                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-success d-block">
                            <i class="bi bi-file-earmark-text"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/monthSelect/index.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize flatpickr for month picker
    const monthPicker = flatpickr("#month_picker", {
        plugins: [
            new monthSelectPlugin({
                shorthand: false,
                dateFormat: "F Y",
                altFormat: "F Y"
            })
        ],
        defaultDate: new Date(),
        maxDate: new Date(),
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length > 0) {
                const selectedDate = selectedDates[0];
                const year = selectedDate.getFullYear();
                const month = selectedDate.getMonth();
                
                // Calculate first day of the month
                const firstDay = new Date(year, month, 1);
                const firstDayStr = firstDay.getFullYear() + '-' + 
                    String(firstDay.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(firstDay.getDate()).padStart(2, '0');
                
                // Calculate last day of the month
                const lastDay = new Date(year, month + 1, 0);
                const lastDayStr = lastDay.getFullYear() + '-' + 
                    String(lastDay.getMonth() + 1).padStart(2, '0') + '-' + 
                    String(lastDay.getDate()).padStart(2, '0');
                
                // Update hidden fields
                document.getElementById('start_date').value = firstDayStr;
                document.getElementById('end_date').value = lastDayStr;
            }
        }
    });
    
    // Set default month (current month)
    const now = new Date();
    const currentMonthStr = now.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    document.getElementById('month_picker').value = currentMonthStr;
    
    // Set default start and end dates
    const year = now.getFullYear();
    const month = now.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    
    document.getElementById('start_date').value = firstDay.getFullYear() + '-' + 
        String(firstDay.getMonth() + 1).padStart(2, '0') + '-' + 
        String(firstDay.getDate()).padStart(2, '0');
    
    document.getElementById('end_date').value = lastDay.getFullYear() + '-' + 
        String(lastDay.getMonth() + 1).padStart(2, '0') + '-' + 
        String(lastDay.getDate()).padStart(2, '0');
});
</script>
@endpush
