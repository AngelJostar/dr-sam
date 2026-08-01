@extends('layouts.app', ['title' => $type === 'chemotherapy' ? 'Proveedor Quimioterapias' : ($type === 'import' ? 'Proveedor Importacion' : 'Proveedor Integral')])

@section('body_class', $type === 'chemotherapy' ? 'provider-chemo-native-body' : ($type === 'import' ? 'provider-import-native-body' : 'provider-native-body'))

@php
  $statusLabels = [
    'requested' => 'Solicitada',
    'accepted' => 'Aceptada',
    'preparing' => 'Preparacion',
    'in_route' => 'En ruta',
    'delivered' => 'Entregada',
    'rejected' => 'Rechazada',
    'cancelled' => 'Cancelada',
  ];

  $typeLabels = [
    'npt' => 'NPT',
    'chemotherapy' => 'Oncologicas',
    'import' => 'Importacion',
  ];

  $serviceLabels = [
    'npt' => 'Nutricion Parenteral',
    'chemotherapy' => 'Quimioterapia',
    'import' => 'Medicamentos de importacion',
  ];

  $chemoCatalog = collect([
    ['family' => 'Agentes alquilantes', 'name' => 'Ciclofosfamida', 'unit' => 'mg', 'use' => 'Dosis capturada para esquemas de quimioterapia.'],
    ['family' => 'Agentes alquilantes', 'name' => 'Ifosfamida', 'unit' => 'mg', 'use' => 'Antineoplasico para protocolos con proteccion urotroxa.'],
    ['family' => 'Platinos', 'name' => 'Cisplatino', 'unit' => 'mg', 'use' => 'Medicamento de platino para mezcla oncologica.'],
    ['family' => 'Platinos', 'name' => 'Carboplatino', 'unit' => 'mg', 'use' => 'Dosis por AUC o calculo medico capturada en la solicitud.'],
    ['family' => 'Platinos', 'name' => 'Oxaliplatino', 'unit' => 'mg', 'use' => 'Platino usado en protocolos gastrointestinales.'],
    ['family' => 'Taxanos', 'name' => 'Paclitaxel', 'unit' => 'mg', 'use' => 'Taxano para infusion oncologica.'],
    ['family' => 'Taxanos', 'name' => 'Docetaxel', 'unit' => 'mg', 'use' => 'Taxano solicitado por protocolo.'],
    ['family' => 'Antraciclinas', 'name' => 'Doxorrubicina', 'unit' => 'mg', 'use' => 'Antraciclina capturada en el formato oncologico.'],
    ['family' => 'Antraciclinas', 'name' => 'Epirubicina', 'unit' => 'mg', 'use' => 'Antraciclina para preparacion centralizada.'],
    ['family' => 'Antimetabolitos', 'name' => 'Fluorouracilo 5-FU', 'unit' => 'mg', 'use' => 'Antimetabolito para infusion o bolo segun protocolo.'],
    ['family' => 'Antimetabolitos', 'name' => 'Metotrexato', 'unit' => 'mg', 'use' => 'Antimetabolito con dosis indicada por el medico.'],
    ['family' => 'Antimetabolitos', 'name' => 'Gemcitabina', 'unit' => 'mg', 'use' => 'Antimetabolito para protocolos oncologicos.'],
  ]);

  $importFiles = collect([
    ['folio' => 'IMP-20260607-003', 'patient' => 'Ximena Sofia Martinez Perez', 'medication' => 'Cannabidiol solucion oral', 'detail' => '100 mg/ml', 'stage' => 'En documentacion', 'responsible' => 'Area administrativa', 'progress' => 11],
    ['folio' => 'IMP-20260611-001', 'patient' => 'Claudia Beatriz Salinas Vega', 'medication' => 'Tocilizumab', 'detail' => '162 mg solucion inyectable', 'stage' => 'Permiso Cofepris', 'responsible' => 'Gestion de Cofepris', 'progress' => 44],
    ['folio' => 'IMP-20260609-002', 'patient' => 'Rafael Ortega Morales', 'medication' => 'Asfotasa alfa', 'detail' => '80 mg/0.8 ml', 'stage' => 'Aduana CDMX', 'responsible' => 'Operador logistico', 'progress' => 78],
  ]);

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $activeSection = $typeLabels[$type] ?? strtoupper($type);
@endphp

