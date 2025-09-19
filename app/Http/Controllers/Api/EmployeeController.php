<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class EmployeeController extends Controller
{
    public function getList(Request $request)
    {
        $employees = Employee::with(['department'])
            ->orderBy('last_name')
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'html' => View::make('components.employee-list', ['employees' => $employees])->render(),
                'data' => $employees
            ]);
        }

        return $employees;
    }
}