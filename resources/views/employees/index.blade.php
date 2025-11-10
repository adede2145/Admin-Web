@extends('layouts.theme')

@section('title', 'Employees')

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
                            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search employees..." value="{{ request('search') }}" style="width: 320px; height: 38px;">
                            <button type="submit" class="btn btn-sm ms-2 px-3" style="background-color: var(--aa-yellow); border-color: var(--aa-yellow); color: #3d0a0a;"><i class="bi bi-search me-1"></i>Search</button>
                            <button type="button" class="btn btn-sm ms-2 px-3" style="background-color: var(--aa-yellow); border-color: var(--aa-yellow); color: #3d0a0a;" onclick="this.form.search.value=''; this.form.submit();"><i class="bi bi-x-circle me-1"></i>Clear</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle employee-table mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col"><i class="bi bi-list-ol me-1"></i>No.</th>
                                        <th scope="col"><i class="bi bi-image me-1"></i>Photo</th>
                                        <th scope="col"><i class="bi bi-hash me-1"></i>Employee ID</th>
                                        <th scope="col"><i class="bi bi-person me-1"></i>Full Name</th>
                                        <th scope="col"><i class="bi bi-briefcase me-1"></i>Employment Type</th>
                                        <th scope="col"><i class="bi bi-building me-1"></i>Office</th>
                                        <th scope="col"><i class="bi bi-credit-card me-1"></i>RFID Code</th>
                                        <th scope="col"><i class="bi bi-gear me-1"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($employees as $employee)
                                        <tr class="emp-row" data-employee-id="{{ $employee->employee_id }}" data-href="{{ request()->fullUrlWithQuery(['employee_id' => $employee->employee_id]) }}">
                                            <td>
                                                <span class="text-muted">{{ $employee->employee_id }}</span>
                                            </td>
                                            <td>
                                                <img src="{{ route('employees.photo', $employee->employee_id) }}" 
                                                     alt="{{ $employee->full_name }}" 
                                                     class="rounded-circle" 
                                                     style="width:40px;height:40px;object-fit:cover;" 
                                                     loading="lazy"
                                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/40x40?text=%20';">
                                            </td>
                                    <td>
                                        <code class="text-primary">{{ $employee->employee_code ?? 'N/A' }}</code>
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
                                            <button class="btn btn-danger btn-sm" onclick="deleteEmployee({{ $employee->employee_id }})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
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
                                <img src="{{ route('employees.photo', $selectedEmployee->employee_id) }}" 
                                     alt="{{ $selectedEmployee->full_name }}" 
                                     style="width:100%;height:100%;object-fit:cover;" 
                                     loading="eager"
                                     onerror="this.onerror=null; this.src='https://via.placeholder.com/140x140?text=%20';">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modals -->
    @foreach($employees as $employee)
    <div class="modal fade" id="editEmployee{{ $employee->employee_id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white;">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="bi bi-person-lines-fill me-2 fs-5"></i>Edit Employee #{{ $employee->employee_id }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
                </div>
                <form action="{{ route('employees.update', $employee->employee_id) }}" method="POST" enctype="multipart/form-data" class="employee-edit-form">
                    @csrf
                    @method('PUT')
                    <div class="modal-body p-4" style="background: #fafbfc;">
                        <div class="row g-4">
                            <!-- Employee ID/Code (Editable in modal) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                    <i class="bi bi-person-badge me-2 fs-6"></i>Employee ID/Code
                                </label>
                                <input type="text" name="employee_code" class="form-control form-control-lg border-2" 
                                       value="{{ $employee->employee_code ?? '' }}" required
                                       style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                       onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            </div>

                            <!-- Employment Type -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                    <i class="bi bi-briefcase me-2 fs-6"></i>Employment Type
                                </label>
                                <select name="employment_type" class="form-select form-select-lg border-2" required
                                        style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                        onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                    <option value="full_time" {{ $employee->employment_type === 'full_time' ? 'selected' : '' }}>Full Time</option>
                                    <option value="part_time" {{ $employee->employment_type === 'part_time' ? 'selected' : '' }}>Part Time</option>
                                    <option value="cos" {{ $employee->employment_type === 'cos' ? 'selected' : '' }}>COS</option>
                                </select>
                            </div>

                            <!-- Full Name -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                    <i class="bi bi-person-circle me-2 fs-6"></i>Full Name
                                </label>
                                <input type="text" name="full_name" class="form-control form-control-lg border-2" 
                                       value="{{ $employee->full_name ?? '' }}" required
                                       style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                       onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                       onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                            </div>

                            <!-- Office -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                    <i class="bi bi-building me-2 fs-6"></i>Office
                                </label>
                                <select name="department_id" class="form-select form-select-lg border-2" required
                                        style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                        onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                        onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                    @foreach($departments as $dept)
                                        <option value="{{ $dept->department_id }}" {{ $employee->department_id == $dept->department_id ? 'selected' : '' }}>
                                            {{ $dept->department_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- RFID Code (Read-only) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                    <i class="bi bi-credit-card me-2 fs-6"></i>RFID Code
                                </label>
                                <input type="text" class="form-control form-control-lg border-2" 
                                       value="{{ $employee->rfid_code ?? '' }}" readonly
                                       style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; background-color: #f8f9fa; color: #6c757d;">
                            </div>

                            <!-- Photo Upload Section -->
                            <div class="col-md-12">
                                <label class="form-label fw-semibold d-flex align-items-center mb-3" style="color: var(--aa-maroon);">
                                    <i class="bi bi-camera me-2 fs-6"></i>Profile Photo
                                </label>
                                <div class="d-flex align-items-center gap-4 p-3" style="background: white; border: 2px solid #e5e7eb; border-radius: 12px; border-style: dashed;">
                                    <div class="position-relative">
                                        <img id="preview_{{ $employee->employee_id }}" 
                                             src="{{ route('employees.photo', $employee->employee_id) }}" 
                                             alt="Preview" 
                                             class="rounded-circle border-3 border-white shadow-sm" 
                                             style="width: 100px; height: 100px; object-fit: cover;" 
                                             onerror="this.onerror=null; this.src='https://via.placeholder.com/100x100?text=No+Photo';">
                                        <div class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-1" style="width: 32px; height: 32px;">
                                            <i class="bi bi-camera text-white d-flex align-items-center justify-content-center" style="font-size: 14px;"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <input type="file" name="photo" id="fileInput_{{ $employee->employee_id }}" 
                                               accept="image/*" class="form-control form-control-lg border-2" 
                                               onchange="updatePreview({{ $employee->employee_id }}, this)"
                                               style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                               onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                               onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <div class="form-text d-flex align-items-center mt-2" style="color: #6c757d; font-size: 0.875rem;">
                                            <i class="bi bi-info-circle me-2"></i>
                                            Choose an image to update the employee photo. Recommended: Square format, max 2MB.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4" style="background: white;">
                        <a href="{{ route('employees.fingerprints.edit', $employee->employee_id) }}" 
                           class="btn btn-lg px-4 fw-semibold"
                           target="_blank"
                           style="background: linear-gradient(135deg, #28a745, #20c997); color: white; border: none; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);"
                           onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(40, 167, 69, 0.4)'"
                           onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(40, 167, 69, 0.3)'">
                            <i class="bi bi-fingerprint me-2"></i>Edit Fingerprints
                        </a>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-lg px-4" data-bs-dismiss="modal"
                                    style="background: #f8f9fa; color: #6c757d; border: 2px solid #e5e7eb; border-radius: 8px; font-weight: 600; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#dee2e6'"
                                    onmouseout="this.style.background='#f8f9fa'; this.style.borderColor='#e5e7eb'">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-lg px-4 fw-bold text-white"
                                    style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); border: none; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(86, 0, 0, 0.3);"
                                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(86, 0, 0, 0.4)'"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(86, 0, 0, 0.3)'">
                                <i class="bi bi-check-circle me-2"></i>Update Employee
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Attendance Modal -->
    <div class="modal fade" id="viewAttendance{{ $employee->employee_id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white;">
                    <h5 class="modal-title fw-bold d-flex align-items-center">
                        <i class="bi bi-clock-history me-2 fs-5"></i>Attendance History - {{ $employee->full_name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
                </div>
                <div class="modal-body p-4" style="background: #fafbfc;">
                    <div class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 rounded-3" style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-left: 4px solid #2196f3;">
                                    <i class="bi bi-calendar-check fs-4 me-3" style="color: #1976d2;"></i>
                                    <div>
                                        <div class="fw-semibold" style="color: #1565c0;">Total Records</div>
                                        <div class="fs-5 fw-bold" style="color: #0d47a1;">{{ $employee->attendanceLogs()->count() }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 rounded-3" style="background: linear-gradient(135deg, #f3e5f5, #e1bee7); border-left: 4px solid #9c27b0;">
                                    <i class="bi bi-clock fs-4 me-3" style="color: #7b1fa2;"></i>
                                    <div>
                                        <div class="fw-semibold" style="color: #6a1b9a;">Recent Activity</div>
                                        <div class="fs-6" style="color: #4a148c;">{{ $employee->attendanceLogs()->latest('time_in')->first() ? \Carbon\Carbon::parse($employee->attendanceLogs()->latest('time_in')->first()->time_in)->diffForHumans() : 'No records' }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex align-items-center p-3 rounded-3" style="background: linear-gradient(135deg, #e8f5e8, #c8e6c9); border-left: 4px solid #4caf50;">
                                    <i class="bi bi-person-check fs-4 me-3" style="color: #388e3c;"></i>
                                    <div>
                                        <div class="fw-semibold" style="color: #2e7d32;">Employee ID</div>
                                        <div class="fs-6 fw-bold" style="color: #1b5e20;">#{{ $employee->employee_id }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                            <thead style="background: linear-gradient(135deg, #f8f9fa, #e9ecef);">
                                <tr>
                                    <th class="fw-semibold py-3 px-4" style="color: var(--aa-maroon); border: none;">
                                        <i class="bi bi-calendar3 me-2"></i>Date
                                    </th>
                                    <th class="fw-semibold py-3 px-4" style="color: var(--aa-maroon); border: none;">
                                        <i class="bi bi-arrow-right-circle me-2"></i>Time In
                                    </th>
                                    <th class="fw-semibold py-3 px-4" style="color: var(--aa-maroon); border: none;">
                                        <i class="bi bi-arrow-left-circle me-2"></i>Time Out
                                    </th>
                                    <th class="fw-semibold py-3 px-4" style="color: var(--aa-maroon); border: none;">
                                        <i class="bi bi-gear me-2"></i>Method
                                    </th>
                                    <th class="fw-semibold py-3 px-4 text-center" style="color: var(--aa-maroon); border: none;">
                                        <i class="bi bi-tools me-2"></i>Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($employee->attendanceLogs()->latest('time_in')->take(10)->get() as $log)
                                    <tr style="transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='white'">
                                        <td class="py-3 px-4 fw-medium" style="border: none;">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-calendar-date me-2" style="color: #6c757d;"></i>
                                                {{ \Carbon\Carbon::parse($log->time_in)->format('M d, Y') }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-4" style="border: none;">
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2 rounded-pill">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ \Carbon\Carbon::parse($log->time_in)->format('h:i A') }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4" style="border: none;">
                                            @if($log->time_out)
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2 rounded-pill">
                                                    <i class="bi bi-clock me-1"></i>
                                                    {{ \Carbon\Carbon::parse($log->time_out)->format('h:i A') }}
                                                </span>
                                            @else
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>Not Set
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-3 px-4" style="border: none;">
                                            <span class="badge {{ $log->method === 'rfid' ? 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25' : 'bg-success bg-opacity-10 text-success border border-success border-opacity-25' }} px-3 py-2 rounded-pill">
                                                <i class="bi bi-{{ $log->method === 'rfid' ? 'credit-card' : 'fingerprint' }} me-1"></i>
                                                {{ ucfirst($log->method) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-4 text-center" style="border: none;">
                                            <button class="btn btn-sm px-3 py-2 rounded-pill" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editAttendance{{ $log->log_id }}"
                                                    style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white; border: none; transition: all 0.3s ease; box-shadow: 0 2px 4px rgba(86, 0, 0, 0.2);"
                                                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 4px 8px rgba(86, 0, 0, 0.3)'"
                                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 4px rgba(86, 0, 0, 0.2)'">
                                                <i class="bi bi-pencil-square me-1"></i>Edit
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5" style="border: none;">
                                            <div class="d-flex flex-column align-items-center">
                                                <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                                                <h6 class="text-muted mb-2">No attendance records found</h6>
                                                <small class="text-muted">This employee hasn't logged any attendance yet.</small>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4" style="background: white;">
                    <button type="button" class="btn btn-lg px-4 fw-semibold" data-bs-dismiss="modal"
                            style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white; border: none; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(86, 0, 0, 0.3);"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(86, 0, 0, 0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(86, 0, 0, 0.3)'">
                        <i class="bi bi-check-circle me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <!-- Edit Attendance Modals for Employee History -->
    @foreach($employees as $employee)
        @foreach($employee->attendanceLogs()->latest('time_in')->take(10)->get() as $log)
        <div class="modal fade" id="editAttendance{{ $log->log_id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                    <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); color: white;">
                        <h5 class="modal-title fw-bold d-flex align-items-center">
                            <i class="bi bi-pencil-square me-2 fs-5"></i>Edit Attendance Record
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
                    </div>
                    <form action="{{ route('attendance.update', $log->log_id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-body p-4" style="background: #fafbfc;">
                            <div class="row g-4">
                                <!-- Employee (Read-only) -->
                                <div class="col-md-12">
                                    <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-person-circle me-2 fs-6"></i>Employee
                                    </label>
                                    <input type="text" class="form-control form-control-lg border-2" 
                                           value="{{ $employee->full_name ?? 'N/A' }}" readonly
                                           style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; background-color: #f8f9fa; color: #6c757d;">
                                </div>

                                <!-- Date -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-calendar3 me-2 fs-6"></i>Date
                                    </label>
                                    <input type="date" name="date" class="form-control form-control-lg border-2" 
                                           value="{{ \Carbon\Carbon::parse($log->time_in)->format('Y-m-d') }}" required
                                           style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                           onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                           onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <!-- Method -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-gear me-2 fs-6"></i>Method
                                    </label>
                                    <select name="method" class="form-select form-select-lg border-2" required
                                            style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                            onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                            onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                        <option value="rfid" {{ $log->method === 'rfid' ? 'selected' : '' }}>RFID</option>
                                        <option value="fingerprint" {{ $log->method === 'fingerprint' ? 'selected' : '' }}>Fingerprint</option>
                                    </select>
                                </div>

                                <!-- Time In -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-arrow-right-circle me-2 fs-6"></i>Time In
                                    </label>
                                    <input type="time" name="time_in" class="form-control form-control-lg border-2" 
                                           value="{{ \Carbon\Carbon::parse($log->time_in)->format('H:i') }}" required
                                           style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                           onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                           onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                </div>

                                <!-- Time Out -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold d-flex align-items-center" style="color: var(--aa-maroon);">
                                        <i class="bi bi-arrow-left-circle me-2 fs-6"></i>Time Out
                                    </label>
                                    <input type="time" name="time_out" class="form-control form-control-lg border-2" 
                                           value="{{ $log->time_out ? \Carbon\Carbon::parse($log->time_out)->format('H:i') : '' }}"
                                           style="border-color: #e5e7eb; border-radius: 8px; padding: 12px 16px; font-size: 1rem; transition: all 0.3s ease;"
                                           onfocus="this.style.borderColor='var(--aa-maroon)'; this.style.boxShadow='0 0 0 0.2rem rgba(86, 0, 0, 0.15)'"
                                           onblur="this.style.borderColor='#e5e7eb'; this.style.boxShadow='none'">
                                    <div class="form-text d-flex align-items-center mt-2" style="color: #6c757d; font-size: 0.875rem;">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Leave empty if employee hasn't timed out yet
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4" style="background: white;">
                            <button type="button" class="btn btn-lg px-4 me-2" data-bs-dismiss="modal"
                                    style="background: #f8f9fa; color: #6c757d; border: 2px solid #e5e7eb; border-radius: 8px; font-weight: 600; transition: all 0.3s ease;"
                                    onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#dee2e6'"
                                    onmouseout="this.style.background='#f8f9fa'; this.style.borderColor='#e5e7eb'">
                                <i class="bi bi-x-circle me-2"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-lg px-4 fw-bold text-white"
                                    style="background: linear-gradient(135deg, var(--aa-maroon), var(--aa-maroon-dark)); border: none; border-radius: 8px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(86, 0, 0, 0.3);"
                                    onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 16px rgba(86, 0, 0, 0.4)'"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(86, 0, 0, 0.3)'">
                                <i class="bi bi-check-circle me-2"></i>Update Record
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    @endforeach

    <script>
        // Cool notification function - Define first so it's available everywhere
        function showNotification(type, message) {
            const toastEl = document.getElementById(type + 'Toast');
            const messageEl = document.getElementById(type + 'Message');
            
            if (!toastEl || !messageEl) {
                console.error('Toast elements not found:', type + 'Toast', type + 'Message');
                return;
            }
            
            messageEl.textContent = message;
            
            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 6000 // Show for 6 seconds
            });
            
            toast.show();
            
            // Add smooth slide-in animation
            toastEl.style.transform = 'translateX(100%)';
            toastEl.style.opacity = '0';
            setTimeout(() => {
                toastEl.style.transition = 'all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
                toastEl.style.transform = 'translateX(0)';
                toastEl.style.opacity = '1';
            }, 10);
        }

        // Listen for messages from the registration window
        window.addEventListener('message', function(event) {
            // For security, you might want to check event.origin
            // if (event.origin !== 'http://127.0.0.1:18426') return;
            
            if (event.data && (event.data.type === 'EMPLOYEE_REGISTERED' || event.data.type === 'EMPLOYEE_UPDATED')) {
                console.log('Received message from registration window:', event.data);
                
                // Store the notification message in sessionStorage before reloading
                const message = event.data.message || 'Employee data updated successfully!';
                sessionStorage.setItem('pendingNotification', JSON.stringify({
                    type: 'success',
                    message: message
                }));
                
                // Reload the page immediately to show the new employee
                window.location.reload();
            }
        });

        // Check for pending notification on page load
        document.addEventListener('DOMContentLoaded', function() {
            const pendingNotification = sessionStorage.getItem('pendingNotification');
            if (pendingNotification) {
                try {
                    const notification = JSON.parse(pendingNotification);
                    // Clear it immediately to prevent showing it again
                    sessionStorage.removeItem('pendingNotification');
                    
                    console.log('Showing pending notification:', notification);
                    
                    // Show the notification after a brief delay to ensure page is fully loaded
                    setTimeout(() => {
                        showNotification(notification.type, notification.message);
                    }, 300);
                } catch (e) {
                    console.error('Error parsing pending notification:', e);
                    sessionStorage.removeItem('pendingNotification');
                }
            }
        });

        document.querySelectorAll('.emp-row').forEach(function(r){
            r.addEventListener('click', function(e){
                if (e.target.closest('button')) return;
                
                const employeeId = this.dataset.employeeId || this.querySelector('code').textContent.replace('#', '');
                console.log('Clicked employee row, ID:', employeeId);
                selectEmployee(employeeId);
            });
        });

        function selectEmployee(employeeId) {
            console.log('Selecting employee:', employeeId);
            
            // Remove active class from all rows
            document.querySelectorAll('.emp-row').forEach(row => {
                row.classList.remove('table-active');
            });
            
            // Add active class to selected row
            const selectedRow = document.querySelector(`[data-employee-id="${employeeId}"]`);
            if (selectedRow) {
                selectedRow.classList.add('table-active');
                console.log('Row highlighted');
            }
            
            // Update URL without page refresh
            const url = new URL(window.location);
            url.searchParams.set('employee_id', employeeId);
            window.history.pushState({}, '', url);
            
            // Simple approach - reload the page with the employee_id parameter
            // This ensures the summary panel updates correctly
            window.location.href = url.toString();
        }

        function updateEmployeeSummary(data) {
            console.log('Updating employee summary with data:', data);
            
            // Find the summary card - it's in the right sidebar
            const summaryCard = document.querySelector('.col-lg-3 .aa-card .card-body');
            if (!summaryCard) {
                console.error('Summary card not found');
                return;
            }

            // Update employee photo
            const photoImg = summaryCard.querySelector('img');
            if (photoImg) {
                photoImg.src = data.photo_url;
                photoImg.alt = data.full_name;
                console.log('Updated photo');
            }

            // Update employee name - it's the fw-bold fs-5 element
            const nameElement = summaryCard.querySelector('.fw-bold.fs-5');
            if (nameElement) {
                nameElement.textContent = data.full_name;
                console.log('Updated name to:', data.full_name);
            }

            // Update office - it's the text-muted small element right after the name
            const deptElement = summaryCard.querySelector('.text-muted.small.mb-3');
            if (deptElement) {
                deptElement.textContent = data.department;
                console.log('Updated office to:', data.department);
            }

            // Update statistics - these are in col-6 elements with display-6 fw-bold
            const statsElements = summaryCard.querySelectorAll('.col-6 .display-6.fw-bold');
            console.log('Found', statsElements.length, 'stats elements');
            if (statsElements.length >= 4) {
                statsElements[0].textContent = data.daysPresent; // Days Present
                statsElements[1].textContent = data.lateArrivals; // Late Arrivals
                statsElements[2].textContent = data.totalHours; // Total Hours
                statsElements[3].textContent = data.overtimeHours; // Overtime
                console.log('Updated stats');
            }

            // Update attendance rate - find the element that shows attendance rate
            const attendanceRateElements = summaryCard.querySelectorAll('.fw-bold');
            attendanceRateElements.forEach(element => {
                if (element.textContent.includes('%')) {
                    element.textContent = data.attendanceRate + '%';
                    console.log('Updated attendance rate to:', data.attendanceRate + '%');
                }
            });

            // Update last attendance - find the element that shows last attendance
            const lastAttendanceElements = summaryCard.querySelectorAll('.text-muted');
            lastAttendanceElements.forEach(element => {
                if (element.textContent.includes('Last Attendance')) {
                    const nextElement = element.nextElementSibling;
                    if (nextElement) {
                        nextElement.textContent = data.lastLog || '-';
                        console.log('Updated last attendance');
                    }
                }
            });

        }

        // Initialize selected employee highlighting on page load
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const selectedEmployeeId = urlParams.get('employee_id');
            if (selectedEmployeeId) {
                const selectedRow = document.querySelector(`[data-employee-id="${selectedEmployeeId}"]`);
                if (selectedRow) {
                    selectedRow.classList.add('table-active');
                }
            }
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
            // Store the employeeId for later use
            document.getElementById('confirmDeleteEmployeeBtn').dataset.employeeId = employeeId;
            
            // Show the custom delete modal
            const modal = new bootstrap.Modal(document.getElementById('deleteEmployeeModal'));
            modal.show();
        }

        function confirmDeleteEmployee() {
            const employeeId = document.getElementById('confirmDeleteEmployeeBtn').dataset.employeeId;
            if (!employeeId) {
                showNotification('error', 'Employee ID not found');
                return;
            }

            // Hide the modal first
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteEmployeeModal'));
            if (modal) {
                modal.hide();
            }

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
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.text();
            })
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
                showNotification('error', 'Failed to delete employee: ' + error.message);
            })
            .finally(() => {
                // Clean up the form
                if (document.body.contains(form)) {
                    document.body.removeChild(form);
                }
            });
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

        // Handle employee edit forms with AJAX for better UX
        document.addEventListener('DOMContentLoaded', function() {
            // Only target edit forms, not the search form or delete helpers
            document.querySelectorAll('form.employee-edit-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const formData = new FormData(form);
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn.innerHTML;
                    const modal = bootstrap.Modal.getInstance(form.closest('.modal'));
                    
                    // Debug: Log form data
                    console.log('Form Data Contents:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ':', value);
                    }
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Updating...';
                    
                    fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || formData.get('_token'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(response => {
                        // Check if response is JSON or HTML
                        const contentType = response.headers.get('content-type');
                        if (contentType && contentType.includes('application/json')) {
                            return response.json().then(data => ({
                                ok: response.ok,
                                status: response.status,
                                data: data,
                                isJson: true
                            }));
                        } else {
                            return response.text().then(html => ({
                                ok: response.ok,
                                status: response.status,
                                data: html,
                                isJson: false
                            }));
                        }
                    })
                    .then(result => {
                        if (result.isJson) {
                            // Handle JSON response
                            if (result.ok) {
                                showNotification('success', result.data.message || 'Employee updated successfully!');
                                if (modal) modal.hide();
                                
                                // Update all images for this employee (ETag handles caching)
                                const employeeId = form.closest('.modal').id.replace('editEmployee', '');
                                
                                // Construct the proper photo URL (no cache-busting needed)
                                const photoUrl = '{{ url("/employees") }}/' + employeeId + '/photo';
                                
                                // Update modal preview image
                                const previewImg = document.getElementById('preview_' + employeeId);
                                if (previewImg) {
                                    previewImg.src = photoUrl;
                                }
                                
                                // Update summary panel image (right sidebar)
                                const summaryImg = document.querySelector('.col-lg-3 .aa-card .card-body img');
                                if (summaryImg) {
                                    summaryImg.src = photoUrl;
                                }
                                
                                // Update table row - ALL COLUMNS except Primary Employee ID (No. column)
                                const tableRow = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
                                if (tableRow) {
                                    // Update photo (column 2)
                                    const tableImg = tableRow.querySelector('img');
                                    if (tableImg) {
                                        tableImg.src = photoUrl;
                                    }
                                    
                                    // Update employee code (column 3)
                                    const employeeCode = formData.get('employee_code');
                                    if (employeeCode) {
                                        const codeCell = tableRow.querySelector('td:nth-child(3) code');
                                        if (codeCell) {
                                            codeCell.textContent = employeeCode;
                                        }
                                    }
                                    
                                    // Update employee name (column 4)
                                    const fullName = formData.get('full_name');
                                    if (fullName) {
                                        const nameCell = tableRow.querySelector('td:nth-child(4)');
                                        if (nameCell) {
                                            nameCell.innerHTML = '<div class="d-flex align-items-center"><i class="bi bi-person me-2 text-muted"></i>' + fullName + '</div>';
                                        }
                                    }
                                    
                                    // Update employment type (column 5)
                                    const empType = formData.get('employment_type');
                                    if (empType) {
                                        const typeCell = tableRow.querySelector('td:nth-child(5) .badge');
                                        if (typeCell) {
                                            typeCell.textContent = empType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                        }
                                    }
                                    
                                    // Update office/department (column 6)
                                    const deptId = formData.get('department_id');
                                    if (deptId) {
                                        // Get department name from the select dropdown
                                        const deptSelect = form.querySelector('select[name="department_id"]');
                                        if (deptSelect) {
                                            const selectedOption = deptSelect.options[deptSelect.selectedIndex];
                                            const deptName = selectedOption.text;
                                            const officeCell = tableRow.querySelector('td:nth-child(6) .badge');
                                            if (officeCell) {
                                                officeCell.textContent = deptName;
                                            }
                                        }
                                    }
                                }
                                
                                // Update summary panel name and department
                                const summaryName = document.querySelector('.col-lg-3 .fw-bold.fs-5');
                                if (summaryName) {
                                    const fullName = formData.get('full_name');
                                    if (fullName) {
                                        summaryName.textContent = fullName;
                                    }
                                }
                                
                                // Update summary panel department
                                const summaryDept = document.querySelector('.col-lg-3 .text-muted.small.mb-3');
                                if (summaryDept) {
                                    const deptId = formData.get('department_id');
                                    if (deptId) {
                                        const deptSelect = form.querySelector('select[name="department_id"]');
                                        if (deptSelect) {
                                            const selectedOption = deptSelect.options[deptSelect.selectedIndex];
                                            summaryDept.textContent = selectedOption.text;
                                        }
                                    }
                                }
                            } else {
                                showNotification('error', result.data.message || 'Failed to update employee.');
                            }
                        } else {
                            // Handle HTML response
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(result.data, 'text/html');
                            
                            // Check for success message
                            const successAlert = doc.querySelector('.alert-success');
                            const errorAlert = doc.querySelector('.alert-danger');
                            
                            if (successAlert) {
                                const message = successAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                                showNotification('success', message);
                                
                                // Close modal
                                if (modal) modal.hide();
                                
                                // Update all images for this employee (ETag handles caching)
                                const employeeId = form.closest('.modal').id.replace('editEmployee', '');
                                
                                // Construct the proper photo URL (no cache-busting needed)
                                const photoUrl = '{{ url("/employees") }}/' + employeeId + '/photo';
                                
                                // Update modal preview image
                                const previewImg = document.getElementById('preview_' + employeeId);
                                if (previewImg) {
                                    previewImg.src = photoUrl;
                                }
                                
                                // Update summary panel image (right sidebar)
                                const summaryImg = document.querySelector('.col-lg-3 .aa-card .card-body img');
                                if (summaryImg) {
                                    summaryImg.src = photoUrl;
                                }
                                
                                // Update table row - ALL COLUMNS except Primary Employee ID (No. column)
                                const tableRow = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
                                if (tableRow) {
                                    // Update photo (column 2)
                                    const tableImg = tableRow.querySelector('img');
                                    if (tableImg) {
                                        tableImg.src = photoUrl;
                                    }
                                    
                                    // Update employee code (column 3)
                                    const employeeCode = formData.get('employee_code');
                                    if (employeeCode) {
                                        const codeCell = tableRow.querySelector('td:nth-child(3) code');
                                        if (codeCell) {
                                            codeCell.textContent = employeeCode;
                                        }
                                    }
                                    
                                    // Update employee name (column 4)
                                    const fullName = formData.get('full_name');
                                    if (fullName) {
                                        const nameCell = tableRow.querySelector('td:nth-child(4)');
                                        if (nameCell) {
                                            nameCell.innerHTML = '<div class="d-flex align-items-center"><i class="bi bi-person me-2 text-muted"></i>' + fullName + '</div>';
                                        }
                                    }
                                    
                                    // Update employment type (column 5)
                                    const empType = formData.get('employment_type');
                                    if (empType) {
                                        const typeCell = tableRow.querySelector('td:nth-child(5) .badge');
                                        if (typeCell) {
                                            typeCell.textContent = empType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                        }
                                    }
                                    
                                    // Update office/department (column 6)
                                    const deptId = formData.get('department_id');
                                    if (deptId) {
                                        // Get department name from the select dropdown
                                        const deptSelect = form.querySelector('select[name="department_id"]');
                                        if (deptSelect) {
                                            const selectedOption = deptSelect.options[deptSelect.selectedIndex];
                                            const deptName = selectedOption.text;
                                            const officeCell = tableRow.querySelector('td:nth-child(6) .badge');
                                            if (officeCell) {
                                                officeCell.textContent = deptName;
                                            }
                                        }
                                    }
                                }
                                
                                // Update summary panel name and department
                                const summaryName = document.querySelector('.col-lg-3 .fw-bold.fs-5');
                                if (summaryName) {
                                    const fullName = formData.get('full_name');
                                    if (fullName) {
                                        summaryName.textContent = fullName;
                                    }
                                }
                                
                                // Update summary panel department
                                const summaryDept = document.querySelector('.col-lg-3 .text-muted.small.mb-3');
                                if (summaryDept) {
                                    const deptId = formData.get('department_id');
                                    if (deptId) {
                                        const deptSelect = form.querySelector('select[name="department_id"]');
                                        if (deptSelect) {
                                            const selectedOption = deptSelect.options[deptSelect.selectedIndex];
                                            summaryDept.textContent = selectedOption.text;
                                        }
                                    }
                                }
                            } else if (errorAlert) {
                                const message = errorAlert.textContent.trim().replace(/^\s*[×]\s*/, '');
                                showNotification('error', message);
                            } else if (result.ok) {
                                showNotification('success', 'Employee updated successfully!');
                                if (modal) modal.hide();
                                
                                // Update all images for this employee (ETag handles caching)
                                const employeeId = form.closest('.modal').id.replace('editEmployee', '');
                                
                                // Construct the proper photo URL (no cache-busting needed)
                                const photoUrl = '{{ url("/employees") }}/' + employeeId + '/photo';
                                
                                // Update modal preview image
                                const previewImg = document.getElementById('preview_' + employeeId);
                                if (previewImg) {
                                    previewImg.src = photoUrl;
                                }
                                
                                // Update summary panel image (right sidebar)
                                const summaryImg = document.querySelector('.col-lg-3 .aa-card .card-body img');
                                if (summaryImg) {
                                    summaryImg.src = photoUrl;
                                }
                                
                                // Update table row - ALL COLUMNS except Primary Employee ID (No. column)
                                const tableRow = document.querySelector(`tr[data-employee-id="${employeeId}"]`);
                                if (tableRow) {
                                    // Update photo (column 2)
                                    const tableImg = tableRow.querySelector('img');
                                    if (tableImg) {
                                        tableImg.src = photoUrl;
                                    }
                                    
                                    // Update employee code (column 3)
                                    const employeeCode = formData.get('employee_code');
                                    if (employeeCode) {
                                        const codeCell = tableRow.querySelector('td:nth-child(3) code');
                                        if (codeCell) {
                                            codeCell.textContent = employeeCode;
                                        }
                                    }
                                    
                                    // Update employee name (column 4)
                                    const fullName = formData.get('full_name');
                                    if (fullName) {
                                        const nameCell = tableRow.querySelector('td:nth-child(4)');
                                        if (nameCell) {
                                            nameCell.innerHTML = '<div class="d-flex align-items-center"><i class="bi bi-person me-2 text-muted"></i>' + fullName + '</div>';
                                        }
                                    }
                                    
                                    // Update employment type (column 5)
                                    const empType = formData.get('employment_type');
                                    if (empType) {
                                        const typeCell = tableRow.querySelector('td:nth-child(5) .badge');
                                        if (typeCell) {
                                            typeCell.textContent = empType.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                                        }
                                    }
                                    
                                    // Update office/department (column 6)
                                    const deptId = formData.get('department_id');
                                    if (deptId) {
                                        // Get department name from the select dropdown
                                        const deptSelect = form.querySelector('select[name="department_id"]');
                                        if (deptSelect) {
                                            const selectedOption = deptSelect.options[deptSelect.selectedIndex];
                                            const deptName = selectedOption.text;
                                            const officeCell = tableRow.querySelector('td:nth-child(6) .badge');
                                            if (officeCell) {
                                                officeCell.textContent = deptName;
                                            }
                                        }
                                    }
                                }
                                
                                // Update summary panel name and department
                                const summaryName = document.querySelector('.col-lg-3 .fw-bold.fs-5');
                                if (summaryName) {
                                    const fullName = formData.get('full_name');
                                    if (fullName) {
                                        summaryName.textContent = fullName;
                                    }
                                }
                                
                                // Update summary panel department
                                const summaryDept = document.querySelector('.col-lg-3 .text-muted.small.mb-3');
                                if (summaryDept) {
                                    const deptId = formData.get('department_id');
                                    if (deptId) {
                                        const deptSelect = form.querySelector('select[name="department_id"]');
                                        if (deptSelect) {
                                            const selectedOption = deptSelect.options[deptSelect.selectedIndex];
                                            summaryDept.textContent = selectedOption.text;
                                        }
                                    }
                                }
                            } else {
                                showNotification('error', 'Failed to update employee. Please check the server logs.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('error', 'An error occurred while updating employee: ' + error.message);
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
        <div id="successToast" class="toast align-items-center text-white bg-success border-0 shadow-lg" role="alert" style="min-width: 350px;">
            <div class="d-flex">
                <div class="toast-body py-3 px-4">
                    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                    <strong id="successMessage" style="font-size: 1rem;"></strong>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        
        <div id="errorToast" class="toast align-items-center text-white bg-danger border-0 shadow-lg" role="alert" style="min-width: 350px;">
            <div class="d-flex">
                <div class="toast-body py-3 px-4">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <strong id="errorMessage" style="font-size: 1rem;"></strong>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
        
        <div id="infoToast" class="toast align-items-center text-white bg-info border-0 shadow-lg" role="alert" style="min-width: 350px;">
            <div class="d-flex">
                <div class="toast-body py-3 px-4">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i>
                    <strong id="infoMessage" style="font-size: 1rem;"></strong>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <!-- Custom Delete Employee Modal -->
    <div class="modal fade" id="deleteEmployeeModal" tabindex="-1" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #dc3545, #c82333); color: white; border-bottom: none;">
                    <h5 class="modal-title" id="deleteEmployeeModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Delete Employee
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="mb-4">
                        <div class="d-flex justify-content-center mb-3">
                            <div class="bg-danger bg-opacity-10 rounded-circle p-3">
                                <i class="bi bi-person-x text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="text-danger mb-3">Confirm Employee Deletion</h5>
                        <p class="text-muted mb-0">
                            Are you sure you want to delete this employee record? 
                            <br><strong class="text-danger">This action cannot be undone.</strong>
                        </p>
                    </div>
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>This will permanently remove the employee and all associated data from the system.</small>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteEmployeeBtn" onclick="confirmDeleteEmployee()">
                        <i class="bi bi-person-x me-1"></i>Delete Employee
                    </button>
                </div>
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
        .employee-table td:nth-child(4) {
            text-align: left;
            white-space: normal;
        }
        /* Ensure table doesn't break layout */
        .employee-table {
            table-layout: fixed;
            width: 100%;
            min-width: 900px;
        }
        /* Column widths for better layout */
        .employee-table th:nth-child(1),
        .employee-table td:nth-child(1) { width: 60px; }
        .employee-table th:nth-child(2),
        .employee-table td:nth-child(2) { width: 80px; }
        .employee-table th:nth-child(3),
        .employee-table td:nth-child(3) { width: 120px; }
        .employee-table th:nth-child(4),
        .employee-table td:nth-child(4) { width: 200px; }
        .employee-table th:nth-child(5),
        .employee-table td:nth-child(5) { width: 120px; }
        .employee-table th:nth-child(6),
        .employee-table td:nth-child(6) { width: 120px; }
        .employee-table th:nth-child(7),
        .employee-table td:nth-child(7) { width: 120px; }
        .employee-table th:nth-child(8),
        .employee-table td:nth-child(8) { width: 140px; }
        
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