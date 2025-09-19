@forelse($attendanceLogs as $log)
    <tr>
        <td>#{{ $log->log_id }}</td>
        <td>
            <div class="d-flex align-items-center">
                <i class="bi bi-person me-2 text-muted"></i>
                {{ $log->employee->full_name ?? 'N/A' }}
            </div>
        </td>
        <td>
            <span class="text-muted">
                {{ $log->employee->department->department_name ?? 'N/A' }}
            </span>
        </td>
        <td>
            <div class="text-success fw-bold">
                {{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}
            </div>
            <div class="text-muted small">
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
                    'rfid' => ['color' => 'primary', 'icon' => 'credit-card'],
                    'fingerprint' => ['color' => 'success', 'icon' => 'fingerprint'],
                    'manual' => ['color' => 'info', 'icon' => 'person-gear']
                ];
                $config = $methodConfig[$log->method] ?? ['color' => 'secondary', 'icon' => 'question'];
            @endphp
            
            @if($log->isRfidWithPhoto())
                <!-- Clickable RFID badge with photo -->
                <span class="badge bg-{{ $config['color'] }} clickable-badge" 
                      style="cursor: pointer;" 
                      onclick="showAttendancePhoto({{ $log->log_id }}, '{{ $log->employee->full_name ?? 'Employee' }}', '{{ $log->time_in->format('M d, Y h:i A') }}')"
                      title="Click to view captured photo">
                    <i class="bi bi-{{ $config['icon'] }} me-1"></i>
                    {{ ucfirst($log->method) }}
                    <i class="bi bi-camera-fill ms-1" style="font-size: 0.8em;"></i>
                </span>
            @else
                <!-- Regular badge without photo -->
                <span class="badge bg-{{ $config['color'] }}">
                    <i class="bi bi-{{ $config['icon'] }} me-1"></i>
                    {{ ucfirst($log->method) }}
                </span>
            @endif
        </td>
        <td>
            <span class="badge bg-secondary">
                Kiosk #{{ $log->kiosk_id ?? 'N/A' }}
            </span>
        </td>
        <td>
            <div class="btn-group btn-group-sm">
                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAttendance{{ $log->log_id }}">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-outline-danger" onclick="deleteAttendance({{ $log->log_id }})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="8" class="text-center text-muted py-4">
            <i class="bi bi-inbox display-4 d-block mb-2"></i>
            No attendance records found
        </td>
    </tr>
@endforelse
