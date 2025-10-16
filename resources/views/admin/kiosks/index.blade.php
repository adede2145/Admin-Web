@extends('layouts.theme')
@section('content')

    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="fw-bold fs-2 mb-0">
                    <i class="bi bi-display me-2 fs-4"></i>Manage Kiosks
                    <span id="updateIndicator" class="spinner-border spinner-border-sm text-primary ms-2" role="status" style="display: none;">
                        <span class="visually-hidden">Loading...</span>
                    </span>
                </h1>
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>All times shown in Manila timezone (UTC+8)
                    <span id="lastUpdate" class="ms-3">Last updated: Just now</span>
                </small>
            </div>
            <div class="d-flex align-items-center">
                <button id="refreshBtn" class="btn btn-outline-secondary btn-sm me-2" onclick="manualRefresh()" title="Refresh Data">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <span class="badge bg-primary fs-5">Super Admin</span>
            </div>
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
                                <div class="h5 mb-0 fw-bold text-dark analytics-total">{{ $analytics['total'] }}</div>
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
                                <div class="h5 mb-0 fw-bold text-dark analytics-online">{{ $analytics['online'] }}</div>
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
                                <div class="h5 mb-0 fw-bold text-dark analytics-offline">{{ $analytics['offline'] }}</div>
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
                                <div class="h5 mb-0 fw-bold text-dark analytics-active">{{ $analytics['active'] }}</div>
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
        <div class="row mb-4 align-items-stretch">
            <!-- Activity Chart -->
            <div class="col-xl-8 col-lg-7">
                <div class="card shadow mb-4 h-100">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between header-maroon">
                        <h6 class="m-0 fw-bold text-white">Kiosk Activity (Last 30 Days)</h6>
                    </div>
                    <div class="card-body d-flex flex-column h-100">
                        <div class="chart-area flex-grow-1 d-flex align-items-center justify-content-center" style="height:100%;max-height:400px;">
                            <canvas id="activityChart" style="width:100%;height:100%"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performing Kiosks -->
            <div class="col-xl-4 col-lg-5">
                <div class="card shadow mb-4 h-100">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between header-maroon">
                        <h6 class="m-0 fw-bold text-white">Top Performing Kiosks</h6>
                    </div>
                    <div class="card-body top-kiosks-container d-flex flex-column h-100">
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
                    <div class="card-header py-3 header-maroon">
                        <h6 class="m-0 fw-bold text-white">Kiosk Uptime Statistics</h6>
                    </div>
                    <div class="card-body">
                        <div class="row uptime-stats-container">
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
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between header-maroon">
                <h6 class="m-0 fw-bold text-white">Kiosk Locations</h6>
                <a href="{{ route('kiosks.create') }}" class="btn btn-warning btn-sm text-dark fw-semibold">
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
                                    <tr data-kiosk-id="{{ $kiosk->kiosk_id }}">
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
                                                <small class="text-muted">{{ $kiosk->last_seen->format('M d, Y h:i A') }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('kiosks.edit', $kiosk) }}" class="btn btn-outline-primary btn-sm" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form action="{{ route('kiosks.destroy', $kiosk) }}" method="POST" class="d-inline" 
                                                      onsubmit="return confirm('Are you sure you want to delete this kiosk location?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
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
        let activityChart;
        let isUpdating = false;
        let updateInterval;

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            initializeChart();
            startRealTimeUpdates();
        });

        // Initialize Activity Chart
        function initializeChart() {
            const activityData = @json($analytics['activity_data']);
            const ctx = document.getElementById('activityChart').getContext('2d');
            
            activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: activityData.map(item => item.date),
                    datasets: [{
                        label: 'Active Kiosks',
                        data: activityData.map(item => item.active),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1,
                        fill: true,
                        pointBackgroundColor: 'rgb(75, 192, 192)',
                        pointBorderColor: '#fff',
                        pointHoverRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                callback: function(value) {
                                    return Math.floor(value);
                                }
                            },
                            title: {
                                display: true,
                                text: 'Number of Active Kiosks'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        }
                    },
                    interaction: {
                        mode: 'nearest',
                        axis: 'x',
                        intersect: false
                    }
                }
            });
        }

        // Start real-time updates
        function startRealTimeUpdates() {
            // Update every 30 seconds
            updateInterval = setInterval(updateKioskData, 30000);
            
            // Also add a manual refresh button functionality
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden && !isUpdating) {
                    updateKioskData();
                }
            });
        }

        // Update kiosk data via API
        async function updateKioskData() {
            if (isUpdating) return;
            
            // Show loading indicator
            const indicator = document.getElementById('updateIndicator');
            if (indicator) indicator.style.display = 'inline-block';
            
            isUpdating = true;
            try {
                const response = await fetch('{{ route("kiosks.analytics.api") }}', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.success) {
                    updateAnalyticsCards(data.analytics);
                    updateKioskTable(data.kiosks);
                    updateChart(data.analytics.activity_data);
                    updateTopKiosks(data.analytics.top_kiosks);
                    updateUptimeStats(data.analytics.uptime_stats);
                    
                    // Update last update timestamp
                    const lastUpdateElement = document.getElementById('lastUpdate');
                    if (lastUpdateElement) {
                        const updateTime = new Date().toLocaleTimeString('en-US', {
                            timeZone: 'Asia/Manila',
                            hour12: true,
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });
                        lastUpdateElement.textContent = `Last updated: ${updateTime}`;
                    }
                    
                    console.log('Data updated at:', data.timestamp);
                } else {
                    throw new Error(data.message || 'Unknown error occurred');
                }
            } catch (error) {
                console.error('Error updating kiosk data:', error);
                showNotification('Failed to update data: ' + error.message, 'error');
            } finally {
                isUpdating = false;
                // Hide loading indicator
                if (indicator) indicator.style.display = 'none';
            }
        }

        // Show notification
        function showNotification(message, type = 'info') {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }

        // Update analytics cards
        function updateAnalyticsCards(analytics) {
            // Update card values with animation
            animateValue('total-kiosks', parseInt(document.querySelector('.analytics-total').textContent) || 0, analytics.total);
            animateValue('online-kiosks', parseInt(document.querySelector('.analytics-online').textContent) || 0, analytics.online);
            animateValue('offline-kiosks', parseInt(document.querySelector('.analytics-offline').textContent) || 0, analytics.offline);
            animateValue('active-kiosks', parseInt(document.querySelector('.analytics-active').textContent) || 0, analytics.active);
        }

        // Animate number changes
        function animateValue(elementId, start, end) {
            const element = document.querySelector(`.analytics-${elementId.split('-')[0]}`);
            if (!element) return;
            
            if (start === end) return;
            
            const duration = 1000;
            const range = end - start;
            const startTime = performance.now();
            
            function animate(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const value = Math.floor(start + (range * progress));
                element.textContent = value;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                }
            }
            
            requestAnimationFrame(animate);
        }

        // Update kiosk table
        function updateKioskTable(kiosks) {
            const tbody = document.querySelector('#kiosksTable tbody');
            if (!tbody) return;

            // Store current row states to avoid unnecessary updates
            const currentRows = Array.from(tbody.children);
            
            kiosks.forEach(kiosk => {
                const existingRow = document.querySelector(`tr[data-kiosk-id="${kiosk.kiosk_id}"]`);
                
                if (existingRow) {
                    // Update existing row
                    updateKioskRow(existingRow, kiosk);
                } else {
                    // Add new row (if kiosk was added)
                    location.reload(); // Reload to get proper table structure
                }
            });
        }

        // Update individual kiosk row
        function updateKioskRow(row, kiosk) {
            // Update status badge
            const statusCell = row.children[2];
            const statusBadge = statusCell.querySelector('.badge');
            
            if (statusBadge) {
                if (kiosk.is_active) {
                    if (kiosk.is_online) {
                        statusBadge.className = 'badge bg-success';
                        statusBadge.innerHTML = '<i class="bi bi-wifi me-1"></i>Online';
                    } else {
                        statusBadge.className = 'badge bg-warning';
                        statusBadge.innerHTML = '<i class="bi bi-wifi-off me-1"></i>Offline';
                    }
                } else {
                    statusBadge.className = 'badge bg-secondary';
                    statusBadge.innerHTML = '<i class="bi bi-pause-circle me-1"></i>Inactive';
                }
            }

            // Update last seen
            const lastSeenCell = row.children[3];
            if (lastSeenCell) {
                lastSeenCell.innerHTML = `
                    <div>${kiosk.last_seen_human}</div>
                    ${kiosk.last_seen_formatted ? `<small class="text-muted">${kiosk.last_seen_formatted}</small>` : ''}
                `;
            }
        }

        // Update chart
        function updateChart(activityData) {
            if (activityChart && activityData) {
                activityChart.data.labels = activityData.map(item => item.date);
                activityChart.data.datasets[0].data = activityData.map(item => item.active);
                activityChart.update('none'); // Update without animation for performance
            }
        }

        // Update top kiosks section
        function updateTopKiosks(topKiosks) {
            const container = document.querySelector('.top-kiosks-container');
            if (!container || !topKiosks) return;
            
            if (topKiosks.length === 0) {
                container.innerHTML = '<p class="text-muted text-center">No kiosk activity data available</p>';
                return;
            }
            
            container.innerHTML = topKiosks.map(kiosk => `
                <div class="d-flex align-items-center mb-3">
                    <div class="flex-shrink-0">
                        <span class="badge bg-${kiosk.status === 'online' ? 'success' : (kiosk.status === 'offline' ? 'warning' : 'secondary')} me-2">
                            <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <div class="small fw-bold">${kiosk.location}</div>
                        <div class="text-muted" style="font-size: 0.75rem;">${kiosk.attendance_count} attendances</div>
                    </div>
                    <div class="flex-shrink-0">
                        <small class="text-muted">${kiosk.last_seen}</small>
                    </div>
                </div>
            `).join('');
        }

        // Update uptime statistics
        function updateUptimeStats(uptimeStats) {
            const container = document.querySelector('.uptime-stats-container');
            if (!container || !uptimeStats) return;
            
            container.innerHTML = uptimeStats.map(stat => `
                <div class="col-md-3 mb-3">
                    <div class="card border-0">
                        <div class="card-body text-center">
                            <h5 class="card-title">${stat.location}</h5>
                            <div class="progress mb-2" style="height: 20px;">
                                <div class="progress-bar ${stat.uptime >= 80 ? 'bg-success' : (stat.uptime >= 60 ? 'bg-warning' : 'bg-danger')}" 
                                     role="progressbar" 
                                     style="width: ${stat.uptime}%">
                                    ${stat.uptime}%
                                </div>
                            </div>
                            <small class="text-muted">Uptime (7 days)</small>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        // Manual refresh function
        function manualRefresh() {
            const refreshBtn = document.getElementById('refreshBtn');
            if (refreshBtn && !isUpdating) {
                refreshBtn.disabled = true;
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise spinner-border spinner-border-sm"></i>';
                
                updateKioskData().finally(() => {
                    refreshBtn.disabled = false;
                    refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                });
            }
        }

        // Add manual refresh functionality
        function addRefreshButton() {
            const header = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-4');
            if (header) {
                const refreshBtn = document.createElement('button');
                refreshBtn.className = 'btn btn-outline-secondary btn-sm ms-2';
                refreshBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                refreshBtn.title = 'Refresh Data';
                refreshBtn.onclick = () => {
                    if (!isUpdating) {
                        updateKioskData();
                    }
                };
                
                header.appendChild(refreshBtn);
            }
        }

        // Clean up on page unload
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
@endsection
