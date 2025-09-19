<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Services\DTRService;
use Illuminate\Http\Request;
use PDF;

class DTRController extends Controller
{
    protected $dtrService;
    
    public function __construct(DTRService $dtrService)
    {
        $this->dtrService = $dtrService;
        $this->middleware(['auth', 'admin.access']);
    }
    
    public function index()
    {
        $employees = Employee::orderBy('last_name')->get();
        return view('dtr.index', compact('employees'));
    }
    
    public function generate(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'year' => 'required|integer|min:2000|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);
        
        $employee = Employee::findOrFail($request->employee_id);
        $dtrData = $this->dtrService->generateMonthlyDTR(
            $employee,
            $request->year,
            $request->month
        );
        
        if ($request->type === 'pdf') {
            $pdf = PDF::loadView('dtr.pdf', $dtrData);
            return $pdf->download("DTR-{$employee->id}-{$request->year}-{$request->month}.pdf");
        }
        
        return view('dtr.preview', $dtrData);
    }
}
