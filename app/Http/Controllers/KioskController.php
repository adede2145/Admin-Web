<?php

namespace App\Http\Controllers;

use App\Models\Kiosk;
use Illuminate\Http\Request;
use Carbon\Carbon;

class KioskController extends Controller
{
    public function __construct()
    {
        // Ensure only super admins can access kiosk management
        $this->middleware('superadmin');
    }

    public function index()
    {
        $kiosks = Kiosk::orderBy('kiosk_id')->get();
        
        // Calculate analytics data
        $analytics = $this->getKioskAnalytics();
        
        return view('admin.kiosks.index', compact('kiosks', 'analytics'));
    }

    public function create()
    {
        return view('admin.kiosks.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'location' => 'required|string|max:100',
        ]);

        Kiosk::create([
            'location' => $validated['location'],
            'is_active' => true,
            'last_seen' => null
        ]);

        return redirect()->route('kiosks.index')
            ->with('success', 'Kiosk location created successfully.');
    }

    public function edit(Kiosk $kiosk)
    {
        return view('admin.kiosks.edit', compact('kiosk'));
    }

    public function update(Request $request, Kiosk $kiosk)
    {
        $validated = $request->validate([
            'location' => 'required|string|max:100',
        ]);

        $kiosk->update($validated);

        return redirect()->route('kiosks.index')
            ->with('success', 'Kiosk location updated successfully.');
    }

    public function destroy(Kiosk $kiosk)
    {
        $kiosk->delete();

        return redirect()->route('kiosks.index')
            ->with('success', 'Kiosk location deleted successfully.');
    }

    /**
     * API endpoint for real-time kiosk analytics
     */
    public function getAnalyticsApi()
    {
        try {
            $analytics = $this->getKioskAnalytics();
            
            // Also get updated kiosk list with current status
            $kiosks = Kiosk::orderBy('kiosk_id')->get()->map(function ($kiosk) {
                return [
                    'kiosk_id' => $kiosk->kiosk_id,
                    'location' => $kiosk->location,
                    'is_active' => $kiosk->is_active,
                    'status' => $kiosk->status,
                    'is_online' => $kiosk->isOnline(),
                    'last_seen_human' => $kiosk->last_seen_human,
                    'last_seen_formatted' => $kiosk->last_seen ? $kiosk->last_seen->format('M d, Y h:i A') : null,
                ];
            });
            
            return response()->json([
                'success' => true,
                'analytics' => $analytics,
                'kiosks' => $kiosks,
                'timestamp' => now('Asia/Manila')->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch analytics data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getKioskAnalytics()
    {
        $totalKiosks = Kiosk::count();
        $activeKiosks = Kiosk::where('is_active', true)->count();
        $inactiveKiosks = $totalKiosks - $activeKiosks;
        
        // Online kiosks (seen within last 5 minutes) - use Manila timezone
        $onlineKiosks = Kiosk::online()->count();
        
        // Offline kiosks (active but not seen recently)
        $offlineKiosks = Kiosk::offline()->count();
        
        // Kiosk activity over last 30 days - fix timezone handling
        $activityData = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = Carbon::now('Asia/Manila')->subDays($i);
            
            // Count kiosks that were active on this day (had heartbeat)
            $activeCount = Kiosk::where('is_active', true)
                ->whereDate('last_seen', $date->format('Y-m-d'))
                ->count();
            
            $activityData[] = [
                'date' => $date->format('M d'),
                'active' => $activeCount
            ];
        }
        
        // Top performing kiosks by attendance logs
        $topKiosks = Kiosk::withCount(['attendanceLogs' => function ($query) {
                $query->where('time_in', '>=', Carbon::now('Asia/Manila')->subDays(30));
            }])
            ->orderBy('attendance_logs_count', 'desc')
            ->take(5)
            ->get()
            ->map(function ($kiosk) {
                return [
                    'id' => $kiosk->kiosk_id,
                    'location' => $kiosk->location,
                    'attendance_count' => $kiosk->attendance_logs_count,
                    'status' => $kiosk->status,
                    'last_seen' => $kiosk->last_seen_human
                ];
            });
        
        // Uptime statistics - improved calculation
        $uptimeStats = [];
        foreach (Kiosk::where('is_active', true)->get() as $kiosk) {
            $uptimePercentage = $this->calculateUptimePercentage($kiosk);
            $uptimeStats[] = [
                'id' => $kiosk->kiosk_id,
                'location' => $kiosk->location,
                'uptime' => $uptimePercentage
            ];
        }
        
        return [
            'total' => $totalKiosks,
            'active' => $activeKiosks,
            'inactive' => $inactiveKiosks,
            'online' => $onlineKiosks,
            'offline' => $offlineKiosks,
            'activity_data' => $activityData,
            'top_kiosks' => $topKiosks,
            'uptime_stats' => $uptimeStats
        ];
    }
    
    private function calculateUptimePercentage($kiosk)
    {
        if (!$kiosk->is_active) {
            return 0;
        }
        
        // Calculate uptime based on last_seen activity over last 7 days
        $sevenDaysAgo = Carbon::now('Asia/Manila')->subDays(7);
        $logs = $kiosk->attendanceLogs()
            ->where('time_in', '>=', $sevenDaysAgo)
            ->get();
        
        if ($logs->isEmpty()) {
            return $kiosk->last_seen && $kiosk->last_seen >= $sevenDaysAgo ? 50 : 0;
        }
        
        // Simple uptime calculation based on activity
        $totalDays = 7;
        $activeDays = $logs->groupBy(function ($log) {
            return $log->time_in->format('Y-m-d');
        })->count();
        
        return round(($activeDays / $totalDays) * 100);
    }
}
