@extends('layouts.theme')
@section('title', 'Employment Types')
@section('content')
    {{-- Security check: Only super admins can access this page --}}
    @if(!auth()->check() || !auth()->user()->role || auth()->user()->role->role_name !== 'super_admin')
        <div class="container-fluid">
            <div class="alert alert-danger">
                <i class="bi bi-shield-exclamation me-2"></i>
                <strong>Access Denied!</strong> Only Super Admins can manage employment types.
            </div>
        </div>
        @php abort(403, 'Access denied. Only Super Admins can manage employment types.'); @endphp
    @endif

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-briefcase me-2 fs-4"></i>Manage Employment Types
            </h1>
            <span class="badge bg-primary fs-5">Super Admin</span>
        </div>

        <div class="row align-items-stretch">
            <!-- Create Employment Type Form -->
            <div class="col-md-6 mb-4">
                <div class="aa-card h-100 shadow-sm rounded-4 overflow-hidden border-0">
                    <div class="card-header header-maroon border-0 rounded-0">
                        <h4 class="card-title mb-0">
                            <i class="bi bi-plus-circle me-2"></i>Create New Employment Type
                        </h4>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('employment-types.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="type_name" class="form-label">
                                    <i class="bi bi-code-slash me-2"></i>Type Name
                                </label>
                                <input type="text" name="type_name" id="type_name" 
                                       class="form-control @error('type_name') is-invalid @enderror" 
                                       placeholder="e.g., consultant, intern" required maxlength="100" 
                                       value="{{ old('type_name') }}">
                                <div class="form-text">Use lowercase with underscores (e.g., part_time_consultant)</div>
                                @error('type_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="display_name" class="form-label">
                                    <i class="bi bi-tag me-2"></i>Display Name
                                </label>
                                <input type="text" name="display_name" id="display_name" 
                                       class="form-control @error('display_name') is-invalid @enderror" 
                                       placeholder="e.g., Consultant" required maxlength="100" 
                                       value="{{ old('display_name') }}">
                                <div class="form-text">How it will appear in forms and dropdowns</div>
                                @error('display_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-warning">
                                    <i class="bi bi-plus-circle me-2"></i>Create Employment Type
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Employment Type Quick Stats -->
            <div class="col-md-6 mb-4">
                <div class="aa-card h-100 shadow-sm rounded-4 overflow-hidden border-0">
                    <div class="card-header header-maroon border-0 rounded-0">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up me-2"></i>Employment Type Overview
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        @php
                            $totalTypes = $employmentTypes->count();
                            $defaultTypes = $employmentTypes->where('is_default', true)->count();
                            $customTypes = $totalTypes - $defaultTypes;
                        @endphp
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-center p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #1d4ed8, #2563eb);">
                                    <div class="display-5 fw-bold text-white">{{ $totalTypes }}</div>
                                    <div class="small text-white-50">Total Types</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="text-center p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #0f766e, #0d9488);">
                                    <div class="display-5 fw-bold text-white">{{ $defaultTypes }}</div>
                                    <div class="small text-white-50">System Default</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="p-3 bg-light rounded-4 border">
                                    <div class="row">
                                        <div class="col-6">
                                            <div class="small text-muted mb-1"><i class="bi bi-star me-1"></i>Custom Types</div>
                                            <div class="fw-semibold" style="color:#d97706;">{{ $customTypes }} type{{ $customTypes != 1 ? 's' : '' }}</div>
                                        </div>
                                        <div class="col-6">
                                            <div class="small text-muted mb-1"><i class="bi bi-shield-check me-1"></i>Protected</div>
                                            <div class="fw-semibold" style="color:#0ea5e9;">{{ $defaultTypes }} type{{ $defaultTypes != 1 ? 's' : '' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Type List Preview -->
                        <div class="mt-4">
                            <h6 class="text-muted mb-3"><i class="bi bi-list-check me-2"></i>Employment Types</h6>
                            @foreach($employmentTypes as $type)
                                <div class="mb-2 d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold">{{ $type->display_name }}</span>
                                        @if($type->is_default)
                                            <span class="badge bg-primary ms-1" style="font-size: 0.7rem;">Default</span>
                                        @endif
                                    </div>
                                    <small class="text-muted">{{ $type->type_name }}</small>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Employment Types List -->
        <div class="aa-card shadow-sm rounded-4 overflow-hidden border-0">
            <div class="card-header header-maroon border-0 rounded-0">
                <h4 class="card-title mb-0">
                    <i class="bi bi-list-ul me-2"></i>All Employment Types
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead style="background:transparent;">
                            <tr>
                                <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-hash me-1"></i>ID</th>
                                <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-tag me-1"></i>Display Name</th>
                                <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-code-slash me-1"></i>Type Name</th>
                                <th class="py-3 px-4" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-people me-1"></i>Employees</th>
                                <th class="py-3 px-2 text-center" style="color:#b71c1c; font-size:1.1rem; font-weight:700; background:transparent;"><i class="bi bi-gear me-1"></i>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($employmentTypes as $type)
                                <tr>
                                    <td class="py-3 px-4">#{{ $type->id }}</td>
                                    <td class="py-3 px-4">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-briefcase me-2 text-muted"></i>
                                            <span class="fw-semibold">{{ $type->display_name }}</span>
                                            @if($type->is_default)
                                                <span class="badge bg-primary ms-2" style="font-size: 0.7rem;">Default</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">
                                        <code class="text-muted">{{ $type->type_name }}</code>
                                    </td>
                                    <td class="py-3 px-4">
                                        @php
                                            $empCount = \App\Models\Employee::where('employment_type', $type->type_name)->count();
                                        @endphp
                                        <span class="badge bg-{{ $empCount > 0 ? 'success' : 'secondary' }}">
                                            {{ $empCount }} employee{{ $empCount != 1 ? 's' : '' }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-2 text-center">
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-primary me-1" 
                                                onclick="openEditModal({{ $type->id }})" 
                                                title="Edit Employment Type">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        
                                        @if(!$type->is_default)
                                            @php
                                                $empCount = \App\Models\Employee::where('employment_type', $type->type_name)->count();
                                            @endphp
                                            @if($empCount == 0)
                                                <button type="button" class="btn btn-sm btn-outline-danger delete-type-btn" 
                                                        data-type-id="{{ $type->id }}" 
                                                        data-type-name="{{ $type->display_name }}"
                                                        title="Delete Employment Type">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            @else
                                                <span class="badge bg-secondary text-muted small" 
                                                      title="Cannot delete type with {{ $empCount }} employee{{ $empCount != 1 ? 's' : '' }}">
                                                    <i class="bi bi-shield-lock me-1"></i>Protected
                                                </span>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary text-muted small" 
                                                  title="Cannot delete system default types">
                                                <i class="bi bi-shield-lock me-1"></i>System
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal" id="editTypeModal_{{ $type->id }}" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">
                                                    <i class="bi bi-pencil-square me-2"></i>Edit Employment Type: {{ $type->display_name }}
                                                </h5>
                                                <button type="button" class="btn-close" onclick="closeModal(document.getElementById('editTypeModal_{{ $type->id }}'))" aria-label="Close"></button>
                                            </div>
                                            <form action="{{ route('employment-types.update', $type) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <div class="modal-body">
                                                    @if($type->is_default)
                                                        <div class="alert alert-info">
                                                            <i class="bi bi-info-circle me-2"></i>
                                                            This is a system default type. Type name cannot be changed, only the display name.
                                                        </div>
                                                        <input type="hidden" name="type_name" value="{{ $type->type_name }}">
                                                    @else
                                                        <div class="mb-3">
                                                            <label for="edit_type_name_{{ $type->id }}" class="form-label">
                                                                <i class="bi bi-code-slash me-2"></i>Type Name <span class="text-danger">*</span>
                                                            </label>
                                                            <input type="text" 
                                                                   name="type_name" 
                                                                   id="edit_type_name_{{ $type->id }}"
                                                                   class="form-control" 
                                                                   value="{{ $type->type_name }}" 
                                                                   required 
                                                                   maxlength="100">
                                                            <div class="form-text">Use lowercase with underscores</div>
                                                        </div>
                                                    @endif
                                                    
                                                    <div class="mb-3">
                                                        <label for="edit_display_name_{{ $type->id }}" class="form-label">
                                                            <i class="bi bi-tag me-2"></i>Display Name <span class="text-danger">*</span>
                                                        </label>
                                                        <input type="text" 
                                                               name="display_name" 
                                                               id="edit_display_name_{{ $type->id }}"
                                                               class="form-control" 
                                                               value="{{ $type->display_name }}" 
                                                               required 
                                                               maxlength="100">
                                                        <div class="form-text">How it will appear in forms</div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" onclick="closeModal(document.getElementById('editTypeModal_{{ $type->id }}'))">
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
                                    <td colspan="5" class="text-center py-4">
                                        <i class="bi bi-inbox fs-1 text-muted d-block mb-2"></i>
                                        <p class="text-muted">No employment types found. Create one to get started.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($employmentTypes->hasPages())
                <div class="d-flex justify-content-center mt-3">
                    {{ $employmentTypes->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function openEditModal(typeId) {
            const modal = document.getElementById('editTypeModal_' + typeId);
            if (modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            }
        }

        function closeModal(modal) {
            if (modal) {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            }
        }

        // Handle delete button clicks
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-type-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const typeId = this.dataset.typeId;
                    const typeName = this.dataset.typeName;
                    
                    if (confirm(`Are you sure you want to delete "${typeName}"?\n\nThis action cannot be undone.`)) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/employment-types/${typeId}`;
                        form.innerHTML = `
                            @csrf
                            @method('DELETE')
                        `;
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>
@endsection
