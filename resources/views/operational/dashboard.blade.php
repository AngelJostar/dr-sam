@extends('layouts.app', ['title' => 'Area operativa'])

@section('body_class', 'operational-native-body')

@php
  $statusLabels = [
    'draft' => 'Borrador',
    'requested' => 'Pendiente',
    'accepted' => 'Aceptada',
    'preparing' => 'En revision',
    'in_route' => 'En ruta',
    'delivered' => 'Entregada',
    'rejected' => 'Rechazada',
    'cancelled' => 'Cancelada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $areaMeta = [
    'nursing' => ['Enfermeria', 'Enfermeria', 'Responsable de Area'],
    'oncology' => ['Centro Oncologico', 'Centro Oncologico', 'Operador'],
    'inpatient-pharmacy' => ['Farmacia intrahospitalaria', 'Farmacia intrahospitalaria', 'Responsable de Area'],
  ];
  [$areaLabel, $areaMetricLabel, $areaRoleLabel] = $areaMeta[$areaKey] ?? $areaMeta['nursing'];
  $isOncology = $areaKey === 'oncology';
  $isInpatientPharmacy = $areaKey === 'inpatient-pharmacy';
  $unitName = $contextUnit?->name ?? $profile?->medicalUnit?->name ?? 'H.G. CHIMALHUACAN';
  $unitCode = $contextUnit?->code ?? $contextUnit?->clues ?? $profile?->medicalUnit?->code ?? $profile?->medicalUnit?->clues ?? 'MCIMB001841';
  $institutionName = $contextUnit?->institution?->name ?? $profile?->medicalUnit?->institution?->name ?? $unitName;
  $visibleRequests = $section === 'history' ? $historicalProviderRequests : $pendingProviderRequests;
  $pendingRequests = $pendingProviderRequests->count();
  $totalRequests = $providerRequests->count();
  $metricCards = [
    'Enfermeria' => $pendingRequests,
    'Farmacia intrahospitalaria' => max(0, $totalRequests - $pendingRequests + 1),
    'Centro Oncologico' => $providerRequests->where('request_type', 'chemo')->count() ?: 1,
    'Total' => $totalRequests,
  ];
  $moduleGroups = [
    'nursing' => 'Enfermeria',
    'oncology' => 'Centro Oncologico',
    'inpatient-pharmacy' => 'Farmacia Intrahospitalaria',
  ];
  $patientRows = $patients->take(12);
@endphp

