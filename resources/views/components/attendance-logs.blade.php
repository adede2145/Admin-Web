@forelse($attendanceLogs as $log)
<tr>
    <td>{{ $log->log_id }}</td>
    <td>
        <a href="#" class="text-dark fw-semibold text-decoration-none employee-link" 
           data-employee-id="{{ $log->employee_id }}">
            {{ $log->employee->full_name }}
        </a>
    </td>
    <td>
        <span class="badge bg-info">
            {{ $log->employee->department->department_name }}
        </span>
    </td>
    <td>
        {{ $log->time_in ? \Carbon\Carbon::parse($log->time_in)->format('M d, Y h:i A') : '-' }}
        @if($log->isLate())
            <span class="badge bg-danger ms-1">Late</span>
        @endif
    </td>
    <td>
        {{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('h:i A') : '-' }}
    </td>
    <td>
        <span class="badge bg-{{ $log->method === 'fingerprint' ? 'success' : 'primary' }}">
            {{ ucfirst($log->method) }}
        </span>
    </td>
    <td>
        <span class="badge bg-secondary">
            {{ $log->kiosk->location }}
        </span>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center py-4">
        <div class="text-muted">
            <i class="bi bi-calendar-x display-4 d-block mb-3"></i>
            No attendance records found
        </div>
    </td>
</tr>
@endforelse