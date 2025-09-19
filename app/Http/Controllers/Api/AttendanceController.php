<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class AttendanceController extends Controller
{
    public function getLogs(Request $request)
    {
        $query = AttendanceLog::with(['employee.department']);

        // Apply same filters as main attendance page
        if ($request->filled('start_date')) {
            $query->whereDate('time_in', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('time_in', '<=', $request->end_date);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }
        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }
        if ($request->filled('login_method')) {
            $query->where('method', $request->login_method);
        }
        if ($request->filled('status')) {
            if ($request->status === 'late') {
                $query->whereTime('time_in', '>', '08:00:00');
            } elseif ($request->status === 'on_time') {
                $query->whereTime('time_in', '<=', '08:00:00');
            }
        }

        // Department restriction for non-super admins
        if (auth()->user()->role->role_name !== 'super_admin' && auth()->user()->department_id) {
            $query->whereHas('employee', function ($q) {
                $q->where('department_id', auth()->user()->department_id);
            });
        }

        $logs = $query->latest('time_in')->paginate(20);

        if ($request->wantsJson()) {
            return response()->json([
                'html' => view('components.attendance-table-rows', ['attendanceLogs' => $logs])->render(),
                'pagination' => $logs->onEachSide(1)->links('pagination::bootstrap-5')->render(),
                'total' => $logs->total(),
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage()
            ]);
        }

        return $logs;
    }
}