@extends('layouts.theme')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
        <div>
            <h1 class="fw-bold fs-2 fs-md-1 mb-0">
                <i class="bi bi-clock-history me-2"></i>Audit Logs
            </h1>
            <p class="text-muted mb-0 small">Track all changes made in the system</p>
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
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-2">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All Actions</option>
                        <option value="create" {{ request('action') == 'create' ? 'selected' : '' }}>Create</option>
                        <option value="edit" {{ request('action') == 'edit' ? 'selected' : '' }}>Edit</option>
                        <option value="delete" {{ request('action') == 'delete' ? 'selected' : '' }}>Delete</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
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
                <div class="col-12 col-md-8 col-lg-3">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <div class="d-flex flex-column flex-sm-row gap-2">
                        <button type="submit" class="btn btn-primary flex-fill">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                        <a href="{{ route('audit.index') }}" class="btn btn-outline-secondary flex-fill">
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
            <!-- Desktop Table View -->
            <div class="table-responsive d-none d-lg-block">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Admin</th>
                            <th>Action</th>
                            <th>Changes</th>
                            <th>View Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auditLogs as $log)
                            <tr class="{{ $log->isUnreadBy(auth()->user()->admin_id) ? 'table-warning' : '' }}">
                                <td>
                                    <div class="text-nowrap">{{ $log->created_at->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                    @if($log->isUnreadBy(auth()->user()->admin_id))
                                        <span class="badge bg-danger ms-1" style="font-size: 0.6em;">NEW</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $log->admin ? $log->admin->username : 'Unknown Admin' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $log->action == 'create' ? 'success' : ($log->action == 'edit' ? 'warning' : ($log->action == 'delete' ? 'danger' : ($log->action == 'verify' ? 'success' : ($log->action == 'reject' ? 'danger' : 'secondary')))) }}">
                                        {{ ucfirst($log->action) }}
                                    </span>
                                </td>
                                <td>
                                    @if($log->action == 'edit')
                                        @if($log->context_info)
                                            <div class="text-warning">
                                                <strong>{{ $log->context_info }}</strong>
                                                @if($log->summary)
                                                    <br><small class="text-muted">{{ $log->summary }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-warning">Record updated</span>
                                        @endif
                                    @elseif($log->action == 'create')
                                        @if($log->context_info)
                                            <div class="text-success">
                                                <strong>{{ $log->context_info }}</strong>
                                                @if($log->summary)
                                                    <br><small class="text-muted">{{ $log->summary }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-success">New record created</span>
                                        @endif
                                    @elseif($log->action == 'delete')
                                        @if($log->context_info)
                                            <div class="text-danger">
                                                <strong>{{ $log->context_info }}</strong>
                                                @if($log->summary)
                                                    <br><small class="text-muted">{{ $log->summary }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-danger">Record deleted</span>
                                        @endif
                                    @elseif($log->action == 'verify')
                                        @if($log->context_info)
                                            <div class="text-success">
                                                <strong>{{ $log->context_info }}</strong>
                                                @if($log->summary)
                                                    <br><small class="text-muted">{{ $log->summary }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-success">RFID attendance verified</span>
                                        @endif
                                    @elseif($log->action == 'reject')
                                        @if($log->context_info)
                                            <div class="text-danger">
                                                <strong>{{ $log->context_info }}</strong>
                                                @if($log->summary)
                                                    <br><small class="text-muted">{{ $log->summary }}</small>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-danger">RFID attendance rejected</span>
                                        @endif
                                    @else
                                        <span class="text-muted">Unknown action: {{ $log->action }}</span>
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
                                <td colspan="5" class="text-center py-4">
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

            <!-- Mobile/Tablet Card View -->
            <div class="d-lg-none">
                @forelse($auditLogs as $log)
                    <div class="card mb-3 {{ $log->isUnreadBy(auth()->user()->admin_id) ? 'border-warning' : '' }}" style="overflow: hidden;">
                        @if($log->isUnreadBy(auth()->user()->admin_id))
                            <div class="bg-warning text-dark px-3 py-1 small fw-bold">
                                <i class="bi bi-star-fill me-1"></i>NEW
                            </div>
                        @endif
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <small class="text-muted d-block">{{ $log->created_at->format('M d, Y') }}</small>
                                    <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                </div>
                                <span class="badge bg-{{ $log->action == 'create' ? 'success' : ($log->action == 'edit' ? 'warning' : ($log->action == 'delete' ? 'danger' : ($log->action == 'verify' ? 'success' : ($log->action == 'reject' ? 'danger' : 'secondary')))) }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </div>
                            
                            <div class="mb-2">
                                <small class="text-muted">Admin:</small>
                                <span class="badge bg-info ms-1">
                                    {{ $log->admin ? $log->admin->username : 'Unknown Admin' }}
                                </span>
                            </div>

                            <div class="mb-3">
                                @if($log->action == 'edit')
                                    @if($log->context_info)
                                        <div class="text-warning">
                                            <strong>{{ $log->context_info }}</strong>
                                            @if($log->summary)
                                                <br><small class="text-muted">{{ $log->summary }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-warning">Record updated</span>
                                    @endif
                                @elseif($log->action == 'create')
                                    @if($log->context_info)
                                        <div class="text-success">
                                            <strong>{{ $log->context_info }}</strong>
                                            @if($log->summary)
                                                <br><small class="text-muted">{{ $log->summary }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-success">New record created</span>
                                    @endif
                                @elseif($log->action == 'delete')
                                    @if($log->context_info)
                                        <div class="text-danger">
                                            <strong>{{ $log->context_info }}</strong>
                                            @if($log->summary)
                                                <br><small class="text-muted">{{ $log->summary }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-danger">Record deleted</span>
                                    @endif
                                @elseif($log->action == 'verify')
                                    @if($log->context_info)
                                        <div class="text-success">
                                            <strong>{{ $log->context_info }}</strong>
                                            @if($log->summary)
                                                <br><small class="text-muted">{{ $log->summary }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-success">RFID attendance verified</span>
                                    @endif
                                @elseif($log->action == 'reject')
                                    @if($log->context_info)
                                        <div class="text-danger">
                                            <strong>{{ $log->context_info }}</strong>
                                            @if($log->summary)
                                                <br><small class="text-muted">{{ $log->summary }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-danger">RFID attendance rejected</span>
                                    @endif
                                @else
                                    <span class="text-muted">Unknown action: {{ $log->action }}</span>
                                @endif
                            </div>

                            <a href="{{ route('audit.show', $log->id) }}" class="btn btn-sm btn-info w-100">
                                <i class="bi bi-eye me-2"></i>View Details
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-clock-history display-4 d-block mb-3"></i>
                            No audit logs found
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="mt-4">
                {{ $auditLogs->withQueryString()->links('vendor.pagination.bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection