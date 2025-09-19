@if($logs->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover" id="attendance-logs-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Time In</th>
                    <th>Time Out</th>
                    <th>Type</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                    <tr>
                        <td>{{ $log->employee->full_name }}</td>
                        <td>{{ $log->time_in ? date('Y-m-d h:i A', strtotime($log->time_in)) : '-' }}</td>
                        <td>{{ $log->time_out ? date('Y-m-d h:i A', strtotime($log->time_out)) : '-' }}</td>
                        <td>{{ ucfirst($log->log_type) }}</td>
                        <td>
                            <span class="badge {{ $log->is_active ? 'bg-success' : 'bg-secondary' }}">
                                {{ $log->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">No attendance logs found.</div>
@endif