<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Institution;
use App\Models\MedicalUnit;
use App\Models\Patient;
use App\Models\PatientOrder;
use App\Models\ProviderRequest;
use Illuminate\Http\JsonResponse;

class DashboardSummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'institutions' => Institution::query()->count(),
            'medical_units' => MedicalUnit::query()->count(),
            'patients' => Patient::query()->count(),
            'appointments' => Appointment::query()->count(),
            'provider_requests' => ProviderRequest::query()->count(),
            'patient_orders' => PatientOrder::query()->count(),
        ]);
    }
}

