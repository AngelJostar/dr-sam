<?php

namespace App\Http\Controllers\InsuranceAdvisor;

use App\Http\Controllers\Controller;
use App\Models\InsurancePolicy;
use App\Models\InsuranceAdvisorNotification;
use App\Models\MedicationDelivery;
use App\Models\Patient;
use App\Models\Treatment;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InsuranceAdvisorController extends Controller
{
    public function index(Request $request): View
    {
        $section = $request->query('section', 'home');
        $allowedSections = ['home', 'all', 'due', 'expired', 'messages', 'alerts', 'claims'];
        if (! in_array($section, $allowedSections, true)) {
            $section = 'home';
        }

        $allPolicies = InsurancePolicy::query()
            ->with(['patient.primaryDoctor'])
            ->orderByRaw("case when status = 'expired' then 0 else 1 end")
            ->orderBy('ends_at')
            ->get();

        $today = now()->startOfDay();
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();
        $insurer = $request->string('insurer')->toString();
        $sort = $request->string('sort', 'expiration')->toString();

        $policyQuery = InsurancePolicy::query()->with(['patient.primaryDoctor', 'patient.user']);

        if ($search !== '') {
            $policyQuery->where(function ($query) use ($search): void {
                $query
                    ->where('policy_number', 'like', "%{$search}%")
                    ->orWhere('insurer_name', 'like', "%{$search}%")
                    ->orWhere('plan_name', 'like', "%{$search}%")
                    ->orWhereHas('patient', fn ($patientQuery) => $patientQuery->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($insurer !== '' && $insurer !== 'all') {
            $policyQuery->where('insurer_name', $insurer);
        }

        if ($status === 'due') {
            $policyQuery->where('status', 'active')
                ->whereBetween('ends_at', [$today, $today->copy()->addDays(60)]);
        } elseif ($status === 'expired') {
            $policyQuery->where(function ($query) use ($today): void {
                $query->where('status', 'expired')->orWhere('ends_at', '<', $today);
            });
        } elseif ($status === 'active') {
            $policyQuery->where('status', 'active')
                ->where(function ($query) use ($today): void {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>', $today->copy()->addDays(60));
                });
        }

        match ($sort) {
            'patient' => $policyQuery->orderBy(
                Patient::query()->select('full_name')->whereColumn('patients.id', 'insurance_policies.patient_id')
            ),
            'recent' => $policyQuery->latest(),
            default => $policyQuery
                ->orderByRaw("case when status = 'expired' or ends_at < ? then 0 else 1 end", [$today])
                ->orderBy('ends_at'),
        };

        $policies = $policyQuery
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
                    ->map(fn (array $claim): array => [
                        'folio' => $claim['folio'],
                        'type' => $claim['type'],
                        'patient' => $policy->patient?->full_name ?? 'Paciente asegurado',
                        'hospital' => $claim['hospital'],
                        'date' => Carbon::parse($claim['event_date'])->format('d M Y'),
                        'policy' => $policy,
                        'diagnosis' => $claim['diagnosis'],
                        'amount' => (float) $claim['estimated_amount'],
                        'status' => $claim['status'] ?? 'Documentacion',
                        'documents' => (int) ($claim['pending_documents'] ?? 6),
                    ])
                    ->all();
            })
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
            'type' => ['required', Rule::in(['reimbursement', 'direct_payment'])],
            'hospital' => ['required', 'string', 'max:180'],
            'event_date' => ['required', 'date'],
            'estimated_amount' => ['required', 'numeric', 'min:0'],
            'diagnosis' => ['required', 'string', 'max:500'],
        ]);

        $policy = InsurancePolicy::query()->findOrFail($data['policy_id']);
        $metadata = $policy->metadata ?? [];
        $claims = collect(data_get($metadata, 'insurance_claims', []));
        $claim = [
            'folio' => 'SIN-'.now()->format('Y').'-'.str_pad((string) (InsurancePolicy::query()->count() + $claims->count() + 1), 3, '0', STR_PAD_LEFT),
            'type' => $data['type'] === 'direct_payment' ? 'Pago directo a hospital' : 'Reembolso',
            'hospital' => $data['hospital'],
            'event_date' => Carbon::parse($data['event_date'])->toDateString(),
            'estimated_amount' => (float) $data['estimated_amount'],
            'diagnosis' => $data['diagnosis'],
            'status' => 'Documentacion',
            'pending_documents' => 6,
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
}
