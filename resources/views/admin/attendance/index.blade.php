@extends('layouts.app')

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
                <form action="{{ route('attendance.dtr') }}" method="POST" class="row g-3">
                    @csrf
                    
                    @if(auth()->user()->isSuperAdmin())
                    <div class="col-md-3">
                        <label for="report_department_id" class="form-label">Office</label>
                        <select name="department_id" id="report_department_id" class="form-select" required>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->department_id }}">
                                    {{ $dept->department_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        <input type="hidden" name="department_id" value="{{ auth()->user()->department_id }}">
                    @endif

                    <div class="col-md-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                    </div>

                    <div class="col-md-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                    </div>

                    <div class="col-md-2">
                        <label for="report_type" class="form-label">Report Type</label>
                        <select name="report_type" id="report_type" class="form-select" required>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>

                    <div class="col-md-1">
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
