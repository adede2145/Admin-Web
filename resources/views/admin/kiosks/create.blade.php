@extends('layouts.theme')
@section('content')

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold fs-2 mb-0">
                <i class="bi bi-plus-circle me-2 fs-4"></i>Add New Kiosk Location
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
                <form action="{{ route('kiosks.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="mb-3">
                                <label for="location" class="form-label">
                                    <i class="bi bi-geo-alt me-1"></i>Location <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('location') is-invalid @enderror" 
                                       id="location" 
                                       name="location" 
                                       value="{{ old('location') }}" 
                                       placeholder="Enter kiosk location (e.g., Main Entrance, Cafeteria, etc.)"
                                       maxlength="100"
                                       required>
                                @error('location')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <div class="form-text">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Provide a descriptive location name for the kiosk (maximum 100 characters).
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
                            <i class="bi bi-check-circle me-1"></i>Create Kiosk
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-info">
                            <i class="bi bi-info-circle me-2"></i>About Kiosk Management
                        </h6>
                    </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">What is a Kiosk?</h6>
                        <p class="text-muted small">
                            A kiosk is a physical device where employees can clock in and out using RFID cards or fingerprint scanners. 
                            Each kiosk has a unique ID and location for tracking attendance.
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary">After Creation</h6>
                        <p class="text-muted small">
                            Once created, the kiosk will be marked as active by default. You can later edit its location, 
                            toggle its active status, or delete it if no longer needed.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
