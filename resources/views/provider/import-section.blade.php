@php
  // All source records already respect the provider scope in the controller.
  $schemas = [
    'Formatos Editables' => ['Paciente', 'Expediente', 'Medicamento', 'Formato', 'Acción'],
    'Resumen' => ['Folio', 'Paciente', 'Unidad', 'Estado', 'Fecha'],
    'Prioridades' => ['Folio', 'Paciente', 'Unidad', 'Estado', 'Fecha requerida'],
    'Solicitudes' => ['Folio', 'Paciente', 'Unidad', 'Estado', 'Fecha'],
    'Seguimiento' => ['Folio', 'Paciente', 'Unidad', 'Estado', 'Última actualización'],
    'Indicadores' => ['Indicador', 'Total', 'Periodo'],
    'Expedientes' => ['Folio', 'Paciente', 'Unidad', 'Estado', 'Fecha'],
    'Pacientes' => ['Paciente', 'Unidad', 'Solicitudes', 'Estado'],
    'Recetas' => ['Receta', 'Paciente', 'Partidas', 'Estado', 'Fecha'],
    'Documentos' => ['Documento', 'Folio', 'Paciente', 'Estado'],
    'Historial clínico' => ['Folio', 'Paciente', 'Diagnóstico', 'Estado', 'Fecha'],
    'Hospitales' => ['Institución', 'Hospital', 'Solicitudes', 'Estado'],
    'Unidades' => ['Unidad', 'Institución', 'Solicitudes', 'Estado'],
    'Contactos' => ['Nombre', 'Hospital', 'Teléfono', 'Correo', 'Estado'],
    'Convenios' => ['Convenio', 'Hospital', 'Vigencia', 'Estado'],
    'Solicitudes hospitalarias' => ['Folio', 'Paciente', 'Unidad', 'Estado', 'Fecha'],
    'Proveedores' => ['Proveedor', 'Tipo', 'Solicitudes', 'Estado'],
    'Medicamentos' => ['Medicamento', 'Presentación', 'Cantidad', 'Unidad', 'Estado'],
    'Cotizaciones' => ['Folio', 'Proveedor', 'Importe', 'Moneda', 'Estado'],
    'Órdenes de compra' => ['Orden', 'Proveedor', 'Importe', 'Fecha', 'Estado'],
    'Documentación' => ['Documento', 'Proveedor', 'Vigencia', 'Estado'],
    'Permisos Cofepris' => ['Permiso', 'Medicamento', 'Vigencia', 'Estado'],
    'Trámites' => ['Trámite', 'Folio', 'Responsable', 'Estado'],
    'Documentos sanitarios' => ['Documento', 'Medicamento', 'Vigencia', 'Estado'],
    'Vigencias' => ['Permiso', 'Fecha de emisión', 'Vencimiento', 'Estado'],
    'Seguimiento regulatorio' => ['Folio', 'Trámite', 'Última actualización', 'Estado'],
    'Embarques' => ['Embarque', 'Origen', 'Destino', 'Fecha', 'Estado'],
    'Aduana' => ['Pedimento', 'Aduana', 'Folio', 'Estado'],
    'Almacén' => ['Medicamento', 'Lote', 'Existencias', 'Ubicación', 'Estado'],
    'Rutas' => ['Ruta', 'Folio', 'Mensajero', 'Estado', 'Fecha programada'],
    'Entregas' => ['Ruta', 'Folio', 'Mensajero', 'Estado', 'Fecha programada'],
    'Trazabilidad' => ['Folio', 'Estado', 'Responsable', 'Fecha'],
    'Facturas' => ['Factura', 'Proveedor', 'Importe', 'Fecha', 'Estado'],
    'Pagos' => ['Referencia', 'Beneficiario', 'Importe', 'Fecha', 'Estado'],
    'Contratos' => ['Contrato', 'Proveedor', 'Vigencia', 'Estado'],
    'Presupuestos' => ['Presupuesto', 'Concepto', 'Importe', 'Estado'],
    'Configuración' => ['Parámetro', 'Valor', 'Estado'],
    'Directorio' => ['Usuario', 'Nombre', 'Rol', 'Estado'],
    'Perfiles' => ['Usuario', 'Nombre', 'Módulo', 'Estado'],
    'Roles' => ['Usuario', 'Nombre', 'Rol', 'Estado'],
    'Permisos' => ['Usuario', 'Módulo', 'Alcance', 'Estado'],
    'Actividad' => ['Usuario', 'Acción', 'Folio', 'Fecha', 'Estado'],
    'Importaciones' => ['Folio', 'Paciente', 'Unidad', 'Estado', 'Fecha'],
    'Tiempos de entrega' => ['Folio', 'Fecha solicitada', 'Fecha requerida', 'Estado'],
    'Costos' => ['Folio', 'Concepto', 'Importe', 'Moneda', 'Estado'],
    'Cumplimiento' => ['Folio', 'Fecha requerida', 'Estado'],
    'Exportaciones' => ['Archivo', 'Formato', 'Fecha', 'Estado'],
  ];
  $columns = $schemas[$panelTitle];
  $rows = collect();
  $displayStatus = fn ($status) => $statusText($status);
  $date = fn ($value) => $value?->format('d/m/Y H:i') ?? 'Sin fecha';
  $addRow = function (array $cells, string $status = 'active') use (&$rows) {
    $rows->push(['cells' => $cells, 'status' => $status]);
  };
  $requestPanels = ['Resumen', 'Prioridades', 'Solicitudes', 'Seguimiento', 'Expedientes', 'Solicitudes hospitalarias', 'Importaciones'];
  if ($panelTitle === 'Formatos Editables') {
    $formats = $importSection === 5 ? ['Carta Encomienda (Agencia Aduanal)', 'Carta Responsiva (Agencia Aduanal)'] : ['Poder Simple (Cofepris)', 'Carta Motivo (Cofepris)'];
    foreach ($importRecords->filter(fn ($record) => $record->patient) as $record) {
      foreach ($formats as $format) {
        $patient = $record->patient;
        $rows->push(['cells' => [$patient->full_name, $record->external_id ?? 'Solicitud '.$record->id, data_get($record->payload, 'prescription_items.0.medication_name', 'Medicamento pendiente'), $format, 'Editar formato'], 'status' => $record->status,
          'patientData' => ['name' => $patient->full_name, 'curp' => $patient->curp ?? 'Pendiente', 'birth' => $patient->birth_date?->format('d/m/Y') ?? 'Pendiente', 'address' => $patient->address ?? 'Pendiente', 'hospital' => $record->medicalUnit?->name ?? 'Pendiente']]);
      }
    }
  } elseif (in_array($panelTitle, $requestPanels, true)) {
    foreach ($importRecords as $record) {
      $lastDate = match ($panelTitle) {
        'Prioridades' => $record->required_at,
        'Seguimiento' => $record->updated_at,
        default => $record->requested_at,
      };
      $addRow([$record->external_id ?? 'Solicitud '.$record->id, $record->patient?->full_name ?? 'Sin paciente', $record->medicalUnit?->name ?? 'Sin unidad', $displayStatus($record->status), $date($lastDate)], $record->status);
    }
  } elseif ($panelTitle === 'Pacientes') {
    foreach ($importRecords->filter(fn ($record) => $record->patient)->groupBy('patient_id') as $records) {
      $record = $records->first();
      $addRow([$record->patient->full_name, $records->pluck('medicalUnit.name')->filter()->unique()->implode(', '), $records->count(), 'Con solicitudes']);
    }
  } elseif (in_array($panelTitle, ['Hospitales', 'Unidades'], true)) {
    foreach ($importRecords->filter(fn ($record) => $record->medicalUnit)->groupBy('medical_unit_id') as $records) {
      $unit = $records->first()->medicalUnit;
      $names = $panelTitle === 'Hospitales'
        ? [$unit->institution?->name ?? 'Sin institución', $unit->name]
        : [$unit->name, $unit->institution?->name ?? 'Sin institución'];
      $addRow([...$names, $records->count(), $unit->status ?? 'Sin estado'], $unit->status ?? 'active');
    }
  } elseif ($panelTitle === 'Proveedores') {
    $providers = $importRecords->pluck('provider')->filter()->unique('id');
    if ($provider) { $providers->push($provider); $providers = $providers->unique('id'); }
    foreach ($providers as $item) {
      $addRow([$item->name, 'Medicamentos de importación', $importRecords->where('provider_id', $item->id)->count(), $item->status === 'active' ? 'Activo' : 'Inactivo'], $item->status);
    }
  } elseif (in_array($panelTitle, ['Directorio', 'Perfiles', 'Roles', 'Permisos'], true)) {
    $users = $importRecords->pluck('provider.user')->filter()->unique('id');
    if ($provider?->user) { $users->push($provider->user); $users = $users->unique('id'); }
    foreach ($users as $item) {
      $cells = $panelTitle === 'Permisos'
        ? [$item->username, $item->module, 'Proveedor de importación']
        : [$item->username, $item->name, $panelTitle === 'Perfiles' ? $item->module : $item->role];
      $addRow([...$cells, $item->status === 'active' ? 'Activo' : 'Inactivo'], $item->status);
    }
  } elseif ($panelTitle === 'Indicadores') {
    foreach ($metrics as $label => $total) { $addRow([$label, $total, 'Todas las solicitudes']); }
  } elseif (in_array($panelTitle, ['Rutas', 'Entregas'], true)) {
    foreach ($deliveryRoutes as $route) {
      $addRow([$route->id, $route->providerRequest?->external_id ?? 'Sin folio', $route->messenger?->user?->name ?? 'Sin asignar', $displayStatus($route->status), $date($route->scheduled_at)], $route->status);
    }
  } elseif (in_array($panelTitle, ['Trazabilidad', 'Actividad'], true)) {
    foreach ($importRecords as $record) {
      foreach ($record->statusEvents as $event) {
        $cells = $panelTitle === 'Trazabilidad'
          ? [$record->external_id, $displayStatus($event->status), $event->actor ?? 'Sin responsable', $date($event->occurred_at)]
          : [$event->actor ?? 'Sin responsable', $displayStatus($event->status), $record->external_id, $date($event->occurred_at), $displayStatus($event->status)];
        $addRow($cells, $event->status ?? 'active');
      }
    }
  } elseif (in_array($panelTitle, ['Recetas', 'Historial clínico', 'Medicamentos'], true)) {
    foreach ($importRecords as $record) {
      if ($panelTitle === 'Recetas' && data_get($record->payload, 'prescription_code')) {
        $addRow([data_get($record->payload, 'prescription_code'), $record->patient?->full_name ?? 'Sin paciente', count(data_get($record->payload, 'prescription_items', [])), $displayStatus($record->status), $date($record->requested_at)], $record->status);
      } elseif ($panelTitle === 'Historial clínico' && data_get($record->payload, 'diagnosis')) {
        $addRow([$record->external_id, $record->patient?->full_name ?? 'Sin paciente', data_get($record->payload, 'diagnosis'), $displayStatus($record->status), $date($record->requested_at)], $record->status);
      } elseif ($panelTitle === 'Medicamentos') {
        foreach (data_get($record->payload, 'prescription_items', []) as $item) {
          $addRow([data_get($item, 'name', data_get($item, 'medication_name', 'Sin nombre')), data_get($item, 'presentation', 'Sin presentación'), data_get($item, 'quantity', 'Sin cantidad'), data_get($item, 'unit', 'Sin unidad'), $displayStatus($record->status)], $record->status);
        }
      }
    }
  } elseif (in_array($panelTitle, ['Tiempos de entrega', 'Cumplimiento'], true)) {
    foreach ($importRecords as $record) {
      $cells = $panelTitle === 'Tiempos de entrega'
        ? [$record->external_id, $date($record->requested_at), $date($record->required_at), $displayStatus($record->status)]
        : [$record->external_id, $date($record->required_at), $displayStatus($record->status)];
      $addRow($cells, $record->status);
    }
  }
  $filterLabels = [
    0 => ['Todos', 'Pendientes', 'En proceso', 'Finalizados'],
    1 => ['Todos', 'Pendientes', 'En proceso', 'Finalizados'],
    2 => ['Todos', 'Activos', 'Inactivos'],
    3 => ['Todos', 'Activos', 'Pendientes', 'Finalizados'],
    4 => ['Todos', 'Pendientes', 'En proceso', 'Finalizados'],
    5 => ['Todos', 'Pendientes', 'En ruta', 'Entregados'],
    6 => ['Todos', 'Pendientes', 'Aprobados', 'Cancelados'],
    7 => ['Todos', 'Activos', 'Inactivos'],
    8 => ['Todos', 'Pendientes', 'Finalizados', 'Cancelados'],
  ];
  if (in_array($panelTitle, ['Proveedores', 'Pacientes', 'Hospitales', 'Unidades', 'Directorio', 'Perfiles', 'Roles', 'Permisos'], true)) {
    $filterLabels[$importSection] = ['Todos', 'Activos', 'Inactivos'];
  }
