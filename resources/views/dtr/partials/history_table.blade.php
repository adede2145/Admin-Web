<style>
    .scroll-hide::-webkit-scrollbar { display: none; }
    .modal-body-scrollable {
        max-height: 70vh;
        overflow-y: auto;
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    .modal-header-maroon {
        background: var(--aa-maroon) !important;
        color: #fff !important;
        border-bottom: 1px solid var(--aa-maroon-dark);
        margin: -1rem -1rem 1rem -1rem;
        padding: 1.5rem;
        border-radius: 0.5rem 0.5rem 0 0;
        min-height: 60px;
    }
    .btn-close-white {
        filter: invert(1) grayscale(100%) brightness(200%);
    }
    .table-header-transparent {
        background: transparent !important;
    }
    .table-header-red {
        color: var(--aa-maroon) !important;
        font-weight: 700;
    }
    .btn-filter-yellow {
        background-color: var(--aa-yellow) !important;
        border-color: var(--aa-yellow) !important;
        color: #3d0a0a !important;
        font-weight: 600;
    }
</style>
<div class="modal-header-maroon d-flex justify-content-between align-items-center">
    <h5 class="modal-title mb-0">
        <i class="bi bi-list-ul me-2"></i>DTR Report History
    </h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body-scrollable px-2 pt-2 pb-4">
    <!-- Filters -->
    <form id="dtrHistoryFilterForm" class="row g-3 mb-3">
        <div class="col-md-2">
            <label class="form-label">Report Type</label>
            <select name="report_type" class="form-select">
                <option value="">All Types</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
                <option value="custom">Custom</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="generated">Generated</option>
                <option value="archived">Archived</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control">
        </div>
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <div class="d-grid">
                <button type="submit" class="btn btn-filter-yellow">
                    <i class="bi bi-search me-2"></i>Filter
                </button>
            </div>
        </div>
        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <div class="d-grid">
                <button type="button" class="btn btn-outline-secondary" id="dtrHistoryResetBtn">
                    <i class="bi bi-arrow-clockwise me-2"></i>Reset
                </button>
            </div>
        </div>
    </form>
    <div class="table-responsive scroll-hide">
        <table class="table table-hover">
            <thead class="table-header-transparent">
                <tr>
                    <th scope="col" class="table-header-red">Report ID</th>
                    <th scope="col" class="table-header-red">Title</th>
                    <th scope="col" class="table-header-red">Office</th>
                    <th scope="col" class="table-header-red">Type</th>
                    <th scope="col" class="table-header-red">Period</th>
                    <th scope="col" class="table-header-red">Employees</th>
                    <th scope="col" class="table-header-red">Total Hours</th>
                    <th scope="col" class="table-header-red">Generated</th>
                    <th scope="col" class="table-header-red">Status</th>
                    <th scope="col" class="table-header-red">Actions</th>
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
    <!-- Results Counter and Pagination -->
    <div class="d-flex justify-content-between align-items-center mt-3">
        <div class="text-muted small">
            @if($reports->count() > 0)
                Showing {{ $reports->firstItem() }} to {{ $reports->lastItem() }} of {{ $reports->total() }} results
            @else
                No results found
            @endif
        </div>
        @if($reports->hasPages())
            <div id="dtrPaginationContainer">
                {{ $reports->onEachSide(1)->appends(request()->query())->links('pagination::bootstrap-5') }}
            </div>
            <script>
                // Convert pagination links to use JavaScript instead of href - run immediately
                (function convertPaginationLinks() {
                    const paginationContainer = document.getElementById('dtrPaginationContainer');
                    if (paginationContainer) {
                        const paginationLinks = paginationContainer.querySelectorAll('.pagination a');
                        
                        // Define the onclick handler function
                        function handlePaginationClick(originalHref) {
                            return function(e) {
                                e.preventDefault();
                                e.stopPropagation();
                                e.stopImmediatePropagation();
                                
                                console.log('Direct onclick handler triggered for:', originalHref);
                                
                                // Show loading state
                                const modalBody = document.getElementById('dtrHistoryModalBody');
                                const originalContent = modalBody.innerHTML;
                                modalBody.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-maroon" role="status"></div><div class="mt-2">Loading page...</div></div>';
                                
                                fetch(originalHref)
                                    .then(response => {
                                        if (!response.ok) {
                                            throw new Error('Network response was not ok');
                                        }
                                        return response.text();
                                    })
                                    .then(html => {
                                        modalBody.innerHTML = html;
                                        // Re-initialize any event handlers that might be needed
                                        if (typeof initializeDTRDeleteButtons === 'function') {
                                            initializeDTRDeleteButtons();
                                        }
                                        // Convert pagination links for AJAX handling
                                        convertPaginationLinks(); // Recursive call to convert new links
                                        console.log('DTR Pagination loaded successfully via direct onclick');
                                    })
                                    .catch(error => {
                                        console.error('Error loading pagination:', error);
                                        modalBody.innerHTML = originalContent;
                                        alert('Error loading page. Please try again.');
                                    });
                                
                                return false;
                            };
                        }
                        
                        paginationLinks.forEach(function(link) {
                            if (link.href && !link.dataset.url) {
                                const originalHref = link.href;
                                link.href = 'javascript:void(0)';
                                link.dataset.url = originalHref;
                                link.style.cursor = 'pointer';
                                // Add onclick handler as backup
                                link.onclick = handlePaginationClick(originalHref);
                            }
                        });
                    }
                })();
            </script>
        @endif
    </div>
</div>
<script>
// AJAX filter for DTR history modal
const dtrHistoryFilterForm = document.getElementById('dtrHistoryFilterForm');
if (dtrHistoryFilterForm) {
    dtrHistoryFilterForm.onsubmit = function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData).toString();
        fetch(`{{ route('dtr.history.modal') }}?${params}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('dtrHistoryModalBody').innerHTML = html;
                // Re-initialize delete button handlers after content is loaded
                if (typeof initializeDTRDeleteButtons === 'function') {
                    initializeDTRDeleteButtons();
                }
                // Convert pagination links for AJAX handling
                const paginationContainer = document.getElementById('dtrPaginationContainer');
                if (paginationContainer) {
                    const paginationLinks = paginationContainer.querySelectorAll('.pagination a');
                    paginationLinks.forEach(function(link) {
                        if (link.href && !link.dataset.url) {
                            const originalHref = link.href;
                            link.href = 'javascript:void(0)';
                            link.dataset.url = originalHref;
                            link.style.cursor = 'pointer';
                        }
                    });
                }
            });
    };
    document.getElementById('dtrHistoryResetBtn').onclick = function() {
        fetch(`{{ route('dtr.history.modal') }}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('dtrHistoryModalBody').innerHTML = html;
                // Re-initialize delete button handlers after content is loaded
                if (typeof initializeDTRDeleteButtons === 'function') {
                    initializeDTRDeleteButtons();
                }
                // Convert pagination links for AJAX handling
                const paginationContainer = document.getElementById('dtrPaginationContainer');
                if (paginationContainer) {
                    const paginationLinks = paginationContainer.querySelectorAll('.pagination a');
                    paginationLinks.forEach(function(link) {
                        if (link.href && !link.dataset.url) {
                            const originalHref = link.href;
                            link.href = 'javascript:void(0)';
                            link.dataset.url = originalHref;
                            link.style.cursor = 'pointer';
                        }
                    });
                }
            });
    };
}

