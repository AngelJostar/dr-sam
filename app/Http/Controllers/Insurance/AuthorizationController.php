<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreAuthorizationRequest;
use App\Models\Authorization;
use App\Models\Hospitalization;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\Insurance\InsuranceAuditService;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorizationController extends Controller
{
    public function index(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $authorizations = Authorization::query()
            ->with(['patient', 'treatment', 'hospitalization'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->input('type')))
            ->latest('requested_at')
            ->paginate(20)
            ->withQueryString();

        return view('insurance.authorizations.index', [
            'authorizations' => $authorizations,
            'abilities' => $permissions->abilitiesFor($request->user()),
            'patients' => Patient::query()->orderBy('full_name')->limit(200)->get(),
            'treatments' => Treatment::query()->with('patient')->latest()->limit(200)->get(),
            'hospitalizations' => Hospitalization::query()->with('patient')->latest('admitted_at')->limit(200)->get(),
        ]);
    }

    public function store(StoreAuthorizationRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $authorization = Authorization::query()->create($data + [
            'authorized_amount' => $data['authorized_amount'] ?? 0,
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.authorization.created', $authorization, [
            'patient_id' => $authorization->patient_id,
        ]);

        return back()->with('status', 'Solicitud de autorizacion registrada.');
    }
}
