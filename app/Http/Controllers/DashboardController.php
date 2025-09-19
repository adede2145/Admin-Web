<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function pieChartData(Request $request)
    {
        $period = $request->query('period', 'today');
        $today = Carbon::today();
        $start = $today->copy();
        $end = $today->copy();
        if ($period === 'week') {
            $start = $today->copy()->subDays(6);
        }
        if ($period === 'month') {
            $start = $today->copy()->startOfMonth();
        }
        $chartLogQuery = AttendanceLog::query()
            ->whereBetween('time_in', [$start->startOfDay(), $end->endOfDay()]);
        if (auth()->user()->role->role_name !== 'super_admin') {
            $chartLogQuery->whereHas('employee', function($q){
                $q->where('department_id', auth()->user()->department_id);
            });
        }
        $rfidCount = (clone $chartLogQuery)->where('method','rfid')->count();
        $fpCount = (clone $chartLogQuery)->where('method','fingerprint')->count();
        return response()->json([
            'rfidCount' => $rfidCount,
            'fpCount' => $fpCount
        ]);
    }
}
