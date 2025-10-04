@extends('layouts.theme')
@section('content')
<div class="container-fluid">
    <!-- Success/Error Messages -->
    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-file-earmark-text me-2 fs-4"></i>DTR Report History
            </h1>
            @if(auth()->user()->role->role_name !== 'super_admin')
            <p class="text-muted mb-0 fs-5">
                <i class="bi bi-building me-1"></i>
                Showing reports for: <strong>{{ auth()->user()->department->department_name ?? 'N/A' }}</strong>
            </p>
            @endif
        </div>
        <div>
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back to Attendance
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="aa-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="weekly" {{ request('report_type') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ request('report_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="custom" {{ request('report_type') === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>Generated</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-2"></i>Filter
                        </button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <a href="{{ route('dtr.history') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- DTR Reports List -->
    <div class="aa-card">
        <div class="card-header header-maroon">
            <h5 class="card-title mb-0">
                <i class="bi bi-list-ul me-2"></i>Generated Reports
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Report ID</th>
                            <th scope="col">Title</th>
                            <th scope="col">Department</th>
                            <th scope="col">Type</th>
                            <th scope="col">Period</th>
                            <th scope="col">Employees</th>
                            <th scope="col">Total Hours</th>
                            <th scope="col">Generated</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                        <tr>
                            <td>
                                <code class="text-primary">#{{ $report->report_id }}</code>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $report->report_title }}</div>
                                <small class="text-muted">by {{ $report->admin_name }}</small>
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    {{ $report->department_name }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $report->report_type === 'weekly' ? 'success' : ($report->report_type === 'monthly' ? 'warning' : 'info') }}">
                                    {{ ucfirst($report->report_type) }}
                                </span>
                            </td>
                            <td>
                                <div class="text-muted">
                                    {{ $report->formatted_period }}
                                </div>
                                <small class="text-muted">{{ $report->total_days }} days</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $report->total_employees }} employees
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-success">
                                    {{ number_format($report->total_hours, 2) }} hrs
                                </div>
                            </td>
                            <td>
                                <div class="text-muted">
                                    {{ $report->formatted_generated_on }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $report->status === 'generated' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($report->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('dtr.details', $report->report_id) }}" class="btn btn-outline-primary btn-sm" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($report->status === 'generated')
                                    <form method="POST" action="{{ route('dtr.delete', $report->report_id) }}" style="display: inline;" class="delete-report-form" data-report-id="{{ $report->report_id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete Report">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                No DTR reports found
                                <br>
                                <small>Generate your first DTR report from the Attendance Management page</small>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($reports->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $reports->links() }}
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Success Modal for Generated Report -->
@if(session('generated_report_id'))
<div class="modal fade" id="generatedReportModal" tabindex="-1" aria-labelledby="generatedReportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="generatedReportModalLabel">
                    <i class="bi bi-check-circle me-2"></i>DTR Report Generated Successfully!
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-success">
                    <i class="bi bi-info-circle me-2"></i>
                    Your DTR report has been generated successfully! Report ID: <strong>#{{ session('generated_report_id') }}</strong>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="bi bi-eye display-4 text-primary mb-3"></i>
                                <h5>View Report Details</h5>
                                <p class="text-muted">Review the complete report with all employee attendance details</p>
                                <a href="{{ route('dtr.details', session('generated_report_id')) }}" class="btn btn-primary">
                                    <i class="bi bi-eye me-2"></i>View Details
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="bi bi-download display-4 text-success mb-3"></i>
                                <h5>Download Report</h5>
                                <p class="text-muted">Download the report in your preferred format</p>
                                <div class="btn-group">
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'html']) }}" class="btn btn-success"><i class="bi bi-filetype-html me-2"></i>HTML</a>
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'pdf']) }}" class="btn btn-danger"><i class="bi bi-filetype-pdf me-2"></i>PDF</a>
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'excel']) }}" class="btn btn-warning"><i class="bi bi-file-earmark-excel me-2"></i>Excel</a>
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'csv']) }}" class="btn btn-secondary"><i class="bi bi-filetype-csv me-2"></i>CSV</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('generated_report_id'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('generatedReportModal'));
        modal.show();
    });
</script>
@endif

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Delete report confirmation
        const deleteForms = document.querySelectorAll('.delete-report-form');
        deleteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const reportId = this.dataset.reportId;
                if (confirm('Are you sure you want to delete this DTR report? This action cannot be undone.')) {
                    this.submit();
                }
            });
        });
    });

    function downloadPDF(reportId) {
        // Redirect to download route
        window.location.href = '{{ url("/dtr") }}/' + reportId + '/download';
    }
</script>
@endpush