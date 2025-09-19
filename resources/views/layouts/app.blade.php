<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Attendance Auto Refresh Script -->
        <script>
            function refreshAttendanceLogs() {
                const logsTable = document.querySelector('#attendance-logs-table tbody');
                if (!logsTable) return;

                // Get CSRF token
                const token = document.querySelector('meta[name="csrf-token"]').content;
                
                fetch('/api/attendance-logs', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': token
                    }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    if (data.html) {
                        logsTable.innerHTML = data.html;
                    }
                })
                .catch(error => {
                    console.error('Auto-refresh failed:', error);
                });
            }

                        // Initialize attendance logs auto-refresh when DOM is loaded
            document.addEventListener('DOMContentLoaded', () => {
                const logsTable = document.querySelector('#attendance-logs-table');
                if (logsTable) {
                    // Initial refresh
                    refreshAttendanceLogs();
                    // Set up periodic refresh every 30 seconds
                    setInterval(refreshAttendanceLogs, 30000);
                }
            });
            }
        </script>
    </head>
    <body class="bg-light" style="background:#eceff3;">
        <div class="min-vh-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="container py-3">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="container py-4">
                @yield('content')
            </main>
        </div>

        <!-- Bootstrap JS Bundle -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>
