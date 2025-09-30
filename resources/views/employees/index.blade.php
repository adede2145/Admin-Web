@extends('layouts.theme')
@section('content')
    <div class="container-fluid">
    <!-- Error/Success Messages -->
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="fw-bold fs-2 mb-0">
            <i class="bi bi-people me-2 fs-4"></i>Employees
        </h1>
    </div>

    {{-- Employee statistics are now calculated in the controller --}}

    <div class="row">
        <div class="col-lg-9">
            <!-- Employee List -->
            <div class="aa-card h-100">
                <div class="card-header header-maroon">
                    <div class="d-flex justify-content-center align-items-center" style="gap: 32px;">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-list-ul me-2"></i>Employee List
                        </h4>
                        <form action="{{ route('employees.index') }}" method="GET" class="d-flex align-items-center justify-content-center" style="max-width: 450px; margin: 0 auto;">
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search employees..." value="{{ request('search') }}" style="width: 320px;">
                            <button type="submit" class="btn btn-sm ms-2 px-3" style="background-color: var(--aa-yellow); border-color: var(--aa-yellow); color: #3d0a0a;"><i class="bi bi-search me-1"></i>Search</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle employee-table mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col"><i class="bi bi-image me-1"></i>Photo</th>
                                        <th scope="col"><i class="bi bi-hash me-1"></i>Employee ID</th>
                                        <th scope="col"><i class="bi bi-person me-1"></i>Full Name</th>
                                        <th scope="col"><i class="bi bi-briefcase me-1"></i>Employment Type</th>
                                        <th scope="col"><i class="bi bi-building me-1"></i>Department</th>
                                        <th scope="col"><i class="bi bi-credit-card me-1"></i>RFID Code</th>
                                        <th scope="col"><i class="bi bi-gear me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($employees as $employee)
                                        <tr class="emp-row" data-href="{{ request()->fullUrlWithQuery(['employee_id' => $employee->employee_id]) }}">
                                            <td>
                                                <img src="{{ route('employees.photo', $employee->employee_id) }}" alt="{{ $employee->full_name }}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;" onerror="this.onerror=null; this.src='https://via.placeholder.com/40x40?text=%20';">
                                            </td>
                                    <td>
                                        <code class="text-primary">#{{ $employee->employee_id }}</code>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person me-2 text-muted"></i>
                                            {{ $employee->full_name ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-aa small fs-6">
                                            {{ ucfirst(str_replace('_', ' ', $employee->employment_type)) }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-aa">
                                            {{ $employee->department->department_name ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $employee->rfid_code ?? 'Not set' }}</code>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editEmployee{{ $employee->employee_id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewAttendance{{ $employee->employee_id }}">
                                                <i class="bi bi-clock"></i>
                                            </button>
                                            <button class="btn btn-outline-danger" onclick="deleteEmployee({{ $employee->employee_id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox display-4 d-block mb-2"></i>
                                                No employees found
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-center mt-3">
                            {{ $employees->onEachSide(1)->links('pagination::bootstrap-5') }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="aa-card h-100">
                    <div class="card-header header-maroon">
                        <h5 class="mb-0"><i class="bi bi-person-circle me-2"></i>Employee Summary</h5>
                    </div>
                    <div class="card-body text-center p-4">
                        <div class="mx-auto rounded-circle mb-3" style="width:140px;height:140px;background:#eee;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                            @if($selectedEmployee)
                                <img src="{{ route('employees.photo', $selectedEmployee->employee_id) }}" alt="{{ $selectedEmployee->full_name }}" style="width:100%;height:100%;object-fit:cover;" onerror="this.onerror=null; this.src='https://via.placeholder.com/140x140?text=%20';">
                            @else
                                <i class="bi bi-person" style="font-size:64px;color:#aaa"></i>
                            @endif
                        </div>
                        <div class="fw-bold fs-5">{{ $selectedEmployee->full_name ?? 'Select an employee' }}</div>
                        <div class="text-muted small mb-3">{{ $selectedEmployee->department->department_name ?? '' }}</div>
                        <div class="row text-center g-2 mb-3">
                            <div class="col-6">
                                <div class="small text-muted">Days Present</div>
                                <div class="display-6 fw-bold" style="color:var(--aa-maroon)">{{ $employeeStats['daysPresent'] }}</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Late Arrivals</div>
                                <div class="display-6 fw-bold" style="color:var(--aa-maroon)">{{ $employeeStats['lateArrivals'] }}</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Total Hours</div>
                                <div class="display-6 fw-bold" style="color:var(--aa-maroon)">{{ number_format($employeeStats['totalHours'], 1) }}</div>
                            </div>
                            <div class="col-6">
                                <div class="small text-muted">Overtime</div>
                                <div class="display-6 fw-bold" style="color:var(--aa-maroon)">{{ number_format($employeeStats['overtimeHours'], 1) }}</div>
                            </div>
                            <div class="col-12">
                                <div class="small text-muted">Attendance Rate</div>
                                <div class="display-1 fw-bold" style="color:var(--aa-maroon)">{{ $employeeStats['attendanceRate'] }}%</div>
                            </div>
                        </div>
                        <div class="text-muted small mb-2">Last Attendance</div>
                        <div class="mb-3">{{ $employeeStats['lastLog'] ? \Carbon\Carbon::parse($employeeStats['lastLog']->time_in)->format('M d, Y h:i A') : '—' }}</div>
                        @if($selectedEmployee)
                            <a href="{{ url('/attendance') }}?employee_id={{ $selectedEmployee->employee_id }}" class="btn btn-outline-dark btn-sm">View Attendance History</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modals -->
    @foreach($employees as $employee)
    <div class="modal fade" id="editEmployee{{ $employee->employee_id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('employees.update', $employee->employee_id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Employee ID</label>
                            <input type="text" class="form-control" value="#{{ $employee->employee_id }}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <div class="d-flex align-items-center gap-3">
                                <img id="preview_{{ $employee->employee_id }}" src="{{ route('employees.photo', $employee->employee_id) }}" alt="Preview" class="rounded" style="width:80px;height:80px;object-fit:cover;" onerror="this.onerror=null; this.src='https://via.placeholder.com/80x80?text=No+Photo';">
                                <input type="file" name="photo" id="fileInput_{{ $employee->employee_id }}" accept="image/*" class="form-control" onchange="updatePreview({{ $employee->employee_id }}, this)">
                            </div>
                            <div class="form-text">Choose an image to update the employee photo.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="{{ $employee->full_name ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Employment Type</label>
                            <select name="employment_type" class="form-select" required>
                                <option value="full_time" {{ $employee->employment_type === 'full_time' ? 'selected' : '' }}>Full Time</option>
                                <option value="part_time" {{ $employee->employment_type === 'part_time' ? 'selected' : '' }}>Part Time</option>
                                <option value="cos" {{ $employee->employment_type === 'cos' ? 'selected' : '' }}>COS</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Department</label>
                            <select name="department_id" class="form-select" required>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept->department_id }}" {{ $employee->department_id == $dept->department_id ? 'selected' : '' }}>
                                        {{ $dept->department_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">RFID Code</label>
                            <input type="text" class="form-control" value="{{ $employee->rfid_code ?? '' }}" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Attendance Modal -->
    <div class="modal fade" id="viewAttendance{{ $employee->employee_id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Attendance History - {{ $employee->full_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time In</th>
                                    <th>Time Out</th>
                                    <th>Method</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->attendanceLogs()->latest('time_in')->take(10)->get() as $log)
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($log->time_in)->format('M d, Y') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}</td>
                                        <td>
                                            @if($log->time_out)
                                                {{ \Carbon\Carbon::parse($log->time_out)->format('h:i A') }}
                                            @else
                                                <span class="badge bg-warning">Not Set</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $log->method === 'rfid' ? 'primary' : 'success' }}">
                                                {{ ucfirst($log->method) }}
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAttendance{{ $log->log_id }}">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No attendance records found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Edit Attendance Modals for Employee History -->
    @foreach($employees as $employee)
        @foreach($employee->attendanceLogs()->latest('time_in')->take(10)->get() as $log)
        <div class="modal fade" id="editAttendance{{ $log->log_id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Attendance Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('attendance.update', $log->log_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Employee</label>
                                <input type="text" class="form-control" value="{{ $employee->full_name ?? 'N/A' }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" 
                                       value="{{ \Carbon\Carbon::parse($log->time_in)->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time In</label>
                                <input type="time" name="time_in" class="form-control" 
                                       value="{{ \Carbon\Carbon::parse($log->time_in)->format('H:i') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time Out</label>
                                <input type="time" name="time_out" class="form-control" 
                                       value="{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('H:i') : '' }}">
                                <div class="form-text">Leave empty if employee hasn't timed out yet</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Method</label>
                                <select name="method" class="form-select" required>
                                    <option value="rfid" {{ $log->method === 'rfid' ? 'selected' : '' }}>RFID</option>
                                    <option value="fingerprint" {{ $log->method === 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    @endforeach

    <script>
        document.querySelectorAll('.emp-row').forEach(function(r){
            r.addEventListener('click', function(e){
                if (e.target.closest('button')) return;
                window.location.href = this.getAttribute('data-href');
            });
        });
        function updatePreview(empId, input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.getElementById('preview_' + empId);
                    if (img) img.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }
        function deleteEmployee(employeeId) {
            if (confirm('Are you sure you want to delete this employee? This action cannot be undone.')) {
                // Show loading notification
                showNotification('info', 'Deleting employee...');
                
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ url("/employees") }}/' + employeeId;
                
                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                
                form.appendChild(csrfToken);
                form.appendChild(methodField);
                document.body.appendChild(form);
                
                // Handle form submission with AJAX
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': csrfToken.value
                    }
                })
                .then(response => response.text())
                .then(html => {
                    // Parse the response to extract messages
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    
                    // Check for success message
                    const successAlert = doc.querySelector('.alert-success');
                    const errorAlert = doc.querySelector('.alert-danger');
                    
                    if (successAlert) {
                        const message = successAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                        showNotification('success', message);
                        
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else if (errorAlert) {
                        const message = errorAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                        showNotification('error', message);
                    } else {
                        showNotification('success', 'Employee deleted successfully!');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('error', 'An error occurred while deleting employee.');
                });
            }
        }

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

        // Handle all form submissions with AJAX for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Handle employee edit forms
            document.querySelectorAll('form[action*="employees"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Parse the response to extract messages
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Check for success message
                        const successAlert = doc.querySelector('.alert-success');
                        const errorAlert = doc.querySelector('.alert-danger');
                        
                        if (successAlert) {
                            const message = successAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                            showNotification('success', message);
                            
                            // Close modal and reload page after short delay
                            if (modal) modal.hide();
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else if (errorAlert) {
                            const message = errorAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                            showNotification('error', message);
                        } else {
                            showNotification('success', 'Employee updated successfully!');
                            if (modal) modal.hide();
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('error', 'An error occurred while updating employee.');
                    })
                    .finally(() => {
                        // Restore button state
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });
            });

            // Handle attendance edit forms
            document.querySelectorAll('form[action*="attendance"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Parse the response to extract messages
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Check for success message
                        const successAlert = doc.querySelector('.alert-success');
                        const errorAlert = doc.querySelector('.alert-danger');
                        
                        if (successAlert) {
                            const message = successAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                            showNotification('success', message);
                            
                            // Close modal and reload page after short delay
                            if (modal) modal.hide();
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else if (errorAlert) {
                            const message = errorAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                            showNotification('error', message);
                        } else {
                            showNotification('success', 'Attendance record updated successfully!');
                            if (modal) modal.hide();
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('error', 'An error occurred while updating attendance.');
                    })
                    .finally(() => {
                        // Restore button state
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });
            });

            // Handle all time in/out forms
            document.querySelectorAll('form[action*="time-in-out"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Processing...';
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token')
                        }
                    })
                    .then(response => response.text())
                    .then(html => {
                        // Parse the response to extract messages
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        
                        // Check for success message
                        const successAlert = doc.querySelector('.alert-success');
                        const errorAlert = doc.querySelector('.alert-danger');
                        
                        if (successAlert) {
                            const message = successAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                            showNotification('success', message);
                            
                            // Reload page after short delay to update UI
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else if (errorAlert) {
                            const message = errorAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                            showNotification('error', message);
                        } else {
                            showNotification('success', 'Action completed successfully!');
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('error', 'An error occurred. Please try again.');
                    })
                    .finally(() => {
                        // Restore button state
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    });
                });
            });
        });
    </script>

    <!-- Edit Attendance Modals -->
    @foreach($employees as $employee)
        @foreach($employee->attendanceLogs()->latest('time_in')->take(10)->get() as $log)
        <div class="modal fade" id="editAttendance{{ $log->log_id }}" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Attendance Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('attendance.update', $log->log_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Employee</label>
                                <input type="text" class="form-control" value="{{ $log->employee->full_name ?? 'N/A' }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" 
                                       value="{{ \Carbon\Carbon::parse($log->time_in)->format('Y-m-d') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time In</label>
                                <input type="time" name="time_in" class="form-control" 
                                       value="{{ \Carbon\Carbon::parse($log->time_in)->format('H:i') }}" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Time Out</label>
                                <input type="time" name="time_out" class="form-control" 
                                       value="{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('H:i') : '' }}">
                                <div class="form-text">Leave empty if employee hasn't timed out yet</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Method</label>
                                <select name="method" class="form-select" required>
                                    <option value="rfid" {{ $log->method === 'rfid' ? 'selected' : '' }}>RFID</option>
                                    <option value="fingerprint" {{ $log->method === 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                                    <option value="manual" {{ $log->method === 'manual' ? 'selected' : '' }}>Manual</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Update Record</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    @endforeach

    <!-- Cool Toast Notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;">
        <div id="successToast" class="toast align-items-center text-white bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <span id="successMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        
        <div id="errorToast" class="toast align-items-center text-white bg-danger border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <span id="errorMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        
        <div id="infoToast" class="toast align-items-center text-white bg-info border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <span id="infoMessage"></span>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

@endsection

    <style>
        .employee-table-scroll {
            max-height: none;
            overflow: visible;
        }
        /* Ensure proper layout that doesn't interfere with sidebar */
        .employee-table thead th {
            background: #fff !important;
            color: var(--aa-maroon) !important;
            border-bottom: none !important;
            font-weight: 600;
            padding: 0.75rem 1.25rem !important;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .employee-table thead th i {
            color: var(--aa-maroon) !important;
            margin-right: 0.5rem;
        }
        .employee-table tbody {
            max-height: 520px;
            overflow-y: auto;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        .employee-table tbody::-webkit-scrollbar {
            display: none;
        }
        .employee-table td {
            padding: 0.75rem 1rem !important;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }
        .employee-table td:nth-child(3) {
            text-align: left;
            white-space: normal;
        }
        /* Ensure table doesn't break layout */
        .employee-table {
            table-layout: fixed;
            width: 100%;
            min-width: 800px;
        }
        /* Column widths for better layout */
        .employee-table th:nth-child(1),
        .employee-table td:nth-child(1) { width: 80px; }
        .employee-table th:nth-child(2),
        .employee-table td:nth-child(2) { width: 120px; }
        .employee-table th:nth-child(3),
        .employee-table td:nth-child(3) { width: 200px; }
        .employee-table th:nth-child(4),
        .employee-table td:nth-child(4) { width: 120px; }
        .employee-table th:nth-child(5),
        .employee-table td:nth-child(5) { width: 120px; }
        .employee-table th:nth-child(6),
        .employee-table td:nth-child(6) { width: 120px; }
        .employee-table th:nth-child(7),
        .employee-table td:nth-child(7) { width: 140px; }
        
        /* Responsive adjustments */
        @media (max-width: 1200px) {
            .employee-table {
                min-width: 700px;
            }
        }
        
        @media (max-width: 992px) {
            .employee-table {
                min-width: 600px;
            }
            .employee-table td {
                padding: 0.5rem 0.75rem !important;
            }
        }
    </style>