@endphp

@if ($panelTitle === 'Proveedores')
  @php
    $demoListing = require resource_path('views/provider/international-providers.php');
    $columns = $demoListing['columns'];
    $rows = $demoListing['rows'];
    foreach ($importSuppliers as $supplier) {
      $meta = $supplier->metadata;
      $state = $meta['initial_status'];
      $rows->push(['cells' => [$supplier->name, $meta['country'], implode(', ', $meta['categories']), $meta['contact_name'], $meta['email'], $meta['phone'], $meta['response_time'], ['active' => 'Activo', 'preferred' => 'Preferente', 'pending' => 'En validación', 'document_risk' => 'Riesgo documental'][$state], 'Sin evaluar', 0, $supplier->updated_at->format('d M Y H:i'), 'Abrir'], 'code' => 'PROV-REG-'.$supplier->id, 'job' => $meta['supplier_type'], 'status' => $state]);
    }
    $filterLabels[$importSection] = $demoListing['filters'];
  @endphp
@endif

@if ($panelTitle === 'Cotizaciones')
  @php
    $demoListing = require resource_path('views/provider/international-quotes.php');
    $columns = $demoListing['columns'];
    $rows = $demoListing['rows'];
    $filterLabels[$importSection] = $demoListing['filters'];
  @endphp
