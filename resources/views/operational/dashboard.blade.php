@extends('layouts.app', ['title' => 'Modulo operativo'])

@section('body_class', 'operational-native-body')

@php
  $statusLabels = [
    'draft' => 'Borrador',
    'requested' => 'Pendiente',
    'accepted' => 'Aprobada',
    'authorized' => 'Aprobada',
    'approved' => 'Aprobada',
    'dispensed' => 'Dispensada',
    'preparing' => 'Preparada',
    'ready' => 'Inspeccionada',
    'in_route' => 'En ruta',
    'delivered' => 'Entregada',
    'rejected' => 'No aprobada',
    'received' => 'Pendiente',
    'materialized' => 'Pendiente',
    'materialization_failed' => 'Error de integración',
    'cancelled' => 'Cancelada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $areaMeta = [
    'nursing' => 'Hospitalizacion',
    'oncology' => 'Centro Oncologico',
    'inpatient-pharmacy' => 'Farmacia intrahospitalaria',
  ];
  $areaLabel = $areaMeta[$areaKey] ?? $areaMeta['nursing'];
  $isPatientCatalog = in_array($section, ['patients', 'patient-create'], true);
  $isOncology = ! $isPatientCatalog && $areaKey === 'oncology';
  $isHospitalization = ! $isPatientCatalog && $areaKey === 'nursing';
  $isInpatientPharmacy = ! $isPatientCatalog && $areaKey === 'inpatient-pharmacy';
  $unitName = $contextUnit?->name ?? $profile?->medicalUnit?->name ?? 'H.G. CHIMALHUACAN';
  $unitCode = $contextUnit?->code ?? $contextUnit?->clues ?? $profile?->medicalUnit?->code ?? $profile?->medicalUnit?->clues ?? 'MCIMB001841';
  $institutionName = $contextUnit?->institution?->name ?? $profile?->medicalUnit?->institution?->name ?? $unitName;
  $visibleRequests = $section === 'history' ? $historicalProviderRequests : $pendingProviderRequests;
  $pendingRequests = $pendingProviderRequests->count();
  $moduleGroups = [
    'nursing' => ['label' => 'Hospitalizacion', 'icon' => 'hospital'],
    'oncology' => ['label' => 'Centro Oncologico', 'icon' => 'oncology'],
    'inpatient-pharmacy' => ['label' => 'Farmacia intrahospitalaria', 'icon' => 'pharmacy'],
  ];
  $areaDefaultRoutes = [
    'nursing' => ['area' => 'nursing', 'section' => 'calendar', 'hospitalization_track' => 'all'],
    'oncology' => ['area' => 'oncology', 'section' => 'calendar', 'oncology_track' => 'infusions'],
    'inpatient-pharmacy' => ['area' => 'inpatient-pharmacy', 'section' => 'pending'],
  ];
  $hospitalizationTrack = request('hospitalization_track', 'all');
  if (! in_array($hospitalizationTrack, ['all', 'nutrition', 'imports'], true)) {
    $hospitalizationTrack = 'all';
  }
  $hospitalizationTrackOptions = [
    'all' => ['label' => 'Calendario de Hospitalización', 'initial' => 'H'],
  ];
  $oncologyTrack = request('oncology_track');
  if (in_array($section, ['mixes', 'mix-history'], true)) {
    $oncologyTrack = 'mixes';
  } elseif (in_array($section, ['services-pending', 'services-history', 'services-preparation', 'services-scheduled', 'service-create', 'service-format', 'infusion-rooms', 'infusion-room-calendar', 'infusion-room-catalog', 'support', 'support-ai', 'support-analytics'], true)) {
    $oncologyTrack = 'infusions';
  } elseif (! in_array($oncologyTrack, ['infusions', 'mixes'], true)) {
    $oncologyTrack = 'infusions';
  }
  $oncologyTrackOptions = [
    'infusions' => ['label' => 'Calendario de Infusiones', 'initial' => 'I', 'section' => 'calendar', 'track' => 'infusions'],
    'rooms' => ['label' => 'Salas de infusion', 'initial' => 'SI', 'section' => 'infusion-rooms', 'track' => 'infusions'],
    'mixes' => ['label' => 'Central de mezclas', 'initial' => 'M', 'section' => 'calendar', 'track' => 'mixes'],
    'support' => ['label' => 'Soporte', 'initial' => 'S', 'section' => 'support', 'track' => 'infusions'],
  ];
  $activeOncologyCarouselItem = match ($section) {
    'infusion-rooms', 'infusion-room-calendar', 'infusion-room-catalog' => 'rooms',
    'support', 'support-ai', 'support-analytics', 'service-create', 'service-format' => 'support',
    default => $oncologyTrack,
  };
  $oncologyTrackNavigation = [
        'infusions' => [
            ['section' => 'calendar', 'label' => 'Inicio'],
            ['section' => 'services-scheduled', 'label' => 'Programadas'],
            ['section' => 'services-pending', 'label' => 'Pendientes'],
            ['section' => 'services-history', 'label' => 'Historial'],
      ['section' => 'services-preparation', 'label' => 'En preparación'],
    ],
    'mixes' => [
            ['section' => 'calendar', 'label' => 'Inicio'],
      ['section' => 'mixes', 'label' => 'Mezclas programadas'],
      ['section' => 'mix-history', 'label' => 'Historial de mezclas'],
    ],
    'rooms' => [
      ['section' => 'infusion-rooms', 'label' => 'Inicio', 'active_sections' => ['infusion-rooms', 'infusion-room-calendar']],
      ['section' => 'infusion-room-catalog', 'label' => 'Catalogo'],
    ],
    'support' => [
      ['section' => 'support', 'label' => 'Formato de solicitud', 'active_sections' => ['support', 'service-create', 'service-format']],
      ['section' => 'support-ai', 'label' => 'Asistencia con IA'],
      ['section' => 'support-analytics', 'label' => 'Analiticas'],
    ],
  ];
  $sectionNavigation = [
    'nursing' => [
      ['section' => 'calendar', 'label' => 'Programación'],
      ['section' => 'pending', 'label' => 'Pendientes'],
      ['section' => 'history', 'label' => 'Historial'],
    ],
    'inpatient-pharmacy' => [
      ['section' => 'pending', 'label' => 'Solicitudes pendientes'],
      ['section' => 'history', 'label' => 'Historial de solicitudes'],
    ],
  ];
  $currentSectionNavigation = $isPatientCatalog
    ? []
    : ($isOncology ? $oncologyTrackNavigation[match ($activeOncologyCarouselItem) {
      'rooms' => 'rooms',
      'support' => 'support',
      default => $oncologyTrack,
    }] : ($sectionNavigation[$areaKey] ?? $sectionNavigation['nursing']));
  $patientRows = $patients->take(12);
  $patientCatalogRouteParameters = $contextUnit ? ['unit' => $contextUnit->id] : [];
@endphp

