<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreHospitalizationDailyNoteRequest;
use App\Models\HospitalizationDailyNote;
use App\Services\Insurance\InsuranceAuditService;
use Illuminate\Http\RedirectResponse;

class HospitalizationDailyNoteController extends Controller
{
    public function store(StoreHospitalizationDailyNoteRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $note = HospitalizationDailyNote::query()->create($data + [
            'captured_by' => $request->user()?->id,
            'prolonged_stay_risk' => (bool) ($data['prolonged_stay_risk'] ?? false),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.hospitalization_note.created', $note, [
            'hospitalization_id' => $note->hospitalization_id,
        ]);

        return redirect()->route('insurance.hospitalizations.show', $note->hospitalization_id)->with('status', 'Nota diaria agregada.');
    }
}
