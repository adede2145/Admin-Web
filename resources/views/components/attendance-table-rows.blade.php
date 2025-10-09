@forelse($attendanceLogs as $log)
<tr>
    <td><strong>#{{ $log->log_id }}</strong></td>
    <td>
        <div class="d-flex align-items-center">
            <div class="employee-avatar me-2">
                {{ strtoupper(substr($log->employee->full_name ?? 'N', 0, 1)) }}
            </div>
            <div>
                <div class="fw-semibold">{{ $log->employee->full_name ?? 'N/A' }}</div>
                <small class="text-muted">
                    ID: {{ $log->employee->employee_id ?? 'N/A' }}
                    @if(auth()->user()->role->role_name !== 'super_admin')
                    • {{ $log->employee->department->department_name ?? 'N/A' }}
                    @endif
                </small>
            </div>
        </div>
    </td>
    @if(auth()->user()->role->role_name === 'super_admin')
    <td>
        <span class="text-muted fw-medium">
            {{ $log->employee->department->department_name ?? 'N/A' }}
        </span>
    </td>
    @endif
    <td>
        <div class="text-success fw-bold time-info">
            {{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}
        </div>
        <div class="text-muted small time-date">
            {{ \Carbon\Carbon::parse($log->time_in)->format('M d, Y') }}
        </div>
    </td>
    <td>
        @if($log->time_out)
        <div class="text-danger fw-bold">
            {{ \Carbon\Carbon::parse($log->time_out)->format('h:i A') }}
        </div>
        <div class="text-muted small">
            {{ \Carbon\Carbon::parse($log->time_out)->format('M d, Y') }}
        </div>
        @else
        <span class="badge bg-warning">Not Set</span>
        @endif
    </td>
    <td>
        @php
        $methodConfig = [
        'rfid' => ['color' => 'primary', 'icon' => 'credit-card', 'tooltip' => 'Captured via RFID'],
        'fingerprint' => ['color' => 'success', 'icon' => 'fingerprint', 'tooltip' => 'Scanned via Fingerprint'],
        'manual' => ['color' => 'info', 'icon' => 'person-gear', 'tooltip' => 'Manual Entry']
        ];
        $config = $methodConfig[$log->method] ?? ['color' => 'secondary', 'icon' => 'question', 'tooltip' => 'Unknown Method'];
        @endphp

        <!-- Non-clickable method display with tooltip -->
        <span class="badge bg-{{ $config['color'] }}"
            title="{{ $config['tooltip'] }}"
            data-bs-toggle="tooltip"
            data-bs-placement="top">
            <i class="bi bi-{{ $config['icon'] }} me-1"></i>
            {{ ucfirst($log->method) }}
            @if($log->isRfidWithPhoto())
            <i class="bi bi-camera-fill ms-1" style="font-size: 0.8em; opacity: 0.8;"></i>
            @endif
        </span>
    </td>
    <td>
        @if($log->method === 'rfid' && $log->rfid_reason)
        <div class="text-muted small"
            title="{{ $log->rfid_reason }}"
            data-bs-toggle="tooltip"
            data-bs-placement="top">
            {{ Str::limit($log->rfid_reason, 30) }}
        </div>
        @else
        <span class="text-muted">-</span>
        @endif
    </td>
    <td>
        @if($log->isRfidWithPhoto())
        <!-- Photo thumbnail/button - ONLY trigger for modal -->
        <button class="btn btn-sm btn-outline-primary photo-view-btn"
            data-log-id="{{ $log->log_id }}"
            data-employee-name="{{ $log->employee->full_name ?? 'Employee' }}"
            data-time-in="{{ $log->time_in->format('M d, Y h:i A') }}"
            title="View captured photo and verify RFID attendance">
            <i class="bi bi-camera-fill me-1"></i>
            <span class="d-none d-md-inline">View</span>
        </button>
        @else
        <span class="text-muted">-</span>
        @endif
    </td>
    <td>
        @if($log->method === 'rfid')
        {!! $log->getVerificationStatusBadge() !!}
        @else
        <span class="text-muted">-</span>
        @endif
    </td>
    <td>
        <span class="badge kiosk-badge">
            Kiosk #{{ $log->kiosk_id ?? 'N/A' }}
        </span>
    </td>
    <td>
        <div class="btn-group btn-group-sm action-buttons">
            <!-- Edit and Delete buttons only - Approve/Reject moved to modal -->
            <button class="btn btn-outline-primary"
                data-bs-toggle="modal"
                data-bs-target="#editAttendance{{ $log->log_id }}"
                title="Edit attendance record">
                <i class="bi bi-pencil"></i>
            </button>
            <button type="button"
                class="btn btn-danger btn-sm delete-attendance-btn"
                data-log-id="{{ $log->log_id }}"
                title="Delete Attendance">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="{{ auth()->user()->role->role_name === 'super_admin' ? '10' : '9' }}" class="text-center text-muted py-4">
        <i class="bi bi-inbox display-4 d-block mb-2"></i>
        No attendance records found
    </td>
</tr>
@endforelse