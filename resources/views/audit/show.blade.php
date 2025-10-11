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

    <div class="row">
        <div class="col-md-6">
            <!-- Basic Information -->
            <div class="aa-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Basic Information</h5>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Date & Time</dt>
                        <dd class="col-sm-8">{{ $log->created_at->format('M d, Y h:i:s A') }}</dd>

                        <dt class="col-sm-4">Admin</dt>
                        <dd class="col-sm-8">{{ $log->admin ? $log->admin->username : 'Unknown Admin' }}</dd>

                        <dt class="col-sm-4">Action</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $log->action == 'create' ? 'success' : ($log->action == 'edit' ? 'warning' : 'danger') }}">
                                {{ ucfirst($log->action) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Module</dt>
                        <dd class="col-sm-8">{{ class_basename($log->model_type) }}</dd>

                        <dt class="col-sm-4">Record ID</dt>
                        <dd class="col-sm-8">{{ $log->model_id }}</dd>

                   
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Changes -->
            <div class="aa-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Changes Made</h5>
                </div>
                <div class="card-body">
                    @if($log->action == 'edit')
                        @if($log->changes && count($log->changes) > 0)
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
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
                            <table class="table">
                                <thead>
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
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Field</th>
                                        <th>Deleted Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($log->old_values as $field => $value)
                                        <tr>
                                            <td>{{ ucwords(str_replace('_', ' ', $field)) }}</td>
                                            <td>{{ is_array($value) ? json_encode($value) : $value }}</td>
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
</div>
@endsection