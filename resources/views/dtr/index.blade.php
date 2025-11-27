@extends('layouts.app')

@section('title', 'DTR Generation')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>DTR Generation</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateDTRModal">
            <i class="bi bi-file-earmark-text me-2"></i>Generate DTR Report
        </button>
    </div>

    <!-- DTR Generation Modal -->
    <div class="modal fade" id="generateDTRModal" tabindex="-1" aria-labelledby="generateDTRModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header header-maroon text-white" style="background-color: #800000;">
                    <h5 class="modal-title" id="generateDTRModalLabel">
                        <i class="bi bi-file-text me-2"></i>Generate DTR Report
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('attendance.dtr') }}" method="POST" id="dtrForm">
                    @csrf
                    <div class="modal-body p-4" style="background-color: #f8f9fa;">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="report_type" class="form-label fw-bold" style="color: #800000;">
                                    <i class="bi bi-gear me-1"></i>Report Type
                                </label>
                                <select class="form-select" id="report_type" name="report_type" required>
                                    <option value="weekly">Weekly Report</option>
                                    <option value="monthly" selected>Monthly Report</option>
                                    <option value="custom">Custom Period</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="department_id" class="form-label fw-bold" style="color: #800000;">
                                    <i class="bi bi-building me-1"></i>Office
                                </label>
                                <select class="form-select" id="department_id" name="department_id">
                                    <option value="">All Offices</option>
                                    {{-- Assuming departments might be passed, if not, this is a placeholder --}}
                                    @if(isset($departments))
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->department_id }}">{{ $dept->department_name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold" style="color: #800000;">
                                <i class="bi bi-people me-1"></i>Employees to include
                            </label>
                            
                            <!-- Search and Select All Container -->
                            <div class="bg-white p-2 border rounded-top border-bottom-0">
                                <div class="input-group mb-2">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                                    <input type="text" class="form-control border-start-0 ps-0" id="employeeSearch" placeholder="Search employee...">
                                </div>
                                <div class="form-check ms-2">
                                    <input class="form-check-input" type="checkbox" id="selectAllEmployees">
                                    <label class="form-check-label fw-bold text-primary" for="selectAllEmployees" style="cursor: pointer;">
                                        Select All Employees
                                    </label>
                                </div>
                            </div>

                            <div class="card rounded-top-0">
                                <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                                    <ul class="list-group list-group-flush" id="employeeList">
                                        @foreach($employees as $employee)
                                            <li class="list-group-item">
                                                <div class="form-check">
                                                    <input class="form-check-input employee-checkbox" type="checkbox" name="employee_ids[]" value="{{ $employee->id ?? $employee->employee_id }}" id="emp_{{ $employee->id ?? $employee->employee_id }}">
                                                    <label class="form-check-label w-100" for="emp_{{ $employee->id ?? $employee->employee_id }}">
                                                        <span class="fw-bold">{{ $employee->last_name }}, {{ $employee->first_name }}</span>
                                                        @if($employee->department)
                                                            <small class="text-muted ms-1">({{ $employee->department->department_name ?? 'N/A' }})</small>
                                                        @endif
                                                    </label>
                                                </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                            <div class="form-text">Leave empty to include all employees in the selected office.</div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="start_date" class="form-label fw-bold" style="color: #800000;">
                                    <i class="bi bi-calendar-event me-1"></i>Start Date
                                </label>
                                <input type="date" class="form-control" id="start_date" name="start_date" required>
                                <div class="form-text" id="startDateHelp">First day of current month</div>
                            </div>
                            <div class="col-md-6">
                                <label for="end_date" class="form-label fw-bold" style="color: #800000;">
                                    <i class="bi bi-calendar-check me-1"></i>End Date
                                </label>
                                <input type="date" class="form-control" id="end_date" name="end_date" required>
                                <div class="form-text" id="endDateHelp">Last day of current month</div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0 p-4" style="background: white;">
                        <button type="button" class="btn btn-outline-secondary btn-lg px-4" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-lg px-4 text-white" style="background-color: #800000;">
                            <i class="bi bi-file-earmark-text me-2"></i>Generate Report
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Set default dates based on report type
        const reportTypeSelect = document.getElementById('report_type');
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const startDateHelp = document.getElementById('startDateHelp');
        const endDateHelp = document.getElementById('endDateHelp');

        function updateDates() {
            const type = reportTypeSelect.value;
            const today = new Date();
            let start, end;

            if (type === 'monthly') {
                // First and last day of current month
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                startDateHelp.textContent = 'First day of current month';
                endDateHelp.textContent = 'Last day of current month';
            } else if (type === 'weekly') {
                // Start of week (Monday) and end of week (Sunday)
                const day = today.getDay();
                const diff = today.getDate() - day + (day == 0 ? -6 : 1); // adjust when day is sunday
                start = new Date(today.setDate(diff));
                end = new Date(today.setDate(start.getDate() + 6));
                startDateHelp.textContent = 'Start of current week';
                endDateHelp.textContent = 'End of current week';
            } else {
                // Custom: keep existing or clear
                startDateHelp.textContent = 'Select start date';
                endDateHelp.textContent = 'Select end date';
                return; // Don't auto-set for custom unless empty
            }

            // Format to YYYY-MM-DD
            if (type !== 'custom') {
                startDateInput.value = start.toISOString().split('T')[0];
                endDateInput.value = end.toISOString().split('T')[0];
            }
        }

        // Initialize
        updateDates();

        // Update on change
        reportTypeSelect.addEventListener('change', updateDates);

        // Search functionality
        const searchInput = document.getElementById('employeeSearch');
        const employeeList = document.getElementById('employeeList');
        const items = employeeList.getElementsByTagName('li');

        searchInput.addEventListener('keyup', function() {
            const filter = searchInput.value.toLowerCase();
            for (let i = 0; i < items.length; i++) {
                const label = items[i].getElementsByTagName('label')[0];
                const txtValue = label.textContent || label.innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    items[i].style.display = "";
                } else {
                    items[i].style.display = "none";
                }
            }
        });

        // Select All functionality
        const selectAllCheckbox = document.getElementById('selectAllEmployees');
        const employeeCheckboxes = document.querySelectorAll('.employee-checkbox');

        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            // Only check visible items if filtering is active? 
            // For now, let's check all visible items to be safe with search
            for (let i = 0; i < items.length; i++) {
                if (items[i].style.display !== 'none') {
                    const checkbox = items[i].querySelector('.employee-checkbox');
                    if (checkbox) checkbox.checked = isChecked;
                }
            }
        });
    });
</script>
    @endsection
