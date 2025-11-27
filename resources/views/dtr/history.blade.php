@extends('layouts.theme')
@section('content')
<style>
    /* Responsive DTR History Styles */
    .dtr-history-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .dtr-history-title {
        flex: 1;
        min-width: 250px;
    }
    
    .dtr-history-actions {
        flex-shrink: 0;
    }
    
    /* Mobile responsive adjustments */
    @media (max-width: 767.98px) {
        .dtr-history-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .dtr-history-title h1 {
            font-size: 1.5rem;
        }
        
        .dtr-history-title p {
            font-size: 0.9rem;
        }
        
        .dtr-history-actions .btn {
            width: 100%;
        }
        
        /* Hide less important columns on mobile */
        .hide-mobile {
            display: none !important;
        }
        
        /* Stack filter inputs */
        .filter-row .col-md-2 {
            margin-bottom: 0.75rem;
        }
        
        /* Adjust table font sizes */
        .table {
            font-size: 0.8rem;
        }
        
        .table th,
        .table td {
            padding: 0.5rem 0.25rem;
        }
        
        /* Modal success cards */
        .modal-success-card {
            margin-bottom: 1rem;
        }
        
        .btn-group {
            width: 100%;
        }
        
        .btn-group .btn {
            font-size: 0.75rem;
        }
    }
    
    @media (min-width: 768px) and (max-width: 991.98px) {
        /* Hide some columns on tablets */
        .hide-tablet {
            display: none !important;
        }
        
        .dtr-history-title h1 {
            font-size: 1.75rem;
        }
    }
</style>

<div class="container-fluid">
    <!-- Success/Error Messages REMOVED (handled by toasts)-->
    {{-- Alerts removed --}}
    <!-- Header -->
    <div class="dtr-history-header">
        <div class="dtr-history-title">
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
        <div class="dtr-history-actions">
            <a href="{{ route('attendance.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>
                <span class="d-none d-sm-inline">Back to Attendance</span>
                <span class="d-sm-none">Back</span>
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="aa-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 filter-row">
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label fw-semibold">Report Type</label>
                    <select name="report_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="weekly" {{ request('report_type') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                        <option value="monthly" {{ request('report_type') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="custom" {{ request('report_type') === 'custom' ? 'selected' : '' }}>Custom</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="generated" {{ request('status') === 'generated' ? 'selected' : '' }}>Generated</option>
                        <option value="archived" {{ request('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label fw-semibold">Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label fw-semibold">End Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-1"></i>
                            <span class="d-none d-sm-inline">Filter</span>
                            <span class="d-sm-none">Search</span>
                        </button>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <div class="d-grid">
                        <a href="{{ route('dtr.history') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            <span class="d-none d-sm-inline">Reset</span>
                            <span class="d-sm-none">Clear</span>
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
                            <th scope="col"><span class="d-none d-sm-inline">Report </span>ID</th>
                            <th scope="col">Title</th>
                            <th scope="col" class="hide-mobile hide-tablet">Office</th>
                            <th scope="col" class="hide-mobile">Type</th>
                            <th scope="col" class="hide-mobile hide-tablet">Period</th>
                            <th scope="col" class="hide-mobile hide-tablet">Employees</th>
                            <th scope="col" class="hide-mobile hide-tablet">Hours</th>
                            <th scope="col" class="hide-mobile">Generated</th>
                            <th scope="col" class="hide-mobile">Status</th>
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
                                <small class="text-muted d-none d-md-inline">by {{ $report->admin_name }}</small>
                                <div class="d-md-none mt-1">
                                    <span class="badge bg-{{ $report->report_type === 'weekly' ? 'success' : ($report->report_type === 'monthly' ? 'warning' : 'info') }} me-1">
                                        {{ ucfirst($report->report_type) }}
                                    </span>
                                    <span class="badge bg-{{ $report->status === 'generated' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </div>
                            </td>
                            <td class="hide-mobile hide-tablet">
                                <span class="badge bg-info">
                                    {{ $report->department_name }}
                                </span>
                            </td>
                            <td class="hide-mobile">
                                <span class="badge bg-{{ $report->report_type === 'weekly' ? 'success' : ($report->report_type === 'monthly' ? 'warning' : 'info') }}">
                                    {{ ucfirst($report->report_type) }}
                                </span>
                            </td>
                            <td class="hide-mobile hide-tablet">
                                <div class="text-muted small">
                                    {{ $report->formatted_period }}
                                </div>
                                <small class="text-muted">{{ $report->total_days }} days</small>
                            </td>
                            <td class="hide-mobile hide-tablet">
                                <span class="badge bg-secondary">
                                    {{ $report->total_employees }}
                                </span>
                            </td>
                            <td class="hide-mobile hide-tablet">
                                <div class="fw-bold text-success small">
                                    {{ number_format($report->total_hours, 2) }}
                                </div>
                            </td>
                            <td class="hide-mobile">
                                <small class="text-muted">
                                    {{ $report->formatted_generated_on }}
                                </small>
                            </td>
                            <td class="hide-mobile">
                                <span class="badge bg-{{ $report->status === 'generated' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($report->status) }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-nowrap">
                                    <a href="{{ route('dtr.details', $report->report_id) }}" class="btn btn-outline-primary btn-sm" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if($report->status === 'generated')
                                    <button type="button" class="btn btn-outline-danger btn-sm delete-dtr-report-btn" data-report-id="{{ $report->report_id }}" data-report-type="{{ $report->report_type }}" data-generated-on="{{ $report->formatted_generated_on }}" title="Delete Report">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
                    <div class="col-12 col-md-6 mb-3 mb-md-0">
                        <div class="card modal-success-card">
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
                    <div class="col-12 col-md-6">
                        <div class="card modal-success-card">
                            <div class="card-body text-center">
                                <i class="bi bi-download display-4 text-success mb-3"></i>
                                <h5>Download Report</h5>
                                <p class="text-muted">Download the report in your preferred format</p>
                                <div class="btn-group d-flex flex-wrap gap-2">
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'html']) }}" class="btn btn-success flex-fill">
                                        <i class="bi bi-filetype-html me-1 d-none d-sm-inline"></i>HTML
                                    </a>
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'pdf']) }}" class="btn btn-danger flex-fill">
                                        <i class="bi bi-filetype-pdf me-1 d-none d-sm-inline"></i>PDF
                                    </a>
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'excel']) }}" class="btn btn-warning flex-fill">
                                        <i class="bi bi-file-earmark-excel me-1 d-none d-sm-inline"></i>Excel
                                    </a>
                                    <a href="{{ route('dtr.download', [session('generated_report_id'), 'csv']) }}" class="btn btn-secondary flex-fill">
                                        <i class="bi bi-filetype-csv me-1 d-none d-sm-inline"></i>CSV
                                    </a>
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

