@if($reports->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Report ID</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Period</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reports as $report)
                    <tr>
                        <td>{{ $report->report_id }}</td>
                        <td>{{ $report->report_title }}</td>
                        <td>{{ $report->department->name }}</td>
                        <td>{{ date('M d, Y', strtotime($report->start_date)) }} - {{ date('M d, Y', strtotime($report->end_date)) }}</td>
                        <td>{{ $report->admin->name }}</td>
                        <td>
                            <span class="badge bg-{{ $report->status === 'completed' ? 'success' : 'warning' }}">
                                {{ ucfirst($report->status) }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('dtr.show', $report->report_id) }}" class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">No DTR reports found.</div>
@endif