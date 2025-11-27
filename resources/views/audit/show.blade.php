@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-clock-history me-2"></i>Audit Log Details
            </h1>
            <p class="text-muted mb-0">
                {{ class_basename($log->model_type) }} - {{ ucfirst($log->action) }}
            </p>
        </div>
        <div>
            <a href="{{ route('audit.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Audit Logs
            </a>
        </div>
    </div>

    <div>
        <!-- Basic Information -->
        <div class="aa-card mb-4 shadow-sm rounded-bottom border-0" style="overflow:hidden;">
            <div class="card-header bg-maroon text-white fw-bold d-flex align-items-center" style="background-color:#890a0a;">
                <i class="bi bi-info-circle me-2"></i>
                <h5 class="mb-0">Basic Information</h5>
            </div>
            <div class="card-body bg-light-subtle">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Date & Time</dt>
                    <dd class="col-sm-8">{{ $log->created_at->format('M d, Y h:i:s A') }}</dd>
                    <dt class="col-sm-4">Admin</dt>
                    <dd class="col-sm-8">{{ $log->admin ? $log->admin->username : 'Unknown Admin' }}</dd>
                    <dt class="col-sm-4">Action</dt>
                    <dd class="col-sm-8">
                        <span class="badge px-3 py-2 fs-6 fw-semibold bg-{{ $log->action == 'create' ? 'success' : ($log->action == 'edit' ? 'warning text-dark' : ($log->action == 'verify' ? 'success' : ($log->action == 'reject' ? 'danger' : ($log->action == 'delete' ? 'danger' : 'secondary')))) }}">
                            <i class="bi bi-{{ $log->action == 'create' ? 'plus-circle' : ($log->action == 'edit' ? 'pencil-square' : ($log->action == 'verify' ? 'check-circle' : ($log->action == 'reject' ? 'x-circle' : 'trash'))) }} me-1"></i>{{ ucfirst($log->action) }}
                        </span>
                    </dd>
                    <dt class="col-sm-4">Module</dt>
                    <dd class="col-sm-8">{{ class_basename($log->model_type) }}</dd>
                    <dt class="col-sm-4">Record ID</dt>
                    <dd class="col-sm-8">{{ $log->model_id }}</dd>
                    @if($log->context_info || $log->summary)
                    <dt class="col-sm-4">Context</dt>
                    <dd class="col-sm-8">
                        @if($log->context_info)
                            <strong>{{ $log->context_info }}</strong>
                        @endif
                        @if($log->summary)
                            <br><small class="text-muted">{{ $log->summary }}</small>
                        @endif
                    </dd>
                    @endif
                </dl>
            </div>
        </div>

        <!-- Changes -->
        <div class="aa-card mb-4 shadow-sm rounded-bottom border-0" style="overflow:hidden;">
            <div class="card-header bg-secondary text-white fw-bold d-flex align-items-center" style="background-color:#2c2c54;">
                <i class="bi bi-arrow-repeat me-2"></i>
                <h5 class="mb-0">Changes Made</h5>
            </div>
            <div class="card-body bg-light-subtle">
                @if($log->action == 'edit')
                    @if($log->changes && count($log->changes) > 0)
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover table-striped align-middle shadow-sm mb-0 rounded-3 overflow-hidden">
                                <thead class="bg-light fw-bold">
                                    <tr>
                                        <th>Field</th>
                                        <th>Old Value</th>
                                        <th>New Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($log->changes as $field => $change)
                                    <tr>
                                        <td>
                                            {{ $field == 'department_id' ? 'Department' : ucwords(str_replace('_', ' ', $field)) }}
                                        </td>
                                        <td class="text-danger">
                                            @if($field == 'department_id')
                                                @php $oldDept = \App\Models\Department::find($change['old']); @endphp
                                                {{ $oldDept ? $oldDept->department_name : $change['old'] }}
                                            @else
                                                {{ is_array($change['old']) ? json_encode($change['old']) : ($change['old'] ?? 'None') }}
                                            @endif
                                        </td>
                                        <td class="text-success">
                                            @if($field == 'department_id')
                                                @php $newDept = \App\Models\Department::find($change['new']); @endphp
                                                {{ $newDept ? $newDept->department_name : $change['new'] }}
                                            @else
                                                {{ is_array($change['new']) ? json_encode($change['new']) : ($change['new'] ?? 'None') }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-muted text-center py-3">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            No specific changes recorded or changes data is corrupted.
                            <br><small>This might occur if the audit data was not properly stored.</small>
                            <br><small class="text-info">Debug: Old Values: {{ is_array($log->old_values) ? json_encode($log->old_values) : ($log->old_values ?? 'NULL') }} | New Values: {{ is_array($log->new_values) ? json_encode($log->new_values) : ($log->new_values ?? 'NULL') }}</small>
                        </div>
                    @endif
                @elseif($log->action == 'create')
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover table-striped align-middle shadow-sm mb-0 rounded-3 overflow-hidden">
                            <thead class="bg-light fw-bold">
                                <tr>
                                    <th>Field</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($log->new_values as $field => $value)
                                <tr>
                                    <td>{{ ucwords(str_replace('_', ' ', $field)) }}</td>
                                    <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-borderless table-hover table-striped align-middle shadow-sm mb-0 rounded-3 overflow-hidden">
                            <thead class="bg-light fw-bold">
                                <tr>
                                    <th>Field</th>
                                    <th>Deleted Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($log->old_values as $field => $value)
                                <tr>
                                    <td>
                                        @if($field == 'employee_id' && $log->model_type == 'App\Models\AttendanceLog')
                                            Employee Name
                                        @elseif($field == 'department_id')
                                            Department
                                        @else
                                            {{ ucwords(str_replace('_', ' ', $field)) }}
                                        @endif
                                    </td>
                                    <td>
                                        @if($field == 'employee_id' && $log->model_type == 'App\Models\AttendanceLog')
                                            @php
                                                $employee = \App\Models\Employee::find($value);
                                            @endphp
                                            {{ $employee ? $employee->full_name : 'Unknown Employee' }}
                                        @elseif($field == 'department_id')
                                            @php
                                                $department = \App\Models\Department::find($value);
                                            @endphp
                                            {{ $department ? $department->department_name : 'Unknown Department' }}
                                        @else
                                            {{ is_array($value) ? json_encode($value) : $value }}
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection