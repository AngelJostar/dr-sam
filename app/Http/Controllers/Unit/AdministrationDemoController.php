<?php

namespace App\Http\Controllers\Unit;

use App\Http\Controllers\Controller;
use App\Models\MedicalUnit;
use App\Services\Platform\PlatformAuditService;
use App\Support\OperationalUnitContext;
use App\Support\UnitAdministrationDemo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdministrationDemoController extends Controller
{
    public static function publicState(MedicalUnit $unit, array $state): array
    {
        $state['documents'] = array_map(function ($document) use ($unit) {
            unset($document['path']);
            $document['url'] = route('unit.administration-demo.document', ['unit' => $unit->id, 'document' => $document['id']]);
            return $document;
        }, $state['documents']);
        unset($state['operations']);
        return $state;
    }

    public function show(Request $request)
    {
        $unit = OperationalUnitContext::resolve($request);
        abort_unless($unit, 404);
        return response()->json(self::publicState($unit, UnitAdministrationDemo::state($unit)));
    }

    public function store(Request $request, PlatformAuditService $audit)
    {
        $unit = OperationalUnitContext::resolve($request);
        abort_unless($unit, 404);
        $request->validate(['version' => 'required|integer|min:1', 'operation_id' => 'required|uuid', 'action' => 'required|string|max:40']);
        $storedPath = null;
        try {
            $state = DB::transaction(function () use ($request, $unit, $audit, &$storedPath) {
                $locked = MedicalUnit::query()->lockForUpdate()->findOrFail($unit->id);
                $state = UnitAdministrationDemo::state($locked);
                if (in_array($request->input('operation_id'), $state['operations'], true)) return $state;
                abort_unless((int) $request->input('version') === (int) $state['version'], 409, 'La información cambió. Actualiza la pantalla antes de guardar.');
                $data = $request->except(['path', 'mime']);
                if ($request->input('action') === 'document') {
                    $request->validate(['file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', 'kind' => 'required|in:insurers,authorizations', 'id' => 'required|string|max:80', 'name' => 'required|string|max:180']);
                    UnitAdministrationDemo::record($state, $data['kind'], $data['id']);
                    $storedPath = $request->file('file')->store('unit-administration-demo/'.$unit->id, 'local');
                    $data['path'] = $storedPath;
                    $data['mime'] = $request->file('file')->getMimeType();
                }
                $state = UnitAdministrationDemo::apply($state, $request->input('action'), $data);
                $state['operations'] = array_slice([...$state['operations'], $request->input('operation_id')], -100);
                $metadata = $locked->metadata ?? [];
                $metadata['administration_demo'] = $state;
                $locked->update(['metadata' => $metadata]);
                $audit->record($request, 'unit.administration.demo.'.$request->input('action'), $locked, 'unit', ['version' => $state['version']]);
                return $state;
            });
        } catch (\Throwable $exception) {
            if ($storedPath) Storage::disk('local')->delete($storedPath);
            throw $exception;
        }
        return response()->json(['message' => 'Guardado correctamente · Demo', 'state' => self::publicState($unit, $state)]);
    }

    public function document(Request $request, string $document)
    {
        $unit = OperationalUnitContext::resolve($request);
        abort_unless($unit, 404);
        $record = collect(UnitAdministrationDemo::state($unit)['documents'])->firstWhere('id', $document);
        abort_unless($record && Storage::disk('local')->exists($record['path']), 404);
        return Storage::disk('local')->download($record['path'], basename($record['path']), ['Content-Type' => $record['mime'], 'X-Content-Type-Options' => 'nosniff']);
    }

    public function invoice(Request $request, string $invoice, string $format)
    {
        $unit = OperationalUnitContext::resolve($request);
        abort_unless($unit && in_array($format, ['print', 'xml'], true), 404);
        $state = UnitAdministrationDemo::state($unit);
        $record = collect($state['invoices'])->firstWhere('id', $invoice);
        abort_unless($record && $record['status'] === 'issued', 404);
        $account = UnitAdministrationDemo::record($state, 'accounts', $record['account_id']);
        if ($format === 'xml') {
            $xml = new \SimpleXMLElement('<DocumentoDemo/>');
            foreach (['folio' => $record['id'], 'unidad' => $unit->name, 'cuenta' => $account['id'], 'receptor' => $record['receiver'], 'importe' => number_format($record['amount'] / 100, 2, '.', ''), 'moneda' => 'MXN', 'leyenda' => 'DEMOSTRACION - SIN VALIDEZ FISCAL'] as $key => $value) $xml->addChild($key, htmlspecialchars((string) $value, ENT_XML1, 'UTF-8'));
            return response($xml->asXML(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.$record['id'].'-demo.xml"', 'X-Content-Type-Options' => 'nosniff']);
        }
        return view('unit.administration.invoice-print', compact('unit', 'record', 'account'));
    }
}
