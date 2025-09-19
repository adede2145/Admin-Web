@extends('layouts.app')

@section('content')
<div class="container">
    <div class="card">
        <div class="card-header">
            <h4 class="mb-0">Generate DTR Report</h4>
        </div>
        <div class="card-body">
            <form action="{{ route('dtr.generate') }}" method="POST" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select name="employee_id" id="employee_id" class="form-select" required>
                        <option value="">Select Employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}">
                                {{ $employee->last_name }}, {{ $employee->first_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="month" class="form-label">Month</label>
                    <select name="month" id="month" class="form-select" required>
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}" {{ $month == date('n') ? 'selected' : '' }}>
                                {{ date('F', mktime(0, 0, 0, $month, 1)) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="year" class="form-label">Year</label>
                    <select name="year" id="year" class="form-select" required>
                        @foreach(range(date('Y')-5, date('Y')) as $year)
                            <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>
                                {{ $year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" name="type" value="preview" class="btn btn-primary">
                        <i class="bi bi-eye"></i> Preview
                    </button>
                    <button type="submit" name="type" value="pdf" class="btn btn-success">
                        <i class="bi bi-file-pdf"></i> Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
