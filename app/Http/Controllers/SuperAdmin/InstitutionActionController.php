<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Models\MedicationCatalogItem;
use App\Models\PlatformModule;
use App\Models\Service;
use App\Models\User;
use App\Services\Platform\PlatformAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InstitutionActionController extends Controller
{
    public function status(Request $request, Institution $institution, PlatformAuditService $audit): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);

        DB::transaction(function () use ($request, $institution, $data, $audit): void {
            $institution->update($data);
            $institution->owner?->update(['status' => $data['status']]);
            $audit->record($request, 'superadmin.institution.status.updated', $institution, 'superadmin', $data);
        });

        $action = $data['status'] === 'active' ? 'desbloqueada' : 'bloqueada';

        return back()->with('status', "Institución {$institution->name} {$action}.");
    }

    public function report(Institution $institution): StreamedResponse
    {
        $institution->load('owner')->loadCount(['medicalUnits', 'services']);
        $row = [
            $institution->name,
            $institution->legal_name,
            ['publica' => 'Pública', 'privada' => 'Privada', 'institution' => 'Institución', 'institucion' => 'Institución'][$institution->type] ?? $institution->type,
            $institution->status === 'active' ? 'Activo' : 'Inactivo',
            $institution->medical_units_count,
            $institution->services_count,
            $institution->owner ? 1 : 0,
            PlatformModule::query()->where('enabled', true)->count(),
        ];

        return response()->streamDownload(function () use ($row): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Institución', 'Razón social', 'Tipo', 'Estatus', 'Hospitales', 'Servicios', 'Usuarios', 'Módulos activos']);
            fputcsv($output, array_map(
                fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r\n]/', $value) ? "'".$value : $value,
                $row,
            ));
            fclose($output);
        }, 'institucion-'.$institution->id.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function destroy(Request $request, Institution $institution, PlatformAuditService $audit): RedirectResponse
    {
        $request->validate(['confirm_delete' => ['accepted']]);

        DB::transaction(function () use ($request, $institution, $audit): void {
            $institution = Institution::query()->lockForUpdate()->findOrFail($institution->id);
            $hasLinkedRecords = $institution->medicalUnits()->exists()
                || $institution->services()->exists()
                || MedicationCatalogItem::query()->where('institution_id', $institution->id)->exists()
                || Service::query()->whereJsonContains('metadata->institution_ids', $institution->id)
                    ->orWhereJsonContains('metadata->institution_ids', (string) $institution->id)->exists()
                || User::query()->where('metadata->institution_id', $institution->id)->exists();

            if ($hasLinkedRecords) {
                throw ValidationException::withMessages([
                    'institution' => 'Esta institución tiene hospitales, servicios, medicamentos o usuarios vinculados. Reasigna esos registros antes de eliminarla.',
                ]);
            }

            $audit->record($request, 'superadmin.institution.deleted', $institution, 'superadmin', ['name' => $institution->name]);
            $owner = $institution->owner;
            $institution->delete();

            if ($owner?->role === 'institution' && ! Institution::query()->where('owner_user_id', $owner->id)->exists()) {
                $owner->update(['status' => 'inactive']);
            }
        });

        return redirect()->route('superadmin.catalog', ['section' => 'institutions'])
            ->with('status', "Institución {$institution->name} eliminada.");
    }
}
