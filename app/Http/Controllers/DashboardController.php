<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Institution;
use App\Models\Invoice;
use App\Models\MedicalUnit;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\ProviderRequest;
use App\Services\Platform\DashboardRegistry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardRegistry $registry): View
    {
        return view('dashboard', [
            'user' => $request->user(),
            'modules' => $registry->modulesFor($request->user()),
            'summary' => [
                'Instituciones' => Institution::query()->count(),
                'Unidades' => MedicalUnit::query()->count(),
                'Pacientes' => Patient::query()->count(),
                'Citas' => Appointment::query()->count(),
                'Solicitudes proveedor' => ProviderRequest::query()->count(),
                'Pedidos farmacia' => PatientOrder::query()->count(),
                'Entregas aseguradora' => MedicationDelivery::query()->count(),
                'Facturas hospitalarias' => Invoice::query()->count(),
            ],
        ]);
    }
}
