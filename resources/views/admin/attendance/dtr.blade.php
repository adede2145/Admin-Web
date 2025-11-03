@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Daily Time Record</h4>
            <div>
                <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Office: {{ $report->department->department_name }}</h5>
                    <p>Report Type: {{ ucfirst($report->report_type) }}</p>
                    <p>Generated On: {{ $report->generated_on->format('F d, Y h:i A') }}</p>
                </div>
            </div>

            @foreach($employeeLogs as $employeeId => $logs)
                @php
                    $logs = collect($logs); // Ensure it's a Collection
                    $employee = $logs->first()->employee;
                @endphp
                
                <div class="employee-section mb-5">
                    <h5>Employee: {{ $employee->full_name }}</h5>
                    <p>Type: {{ ucfirst($employee->employment_type) }}</p>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>First Log</th>
                                    <th>Method</th>
                                    <th>Last Log</th>
                                    <th>Method</th>
                                    <th>Total Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $currentDate = null;
                                    $firstLog = null;
                                    $lastLog = null;
                                @endphp

                                @foreach($logs->sortBy('time_in') as $log)
                                    @php
                                        $logDate = $log->time_in->toDateString();
                                        
                                        if ($logDate !== $currentDate) {
                                            if ($currentDate !== null) {
                                                $totalHours = $firstLog && $lastLog 
                                                    ? $lastLog->time_in->diffInHours($firstLog->time_in) 
                                                    : 0;
                                                echo '<tr>';
                                                echo '<td>' . $currentDate . '</td>';
                                                echo '<td>' . ($firstLog ? $firstLog->time_in->format('h:i A') : '-') . '</td>';
                                                echo '<td>' . ($firstLog ? ucfirst($firstLog->method) : '-') . '</td>';
                                                echo '<td>' . ($lastLog ? $lastLog->time_in->format('h:i A') : '-') . '</td>';
                                                echo '<td>' . ($lastLog ? ucfirst($lastLog->method) : '-') . '</td>';
                                                echo '<td>' . number_format($totalHours, 1) . '</td>';
                                                echo '</tr>';
                                            }

                                            $currentDate = $logDate;
                                            $firstLog = $logs->filter(function ($l) use ($logDate) {
                                                return $l->time_in->toDateString() === $logDate;
                                            })->first();
                                            $lastLog = $logs->filter(function ($l) use ($logDate) {
                                                return $l->time_in->toDateString() === $logDate;
                                            })->last();
                                        } else {
                                            $lastLog = $log;
                                        }
                                    @endphp
                                @endforeach

                                @if($currentDate !== null)
                                    @php
                                        $totalHours = $firstLog && $lastLog 
                                            ? $lastLog->time_in->diffInHours($firstLog->time_in) 
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $currentDate }}</td>
                                        <td>{{ $firstLog ? $firstLog->time_in->format('h:i A') : '-' }}</td>
                                        <td>{{ $firstLog ? ucfirst($firstLog->method) : '-' }}</td>
                                        <td>{{ $lastLog ? $lastLog->time_in->format('h:i A') : '-' }}</td>
                                        <td>{{ $lastLog ? ucfirst($lastLog->method) : '-' }}</td>
                                        <td>{{ number_format($totalHours, 1) }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="mt-5">
                <div class="row">
                    <div class="col-md-6">
                        <p>Prepared by:</p>
                        <div class="mt-4">
                            <hr style="width: 200px;">
                            <p class="text-center" style="width: 200px;">{{ $report->admin->username }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <p>Certified Correct:</p>
                        <div class="mt-4">
                            <hr style="width: 200px;">
                            <p class="text-center" style="width: 200px;">Office Head</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style type="text/css" media="print">
    @page {
        size: landscape;
        margin: 1cm;
    }
    
    .btn {
        display: none;
    }
    
    .card {
        border: none;
    }
    
    .card-header {
        background: none;
        border: none;
    }

    .employee-section {
        page-break-inside: avoid;
    }
</style>
@endpush
@endsection
