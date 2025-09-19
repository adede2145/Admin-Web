@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">DTR Preview</h4>
            <div>
                <a href="{{ route('dtr.index') }}" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="text-center mb-4">
                <h3>DAILY TIME RECORD</h3>
                <h5>{{ $period['month'] }} {{ $period['year'] }}</h5>
                <div class="row mt-3">
                    <div class="col-md-6 text-md-end">
                        <strong>Name:</strong>
                    </div>
                    <div class="col-md-6 text-md-start">
                        {{ $employee->last_name }}, {{ $employee->first_name }}
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Day</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Total Hours</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attendance as $record)
                            <tr>
                                <td>{{ date('M d, Y', strtotime($record['date'])) }}</td>
                                <td>{{ $record['day'] }}</td>
                                <td>{{ $record['time_in'] ?? '-' }}</td>
                                <td>{{ $record['time_out'] ?? '-' }}</td>
                                <td>{{ number_format($record['total_hours'], 2) }}</td>
                                <td>{{ $record['remarks'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <h5>Summary</h5>
                    <table class="table table-sm">
                        <tr>
                            <td>Total Days:</td>
                            <td>{{ $summary['total_days'] }}</td>
                        </tr>
                        <tr>
                            <td>Present Days:</td>
                            <td>{{ $summary['present_days'] }}</td>
                        </tr>
                        <tr>
                            <td>Late Days:</td>
                            <td>{{ $summary['late_days'] }}</td>
                        </tr>
                        <tr>
                            <td>Absent Days:</td>
                            <td>{{ $summary['absent_days'] }}</td>
                        </tr>
                        <tr>
                            <td>Incomplete Days:</td>
                            <td>{{ $summary['incomplete_days'] }}</td>
                        </tr>
                        <tr>
                            <td>Total Hours:</td>
                            <td>{{ number_format($summary['total_hours'], 2) }}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="col-md-6">
                    <div class="mt-5">
                        <div class="text-center">
                            <div>Certified Correct:</div>
                            <div class="mt-4">
                                <hr style="width: 200px; margin: 0 auto;">
                                <div>Authorized Signatory</div>
                            </div>
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
        size: portrait;
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
</style>
@endpush
@endsection
