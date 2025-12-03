<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTR Report - {{ $reportData['office']->department_name ?? 'All Offices' }}</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            body { margin: 0; padding: 20px; }
            .container { max-width: 100% !important; }
        }
        
        .dtr-header {
            border: 2px solid #000;
            padding: 20px;
            margin-bottom: 30px;
            background: #f8f9fa;
        }
        
        .dtr-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .dtr-info {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
            background: white;
        }
        
        .dtr-table {
            border: 2px solid #000;
            margin-bottom: 30px;
        }
        
        .dtr-table th {
            background: #343a40;
            color: white;
            text-align: center;
            vertical-align: middle;
            border: 1px solid #000;
            padding: 12px 8px;
            font-size: 12px;
        }
        
        .dtr-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
            font-size: 11px;
        }
        
        .employee-section {
            margin-bottom: 40px;
            page-break-inside: avoid;
        }
        
        .employee-header {
            background: #e9ecef;
            padding: 10px;
            border: 1px solid #dee2e6;
            margin-bottom: 15px;
        }
        
        .signature-section {
            margin-top: 50px;
            border-top: 1px solid #000;
            padding-top: 20px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 auto;
            margin-top: 40px;
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body>
    <!-- Print and Back Buttons -->
    <div class="no-print">
        <div class="back-button">
            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Attendance
            </a>
        </div>
        <div class="print-button">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="bi bi-printer"></i> Print DTR
            </button>
        </div>
    </div>

    <div class="container-fluid" style="max-width: 1200px;">
        <!-- Office Selection -->
        <form method="GET" action="">
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="office_id" class="form-label fw-bold">Select Office</label>
                    <select name="office_id" id="office_id" class="form-select">
                        <option value="">All Offices</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->department_id }}" {{ request('office_id') == $dept->department_id ? 'selected' : '' }}>{{ $dept->department_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-8 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </div>
        </form>
        <!-- DTR Header -->
        <div class="dtr-header">
            <div class="dtr-title">
                Daily Time Record
            </div>
            <div class="row">
                <div class="col-md-6">
                    <strong>Office:</strong> {{ $reportData['office']->department_name ?? 'All Offices' }}
                </div>
                <div class="col-md-6 text-end">
                    <strong>Report Type:</strong> {{ ucfirst($reportData['report_type']) }}
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <strong>Period:</strong> {{ \Carbon\Carbon::parse($reportData['start_date'])->format('M d, Y') }} - {{ \Carbon\Carbon::parse($reportData['end_date'])->format('M d, Y') }}
                </div>
                <div class="col-md-6 text-end">
                    <strong>Generated:</strong> {{ \Carbon\Carbon::parse($reportData['generated_on'])->format('M d, Y h:i A') }}
                </div>
            </div>
        </div>

        <!-- Employee DTR Sections -->
        @foreach($reportData['employeeLogs'] as $employeeId => $logs)
            @php
                $employee = $logs->first()->employee;
                $currentDate = null;
                $dailyLogs = [];
                
                // Group logs by date
                foreach($logs->sortBy('time_in') as $log) {
                    $logDate = $log->time_in->toDateString();
                    if (!isset($dailyLogs[$logDate])) {
                        $dailyLogs[$logDate] = [];
                    }
                    $dailyLogs[$logDate][] = $log;
                }
            @endphp
            
            <div class="employee-section">
                <!-- Employee Header -->
                <div class="employee-header">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Employee Name:</strong> {{ $employee->full_name }}
                        </div>
                        <div class="col-md-3">
                            <strong>ID:</strong> {{ $employee->employee_id }}
                        </div>
                        <div class="col-md-3">
                            <strong>Type:</strong> {{ $employee->employment_type === 'faculty with designation' ? 'Faculty' : ucfirst($employee->employment_type) }}
                        </div>
                    </div>
                </div>

                <!-- DTR Table -->
                <div class="table-responsive">
                    <table class="table table-bordered dtr-table">
                        <thead>
                            <tr>
                                <th rowspan="2" style="width: 12%;">Date</th>
                                <th colspan="2">Morning</th>
                                <th colspan="2">Afternoon</th>
                                <th rowspan="2" style="width: 10%;">Total Hours</th>
                                <th rowspan="2" style="width: 10%;">Overtime</th>
                            </tr>
                            <tr>
                                <th style="width: 12%;">Time In</th>
                                <th style="width: 12%;">Time Out</th>
                                <th style="width: 12%;">Time In</th>
                                <th style="width: 12%;">Time Out</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($dailyLogs as $date => $dayLogs)
                                @php
                                    $morningIn = null;
                                    $morningOut = null;
                                    $afternoonIn = null;
                                    $afternoonOut = null;
                                    
                                    // Sort logs by time for the day
                                    $sortedLogs = $dayLogs->sortBy('time_in');
                                    $firstLog = $sortedLogs->first();
                                    $lastLog = $sortedLogs->last();
                                    
                                    if ($firstLog) {
                                        $morningIn = $firstLog->time_in;
                                        if ($firstLog->time_out) {
                                            $morningOut = $firstLog->time_out;
                                        }
                                    }
                                    
                                    if ($lastLog && $lastLog->time_in != $firstLog->time_in) {
                                        $afternoonIn = $lastLog->time_in;
                                        if ($lastLog->time_out) {
                                            $afternoonOut = $lastLog->time_out;
                                        }
                                    }
                                    
                                    // Calculate total hours
                                    $totalHours = 0;
                                    if ($morningIn && $afternoonOut) {
                                        $totalHours = $morningIn->diffInHours($afternoonOut, false);
                                    } elseif ($morningIn && $morningOut) {
                                        $totalHours = $morningIn->diffInHours($morningOut, false);
                                    }
                                    
                                    // Calculate overtime (assuming 8 hours is regular work day)
                                    $overtime = max(0, $totalHours - 8);
                                @endphp
                                
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</td>
                                    <td>{{ $morningIn ? $morningIn->format('h:i A') : '-' }}</td>
                                    <td>{{ $morningOut ? $morningOut->format('h:i A') : '-' }}</td>
                                    <td>{{ $afternoonIn ? $afternoonIn->format('h:i A') : '-' }}</td>
                                    <td>{{ $afternoonOut ? $afternoonOut->format('h:i A') : '-' }}</td>
                                    <td>{{ $totalHours > 0 ? number_format($totalHours, 1) : '-' }}</td>
                                    <td>{{ $overtime > 0 ? number_format($overtime, 1) : '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        <!-- Signature Section -->
        <div class="signature-section">
            <div class="row">
                <div class="col-md-6 text-center">
                    <p><strong>Prepared by:</strong></p>
                    <div class="signature-line"></div>
                    <p class="mt-2">{{ $reportData['admin']->username }}</p>
                    <small>Office Administrator</small>
                </div>
                <div class="col-md-6 text-center">
                    <p><strong>Certified Correct:</strong></p>
                    <div class="signature-line"></div>
                    <p class="mt-2">Office Head</p>
                    <small>Office Head</small>
                </div>
            </div>
        </div>

        <!-- Summary Section -->
        <div class="mt-5">
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Total Employees</h5>
                            <h3 class="text-primary">{{ count($reportData['employeeLogs']) }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Report Period</h5>
                            <h6 class="text-info">{{ \Carbon\Carbon::parse($reportData['start_date'])->format('M d') }} - {{ \Carbon\Carbon::parse($reportData['end_date'])->format('M d, Y') }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="card-title">Generated On</h5>
                            <h6 class="text-success">{{ \Carbon\Carbon::parse($reportData['generated_on'])->format('M d, Y') }}</h6>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 