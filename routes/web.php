<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DTRController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\KioskController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Leave form download route (accessible without auth)
Route::get('/download/leave-form', function () {
    $filePath = base_path('Leave/LEAVE-FORM-FORM-6.xls');
    
    if (!file_exists($filePath)) {
        abort(404, 'Leave form file not found');
    }
    
    return response()->download($filePath, 'LEAVE-FORM-FORM-6.xls');
})->name('leave.form.download');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard/piechart-data', [App\Http\Controllers\DashboardController::class, 'pieChartData'])->middleware(['auth', 'verified']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Kiosk API Routes
Route::prefix('api/kiosk')->group(function () {
    Route::post('/verify/fingerprint', [AttendanceController::class, 'verifyFingerprint']);
    Route::post('/verify/rfid', [AttendanceController::class, 'verifyRFID']);
    Route::post('/attendance', [AttendanceController::class, 'store']);
    Route::post('/heartbeat', [AttendanceController::class, 'heartbeat']);
});

// API Routes for auto-refresh
Route::middleware(['auth'])->prefix('api')->group(function () {
    Route::get('/attendance/logs', [App\Http\Controllers\Api\AttendanceController::class, 'getLogs'])->name('api.attendance.logs');
    Route::get('/attendance/{id}/verification-data', [AttendanceController::class, 'getVerificationData'])->name('api.attendance.verification-data');
    
    // Token generation for local registration (uses web session auth)
    Route::post('/generate-token', [App\Http\Controllers\Api\RegistrationTokenController::class, 'generateToken'])->name('api.generate-token');
});

// Admin Routes
Route::middleware(['auth'])->group(function () {
    // Debug token generation page
    Route::get('/debug-token', function () {
        return view('debug-token');
    })->name('debug.token');
    
// Debug token flow (complete step-by-step debugging)
Route::get('/debug-token-flow', function () {
    return view('debug-token-flow');
})->middleware(['auth']);

// Simple token test
Route::get('/test-token-simple', function () {
    return view('test-token-simple');
})->middleware(['auth']);
    // Attendance Management
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/dtr', [AttendanceController::class, 'generateDTR'])->name('attendance.dtr');

    // DTR Reports
    Route::get('/dtr/history', [AttendanceController::class, 'dtrHistory'])->name('dtr.history');
    Route::get('/dtr/history/modal', [App\Http\Controllers\AttendanceController::class, 'dtrHistoryModal'])->name('dtr.history.modal');
    Route::get('/dtr/{id}', [AttendanceController::class, 'dtrDetails'])->name('dtr.details');
    Route::delete('/dtr/{id}', [AttendanceController::class, 'deleteDTR'])->name('dtr.delete');

    // Advanced Reporting
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    // Restore custom report generate/export endpoints
    Route::post('/reports/generate', [ReportController::class, 'generateCustomReport'])->name('reports.generate');
    Route::get('/reports/export/{format}', [ReportController::class, 'exportReport'])->name('reports.export');
    Route::get('/reports/saved', [ReportController::class, 'savedReports'])->name('reports.saved');
    Route::post('/reports/schedule', [ReportController::class, 'scheduleReport'])->name('reports.schedule');

    // DTR Overrides (non-destructive leave overlay)
    Route::post('/dtr-overrides', [\App\Http\Controllers\DTROverrideController::class, 'store'])->name('dtr.override.store');
    Route::delete('/dtr-overrides', [\App\Http\Controllers\DTROverrideController::class, 'destroy'])->name('dtr.override.destroy');
    
    // DTR Head Officer Overrides
    Route::post('/dtr-head-officer', [\App\Http\Controllers\DTROverrideController::class, 'storeHeadOfficer'])->name('dtr.head-officer.store');
    Route::delete('/dtr-head-officer', [\App\Http\Controllers\DTROverrideController::class, 'destroyHeadOfficer'])->name('dtr.head-officer.destroy');

    // Employee Management
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/{id}/summary', [EmployeeController::class, 'getSummary'])->name('employees.summary');
    Route::get('/employees/{id}/photo', [EmployeeController::class, 'photo'])->name('employees.photo');
    Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

    // Fingerprint editing (Admin or Super Admin only)
    Route::middleware(['admin_or_superadmin'])->group(function () {
        Route::get('/employees/{id}/fingerprints/edit', [EmployeeController::class, 'editFingerprints'])->name('employees.fingerprints.edit');
        Route::put('/employees/{id}/fingerprints', [EmployeeController::class, 'updateFingerprints'])->name('employees.fingerprints.update');
    });

    // Attendance CRUD operations
    Route::put('/attendance/{id}', [AttendanceController::class, 'update'])->name('attendance.update');
    Route::delete('/attendance/{id}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
    Route::post('/attendance/time-in-out', [AttendanceController::class, 'timeInOut'])->name('attendance.time-in-out');
    Route::get('/attendance/{id}/photo', [AttendanceController::class, 'showPhoto'])->name('attendance.photo');
    // Diagnostic helper for photo integrity
    Route::get('/attendance/{id}/photo-info', [AttendanceController::class, 'photoInfo'])->name('attendance.photo.info');

    // RFID Verification routes
    Route::post('/attendance/{id}/approve', [AttendanceController::class, 'approveRfid'])->name('attendance.approve');
    Route::post('/attendance/{id}/reject', [AttendanceController::class, 'rejectRfid'])->name('attendance.reject');

    // Register Employee (Admin or Super Admin only)
    Route::middleware(['admin_or_superadmin'])->group(function () {
        Route::get('/employees/register', function () {
            return view('employees.register');
        })->name('employees.register');
        Route::post('/employees/register', [EmployeeController::class, 'store'])->name('employees.store');
    });

    // Audit Logs (accessible to both admins and superadmins)
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('/audit-logs/{id}', [AuditLogController::class, 'show'])->name('audit.show');
    Route::post('/audit-logs/mark-read', [AuditLogController::class, 'markAsRead'])->name('audit.mark-read');
});

// DTR Download Route (accessible to all authenticated users)
Route::middleware(['auth'])->group(function () {
    Route::get('/dtr/{id}/download/{format?}', [AttendanceController::class, 'downloadDTR'])->name('dtr.download');
});

// Superadmin Only Panel
Route::middleware(['auth', 'superadmin'])->group(function () {
    // Superadmin dashboard with admin management
    Route::get('/admin-panel', [AdminController::class, 'index'])->name('admin.panel');

    // Manage Admin Users
    Route::post('/admin/users', [AdminController::class, 'store'])->name('admin.users.store');
    Route::put('/admin/users/{id}', [AdminController::class, 'update'])->name('admin.users.update');
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    
    // API endpoint for employee filtering
    Route::get('/api/employees/by-department', [AdminController::class, 'getEmployeesByDepartment'])->name('api.employees.by_department');

    // Department Management
    Route::resource('departments', DepartmentController::class);

    // Kiosk Management
    Route::resource('kiosks', KioskController::class);
    Route::get('/kiosks-analytics-api', [KioskController::class, 'getAnalyticsApi'])->name('kiosks.analytics.api');

    // Admin creation stats for line graph
    Route::get('/admin-panel/creation-stats', [AdminController::class, 'creationStats'])->name('admin.creationStats');
});

require __DIR__ . '/auth.php';
