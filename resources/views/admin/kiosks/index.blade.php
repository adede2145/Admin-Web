@extends('layouts.theme')
@section('content')

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold fs-2 mb-0">
                    <i class="bi bi-display me-2 fs-4"></i>Manage Kiosks
                </h1>
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>All times shown in Manila timezone (UTC+8)
                </small>
            </div>
            <span class="badge bg-primary fs-5">Super Admin</span>
        </div>

        <!-- Success / Error Messages -->
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Analytics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-primary border-4 shadow h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                                    Total Kiosks</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $analytics['total'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-display text-primary" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-success border-4 shadow h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-success text-uppercase mb-1">
                                    Online Kiosks</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $analytics['online'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-wifi text-success" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-warning border-4 shadow h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                                    Offline Kiosks</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $analytics['offline'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-wifi-off text-warning" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-info border-4 shadow h-100">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="text-xs fw-bold text-info text-uppercase mb-1">
                                    Active Kiosks</div>
                                <div class="h5 mb-0 fw-bold text-dark">{{ $analytics['active'] }}</div>
                            </div>
                            <div class="col-auto">
                                <i class="bi bi-check-circle text-info" style="font-size: 2rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <!-- Activity Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Kiosk Activity (Last 30 Days)</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="activityChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performing Kiosks -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 fw-bold text-primary">Top Performing Kiosks</h6>
                    </div>
                    <div class="card-body">
                        @if(count($analytics['top_kiosks']) > 0)
                            @foreach($analytics['top_kiosks'] as $kiosk)
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-{{ $kiosk['status'] === 'online' ? 'success' : ($kiosk['status'] === 'offline' ? 'warning' : 'secondary') }} me-2">
                                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small fw-bold">{{ $kiosk['location'] }}</div>
                                        <div class="text-muted" style="font-size: 0.75rem;">{{ $kiosk['attendance_count'] }} attendances</div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <small class="text-muted">{{ $kiosk['last_seen'] }}</small>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-muted text-center">No kiosk activity data available</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Uptime Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 fw-bold text-primary">Kiosk Uptime Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($analytics['uptime_stats'] as $stat)
                                <div class="col-md-3 mb-3">
                                    <div class="card border-0">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $stat['location'] }}</h5>
                                            <div class="progress mb-2" style="height: 20px;">
                                                <div class="progress-bar {{ $stat['uptime'] >= 80 ? 'bg-success' : ($stat['uptime'] >= 60 ? 'bg-warning' : 'bg-danger') }}" 
                                                     role="progressbar" 
                                                     style="width: {{ $stat['uptime'] }}%">
                                                    {{ $stat['uptime'] }}%
                                                </div>
                                            </div>
                                            <small class="text-muted">Uptime (7 days)</small>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kiosks Management Section -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary">Kiosk Locations</h6>
                <a href="{{ route('kiosks.create') }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Add New Kiosk
                </a>
            </div>
            <div class="card-body">
                @if($kiosks->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-bordered" id="kiosksTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Kiosk ID</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Last Seen</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kiosks as $kiosk)
                                    <tr>
                                        <td class="fw-bold">#{{ $kiosk->kiosk_id }}</td>
                                        <td>{{ $kiosk->location }}</td>
                                        <td>
                                            @if($kiosk->is_active)
                                                @if($kiosk->isOnline())
                                                    <span class="badge bg-success">
                                                        <i class="bi bi-wifi me-1"></i>Online
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning">
                                                        <i class="bi bi-wifi-off me-1"></i>Offline
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">
                                                    <i class="bi bi-pause-circle me-1"></i>Inactive
                                                </span>
                                            @endif
                                        </td>
                                        <td>
                                            <div>{{ $kiosk->last_seen_human }}</div>
                                            @if($kiosk->last_seen)
                                                <small class="text-muted">{{ $kiosk->last_seen->setTimezone('Asia/Manila')->format('M d, Y h:i A') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('kiosks.edit', $kiosk) }}" class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('kiosks.toggle-status', $kiosk) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-outline-{{ $kiosk->is_active ? 'warning' : 'success' }} btn-sm">
                                                        <i class="bi bi-{{ $kiosk->is_active ? 'pause' : 'play' }}"></i>
                                                    </button>
                                                </form>
                                                <form action="{{ route('kiosks.destroy', $kiosk) }}" method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this kiosk location?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bi bi-display text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">No Kiosks Found</h5>
                        <p class="text-muted">Start by adding your first kiosk location.</p>
                        <a href="{{ route('kiosks.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add First Kiosk
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Activity Chart
        const activityData = @json($analytics['activity_data']);
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: activityData.map(item => item.date),
                datasets: [{
                    label: 'Active Kiosks',
                    data: activityData.map(item => item.active),
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
@endsection
