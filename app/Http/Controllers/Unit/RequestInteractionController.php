<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\ProviderRequest;
use App\Models\OperationalProfile;
use App\Models\ProviderRequestInteraction;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RequestInteractionController extends Controller
{
    public function index(Request $request, ProviderRequest $providerRequest): JsonResponse
    {
        $this->authorizeRequest($request, $providerRequest);

        return $this->response($providerRequest, $request);
    }

    public function store(Request $request, ProviderRequest $providerRequest, PlatformAuditService $audit): JsonResponse
    {
        $this->authorizeRequest($request, $providerRequest);
        $data = $request->validate([
            'type' => ['required', Rule::in(['message', 'adjustment'])],
            'body' => ['required', 'string', 'max:3000'],
        ]);

        DB::transaction(function () use ($request, $providerRequest, $data, $audit): void {
            $record = ProviderRequest::query()->lockForUpdate()->findOrFail($providerRequest->id);
            $this->authorizeRequest($request, $record);
            if ($data['type'] === 'adjustment' && ! $this->canRequestAdjustment($record)) {
                throw ValidationException::withMessages(['body' => 'La solicitud ya está cerrada; puedes consultar sus ajustes anteriores.']);
            }

            $interaction = $record->interactions()->create([
                'user_id' => $request->user()->id,
                'type' => $data['type'],
                'body' => $data['body'],
                'status' => $data['type'] === 'adjustment' ? 'open' : null,
            ]);
            $audit->record($request, 'unit.request.'.$data['type'].'.created', $interaction, 'unit', [
                'provider_request_id' => $record->id,
                'medical_unit_id' => $record->medical_unit_id,
            ]);
        });

        return $this->response($providerRequest, $request, 201);
    }

    public function resolve(Request $request, ProviderRequest $providerRequest, ProviderRequestInteraction $interaction, PlatformAuditService $audit): JsonResponse
    {
        $this->authorizeRequest($request, $providerRequest);
        abort_unless((int) $interaction->provider_request_id === (int) $providerRequest->id && $interaction->type === 'adjustment', 404);
        $data = $request->validate(['resolution' => ['required', 'string', 'max:3000']]);

        DB::transaction(function () use ($request, $providerRequest, $interaction, $data, $audit): void {
            $record = ProviderRequest::query()->lockForUpdate()->findOrFail($providerRequest->id);
            $this->authorizeRequest($request, $record);
            $interaction = ProviderRequestInteraction::query()->lockForUpdate()->findOrFail($interaction->id);
            if ($interaction->status !== 'open') {
                throw ValidationException::withMessages(['resolution' => 'Este ajuste ya fue resuelto. Actualiza el historial para consultar la resolución.']);
            }
            $interaction->update([
                'status' => 'resolved',
                'resolution' => $data['resolution'],
                'resolved_by' => $request->user()->id,
                'resolved_at' => now(),
            ]);
            $audit->record($request, 'unit.request.adjustment.resolved', $interaction, 'unit', [
                'provider_request_id' => $providerRequest->id,
                'medical_unit_id' => $providerRequest->medical_unit_id,
            ]);
        });

        return $this->response($providerRequest, $request);
    }

    private function authorizeRequest(Request $request, ProviderRequest $record): void
    {
        abort_unless(in_array($record->request_type, ['npt', 'nutrition', 'chemo', 'chemotherapy'], true), 404);
        $operational = $request->routeIs('operational.mixtures.interactions.*');
        if ($operational) {
            abort_unless(in_array($record->request_type, ['chemo', 'chemotherapy'], true), 404);
        }
        $unit = $record->medicalUnit;
        $user = $request->user();
        $allowed = $unit && (
            in_array($user?->role, ['superadmin', 'admin'], true)
            || ($user?->role === 'unit' && $unit->unit_username === $user->username)
            || ($user?->role === 'institution' && $user->institution && (int) $unit->institution_id === (int) $user->institution->id)
            || ($operational && $user?->role === 'operational' && OperationalProfile::query()
                ->where('user_id', $user->id)->where('medical_unit_id', $unit->id)->where('status', 'active')->exists())
        );
        abort_unless($allowed, 403, 'No puedes consultar solicitudes de otra unidad.');
    }

    private function canRequestAdjustment(ProviderRequest $record): bool
    {
        return ! in_array($record->status, ['delivered', 'completed', 'cancelled', 'rejected'], true);
    }

    private function response(ProviderRequest $record, Request $request, int $status = 200): JsonResponse
    {
        $record->refresh();
        $interactions = $record->interactions()->with(['author:id,name', 'resolver:id,name'])->orderBy('id')->get();

        return response()->json([
            'can_request_adjustment' => $this->canRequestAdjustment($record),
            'message_count' => $interactions->where('type', 'message')->count(),
            'open_adjustment_count' => $interactions->where('type', 'adjustment')->where('status', 'open')->count(),
            'interactions' => $interactions->map(fn ($entry) => [
                'id' => $entry->id,
                'type' => $entry->type,
                'body' => $entry->body,
                'author' => $entry->author?->name ?? 'Usuario no disponible',
                'created_at' => $entry->created_at->format('d/m/Y H:i'),
                'status' => $entry->status,
                'resolution' => $entry->resolution,
                'resolved_by' => $entry->resolver?->name,
                'resolved_at' => $entry->resolved_at?->format('d/m/Y H:i'),
                'resolve_url' => $entry->type === 'adjustment' && $entry->status === 'open'
                    ? route($request->routeIs('operational.mixtures.interactions.*') ? 'operational.mixtures.interactions.resolve' : 'unit.requests.interactions.resolve', [$record, $entry]) : null,
            ]),
        ], $status)->header('Cache-Control', 'no-store, private');
    }
}
