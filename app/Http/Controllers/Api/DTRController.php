<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DTRReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class DTRController extends Controller
{
    public function getReports(Request $request)
    {
        $reports = DTRReport::with(['department', 'admin'])
            ->orderBy('created_at', 'desc')
            ->take(20)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'html' => View::make('components.dtr-reports', ['reports' => $reports])->render(),
                'data' => $reports
            ]);
        }

        return $reports;
    }
}