@endif

<section class="import-detail-panel" data-import-panel>
  @if (in_array($panelTitle, ['Embarques', 'Directorio', 'Trámites', 'Pacientes', 'Expedientes'], true))
    @php
      $referenceListing = require resource_path('views/provider/reference-lists.php');
      $columns = $referenceListing['columns'];
      $rows = $panelTitle === 'Pacientes' && $rows->isNotEmpty() ? $rows->map(fn ($row) => ['cells' => [$row['cells'][0], 'Sin registrar', 'Sin registrar', $row['cells'][1], $row['cells'][2], 'Ver detalle'], 'status' => $row['status']]) : $referenceListing['rows'];
      $filterLabels[$importSection] = $referenceListing['filters'];
      if ($panelTitle === 'Expedientes' && $importSection === 1) {
        $columns = [...array_slice($columns, 0, 7), 'Documentos del paciente', 'Documentos Regulatorio', 'Documentos Logística'];
        $rows = $rows->map(function ($row) use ($importRecords) {
          $record = $importRecords->firstWhere('external_id', $row['cells'][3]);
          $documents = $record?->patient?->documents ?? collect();
          $row['documents'] = collect(['INE', 'Comprobante de domicilio', 'Cédula fiscal', 'CURP', 'Receta médica'])->map(function ($name) use ($documents) {
            $document = $documents->first(fn ($item) => mb_strtolower($item->document_type) === mb_strtolower($name) || mb_strtolower(data_get($item->metadata, 'category', '')) === mb_strtolower($name));
            $exists = $document?->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($document->file_path);
            return ['name' => $name, 'url' => $exists ? route('provider.import.documents.view', $document) : null];
          })->values();
          $row['cells'] = [...array_slice($row['cells'], 0, 7), 'Ver '.$row['documents']->whereNotNull('url')->count().' de 5', 'Ver', 'Ver'];
          return $row;
        });
      }
      if ($panelTitle === 'Expedientes' && $importSection === 6) {
        if ($importRecords->isNotEmpty()) {
          $rows = $importRecords->map(fn ($record) => ['cells' => [
            $record->medicalUnit?->institution?->name ?? 'Pendiente', $record->medicalUnit?->name ?? 'Pendiente',
            $record->patient?->full_name ?? 'Pendiente', $record->external_id ?? 'Solicitud '.$record->id,
            $record->provider?->name ?? 'Pendiente', data_get($record->payload, 'prescription_items.0.medication_name', 'Pendiente'),
            $displayStatus($record->status), 'Editar poder', 'Editar carta motivo', 'Pendiente',
          ], 'status' => $record->status]);
        }
        $columns = [...array_slice($columns, 0, 3), 'Datos del paciente', 'Documentos', ...array_slice($columns, 3, 7), 'Documento agencia aduanal 1', 'Documento agencia aduanal 2'];
        $rows = $rows->map(function ($row) use ($importRecords) {
          $patientDocuments = $importRecords->firstWhere('external_id', $row['cells'][3])?->patient?->documents ?? collect();
          $patientDocuments = $patientDocuments->concat(\App\Models\Document::query()->where('uploaded_by', auth()->id())->where('metadata->folio', $row['cells'][3])->get());
          $loadedCount = collect(['INE', 'Comprobante de domicilio', 'Cédula fiscal', 'CURP', 'Receta médica'])->filter(function ($name) use ($patientDocuments) {
            return $patientDocuments->contains(fn ($document) => (mb_strtolower($document->document_type) === mb_strtolower($name) || mb_strtolower(data_get($document->metadata, 'category', '')) === mb_strtolower($name)) && $document->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($document->file_path));
          })->count();
          $row['loadedDocuments'] = $loadedCount;
          $patient = $importRecords->firstWhere('external_id', $row['cells'][3])?->patient;
          $row['patientDetails'] = ['Nombre' => $patient?->full_name ?? 'Pendiente de vincular', 'CURP' => $patient?->curp ?? 'Pendiente', 'Fecha de nacimiento' => $patient?->birth_date?->format('d/m/Y') ?? 'Pendiente', 'Correo electrónico' => $patient?->email ?? 'Pendiente', 'Teléfono' => $patient?->phone ?? 'Pendiente', 'Dirección' => $patient?->address ?? 'Pendiente'];
          if (!$patient && !$importRecords->firstWhere('external_id', $row['cells'][3])) {
            $demoNames = ['IMP-2026-001' => 'María López Hernández', 'IMP-2026-002' => 'Jorge Martínez Ruiz', 'IMP-2026-003' => 'Ana Torres García', 'IMP-2026-004' => 'Carlos Pérez Luna', 'IMP-2026-005' => 'Valeria López Guzmán'];
            $row['patientDetails'] = ['Nombre' => $demoNames[$row['cells'][3]] ?? 'Paciente de demostración', 'CURP' => 'CURP-DEMO-000001', 'Fecha de nacimiento' => '15/03/1992', 'Correo electrónico' => 'paciente.demo@example.com', 'Teléfono' => '55 0000 1234', 'Dirección' => 'Av. Ejemplo 123, Col. Demostración, Ciudad de México'];
          }
          $row['patientDetails'] = array_merge($row['patientDetails'], auth()->user()->metadata['import_patient_details'][$row['cells'][3]] ?? []);
          $row['uploadedFiles'] = collect(['ine' => 'INE', 'address' => 'Comprobante de domicilio', 'tax' => 'Cédula fiscal', 'curp' => 'CURP', 'prescription' => 'Receta médica', 'summary' => 'Resumen clínico'])->map(function ($name) use ($patientDocuments) {
            $document = $patientDocuments->sortByDesc('id')->first(fn ($item) => (mb_strtolower($item->document_type) === mb_strtolower($name) || mb_strtolower(data_get($item->metadata, 'category', '')) === mb_strtolower($name)) && $item->file_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($item->file_path));
            return $document ? ['name' => $document->name, 'url' => route('provider.import.documents.view', $document)] : null;
          })->filter()->all();
          $row['cells'] = [...array_slice($row['cells'], 0, 3), 'Ver', $loadedCount.' de 5', ...array_slice($row['cells'], 3, 7), 'Pendiente', 'Pendiente'];
          return $row;
        });
      }
    @endphp
  @endif
  @if (session('status'))<div class="notice success">{{ session('status') }}</div>@endif
  <div class="provider-import-native-card import-summary">
    <div>
      <h2>{{ $panelTitle }}</h2>
      <p>{{ $sectionTitle }} · Consulta y seguimiento de {{ mb_strtolower($panelTitle) }}.</p>
      <span class="import-summary-badge">{{ $rows->count() }} registros disponibles</span>
      @if (in_array($panelTitle, ['Proveedores', 'Cotizaciones', 'Embarques', 'Directorio', 'Trámites', 'Pacientes', 'Expedientes']))<small class="import-demo-label">Diseño de referencia; los registros ilustrativos son de demostración.</small>@endif
    </div>
    <div class="import-summary-actions">
      @if ($panelTitle === 'Proveedores')<button type="button" class="import-export-button" data-new-supplier>⊕ Nuevo proveedor</button>@endif
      <button type="button" class="import-export-button" data-import-export>Descargar CSV</button>
    </div>
  </div>
  @if ($importSection === 0 && $importAction === 0)
    @include('provider.import-welcome')
  @endif
  @if ($importSection === 8 && $importAction === 0)
    @include('provider.import-analytics')
  @endif
  <div class="import-section-filters" aria-label="Filtros de {{ $panelTitle }}">
    @foreach ($filterLabels[$importSection] as $filter)
      <button type="button" @class(['import-filter-button', 'is-active' => $loop->first]) data-import-filter="{{ $filter }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}">{{ $filter }}</button>
    @endforeach
    <label class="import-search">Buscar en {{ mb_strtolower($panelTitle) }}<input type="search" placeholder="Buscar por nombre, folio o estado" data-import-search></label>
  </div>
  <div @class(['import-quotes-layout' => $panelTitle === 'Cotizaciones'])>
  <section class="provider-import-native-card">
    <div class="provider-import-native-heading"><div><h2>{{ match ($panelTitle) { 'Proveedores' => 'Listado de proveedores', 'Cotizaciones' => 'Listado de cotizaciones', default => $panelTitle } }}</h2>@if ($panelTitle === 'Proveedores')<p>Consulta la información de tus proveedores internacionales.</p>@elseif ($panelTitle === 'Cotizaciones')<p>Cotizaciones recibidas de proveedores con sus condiciones comerciales.</p>@endif</div><span data-import-count>{{ $rows->count() }} registros</span></div>
    <div class="provider-import-native-table-scroll">
      <table class="provider-import-native-table import-detail-table {{ $panelTitle === 'Expedientes' ? 'import-expedients-table' : '' }}" data-drsam-table-filter-skip>
        <thead><tr>@foreach ($columns as $index => $column)<th><span class="import-column-title">
          @if ($column === 'Documentos del paciente')
            Documentos del<br>paciente
          @else
            {{ $column }}
          @endif
        </span>
          <select data-import-column="{{ $index }}" aria-label="Filtrar {{ $column }}">
            <option value="">Todos</option>
            @foreach ($rows->map(fn ($row) => (string) ($row['cells'][$index] ?? 'Sin información'))->unique()->sort() as $value)
              <option value="{{ $value }}">{{ $value }}</option>
            @endforeach
          </select>
        </th>@endforeach</tr></thead>
        <tbody>
          @foreach ($rows as $row)
            <tr data-import-row data-status="{{ $row['status'] }}">
              @foreach ($row['cells'] as $index => $cell)
                <td data-filter-value="{{ $cell ?? 'Sin información' }}">
                  @php
                    $administrationPatientDetails = $panelTitle === 'Expedientes' && $importSection === 6 && $index === 3;
                    $administrationDocuments = $panelTitle === 'Expedientes' && $importSection === 6 && $index === 4;
                    if ($panelTitle === 'Expedientes' && $importSection === 6 && $index > 4) $index -= 2;
                    $expedientFolio = $panelTitle === 'Expedientes' ? $row['cells'][$importSection === 6 ? 5 : 3] : '';
                    $expedientMedicine = $panelTitle === 'Expedientes' ? $row['cells'][$importSection === 6 ? 7 : 5] : '';
                    $expedientStage = $panelTitle === 'Expedientes' ? $row['cells'][$importSection === 6 ? 8 : 6] : '';
                  @endphp
                  @if ($administrationPatientDetails)
                    <button type="button" data-patient-folio="{{ $expedientFolio }}" data-patient-details="{{ json_encode($row['patientDetails']) }}">Ver</button>
                  @elseif ($administrationDocuments)
                    <button type="button" class="import-upload-count {{ ($row['loadedDocuments'] ?? 0) === 5 ? 'is-complete' : 'is-pending' }}" data-patient-upload data-uploaded-files="{{ json_encode($row['uploadedFiles'] ?? []) }}" data-upload-folio="{{ $expedientFolio }}" data-upload-enabled="{{ $importRecords->firstWhere('external_id', $expedientFolio)?->patient_id ? 'true' : 'false' }}" aria-label="{{ $cell }} documentos cargados. Abrir carga de documentos">{{ $cell }}</button>
                  @elseif ($panelTitle === 'Expedientes' && $importSection === 6 && $index >= 10)
                    <span class="import-provider-pill is-pending">{{ $cell }}</span>
                  @elseif ($panelTitle === 'Expedientes' && $importSection === 1 && $index >= 7)
                    <button type="button" @class(['import-upload-count' => $index === 7, 'is-pending' => $index === 7 && $row['documents']->whereNotNull('url')->count() < 5, 'is-complete' => $index === 7 && $row['documents']->whereNotNull('url')->count() === 5]) data-expedient-documents data-document-title="{{ $columns[$index] }}" data-documents="{{ $index === 7 ? $row['documents']->toJson() : '[]' }}">{{ $cell }}</button>
                  @elseif ($panelTitle === 'Formatos Editables' && $index === 4)
                    <button type="button" data-expedient-editor data-letter-group="{{ $importSection === 5 ? 'aduanal' : 'cofepris' }}" data-letter-type="{{ $row['cells'][3] }}" data-folio="{{ $row['cells'][1] }}" data-medicine="{{ $row['cells'][2] }}" data-letter-patient="{{ json_encode($row['patientData']) }}">Editar formato</button>
                  @elseif ($panelTitle === 'Expedientes' && $index === 6)
                    <button type="button" class="import-provider-pill is-{{ $row['status'] }}" data-expedient-dashboard data-folio="{{ $expedientFolio }}" data-medicine="{{ $expedientMedicine }}" data-stage="{{ $expedientStage }}" data-status="{{ $row['status'] }}">{{ $cell }}</button>
                    @elseif ($panelTitle === 'Expedientes' && in_array($index, [7, 8]))
                      <button type="button" data-expedient-editor data-letter-group="cofepris" data-letter-type="{{ $index === 7 ? 'Poder Simple (Cofepris)' : 'Carta Motivo (Cofepris)' }}" data-folio="{{ $expedientFolio }}" data-medicine="{{ $expedientMedicine }}">{{ $cell }}</button>
                    @elseif ($panelTitle === 'Expedientes' && in_array($index, [9, 10]))
                    <span class="import-provider-pill is-pending">{{ $cell }}</span>
                    @elseif ($panelTitle === 'Expedientes' && $index === 11)
                    <button type="button" disabled title="No hay documento registrado">Ver documento</button>
                    @elseif ($panelTitle === 'Expedientes' && $index === 12)
                    <button type="button" data-import-open>Abrir</button>
                    @elseif ($panelTitle === 'Expedientes' && in_array($index, [13, 14]))
                      <button type="button" data-expedient-editor data-letter-group="{{ $index === 13 ? 'cofepris' : 'aduanal' }}" data-folio="{{ $expedientFolio }}" data-medicine="{{ $expedientMedicine }}">Editar cartas</button>
                  @elseif ($panelTitle === 'Pacientes' && $index === count($columns) - 1)
                    @php
                      $patientOrders = $importRecords->filter(fn ($record) => $record->patient?->full_name === $row['cells'][0])->map(fn ($record) => [
                        'folio' => $record->external_id ?? 'Pedido '.$record->id,
                        'status' => $record->status,
                        'medicine' => data_get($record->payload, 'prescription_items.0.medication_name', 'Medicamento de importación'),
                      ])->values();
                    @endphp
                    <button type="button" data-patient-dashboard data-patient-name="{{ $row['cells'][0] }}" data-patient-orders="{{ $patientOrders->toJson() }}">Ver dashboard</button>
                  @elseif (in_array($panelTitle, ['Proveedores', 'Cotizaciones', 'Directorio', 'Trámites', 'Expedientes']) && $index === count($columns) - 1)
                    <button type="button" data-import-open>{{ $panelTitle === 'Cotizaciones' ? 'Ver' : 'Abrir' }}</button>
                  @elseif ((in_array($panelTitle, ['Embarques', 'Trámites']) && $index === 4) || (in_array($panelTitle, ['Directorio', 'Expedientes']) && $index === 3))
                    <span class="import-provider-pill is-{{ $row['status'] }}">{{ $cell }}</span>
                  @elseif ($panelTitle === 'Cotizaciones' && $index === 1)
                    <strong>{{ $cell }}</strong><small class="import-cell-detail">{{ $row['country'] }}</small>
                  @elseif ($panelTitle === 'Cotizaciones' && $index === 9)
                    <span class="import-provider-pill is-{{ $row['status'] }}">{{ $cell }}</span>
                  @elseif ($panelTitle === 'Proveedores' && $index === 0)
                    <strong>{{ $cell }}</strong><small class="import-cell-detail">{{ $row['code'] }}</small>
                  @elseif ($panelTitle === 'Proveedores' && $index === 3)
                    <strong>{{ $cell }}</strong><small class="import-cell-detail">{{ $row['job'] }}</small>
                  @elseif ($panelTitle === 'Proveedores' && in_array($index, [2, 7]))
                    <span class="import-provider-pill {{ $index === 7 ? 'is-'.$row['status'] : '' }}">{{ $cell }}</span>
                  @elseif ($panelTitle === 'Proveedores' && $index === 8)
                    <span class="import-rating-star" aria-hidden="true">★</span> {{ $cell }}
                  @else
                    {{ $cell ?? 'Sin información' }}
                  @endif
                </td>
              @endforeach
            </tr>
          @endforeach
          <tr data-import-empty @if ($rows->isNotEmpty()) hidden @endif><td colspan="{{ count($columns) }}">{{ $rows->isEmpty() ? 'No hay registros disponibles para esta opción.' : 'No hay resultados para los filtros seleccionados.' }}</td></tr>
        </tbody>
      </table>
    </div>
  </section>
  @if ($panelTitle === 'Cotizaciones')
    <aside class="provider-import-native-card import-quote-comparison">
      <h2>Comparativa de propuestas</h2><p>Resumen ilustrativo de la referencia.</p>
      <article><span aria-hidden="true">♜</span><h3>Mejor precio</h3><strong>Nippon Medical Co.</strong><p>Osimertinib 80 mg</p><b>$520.00 USD</b><small>por unidad · Cotización de demostración</small></article>
      <article><span aria-hidden="true">♧</span><h3>Menor tiempo de entrega</h3><strong>Global Pharma Solutions</strong><p>Nivolumab 10 mg/ml</p><b>7 días</b></article>
      <article><span aria-hidden="true">☆</span><h3>Proveedor preferido</h3><strong>EuroHealth BV</strong><p>Adalimumab 40 mg</p><small>Selección ilustrativa de la referencia.</small></article>
    </aside>
  @endif
  </div>