@section('content')
  <div class="operational-native-screen">
    <header class="operational-native-topbar" aria-label="Barra superior operativa">
      <strong>MODULO OPERATIVO</strong>
      <span>{{ strtoupper($institutionName) }}</span>
      <div class="operational-native-user">
        <button type="button" aria-label="Notificaciones">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        </button>
        <div>
          <strong>Usuario activo</strong>
          <small>{{ $unitName }}</small>
        </div>
        <form method="post" action="{{ route('logout') }}" class="operational-native-logout">
          @csrf
          <button type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
            Cerrar sesion
          </button>
        </form>
      </div>
    </header>

    <aside class="operational-native-sidebar" aria-label="Navegacion operativa">
      <nav class="operational-native-menu operational-area-menu">
        @foreach ($moduleGroups as $groupKey => $group)
          @php $isActiveGroup = ! $isPatientCatalog && $groupKey === $areaKey; @endphp
          <a @class(['is-active' => $isActiveGroup]) href="{{ route('operational.dashboard', $areaDefaultRoutes[$groupKey] ?? ['area' => $groupKey, 'section' => 'pending']) }}">
            <span aria-hidden="true">
              @if ($group['icon'] === 'hospital')
                <svg viewBox="0 0 24 24"><path d="M4 21V5a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v16"/><path d="M16 9h3a1 1 0 0 1 1 1v11"/><path d="M8 8h4M10 6v4M8 14h4M8 18h4"/></svg>
              @elseif ($group['icon'] === 'oncology')
                <svg viewBox="0 0 24 24"><path d="M4 6h16v15H4z"/><path d="M9 3h6v3H9z"/><path d="M12 10v7M8.5 13.5h7"/></svg>
              @else
                <svg viewBox="0 0 24 24"><path d="m7 16 9-9a3 3 0 0 1 4 4l-9 9a3 3 0 0 1-4-4Z"/><path d="m12 11 4 4"/></svg>
              @endif
            </span>
            {{ $group['label'] }}
            <i aria-hidden="true">›</i>
          </a>
        @endforeach
        <a href="{{ route('outpatient.dashboard') }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6 4v5a5 5 0 0 0 10 0V4"/><path d="M9 4H5"/><path d="M17 4h-4"/><path d="M11 14v2a4 4 0 0 0 8 0v-3"/><circle cx="19" cy="10" r="2"/></svg>
          </span>
          Consulta Externa
          <i aria-hidden="true">›</i>
        </a>

        <a href="{{ route('external-pharmacy.dashboard') }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 9v11h16V9"/><path d="M3 9h18l-2-5H5L3 9Z"/><path d="M12 12v5M9.5 14.5h5"/></svg>
          </span>
          Farmacia Externa
          <i aria-hidden="true">›</i>
        </a>

        <a @class(['is-active' => $isPatientCatalog]) href="{{ route('operational.patients.index', $patientCatalogRouteParameters) }}">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </span>
          Catalogo de pacientes
          <i aria-hidden="true">›</i>
        </a>
      </nav>
    </aside>

    <section class="operational-native-workspace">
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

      @if ($isOncology)
        <section class="operational-oncology-carousel operational-compact-carousel" aria-label="Filtros de Centro Oncologico">
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
          <div class="operational-oncology-carousel-track">
            @foreach ($oncologyTrackOptions as $trackKey => $trackOption)
              <a @class(['operational-oncology-filter', 'is-active' => $activeOncologyCarouselItem === $trackKey]) href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => $trackOption['section'], 'oncology_track' => $trackOption['track']] + ($contextUnit ? ['unit' => $contextUnit->id] : [])) }}">
                <span class="operational-oncology-filter-initial" aria-hidden="true">{{ $trackOption['initial'] }}</span>
                <span class="operational-oncology-filter-copy">
                  <strong>{{ $trackOption['label'] }}</strong>
                  @if ($activeOncologyCarouselItem === $trackKey)
                    <small>✓ Seleccionada</small>
                  @endif
                </span>
              </a>
            @endforeach
          </div>
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
        </section>
      @elseif ($isHospitalization)
        <section class="operational-oncology-carousel operational-compact-carousel operational-hospitalization-carousel" aria-label="Filtros de Hospitalizacion">
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
          <div class="operational-oncology-carousel-track">
            @foreach ($hospitalizationTrackOptions as $trackKey => $trackOption)
              <a @class(['operational-oncology-filter', 'is-active' => $hospitalizationTrack === $trackKey]) href="{{ route('operational.dashboard', ['area' => 'nursing', 'section' => 'calendar', 'hospitalization_track' => $trackKey] + ($contextUnit ? ['unit' => $contextUnit->id] : [])) }}">
                <span class="operational-oncology-filter-initial" aria-hidden="true">{{ $trackOption['initial'] }}</span>
                <span class="operational-oncology-filter-copy">
                  <strong>{{ $trackOption['label'] }}</strong>
                  @if ($hospitalizationTrack === $trackKey)
                    <small>✓ Seleccionada</small>
                  @endif
                </span>
              </a>
            @endforeach
          </div>
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
        </section>
      @elseif ($isInpatientPharmacy)
        <section class="operational-oncology-carousel operational-compact-carousel operational-inpatient-pharmacy-carousel" aria-label="Filtros de Farmacia intrahospitalaria">
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
          <div class="operational-oncology-carousel-track">
            <a class="operational-oncology-filter is-active" href="{{ route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'pending'] + ($contextUnit ? ['unit' => $contextUnit->id] : [])) }}">
              <span class="operational-oncology-filter-initial" aria-hidden="true">F</span>
              <span class="operational-oncology-filter-copy">
                <strong>Farmacia intrahospitalaria</strong>
                <small>✓ Seleccionada</small>
              </span>
            </a>
          </div>
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
        </section>
      @endif

      @unless ($isPatientCatalog)
        <nav class="operational-section-tabs" aria-label="Secciones de {{ $areaLabel }}">
          @foreach ($currentSectionNavigation as $navigationItem)
            <a @class(['is-active' => in_array($section, $navigationItem['active_sections'] ?? [$navigationItem['section']], true)]) href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => $navigationItem['section']] + ($isOncology ? ['oncology_track' => $oncologyTrack] : []) + ($isHospitalization ? ['hospitalization_track' => $hospitalizationTrack] : []) + ($contextUnit ? ['unit' => $contextUnit->id] : [])) }}">
              {{ $navigationItem['label'] }}
            </a>
          @endforeach
        </nav>
      @endunless

      @if (in_array($section, ['pending', 'history'], true))
        <section class="operational-native-table-card">
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Historial de solicitudes</p>
              @unless($isHospitalization)
                <h2>Solicitudes enviadas por hospitales</h2>
                <span>Modulo operativo de {{ $institutionName }} - {{ $section === 'history' ? 'Historial de solicitudes enviadas por hospitales' : 'Solicitudes pendientes de '.$areaLabel }}</span>
              @endunless
            </div>
            <strong>{{ $section === 'history' ? $visibleRequests->count().' solicitudes' : $pendingRequests.' pendiente'.($pendingRequests === 1 ? '' : 's') }}</strong>
          </div>

          <form class="operational-native-filter" method="get">
            <input type="hidden" name="section" value="{{ $section }}">
            <input type="hidden" name="area" value="{{ $areaKey }}">
            <label>
              <span aria-hidden="true">âŒ•</span>
              <input name="search" placeholder="Buscar folio, paciente, medico o servicio">
            </label>
            <label>
              Estado
              <select name="status">
                <option value="">Todos</option>
                @foreach (['requested', 'accepted', 'dispensed', 'preparing', 'ready', 'in_route', 'delivered', 'rejected', 'cancelled'] as $status)
                  <option value="{{ $status }}">{{ $statusText($status) }}</option>
                @endforeach
              </select>
            </label>
          </form>

          <div class="operational-native-table-scroll">
            <table class="operational-native-table">
              <thead>
                <tr>
                  <th>Folio</th>
                  <th>Fecha</th>
                  <th>Paciente</th>
                  <th>Registro</th>
                  <th>Servicio solicitante</th>
                  <th>Volumen</th>
                  <th>Medico</th>
                  <th>Autorizaciones</th>
                  <th>Mezcla</th>
                  @unless ($isOncology)
                    <th>Enviar a proveedor</th>
                    <th>Cancelar Solicitud</th>
                  @endunless
                  <th>Visualizar Solicitud</th>
                  <th>Estado de Central de Mezclas</th>
                  <th>Observaciones</th>
                  @if ($isInpatientPharmacy)
                    <th>Remision de entrega</th>
                  @endif
                </tr>
              </thead>
              <tbody>
                @forelse ($visibleRequests as $providerRequest)
                  @php
                    $requestAuthorizations = data_get($providerRequest->payload, 'authorizations', []);
                    $authorizationRequirements = \App\Support\MixtureAuthorizationPolicy::requirements($providerRequest->request_type);
                    $authorizationLabels = $providerRequest->request_type === 'chemo'
                      ? ['oncology' => 'Centro Onc.', 'pharmacy' => 'Farm. Intra.']
                      : ['nursing' => 'Enfermeria', 'pharmacy' => 'Farm. Intra.'];
                    $authorizationAreaKeys = [
                      'nursing' => 'nursing', 'enfermeria' => 'nursing',
                      'inpatient-pharmacy' => 'pharmacy', 'farmacia' => 'pharmacy',
                      'oncology' => 'oncology', 'oncologia' => 'oncology',
                    ];
                    $currentAuthorization = $authorizationAreaKeys[$areaKey] ?? null;
                    $profileAuthorization = $authorizationAreaKeys[$profile?->area?->key] ?? null;
                    $operationalRoles = ['superadmin', 'admin', 'institution', 'unit', 'operational'];
                    $canOperateCurrentArea = in_array(auth()->user()?->role, $operationalRoles, true)
                      && $currentAuthorization !== null
                      && (auth()->user()?->role !== 'operational' || $profileAuthorization === $currentAuthorization);
                    $authorizationIsLocked = in_array($providerRequest->status, ['delivered', 'cancelled', 'rejected'], true);
                    $canEditAuthorization = $canOperateCurrentArea;
                    $allAuthorizationsApproved = \App\Support\MixtureAuthorizationPolicy::allApproved($providerRequest->payload ?? [], $providerRequest->request_type);
                    $latestAuthorizationEvent = $providerRequest->statusEvents
                      ->first(fn ($event) => data_get($event->metadata, 'type') === 'authorization');
                    $remoteMixtureStatus = $providerRequest->mixtureIntegration?->remote_status;
                    $cancellationLockedByCbta = \App\Support\MixtureAuthorizationPolicy::cancellationLockedByCbta($remoteMixtureStatus);
                    $centralStatus = match (true) {
                      filled($remoteMixtureStatus) => \App\Support\MixtureIntegrationStatus::label($remoteMixtureStatus),
                      data_get($providerRequest->payload, 'cancellation') !== null,
                      in_array($providerRequest->status, ['cancelled', 'rejected'], true) => 'Cancelada',
                      $providerRequest->status === 'delivered' => 'Entregada',
                      $providerRequest->status === 'in_route' => 'En ruta',
                      $providerRequest->status === 'preparing' => 'Preparada',
                      $allAuthorizationsApproved => 'Pendiente',
                      default => 'En revisión',
                    };
                  @endphp
                  <tr>
                    <td><strong>{{ $providerRequest->external_id ?? 'NPT-'.$providerRequest->id }}</strong></td>
                    <td>{{ $providerRequest->requested_at?->format('d M Y, h:i a') ?? 'N/A' }}</td>
                    <td><strong>{{ $providerRequest->patient?->full_name ?? 'Sin paciente' }}</strong></td>
                    <td>REG-{{ str_pad((string) $providerRequest->id, 4, '0', STR_PAD_LEFT) }}</td>
                    <td>
                      @if ($isOncology)
                        <span class="operational-chip oncology">Oncologia medica</span>
                      @else
                        {{ data_get($providerRequest->payload, 'service', $providerRequest->request_type === 'chemo' ? 'Oncologia medica' : 'Medicina interna') }}
                      @endif
                    </td>
                    <td>{{ data_get($providerRequest->payload, 'volume', $isOncology ? '500' : '1200') }}</td>
                    <td>{{ data_get($providerRequest->payload, 'doctor', $isOncology ? 'Dra. Laura Benitez' : 'Carter Jimmy') }}</td>
                    <td>
                      <div class="operational-authorization-list">
                        @foreach ($authorizationLabels as $authorizationKey => $authorizationLabel)
                          @php
                            $authorizationStatus = \App\Support\MixtureAuthorizationPolicy::status($requestAuthorizations, $authorizationKey, $providerRequest->request_type);
                            $authorizationStatusLabel = ['approved' => 'Autorizado', 'rejected' => 'Rechazado', 'pending' => 'Pendiente'][$authorizationStatus] ?? 'Pendiente';
                            $authorizationClass = ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning'][$authorizationStatus] ?? 'warning';
                            $canEditThisAuthorization = ! $authorizationIsLocked
                              && $canEditAuthorization
                              && ($currentAuthorization === $authorizationKey || in_array(auth()->user()?->role, ['superadmin', 'admin'], true));
                          @endphp
                          @if ($canEditThisAuthorization)
                            <div class="operational-authorization-control">
                              <button
                                class="authorization-pill authorization-button auth-{{ $authorizationStatus }}"
                                type="button"
                                data-authorization-toggle="true"
                                data-authorization-area="{{ $authorizationKey }}"
                                data-authorization-folio="{{ $providerRequest->external_id ?? 'NPT-'.$providerRequest->id }}"
                                data-authorization-scope="{{ $section }}"
                                aria-expanded="false"
                                aria-label="{{ $authorizationLabel }} {{ $authorizationStatusLabel }}"
                                title="{{ $authorizationLabel }}: {{ $authorizationStatusLabel }}"
                                style="cursor:pointer;pointer-events:auto"
                                onclick="toggleOperationalAuthorizationMenu(this, event)"
                              >
                                {{ $authorizationLabel }}
                              </button>
                              <form class="operational-authorization-menu" method="post" action="{{ route('operational.provider-requests.authorizations.update', $providerRequest) }}">
                                @csrf
                                @method('patch')
                                <input type="hidden" name="authorization" value="{{ $authorizationKey }}">
                                <input type="hidden" name="operating_area" value="{{ $areaKey }}">
                                <button type="submit" name="status" value="approved" @class(['success', 'is-selected' => $authorizationStatus === 'approved'])>Autorizado</button>
                                <button type="submit" name="status" value="pending" @class(['warning', 'is-selected' => $authorizationStatus === 'pending'])>Pendiente</button>
                                <button type="submit" name="status" value="rejected" @class(['danger', 'is-selected' => $authorizationStatus === 'rejected'])>Rechazado</button>
                              </form>
                            </div>
                          @else
                            <span class="authorization-pill auth-{{ $authorizationStatus }} authorization-locked" data-authorization-area="{{ $authorizationKey }}" title="Autorización de {{ $authorizationLabel }}: {{ $authorizationStatusLabel }}">
                              {{ $authorizationLabel }}
                            </span>
                          @endif
                        @endforeach
                      </div>
                    </td>
                    <td><strong>{{ data_get($providerRequest->payload, 'mix_status', $isOncology ? 'Protocolo por validar' : 'No Estable') }}</strong></td>
                    @unless ($isOncology)
                      <td>
                        @php
                          $assignedProviderName = data_get($providerRequest->payload, 'provider_assignment.name');
                          $providerWasSent = filled($assignedProviderName);
                          $inspectionAlreadyCompleted = in_array($remoteMixtureStatus, ['ready', 'in_route', 'delivered'], true)
                            || in_array($providerRequest->status, ['ready', 'in_route', 'delivered'], true);
                        @endphp
                        @if ($providerWasSent)
                          <span class="authorization-pill auth-approved">{{ $assignedProviderName }}</span>
                        @elseif ($allAuthorizationsApproved && ! $authorizationIsLocked && ! $inspectionAlreadyCompleted && $providerRequest->request_type !== 'chemo')
                          <div class="operational-provider-control">
                            <button
                              class="authorization-pill authorization-button auth-pending"
                              type="button"
                              data-provider-toggle="true"
                              aria-expanded="false"
                              onclick="toggleOperationalProviderMenu(this, event)"
                            >Pendiente</button>
                            <form class="operational-provider-menu" method="post" action="{{ route('operational.provider-requests.status', $providerRequest) }}">
                              @csrf
                              @method('patch')
                              <input type="hidden" name="status" value="accepted">
                              <input type="hidden" name="provider_name" value="Prodifem">
                              <input type="hidden" name="notes" value="Enviado a Prodifem desde tablero operativo">
                              <button type="submit" class="authorization-pill auth-approved">Prodifem</button>
                            </form>
                          </div>
                        @else
                          <button type="button" class="operational-disabled-action" disabled title="{{ $inspectionAlreadyCompleted ? 'La mezcla ya fue inspeccionada y no puede reenviarse al proveedor' : 'Requiere autorización de Enfermería y Farmacia intrahospitalaria' }}">Pendiente</button>
                        @endif
                      </td>
                      <td>
                        @if ($isInpatientPharmacy && $allAuthorizationsApproved && ! $authorizationIsLocked && ! $cancellationLockedByCbta)
                          <form class="operational-mini-form" method="post" action="{{ route('operational.provider-requests.status', $providerRequest) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="operating_area" value="inpatient-pharmacy">
                            <input type="hidden" name="notes" value="Cancelado por Farmacia intrahospitalaria">
                            <button type="submit" class="danger">Cancelar</button>
                          </form>
                        @else
                          <button type="button" class="operational-disabled-action" disabled title="{{ $cancellationLockedByCbta ? 'La solicitud ya fue aprobada en Mezclas y no puede cancelarse desde Dr. Sam' : 'Solo Farmacia intrahospitalaria puede cancelar cuando ambas autorizaciones están aprobadas' }}">Cancelar</button>
                        @endif
                      </td>
                    @endunless
                    <td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => $section, 'detail_request' => $providerRequest->id]) }}#solicitud-detalle">Ver</a></td>
                    <td><span class="operational-chip">{{ $centralStatus }}</span></td>
                    <td>{{ $latestAuthorizationEvent?->notes ?: ($providerRequest->statusEvents->first()?->notes ?? ($isOncology ? 'Primera validacion pendiente por Centro Oncologico.' : 'Sin observaciones')) }}</td>
                    @if ($isInpatientPharmacy)
                      <td>{{ data_get($providerRequest->payload, 'delivery_remission', 'Pendiente') }}</td>
                    @endif
                  </tr>
                  @if ($cancellation = data_get($providerRequest->payload, 'cancellation'))
                    <tr class="operational-cancellation-row">
                      <td colspan="{{ $isOncology ? 12 : ($isInpatientPharmacy ? 15 : 14) }}">
                        <strong>{{ data_get($cancellation, 'message', 'Solicitud cancelada.') }}</strong>
                        @if (data_get($cancellation, 'actor'))
                          Rechazado por {{ data_get($cancellation, 'actor') }}.
                        @endif
                      </td>
                    </tr>
                  @endif
                @empty
                  <tr>
                    <td colspan="{{ $isOncology ? 12 : ($isInpatientPharmacy ? 15 : 14) }}">No hay solicitudes para esta vista.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
        @php
          $detailRequestId = (int) request('detail_request');
          $detailRequest = $visibleRequests->firstWhere('id', $detailRequestId);
        @endphp
        @if ($detailRequest)
          @php
            $detailPayload = $detailRequest->payload ?? [];
            $detailAuthorizations = data_get($detailPayload, 'authorizations', []);
            $detailItems = collect(data_get($detailPayload, 'integration_items', []));
            $detailRemission = data_get($detailPayload, 'cbta.remission', []);
            $detailRemoteStatus = $detailRequest->mixtureIntegration?->remote_status;
            $detailIntegration = $detailRequest->mixtureIntegration;
            $detailIntegrationError = $detailIntegration?->last_error;
            $detailIntegrationMessage = data_get($detailIntegration?->metadata, 'remote_status_message');
            $detailIntegrationStage = data_get($detailIntegration?->metadata, 'remote_integration_stage');
            $detailIntegrationHasError = (bool) data_get($detailIntegration?->metadata, 'remote_has_error', false) || filled($detailIntegrationError);
            $detailStatusEvents = $detailRequest->statusEvents
              ->sortBy('occurred_at')
              ->unique(fn ($event) => implode('|', [$event->status, $event->occurred_at?->toIso8601String(), $event->notes]));
          @endphp
          <div id="solicitud-detalle" class="operational-modal is-open" role="dialog" aria-modal="true" aria-labelledby="solicitud-detalle-title">
            <a class="operational-modal-backdrop" href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => $section]) }}" aria-label="Cerrar"></a>
            <article class="operational-modal-card operational-request-detail-card">
              <header>
                <div>
                  <p class="eyebrow">Detalle de solicitud</p>
                  <h2 id="solicitud-detalle-title">{{ $detailRequest->external_id ?? 'Solicitud '.$detailRequest->id }}</h2>
                  <p>{{ data_get($detailPayload, 'service', 'Servicio de mezclas') }} · {{ $detailRequest->patient?->full_name ?? 'Sin paciente' }}</p>
                </div>
                <a href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => $section]) }}">Cerrar</a>
              </header>
              <div class="operational-modal-body operational-request-detail-body">
                <dl class="operational-request-detail-grid">
                  <div><dt>Folio</dt><dd>{{ $detailRequest->external_id ?? '—' }}</dd></div>
                  <div><dt>Paciente</dt><dd>{{ $detailRequest->patient?->full_name ?? 'Sin paciente' }}</dd></div>
                  <div><dt>Registro</dt><dd>{{ data_get($detailPayload, 'clinical_format.registration', 'REG-'.str_pad((string) $detailRequest->id, 4, '0', STR_PAD_LEFT)) }}</dd></div>
                  <div><dt>Médico</dt><dd>{{ data_get($detailPayload, 'doctor', 'Sin médico') }}</dd></div>
                  <div><dt>Unidad</dt><dd>{{ $detailRequest->medicalUnit?->name ?? 'Sin unidad' }}</dd></div>
                  <div><dt>Servicio</dt><dd>{{ data_get($detailPayload, 'service', $detailRequest->request_type) }}</dd></div>
                  <div><dt>Volumen</dt><dd>{{ data_get($detailPayload, 'clinical_format.total_volume', data_get($detailPayload, 'volume', '—')) }} ml</dd></div>
                  <div><dt>Diagnóstico</dt><dd>{{ data_get($detailPayload, 'diagnosis', 'No informado') }}</dd></div>
                  <div><dt>Estado Dr. Sam</dt><dd>{{ $statusText($detailRequest->status) }}</dd></div>
                  <div><dt>Estado CBTA</dt><dd>{{ $detailRemoteStatus ? $statusText($detailRemoteStatus) : 'Sin sincronización' }}</dd></div>
                  <div><dt>Enfermería</dt><dd>{{ $statusText(data_get($detailAuthorizations, 'operational', 'pending')) }}</dd></div>
                  <div><dt>Farmacia intrahospitalaria</dt><dd>{{ $statusText(data_get($detailAuthorizations, 'pharmacy', 'pending')) }}</dd></div>
                </dl>

                @if ($detailIntegration)
                  <section class="operational-integration-alert {{ $detailIntegrationHasError ? 'is-error' : ($detailRemoteStatus === 'delivered' ? 'is-success' : 'is-pending') }}" role="status">
                    <div>
                      <strong>{{ $detailIntegrationHasError ? 'CBTA requiere atención' : 'Sincronización con CBTA' }}</strong>
                      <p>{{ $detailIntegrationError ?: $detailIntegrationMessage ?: 'La solicitud está sincronizada y su estado se consulta automáticamente.' }}</p>
                    </div>
                    <dl>
                      <div><dt>Etapa</dt><dd>{{ $detailIntegrationStage ? str($detailIntegrationStage)->replace('_', ' ')->title() : 'Sincronización' }}</dd></div>
                      <div><dt>Última consulta</dt><dd>{{ $detailIntegration->last_synced_at?->format('d/m/Y H:i') ?? 'Pendiente' }}</dd></div>
                    </dl>
                  </section>
                @endif

                <section class="operational-request-detail-section">
                  <h3>Componentes solicitados</h3>
                  <div class="operational-request-detail-table-wrap">
                    <table>
                      <thead><tr><th>Producto</th><th>Presentación</th><th>Cantidad</th></tr></thead>
                      <tbody>
                        @forelse ($detailItems as $item)
                          <tr><td>{{ data_get($item, 'product_code', 'Sin código') }}</td><td>{{ data_get($item, 'presentation_code', 'No informada') }}</td><td>{{ data_get($item, 'quantity', '—') }} {{ data_get($item, 'unit') }}</td></tr>
                        @empty
                          <tr><td colspan="3">La solicitud no contiene componentes desglosados.</td></tr>
                        @endforelse
                      </tbody>
                    </table>
                  </div>
                </section>

                <section class="operational-request-detail-section">
                  <h3>Seguimiento</h3>
                  <ol class="operational-request-timeline">
                    @forelse ($detailStatusEvents as $event)
                      <li><span></span><div><strong>{{ $statusText($event->status) }}</strong><small>{{ $event->occurred_at?->format('d/m/Y H:i') }}</small>@if($event->notes)<p>{{ $event->notes }}</p>@endif</div></li>
                    @empty
                      <li><span></span><div><strong>Solicitud registrada</strong></div></li>
                    @endforelse
                  </ol>
                </section>

                <section class="operational-request-detail-section operational-request-remission-summary">
                  <h3>Remisión</h3>
                  @if (data_get($detailRemission, 'available') === true)
                    <p>
                      Remisión No. <strong>{{ data_get($detailRemission, 'number', 'Sin número') }}</strong>
                      @if (data_get($detailRemission, 'issued_at'))
                        , emitida el {{ \Illuminate\Support\Carbon::parse(data_get($detailRemission, 'issued_at'))->format('d/m/Y H:i') }}
                      @endif
                      .
                    </p>
                  @else
                    <p>La remisión todavía no está disponible.</p>
                  @endif
                </section>
              </div>
              <footer><a href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => $section]) }}">Cerrar</a></footer>
            </article>
          </div>
        @endif
      @elseif ($section === 'calendar' && $isHospitalization)
        @php $calendarMode = 'hospitalization'; @endphp
        @include('operational.sections.service-calendar')
      @elseif (in_array($section, ['services-pending', 'services-history', 'services-preparation', 'services-scheduled', 'service-create', 'service-format', 'mixes', 'mix-history', 'calendar', 'infusion-rooms', 'infusion-room-calendar', 'infusion-room-catalog', 'support', 'support-ai', 'support-analytics'], true))
        @include('operational.sections.oncology')
      @else
        @php $editingPatient = $patientRows->firstWhere('id', (int) request('edit_patient')); @endphp
        <section class="operational-native-table-card operational-patients-card" data-operational-patient-catalog>
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Catalogo compartido</p>
              <h2>Catalogo de pacientes</h2>
              <span>{{ $unitName }} - Pacientes vinculados a todas las areas operativas</span>
            </div>
            <div class="operational-heading-actions">
              <strong>{{ $patientRows->count() }} pacientes</strong>
              <a class="operational-primary-button" href="{{ route('operational.patients.index', array_merge($patientCatalogRouteParameters, ['modal' => 'patient'])) }}"><span aria-hidden="true">+</span> Nuevo paciente</a>
            </div>
          </div>

          <div class="operational-native-table-scroll">
            <table class="operational-native-table operational-patients-table">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Apellidos</th>
                  <th>Edad</th>
                  <th>CURP</th>
                  <th>NSS federal</th>
                  <th>NSS estatal</th>
                  <th>Usuario plataforma</th>
                  <th>Entidad federativa</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($patientRows as $patient)
                  <tr>
                    <td><strong>{{ $patient->first_name ?: str($patient->full_name)->before(' ') }}</strong></td>
                    <td><strong>{{ $patient->last_name ?: str($patient->full_name)->after(' ') }}</strong></td>
                    <td>{{ $patient->birth_date ? (int) $patient->birth_date->age : data_get($patient->metadata, 'age', 'N/A') }}</td>
                    <td><strong>{{ $patient->curp ?? data_get($patient->metadata, 'curp', 'Sin CURP') }}</strong></td>
                    <td>{{ data_get($patient->metadata, 'nss_federal', 'Sin NSS') }}</td>
                    <td>{{ data_get($patient->metadata, 'nss_estatal', 'Sin NSS') }}</td>
                    <td>{{ $patient->platform_number ?? 'USR-'.str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT) }}</td>
                    <td>{{ data_get($patient->metadata, 'state', 'Mexico') }}</td>
                    <td><a class="operational-secondary-button" href="{{ route('operational.patients.index', array_merge($patientCatalogRouteParameters, ['edit_patient' => $patient->id])) }}">Editar</a></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9">Sin pacientes registrados para esta unidad.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <div id="alta-paciente" @class(['operational-modal', 'is-open' => $section === 'patient-create' || request('modal') === 'patient' || (bool) $editingPatient]) role="dialog" aria-modal="true" aria-labelledby="alta-paciente-title">
          <a class="operational-modal-backdrop" href="{{ route('operational.patients.index', $patientCatalogRouteParameters) }}" aria-label="Cerrar"></a>
          <form class="operational-modal-card" method="post" action="{{ $editingPatient ? route('operational.patients.update', $editingPatient) : route('operational.patients.store') }}">
            @csrf
            @if($editingPatient) @method('patch') @endif
            @if ($contextUnit)<input type="hidden" name="unit" value="{{ $contextUnit->id }}">@endif
            <header>
              <div>
                <h2 id="alta-paciente-title">{{ $editingPatient ? 'Editar paciente' : 'Alta de paciente' }}</h2>
                <p>{{ $institutionName }} - Catalogo de pacientes</p>
              </div>
              <a href="{{ route('operational.patients.index', $patientCatalogRouteParameters) }}">Cerrar</a>
            </header>

            <div class="operational-modal-body">
              <label>Nombre<input name="first_name" type="text" required value="{{ old('first_name', $editingPatient?->first_name) }}"></label>
              <label>Apellidos<input name="last_name" type="text" required value="{{ old('last_name', $editingPatient?->last_name) }}"></label>
              <label>Fecha de nacimiento<input name="birth_date" type="date" value="{{ old('birth_date', $editingPatient?->birth_date?->format('Y-m-d')) }}"></label>
              <label>Sexo<select name="sex"><option value="">Sin especificar</option><option value="female" @selected(old('sex', $editingPatient?->sex) === 'female')>Femenino</option><option value="male" @selected(old('sex', $editingPatient?->sex) === 'male')>Masculino</option><option value="other" @selected(old('sex', $editingPatient?->sex) === 'other')>Otro</option></select></label>
              <label>CURP<input name="curp" type="text" maxlength="18" value="{{ old('curp', $editingPatient?->curp) }}"></label>
              <label>Numero de seguridad social federal<input name="nss_federal" type="text" value="{{ old('nss_federal', data_get($editingPatient?->metadata, 'nss_federal')) }}"></label>
              <label>Numero de seguridad social estatal<input name="nss_estatal" type="text" value="{{ old('nss_estatal', data_get($editingPatient?->metadata, 'nss_estatal')) }}"></label>
              <label>Numero de usuario de la plataforma<input name="platform_number" type="text" value="{{ old('platform_number', $editingPatient?->platform_number) }}"></label>
              <label>Telefono<input name="phone" type="text" value="{{ old('phone', $editingPatient?->phone) }}"></label>
              <label>Correo<input name="email" type="email" value="{{ old('email', $editingPatient?->email) }}"></label>
              <label>Entidad Federativa<input name="state" type="text" value="{{ old('state', data_get($editingPatient?->metadata, 'state', 'MEXICO')) }}"></label>
            </div>

            <footer>
              <a href="{{ route('operational.patients.index', $patientCatalogRouteParameters) }}">Cancelar</a>
              <button type="submit">{{ $editingPatient ? 'Guardar cambios' : 'Guardar paciente' }}</button>
            </footer>
          </form>
        </div>
      @endif
    </section>
  </div>

  @php
    $incomingOncologyRequest = $isOncology
      ? $pendingProviderRequests->firstWhere('id', (int) request('assign_request'))
      : null;
  @endphp
  @if ($isOncology && (request()->boolean('new_infusion') || $incomingOncologyRequest))
    @php
      $operationalOncologyServices = collect(['Quimioterapia'])
        ->merge(collect($services)->map(fn ($item) => $item->service?->name)->filter())
        ->push(data_get($incomingOncologyRequest?->payload, 'service'))
        ->filter()
        ->unique()
        ->values();
      $operationalInfusionNurses = collect($infusionRooms)->pluck('responsible_name')->filter()->unique()->values();
      $oncologyModalCloseUrl = route('operational.dashboard', [
        'area' => 'oncology',
        'section' => $incomingOncologyRequest ? 'services-pending' : 'calendar',
        'oncology_track' => 'infusions',
        'unit' => $contextUnit?->id,
      ]);
    @endphp
    @include('doctor.partials.oncology-mixture-request-modal', [
      'oncologyRequestContext' => 'operational',
      'oncologyExistingRequest' => $incomingOncologyRequest,
      'oncologyFormAction' => $incomingOncologyRequest
        ? route('operational.infusion-assignments.update', $incomingOncologyRequest)
        : route('operational.service-requests.store'),
      'oncologyFormMethod' => $incomingOncologyRequest ? 'patch' : 'post',
      'oncologyCloseUrl' => $oncologyModalCloseUrl,
      'oncologyUnitName' => $unitName,
      'oncologyMedicalUnitId' => $contextUnit?->id,
      'oncologyDoctorName' => data_get($profile?->metadata, 'doctor_name', ''),
      'oncologyProfessionalLicense' => data_get($profile?->metadata, 'professional_license', ''),
      'oncologyServiceOptions' => $operationalOncologyServices,
      'oncologyInfusionRooms' => $infusionRooms,
      'oncologyInfusionNurses' => $operationalInfusionNurses,
    ])
  @endif
  <script>
    document.querySelectorAll('[data-operational-carousel-prev], [data-operational-carousel-next]').forEach((button) => {
      button.addEventListener('click', () => {
        const carousel = button.closest('.operational-oncology-carousel');
        const track = carousel?.querySelector('.operational-oncology-carousel-track');
        if (!track) return;

        const itemWidth = track.querySelector('.operational-oncology-filter')?.getBoundingClientRect().width ?? 168;
        const scrollDistance = itemWidth + 10;

        track.scrollBy({
          left: button.matches('[data-operational-carousel-prev]') ? -scrollDistance : scrollDistance,
          behavior: 'smooth',
        });
      });
    });

    const oncologyRequestModal = document.querySelector('[data-oncology-request-modal]');
    if (oncologyRequestModal) {
      document.body.classList.add('doctor-oncology-modal-open');
      const form = oncologyRequestModal.querySelector('[data-oncology-request-form]');
      const patientSelect = form?.querySelector('[data-oncology-patient-select]');
      const setValue = (selector, value, overwrite = false) => {
        const field = form?.querySelector(selector);
        if (field && (overwrite || !field.value) && value !== undefined && value !== null) field.value = value;
      };
      const populatePatient = ({ overwrite = false } = {}) => {
        const option = patientSelect?.selectedOptions[0];
        if (!option?.value) return;
        setValue('[data-oncology-patient-identifier]', option.dataset.patientIdentifier, true);
        setValue('[data-oncology-patient-birth]', option.dataset.patientBirth, true);
        setValue('[data-oncology-patient-age]', option.dataset.patientAge, true);
        setValue('input[name="oncology[weight]"]', option.dataset.patientWeight, overwrite);
        setValue('input[name="oncology[height]"]', option.dataset.patientHeight, overwrite);
        setValue('input[name="oncology[body_surface]"]', option.dataset.patientSurface, overwrite);
        const normalizedSex = String(option.dataset.patientSex || '').toLocaleLowerCase('es-MX');
        const sexValue = normalizedSex.startsWith('f') ? 'Femenino' : (normalizedSex.startsWith('m') ? 'Masculino' : '');
        if (sexValue && (overwrite || !form.querySelector('input[name="oncology[sex]"]:checked'))) {
          form.querySelector(`input[name="oncology[sex]"][value="${sexValue}"]`)?.click();
        }
      };
      if (!form?.hasAttribute('data-oncology-incoming-request')) {
        patientSelect?.addEventListener('change', () => populatePatient({ overwrite: true }));
        populatePatient();
      }

      const programmingDate = form?.querySelector('[data-oncology-programming-date]');
      programmingDate?.addEventListener('change', () => {
        const requestDate = form.querySelector('[data-oncology-request-date]');
        if (requestDate) requestDate.value = programmingDate.value;
      });

      form?.querySelectorAll('[data-oncology-count-input]').forEach(field => {
        const output = form.querySelector(`[data-oncology-count="${field.dataset.oncologyCountInput}"]`);
        const updateCount = () => { if (output) output.textContent = String(field.value.length); };
        field.addEventListener('input', updateCount);
        updateCount();
      });
      form?.querySelector('[data-oncology-file]')?.addEventListener('change', event => {
        const label = form.querySelector('[data-oncology-file-label]');
        if (label) label.textContent = event.currentTarget.files?.[0]?.name || 'Subir firma y c\u00e9dula';
      });

      const medicationRows = [...form.querySelectorAll('[data-oncology-medication-row]')];
      const addMedicationButton = form.querySelector('[data-oncology-add-medication]');
      const visibleMedicationRows = () => medicationRows.filter(row => !row.hidden);
      const copyMedicationRow = (targetRow, sourceRow) => {
        const targetFields = [...targetRow.querySelectorAll('input, select, textarea')];
        const sourceFields = [...sourceRow.querySelectorAll('input, select, textarea')];
        targetFields.forEach((field, index) => {
          const sourceField = sourceFields[index];
          if (!sourceField) return;
          if (field.matches('[type="checkbox"], [type="radio"]')) field.checked = sourceField.checked;
          else field.value = sourceField.value;
        });
      };
      const clearMedicationRow = row => {
        row.querySelectorAll('input, select, textarea').forEach(field => {
          if (field.matches('[type="checkbox"], [type="radio"]')) field.checked = false;
          else if (field instanceof HTMLSelectElement) field.selectedIndex = 0;
          else field.value = '';
        });
      };
      const refreshMedicationButtons = () => {
        const visibleRows = visibleMedicationRows();
        if (addMedicationButton) addMedicationButton.hidden = visibleRows.length === medicationRows.length;
        medicationRows.forEach(row => {
          const removeButton = row.querySelector('[data-oncology-remove-medication]');
          if (removeButton) removeButton.hidden = row.hidden || visibleRows.length <= 1;
        });
      };
      addMedicationButton?.addEventListener('click', () => {
        const nextRow = medicationRows.find(row => row.hidden);
        if (!nextRow) return;
        clearMedicationRow(nextRow);
        nextRow.hidden = false;
        nextRow.querySelector('input[name$="[medication]"]')?.focus();
        refreshMedicationButtons();
      });
      form?.querySelectorAll('[data-oncology-remove-medication]').forEach(button => {
        button.addEventListener('click', () => {
          const row = button.closest('[data-oncology-medication-row]');
          const visibleRows = visibleMedicationRows();
          const rowIndex = visibleRows.indexOf(row);
          if (rowIndex < 0 || visibleRows.length <= 1) return;

          for (let index = rowIndex; index < visibleRows.length - 1; index += 1) {
            copyMedicationRow(visibleRows[index], visibleRows[index + 1]);
          }
          const lastVisibleRow = visibleRows[visibleRows.length - 1];
          clearMedicationRow(lastVisibleRow);
          lastVisibleRow.hidden = true;
          refreshMedicationButtons();
        });
      });
      refreshMedicationButtons();

      const roomSelect = form?.querySelector('[data-oncology-infusion-room]');
      const seatSelect = form?.querySelector('[data-oncology-infusion-seat]');
      const refreshSeats = ({ clearInvalid = false } = {}) => {
        const roomId = roomSelect?.value || '';
        [...(seatSelect?.options || [])].forEach(option => {
          if (!option.dataset.infusionRoom) return;
          const belongsToRoom = option.dataset.infusionRoom === roomId;
          option.hidden = !belongsToRoom;
          option.disabled = !belongsToRoom;
        });
        if (clearInvalid && seatSelect?.selectedOptions[0]?.disabled) seatSelect.value = '';
      };
      roomSelect?.addEventListener('change', () => refreshSeats({ clearInvalid: true }));
      refreshSeats();

      form?.querySelectorAll('[data-oncology-save-mode]').forEach(button => {
        button.addEventListener('click', () => {
          const assignmentRequired = button.dataset.oncologySaveMode === 'scheduled';
          ['[data-oncology-infusion-room]', '[data-oncology-infusion-seat]', 'input[name="assignment[application_date]"]', 'input[name="assignment[starts_at]"]']
            .forEach(selector => {
              const field = form.querySelector(selector);
              if (field) field.required = assignmentRequired;
            });
        });
      });
    }

    document.querySelectorAll('[data-oncology-workflow-list]').forEach(list => {
      const tableBody = list.querySelector('[data-oncology-workflow-table] tbody');
      const sortButton = list.querySelector('[data-oncology-workflow-sort]');
      let ascending = false;
      sortButton?.addEventListener('click', () => {
        ascending = !ascending;
        const rows = [...tableBody.querySelectorAll('[data-oncology-workflow-row]')];
        rows
          .sort((first, second) => {
            const comparison = String(first.dataset.sortDate || '').localeCompare(String(second.dataset.sortDate || ''));
            return ascending ? comparison : -comparison;
          })
          .forEach(row => tableBody.append(row));
        sortButton.textContent = ascending ? 'Fecha ascendente' : 'Fecha descendente';
      });
    });

    const serviceCalendar = document.querySelector('[data-operational-service-calendar]');
    if (serviceCalendar) {
      const eventSource = serviceCalendar.querySelector('[data-service-calendar-events]');
      const events = eventSource ? JSON.parse(eventSource.textContent || '[]') : [];
      const grid = serviceCalendar.querySelector('[data-service-calendar-grid]');
      const monthSelect = serviceCalendar.querySelector('[data-service-calendar-month]');
      const yearSelect = serviceCalendar.querySelector('[data-service-calendar-year]');
      const searchInput = serviceCalendar.querySelector('[data-service-calendar-search]');
      const dialog = serviceCalendar.querySelector('[data-service-calendar-dialog]');
      const dialogTitle = serviceCalendar.querySelector('[data-service-calendar-dialog-title]');
      const dialogBody = serviceCalendar.querySelector('[data-service-calendar-dialog-body]');
      const mixtureDialog = serviceCalendar.querySelector('[data-mixture-detail-dialog]');
      const mixtureScheduleForm = serviceCalendar.querySelector('[data-mixture-schedule-form]');
      const initialDate = serviceCalendar.dataset.initialDate || new Date().toISOString().slice(0, 10);
      const calendarMode = serviceCalendar.dataset.calendarMode || 'oncology';
      const isHospitalizationCalendar = calendarMode === 'hospitalization';
      const isMixtureCalendar = calendarMode === 'oncology' && serviceCalendar.dataset.oncologyTrack === 'mixes';
      let visibleDate = new Date(`${initialDate}T12:00:00`);

      const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
      })[character]);

      const isoDate = (date) => [
        date.getFullYear(),
        String(date.getMonth() + 1).padStart(2, '0'),
        String(date.getDate()).padStart(2, '0'),
      ].join('-');

      const normalize = (value) => String(value ?? '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

      const ensureYearOption = (year) => {
        if ([...yearSelect.options].some((option) => Number(option.value) === year)) return;
        yearSelect.add(new Option(String(year), String(year)));
        [...yearSelect.options]
          .sort((first, second) => Number(first.value) - Number(second.value))
          .forEach((option) => yearSelect.append(option));
      };

      const filteredEvents = () => {
        const filters = Object.fromEntries(
          [...serviceCalendar.querySelectorAll('[data-service-calendar-filter]')]
            .map((filter) => [filter.dataset.serviceCalendarFilter, filter.value])
        );
        const query = normalize(searchInput?.value);

        return events.filter((item) => {
          const matchesFilters = Object.entries(filters).every(([key, value]) => !value || item[key] === value);
          const searchable = normalize([item.patient, item.curp, item.folio, item.service, item.doctor, item.room].join(' '));
          return matchesFilters && (!query || searchable.includes(query));
        });
      };

      const openDay = (date, visibleEvents = filteredEvents()) => {
        const dayEvents = visibleEvents
          .filter((item) => item.date === date)
          .sort((first, second) => String(first.time).localeCompare(String(second.time)));
        if (!dialog) return;

        const dateValue = new Date(`${date}T12:00:00`);
        const formattedDate = new Intl.DateTimeFormat('es-MX', {
          weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
        }).format(dateValue);
        dialogTitle.textContent = `Servicios del ${formattedDate}`;
        dialogBody.innerHTML = dayEvents.length ? `
          <div class="operational-service-calendar-dialog-count">${dayEvents.length} ${dayEvents.length === 1 ? 'servicio' : 'servicios'}</div>
          ${dayEvents.map((item) => `
            <article class="operational-service-calendar-dialog-event is-${escapeHtml(item.status)}">
              <div class="operational-service-calendar-dialog-event-copy">
                <div class="operational-service-calendar-dialog-event-title">
                  <time>${escapeHtml(item.time)}</time>
                  <div>
                    <h4>${escapeHtml(item.service)}</h4>
                    ${item.medication ? `<span>${escapeHtml(item.medication)}</span>` : ''}
                  </div>
                </div>
                <p><b>${isHospitalizationCalendar ? 'Ubicaci&oacute;n / cama' : 'Sala'}:</b> ${escapeHtml(item.room)}</p>
                <p><b>Responsable:</b> ${escapeHtml(item.doctor)}</p>
                <p><b>Paciente:</b> ${escapeHtml(item.patient)}</p>
                <p><b>Folio:</b> ${escapeHtml(item.folio)}</p>
              </div>
              <div class="operational-service-calendar-dialog-actions">
                <span class="operational-service-calendar-dialog-status is-${escapeHtml(item.status)}">${escapeHtml(item.statusLabel)}</span>
                ${item.mixture ? `<button type="button" data-service-calendar-mixture="${escapeHtml(item.key)}">${escapeHtml(item.detailLabel || 'Ver mezcla')}</button>` : ''}
                ${item.detailUrl ? `<a class="is-primary" href="${escapeHtml(item.detailUrl)}">${escapeHtml(item.detailLabel || 'Ver detalle')}</a>` : ''}
                ${item.actionUrl ? `<a href="${escapeHtml(item.actionUrl)}">${escapeHtml(item.actionLabel)}</a>` : ''}
              </div>
            </article>
          `).join('')}
        ` : `
          <div class="operational-service-calendar-dialog-empty">
            <span>0 servicios</span>
            <h4>Sin servicios programados</h4>
            <p>${isHospitalizationCalendar ? 'No hay solicitudes de Hospitalizaci&oacute;n registradas para este d&iacute;a.' : 'No hay mezclas ni infusiones registradas para este d&iacute;a.'}</p>
          </div>
        `;
        dialog.showModal();
      };

      const setMixtureText = (selector, value) => {
        const target = mixtureDialog?.querySelector(selector);
        if (target) target.textContent = value || 'Sin dato';
      };

      const openMixture = (key) => {
        const item = events.find((eventItem) => String(eventItem.key) === String(key));
        const mixture = item?.mixture;
        if (!item || !mixture || !mixtureDialog) return;

        setMixtureText('[data-mixture-dialog-title]', mixture.title || 'Solicitud de mezcla');
        setMixtureText('[data-mixture-folio]', mixture.folio);
        setMixtureText('[data-mixture-type]', mixture.type);
        setMixtureText('[data-mixture-selected-medication]', item.medication || mixture.medications?.[mixture.selectedMedicationIndex || 0]?.medication || item.service);
        setMixtureText('[data-mixture-status]', mixture.status);
        setMixtureText('[data-mixture-patient]', mixture.patient?.name);
        setMixtureText('[data-mixture-age]', mixture.patient?.age);
        setMixtureText('[data-mixture-sex]', mixture.patient?.sex);
        setMixtureText('[data-mixture-diagnosis]', mixture.patient?.diagnosis);
        setMixtureText('[data-mixture-weight]', mixture.patient?.weight);
        setMixtureText('[data-mixture-height]', mixture.patient?.height);
        setMixtureText('[data-mixture-body-surface]', mixture.patient?.bodySurface);
        setMixtureText('[data-mixture-doctor]', mixture.clinical?.doctor);
        setMixtureText('[data-mixture-requesting-service]', mixture.clinical?.requestingService);
        setMixtureText('[data-mixture-room]', mixture.clinical?.room);
        setMixtureText('[data-mixture-shift]', mixture.clinical?.shift);
        setMixtureText('[data-mixture-priority]', mixture.clinical?.priority);
        setMixtureText('[data-mixture-observations]', mixture.observations);

        const status = mixtureDialog.querySelector('[data-mixture-status]');
        if (status) status.className = `operational-oncology-mixture-status is-${item.status}`;

        const medicationIndex = mixtureDialog.querySelector('[data-mixture-medication-index]');
        const dateInput = mixtureDialog.querySelector('[data-mixture-date]');
        if (medicationIndex) medicationIndex.value = item.medicationIndex;
        if (dateInput) dateInput.value = item.date;
        if (mixtureScheduleForm) mixtureScheduleForm.action = item.scheduleUpdateUrl;

        const medicationRows = mixtureDialog.querySelector('[data-mixture-medications]');
        if (medicationRows) {
          medicationRows.innerHTML = (mixture.medications || []).map((medication, index) => `
            <tr class="${index === Number(item.medicationIndex) ? 'is-selected' : ''}">
              <td><strong>${escapeHtml(medication.medication)}</strong></td>
              <td>${escapeHtml(medication.dose)}</td>
              <td>${escapeHtml(medication.diluent)}</td>
              <td>${escapeHtml(medication.finalVolume)}</td>
              <td>${escapeHtml(medication.route)}</td>
              <td>${escapeHtml(medication.duration)}</td>
              <td>${escapeHtml(medication.time)}</td>
            </tr>
          `).join('');
        }

        if (dialog?.open) dialog.close();
        mixtureDialog.showModal();
      };

      const renderCalendar = () => {
        visibleDate = new Date(visibleDate.getFullYear(), visibleDate.getMonth(), 1, 12);
        ensureYearOption(visibleDate.getFullYear());
        monthSelect.value = String(visibleDate.getMonth());
        yearSelect.value = String(visibleDate.getFullYear());

        const firstDay = new Date(visibleDate.getFullYear(), visibleDate.getMonth(), 1, 12);
        const startDay = new Date(firstDay);
        startDay.setDate(firstDay.getDate() - firstDay.getDay());
        const today = isoDate(new Date());
        const visibleEvents = filteredEvents();

        grid.innerHTML = Array.from({ length: 42 }, (_, index) => {
          const day = new Date(startDay);
          day.setDate(startDay.getDate() + index);
          const date = isoDate(day);
          const dayEvents = visibleEvents.filter((item) => item.date === date);
          const classes = [
            'operational-service-calendar-day',
            day.getMonth() !== visibleDate.getMonth() ? 'is-outside' : '',
            date === today ? 'is-today' : '',
            dayEvents.length ? 'has-events' : '',
          ].filter(Boolean).join(' ');
          const eventButtons = dayEvents.slice(0, 3).map((item) => `
            <button type="button" class="operational-service-calendar-event is-${escapeHtml(item.status)}" data-service-calendar-date="${date}">
              <strong>${escapeHtml(isMixtureCalendar && item.medication ? item.medication : item.service)}</strong>
              <span>${escapeHtml(item.room)} · ${escapeHtml(item.time)}</span>
            </button>
          `).join('');
          const remaining = dayEvents.length > 3
            ? `<button type="button" class="operational-service-calendar-more" data-service-calendar-date="${date}">+${dayEvents.length - 3} ${isMixtureCalendar ? 'mezclas' : 'servicios'}</button>`
            : '';

          return `
            <article class="${classes}" data-service-calendar-day="${date}" data-service-calendar-date="${date}">
              <button type="button" class="operational-service-calendar-date" data-service-calendar-date="${date}" aria-label="Ver programaci&oacute;n del ${date}">${day.getDate()}</button>
              <div>${eventButtons}${remaining}</div>
            </article>
          `;
        }).join('');
      };

      serviceCalendar.querySelectorAll('[data-service-calendar-step]').forEach((button) => {
        button.addEventListener('click', () => {
          visibleDate.setMonth(visibleDate.getMonth() + Number(button.dataset.serviceCalendarStep));
          renderCalendar();
        });
      });

      serviceCalendar.querySelector('[data-service-calendar-today]')?.addEventListener('click', () => {
        const today = new Date();
        visibleDate = new Date(today.getFullYear(), today.getMonth(), 1, 12);
        renderCalendar();
      });

      monthSelect?.addEventListener('change', () => {
        visibleDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1, 12);
        renderCalendar();
      });
      yearSelect?.addEventListener('change', () => {
        visibleDate = new Date(Number(yearSelect.value), Number(monthSelect.value), 1, 12);
        renderCalendar();
      });

      serviceCalendar.querySelectorAll('[data-service-calendar-filter]').forEach((filter) => {
        filter.addEventListener('change', renderCalendar);
      });
      searchInput?.addEventListener('input', renderCalendar);
      serviceCalendar.querySelector('[data-service-calendar-clear]')?.addEventListener('click', () => {
        serviceCalendar.querySelectorAll('[data-service-calendar-filter]').forEach((filter) => { filter.value = ''; });
        if (searchInput) searchInput.value = '';
        renderCalendar();
      });

      grid?.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-service-calendar-date]');
        if (trigger) openDay(trigger.dataset.serviceCalendarDate);
      });
      dialogBody?.addEventListener('click', (event) => {
        const mixtureButton = event.target.closest('[data-service-calendar-mixture]');
        if (mixtureButton) openMixture(mixtureButton.dataset.serviceCalendarMixture);
      });
      dialog?.querySelectorAll('[data-service-calendar-dialog-close]').forEach((button) => {
        button.addEventListener('click', () => dialog.close());
      });
      dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
      });
      mixtureDialog?.querySelectorAll('[data-mixture-detail-close]').forEach((button) => {
        button.addEventListener('click', () => mixtureDialog.close());
      });
      mixtureDialog?.addEventListener('click', (event) => {
        if (event.target === mixtureDialog) mixtureDialog.close();
      });

      renderCalendar();
    }

    function toggleOperationalAuthorizationMenu(toggle, event) {
      event.preventDefault();
      event.stopPropagation();

      const control = toggle.closest('.operational-authorization-control');
      const willOpen = !control.classList.contains('is-open');

      document.querySelectorAll('.operational-authorization-control.is-open').forEach((other) => {
        other.classList.remove('is-open');
        other.querySelector('[data-authorization-toggle]')?.setAttribute('aria-expanded', 'false');
      });

      control.classList.toggle('is-open', willOpen);
      toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    function toggleOperationalProviderMenu(toggle, event) {
      event.preventDefault();
      event.stopPropagation();
      const control = toggle.closest('.operational-provider-control');
      const willOpen = !control.classList.contains('is-open');

      document.querySelectorAll('.operational-provider-control.is-open').forEach((other) => {
        other.classList.remove('is-open');
        other.querySelector('[data-provider-toggle]')?.setAttribute('aria-expanded', 'false');
      });

      control.classList.toggle('is-open', willOpen);
      toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    document.addEventListener('click', (event) => {
      document.querySelectorAll('.operational-authorization-control.is-open').forEach((control) => {
        if (control.contains(event.target)) return;
        control.classList.remove('is-open');
        control.querySelector('[data-authorization-toggle]')?.setAttribute('aria-expanded', 'false');
      });
      document.querySelectorAll('.operational-provider-control.is-open').forEach((control) => {
        if (control.contains(event.target)) return;
        control.classList.remove('is-open');
        control.querySelector('[data-provider-toggle]')?.setAttribute('aria-expanded', 'false');
      });
    });
  </script>
@endsection