<!-- Custom Delete DTR Report Modal -->
<div class="modal fade" id="deleteDTRReportModal" tabindex="-1" aria-labelledby="deleteDTRReportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none;">
                <h5 class="modal-title" id="deleteDTRReportModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete DTR Report
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center py-4">
                <div class="mb-4">
                    <div class="d-flex justify-content-center mb-3">
                        <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                            <i class="bi bi-trash text-danger" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                    <h5 class="text-danger mb-3">Confirm Deletion</h5>
                    <p class="text-muted mb-0" id="deleteDTRReportMessage">
                        Are you sure you want to delete this DTR report? 
                        <br><strong class="text-danger">This action cannot be undone.</strong>
                    </p>
                </div>
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-info-circle me-2"></i>
                    <small id="deleteDTRReportWarning">This will permanently remove the DTR report from the system.</small>
                </div>
            </div>
            <div class="modal-footer border-top-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteDTRReportBtn" onclick="confirmDeleteDTRReport()">
                    <i class="bi bi-trash me-1"></i>Delete Report
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DTR delete buttons
        initializeDTRDeleteButtons();
    });
    
    // DTR Delete Functions
    function initializeDTRDeleteButtons() {
        // Remove any existing event listeners to prevent duplicates
        document.querySelectorAll('.delete-dtr-report-btn').forEach(btn => {
            btn.removeEventListener('click', handleDTRDeleteClick);
        });
        
        // Add event listeners to DTR delete buttons
        document.querySelectorAll('.delete-dtr-report-btn').forEach(btn => {
            btn.addEventListener('click', handleDTRDeleteClick);
        });
        
        console.log('Initialized DTR delete buttons:', document.querySelectorAll('.delete-dtr-report-btn').length);
    }
    
    function handleDTRDeleteClick(e) {
        e.preventDefault();
        const reportId = e.target.closest('.delete-dtr-report-btn').dataset.reportId;
        const reportType = e.target.closest('.delete-dtr-report-btn').dataset.reportType;
        const generatedOn = e.target.closest('.delete-dtr-report-btn').dataset.generatedOn;
        
        console.log('DTR delete button clicked:', { reportId, reportType, generatedOn });
        deleteDTRReport(reportId, reportType, generatedOn);
    }
    
    function deleteDTRReport(reportId, reportType, generatedOn) {
        console.log('deleteDTRReport called:', { reportId, reportType, generatedOn });
        
        // Store the reportId for later use
        document.getElementById('confirmDeleteDTRReportBtn').dataset.reportId = reportId;
        
        // Update modal content with report-specific information
        document.getElementById('deleteDTRReportMessage').innerHTML = 
            `Are you sure you want to delete this <strong>${reportType}</strong> DTR report?<br><strong class="text-danger">This action cannot be undone.</strong>`;
        
        document.getElementById('deleteDTRReportWarning').textContent = 
            `This will permanently remove the DTR report from the system. Generated on: ${generatedOn}`;
        
        // Show the custom delete modal
        const modal = new bootstrap.Modal(document.getElementById('deleteDTRReportModal'));
        modal.show();
    }
    
    function confirmDeleteDTRReport() {
        const reportId = document.getElementById('confirmDeleteDTRReportBtn').dataset.reportId;
        console.log('confirmDeleteDTRReport called with reportId:', reportId);
        
        if (!reportId) {
            console.error('No reportId found in confirmDeleteDTRReportBtn dataset');
            alert('Error: No DTR report ID found. Please try again.');
            return;
        }
        
        try {
            // Create a form and submit it
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("dtr.delete", ":reportId") }}'.replace(':reportId', reportId);
            
            console.log('Form action URL:', form.action);

            const csrfToken = document.createElement('input');
            csrfToken.type = 'hidden';
            csrfToken.name = '_token';
            csrfToken.value = '{{ csrf_token() }}';

            const methodField = document.createElement('input');
            methodField.type = 'hidden';
            methodField.name = '_method';
            methodField.value = 'DELETE';

            form.appendChild(csrfToken);
            form.appendChild(methodField);
            document.body.appendChild(form);
            
            console.log('Submitting delete form for reportId:', reportId);
            form.submit();
        } catch (error) {
            console.error('Error deleting DTR report:', error);
            alert('An error occurred while trying to delete the DTR report. Please try again.');
        }
    }

    function downloadPDF(reportId) {
        // Redirect to download route
        window.location.href = '{{ url("/dtr") }}/' + reportId + '/download';
    }
</script>
@endpush
@include('layouts.toast-js')