@section('content')
  <div class="operational-native-screen">
    <aside class="operational-native-sidebar" aria-label="Navegacion operativa">
      <div class="operational-native-brand">
        <span class="operational-native-mark" aria-hidden="true">+</span>
        <div>
          <strong>Area Operativa</strong>
          <span>{{ $institutionName }}</span>
        </div>
      </div>

      <nav class="operational-native-menu operational-legacy-menu">
        @foreach ($moduleGroups as $groupKey => $group)
          @php $isActiveGroup = $groupKey === $areaKey; @endphp
          <section @class(['is-active' => $isActiveGroup, 'is-oncology' => $groupKey === 'oncology'])>
            <h2 class="operational-legacy-group-title">
              <span class="operational-legacy-group-icon" aria-hidden="true">{{ $groupKey === 'nursing' ? '⊕' : ($groupKey === 'oncology' ? '⊕' : '☩') }}</span>
              {{ $group }}
            </h2>
            @if ($groupKey === 'oncology')
              <div class="operational-legacy-subgroup is-administrative">
                <small class="operational-menu-caption">AREA ADMINISTRATIVA</small>
                <a @class(['is-selected' => $isActiveGroup && $section === 'services-pending']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'services-pending']) }}">Servicios pendientes</a>
                <a @class(['is-selected' => $isActiveGroup && $section === 'services-history']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'services-history']) }}">Historial de servicios</a>
                <a @class(['is-selected' => $isActiveGroup && $section === 'service-create']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'service-create']) }}">Nuevo servicio</a>
                <a @class(['is-selected' => $isActiveGroup && $section === 'service-format']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'service-format']) }}">Formato de solicitud</a>
              </div>
              <div class="operational-legacy-subgroup is-operational">
                <small class="operational-menu-caption">AREA OPERATIVA</small>
                <a @class(['is-selected' => $isActiveGroup && $section === 'mixes']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'mixes']) }}">Mezclas programadas</a>
                <a @class(['is-selected' => $isActiveGroup && $section === 'mix-history']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'mix-history']) }}">Historial de mezclas</a>
              </div>
              <a @class(['operational-legacy-calendar', 'is-selected' => $isActiveGroup && $section === 'calendar']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'calendar']) }}">Calendario de servicios</a>
              <a @class(['is-selected' => $isActiveGroup && $section === 'patients']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'patients']) }}">Pacientes</a>
              <a @class(['is-selected' => $isActiveGroup && $section === 'patient-create']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'patient-create']) }}">Alta de pacientes</a>
              <a @class(['is-selected' => $isActiveGroup && $section === 'infusion-rooms']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'infusion-rooms']) }}">Salas de infusion</a>
            @else
              <a @class(['is-selected' => $isActiveGroup && $section === 'pending']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'pending']) }}">Solicitudes pendientes</a>
              <a @class(['is-selected' => $isActiveGroup && $section === 'history']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'history']) }}">Historial de solicitudes</a>
              <a @class(['is-selected' => $isActiveGroup && $section === 'patients']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'patients']) }}">Pacientes</a>
              <a @class(['is-selected' => $isActiveGroup && $section === 'patient-create']) href="{{ route('operational.dashboard', ['area' => $groupKey, 'section' => 'patient-create']) }}">Alta de paciente</a>
            @endif
          </section>
        @endforeach
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

      <header class="operational-native-header">
        <div>
          <p class="eyebrow">{{ strtoupper($areaLabel) }}</p>
          <h1>Modulo Area Operativa - {{ $areaLabel }}</h1>
          <p>{{ $areaLabel }} - {{ $areaLabel }} - {{ $institutionName }}</p>
        </div>

        <form class="operational-native-controls">
          <label>
            Operar como
            <select>
              <option>{{ $areaLabel }} - {{ $institutionName }} - {{ $areaRoleLabel }} - {{ $areaLabel }}</option>
            </select>
          </label>
          <label>
            Rol
            <select>
              <option>{{ $areaLabel }}</option>
            </select>
          </label>
          <label class="span-2">
            Unidad
            <select>
              <option>{{ $unitName }} - {{ $unitCode }}</option>
            </select>
          </label>
          <time>{{ strtolower(now()->format('d M Y')) }}</time>
        </form>
      </header>

      @if (in_array($section, ['pending', 'history'], true))
        <section class="operational-native-metrics" aria-label="Resumen operativo">
          @foreach ($metricCards as $label => $value)
            <article @class(['is-active' => $label === $areaMetricLabel])>
              <span>{{ $label }}</span>
              <strong>{{ number_format($value) }}</strong>
              <small>{{ $loop->last ? 'Solicitudes visibles' : 'Pendientes de validacion' }}</small>
            </article>
          @endforeach
        </section>

        <section class="operational-native-table-card">
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Historial de solicitudes</p>
              <h2>Solicitudes enviadas por hospitales</h2>
              <span>Area operativa de {{ $institutionName }} - {{ $section === 'history' ? 'Historial de solicitudes enviadas por hospitales' : 'Solicitudes pendientes de '.$areaLabel }}</span>
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
                @foreach (['requested', 'accepted', 'preparing', 'in_route', 'delivered', 'rejected', 'cancelled'] as $status)
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
                    $authorizationRequirements = data_get($providerRequest->payload, 'authorization_requirements', $isOncology ? ['oncology', 'pharmacy'] : ['operational', 'pharmacy']);
                    $authorizationLabels = $isOncology
                      ? ['oncology' => 'Centro Onc.', 'pharmacy' => 'Farm. Intra.']
                      : ['operational' => 'Enfermeria', 'pharmacy' => 'Farm. Intra.'];
                    $authorizationAreaKeys = [
                      'nursing' => 'operational', 'enfermeria' => 'operational',
                      'inpatient-pharmacy' => 'pharmacy', 'farmacia' => 'pharmacy',
                      'oncology' => 'oncology', 'oncologia' => 'oncology',
                    ];
                    $currentAuthorization = $authorizationAreaKeys[$areaKey] ?? null;
                    $profileAuthorization = $authorizationAreaKeys[$profile?->area?->key] ?? null;
                    $operationalRoles = ['superadmin', 'admin', 'institution', 'unit', 'operational'];
                    $canOperateCurrentArea = in_array(auth()->user()?->role, $operationalRoles, true)
                      && $currentAuthorization !== null;
                    $authorizationIsLocked = in_array($providerRequest->status, ['delivered', 'cancelled', 'rejected'], true);
                    $canEditAuthorization = $canOperateCurrentArea;
                    $allAuthorizationsApproved = collect($authorizationRequirements)
                      ->every(fn ($key) => ($requestAuthorizations[$key] ?? 'pending') === 'approved');
                    $latestAuthorizationEvent = $providerRequest->statusEvents
                      ->first(fn ($event) => data_get($event->metadata, 'type') === 'authorization');
                    $centralStatus = match (true) {
                      data_get($providerRequest->payload, 'cancellation') !== null,
                      in_array($providerRequest->status, ['cancelled', 'rejected'], true) => 'Cancelada',
                      $providerRequest->status === 'delivered' => 'Entregada',
                      $providerRequest->status === 'in_route' => 'En ruta',
                      $providerRequest->status === 'preparing' => 'En preparación',
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
                            $authorizationStatus = $requestAuthorizations[$authorizationKey] ?? 'pending';
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
                                data-authorization-area="{{ $authorizationKey === 'operational' ? 'nursing' : $authorizationKey }}"
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
                            <span class="authorization-pill auth-{{ $authorizationStatus }} authorization-locked" title="Autorización de {{ $authorizationLabel }}: {{ $authorizationStatusLabel }}">
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
                          $providerWasSent = $providerRequest->status === 'accepted' && filled($assignedProviderName);
                        @endphp
                        @if ($providerWasSent)
                          <span class="authorization-pill auth-approved">{{ $assignedProviderName }}</span>
                        @elseif ($allAuthorizationsApproved && ! $authorizationIsLocked && $providerRequest->request_type !== 'chemo')
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
                          <button type="button" class="operational-disabled-action" disabled title="Requiere autorización de Enfermería y Farmacia intrahospitalaria">Pendiente</button>
                        @endif
                      </td>
                      <td>
                        @if ($isInpatientPharmacy && $allAuthorizationsApproved && ! $authorizationIsLocked)
                          <form class="operational-mini-form" method="post" action="{{ route('operational.provider-requests.status', $providerRequest) }}">
                            @csrf
                            @method('patch')
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="operating_area" value="inpatient-pharmacy">
                            <input type="hidden" name="notes" value="Cancelado por Farmacia intrahospitalaria">
                            <button type="submit" class="danger">Cancelar</button>
                          </form>
                        @else
                          <button type="button" class="operational-disabled-action" disabled title="Solo Farmacia intrahospitalaria puede cancelar cuando ambas autorizaciones están aprobadas">Cancelar</button>
                        @endif
                      </td>
                    @endunless
                    <td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => $section]) }}">Ver</a></td>
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
      @elseif (in_array($section, ['services-pending', 'services-history', 'service-create', 'service-format', 'mixes', 'mix-history', 'calendar', 'infusion-rooms'], true))
        @include('operational.sections.oncology')
      @else
        @php($editingPatient = $patientRows->firstWhere('id', (int) request('edit_patient')))
        <section class="operational-native-table-card operational-patients-card">
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Consulta externa</p>
              <h2>Catalogo de pacientes</h2>
              <span>{{ $institutionName }} - Pacientes registrados</span>
            </div>
            <div class="operational-heading-actions">
              <strong>{{ $patientRows->count() }} pacientes</strong>
              <a class="operational-primary-button" href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients', 'modal' => 'patient']) }}"><span aria-hidden="true">+</span> Nuevo paciente</a>
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
                    <td><a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients', 'edit_patient' => $patient->id]) }}">Editar</a></td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9">Sin pacientes registrados para esta area operativa.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <div id="alta-paciente" @class(['operational-modal', 'is-open' => $section === 'patient-create' || request('modal') === 'patient' || (bool) $editingPatient]) role="dialog" aria-modal="true" aria-labelledby="alta-paciente-title">
          <a class="operational-modal-backdrop" href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients']) }}" aria-label="Cerrar"></a>
          <form class="operational-modal-card" method="post" action="{{ $editingPatient ? route('operational.patients.update', $editingPatient) : route('operational.patients.store') }}">
            @csrf
            @if($editingPatient) @method('patch') @endif
            <input type="hidden" name="area" value="{{ $areaKey }}">
            <header>
              <div>
                <h2 id="alta-paciente-title">{{ $editingPatient ? 'Editar paciente' : 'Alta de paciente' }}</h2>
                <p>{{ $institutionName }} - Catalogo de pacientes</p>
              </div>
              <a href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients']) }}">Cerrar</a>
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
              <a href="{{ route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients']) }}">Cancelar</a>
              <button type="submit">{{ $editingPatient ? 'Guardar cambios' : 'Guardar paciente' }}</button>
            </footer>
          </form>
        </div>
      @endif
    </section>
  </div>
  <script>
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
