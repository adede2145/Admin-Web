@extends('layouts.theme')
@section('title', 'Departments')
@section('content')
    {{-- Security check: Only super admins can access this page --}}
    @if(!auth()->check() || !auth()->user()->role || auth()->user()->role->role_name !== 'super_admin')
        <div class="container-fluid">
            <div class="alert alert-danger">
                <i class="bi bi-shield-exclamation me-2"></i>
                <strong>Access Denied!</strong> Only Super Admins can manage departments.
            </div>
        </div>
        @php abort(403, 'Access denied. Only Super Admins can manage departments.'); @endphp
    @endif

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-building me-2 fs-4"></i>Manage Departments
            </h1>
            <span class="badge bg-primary fs-5">Super Admin</span>
        </div>

        <!-- Success/Error Messages REMOVED (use toast notification)-->
        {{-- Alerts removed --}}

        <div class="row align-items-stretch">
            <!-- Create Department Form -->
            <div class="col-md-6 mb-4">
                <div class="aa-card h-100 shadow-sm">
                    <div class="card-header header-maroon">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-plus-circle me-2"></i>Create New Department
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('departments.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="department_name" class="form-label">
                                    <i class="bi bi-building me-2"></i>Department Name
                                </label>
                                <input type="text" name="department_name" id="department_name" 
                                       class="form-control @error('department_name') is-invalid @enderror" 
                                       placeholder="Enter department name" required maxlength="100" 
                                       value="{{ old('department_name') }}">
                                @error('department_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-plus-circle me-2"></i>Create Department
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Department Quick Stats -->
            <div class="col-md-6 mb-4">
                <div class="aa-card h-100 shadow-sm">
                    <div class="card-header header-maroon">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up me-2"></i>Department Overview
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $totalEmployees = $departments->sum('employees_count');
                            $avgEmployeesPerDept = $departments->count() > 0 ? round($totalEmployees / $departments->count(), 1) : 0;
                            $largestDept = $departments->sortByDesc('employees_count')->first();
                            $smallestDept = $departments->where('employees_count', '>', 0)->sortBy('employees_count')->first();
                        @endphp
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded position-relative overflow-hidden">
                                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-primary opacity-10"></div>
                                    <div class="position-relative">
                                        <div class="display-6 fw-bold text-primary">{{ $departments->count() }}</div>
                                        <div class="small text-muted">Total Departments</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-3 bg-light rounded position-relative overflow-hidden">
                                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-success opacity-10"></div>
                                    <div class="position-relative">
                                        <div class="display-6 fw-bold text-success">{{ $totalEmployees }}</div>
                                        <div class="small text-muted">Total Employees</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="small text-muted mb-1"><i class="bi bi-trophy me-1"></i>Largest Department</div>
                                            <div class="fw-semibold text-warning">
                                                {{ $largestDept ? $largestDept->department_name : 'N/A' }}
                                                @if($largestDept)
                                                    <span class="text-muted">({{ $largestDept->employees_count }} emp.)</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="small text-muted mb-1"><i class="bi bi-calculator me-1"></i>Average per Dept</div>
                                            <div class="fw-semibold text-info">{{ $avgEmployeesPerDept }} employees</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Department Distribution Chart -->
                        <div class="mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-bar-chart me-2"></i>Department Size Distribution</h6>
                            @foreach($departments->sortByDesc('employees_count') as $dept)
                                @php
                                    $percentage = $totalEmployees > 0 ? ($dept->employees_count / $totalEmployees) * 100 : 0;
                                    $colors = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];
                                    $color = $colors[$loop->index % count($colors)];
                                @endphp
                                <div class="mb-2">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>{{ $dept->department_name }}</span>
                                        <span>{{ $dept->employees_count }} ({{ number_format($percentage, 1) }}%)</span>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar bg-{{ $color }}" role="progressbar" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department List -->
        <div class="aa-card shadow-sm">
            <div class="card-header header-maroon">
                <h4 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>All Departments
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background:transparent;">
                            <tr>
                                <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-hash me-1"></i>ID</th>
                                <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-building me-1"></i>Department Name</th>
                                <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-people me-1"></i>Employees</th>
                                <th class="py-3 px-2 text-center" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-gear me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($departments as $department)
                                <tr>
                                    <td class="py-3 px-4">#{{ $department->department_id }}</td>
                                    <td class="py-3 px-4">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-building me-2 text-muted"></i>
                                            <span class="fw-semibold">{{ $department->department_name }}</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="badge bg-{{ $department->employees_count > 0 ? 'success' : 'secondary' }}">
                                            {{ $department->employees_count }} employee{{ $department->employees_count != 1 ? 's' : '' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary me-1 edit-dept-btn" 
                                                onclick="openEditModal({{ $department->department_id }})" 
                                                data-dept-id="{{ $department->department_id }}"
                                                title="Edit Department">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-department-btn" data-department-id="{{ $department->department_id }}" data-department-name="{{ $department->department_name }}" data-employees-count="{{ $department->employees_count }}" {{ $department->employees_count > 0 ? 'disabled title="Cannot delete department with employees"' : '' }}>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>

                                <!-- Edit Department Modal -->
                                <div class="modal" id="editDepartmentModal_{{ $department->department_id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editDepartmentModalLabel_{{ $department->department_id }}">
                                                    <i class="bi bi-pencil-square me-2"></i>Edit Department: {{ $department->department_name }}
                                                </h5>
                                                <button type="button" class="btn-close" onclick="closeModal(document.getElementById('editDepartmentModal_{{ $department->department_id }}'))" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('departments.update', ['department' => $department->department_id]) }}" method="POST" id="editDepartmentForm_{{ $department->department_id }}" onsubmit="return validateAndSubmit(this)">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="edit_department_name_{{ $department->department_id }}" class="form-label">
                                                            <i class="bi bi-building me-2"></i>Department Name
                                                        </label>
                                                        <input type="text" 
                                                               name="department_name" 
                                                               id="edit_department_name_{{ $department->department_id }}"
                                                               class="form-control" 
                                                               value="{{ $department->department_name }}" 
                                                               required 
                                                               maxlength="100"
                                                               placeholder="Enter department name">
                                                        <div class="invalid-feedback"></div>
                                                    </div>
                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        This department currently has <strong>{{ $department->employees_count }}</strong> employee{{ $department->employees_count != 1 ? 's' : '' }}.
                                                        @if($department->employees_count > 0)
                                                            <br><small class="text-muted">Note: You cannot delete this department while it has employees.</small>
                                                        @endif
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary cancel-btn" onclick="closeModal(document.getElementById('editDepartmentModal_{{ $department->department_id }}'))">
                                                        <i class="bi bi-x-circle me-1"></i>Cancel
                                                    </button>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="bi bi-check-circle me-1"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                            <h5>No Departments Found</h5>
                                            <p>Create your first department using the form above.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Delete Department Modal -->
    <div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-labelledby="deleteDepartmentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none;">
                    <h5 class="modal-title" id="deleteDepartmentModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Delete Department
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
                        <p class="text-muted mb-0" id="deleteDepartmentMessage">
                            Are you sure you want to delete this department? 
                            <br><strong class="text-danger">This action cannot be undone.</strong>
                        </p>
                    </div>
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <small id="deleteDepartmentWarning">This will permanently remove the department from the system.</small>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteDepartmentBtn" onclick="confirmDeleteDepartment()">
                        <i class="bi bi-trash me-1"></i>Delete Department
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;">
        <!-- Success Toast -->
        <div id="successToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <i class="bi bi-check-circle-fill me-2"></i>
                <strong class="me-auto">Success</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="successMessage"></div>
        </div>

        <!-- Error Toast -->
        <div id="errorToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-danger text-white">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong class="me-auto">Error</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="errorMessage"></div>
        </div>

        <!-- Info Toast -->
        <div id="infoToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-info text-white">
                <i class="bi bi-info-circle-fill me-2"></i>
                <strong class="me-auto">Info</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="infoMessage"></div>
        </div>
    </div>

    <style>
        .aa-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            background: #fff;
            /* Removed transitions to prevent conflicts */
        }
        
        /* Completely disable hover effects to prevent modal flickering */
        .aa-card:hover {
            /* No hover effects */
        }
        
        .header-maroon {
            background: var(--aa-maroon);
            color: #fff;
            border: none;
            padding: 1rem 1.5rem;
        }
        
        .header-maroon .card-title {
            color: #fff;
            font-weight: 600;
        }
        
        .btn-warning {
            background: var(--aa-yellow);
            border-color: var(--aa-yellow);
            color: #000;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-warning:hover {
            background: #e0a800;
            border-color: #e0a800;
            color: #000;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
        }
        
        .table th {
            border-bottom: 2px solid #dee2e6;
            font-weight: 700;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        .table tbody tr {
            transition: background-color 0.2s ease;
        }
        
        .table tbody tr:hover {
            background-color: rgba(86, 0, 0, 0.05);
        }
        
        .badge {
            font-size: 0.85em;
            transition: transform 0.2s ease;
        }
        
        .badge:hover {
            transform: scale(1.05);
        }
        
        .btn-outline-primary:hover {
            background: var(--aa-maroon);
            border-color: var(--aa-maroon);
            transform: scale(1.05);
        }
        
        .btn-outline-danger:hover {
            background: #dc3545;
            border-color: #dc3545;
            transform: scale(1.05);
        }
        
        .modal-header {
            background: var(--aa-maroon);
            color: #fff;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        .alert-info {
            background-color: #e3f2fd;
            border-color: #90caf9;
            color: #0d47a1;
        }
        
        /* NUCLEAR ANTI-FLICKER APPROACH - ZERO TRANSITIONS/ANIMATIONS */
        .modal,
        .modal *,
        .modal::before,
        .modal::after,
        .modal *::before,
        .modal *::after {
            transition: none !important;
            animation: none !important;
            transform: none !important;
            will-change: auto !important;
        }
        
        .modal {
            z-index: 1055 !important;
            display: none !important;
        }
        
        .modal.show {
            display: block !important;
        }
        
        .modal-backdrop {
            z-index: 1050 !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            transition: none !important;
            animation: none !important;
            opacity: 0.5 !important;
        }
        
        .modal-dialog {
            margin: 1.75rem auto !important;
            max-width: 500px !important;
            position: relative !important;
            width: auto !important;
            pointer-events: none !important;
            transform: none !important;
        }
        
        .modal-content {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            pointer-events: auto !important;
            background-color: #fff !important;
            background-clip: padding-box !important;
            border: 1px solid rgba(0, 0, 0, 0.2) !important;
            border-radius: 0.3rem !important;
            outline: 0 !important;
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.5) !important;
        }
        
        /* Override ALL hover effects in modal */
        .modal *:hover {
            transform: none !important;
            animation: none !important;
            transition: none !important;
        }
        
        /* Only allow color changes on hover */
        .modal .btn:hover {
            transition: background-color 0.1s ease, border-color 0.1s ease, color 0.1s ease !important;
        }
        
        .modal .form-control:hover,
        .modal input:hover,
        .modal textarea:hover {
            transition: border-color 0.1s ease !important;
        }
        
        .progress {
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .progress-bar {
            transition: width 0.8s ease-in-out;
        }
        
        .opacity-10 {
            opacity: 0.1;
        }
        
        /* All animations disabled to prevent modal conflicts */
        .aa-card {
            /* No animations */
        }
        
        .table tbody tr {
            /* No animations */
        }
    </style>

    <script>
        // Debug function to check if script is loading
        console.log('Department management script loaded');
        
        // Prevent multiple event bindings and modal conflicts
        let modalHandlersAttached = false;
        
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - Department script');
            
            // Only attach handlers once
            if (modalHandlersAttached) return;
            modalHandlersAttached = true;
            
            // Initialize delete department modal
            initializeDeleteDepartmentModal();
            
            // Remove any existing click handlers to prevent conflicts
            document.querySelectorAll('.edit-dept-btn').forEach(function(btn) {
                // Clone button to remove all event listeners
                const newBtn = btn.cloneNode(true);
                btn.parentNode.replaceChild(newBtn, btn);
            });
            
            // Auto-hide success/error messages after 5 seconds
            const alerts = document.querySelectorAll('.alert-dismissible');
            alerts.forEach(function(alert) {
                setTimeout(() => {
                    try {
                        const bsAlert = new bootstrap.Alert(alert);
                        bsAlert.close();
                    } catch (e) {
                        console.log('Alert already dismissed');
                    }
                }, 5000);
            });
            
            // Handle delete confirmations with better messaging
            document.querySelectorAll('form[action*="departments"][method="POST"] button[type="submit"]').forEach(function(deleteBtn) {
                if (deleteBtn.innerHTML.includes('bi-trash')) {
                    const form = deleteBtn.closest('form');
                    
                    // Remove existing listeners to prevent duplicates
                    const newForm = form.cloneNode(true);
                    form.parentNode.replaceChild(newForm, form);
                    
                    newForm.addEventListener('submit', function(e) {
                        const departmentRow = newForm.closest('tr');
                        const departmentName = departmentRow.querySelector('.fw-semibold').textContent.trim();
                        const employeeCount = departmentRow.querySelector('.badge').textContent.trim();
                        
                        const confirmMessage = `Are you sure you want to delete "${departmentName}"?\n\n` +
                                             `Current employees: ${employeeCount}\n\n` +
                                             `This action cannot be undone!`;
                        
                        if (!confirm(confirmMessage)) {
                            e.preventDefault();
                            return false;
                        }
                        
                        // Show loading state
                        const submitBtn = newForm.querySelector('button[type="submit"]');
                        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
                        submitBtn.disabled = true;
                    });
                }
            });
        });
        
        // Ultra-simple modal handler - no Bootstrap animations
        function openEditModal(departmentId) {
            console.log('openEditModal called with ID:', departmentId);
            
            // Hide all modals first
            document.querySelectorAll('.modal').forEach(function(m) {
                m.style.display = 'none';
                m.classList.remove('show');
            });
            
            // Remove all backdrops
            document.querySelectorAll('.modal-backdrop').forEach(function(b) {
                b.remove();
            });
            
            const modal = document.getElementById('editDepartmentModal_' + departmentId);
            console.log('Modal element found:', modal);
            
            if (modal) {
                try {
                    // Reset form state
                    const form = modal.querySelector('form');
                    const input = form.querySelector('[name="department_name"]');
                    const feedbackDiv = form.querySelector('.invalid-feedback');
                    
                    if (input) {
                        input.classList.remove('is-invalid');
                    }
                    if (feedbackDiv) {
                        feedbackDiv.textContent = '';
                    }
                    
                    // Create backdrop manually
                    const backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop';
                    backdrop.style.cssText = 'position: fixed; top: 0; left: 0; z-index: 1050; width: 100vw; height: 100vh; background-color: rgba(0, 0, 0, 0.5);';
                    document.body.appendChild(backdrop);
                    
                    // Show modal manually - no Bootstrap
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    
                    // Add body class
                    document.body.classList.add('modal-open');
                    document.body.style.overflow = 'hidden';
                    
                    console.log('Modal should be showing now');
                    
                    // Focus on input
                    setTimeout(() => {
                        if (input) {
                            input.focus();
                            input.select();
                        }
                    }, 100);
                    
                    // Handle ALL close buttons
                    const closeBtns = modal.querySelectorAll('.btn-close, [data-bs-dismiss="modal"], .btn-secondary, .cancel-btn');
                    closeBtns.forEach(function(btn) {
                        btn.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            closeModal(modal);
                        };
                    });
                    
                    // Handle backdrop click
                    backdrop.onclick = function(e) {
                        if (e.target === backdrop) {
                            closeModal(modal);
                        }
                    };
                    
                    // Handle ESC key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            closeModal(modal);
                        }
                    });
                    
                } catch (error) {
                    console.error('Error opening modal:', error);
                    alert('Error opening modal: ' + error.message);
                }
            } else {
                console.error('Modal not found with ID: editDepartmentModal_' + departmentId);
                alert('Modal not found. Please refresh the page.');
            }
        }
        
        // Close modal function
        function closeModal(modal) {
            // Hide modal
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            
            // Remove backdrop
            document.querySelectorAll('.modal-backdrop').forEach(function(b) {
                b.remove();
            });
            
            // Restore body
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
        }
        
        // Make functions globally available
        window.openEditModal = openEditModal;
        window.closeModal = closeModal;
        
        // Form validation handler
        function validateAndSubmit(form) {
            const departmentNameInput = form.querySelector('[name="department_name"]');
            const feedbackDiv = form.querySelector('.invalid-feedback');
            
            // Reset validation state
            departmentNameInput.classList.remove('is-invalid');
            feedbackDiv.textContent = '';
            
            // Validate department name
            if (!departmentNameInput.value.trim()) {
                departmentNameInput.classList.add('is-invalid');
                feedbackDiv.textContent = 'Department name is required.';
                return false;
            }
            
            if (departmentNameInput.value.trim().length > 100) {
                departmentNameInput.classList.add('is-invalid');
                feedbackDiv.textContent = 'Department name cannot exceed 100 characters.';
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving...';
            submitBtn.disabled = true;
            
            return true;
        }
        
        // Make function globally available
        window.validateAndSubmit = validateAndSubmit;
        
        // Delete Department Modal Functions
        function initializeDeleteDepartmentModal() {
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-department-btn')) {
                    e.preventDefault();
                    const departmentId = e.target.closest('.delete-department-btn').dataset.departmentId;
                    const departmentName = e.target.closest('.delete-department-btn').dataset.departmentName;
                    const employeesCount = e.target.closest('.delete-department-btn').dataset.employeesCount;
                    deleteDepartment(departmentId, departmentName, employeesCount);
                }
            });
        }

        function deleteDepartment(departmentId, departmentName, employeesCount) {
            // Store the departmentId for later use
            document.getElementById('confirmDeleteDepartmentBtn').dataset.departmentId = departmentId;
            
            // Update modal content with department-specific information
            document.getElementById('deleteDepartmentMessage').innerHTML = 
                `Are you sure you want to delete "<strong>${departmentName}</strong>"?<br><strong class="text-danger">This action cannot be undone.</strong>`;
            
            document.getElementById('deleteDepartmentWarning').textContent = 
                `This will permanently remove the department from the system. Current employees: ${employeesCount}`;
            
            // Show the custom delete modal
            const modal = new bootstrap.Modal(document.getElementById('deleteDepartmentModal'));
            modal.show();
        }

        function confirmDeleteDepartment() {
            const departmentId = document.getElementById('confirmDeleteDepartmentBtn').dataset.departmentId;
            
            try {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("departments.destroy", ":departmentId") }}'.replace(':departmentId', departmentId);

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
                form.submit();
            } catch (error) {
                console.error('Error deleting department:', error);
                alert('An error occurred while trying to delete the department. Please try again.');
            }
        }
        
        // Test function to verify everything is working
        function testDepartmentFunctions() {
            console.log('Testing department functions...');
            console.log('openEditModal function:', typeof window.openEditModal);
            console.log('validateAndSubmit function:', typeof window.validateAndSubmit);
            console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
        }
        
        // Run test when page loads
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(testDepartmentFunctions, 1000);
        });
        
        // Cool notification functions
        function showNotification(type, message) {
            const toastEl = document.getElementById(type + 'Toast');
            const messageEl = document.getElementById(type + 'Message');
            
            if (!toastEl || !messageEl) return;
            
            messageEl.textContent = message;
            
            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 5000
            });
            
            toast.show();
            
            // Add animation effect
            toastEl.style.transform = 'translateX(100%)';
            setTimeout(() => {
                toastEl.style.transition = 'transform 0.3s ease-in-out';
                toastEl.style.transform = 'translateX(0)';
            }, 100);
        }

        // Show notifications based on session messages
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('success', '{{ session('success') }}');
            });
        @endif
        
        @if(session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('error', '{{ session('error') }}');
            });
        @endif
        
        @if(session('info'))
            document.addEventListener('DOMContentLoaded', function() {
                showNotification('info', '{{ session('info') }}');
            });
        @endif
    </script>
@include('layouts.toast-js')
@endsection
