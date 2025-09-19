<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceLog;
use App\Models\Kiosk;
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
            $chartLogQuery->whereHas('employee', function ($q) {
                $q->where('department_id', auth()->user()->department_id);
            });
        }
        $rfidCount = (clone $chartLogQuery)->where('method', 'rfid')->count();
        $fpCount = (clone $chartLogQuery)->where('method', 'fingerprint')->count();
        return response()->json([
            'rfidCount' => $rfidCount,
            'fpCount' => $fpCount
        ]);
    }

    /**
     * Get the count of active kiosks
     * 
     * @return int
     */
    public function getActiveKioskCount()
    {
        return Kiosk::where('is_active', 1)->count();
    }

    /**
     * Get detailed kiosk status information
     * 
     * @return array
     */
    public function getKioskStatusData()
    {
        $totalKiosks = Kiosk::count();
        $activeKiosks = Kiosk::where('is_active', 1)->count();
        $inactiveKiosks = $totalKiosks - $activeKiosks;

        // Kiosks that haven't been seen in the last 5 minutes (considered offline)
        $fiveMinutesAgo = Carbon::now()->subMinutes(5);
        $onlineKiosks = Kiosk::where('is_active', 1)
            ->where('last_seen', '>=', $fiveMinutesAgo)
            ->count();

        $offlineKiosks = $activeKiosks - $onlineKiosks;

        return [
            'total_kiosks' => $totalKiosks,
            'active_kiosks' => $activeKiosks,
            'inactive_kiosks' => $inactiveKiosks,
            'online_kiosks' => $onlineKiosks,
            'offline_kiosks' => $offlineKiosks,
            'kiosk_uptime_percentage' => $activeKiosks > 0 ? round(($onlineKiosks / $activeKiosks) * 100, 1) : 0
        ];
    }

    /**
     * API endpoint to get kiosk status data
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function kioskStatusApi()
    {
        return response()->json($this->getKioskStatusData());
    }
}
