<?php

namespace App\Http\Controllers\InsuranceAdvisor;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\InsuranceAdvisorNotification;
use App\Models\InsurancePolicy;
use App\Models\Hospitalization;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\Provider;
use App\Models\ProviderRequest;
use App\Models\Treatment;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InsuranceAdvisorController extends Controller
{
    private const QUOTATION_SERVICES = [
        'clinical_labs' => 'Analisis clinicos',
        'chemotherapy' => 'Quimioterapia',
        'home_care' => 'Home care',
    ];

    public function index(Request $request): View
    {
        $section = $request->query('section', 'home');
        $allowedSections = ['home', 'all', 'due', 'expired', 'messages', 'alerts', 'claims'];
        if (! in_array($section, $allowedSections, true)) {
            $section = 'home';
        }

        $allPolicies = InsurancePolicy::query()
            ->with(['patient.primaryDoctor', 'patient.user'])
            ->orderByRaw("case when status = 'expired' then 0 else 1 end")
            ->orderBy('ends_at')
            ->get();

        $today = now()->startOfDay();
        $policies = $this->filteredPolicyQuery($request)
            ->paginate(10)
            ->withQueryString();

        $dueSoonPolicies = $allPolicies
            ->filter(fn (InsurancePolicy $policy) => $policy->ends_at && $policy->ends_at->isFuture() && $today->diffInDays($policy->ends_at, false) <= 60)
            ->values();
        $expiredPolicies = $allPolicies
            ->filter(fn (InsurancePolicy $policy) => $policy->status === 'expired' || ($policy->ends_at && $policy->ends_at->isPast()))
            ->values();
        $activePolicies = $allPolicies
            ->filter(fn (InsurancePolicy $policy) => $policy->status === 'active'
                && (! $policy->ends_at || $today->diffInDays($policy->ends_at, false) > 60))
            ->values();
        $selectedPolicyId = $request->integer('policy');
        $selectedPolicy = $allPolicies->firstWhere('id', $selectedPolicyId)
            ?? $dueSoonPolicies->first()
            ?? $activePolicies->first()
            ?? $allPolicies->first();
        $policyMessages = InsuranceAdvisorNotification::query()
            ->with('policy.patient')
            ->where('type', 'message')
            ->latest('occurred_at')
            ->get()
            ->map(fn (InsuranceAdvisorNotification $notification): array => [
                'sent_at' => $notification->occurred_at->format('d M Y, h:i a'),
                'patient' => $notification->policy?->patient?->full_name ?? 'Paciente asegurado',
                'subject' => $notification->subject,
                'body' => $notification->body,
            ]);
        $policyAlerts = InsuranceAdvisorNotification::query()
            ->where('type', 'alert')
            ->latest('occurred_at')
            ->get()
            ->map(fn (InsuranceAdvisorNotification $notification): array => [
                'created_at' => $notification->occurred_at->format('d M Y, h:i a'),
                'subject' => $notification->subject,
                'body' => $notification->body,
            ]);
        $storedClaims = $allPolicies
            ->flatMap(function (InsurancePolicy $policy): array {
                return collect(data_get($policy->metadata, 'insurance_claims', []))
                    ->map(function (array $claim) use ($policy): array {
                        $claim = $this->normalizeClaim($claim);

                        return [
                            ...$claim,
                            'patient' => $policy->patient?->full_name ?? 'Paciente asegurado',
                            'date' => filled($claim['event_date']) ? Carbon::parse($claim['event_date'])->format('d M Y') : 'Sin fecha',
                            'policy' => $policy,
                            'amount' => (float) $claim['estimated_amount'],
                            'documents_pending' => $this->pendingClaimDocuments($claim),
                        ];
                    })
                    ->all();
            })
            ->sortByDesc('created_at')
            ->values();
        $claims = $storedClaims;

        return view('insurance_advisor.dashboard', [
            'section' => $section,
            'allPolicies' => $allPolicies,
            'policies' => $policies,
            'dueSoonPolicies' => $dueSoonPolicies,
            'expiredPolicies' => $expiredPolicies,
            'activePolicies' => $activePolicies,
            'policyMessages' => $policyMessages,
            'policyAlerts' => $policyAlerts,
            'selectedPolicy' => $selectedPolicy,
            'insurers' => $allPolicies->pluck('insurer_name')->filter()->unique()->sort()->values(),
            'claims' => $claims,
            'quotationServices' => self::QUOTATION_SERVICES,
            'policyAssistantData' => $policies->getCollection()->mapWithKeys(function (InsurancePolicy $policy) use ($storedClaims): array {
                $policyClaims = $storedClaims->where('policy.id', $policy->id);

                return [(string) $policy->id => [
                    'id' => $policy->id,
                    'policy_number' => $policy->policy_number,
                    'patient' => $policy->patient?->full_name ?? 'Paciente asegurado',
                    'insurer' => $policy->insurer_name,
                    'product' => $policy->plan_name ?? 'GMM Hospitalario',
                    'starts_at' => $policy->starts_at?->format('d M Y') ?? 'Sin fecha',
                    'ends_at' => $policy->ends_at?->format('d M Y') ?? 'Sin fecha',
                    'status' => $policy->status,
                    'premium' => (float) data_get($policy->metadata, 'premium', 31750),
                    'deductible' => (float) data_get($policy->metadata, 'deductible', 18000),
                    'coinsurance' => data_get($policy->metadata, 'coinsurance', '10%'),
                    'payment_status' => data_get($policy->metadata, 'payment_status', 'Pendiente de renovacion'),
                    'sync_status' => data_get($policy->metadata, 'doctor_sync.status', 'not_synced'),
                    'claims' => $policyClaims->map(fn (array $claim): array => [
                        'folio' => $claim['folio'],
                        'status' => $claim['status'],
                        'stage' => $claim['stage'],
                        'hospital' => $claim['hospital'],
                        'documents_pending' => $claim['documents_pending'],
                    ])->values()->all(),
                ]];
            }),
            'treatments' => Treatment::query()
                ->with(['patient', 'medication'])
                ->latest()
                ->limit(10)
                ->get(),
            'deliveries' => MedicationDelivery::query()
                ->with(['patient', 'provider'])
                ->latest('scheduled_delivery_date')
                ->limit(10)
                ->get(),
            'metrics' => [
                'Polizas activas' => InsurancePolicy::query()->where('status', 'active')->count(),
                'Pacientes activos' => Patient::query()->where('status', 'active')->count(),
                'Alto riesgo' => Patient::query()->whereIn('risk_level', ['high', 'critical'])->count(),
                'Tratamientos activos' => Treatment::query()->where('status', 'active')->count(),
                'Entregas pendientes' => MedicationDelivery::query()->whereIn('status', ['pending', 'in_route', 'rescheduled'])->count(),
            ],
        ]);
    }

    public function exportPolicies(Request $request): StreamedResponse
    {
        $policies = $this->filteredPolicyQuery($request)->get();
        $filename = 'polizas-gmm-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($policies): void {
            $output = fopen('php://output', 'wb');
            echo "\xEF\xBB\xBF";
            fputcsv($output, [
                'Poliza',
                'Asegurado',
                'Usuario plataforma',
                'Aseguradora',
                'Producto',
                'Inicio vigencia',
                'Fin vigencia',
                'Prima',
                'Estatus',
                'Sincronizacion',
            ]);

            foreach ($policies as $policy) {
                fputcsv($output, [
                    $policy->policy_number,
                    $policy->patient?->full_name,
                    $policy->patient?->platform_number,
                    $policy->insurer_name,
                    $policy->plan_name,
                    $policy->starts_at?->format('Y-m-d'),
                    $policy->ends_at?->format('Y-m-d'),
                    (float) data_get($policy->metadata, 'premium', 0),
                    $policy->status,
                    $this->policySyncLabel(data_get($policy->metadata, 'doctor_sync.status', 'not_synced')),
                ]);
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function requestPolicySync(
        Request $request,
        InsurancePolicy $policy,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $this->queuePolicySync($request, $policy, $audit);

        return back()->with('status', "Solicitud de sincronizacion enviada para {$policy->policy_number}.");
    }

    public function bulkPolicySync(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'target' => ['required', Rule::in(['no', 'pending', 'both'])],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:40'],
            'insurer' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:40'],
        ]);

        $policies = $this->filteredPolicyQuery($request)->get()->filter(function (InsurancePolicy $policy) use ($data): bool {
            $syncStatus = data_get($policy->metadata, 'doctor_sync.status', 'not_synced');

            return match ($data['target']) {
                'no' => $syncStatus === 'not_synced',
                'pending' => $syncStatus === 'pending',
                default => in_array($syncStatus, ['not_synced', 'pending'], true),
            };
        });

        $policies->each(fn (InsurancePolicy $policy) => $this->queuePolicySync($request, $policy, $audit));

        return back()->with('status', $policies->isEmpty()
            ? 'No hay polizas que requieran sincronizacion en la seleccion actual.'
            : $policies->count().' solicitudes de sincronizacion enviadas.');
    }

    public function syncAlerts(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $today = now()->startOfDay();
        $created = 0;

        InsurancePolicy::query()
            ->with('patient')
            ->whereNotNull('ends_at')
            ->where(function ($query) use ($today): void {
                $query->where('ends_at', '<=', $today->copy()->addDays(60))->orWhere('status', 'expired');
            })
            ->get()
            ->each(function (InsurancePolicy $policy) use ($today, &$created): void {
                $expired = $policy->status === 'expired' || $policy->ends_at->lt($today);
                $days = abs((int) $policy->ends_at->diffInDays($today));
                $periodKey = $policy->ends_at->format('Y-m-d');
                $patient = $policy->patient?->full_name ?? 'Paciente asegurado';

                $records = [
                    [
                        'type' => 'alert',
                        'audience' => 'advisor',
                        'subject' => $expired ? 'Póliza vencida' : 'Póliza por vencer',
                        'body' => "{$patient} / {$policy->policy_number}: ".($expired ? "Vencida hace {$days} días." : "Vence en {$days} días."),
                    ],
                    [
                        'type' => 'message',
                        'audience' => 'insured',
                        'subject' => $expired ? 'Póliza vencida' : 'Renovación próxima',
                        'body' => $expired
                            ? "Tu póliza {$policy->policy_number} venció el {$policy->ends_at->format('d M Y')}. El asesor puede apoyarte con la reactivación."
                            : "Tu póliza {$policy->policy_number} vence el {$policy->ends_at->format('d M Y')}. Puedes enviar el comprobante de pago para renovar tu cobertura.",
                    ],
                ];

                foreach ($records as $record) {
                    $notification = InsuranceAdvisorNotification::query()->firstOrCreate(
                        ['code' => "policy-{$policy->id}-{$periodKey}-{$record['type']}"],
                        array_merge($record, [
                            'insurance_policy_id' => $policy->id,
                            'occurred_at' => now(),
                            'metadata' => ['policy_status' => $policy->status, 'ends_at' => $periodKey],
                        ]),
                    );
                    $created += $notification->wasRecentlyCreated ? 1 : 0;
                }
            });

        if ($notification = InsuranceAdvisorNotification::query()->latest('occurred_at')->first()) {
            $audit->record($request, 'insurance_advisor.alerts.synchronized', $notification, 'insurance_advisor', ['created' => $created]);
        }

        return back()->with('status', $created > 0 ? "Se generaron {$created} notificaciones." : 'Las alertas ya estaban sincronizadas.');
    }

    public function sendPolicyMessage(Request $request, InsurancePolicy $policy, PlatformAuditService $audit): RedirectResponse
    {
        $policy->loadMissing('patient');
        $data = $request->validate([
            'subject' => ['nullable', 'string', 'max:180'],
            'body' => ['nullable', 'string', 'max:1200'],
        ]);
        $subject = $data['subject'] ?? "Seguimiento de p\u{00F3}liza";
        $body = $data['body'] ?? "Te contactamos para dar seguimiento a tu p\u{00F3}liza {$policy->policy_number}. Tu asesor puede ayudarte con la renovaci\u{00F3}n y documentaci\u{00F3}n pendiente.";
        $notification = InsuranceAdvisorNotification::query()->create([
            'insurance_policy_id' => $policy->id,
            'code' => 'advisor-message-'.$policy->id.'-'.now()->format('YmdHisv'),
            'type' => 'message',
            'audience' => 'insured',
            'subject' => $subject,
            'body' => $body,
            'occurred_at' => now(),
            'metadata' => ['sent_by' => $request->user()?->id],
        ]);
        $audit->record($request, 'insurance_advisor.message.sent', $notification, 'insurance_advisor', ['policy_id' => $policy->id]);

        return redirect()->route('insurance-advisor.dashboard', ['section' => 'messages'])->with('status', 'Mensaje registrado y enviado al historial.');
    }

    public function updatePolicyStatus(Request $request, InsurancePolicy $policy, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'inactive', 'expired', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $metadata = $policy->metadata ?? [];
        $metadata['advisor_note'] = $data['notes'] ?? null;
        $metadata['advisor_updated_by'] = $request->user()?->id;
        $metadata['advisor_updated_at'] = now()->toISOString();

        $policy->update([
            'status' => $data['status'],
            'metadata' => $metadata,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.policy.updated', $policy, 'insurance_advisor', [
            'policy_number' => $policy->policy_number,
            'status' => $policy->status,
        ]);

        return back()->with('status', "Poliza {$policy->policy_number} actualizada.");
    }

    public function renewPolicy(Request $request, InsurancePolicy $policy, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'payment_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'amount' => ['required', 'numeric', 'min:0'],
            'paid_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $today = now()->startOfDay();
        $previousStart = $policy->starts_at?->toDateString();
        $previousEnd = $policy->ends_at?->toDateString();
        $nextStart = $policy->ends_at && $policy->ends_at->greaterThanOrEqualTo($today)
            ? $policy->ends_at->copy()->addDay()
            : $today;
        $nextEnd = $nextStart->copy()->addYear()->subDay();
        $proofPath = $request->file('payment_file')->store("insurance-renewals/{$policy->id}");

        $metadata = $policy->metadata ?? [];
        $history = collect(data_get($metadata, 'renewal_history', []));
        $renewal = [
            'paid_at' => Carbon::parse($data['paid_at'])->toDateString(),
            'amount' => (float) $data['amount'],
            'file_path' => $proofPath,
            'file_name' => $request->file('payment_file')->getClientOriginalName(),
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'new_start' => $nextStart->toDateString(),
            'new_end' => $nextEnd->toDateString(),
            'notes' => $data['notes'] ?? null,
            'registered_by' => $request->user()?->id,
            'registered_at' => now()->toISOString(),
        ];

        $metadata['payment_status'] = 'Pagada';
        $metadata['last_payment'] = $renewal['paid_at'];
        $metadata['renewal_history'] = $history->prepend($renewal)->values()->all();

        $policy->update([
            'starts_at' => $nextStart,
            'ends_at' => $nextEnd,
            'status' => 'active',
            'metadata' => $metadata,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.policy.renewed', $policy, 'insurance_advisor', [
            'policy_number' => $policy->policy_number,
            'amount' => $renewal['amount'],
            'paid_at' => $renewal['paid_at'],
            'new_start' => $renewal['new_start'],
            'new_end' => $renewal['new_end'],
        ]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['policy' => $policy->id])
            ->with('status', "Pago registrado y nuevo periodo generado para {$policy->policy_number}.");
    }

    public function storeClaim(Request $request, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validate([
            'policy_id' => ['required', 'integer', 'exists:insurance_policies,id'],
            'type' => ['required', Rule::in(['reimbursement', 'direct_payment', 'hospital_discharge'])],
            'hospital' => ['required', 'string', 'max:180'],
            'event_date' => ['required', 'date'],
            'estimated_amount' => ['required', 'numeric', 'min:0'],
            'diagnosis' => ['required', 'string', 'max:500'],
        ]);

        $policy = InsurancePolicy::query()->findOrFail($data['policy_id']);
        $metadata = $policy->metadata ?? [];
        $claims = collect(data_get($metadata, 'insurance_claims', []));
        $typeKey = $this->canonicalClaimType($data['type']);
        $claim = [
            'folio' => $this->nextClaimFolio(),
            'type' => $this->claimTypeLabel($typeKey),
            'type_key' => $typeKey,
            'hospital' => $data['hospital'],
            'event_date' => Carbon::parse($data['event_date'])->toDateString(),
            'estimated_amount' => (float) $data['estimated_amount'],
            'diagnosis' => $data['diagnosis'],
            'status' => 'Documentacion',
            'stage' => 'Documentacion',
            'documents' => $this->claimDocumentDefinitions($typeKey),
            'pending_documents' => count($this->claimDocumentDefinitions($typeKey)),
            'quotations' => [],
            'notes' => [[
                'at' => now()->toISOString(),
                'text' => 'Siniestro creado por el asesor.',
            ]],
            'created_by' => $request->user()?->id,
            'created_at' => now()->toISOString(),
        ];
        $metadata['insurance_claims'] = $claims->prepend($claim)->values()->all();

        $policy->update([
            'metadata' => $metadata,
            'updated_by' => $request->user()?->id,
        ]);

        $audit->record($request, 'insurance.claim.created', $policy, 'insurance_advisor', [
            'folio' => $claim['folio'],
            'policy_number' => $policy->policy_number,
        ]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['section' => 'claims'])
            ->with('status', "Siniestro {$claim['folio']} creado.");
    }

    public function uploadClaimDocument(
        Request $request,
        InsurancePolicy $policy,
        string $claim,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'document_key' => ['required', 'string', 'max:120'],
            'document_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'max:15360'],
        ]);
        [$metadata, $claims, $claimIndex, $storedClaim] = $this->claimContext($policy, $claim);
        $documentIndex = collect($storedClaim['documents'])->search(
            fn (array $document): bool => hash_equals((string) $document['key'], $data['document_key'])
        );
        abort_if($documentIndex === false, 404);

        $file = $request->file('document_file');
        $path = $file->store("insurance-claims/{$policy->id}/{$claim}");
        $documentDefinition = $storedClaim['documents'][$documentIndex];
        $document = Document::query()->create([
            'patient_id' => $policy->patient_id,
            'hospitalization_id' => $storedClaim['hospitalization_id'] ?? null,
            'name' => $documentDefinition['name'],
            'document_type' => 'insurance_claim',
            'file_path' => $path,
            'file_mime' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()?->id,
            'loaded_at' => now(),
            'status' => 'current',
            'metadata' => [
                'insurance_policy_id' => $policy->id,
                'claim_folio' => $claim,
                'document_key' => $data['document_key'],
            ],
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        $storedClaim['documents'][$documentIndex] = [
            ...$documentDefinition,
            'status' => 'received',
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'document_id' => $document->id,
            'uploaded_at' => now()->toISOString(),
        ];
        $storedClaim['pending_documents'] = $this->pendingClaimDocuments($storedClaim);
        $storedClaim['notes'] = $this->prependClaimNote(
            $storedClaim,
            "Documento cargado: {$documentDefinition['name']} ({$file->getClientOriginalName()}).",
        );
        $this->persistClaim($policy, $metadata, $claims, $claimIndex, $storedClaim, $request->user()?->id);
        $audit->record($request, 'insurance.claim.document.uploaded', $document, 'insurance_advisor', [
            'claim_folio' => $claim,
            'policy_number' => $policy->policy_number,
        ]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $claim])
            ->with('status', "Documento agregado al siniestro {$claim}.");
    }

    public function requestClaimDocuments(
        Request $request,
        InsurancePolicy $policy,
        string $claim,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $policy->loadMissing('patient');
        [$metadata, $claims, $claimIndex, $storedClaim] = $this->claimContext($policy, $claim);
        $missingDocuments = collect($storedClaim['documents'])
            ->reject(fn (array $document): bool => $this->claimDocumentReceived($document))
            ->where('required', true)
            ->pluck('name')
            ->values();

        if ($missingDocuments->isEmpty()) {
            return back()->with('status', "El siniestro {$claim} no tiene documentos pendientes.");
        }

        $notification = InsuranceAdvisorNotification::query()->create([
            'insurance_policy_id' => $policy->id,
            'code' => 'claim-documents-'.Str::uuid(),
            'type' => 'message',
            'audience' => 'insured',
            'subject' => 'Documentos pendientes para siniestro',
            'body' => "Para continuar con {$claim}, faltan: ".$missingDocuments->join(', ').'.',
            'occurred_at' => now(),
            'metadata' => ['claim_folio' => $claim, 'documents' => $missingDocuments->all()],
        ]);
        $storedClaim['notes'] = $this->prependClaimNote($storedClaim, 'Se solicitaron documentos pendientes al asegurado.');
        $this->persistClaim($policy, $metadata, $claims, $claimIndex, $storedClaim, $request->user()?->id);
        $audit->record($request, 'insurance.claim.documents.requested', $notification, 'insurance_advisor', [
            'claim_folio' => $claim,
        ]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $claim])
            ->with('status', 'Solicitud de documentos enviada al asegurado.');
    }

    public function processClaimDischarge(
        Request $request,
        InsurancePolicy $policy,
        string $claim,
        PlatformAuditService $audit,
    ): RedirectResponse {
        [$metadata, $claims, $claimIndex, $storedClaim] = $this->claimContext($policy, $claim);
        $storedClaim['stage'] = 'Alta hospitalaria';
        $storedClaim['status'] = 'Alta hospitalaria en tramite';
        $storedClaim['notes'] = $this->prependClaimNote($storedClaim, 'Se preparo el tramite de alta hospitalaria y pase de salida.');
        $this->persistClaim($policy, $metadata, $claims, $claimIndex, $storedClaim, $request->user()?->id);
        $audit->record($request, 'insurance.claim.discharge.started', $policy, 'insurance_advisor', ['claim_folio' => $claim]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $claim])
            ->with('status', 'Alta hospitalaria marcada en tramite.');
    }

    public function storeClaimFollowUp(
        Request $request,
        InsurancePolicy $policy,
        string $claim,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $data = $request->validate(['note' => ['required', 'string', 'max:1200']]);
        [$metadata, $claims, $claimIndex, $storedClaim] = $this->claimContext($policy, $claim);
        $storedClaim['notes'] = $this->prependClaimNote($storedClaim, $data['note']);
        $this->persistClaim($policy, $metadata, $claims, $claimIndex, $storedClaim, $request->user()?->id);
        $audit->record($request, 'insurance.claim.follow_up.created', $policy, 'insurance_advisor', ['claim_folio' => $claim]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $claim])
            ->with('status', "Seguimiento agregado a {$claim}.");
    }

    public function sendClaimToInsurer(
        Request $request,
        InsurancePolicy $policy,
        string $claim,
        PlatformAuditService $audit,
    ): RedirectResponse {
        [$metadata, $claims, $claimIndex, $storedClaim] = $this->claimContext($policy, $claim);
        $missingDocuments = collect($storedClaim['documents'])
            ->reject(fn (array $document): bool => $this->claimDocumentReceived($document))
            ->where('required', true)
            ->pluck('name')
            ->values();
        $hospitalization = null;

        if ($policy->patient_id) {
            $hospitalization = Hospitalization::query()
                ->where('patient_id', $policy->patient_id)
                ->get()
                ->first(fn (Hospitalization $record): bool => data_get($record->metadata, 'insurance_claim_folio') === $claim);
            $hospitalizationData = [
                'patient_id' => $policy->patient_id,
                'doctor_id' => $policy->patient?->primary_doctor_id,
                'hospital_name' => $storedClaim['hospital'],
                'admitted_at' => $storedClaim['event_date'],
                'reason' => $storedClaim['diagnosis'],
                'admission_diagnosis' => $storedClaim['diagnosis'],
                'area' => $storedClaim['type'],
                'event_type' => $storedClaim['type_key'],
                'status' => 'in_review',
                'authorized_amount' => (float) $storedClaim['estimated_amount'],
                'administrative_notes' => $missingDocuments->isEmpty()
                    ? 'Expediente recibido con documentacion completa.'
                    : $missingDocuments->count().' documentos pendientes: '.$missingDocuments->join(', '),
                'metadata' => [
                    'source' => 'insurance_advisor',
                    'insurance_policy_id' => $policy->id,
                    'insurance_claim_folio' => $claim,
                    'insurer_name' => $policy->insurer_name,
                    'missing_documents' => $missingDocuments->all(),
                ],
                'updated_by' => $request->user()?->id,
            ];
            if ($hospitalization) {
                $hospitalization->update($hospitalizationData);
            } else {
                $hospitalization = Hospitalization::query()->create($hospitalizationData + [
                    'created_by' => $request->user()?->id,
                ]);
            }
        }

        $storedClaim['stage'] = 'En revision aseguradora';
        $storedClaim['status'] = 'Enviado a aseguradora';
        $storedClaim['hospitalization_id'] = $hospitalization?->id;
        $summary = $missingDocuments->isEmpty()
            ? 'con documentos completos'
            : 'con '.$missingDocuments->count().' documentos pendientes: '.$missingDocuments->join(', ');
        $storedClaim['notes'] = $this->prependClaimNote(
            $storedClaim,
            "Expediente enviado a {$policy->insurer_name} {$summary}.",
        );
        $this->persistClaim($policy, $metadata, $claims, $claimIndex, $storedClaim, $request->user()?->id);
        $audit->record($request, 'insurance.claim.sent_to_insurer', $hospitalization ?? $policy, 'insurance_advisor', [
            'claim_folio' => $claim,
            'missing_documents' => $missingDocuments->all(),
        ]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $claim])
            ->with('status', $missingDocuments->isEmpty()
                ? 'Expediente enviado a la aseguradora con documentos completos.'
                : 'Expediente enviado a la aseguradora con documentos pendientes.');
    }

    public function storeClaimQuotation(
        Request $request,
        InsurancePolicy $policy,
        string $claim,
        PlatformAuditService $audit,
    ): RedirectResponse {
        $data = $request->validate([
            'service' => ['required', Rule::in(array_keys(self::QUOTATION_SERVICES))],
            'institution' => ['required', 'string', 'max:180'],
            'unit' => ['required', 'string', 'max:180'],
            'prescription_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
            'clinical_summary_file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
        ]);
        [$metadata, $claims, $claimIndex, $storedClaim] = $this->claimContext($policy, $claim);
        $basePath = "insurance-claims/{$policy->id}/{$claim}/quotations";
        $prescriptionPath = $request->file('prescription_file')->store($basePath);
        $summaryPath = $request->file('clinical_summary_file')->store($basePath);
        $providerTypes = match ($data['service']) {
            'clinical_labs' => ['clinical_labs', 'clinical-labs', 'laboratory'],
            'chemotherapy' => ['chemotherapy', 'chemo'],
            default => ['home_care', 'home-care'],
        };
        $provider = Provider::query()->whereIn('provider_type', $providerTypes)->where('status', 'active')->first();
        $quotationId = 'COT-'.now()->format('YmdHisv');
        $providerRequest = ProviderRequest::query()->create([
            'provider_id' => $provider?->id,
            'patient_id' => $policy->patient_id,
            'external_id' => $quotationId,
            'request_type' => $data['service'],
            'status' => 'requested',
            'requested_at' => now(),
            'payload' => [
                'source' => 'insurance_advisor',
                'insurance_policy_id' => $policy->id,
                'claim_folio' => $claim,
                'service_label' => self::QUOTATION_SERVICES[$data['service']],
                'institution' => $data['institution'],
                'unit' => $data['unit'],
                'prescription_file_path' => $prescriptionPath,
                'clinical_summary_file_path' => $summaryPath,
            ],
        ]);
        $quotation = [
            'id' => $quotationId,
            'provider_request_id' => $providerRequest->id,
            'service' => $data['service'],
            'service_label' => self::QUOTATION_SERVICES[$data['service']],
            'institution' => $data['institution'],
            'unit' => $data['unit'],
            'prescription_file_name' => $request->file('prescription_file')->getClientOriginalName(),
            'prescription_file_path' => $prescriptionPath,
            'clinical_summary_file_name' => $request->file('clinical_summary_file')->getClientOriginalName(),
            'clinical_summary_file_path' => $summaryPath,
            'status' => 'Solicitada',
            'created_at' => now()->toISOString(),
        ];
        $storedClaim['quotations'] = collect($storedClaim['quotations'] ?? [])->prepend($quotation)->values()->all();
        $storedClaim['notes'] = $this->prependClaimNote(
            $storedClaim,
            'Cotizacion solicitada: '.$quotation['service_label'].' / '.$data['institution'].' / '.$data['unit'].'.',
        );
        $this->persistClaim($policy, $metadata, $claims, $claimIndex, $storedClaim, $request->user()?->id);
        $audit->record($request, 'insurance.claim.quotation.requested', $providerRequest, 'insurance_advisor', [
            'claim_folio' => $claim,
            'quotation_id' => $quotationId,
        ]);

        return redirect()
            ->route('insurance-advisor.dashboard', ['section' => 'claims', 'claim' => $claim])
            ->with('status', 'Solicitud de cotizacion guardada y enviada al proveedor.');
    }

    private function filteredPolicyQuery(Request $request): Builder
    {
        $today = now()->startOfDay();
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $insurer = $request->string('insurer')->toString();
        $sort = $request->string('sort', 'expiration')->toString();
        $query = InsurancePolicy::query()->with(['patient.primaryDoctor', 'patient.user']);

        if ($search !== '') {
            $query->where(function (Builder $policyQuery) use ($search): void {
                $policyQuery
                    ->where('policy_number', 'like', "%{$search}%")
                    ->orWhere('insurer_name', 'like', "%{$search}%")
                    ->orWhere('plan_name', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn (Builder $patientQuery) => $patientQuery->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($insurer !== '' && $insurer !== 'all') {
            $query->where('insurer_name', $insurer);
        }

        if ($status === 'due') {
            $query->where('status', 'active')
                ->whereBetween('ends_at', [$today, $today->copy()->addDays(60)]);
        } elseif ($status === 'expired') {
            $query->where(function (Builder $policyQuery) use ($today): void {
                $policyQuery->where('status', 'expired')->orWhere('ends_at', '<', $today);
            });
        } elseif ($status === 'active') {
            $query->where('status', 'active')
                ->where(function (Builder $policyQuery) use ($today): void {
                    $policyQuery->whereNull('ends_at')->orWhere('ends_at', '>', $today->copy()->addDays(60));
                });
        }

        match ($sort) {
            'patient' => $query->orderBy(
                Patient::query()->select('full_name')->whereColumn('patients.id', 'insurance_policies.patient_id')
            ),
            'recent' => $query->latest(),
            default => $query
                ->orderByRaw("case when status = 'expired' or ends_at < ? then 0 else 1 end", [$today])
                ->orderBy('ends_at'),
        };

        return $query;
    }

    private function queuePolicySync(
        Request $request,
        InsurancePolicy $policy,
        PlatformAuditService $audit,
    ): void {
        $policy->loadMissing('patient.primaryDoctor');
        $metadata = $policy->metadata ?? [];
        $previous = data_get($metadata, 'doctor_sync', []);
        $attempt = (int) data_get($previous, 'attempts', 0) + 1;
        $historyEntry = [
            'attempt' => $attempt,
            'requested_at' => now()->toISOString(),
            'requested_by' => $request->user()?->id,
            'doctor_id' => $policy->patient?->primary_doctor_id,
            'doctor_name' => $policy->patient?->primaryDoctor?->full_name,
        ];
        $metadata['doctor_sync'] = [
            ...$previous,
            'status' => 'pending',
            'attempts' => $attempt,
            'requested_at' => data_get($previous, 'requested_at', $historyEntry['requested_at']),
            'updated_at' => $historyEntry['requested_at'],
            'requested_by' => $historyEntry['requested_by'],
            'doctor_id' => $historyEntry['doctor_id'],
            'doctor_name' => $historyEntry['doctor_name'],
            'history' => collect(data_get($previous, 'history', []))->prepend($historyEntry)->take(20)->values()->all(),
        ];
        $policy->update([
            'metadata' => $metadata,
            'updated_by' => $request->user()?->id,
        ]);
        InsuranceAdvisorNotification::query()->create([
            'insurance_policy_id' => $policy->id,
            'code' => 'policy-sync-'.Str::uuid(),
            'type' => 'sync',
            'audience' => 'doctor',
            'subject' => 'Solicitud de sincronizacion de poliza',
            'body' => "Se solicita validar y enlazar la poliza {$policy->policy_number} con el expediente de ".($policy->patient?->full_name ?? 'Paciente asegurado').'.',
            'occurred_at' => now(),
            'metadata' => $historyEntry,
        ]);
        $audit->record($request, 'insurance.policy.sync_requested', $policy, 'insurance_advisor', [
            'policy_number' => $policy->policy_number,
            'attempt' => $attempt,
            'doctor_id' => $historyEntry['doctor_id'],
        ]);
    }

    private function policySyncLabel(?string $status): string
    {
        return match ($status) {
            'approved', 'synced' => 'Si',
            'pending' => 'Pendiente',
            default => 'No',
        };
    }

    private function nextClaimFolio(): string
    {
        $year = now()->format('Y');
        $folios = InsurancePolicy::query()
            ->get(['metadata'])
            ->flatMap(fn (InsurancePolicy $policy) => collect(data_get($policy->metadata, 'insurance_claims', []))->pluck('folio'))
            ->filter();
        $next = $folios
            ->filter(fn (string $folio): bool => str_starts_with($folio, "SIN-{$year}-"))
            ->map(fn (string $folio): int => (int) Str::afterLast($folio, '-'))
            ->max() + 1;

        return "SIN-{$year}-".str_pad((string) max(1, $next), 3, '0', STR_PAD_LEFT);
    }

    private function canonicalClaimType(?string $type): string
    {
        $value = Str::lower(Str::ascii((string) $type));

        if (in_array($value, ['direct_payment', 'hospital_payment'], true) || str_contains($value, 'pago directo')) {
            return 'direct_payment';
        }

        if ($value === 'hospital_discharge' || str_contains($value, 'alta hospitalaria')) {
            return 'hospital_discharge';
        }

        return 'reimbursement';
    }

    private function claimTypeLabel(string $type): string
    {
        return match ($this->canonicalClaimType($type)) {
            'direct_payment' => 'Pago directo a hospital',
            'hospital_discharge' => 'Alta hospitalaria',
            default => 'Reembolso',
        };
    }

    private function claimDocumentDefinitions(string $type): array
    {
        $names = match ($this->canonicalClaimType($type)) {
            'direct_payment' => [
                'Identificacion oficial',
                'Informe medico',
                'Admision hospitalaria',
                'Presupuesto hospitalario',
                'Carta de autorizacion',
                'Resumen clinico',
            ],
            'hospital_discharge' => [
                'Identificacion oficial',
                'Resumen clinico',
                'Indicaciones de egreso',
                'Pase de salida',
                'Carta de cobertura',
                'Estado de cuenta hospitalario',
            ],
            default => [
                'Identificacion oficial',
                'Informe medico',
                'Facturas CFDI',
                'Comprobantes de pago',
                'Estado de cuenta',
                'Solicitud de reembolso',
            ],
        };

        return collect($names)->map(fn (string $name): array => [
            'key' => Str::slug($name),
            'name' => $name,
            'required' => true,
            'status' => 'pending',
            'file_name' => null,
            'file_path' => null,
        ])->all();
    }

    private function normalizeClaim(array $claim): array
    {
        $typeKey = $this->canonicalClaimType($claim['type_key'] ?? $claim['type'] ?? 'reimbursement');
        $documents = collect($claim['documents'] ?? $this->claimDocumentDefinitions($typeKey))
            ->map(function (array $document): array {
                $received = $this->claimDocumentReceived($document);
                $name = $document['name'] ?? 'Documento';

                return [
                    ...$document,
                    'key' => $document['key'] ?? Str::slug($name),
                    'name' => $name,
                    'required' => (bool) ($document['required'] ?? true),
                    'status' => $received ? 'received' : 'pending',
                    'file_name' => $document['file_name'] ?? null,
                    'file_path' => $document['file_path'] ?? null,
                ];
            })
            ->values()
            ->all();
        $normalized = [
            ...$claim,
            'folio' => $claim['folio'] ?? 'SIN-SIN-FOLIO',
            'type_key' => $typeKey,
            'type' => $this->claimTypeLabel($typeKey),
            'hospital' => $claim['hospital'] ?? 'Sin hospital',
            'event_date' => $claim['event_date'] ?? null,
            'diagnosis' => $claim['diagnosis'] ?? 'Sin diagnostico',
            'estimated_amount' => (float) ($claim['estimated_amount'] ?? 0),
            'status' => $claim['status'] ?? 'Documentacion',
            'stage' => $claim['stage'] ?? $claim['status'] ?? 'Documentacion',
            'documents' => $documents,
            'quotations' => collect($claim['quotations'] ?? [])->values()->all(),
            'notes' => collect($claim['notes'] ?? [])->values()->all(),
            'created_at' => $claim['created_at'] ?? $claim['event_date'] ?? now()->toISOString(),
        ];
        $normalized['pending_documents'] = $this->pendingClaimDocuments($normalized);

        return $normalized;
    }

    private function claimDocumentReceived(array $document): bool
    {
        return filled($document['file_path'] ?? null)
            || filled($document['file_name'] ?? null)
            || ($document['status'] ?? null) === 'received';
    }

    private function pendingClaimDocuments(array $claim): int
    {
        return collect($claim['documents'] ?? [])
            ->filter(fn (array $document): bool => (bool) ($document['required'] ?? true) && ! $this->claimDocumentReceived($document))
            ->count();
    }

    private function claimContext(InsurancePolicy $policy, string $folio): array
    {
        $metadata = $policy->metadata ?? [];
        $claims = collect(data_get($metadata, 'insurance_claims', []))->values();
        $index = $claims->search(fn (array $claim): bool => ($claim['folio'] ?? null) === $folio);
        abort_if($index === false, 404);

        return [$metadata, $claims, $index, $this->normalizeClaim((array) $claims[$index])];
    }

    private function persistClaim(
        InsurancePolicy $policy,
        array $metadata,
        Collection $claims,
        int $index,
        array $claim,
        ?int $userId,
    ): void {
        $claim['pending_documents'] = $this->pendingClaimDocuments($claim);
        $claims->put($index, $claim);
        $metadata['insurance_claims'] = $claims->values()->all();
        $policy->update(['metadata' => $metadata, 'updated_by' => $userId]);
    }

    private function prependClaimNote(array $claim, string $note): array
    {
        return collect($claim['notes'] ?? [])->prepend([
            'at' => now()->toISOString(),
            'text' => $note,
        ])->take(100)->values()->all();
    }
}