// Set filter values from query (for AJAX reloads)
(function syncFilterValues() {
    const urlParams = new URLSearchParams(window.location.search);
    ['report_type','status','start_date','end_date'].forEach(function(name) {
        if (urlParams.has(name)) {
            const el = dtrHistoryFilterForm.querySelector(`[name="${name}"]`);
            if (el) el.value = urlParams.get(name);
        }
    });
})();

// Handle pagination clicks in modal - use more specific targeting with immediate execution
(function() {
    console.log('Setting up DTR pagination event listener');
    
    document.addEventListener('click', function(e) {
        // Check if click is within the DTR pagination container
        const paginationLink = e.target.closest('#dtrPaginationContainer .pagination a');
        if (paginationLink) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            const link = paginationLink;
            const url = link.dataset.url || link.href;
            console.log('DTR Pagination clicked:', url); // Debug log
            console.log('Modal pagination working - staying within modal instead of redirecting');
        
        // Show loading state
        const modalBody = document.getElementById('dtrHistoryModalBody');
        const originalContent = modalBody.innerHTML;
        modalBody.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-maroon" role="status"></div><div class="mt-2">Loading page...</div></div>';
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                modalBody.innerHTML = html;
                // Re-initialize any event handlers that might be needed
                if (typeof initializeDTRDeleteButtons === 'function') {
                    initializeDTRDeleteButtons();
                }
                // Convert pagination links for AJAX handling
                const paginationContainer = document.getElementById('dtrPaginationContainer');
                if (paginationContainer) {
                    const paginationLinks = paginationContainer.querySelectorAll('.pagination a');
                    paginationLinks.forEach(function(link) {
                        if (link.href && !link.dataset.url) {
                            const originalHref = link.href;
                            link.href = 'javascript:void(0)';
                            link.dataset.url = originalHref;
                            link.style.cursor = 'pointer';
                        }
                    });
                }
                console.log('DTR Pagination loaded successfully');
            })
            .catch(error => {
                console.error('Error loading pagination:', error);
                modalBody.innerHTML = originalContent;
                alert('Error loading page. Please try again.');
            });
        }
    });
})();
</script>