</section>
@if ($panelTitle === 'Proveedores')
  @include('provider.new-supplier')
  <dialog class="import-provider-dialog" data-import-dialog>
    <form method="dialog"><button aria-label="Cerrar detalle">Cerrar ×</button></form>
    <h2 data-import-detail-title></h2><p>Proveedor internacional · Datos de demostración</p><dl data-import-detail-fields></dl>
  </dialog>
@endif
@if (in_array($panelTitle, ['Cotizaciones', 'Directorio', 'Trámites', 'Pacientes', 'Expedientes'], true))
  <dialog class="import-provider-dialog" data-import-dialog>
    <form method="dialog"><button aria-label="Cerrar detalle">Cerrar ×</button></form>
    <h2 data-import-detail-title></h2><p>{{ $panelTitle }} · Datos de demostración</p><dl data-import-detail-fields></dl>
  </dialog>
@endif
@push('scripts')
  <script src="{{ asset('js/provider-import.js') }}?v={{ filemtime(public_path('js/provider-import.js')) }}" defer></script>
@endpush

@if ($panelTitle === 'Expedientes' || $panelTitle === 'Formatos Editables')
  @if ($panelTitle === 'Expedientes') @include('provider.expedient-dashboard') @endif
  <dialog class="import-provider-dialog import-letter-editor" data-letter-dialog>
    <header><h2>Archivos editables · <span data-letter-folio></span></h2><button type="button" data-letter-close aria-label="Cerrar editor">Cerrar ×</button></header>
    <p>Borradores para completar y revisar antes de firmar. No son documentos oficiales emitidos.</p>
    <label>Documento<select data-letter-type>
      <option data-group="aduanal">Carta Encomienda (Agencia Aduanal)</option>
      <option data-group="aduanal">Carta Responsiva (Agencia Aduanal)</option>
      <option data-group="cofepris">Poder Simple (Cofepris)</option>
      <option data-group="cofepris">Carta Motivo (Cofepris)</option>
    </select></label>
    <textarea data-letter-content rows="18" aria-label="Contenido editable de la carta"></textarea>
    <footer><button type="button" data-letter-download>Descargar archivo editable (.txt)</button></footer>
    <small>Conserva tus cambios descargando el archivo antes de cerrar. Los borradores de esta sesión permanecen al cambiar de carta.</small>
  </dialog>
  @push('scripts')<script src="{{ asset('js/import-letters.js') }}?v={{ filemtime(public_path('js/import-letters.js')) }}" defer></script>@endpush
