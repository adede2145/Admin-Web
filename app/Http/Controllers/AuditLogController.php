<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $admin = auth()->user();
        
        $query = AuditLog::with('admin')
            ->forAdmin($admin) // Apply RBAC
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $auditLogs = $query->paginate(50);
        
        // Get admins based on user role for filter dropdown
        if ($admin->role->role_name === 'super_admin') {
            $admins = \App\Models\Admin::all();
        } else {
            $admins = \App\Models\Admin::where('department_id', $admin->department_id)->get();
        }
        
        return view('audit.index', compact('auditLogs', 'admins'));
    }

    public function show($id)
    {
        $admin = auth()->user();
        $log = AuditLog::with('admin')->forAdmin($admin)->findOrFail($id);
        
        // Mark as read by current admin
        $log->markAsReadBy($admin->admin_id);
        
        return view('audit.show', compact('log'));
    }

    // Mark audit logs as read when visiting the index page
    public function markAsRead(Request $request)
    {
        $admin = auth()->user();
        
        // Get unread audit logs for this admin
        $unreadLogs = AuditLog::forAdmin($admin)
            ->where('created_at', '>=', now()->subDays(7)) // Only recent logs
            ->get()
            ->filter(function ($log) use ($admin) {
                return $log->isUnreadBy($admin->admin_id);
            });

        // Mark them as read
        foreach ($unreadLogs as $log) {
            $log->markAsReadBy($admin->admin_id);
        }

        return response()->json(['success' => true, 'marked_count' => $unreadLogs->count()]);
    }
}