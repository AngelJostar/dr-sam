<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Services\Insurance\InsurancePermissionService;
use App\Services\Insurance\InsuranceReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, InsuranceReportService $reports, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        return view('insurance.reports.index', [
            'reports' => $reports->build($request),
            'filters' => $request->only(['period_from', 'period_to', 'status']),
            'abilities' => $permissions->abilitiesFor($request->user()),
        ]);
    }
}