@endif

@if ($panelTitle === 'Pacientes')
  <dialog class="patient-order-dashboard" data-patient-dashboard-dialog>
    <header><span class="patient-dashboard-brand">Klini · Contigo en cada paso</span><button type="button" data-close-patient-dashboard aria-label="Cerrar dashboard">×</button></header>
    <div class="patient-dashboard-body">
      <p class="patient-dashboard-eyebrow">SEGUIMIENTO DE TU MEDICAMENTO</p>
      <h2 data-patient-dashboard-name></h2><p>Consulta cómo avanza tu pedido hasta llegar a tus manos.</p>
      <label class="patient-dashboard-order-label">Pedido<select data-patient-dashboard-order></select></label>
      <div class="patient-dashboard-hero"><div><small data-patient-dashboard-folio></small><h3 data-patient-dashboard-medicine></h3><p data-patient-dashboard-stage></p></div><div class="patient-dashboard-ring"><b data-patient-dashboard-percent></b><small>avance</small></div></div>
      <div class="patient-dashboard-progress"><i data-patient-dashboard-progress></i></div>
      <p class="patient-dashboard-remaining" data-patient-dashboard-remaining aria-live="polite"></p>
      <ol class="patient-dashboard-timeline" data-patient-dashboard-timeline></ol>
      <div class="patient-dashboard-note" data-patient-dashboard-note></div>
    </div>
  </dialog>
  @push('scripts')<script src="{{ asset('js/patient-import-dashboard.js') }}?v={{ filemtime(public_path('js/patient-import-dashboard.js')) }}" defer></script>@endpush
