<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * OfficeController
 * 
 * Handles API requests for office/department data from local registration stations.
 * Implements RBAC to ensure admins only see their assigned offices.
 */
class OfficeController extends Controller
{
    /**
     * Get list of offices based on authenticated user's role
     * 
     * RBAC Rules:
     * - Super Admin: Returns all offices
     * - Admin: Returns only their assigned office(s)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Get authenticated user from Sanctum token
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please provide a valid authentication token.'
                ], 401);
            }

            // Check if user has a role
            if (!$user->role) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has no role assigned.'
                ], 403);
            }

            $roleName = $user->role->role_name;

            // Super Admin: Get all offices
            if ($roleName === 'super_admin') {
                $offices = Department::select('id', 'department_id', 'department_name')
                    ->orderBy('department_name', 'asc')
                    ->get();
                
                Log::info('Super Admin fetched all offices', [
                    'user_id' => $user->admin_id,
                    'office_count' => $offices->count()
                ]);
            } 
            // Regular Admin: Get only their department(s)
            else if ($roleName === 'admin') {
                $offices = Department::select('id', 'department_id', 'department_name')
                    ->where('id', $user->department_id)
                    ->orWhere('department_id', $user->department_id)
                    ->orderBy('department_name', 'asc')
                    ->get();
                
                Log::info('Admin fetched assigned offices', [
                    'user_id' => $user->admin_id,
                    'department_id' => $user->department_id,
                    'office_count' => $offices->count()
                ]);
            }
            // Other roles: No access
            else {
                return response()->json([
                    'success' => false,
                    'message' => 'Your role does not have access to this resource.'
                ], 403);
            }

            // Check if any offices were found
            if ($offices->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No offices found for your account. Please contact your administrator.',
                    'offices' => []
                ], 200);
            }

            // Return offices
            return response()->json([
                'success' => true,
                'message' => 'Offices retrieved successfully',
                'offices' => $offices,
                'role' => $roleName,
                'user' => [
                    'id' => $user->admin_id,
                    'username' => $user->username,
                    'department_id' => $user->department_id
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch offices', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve offices. Please try again later.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get a specific office by ID
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $office = Department::find($id);

            if (!$office) {
                return response()->json([
                    'success' => false,
                    'message' => 'Office not found'
                ], 404);
            }

            // RBAC check: Regular admins can only view their own office
            $roleName = $user->role->role_name ?? '';
            if ($roleName !== 'super_admin' && $office->id !== $user->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to view this office'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'office' => $office
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to fetch office', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve office',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
