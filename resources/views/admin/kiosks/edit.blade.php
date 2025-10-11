@extends('layouts.theme')
@section('content')

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-pencil me-2 fs-4"></i>Edit Kiosk Location
            </h1>
            <a href="{{ route('kiosks.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Kiosks
            </a>
        </div>

        <!-- Error Messages -->
        @if($errors->any())
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <strong>Validation Error:</strong>
                <ul class="mb-0 mt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Form Card -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 fw-bold text-primary">Kiosk Information</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('kiosks.update', $kiosk) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="kiosk_id" class="form-label">
                                    <i class="bi bi-hash me-1"></i>Kiosk ID
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="kiosk_id" 
                                       value="{{ $kiosk->kiosk_id }}" 
                                       readonly>
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Kiosk ID cannot be changed after creation.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="location" class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i>Location <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('location') is-invalid @enderror" 
                                       id="location" 
                                       name="location" 
                                       value="{{ old('location', $kiosk->location) }}" 
                                       placeholder="Enter kiosk location"
                                       maxlength="100"
                                       required>
                                @error('location')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Kiosk Status Info -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-1">
                                            <i class="bi bi-info-circle me-1"></i>Current Status
                                        </h6>
                                        <p class="mb-0">
                                            @if($kiosk->is_active)
                                                @if($kiosk->isOnline())
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-wifi me-1"></i>Active & Online
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-wifi-off me-1"></i>Active & Offline
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-pause-circle me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-0">
                                            <strong>Last Seen:</strong> {{ $kiosk->last_seen_human }}
                                            @if($kiosk->last_seen)
                                                <br><small class="text-muted">{{ $kiosk->last_seen->setTimezone('Asia/Manila')->format('M d, Y h:i A') }}</small>
                                            @endif
                                        </p>
                                        <p class="mb-0">
                                            <strong>Attendance Count:</strong> {{ $kiosk->attendanceLogs()->count() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('kiosks.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Update Kiosk
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quick Actions Card -->
        <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-warning">
                            <i class="bi bi-gear me-2"></i>Quick Actions
                        </h6>
                    </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Toggle Status</h6>
                        <p class="text-muted small mb-3">
                            Activate or deactivate this kiosk. Inactive kiosks won't accept new attendance logs.
                        </p>
                        <form action="{{ route('kiosks.toggle-status', $kiosk) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-{{ $kiosk->is_active ? 'warning' : 'success' }}">
                                <i class="bi bi-{{ $kiosk->is_active ? 'pause' : 'play' }} me-1"></i>
                                {{ $kiosk->is_active ? 'Deactivate' : 'Activate' }} Kiosk
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-danger">Danger Zone</h6>
                        <p class="text-muted small mb-3">
                            Permanently delete this kiosk location. This action cannot be undone.
                        </p>
                        <form action="{{ route('kiosks.destroy', $kiosk) }}" method="POST" class="d-inline" 
                              onsubmit="return confirm('Are you sure you want to delete this kiosk? This will also delete all associated attendance logs!')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-trash me-1"></i>Delete Kiosk
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