@endif
<dialog class="import-provider-dialog import-patient-documents-dialog" data-documents-dialog>
  <header><span class="import-documents-heading-icon" aria-hidden="true">▤</span><h2 data-documents-title>Documentos del paciente</h2><button type="button" data-documents-close aria-label="Cerrar">×</button></header>
  <p>Consulta y visualización de archivos cargados</p>
  <table class="import-patient-documents-table" data-drsam-table-filter-skip><thead><tr><th>Documento</th><th>Estado</th><th>Acciones</th></tr></thead><tbody data-documents-list></tbody></table>
  <footer data-patient-documents-footer><button type="button" data-patient-documents-close>Cerrar</button><button type="button" data-documents-download>↓ Descargar todo</button></footer>
  <div class="import-logistics-documents" data-logistics-documents hidden></div>
  <footer data-logistics-footer hidden><button type="button" data-logistics-close>Cerrar</button></footer>
</dialog>
@push('scripts')
<script>
document.querySelectorAll('[data-expedient-documents]').forEach(button => button.addEventListener('click', () => {
 const dialog = document.querySelector('[data-documents-dialog]');
 dialog.querySelector('[data-documents-title]').textContent = button.dataset.documentTitle;
 const body = dialog.querySelector('[data-documents-list]'); body.replaceChildren();
 const documents = JSON.parse(button.dataset.documents);
 const logistics = button.dataset.documentTitle === 'Documentos Logística';
 dialog.classList.toggle('is-logistics-documents', logistics);
 dialog.querySelector('table').hidden = logistics;
 const cards = dialog.querySelector('[data-logistics-documents]'); cards.hidden = !logistics; cards.replaceChildren();
 dialog.querySelector('[data-logistics-footer]').hidden = !logistics;
 dialog.querySelector('[data-patient-documents-footer]').hidden = logistics;
 const download = dialog.querySelector('[data-documents-download]');
 download.disabled = !documents.some(item => item.url);
 download.onclick = () => documents.filter(item => item.url).forEach(item => { const link = window.document.createElement('a'); link.href = item.url + '?download=1'; link.download = ''; window.document.body.append(link); link.click(); link.remove(); });
 if (logistics) {
  dialog.querySelector('p').textContent = 'Consulta y visualiza los documentos disponibles en el expediente.';
  ['Documento agencia Aduanal 1', 'Documento agencia Aduanal 2'].forEach(name => {
   const doc = documents.find(item => item.name === name);
   const card = window.document.createElement('article');
   const icon = window.document.createElement('span'); icon.className = 'import-logistics-file-icon'; icon.textContent = '▤'; icon.setAttribute('aria-hidden', 'true');
   const info = window.document.createElement('div'); const title = window.document.createElement('strong'); title.textContent = name;
   const detail = window.document.createElement('small'); detail.textContent = doc?.detail || 'Archivo pendiente de carga'; info.append(title, detail);
   const state = window.document.createElement('span'); state.className = 'import-logistics-state'; state.textContent = doc?.url ? '● Disponible' : '● Pendiente';
   const view = window.document.createElement(doc?.url ? 'a' : 'button'); view.textContent = '◉ Ver';
   if (doc?.url) { view.href = doc.url; view.target = '_blank'; view.rel = 'noopener'; } else { view.disabled = true; }
   card.append(icon, info, state, view); cards.append(card);
  });
  dialog.showModal(); return;
 }
 dialog.querySelector('p').textContent = 'Consulta y visualización de archivos cargados';
 if (!documents.length) { const row = body.insertRow(); row.insertCell().textContent = 'No hay documentos cargados para este expediente.'; }
 documents.forEach(document => {
  const row = body.insertRow(); const name = row.insertCell();
  const icon = window.document.createElement('span'); icon.className = 'import-document-row-icon'; icon.textContent = '▤'; icon.setAttribute('aria-hidden', 'true'); name.append(icon, document.name);
  const status = window.document.createElement('span'); status.className = document.url ? 'import-document-status is-loaded' : 'import-document-status'; status.textContent = document.url ? '✓ Cargado' : 'Pendiente'; row.insertCell().append(status);
  const cell = row.insertCell();
  if (document.url) { const link = window.document.createElement('a'); link.href = document.url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Ver'; cell.append(link); }
  else { const button = window.document.createElement('button'); button.textContent = 'Ver'; button.disabled = true; cell.append(button); }
 }); dialog.showModal();
}));
document.querySelector('[data-documents-close]').addEventListener('click', () => document.querySelector('[data-documents-dialog]').close());
document.querySelector('[data-logistics-close]').addEventListener('click', () => document.querySelector('[data-documents-dialog]').close());
document.querySelector('[data-patient-documents-close]').addEventListener('click', () => document.querySelector('[data-documents-dialog]').close());
</script>
@endpush
@if ($panelTitle === 'Expedientes' && $importSection === 6)
<dialog class="import-provider-dialog import-upload-dialog" data-upload-dialog>
<form method="post" enctype="multipart/form-data" action="{{ route('provider.import.patient_documents.store') }}">
@csrf
<input type="hidden" name="folio" data-upload-folio-field>
<header><span class="import-documents-heading-icon">▤</span><div><h2>Agregar archivos del paciente</h2><p>Sube los documentos solicitados para integrarlos al expediente.</p></div><button type="button" data-upload-close aria-label="Cerrar">×</button></header>
<p data-upload-warning hidden>Los archivos se guardarán en este expediente como pendientes de vinculación al paciente.</p>
@foreach (['ine' => 'INE', 'address' => 'Comprobante de domicilio', 'tax' => 'Cédula fiscal', 'curp' => 'CURP', 'prescription' => 'Receta médica', 'summary' => 'Resumen clínico'] as $key => $name)
<article class="import-upload-row"><span class="import-document-row-icon">▤</span><div><strong>{{ $name }}</strong><small>Formatos aceptados: PDF / JPG / PNG · Máx. 10 MB</small></div><label class="import-upload-drop">↑ Subir archivo<small data-upload-name>o arrastra y suelta aquí</small><input type="file" name="files[{{ $key }}]" accept=".pdf,.jpg,.jpeg,.png" aria-label="Subir {{ $name }}"></label></article>
@endforeach
<footer><small>Todos los archivos serán vinculados al expediente del paciente.</small><button type="button" data-upload-close>Cancelar</button><button type="submit" data-upload-save>Guardar</button></footer>
</form></dialog>
@push('scripts')
<script>
(() => {
 const dialog = document.querySelector('[data-upload-dialog]');
 document.querySelectorAll('[data-patient-upload]').forEach(button => button.addEventListener('click', () => {
  dialog.querySelector('form').reset(); dialog.querySelector('[data-upload-folio-field]').value = button.dataset.uploadFolio;
  const enabled = button.dataset.uploadEnabled === 'true'; dialog.querySelector('[data-upload-warning]').hidden = enabled;
  dialog.querySelector('[data-upload-save]').disabled = false;
  const saved = JSON.parse(button.dataset.uploadedFiles || '{}');
  dialog.querySelectorAll('input[type=file]').forEach(input => {
   input.disabled = false;
   const label = input.parentElement, key = input.name.match(/\[(.*?)\]/)[1], file = saved[key];
   label.querySelector('[data-upload-name]').textContent = 'o arrastra y suelta aquí';
   label.hidden = Boolean(file);
   let completed = label.parentElement.querySelector('.import-upload-completed');
   if (completed) completed.remove();
   if (file) {
    completed = document.createElement('div'); completed.className = 'import-upload-completed';
    const name = document.createElement('strong'); name.textContent = file.name;
    const link = document.createElement('a'); link.href = file.url; link.target = '_blank'; link.rel = 'noopener'; link.textContent = 'Ver';
    completed.append(name, link); label.after(completed);
   }
  }); dialog.showModal();
 }));
 dialog.querySelectorAll('[data-upload-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
 dialog.querySelectorAll('.import-upload-drop').forEach(label => {
  const input = label.querySelector('input'); const update = () => label.querySelector('[data-upload-name]').textContent = input.files[0]?.name || 'o arrastra y suelta aquí';
  input.addEventListener('change', update);
  label.addEventListener('dragover', event => event.preventDefault());
  label.addEventListener('drop', event => { event.preventDefault(); if (input.disabled) return; const transfer = new DataTransfer(); if (event.dataTransfer.files[0]) transfer.items.add(event.dataTransfer.files[0]); input.files = transfer.files; update(); });
 });
 dialog.querySelector('form').addEventListener('submit', event => { if (![...dialog.querySelectorAll('input[type=file]')].some(input => input.files.length)) { event.preventDefault(); dialog.querySelector('input[type=file]').focus(); alert('Selecciona al menos un archivo para guardar.'); } });
})();
</script>
@endpush
@endif
@if ($panelTitle === 'Expedientes' && $importSection === 6)
<dialog class="import-provider-dialog import-patient-info-dialog" data-patient-details-dialog><header><span class="patient-info-avatar" aria-hidden="true">♟</span><div><h2>Datos del paciente</h2><p>Información general del paciente en el expediente.</p></div><button type="button" data-patient-details-close aria-label="Cerrar">×</button></header><dl data-patient-details-list></dl><footer><button type="button" data-patient-details-close>× &nbsp; Cerrar</button><button type="button" data-patient-details-edit>✎ Editar</button></footer><form method="post" action="{{ route('provider.import.patient_details.store') }}" data-patient-details-form hidden>@csrf<input type="hidden" name="folio" data-details-folio><div data-details-fields></div><button type="submit">Guardar</button></form></dialog>
@push('scripts')
<script>
document.querySelectorAll('[data-patient-details]').forEach(button => button.addEventListener('click', () => {
 const dialog = document.querySelector('[data-patient-details-dialog]'), list = dialog.querySelector('[data-patient-details-list]'); list.replaceChildren(); dialog.querySelector('[data-patient-details-form]').hidden = true; dialog.querySelector('[data-details-folio]').value = button.dataset.patientFolio; dialog.dataset.details = button.dataset.patientDetails; dialog.querySelector('[data-patient-details-edit]').hidden = false; dialog.querySelector('h2 + p').textContent = 'Información general del paciente en el expediente. Los registros de demostración contienen datos ficticios.';
 Object.entries(JSON.parse(button.dataset.patientDetails)).forEach(([key, value]) => { const label = document.createElement('dt'), detail = document.createElement('dd'); const icons = {'Nombre':'♟','CURP':'▤','Fecha de nacimiento':'▦','Correo electrónico':'✉','Teléfono':'☎','Dirección':'⌖'}; const icon = document.createElement('span'); icon.className = 'patient-info-row-icon'; icon.textContent = icons[key] || '▤'; icon.setAttribute('aria-hidden', 'true'); label.append(icon, key); const badge = document.createElement('span'); badge.className = value === 'Pendiente de vincular' ? 'patient-info-value is-unlinked' : 'patient-info-value'; badge.textContent = value; detail.append(badge); list.append(label, detail); }); dialog.showModal();
}));
document.querySelector('[data-patient-details-edit]').addEventListener('click', () => {
 const dialog = document.querySelector('[data-patient-details-dialog]'), form = dialog.querySelector('[data-patient-details-form]');
 const fields = form.querySelector('[data-details-fields]'); fields.replaceChildren();
 const details = Object.entries(JSON.parse(dialog.dataset.details));
 dialog.querySelectorAll('[data-patient-details-list] dd').forEach((cell, index) => {
  const [key, value] = details[index]; const input = document.createElement('input');
  input.name = 'details[' + key + ']'; input.value = value.startsWith('Pendiente') ? '' : value;
  input.setAttribute('form', 'patient-details-edit-form'); input.setAttribute('aria-label', key);
  input.maxLength = 500; cell.replaceChildren(input);
 });
 form.id = 'patient-details-edit-form'; form.hidden = false;
 dialog.querySelector('[data-patient-details-edit]').hidden = true;
 dialog.querySelector('[data-patient-details-list] input')?.focus();
});
 document.querySelectorAll('[data-patient-details-close]').forEach(button => button.addEventListener('click', () => document.querySelector('[data-patient-details-dialog]').close()));
</script>
@endpush
@endif
@if (session('patient_details_saved'))
<dialog class="import-provider-dialog import-save-success" data-patient-save-success aria-labelledby="patient-save-success-title">
<span class="import-save-success-icon" aria-hidden="true">✓</span>
<h2 id="patient-save-success-title">Datos guardados satisfactoriamente</h2>
<button type="button" data-patient-save-success-close>Aceptar</button>
</dialog>
@push('scripts')
<script>
(() => { const dialog = document.querySelector('[data-patient-save-success]'); dialog.showModal(); dialog.querySelector('[data-patient-save-success-close]').addEventListener('click', () => dialog.close()); })();
</script>
@endpush
@endif
