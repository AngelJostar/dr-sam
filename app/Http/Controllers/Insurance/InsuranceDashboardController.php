<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Services\Insurance\InsuranceDashboardService;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsuranceDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        InsuranceDashboardService $dashboard,
        InsurancePermissionService $permissions,
    ): View {
        $permissions->assert($request->user(), 'view');

        return view('insurance.dashboard', [
            'metrics' => $dashboard->metrics(),
            'alerts' => $dashboard->alerts()->take(18),
            'patientsByDiagnosis' => $dashboard->patientsByDiagnosis(),
            'abilities' => $permissions->abilitiesFor($request->user()),
        ]);
    }
}
