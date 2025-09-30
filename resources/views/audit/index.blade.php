@extends('layouts.theme')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-clock-history me-2"></i>Audit Logs
            </h1>
            <p class="text-muted mb-0">Track all changes made in the system</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="aa-card mb-4">
        <div class="card-header header-maroon">
            <h5 class="card-title mb-0">
                <i class="bi bi-funnel me-2"></i>Filter Options
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                        <option value="edit" {{ request('action') == 'edit' ? 'selected' : '' }}>Edit</option>
                        <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Admin</label>
                    <select name="admin_id" class="form-select">
                        <option value="">All Admins</option>
                        @foreach($admins as $admin)
                            <option value="{{ $admin->admin_id }}" {{ request('admin_id') == $admin->admin_id ? 'selected' : '' }}>
                                {{ $admin->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Module</label>
                    <select name="model_type" class="form-select">
                        <option value="">All Modules</option>
                        <option value="Employee" {{ request('model_type') == 'Employee' ? 'selected' : '' }}>Employee</option>
                        <option value="AttendanceLog" {{ request('model_type') == 'AttendanceLog' ? 'selected' : '' }}>Attendance</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                        <a href="{{ route('audit.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-2"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Logs Table -->
    <div class="aa-card">
        <div class="card-header header-maroon">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul me-2"></i>Audit Logs
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Changes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr class="{{ $log->isUnreadBy(auth()->user()->admin_id) ? 'table-warning' : '' }}">
                                <td>
                                    {{ $log->created_at->format('M d, Y h:i A') }}
                                    @if($log->isUnreadBy(auth()->user()->admin_id))
                                        <span class="badge bg-danger ms-1" style="font-size: 0.6em;">NEW</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $log->admin->username }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->action == 'create' ? 'success' : ($log->action == 'edit' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td>
                                    {{ $log->model_type }}
                                </td>
                                <td>
                                    @if($log->action == 'edit')
                                        @foreach($log->changes as $field => $change)
                                            <div class="small">
                                                <strong>{{ ucwords(str_replace('_', ' ', $field)) }}:</strong>
                                                <span class="text-danger">{{ $change['old'] }}</span>
                                                <i class="bi bi-arrow-right mx-1"></i>
                                                <span class="text-success">{{ $change['new'] }}</span>
                                            </div>
                                        @endforeach
                                    @elseif($log->action == 'create')
                                        <span class="text-success">New record created</span>
                                    @else
                                        <span class="text-danger">Record deleted</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('audit.show', $log->id) }}" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-clock-history display-4 d-block mb-3"></i>
                                        No audit logs found
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $auditLogs->withQueryString()->links() }}
            </div>
        </div>
    </div>
</div>
@endsection