@section('content')
  @if ($type === 'import')
    <div class="provider-import-native-screen">
      <aside class="provider-import-native-sidebar" aria-label="Navegacion proveedor importacion">
        <div class="provider-import-native-brand">
          <span>I</span>
          <div>
            <strong>Proveedor Importacion</strong>
            <small>Medicamentos internacionales</small>
          </div>
        </div>
        <nav class="provider-import-native-menu">
          <a class="is-active" href="{{ route('provider.import.dashboard') }}"><span>T</span>Tablero</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>D</span>Dashboard de Entrega</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>E</span>Expedientes</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>S</span>Seguimiento</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>A</span>Area Administrativa</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>P</span>Proveedor Extranjero</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>C</span>Gestion Cofepris</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>O</span>Operador Logistico</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>DO</span>Documentos</a>
          <a href="{{ route('provider.import.dashboard') }}"><span>R</span>Reportes</a>
        </nav>
      </aside>

      <section class="provider-import-native-workspace">
        @if (session('status'))
          <div class="notice success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
          <div class="notice danger">
            <strong>No se pudo actualizar.</strong>
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <header class="provider-import-native-topbar">
          <div>
            <p class="eyebrow">Proveedor extranjero y tramite sanitario</p>
            <h1>Tablero de importacion</h1>
          </div>
          <div>
            <a href="{{ route('dashboard') }}">&larr; Atras</a>
            <time>{{ now()->format('d M Y') }}</time>
          </div>
        </header>

        <section class="provider-import-native-card">
          <div class="provider-import-native-heading">
            <div>
              <h2>Expedientes con prioridad operativa</h2>
              <p>Seguimiento de recetas, permisos, aduana y almacen.</p>
            </div>
            <button type="button">Nuevo expediente</button>
          </div>
          <div class="provider-import-native-table-scroll">
            <table class="provider-import-native-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Paciente</th>
                  <th>Medicamento</th>
                  <th>Etapa</th>
                  <th>Responsable</th>
                  <th>Avance</th>
                  <th>Accion</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($importFiles as $file)
                  <tr>
                    <td><strong>{{ $file['folio'] }}</strong></td>
                    <td>{{ $file['patient'] }}</td>
                    <td>
                      <strong>{{ $file['medication'] }}</strong>
                      <span>{{ $file['detail'] }}</span>
                    </td>
                    <td><span class="provider-import-native-stage">{{ $file['stage'] }}</span></td>
                    <td>{{ $file['responsible'] }}</td>
                    <td>
                      <strong>{{ $file['progress'] }}%</strong>
                      <span class="provider-import-native-progress"><i style="width: {{ $file['progress'] }}%"></i></span>
                    </td>
                    <td><button type="button">Abrir</button></td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </section>

        <section class="provider-import-native-card">
          <div class="provider-import-native-heading">
            <div>
              <h2>Solicitudes pendientes</h2>
              <p>{{ $requests->total() }} solicitudes asignadas al proveedor.</p>
            </div>
          </div>
          <div class="provider-import-native-requests">
            @forelse ($requests as $request)
              <article>
                <strong>{{ $request->external_id ?? 'Solicitud '.$request->id }}</strong>
                <span>{{ $request->patient?->full_name ?? 'Sin paciente' }} / {{ $request->medicalUnit?->name ?? 'Sin unidad' }}</span>
                @if (data_get($request->payload, 'prescription_code'))
                  <small>Receta CE: {{ data_get($request->payload, 'prescription_code') }} · {{ collect(data_get($request->payload, 'prescription_items', []))->count() }} partida(s)</small>
                @endif
                @if (data_get($request->payload, 'diagnosis'))
                  <small>Diagnostico: {{ data_get($request->payload, 'diagnosis') }}</small>
                @endif
                <form method="post" action="{{ route('provider.requests.status', [$type, $request]) }}">
                  @csrf
                  @method('patch')
                  <select name="status">
                    @foreach (['accepted', 'preparing', 'in_route', 'delivered', 'rejected', 'cancelled'] as $status)
                      <option value="{{ $status }}" @selected($request->status === $status)>{{ $statusText($status) }}</option>
                    @endforeach
                  </select>
                  <input name="notes" placeholder="Nota del proveedor">
                  <button type="submit">Actualizar</button>
                </form>
              </article>
            @empty
              <p>No hay solicitudes para este proveedor.</p>
            @endforelse
          </div>
        </section>
      </section>
    </div>
  @elseif ($type === 'chemotherapy')
    <div class="provider-chemo-native-screen">
      <aside class="provider-chemo-native-sidebar" aria-label="Navegacion proveedor quimioterapias">
        <div class="provider-chemo-native-brand">
          <span>Q</span>
          <div>
            <strong>Proveedor Quimioterapias</strong>
            <small>Central oncologica</small>
          </div>
        </div>
        <nav class="provider-chemo-native-menu">
          <a class="is-active" href="{{ route('provider.chemo.dashboard') }}"><span>M</span>Medicamentos</a>
          <a href="{{ route('provider.chemo.dashboard') }}"><span>H</span>Historial de Solicitudes</a>
          <a href="{{ route('provider.chemo.dashboard') }}"><span>R</span>Reportes</a>
        </nav>
      </aside>

      <section class="provider-chemo-native-workspace">
        @if (session('status'))
          <div class="notice success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
          <div class="notice danger">
            <strong>No se pudo actualizar.</strong>
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <header class="provider-chemo-native-topbar">
          <div>
            <p class="eyebrow">Servicio medico integral</p>
            <h1>Catalogo de medicamentos general</h1>
          </div>
          <div>
            <a href="{{ route('dashboard') }}">&larr; Atras</a>
            <time>{{ now()->format('d M Y') }}</time>
          </div>
        </header>

        <div class="provider-chemo-native-tabs">
          <a class="is-active" href="{{ route('provider.chemo.dashboard') }}">Catalogo general</a>
          <a href="{{ route('provider.chemo.dashboard') }}">Catalogos especificos</a>
        </div>

        <form class="provider-chemo-native-filters">
          <label class="search">
            <span>Q</span>
            <input placeholder="Buscar medicamento, familia o uso">
          </label>
          <label>Familia
            <select>
              <option>Todas</option>
              @foreach ($chemoCatalog->pluck('family')->unique() as $family)
                <option>{{ $family }}</option>
              @endforeach
            </select>
          </label>
        </form>

        <section class="provider-chemo-native-card">
          <div class="provider-chemo-native-heading">
            <div>
              <h2>Catalogo de medicamentos general</h2>
              <p>{{ $chemoCatalog->count() }} visibles Â· {{ $chemoCatalog->count() }} activos</p>
            </div>
            <button type="button">Ver Formato de Solicitud</button>
          </div>
          <div class="provider-chemo-native-table-scroll">
            <table class="provider-chemo-native-table">
              <thead>
                <tr>
                  <th>Activo</th>
                  <th>Estado</th>
                  <th>Familia</th>
                  <th>Medicamento</th>
                  <th>Unidad</th>
                  <th>Uso clinico / operativo</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($chemoCatalog as $item)
                  <tr>
                    <td><span class="provider-chemo-native-check">âœ“</span></td>
                    <td><span class="provider-chemo-native-status">Activo</span></td>
                    <td>{{ $item['family'] }}</td>
                    <td><strong>{{ $item['name'] }}</strong></td>
                    <td>{{ $item['unit'] }}</td>
                    <td>{{ $item['use'] }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </section>

        <section class="provider-chemo-native-card">
          <div class="provider-chemo-native-heading">
            <div>
              <h2>Solicitudes pendientes</h2>
              <p>{{ $requests->total() }} solicitudes asignadas al proveedor.</p>
            </div>
            <span>Quimioterapia</span>
          </div>
          <div class="provider-chemo-native-requests">
            @forelse ($requests as $request)
              <article>
                <strong>{{ $request->external_id ?? 'Solicitud '.$request->id }}</strong>
                <span>{{ $request->patient?->full_name ?? 'Sin paciente' }} / {{ $request->medicalUnit?->name ?? 'Sin unidad' }}</span>
                @if (data_get($request->payload, 'prescription_code'))
                  <small>Receta CE: {{ data_get($request->payload, 'prescription_code') }} · {{ collect(data_get($request->payload, 'prescription_items', []))->count() }} partida(s)</small>
                @endif
                @if (data_get($request->payload, 'diagnosis'))
                  <small>Diagnostico: {{ data_get($request->payload, 'diagnosis') }}</small>
                @endif
                <form method="post" action="{{ route('provider.requests.status', [$type, $request]) }}">
                  @csrf
                  @method('patch')
                  <select name="status">
                    @foreach (['accepted', 'preparing', 'in_route', 'delivered', 'rejected', 'cancelled'] as $status)
                      <option value="{{ $status }}" @selected($request->status === $status)>{{ $statusText($status) }}</option>
                    @endforeach
                  </select>
                  <input name="notes" placeholder="Nota del proveedor">
                  <button type="submit">Actualizar</button>
                </form>
              </article>
            @empty
              <p>No hay solicitudes para este proveedor.</p>
            @endforelse
          </div>
        </section>
      </section>
    </div>
  @else
    @php
      $activeProviderSection = request('section', 'history');
      $activeProviderScope = request('scope', 'integral');
      if (! in_array($activeProviderScope, ['integral', 'npt', 'oncology'], true)) {
          $activeProviderScope = 'integral';
      }
      $validProviderSections = ['history', 'pending', 'prices', 'messengers', 'shipments'];
      if (! in_array($activeProviderSection, $validProviderSections, true)) {
          $activeProviderSection = 'history';
      }

      $providerRows = collect([
          ['service' => 'Oncologica', 'folio' => 'QT-18', 'hospital' => 'Hospital General Norte', 'patient' => 'Torres Pineda, Mariana', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-19', 'hospital' => 'Hospital General Norte', 'patient' => 'Castillo Vega, Roberto', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-20', 'hospital' => 'Hospital General Norte', 'patient' => 'Flores Mendoza, Renata', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-21', 'hospital' => 'Hospital General Norte', 'patient' => 'Sanchez Ruiz, Diego', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-22', 'hospital' => 'Hospital General Norte', 'patient' => 'Rodriguez Luna, Camila', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'Oncologica', 'folio' => 'QT-23', 'hospital' => 'Hospital General Norte', 'patient' => 'Garcia Moreno, Valeria', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '30/06/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Pendiente', 'delivery_state' => 'warning'],
          ['service' => 'NPT', 'folio' => 'NPT-17', 'hospital' => 'Hospital General Bajio', 'patient' => 'Hernandez, Arturo', 'doctor' => 'Carter Jimmy', 'auth' => ['Enfermeria', 'Farm. Intra.'], 'date' => '27/05/2026', 'obs' => 'Con Observacion', 'obs_state' => 'danger', 'central' => 'En Revision', 'central_state' => 'warning', 'delivery' => 'Pendiente', 'delivery_state' => 'muted'],
          ['service' => 'NPT', 'folio' => 'NPT-16', 'hospital' => 'Hospital General Norte', 'patient' => 'Hernandez Villanueva, Javier Alejandro', 'doctor' => 'Carter Jimmy', 'auth' => ['Enfermeria', 'Farm. Intra.'], 'date' => '27/05/2026', 'obs' => 'Sin Observacion', 'obs_state' => 'neutral', 'central' => 'Autorizada', 'central_state' => 'success', 'delivery' => 'Entregada', 'delivery_state' => 'success'],
          ['service' => 'Oncologica', 'folio' => 'QT-17', 'hospital' => 'Centro de Alta Especialidad Regio', 'patient' => 'Hernandez, Arturo', 'doctor' => 'Carter Jimmy', 'auth' => ['Centro Onc.', 'Farm. Intra.'], 'date' => '27/05/2026', 'obs' => 'Con Observacion', 'obs_state' => 'success', 'central' => 'En Revision', 'central_state' => 'warning', 'delivery' => 'Pendiente', 'delivery_state' => 'muted'],
      ]);

      $requestRows = $requests->map(function ($request) use ($statusText, $type) {
          $centralStatus = $request->status === 'rejected'
              ? 'Rechazada'
              : (in_array($request->status, ['accepted', 'preparing', 'in_route', 'delivered'], true) ? 'Autorizada' : 'En Revision');
          $prescriptionItems = collect(data_get($request->payload, 'prescription_items', []));
          $diagnosis = data_get($request->payload, 'diagnosis');
          $prescriptionCode = data_get($request->payload, 'prescription_code');
          $serviceName = data_get($request->payload, 'service') ?: ($type === 'chemotherapy' ? 'Oncologica' : 'NPT');
          $observations = $request->status === 'rejected'
              ? 'Con Observacion'
              : trim(collect([$prescriptionCode ? 'Receta CE '.$prescriptionCode : null, $diagnosis, $prescriptionItems->count() ? $prescriptionItems->count().' partida(s)' : null])->filter()->join(' / '));

          return [
              'service' => str_contains(strtolower($serviceName), 'npt') || str_contains(strtolower($serviceName), 'nutric') ? 'NPT' : 'Oncologica',
              'folio' => $request->external_id ?? 'Solicitud '.$request->id,
              'hospital' => $request->medicalUnit?->name ?? 'Hospital sin unidad',
              'patient' => $request->patient?->full_name ?? 'Sin paciente',
              'doctor' => data_get($request->payload, 'doctor') ?: 'Sin medico',
              'auth' => $prescriptionCode ? ['Consulta Ext.', 'Central'] : ['Enfermeria', 'Farm. Intra.'],
              'date' => $request->requested_at?->format('d/m/Y') ?? 'Sin fecha',
              'obs' => $observations !== '' ? $observations : 'Sin Observacion',
              'obs_state' => $request->status === 'rejected' ? 'danger' : ($prescriptionCode || $diagnosis ? 'success' : 'neutral'),
              'central' => $centralStatus,
              'central_state' => match ($centralStatus) {
                  'Autorizada' => 'success',
                  'Rechazada' => 'danger',
                  default => 'warning',
              },
              'delivery' => in_array($request->status, ['delivered'], true) ? 'Entregada' : 'Pendiente',
              'delivery_state' => in_array($request->status, ['delivered'], true) ? 'success' : 'muted',
              'request_id' => $request->id,
              'detail' => data_get($request->payload, 'clinical_summary') ?: data_get($request->payload, 'diagnosis') ?: 'Solicitud registrada en la central de mezclas.',
              'location_url' => data_get($request->payload, 'location_url'),
              'remission' => data_get($request->payload, 'remission') ?: 'Remision pendiente de carga.',
          ];
      });

      $providerRows = $providerRows->map(fn ($row) => array_merge([
          'request_id' => null,
          'detail' => $row['obs'] === 'Sin Observacion' ? 'La solicitud no tiene observaciones registradas.' : 'La solicitud requiere revision antes de continuar.',
          'location_url' => 'https://www.google.com/maps/search/?api=1&query=19.4125,-99.1528',
          'remission' => $row['delivery'] === 'Entregada' ? 'Remision de entrega registrada y confirmada.' : 'Remision pendiente de entrega.',
      ], $row));

      $allProviderRows = $requestRows->concat($providerRows);
      $pendingProviderRows = $allProviderRows->filter(fn ($row) => $row['central'] !== 'Autorizada' || $row['delivery'] !== 'Entregada')->values();

    $scopedProviderRows = match ($activeProviderScope) {
        'npt' => $allProviderRows->where('service', 'NPT')->values(),
        'oncology' => $allProviderRows->where('service', 'Oncologica')->values(),
        default => $allProviderRows,
    };
    $scopedPendingProviderRows = $scopedProviderRows->filter(fn ($row) => $row['central'] !== 'Autorizada' || $row['delivery'] !== 'Entregada')->values();
    $displayProviderRows = $activeProviderSection === 'history' ? $scopedProviderRows : $scopedPendingProviderRows;
@endphp

    <div class="provider-native-screen">
      <aside class="provider-native-sidebar" aria-label="Navegacion proveedor">
        <div class="provider-native-brand">
          <span>P</span>
          <div>
            <strong>Proveedor Integral</strong>
            <small>Central de mezclas NPT y oncologicas</small>
          </div>
        </div>

        <nav class="provider-native-menu">
          <p>Vista integral</p>
          <strong>NPT y Oncologicas</strong>
          <a class="{{ $activeProviderScope === 'integral' && $activeProviderSection === 'history' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'history', 'scope' => 'integral']) }}"><span>H</span>Historial de Solicitudes</a>
          <a class="{{ $activeProviderScope === 'integral' && $activeProviderSection === 'pending' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'pending', 'scope' => 'integral']) }}"><span>*</span>Solicitudes Pendientes</a>
          <a class="{{ $activeProviderSection === 'prices' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'prices']) }}"><span>=</span>Catalogo y Lista de Precios</a>
          <a class="{{ $activeProviderSection === 'messengers' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'messengers']) }}"><span>Cb</span>Mensajeros</a>
          <a class="{{ $activeProviderSection === 'shipments' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'shipments']) }}"><span>Cb</span>Envios</a>

          <div class="provider-native-menu-divider"></div>
          <p>Nutricion parenteral</p>
          <strong>NPT</strong>
          <a class="{{ $activeProviderScope === 'npt' && $activeProviderSection === 'history' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'history', 'scope' => 'npt']) }}"><span>H</span>Historial de Solicitudes</a>
          <a class="{{ $activeProviderScope === 'npt' && $activeProviderSection === 'pending' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'pending', 'scope' => 'npt']) }}"><span>*</span>Solicitudes Pendientes</a>

          <div class="provider-native-menu-divider"></div>
          <p>Quimioterapia</p>
          <strong>Oncologicas</strong>
          <a class="{{ $activeProviderScope === 'oncology' && $activeProviderSection === 'history' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'history', 'scope' => 'oncology']) }}"><span>H</span>Historial de Solicitudes</a>
          <a class="{{ $activeProviderScope === 'oncology' && $activeProviderSection === 'pending' ? 'is-active' : '' }}" href="{{ route('provider.npt.dashboard', ['section' => 'pending', 'scope' => 'oncology']) }}"><span>*</span>Solicitudes Pendientes</a>

          <div class="provider-native-menu-divider"></div>
          <a href="{{ route('provider.npt.dashboard', ['section' => 'history']) }}"><span>R</span>Reportes</a>
        </nav>
      </aside>

      <section class="provider-native-workspace">
        @if (session('status'))
          <div class="notice success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
          <div class="notice danger">
            <strong>No se pudo actualizar.</strong>
            <ul>
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <header class="provider-native-topbar">
          <div>
            <p class="eyebrow">
              @if ($activeProviderSection === 'messengers')
                Proveedor integral / Mensajeros
              @elseif ($activeProviderSection === 'prices' || $activeProviderSection === 'shipments')
                Vista integral / NPT y Oncologicas
              @else
                {{ $activeProviderScope === 'npt' ? 'Proveedor NPT / Central de mezclas' : ($activeProviderScope === 'oncology' ? 'Proveedor Oncologico / Central de mezclas' : 'Proveedor integral / Central de mezclas NPT y Oncologicas') }}
              @endif
            </p>
            <h1>
              @if ($activeProviderSection === 'prices')
                Catalogo y Lista de Precios
              @elseif ($activeProviderSection === 'messengers')
                Mensajeros
              @elseif ($activeProviderSection === 'shipments')
                Envios
              @else
                {{ $activeProviderScope === 'npt' ? 'Historial de Solicitudes' : ($activeProviderScope === 'oncology' ? 'Historial de Solicitudes Oncologicas' : 'NPT y Oncologicas') }}
              @endif
            </h1>
          </div>
          <div class="provider-native-actions">
            <a href="{{ route('dashboard') }}">&larr; Atras</a>
            <time>{{ now()->locale('es')->translatedFormat('d \d\e F \d\e Y') }}</time>
          </div>
        </header>

        @if (in_array($activeProviderSection, ['history', 'pending'], true))
          <div class="provider-native-history-page-heading">
            <div>
              <h2>{{ $activeProviderScope === 'npt' ? 'Historial de Solicitudes Nutricion Parenteral' : ($activeProviderScope === 'oncology' ? 'Historial de Solicitudes Oncologicas' : 'NPT y Oncologicas') }}</h2>
              <p>{{ $activeProviderScope === 'npt' ? 'Solicitudes de nutricion parenteral enviadas por hospitales' : ($activeProviderScope === 'oncology' ? 'Solicitudes oncologicas enviadas por hospitales' : 'Vista integral de solicitudes de Nutricion Parenteral y Oncologia') }}</p>
            </div>
            <div class="provider-native-kpi-pills">
              @if ($activeProviderScope === 'npt')
                <a class="provider-native-area-link" href="{{ route('operational.dashboard', ['area' => 'nursing', 'section' => 'history']) }}">↗ Area Operativa</a>
              @endif
              <span class="provider-native-count-pill">{{ $scopedProviderRows->count() }} solicitudes</span>
              <span class="provider-native-count-pill">{{ $scopedPendingProviderRows->count() }} {{ $activeProviderScope === 'npt' ? 'en revision' : 'pendientes' }}</span>
            </div>
          </div>

          <section class="provider-native-card">
            <div class="provider-native-heading">
              <div>
                <h2>{{ $activeProviderSection === 'history' ? ($activeProviderScope === 'npt' ? 'Historial de Solicitudes Nutricion Parenteral' : 'Historial de Solicitudes') : 'Solicitudes Pendientes' }}</h2>
                <p>{{ $activeProviderSection === 'history' ? ($activeProviderScope === 'npt' ? 'Solicitudes de nutricion parenteral enviadas por hospitales' : 'Todas las solicitudes NPT y Oncologicas') : 'Solicitudes que siguen en revision por central' }}</p>
              </div>
            </div>
                        <div class="provider-native-table-wrap">
                            <table class="provider-native-table">
                                <thead>
                                    <tr>
                                        @if ($activeProviderScope === 'integral')<th>Servicio</th>@endif
                                        <th>Folio</th>
                                        <th>Hospital</th>
                                        <th>Paciente</th>
                                        <th>Medico</th>
                                        <th>Autorizaciones</th>
                                        <th>Fecha</th>
                                        <th>Observaciones</th>
                                        <th>Estado de Central</th>
                                        @if ($activeProviderScope === 'integral')<th>Estatus de Entrega</th>@endif
                                        <th>Visualizar Solicitud</th>
                                        <th>Ubicacion</th>
                                        <th>Remision de entrega</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($displayProviderRows as $row)
                                        @php
                                          $centralEnabled = collect($row['auth'])->every(fn ($auth) => ! str_contains($auth, 'Pendiente'));
                                          $rowKey = \Illuminate\Support\Str::slug($row['folio']);
                                        @endphp
                                        <tr data-provider-request-row="{{ $rowKey }}">
                                            @if ($activeProviderScope === 'integral')<td><span class="provider-native-service {{ $row['service'] === 'NPT' ? 'is-npt' : 'is-oncology' }}">{{ $row['service'] }}</span></td>@endif
                                            <td><strong>{{ $row['folio'] }}</strong></td>
                                            <td><strong>{{ $row['hospital'] }}</strong></td>
                                            <td><strong>{{ $row['patient'] }}</strong></td>
                                            <td>{{ $row['doctor'] }}</td>
                                            <td>
                                                @foreach ($row['auth'] as $auth)
                                                    <span class="provider-native-mini-badge {{ str_contains($auth, 'Farm') ? 'is-warning' : 'is-success' }}">{{ $auth }}</span>
                                                @endforeach
                                            </td>
                                            <td>{{ $row['date'] }}</td>
                                            <td><button class="provider-native-observation is-{{ $row['obs_state'] }}" type="button" data-provider-dialog="observation-{{ $rowKey }}">{{ $row['obs'] }}</button></td>
                                            <td>
                                              @if ($row['request_id'] && $centralEnabled)
                                                <form method="post" action="{{ route('provider.requests.status', [$type, $row['request_id']]) }}" data-provider-central-form>
                                                  @csrf
                                                  @method('PATCH')
                                                  <select class="provider-native-central-select is-{{ $row['central_state'] }}" name="status" aria-label="Estado de Central {{ $row['folio'] }}">
                                                    <option class="is-warning" value="requested" @selected($row['central'] === 'En Revision')>En Revision</option>
                                                    <option class="is-success" value="accepted" @selected($row['central'] === 'Autorizada')>Autorizada</option>
                                                    <option class="is-danger" value="rejected" @selected($row['central'] === 'Rechazada')>Rechazada</option>
                                                  </select>
                                                </form>
                                              @else
                                                <select class="provider-native-central-select is-{{ $row['central_state'] }}" aria-label="Estado de Central {{ $row['folio'] }}" @disabled(! $centralEnabled)>
                                                  <option class="is-warning" @selected($row['central'] === 'En Revision')>En Revision</option>
                                                  <option class="is-success" @selected($row['central'] === 'Autorizada')>Autorizada</option>
                                                  <option class="is-danger" @selected($row['central'] === 'Rechazada')>Rechazada</option>
                                                </select>
                                              @endif
                                            </td>
                                            @if ($activeProviderScope === 'integral')<td><span class="provider-native-status-pill is-{{ $row['delivery_state'] }}">{{ $row['delivery'] }}</span></td>@endif
                                            <td><button class="provider-native-cta" type="button" data-provider-dialog="request-{{ $rowKey }}">Ver Formato de Solicitud <span>&rsaquo;</span></button></td>
                                            <td>
                                              @if ($row['location_url'])
                                                <a class="provider-native-view-button is-location" href="{{ $row['location_url'] }}" target="_blank" rel="noopener">○ Ver</a>
                                              @else
                                                <button class="provider-native-view-button is-location" type="button" disabled>○ Ver</button>
                                              @endif
                                            </td>
                                            <td><button class="provider-native-view-button is-green" type="button" data-provider-dialog="remission-{{ $rowKey }}">○ Ver</button></td>
                                        </tr>
                                        <tr class="provider-native-dialog-row">
                                          <td colspan="{{ $activeProviderScope === 'integral' ? 13 : 11 }}">
                                            <dialog id="observation-{{ $rowKey }}" class="provider-native-dialog">
                                              <header><strong>Observaciones · {{ $row['folio'] }}</strong><button type="button" data-provider-dialog-close>Cerrar</button></header>
                                              <p>{{ $row['detail'] }}</p>
                                            </dialog>
                                            <dialog id="request-{{ $rowKey }}" class="provider-native-dialog">
                                              <header><strong>Formato de solicitud · {{ $row['folio'] }}</strong><button type="button" data-provider-dialog-close>Cerrar</button></header>
                                              <dl>
                                                <div><dt>Servicio</dt><dd>{{ $row['service'] }}</dd></div>
                                                <div><dt>Hospital</dt><dd>{{ $row['hospital'] }}</dd></div>
                                                <div><dt>Paciente</dt><dd>{{ $row['patient'] }}</dd></div>
                                                <div><dt>Medico</dt><dd>{{ $row['doctor'] }}</dd></div>
                                                <div><dt>Fecha</dt><dd>{{ $row['date'] }}</dd></div>
                                                <div><dt>Estado</dt><dd>{{ $row['central'] }}</dd></div>
                                              </dl>
                                              <p>{{ $row['detail'] }}</p>
                                            </dialog>
                                            <dialog id="remission-{{ $rowKey }}" class="provider-native-dialog">
                                              @php
                                                $isOncologyRemission = $row['service'] === 'Oncologica';
                                                $remissionProduct = $isOncologyRemission ? 'Docetaxel' : 'Mezcla de nutricion parenteral';
                                                $remissionQuantity = $isOncologyRemission ? '1 mg (1 frasco facturado)' : '1 bolsa (1 preparacion)';
                                                $remissionPriceType = $isOncologyRemission ? 'Precio unitario por frasco' : 'Precio unitario por mezcla';
                                                $remissionAmount = $isOncologyRemission ? '$100.00' : '$1,250.00';
                                              @endphp
                                              <header class="provider-native-remission-header">
                                                <div>
                                                  <small>REMISION DE ENTREGA</small>
                                                  <strong>Remision de entrega {{ $row['folio'] }}</strong>
                                                  <span>{{ $row['hospital'] }} - {{ $row['date'] }} - {{ $row['service'] }}</span>
                                                </div>
                                                <button type="button" data-provider-dialog-close>Cerrar</button>
                                              </header>
                                              <div class="provider-native-remission-body">
                                                <div class="provider-native-remission-summary">
                                                  <div>
                                                    <small>{{ strtoupper($row['service']) }}</small>
                                                    <strong>Productos entregados y precios</strong>
                                                    <span>Precio de referencia {{ $row['service'] }}: {{ $remissionAmount }} por {{ $isOncologyRemission ? 'frasco' : 'mezcla' }}</span>
                                                  </div>
                                                  <span class="provider-native-remission-status">{{ $row['central'] }}</span>
                                                </div>
                                                <dl class="provider-native-remission-data">
                                                  <div><dt>Folio</dt><dd>{{ $row['folio'] }}</dd></div>
                                                  <div><dt>Hospital</dt><dd>{{ $row['hospital'] }}</dd></div>
                                                  <div><dt>Paciente</dt><dd>{{ $row['patient'] }}</dd></div>
                                                  <div><dt>Medico</dt><dd>{{ $row['doctor'] }}</dd></div>
                                                  <div><dt>Fecha de entrega</dt><dd>{{ $row['date'] }}, 10:40 a.m.</dd></div>
                                                  <div><dt>Total remision</dt><dd>{{ $remissionAmount }}</dd></div>
                                                </dl>
                                                <div class="provider-native-remission-table-wrap">
                                                  <table class="provider-native-remission-table">
                                                    <thead>
                                                      <tr>
                                                        <th>Producto</th>
                                                        <th>Cantidad entregada</th>
                                                        <th>Tipo de precio</th>
                                                        <th>Precio unitario</th>
                                                        <th>Importe</th>
                                                      </tr>
                                                    </thead>
                                                    <tbody>
                                                      <tr>
                                                        <td><strong>{{ $remissionProduct }}</strong><small>Precio de referencia del catalogo</small></td>
                                                        <td>{{ $remissionQuantity }}</td>
                                                        <td>{{ $remissionPriceType }}</td>
                                                        <td>{{ $remissionAmount }}</td>
                                                        <td>{{ $remissionAmount }}</td>
                                                      </tr>
                                                    </tbody>
                                                    <tfoot>
                                                      <tr><th colspan="4">Subtotal</th><td>{{ $remissionAmount }}</td></tr>
                                                      <tr><th colspan="4">Total</th><td>{{ $remissionAmount }}</td></tr>
                                                    </tfoot>
                                                  </table>
                                                </div>
                                                <p class="provider-native-remission-note">Hay productos sin precio en la lista asignada. Se uso el precio de referencia del catalogo para mantener la remision visible.</p>
                                                <div class="provider-native-remission-signatures">
                                                  <span>Entrega proveedor integral</span>
                                                  <span>Recibe area operativa</span>
                                                </div>
                                              </div>
                                            </dialog>
                                          </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
          </section>
        @elseif ($activeProviderSection === 'prices')
          <section class="provider-native-card">
            <div class="provider-native-heading">
              <div>
                <h2>Catalogo y Lista de Precios</h2>
                <p>Catalogos generales y listas asignadas por institucion o unidad</p>
              </div>
            </div>
            <div class="provider-native-catalogs">
              <article class="is-selected">
                <span>N</span>
                <div>
                  <strong>Catalogo NPT</strong>
                  <p>Administrar catalogo general de Nutricion Parenteral</p>
                </div>
              </article>
              <article>
                <span>O</span>
                <div>
                  <strong>Catalogo Oncologicas</strong>
                  <p>Administrar catalogo general de medicamentos de oncologia</p>
                </div>
              </article>
            </div>
          </section>

          <section class="provider-native-card provider-native-price-card">
            <div class="provider-native-heading">
              <div>
                <h2>Listas de precios</h2>
                <p>Cada lista se relaciona con un catalogo y puede asignarse a instituciones o unidades.</p>
              </div>
              <div class="provider-native-price-actions">
                <label>Ver listas de precios
                  <select>
                    <option>Todas</option>
                    <option>NPT</option>
                    <option>Oncologicas</option>
                  </select>
                </label>
                <button type="button">Crear lista de precios</button>
                <button type="button">Importar lista de precios</button>
              </div>
            </div>
            <div class="provider-native-empty">
              Aun no hay listas de precios creadas.
            </div>
          </section>
        @elseif ($activeProviderSection === 'messengers')
          @php
            $providerMessengers = $messengers->map(function ($profile) {
              $name = $profile->user?->name ?: 'Mensajero sin usuario';
              $parts = preg_split('/\s+/', trim($name), 2);
              $routes = $profile->deliveryRoutes->map(function ($route) {
                return [
                  'number' => $route->route_code ?: (string) $route->id,
                  'name' => $route->destination ?: 'Ruta sin destino',
                  'time' => $route->scheduled_at?->format('H:i') ?: 'Horario pendiente',
                  'pending' => in_array($route->status, ['delivered', 'cancelled'], true) ? 0 : 1,
                  'units' => $route->providerRequest?->medical_unit_id ? 1 : 0,
                ];
              })->values()->all();

              return [
                'initials' => collect($parts)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join(''),
                'name' => $name,
                'status' => match ($profile->status) {
                  'active' => 'Disponible',
                  'in_route' => 'En ruta',
                  default => ucfirst(str_replace('_', ' ', $profile->status)),
                },
                'tone' => $profile->status === 'active' ? 'success' : 'danger',
                'routes' => $profile->deliveryRoutes->map(fn ($route) => trim(($route->route_code ?: 'Ruta').' - '.($route->destination ?: 'Destino pendiente')))->join(', ') ?: 'Sin rutas activas asignadas',
                'first' => $parts[0] ?? $name,
                'last' => $parts[1] ?? '',
                'unit' => $profile->vehicle ?: 'Unidad no registrada',
                'shift' => data_get($profile->metadata, 'shift', 'Sin turno registrado'),
                'phone' => $profile->phone ?: 'Sin telefono registrado',
                'count' => $profile->deliveryRoutes->count().' ruta(s) asignada(s)',
                'route_rows' => $routes,
              ];
            });
          @endphp
          <section class="provider-native-card" data-provider-messenger-list>
            <div class="provider-native-heading">
              <div>
                <h2>Mensajeros</h2>
                <p>Gestion de mensajeros, rutas asignadas y entregas por turno.</p>
              </div>
              <button type="button">Dar de alta mensajero</button>
            </div>
            <div class="provider-native-info-banner">La ruta puede cambiar dependiendo de la demanda.</div>
            <div class="provider-native-messenger-grid">
              @foreach ($providerMessengers as $messengerIndex => $messenger)
                <article class="provider-native-messenger-card">
                  <span class="provider-native-avatar is-{{ $messenger['tone'] }}">{{ $messenger['initials'] }}</span>
                  <div class="provider-native-messenger-body">
                    <strong>{{ $messenger['name'] }}</strong>
                    <em>{{ $messenger['status'] }}</em>
                    <hr>
                    <p>Rutas asignadas</p>
                    <b>{{ $messenger['routes'] }}</b>
                    <small>Nombre: {{ $messenger['first'] }}</small>
                    <small>Apellido: {{ $messenger['last'] }}</small>
                    <small>Unidad que maneja: {{ $messenger['unit'] }}</small>
                    <small>Turno asignado: {{ $messenger['shift'] }}</small>
                    <small>Telefono: {{ $messenger['phone'] }}</small>
                    <small>{{ $messenger['count'] }}</small>
                    <div class="provider-native-card-actions">
                      <button type="button" data-provider-messenger-open="{{ $messengerIndex }}">Ver</button>
                      <button type="button">Editar</button>
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
          </section>
          @foreach ($providerMessengers as $messengerIndex => $messenger)
            @php
              $messengerRoutes = $messenger['route_rows'];
            @endphp
            <section class="provider-native-messenger-detail" data-provider-messenger-detail="{{ $messengerIndex }}" hidden>
              <header class="provider-native-messenger-detail-hero">
                <button type="button" data-provider-messenger-back>&larr; Regresar</button>
                <span class="provider-native-avatar is-{{ $messenger['tone'] }}">{{ $messenger['initials'] }}</span>
                <div>
                  <small>INFORMACION DEL MENSAJERO</small>
                  <strong>{{ $messenger['name'] }}</strong>
                  <p>{{ $messenger['phone'] }} · {{ $messenger['shift'] }} · {{ $messenger['unit'] }}</p>
                </div>
                <aside><small>RUTAS ASIGNADAS</small><strong>{{ $messenger['routes'] }}</strong></aside>
                <button type="button">Editar informacion</button>
              </header>

              <section class="provider-native-messenger-detail-card">
                <header><strong>Datos generales</strong><small>Informacion administrativa del mensajero.</small></header>
                <dl>
                  <div><dt>Nombre</dt><dd>{{ $messenger['first'] }}</dd></div>
                  <div><dt>Apellido</dt><dd>{{ $messenger['last'] }}</dd></div>
                  <div><dt>Telefono</dt><dd>{{ $messenger['phone'] }}</dd></div>
                  <div><dt>Unidad que maneja</dt><dd>{{ $messenger['unit'] }}</dd></div>
                  <div><dt>Turno asignado</dt><dd>{{ $messenger['shift'] }}</dd></div>
                  <div><dt>Ruta base</dt><dd>{{ data_get($messengerRoutes, '0.name', 'Sin ruta base') }}</dd></div>
                  <div><dt>Estado</dt><dd>{{ $messenger['status'] }}</dd></div>
                </dl>
              </section>

              <section class="provider-native-messenger-detail-card">
                <header><strong>Rutas asignadas</strong><small>Rutas activas relacionadas al mensajero desde Envios.</small></header>
                @foreach ($messengerRoutes as $routeIndex => $route)
                  <article class="provider-native-assigned-route">
                    <div class="provider-native-assigned-route-heading">
                      <div>
                        <small>RUTA NO. {{ $route['number'] }}</small>
                        <strong>{{ $route['name'] }}</strong>
                        <p>{{ $route['time'] }} - {{ $route['pending'] }} solicitudes pendientes</p>
                        <span>NUMERO DE RUTA: {{ $route['number'] }}</span>
                        <span>QR: QR-RUTA-{{ $route['number'] }}</span>
                        <span>{{ $route['units'] }} unidades asignadas</span>
                      </div>
                      <button type="button" @disabled($route['pending'] === 0)>Comenzar ruta</button>
                    </div>
                    <table>
                      <thead><tr><th>Cliente</th><th>Hospital</th><th>Direccion</th><th>Paciente</th><th>Entrega</th></tr></thead>
                      <tbody>
                        @if ($route['pending'] === 0)
                          <tr><td colspan="5" class="provider-native-route-empty">Sin solicitudes pendientes para esta ruta.</td></tr>
                        @else
                          @foreach ($displayProviderRows->take(min($route['pending'], 6)) as $delivery)
                            <tr>
                              <td><strong>IMSS Bienestar Estado de Mexico</strong><small>{{ $delivery['folio'] }}</small></td>
                              <td>{{ $delivery['hospital'] }}</td>
                              <td>Entrada de proveedores, {{ $delivery['hospital'] }}, Ciudad de Mexico</td>
                              <td>{{ $delivery['patient'] }}</td>
                              <td><span>Enviada</span></td>
                            </tr>
                          @endforeach
                        @endif
                      </tbody>
                    </table>
                  </article>
                @endforeach
              </section>
            </section>
          @endforeach
        @elseif ($activeProviderSection === 'shipments')
          @php
            $providerShipmentRoutes = $deliveryRoutes->map(function ($route) {
              $routeNumber = $route->route_code ?: (string) $route->id;
              $pending = in_array($route->status, ['delivered', 'cancelled'], true) ? 0 : 1;

              return [
                'id' => $route->id,
                'status' => $route->status,
                'name' => $route->destination ?: 'Ruta sin destino',
                'badge' => 'Ruta No. '.$routeNumber,
                'number' => $routeNumber,
                'subtitle' => $route->origin ?: 'Origen pendiente',
                'units' => $route->providerRequest?->medical_unit_id ? '1' : '0',
                'mixes' => (string) $pending,
                'pending' => (string) $pending,
                'schedule' => $route->scheduled_at?->format('H:i') ?: 'Pendiente de asignacion',
                'messenger' => $route->messenger?->user?->name ?: 'Sin asignar',
              ];
            });
          @endphp
          <section class="provider-native-card">
            <div class="provider-native-heading">
              <div>
                <h2>Envios</h2>
                <p>Solicitudes en proceso de entrega y gestion de rutas diarias.</p>
              </div>
              <button type="button">Historial de Entregas</button>
            </div>
          </section>

          <section class="provider-native-card">
            <div data-provider-shipment-list>
            <div class="provider-native-heading">
              <div>
                <h2>Solicitudes en proceso de entrega</h2>
                <p>Solicitudes autorizadas por central listas para asignacion de ruta.</p>
              </div>
              <button type="button">Catalogo de Rutas</button>
            </div>
            <div class="provider-native-panel-toolbar">
              <label class="provider-native-select-block">Filtrar por institucion
                <select><option>Todas las instituciones</option></select>
              </label>
            </div>
            <div class="provider-native-routes-grid">
              @foreach ($providerShipmentRoutes as $routeIndex => $route)
                <article class="provider-native-route-card">
                  <div>
                    <span class="provider-native-route-icon">#</span>
                    <span class="provider-native-route-badge">{{ $route['badge'] }}</span>
                  </div>
                  <h3>{{ $route['name'] }}</h3>
                  <p>{{ $route['subtitle'] }}</p>
                  <div class="provider-native-route-stats">
                    <span><small>Unidades</small><b>{{ $route['units'] }}</b></span>
                    <span><small>Mezclas totales del dia</small><b>{{ $route['mixes'] }}</b></span>
                    <span><small>Pendientes de entregar</small><b>{{ $route['pending'] }}</b></span>
                    <span><small>Horario</small><b>{{ $route['schedule'] }}</b></span>
                    <span><small>Mensajero</small><b>{{ $route['messenger'] }}</b></span>
                  </div>
                  <button class="provider-native-route-button" type="button" data-provider-shipment-open="{{ $routeIndex }}">Abrir detalle</button>
                </article>
              @endforeach
            </div>
            </div>

            @foreach ($providerShipmentRoutes as $routeIndex => $route)
              <div class="provider-native-shipment-detail" data-provider-shipment-detail="{{ $routeIndex }}" hidden>
                <header>
                  <div>
                    <small>DETALLE DE RUTA</small>
                    <strong>Solicitudes pendientes de entregar</strong>
                    <p>Listado de solicitudes relacionadas a la ruta seleccionada.</p>
                  </div>
                  <button type="button" data-provider-shipment-back>&larr; Regresar</button>
                </header>
                <article class="provider-native-shipment-route-summary">
                  <div class="provider-native-route-card">
                    <div><span class="provider-native-route-icon">▦</span><span class="provider-native-route-badge">{{ $route['badge'] }}</span></div>
                    <h3>{{ $route['name'] }}</h3>
                    <p>{{ $route['subtitle'] }}</p>
                    <div class="provider-native-route-stats">
                      <span><small>Unidades</small><b>{{ $route['units'] }}</b></span>
                      <span><small>Mezclas totales del dia</small><b>{{ $route['mixes'] }}</b></span>
                      <span><small>Pendientes de entregar</small><b>{{ $route['pending'] }}</b></span>
                      <span><small>Horario</small><b>{{ $route['schedule'] }}</b></span>
                      <span><small>Mensajero</small><b>{{ $route['messenger'] }}</b></span>
                    </div>
                  </div>
                </article>
                <section class="provider-native-shipment-pending">
                  <header>
                    <div><strong>Solicitudes pendientes de entregar</strong><small>Mezclas autorizadas por central que aun no tienen confirmacion de entrega.</small></div>
                    @if ((int) $route['pending'] > 0)
                      <form method="post" action="{{ route('provider.delivery-routes.status', [$type, $route['id']]) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $route['status'] === 'in_route' ? 'delivered' : 'in_route' }}">
                        <input type="hidden" name="notes" value="{{ $route['status'] === 'in_route' ? 'Entrega confirmada desde el portal del proveedor.' : 'Ruta enviada desde el portal del proveedor.' }}">
                        <button type="submit">{{ $route['status'] === 'in_route' ? 'Confirmar entrega' : 'Enviar' }}</button>
                      </form>
                    @else
                      <button type="button" disabled>Entregada</button>
                    @endif
                  </header>
                  @if ((int) $route['pending'] === 0)
                    <p>Sin solicitudes pendientes de entregar en esta ruta.</p>
                  @else
                    <table>
                      <thead><tr><th>Folio</th><th>Hospital</th><th>Paciente</th><th>Servicio</th><th>Estado</th></tr></thead>
                      <tbody>
                        @foreach ($displayProviderRows->take(min((int) $route['pending'], 6)) as $delivery)
                          <tr>
                            <td><strong>{{ $delivery['folio'] }}</strong></td>
                            <td>{{ $delivery['hospital'] }}</td>
                            <td>{{ $delivery['patient'] }}</td>
                            <td>{{ $delivery['service'] }}</td>
                            <td><span>Pendiente</span></td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  @endif
                </section>
              </div>
            @endforeach
          </section>
        @endif
      </section>
    </div>
  @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const messengerList = document.querySelector('[data-provider-messenger-list]');
  const messengerDetails = document.querySelectorAll('[data-provider-messenger-detail]');

  document.querySelectorAll('[data-provider-messenger-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      if (messengerList) messengerList.hidden = true;
      messengerDetails.forEach((detail) => {
        detail.hidden = detail.dataset.providerMessengerDetail !== trigger.dataset.providerMessengerOpen;
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('[data-provider-messenger-back]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      messengerDetails.forEach((detail) => { detail.hidden = true; });
      if (messengerList) messengerList.hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  const shipmentList = document.querySelector('[data-provider-shipment-list]');
  const shipmentDetails = document.querySelectorAll('[data-provider-shipment-detail]');

  document.querySelectorAll('[data-provider-shipment-open]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      if (shipmentList) shipmentList.hidden = true;
      shipmentDetails.forEach((detail) => {
        detail.hidden = detail.dataset.providerShipmentDetail !== trigger.dataset.providerShipmentOpen;
      });
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('[data-provider-shipment-back]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      shipmentDetails.forEach((detail) => { detail.hidden = true; });
      if (shipmentList) shipmentList.hidden = false;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  });

  document.querySelectorAll('[data-provider-dialog]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const dialog = document.getElementById(trigger.dataset.providerDialog);
      if (dialog && typeof dialog.showModal === 'function') dialog.showModal();
    });
  });

  document.querySelectorAll('[data-provider-dialog-close]').forEach((trigger) => {
    trigger.addEventListener('click', () => trigger.closest('dialog')?.close());
  });

  document.querySelectorAll('.provider-native-dialog').forEach((dialog) => {
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
  });

  document.querySelectorAll('[data-provider-central-form] select').forEach((select) => {
    select.addEventListener('change', () => select.form.requestSubmit());
  });
});
</script>
@endpush
