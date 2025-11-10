<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reportData['title'] }} - {{ config('app.name', 'AUTO AUDIT') }}</title>
    
    <!-- Preload critical CSS -->
    <link rel="preload" as="style" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="preload" as="style" href="{{ asset('css/bootstrap-icons-full.css') }}">
    
    <!-- Bootstrap CSS - LOCAL -->
    <link href="{{ asset('css/bootstrap.min.css') }}" rel="stylesheet">
    
    <!-- Bootstrap Icons - LOCAL (COMPLETE) -->
    <link rel="stylesheet" href="{{ asset('css/bootstrap-icons-full.css') }}">
    
    <!-- Chart.js - Keep CDN for now (specialized library) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --aa-maroon: #560000;
            --aa-maroon-dark: #3a0000;
            --aa-yellow: #ffc107;
            --aa-sidebar: #450000;
            --aa-gradient: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark));
            --aa-shadow: 0 8px 32px rgba(86, 0, 0, 0.15), 0 2px 8px rgba(86, 0, 0, 0.1);
            --aa-shadow-hover: 0 12px 40px rgba(86, 0, 0, 0.25), 0 4px 12px rgba(86, 0, 0, 0.15);
        }
        
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .aa-card {
            box-shadow: var(--aa-shadow) !important;
            border: 1px solid rgba(86, 0, 0, 0.1);
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        
        .aa-card:hover {
            box-shadow: var(--aa-shadow-hover) !important;
            transform: translateY(-2px);
        }
        
        .header-maroon { 
            background: var(--aa-gradient) !important; 
            color: #fff !important;
            border-radius: 12px 12px 0 0;
            border: none;
        }
        
        .metric-card { 
            transition: all 0.3s ease;
            border-radius: 12px;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--aa-gradient);
        }
        
        .metric-card:hover { 
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--aa-shadow-hover);
            border-color: var(--aa-maroon);
        }
        
        .metric-card.bg-primary {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
        }
        
        .metric-card.bg-success {
            background: linear-gradient(135deg, #28a745, #1e7e34) !important;
        }
        
        .metric-card.bg-info {
            background: linear-gradient(135deg, #17a2b8, #117a8b) !important;
        }
        
        .metric-card.bg-warning {
            background: linear-gradient(135deg, #ffc107, #e0a800) !important;
            color: #212529 !important;
        }
        
        .metric-card.bg-danger {
            background: linear-gradient(135deg, #dc3545, #c82333) !important;
        }
        
        .table-enhanced {
            border-radius: 12px;
            overflow: hidden;
            border: 2px solid rgba(86, 0, 0, 0.1);
        }
        
        .table-enhanced thead {
            background: var(--aa-gradient);
            color: white;
        }
        
        .table-enhanced thead th {
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
        }
        
        .table-enhanced tbody tr {
            transition: all 0.2s ease;
            border-bottom: 1px solid rgba(86, 0, 0, 0.05);
        }
        
        .table-enhanced tbody tr:hover {
            background: linear-gradient(90deg, rgba(86, 0, 0, 0.05), rgba(86, 0, 0, 0.02));
            transform: scale(1.01);
        }
        
        .table-enhanced tbody td {
            padding: 1rem;
            border: none;
            vertical-align: middle;
        }
        
        .export-btn {
            border-radius: 8px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .export-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .export-btn:hover::before {
            left: 100%;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .btn-success.export-btn {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            border-color: #1e7e34;
        }
        
        .btn-danger.export-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border-color: #c82333;
        }
        
        .btn-info.export-btn {
            background: linear-gradient(135deg, #17a2b8, #117a8b);
            border-color: #117a8b;
        }
        
        .report-title {
            background: var(--aa-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 700;
        }
        
        .section-divider {
            height: 3px;
            background: var(--aa-gradient);
            border-radius: 2px;
            margin: 2rem 0;
        }
        
        .stats-table {
            background: white;
            border-radius: 12px;
            border: 2px solid rgba(86, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .stats-table tbody tr:nth-child(even) {
            background: rgba(86, 0, 0, 0.02);
        }
        
        .stats-table tbody tr:hover {
            background: rgba(86, 0, 0, 0.05);
        }
        
        .stats-table th {
            background: var(--aa-gradient);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }
        
        .stats-table td {
            padding: 1rem;
            border: none;
            font-weight: 500;
        }
        
        .badge-enhanced {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media print {
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            .aa-card { box-shadow: none !important; }
            .metric-card:hover { transform: none !important; }
        }
    </style>
</head>
<body>
    <!-- Page Header (matches main design) -->
    <div class="no-print mb-4">
        <div class="container">
            <div class="aa-card">
                <div class="card-header header-maroon d-flex justify-content-between align-items-center">
                    <h4 class="mb-0 text-white"><i class="bi bi-graph-up me-2"></i>{{ $reportData['title'] }}</h4>
                    <div class="d-flex align-items-center gap-2">
                        <a class="btn btn-warning btn-sm text-dark fw-semibold" href="{{ route('reports.index') }}">
                            <i class="bi bi-arrow-left me-1"></i>Back to Reports
                        </a>
                        <button onclick="window.print()" class="btn btn-warning btn-sm text-dark fw-semibold">
                            <i class="bi bi-printer me-1"></i>Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Report Info -->
        <div class="aa-card mb-4">
            <div class="card-header header-maroon">
                <h5 class="mb-0 text-white"><i class="bi bi-file-earmark-text me-2"></i>Report Details</h5>
            </div>
            <div class="card-body text-center">
                <h1 class="display-6 mb-2 report-title">{{ $reportData['title'] }}</h1>
                <p class="lead mb-1 fw-semibold" style="color: var(--aa-maroon);">Period: {{ $reportData['period'] }}</p>
                <p class="mb-0 text-muted">Generated by: {{ $reportData['generated_by'] }} on {{ $reportData['generated_on']->format('M d, Y \\a\\t h:i A') }}</p>
            </div>
        </div>

        <!-- Export Options -->
        <div class="card shadow-sm mb-4 no-print">
            <div class="card-header header-maroon">
                <h5 class="mb-0 text-white">
                    <i class="bi bi-download me-2"></i>Export Options
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <span></span>
                    <div class="btn-group">
                        <a href="{{ route('reports.export', 'excel') }}" class="btn btn-success export-btn">
                            <i class="bi bi-file-earmark-excel me-2"></i>Excel
                        </a>
                        <a href="{{ route('reports.export', 'pdf') }}" class="btn btn-danger export-btn">
                            <i class="bi bi-file-earmark-pdf me-2"></i>PDF
                        </a>
                        <a href="{{ route('reports.export', 'csv') }}" class="btn btn-info export-btn">
                            <i class="bi bi-file-earmark-text me-2"></i>CSV
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @switch($reportData['type'])
            @case('attendance_summary')
                <!-- Attendance Summary Report -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card metric-card bg-primary text-white fade-in" style="animation-delay: 0.1s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['total_records'] }}</h3>
                                <p class="mb-0 fw-semibold">Total Records</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-success text-white fade-in" style="animation-delay: 0.2s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['unique_employees'] }}</h3>
                                <p class="mb-0 fw-semibold">Unique Employees</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-info text-white fade-in" style="animation-delay: 0.3s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['average_hours_per_day'] }}</h3>
                                <p class="mb-0 fw-semibold">Avg Hours/Day</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-warning text-white fade-in" style="animation-delay: 0.4s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['attendance_rate'] }}%</h3>
                                <p class="mb-0 fw-semibold">Attendance Rate</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider"></div>

                <div class="aa-card mb-4 fade-in">
                    <div class="card-header header-maroon">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-list-ul me-2"></i>Detailed Statistics
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table stats-table mb-0">
                                <tbody>
                                    @foreach($reportData['summary'] as $key => $value)
                                        <tr>
                                            <th style="width: 40%; color: var(--aa-maroon); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                            <td style="font-weight: 500; color: #333;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @break

            @case('employee_performance')
                <!-- Employee Performance Report -->
                <div class="aa-card mb-4 fade-in">
                    <div class="card-header header-maroon">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-person-check me-2"></i>Employee Performance Summary
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-enhanced mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-hash me-1"></i>Employee ID</th>
                                        <th><i class="bi bi-person me-1"></i>Employee Name</th>
                                        <th><i class="bi bi-building me-1"></i>Department</th>
                                        <th><i class="bi bi-clock me-1"></i>Total Hours</th>
                                        <th><i class="bi bi-clock-history me-1"></i>Overtime Hours</th>
                                        <th><i class="bi bi-calendar-check me-1"></i>Present Days</th>
                                        <th><i class="bi bi-graph-up me-1"></i>Avg Hours/Day</th>
                                        <th><i class="bi bi-percent me-1"></i>Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['data'] as $employee)
                                        <tr>
                                            <td><code class="text-primary fw-bold">#{{ $employee['employee_id'] }}</code></td>
                                            <td class="fw-semibold">{{ $employee['employee_name'] }}</td>
                                            <td><span class="badge bg-info badge-enhanced">{{ $employee['department'] }}</span></td>
                                            <td class="fw-bold text-success">{{ $employee['total_hours'] }}</td>
                                            <td class="fw-bold text-warning">{{ $employee['overtime_hours'] }}</td>
                                            <td class="fw-semibold">{{ $employee['present_days'] }}</td>
                                            <td class="fw-semibold">{{ $employee['average_hours_per_day'] }}</td>
                                            <td>
                                                <span class="badge badge-enhanced bg-{{ $employee['attendance_rate'] >= 90 ? 'success' : ($employee['attendance_rate'] >= 80 ? 'warning' : 'danger') }}">
                                                    {{ $employee['attendance_rate'] }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @break

            @case('department_comparison')
                <!-- Department Comparison Report -->
                <div class="aa-card mb-4 fade-in">
                    <div class="card-header header-maroon">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-building me-2"></i>Department Performance Comparison
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-enhanced mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-building me-1"></i>Department</th>
                                        <th><i class="bi bi-people me-1"></i>Employee Count</th>
                                        <th><i class="bi bi-clock me-1"></i>Total Hours</th>
                                        <th><i class="bi bi-clock-history me-1"></i>Overtime Hours</th>
                                        <th><i class="bi bi-graph-up me-1"></i>Avg Hours/Employee</th>
                                        <th><i class="bi bi-percent me-1"></i>Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['data'] as $dept)
                                        <tr>
                                            <td class="fw-bold" style="color: var(--aa-maroon);">{{ $dept['department_name'] }}</td>
                                            <td><span class="badge bg-secondary badge-enhanced">{{ $dept['employee_count'] }}</span></td>
                                            <td class="fw-bold text-success">{{ $dept['total_hours'] }}</td>
                                            <td class="fw-bold text-warning">{{ $dept['overtime_hours'] }}</td>
                                            <td class="fw-semibold">{{ $dept['average_hours_per_employee'] }}</td>
                                            <td>
                                                <span class="badge badge-enhanced bg-{{ $dept['attendance_rate'] >= 90 ? 'success' : ($dept['attendance_rate'] >= 80 ? 'warning' : 'danger') }}">
                                                    {{ $dept['attendance_rate'] }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @break

            @case('overtime_analysis')
                <!-- Overtime Analysis Report -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card metric-card bg-warning text-white fade-in" style="animation-delay: 0.1s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['total_overtime_hours'] }}</h3>
                                <p class="mb-0 fw-semibold">Total Overtime Hours</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-info text-white fade-in" style="animation-delay: 0.2s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['employees_with_overtime'] }}</h3>
                                <p class="mb-0 fw-semibold">Employees with Overtime</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-success text-white fade-in" style="animation-delay: 0.3s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['average_overtime_per_employee'] }}</h3>
                                <p class="mb-0 fw-semibold">Avg Overtime/Employee</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-primary text-white fade-in" style="animation-delay: 0.4s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['highest_overtime_employee']['employee_name'] ?? 'N/A' }}</h3>
                                <p class="mb-0 fw-semibold">Highest Overtime Employee</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider"></div>

                <div class="aa-card mb-4 fade-in">
                    <div class="card-header header-maroon">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-clock-history me-2"></i>Employee Overtime Details
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-enhanced mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-person me-1"></i>Employee Name</th>
                                        <th><i class="bi bi-clock-history me-1"></i>Total Overtime Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['employee_overtime'] as $employee)
                                        <tr>
                                            <td class="fw-semibold">{{ $employee['employee_name'] }}</td>
                                            <td class="fw-bold text-warning fs-5">{{ $employee['total_overtime'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @break

            @case('absenteeism_report')
                <!-- Absenteeism Report -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card metric-card bg-danger text-white fade-in" style="animation-delay: 0.1s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['total_absences'] }}</h3>
                                <p class="mb-0 fw-semibold">Total Absences</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-warning text-white fade-in" style="animation-delay: 0.2s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['employees_with_absences'] }}</h3>
                                <p class="mb-0 fw-semibold">Employees with Absences</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-info text-white fade-in" style="animation-delay: 0.3s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['absence_rate'] }}%</h3>
                                <p class="mb-0 fw-semibold">Absence Rate</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card bg-primary text-white fade-in" style="animation-delay: 0.4s;">
                            <div class="card-body text-center">
                                <h3 class="mb-0 fw-bold">{{ $reportData['summary']['most_absent_employee']['employee_name'] ?? 'N/A' }}</h3>
                                <p class="mb-0 fw-semibold">Most Absent Employee</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section Divider -->
                <div class="section-divider"></div>

                <div class="aa-card mb-4 fade-in">
                    <div class="card-header header-maroon">
                        <h5 class="mb-0 text-white">
                            <i class="bi bi-person-x me-2"></i>Employee Absence Details
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-enhanced mb-0">
                                <thead>
                                    <tr>
                                        <th><i class="bi bi-person me-1"></i>Employee Name</th>
                                        <th><i class="bi bi-building me-1"></i>Department</th>
                                        <th><i class="bi bi-x-circle me-1"></i>Absences</th>
                                        <th><i class="bi bi-percent me-1"></i>Attendance Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($reportData['employee_absences'] as $employee)
                                        <tr>
                                            <td class="fw-semibold">{{ $employee['employee_name'] }}</td>
                                            <td><span class="badge bg-info badge-enhanced">{{ $employee['department'] }}</span></td>
                                            <td class="fw-bold text-danger fs-5">{{ $employee['absences'] }}</td>
                                            <td>
                                                <span class="badge badge-enhanced bg-{{ $employee['attendance_rate'] >= 90 ? 'success' : ($employee['attendance_rate'] >= 80 ? 'warning' : 'danger') }}">
                                                    {{ $employee['attendance_rate'] }}%
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @break
        @endswitch

        <!-- Report Footer -->
        <div class="card shadow-sm no-print">
            <div class="card-body text-center">
                <p class="text-muted mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    This report was generated on {{ $reportData['generated_on']->format('M d, Y \a\t h:i A') }} by {{ $reportData['generated_by'] }}
                </p>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS - LOCAL -->
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}" defer></script>
</body>
</html>
