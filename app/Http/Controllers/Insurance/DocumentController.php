<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Insurance\StoreDocumentRequest;
use App\Models\Authorization;
use App\Models\Document;
use App\Models\Hospitalization;
use App\Models\Invoice;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\Insurance\InsuranceAuditService;
use App\Services\Insurance\InsurancePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentController extends Controller
{
    public function index(Request $request, InsurancePermissionService $permissions): View
    {
        $permissions->assert($request->user(), 'view');

        $documents = Document::query()
            ->with(['patient', 'uploadedBy'])
            ->when($request->filled('type'), fn ($query) => $query->where('document_type', $request->input('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->latest('loaded_at')
            ->paginate(20)
            ->withQueryString();

        return view('insurance.documents.index', [
            'documents' => $documents,
            'abilities' => $permissions->abilitiesFor($request->user()),
            'patients' => Patient::query()->orderBy('full_name')->limit(200)->get(),
            'treatments' => Treatment::query()->with('patient')->latest()->limit(200)->get(),
            'deliveries' => MedicationDelivery::query()->with('patient')->latest()->limit(200)->get(),
            'hospitalizations' => Hospitalization::query()->with('patient')->latest()->limit(200)->get(),
            'authorizations' => Authorization::query()->with('patient')->latest()->limit(200)->get(),
            'invoices' => Invoice::query()->latest()->limit(200)->get(),
        ]);
    }

    public function store(StoreDocumentRequest $request, InsuranceAuditService $audit): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('document_file');

        if ($file) {
            $data['file_path'] = $file->store('insurance-documents');
            $data['file_mime'] = $file->getClientMimeType();
            $data['file_size'] = $file->getSize();
        }

        unset($data['document_file']);

        $document = Document::query()->create($data + [
            'uploaded_by' => $request->user()?->id,
            'loaded_at' => now(),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.document.created', $document, [
            'patient_id' => $document->patient_id,
            'document_type' => $document->document_type,
        ]);

        return back()->with('status', 'Documento registrado.');
    }
}
