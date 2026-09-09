
<?php $__env->startSection('body_class', 'operational-native-body'); ?>

<?php
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
?>

<?php $__env->startSection('content'); ?>
  <div class="operational-native-screen">
    <header class="operational-native-topbar" aria-label="Barra superior operativa">
      <strong>MODULO OPERATIVO</strong>
      <span><?php echo e(strtoupper($institutionName)); ?></span>
      <div class="operational-native-user">
        <button type="button" aria-label="Notificaciones">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        </button>
        <div>
          <strong>Usuario activo</strong>
          <small><?php echo e($unitName); ?></small>
        </div>
        <form method="post" action="<?php echo e(route('logout')); ?>" class="operational-native-logout">
          <?php echo csrf_field(); ?>
          <button type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
            Cerrar sesion
          </button>
        </form>
      </div>
    </header>

    <aside class="operational-native-sidebar" aria-label="Navegacion operativa">
      <nav class="operational-native-menu operational-area-menu">
        <?php $__currentLoopData = $moduleGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php $isActiveGroup = ! $isPatientCatalog && $groupKey === $areaKey; ?>
          <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $isActiveGroup]); ?>" href="<?php echo e(route('operational.dashboard', $areaDefaultRoutes[$groupKey] ?? ['area' => $groupKey, 'section' => 'pending'])); ?>">
            <span aria-hidden="true">
              <?php if($group['icon'] === 'hospital'): ?>
                <svg viewBox="0 0 24 24"><path d="M4 21V5a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v16"/><path d="M16 9h3a1 1 0 0 1 1 1v11"/><path d="M8 8h4M10 6v4M8 14h4M8 18h4"/></svg>
              <?php elseif($group['icon'] === 'oncology'): ?>
                <svg viewBox="0 0 24 24"><path d="M4 6h16v15H4z"/><path d="M9 3h6v3H9z"/><path d="M12 10v7M8.5 13.5h7"/></svg>
              <?php else: ?>
                <svg viewBox="0 0 24 24"><path d="m7 16 9-9a3 3 0 0 1 4 4l-9 9a3 3 0 0 1-4-4Z"/><path d="m12 11 4 4"/></svg>
              <?php endif; ?>
            </span>
            <?php echo e($group['label']); ?>

            <i aria-hidden="true">›</i>
          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <a href="<?php echo e(route('outpatient.dashboard')); ?>">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M6 4v5a5 5 0 0 0 10 0V4"/><path d="M9 4H5"/><path d="M17 4h-4"/><path d="M11 14v2a4 4 0 0 0 8 0v-3"/><circle cx="19" cy="10" r="2"/></svg>
          </span>
          Consulta Externa
          <i aria-hidden="true">›</i>
        </a>

        <a href="<?php echo e(route('external-pharmacy.dashboard')); ?>">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 9v11h16V9"/><path d="M3 9h18l-2-5H5L3 9Z"/><path d="M12 12v5M9.5 14.5h5"/></svg>
          </span>
          Farmacia Externa
          <i aria-hidden="true">›</i>
        </a>

        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $isPatientCatalog]); ?>" href="<?php echo e(route('operational.patients.index', $patientCatalogRouteParameters)); ?>">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </span>
          Catalogo de pacientes
          <i aria-hidden="true">›</i>
        </a>
      </nav>
    </aside>

    <section class="operational-native-workspace">
      <?php if(session('status')): ?>
        <div class="notice success"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <?php if($errors->any()): ?>
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if($isOncology): ?>
        <section class="operational-oncology-carousel operational-compact-carousel" aria-label="Filtros de Centro Oncologico">
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
          <div class="operational-oncology-carousel-track">
            <?php $__currentLoopData = $oncologyTrackOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trackKey => $trackOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['operational-oncology-filter', 'is-active' => $activeOncologyCarouselItem === $trackKey]); ?>" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => $trackOption['section'], 'oncology_track' => $trackOption['track']] + ($contextUnit ? ['unit' => $contextUnit->id] : []))); ?>">
                <span class="operational-oncology-filter-initial" aria-hidden="true"><?php echo e($trackOption['initial']); ?></span>
                <span class="operational-oncology-filter-copy">
                  <strong><?php echo e($trackOption['label']); ?></strong>
                  <?php if($activeOncologyCarouselItem === $trackKey): ?>
                    <small>✓ Seleccionada</small>
                  <?php endif; ?>
                </span>
              </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
        </section>
      <?php elseif($isHospitalization): ?>
        <section class="operational-oncology-carousel operational-compact-carousel operational-hospitalization-carousel" aria-label="Filtros de Hospitalizacion">
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
          <div class="operational-oncology-carousel-track">
            <?php $__currentLoopData = $hospitalizationTrackOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trackKey => $trackOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['operational-oncology-filter', 'is-active' => $hospitalizationTrack === $trackKey]); ?>" href="<?php echo e(route('operational.dashboard', ['area' => 'nursing', 'section' => 'calendar', 'hospitalization_track' => $trackKey] + ($contextUnit ? ['unit' => $contextUnit->id] : []))); ?>">
                <span class="operational-oncology-filter-initial" aria-hidden="true"><?php echo e($trackOption['initial']); ?></span>
                <span class="operational-oncology-filter-copy">
                  <strong><?php echo e($trackOption['label']); ?></strong>
                  <?php if($hospitalizationTrack === $trackKey): ?>
                    <small>✓ Seleccionada</small>
                  <?php endif; ?>
                </span>
              </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
        </section>
      <?php elseif($isInpatientPharmacy): ?>
        <section class="operational-oncology-carousel operational-compact-carousel operational-inpatient-pharmacy-carousel" aria-label="Filtros de Farmacia intrahospitalaria">
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
          <div class="operational-oncology-carousel-track">
            <a class="operational-oncology-filter is-active" href="<?php echo e(route('operational.dashboard', ['area' => 'inpatient-pharmacy', 'section' => 'pending'] + ($contextUnit ? ['unit' => $contextUnit->id] : []))); ?>">
              <span class="operational-oncology-filter-initial" aria-hidden="true">F</span>
              <span class="operational-oncology-filter-copy">
                <strong>Farmacia intrahospitalaria</strong>
                <small>✓ Seleccionada</small>
              </span>
            </a>
          </div>
          <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
        </section>
      <?php endif; ?>

      <?php if (! ($isPatientCatalog)): ?>
        <nav class="operational-section-tabs" aria-label="Secciones de <?php echo e($areaLabel); ?>">
          <?php $__currentLoopData = $currentSectionNavigation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $navigationItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => in_array($section, $navigationItem['active_sections'] ?? [$navigationItem['section']], true)]); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $areaKey, 'section' => $navigationItem['section']] + ($isOncology ? ['oncology_track' => $oncologyTrack] : []) + ($isHospitalization ? ['hospitalization_track' => $hospitalizationTrack] : []) + ($contextUnit ? ['unit' => $contextUnit->id] : []))); ?>">
              <?php echo e($navigationItem['label']); ?>

            </a>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>
      <?php endif; ?>

      <?php if(in_array($section, ['pending', 'history'], true)): ?>
        <section class="operational-native-table-card">
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Historial de solicitudes</p>
              <?php if (! ($isHospitalization)): ?>
                <h2>Solicitudes enviadas por hospitales</h2>
                <span>Modulo operativo de <?php echo e($institutionName); ?> - <?php echo e($section === 'history' ? 'Historial de solicitudes enviadas por hospitales' : 'Solicitudes pendientes de '.$areaLabel); ?></span>
              <?php endif; ?>
            </div>
            <strong><?php echo e($section === 'history' ? $visibleRequests->count().' solicitudes' : $pendingRequests.' pendiente'.($pendingRequests === 1 ? '' : 's')); ?></strong>
          </div>

          <form class="operational-native-filter" method="get">
            <input type="hidden" name="section" value="<?php echo e($section); ?>">
            <input type="hidden" name="area" value="<?php echo e($areaKey); ?>">
            <label>
              <span aria-hidden="true">âŒ•</span>
              <input name="search" placeholder="Buscar folio, paciente, medico o servicio">
            </label>
            <label>
              Estado
              <select name="status">
                <option value="">Todos</option>
                <?php $__currentLoopData = ['requested', 'accepted', 'preparing', 'in_route', 'delivered', 'rejected', 'cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($status); ?>"><?php echo e($statusText($status)); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
                  <?php if (! ($isOncology)): ?>
                    <th>Enviar a proveedor</th>
                    <th>Cancelar Solicitud</th>
                  <?php endif; ?>
                  <th>Visualizar Solicitud</th>
                  <th>Estado de Central de Mezclas</th>
                  <th>Observaciones</th>
                  <?php if($isInpatientPharmacy): ?>
                    <th>Remision de entrega</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $visibleRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $providerRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $requestAuthorizations = data_get($providerRequest->payload, 'authorizations', []);
                    $authorizationRequirements = data_get($providerRequest->payload, 'authorization_requirements', $isOncology ? ['oncology', 'pharmacy'] : ['operational', 'pharmacy']);
                    $authorizationLabels = $isOncology
                      ? ['oncology' => 'Centro Onc.', 'pharmacy' => 'Farm. Intra.']
                      : ['operational' => 'Hospitalizacion', 'pharmacy' => 'Farm. Intra.'];
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
                  ?>
                  <tr>
                    <td><strong><?php echo e($providerRequest->external_id ?? 'NPT-'.$providerRequest->id); ?></strong></td>
                    <td><?php echo e($providerRequest->requested_at?->format('d M Y, h:i a') ?? 'N/A'); ?></td>
                    <td><strong><?php echo e($providerRequest->patient?->full_name ?? 'Sin paciente'); ?></strong></td>
                    <td>REG-<?php echo e(str_pad((string) $providerRequest->id, 4, '0', STR_PAD_LEFT)); ?></td>
                    <td>
                      <?php if($isOncology): ?>
                        <span class="operational-chip oncology">Oncologia medica</span>
                      <?php else: ?>
                        <?php echo e(data_get($providerRequest->payload, 'service', $providerRequest->request_type === 'chemo' ? 'Oncologia medica' : 'Medicina interna')); ?>

                      <?php endif; ?>
                    </td>
                    <td><?php echo e(data_get($providerRequest->payload, 'volume', $isOncology ? '500' : '1200')); ?></td>
                    <td><?php echo e(data_get($providerRequest->payload, 'doctor', $isOncology ? 'Dra. Laura Benitez' : 'Carter Jimmy')); ?></td>
                    <td>
                      <div class="operational-authorization-list">
                        <?php $__currentLoopData = $authorizationLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $authorizationKey => $authorizationLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <?php
                            $authorizationStatus = $requestAuthorizations[$authorizationKey] ?? 'pending';
                            $authorizationStatusLabel = ['approved' => 'Autorizado', 'rejected' => 'Rechazado', 'pending' => 'Pendiente'][$authorizationStatus] ?? 'Pendiente';
                            $authorizationClass = ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning'][$authorizationStatus] ?? 'warning';
                            $canEditThisAuthorization = ! $authorizationIsLocked
                              && $canEditAuthorization
                              && ($currentAuthorization === $authorizationKey || in_array(auth()->user()?->role, ['superadmin', 'admin'], true));
                          ?>
                          <?php if($canEditThisAuthorization): ?>
                            <div class="operational-authorization-control">
                              <button
                                class="authorization-pill authorization-button auth-<?php echo e($authorizationStatus); ?>"
                                type="button"
                                data-authorization-toggle="true"
                                data-authorization-area="<?php echo e($authorizationKey === 'operational' ? 'nursing' : $authorizationKey); ?>"
                                data-authorization-folio="<?php echo e($providerRequest->external_id ?? 'NPT-'.$providerRequest->id); ?>"
                                data-authorization-scope="<?php echo e($section); ?>"
                                aria-expanded="false"
                                aria-label="<?php echo e($authorizationLabel); ?> <?php echo e($authorizationStatusLabel); ?>"
                                title="<?php echo e($authorizationLabel); ?>: <?php echo e($authorizationStatusLabel); ?>"
                                style="cursor:pointer;pointer-events:auto"
                                onclick="toggleOperationalAuthorizationMenu(this, event)"
                              >
                                <?php echo e($authorizationLabel); ?>

                              </button>
                              <form class="operational-authorization-menu" method="post" action="<?php echo e(route('operational.provider-requests.authorizations.update', $providerRequest)); ?>">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('patch'); ?>
                                <input type="hidden" name="authorization" value="<?php echo e($authorizationKey); ?>">
                                <input type="hidden" name="operating_area" value="<?php echo e($areaKey); ?>">
                                <button type="submit" name="status" value="approved" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['success', 'is-selected' => $authorizationStatus === 'approved']); ?>">Autorizado</button>
                                <button type="submit" name="status" value="pending" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['warning', 'is-selected' => $authorizationStatus === 'pending']); ?>">Pendiente</button>
                                <button type="submit" name="status" value="rejected" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['danger', 'is-selected' => $authorizationStatus === 'rejected']); ?>">Rechazado</button>
                              </form>
                            </div>
                          <?php else: ?>
                            <span class="authorization-pill auth-<?php echo e($authorizationStatus); ?> authorization-locked" title="Autorización de <?php echo e($authorizationLabel); ?>: <?php echo e($authorizationStatusLabel); ?>">
                              <?php echo e($authorizationLabel); ?>

                            </span>
                          <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </div>
                    </td>
                    <td><strong><?php echo e(data_get($providerRequest->payload, 'mix_status', $isOncology ? 'Protocolo por validar' : 'No Estable')); ?></strong></td>
                    <?php if (! ($isOncology)): ?>
                      <td>
                        <?php
                          $assignedProviderName = data_get($providerRequest->payload, 'provider_assignment.name');
                          $providerWasSent = $providerRequest->status === 'accepted' && filled($assignedProviderName);
                        ?>
                        <?php if($providerWasSent): ?>
                          <span class="authorization-pill auth-approved"><?php echo e($assignedProviderName); ?></span>
                        <?php elseif($allAuthorizationsApproved && ! $authorizationIsLocked && $providerRequest->request_type !== 'chemo'): ?>
                          <div class="operational-provider-control">
                            <button
                              class="authorization-pill authorization-button auth-pending"
                              type="button"
                              data-provider-toggle="true"
                              aria-expanded="false"
                              onclick="toggleOperationalProviderMenu(this, event)"
                            >Pendiente</button>
                            <form class="operational-provider-menu" method="post" action="<?php echo e(route('operational.provider-requests.status', $providerRequest)); ?>">
                              <?php echo csrf_field(); ?>
                              <?php echo method_field('patch'); ?>
                              <input type="hidden" name="status" value="accepted">
                              <input type="hidden" name="provider_name" value="Prodifem">
                              <input type="hidden" name="notes" value="Enviado a Prodifem desde tablero operativo">
                              <button type="submit" class="authorization-pill auth-approved">Prodifem</button>
                            </form>
                          </div>
                        <?php else: ?>
                          <button type="button" class="operational-disabled-action" disabled title="Requiere autorización de Hospitalizacion y Farmacia intrahospitalaria">Pendiente</button>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php if($isInpatientPharmacy && $allAuthorizationsApproved && ! $authorizationIsLocked): ?>
                          <form class="operational-mini-form" method="post" action="<?php echo e(route('operational.provider-requests.status', $providerRequest)); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('patch'); ?>
                            <input type="hidden" name="status" value="cancelled">
                            <input type="hidden" name="operating_area" value="inpatient-pharmacy">
                            <input type="hidden" name="notes" value="Cancelado por Farmacia intrahospitalaria">
                            <button type="submit" class="danger">Cancelar</button>
                          </form>
                        <?php else: ?>
                          <button type="button" class="operational-disabled-action" disabled title="Solo Farmacia intrahospitalaria puede cancelar cuando ambas autorizaciones están aprobadas">Cancelar</button>
                        <?php endif; ?>
                      </td>
                    <?php endif; ?>
                    <td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => $areaKey, 'section' => $section])); ?>">Ver</a></td>
                    <td><span class="operational-chip"><?php echo e($centralStatus); ?></span></td>
                    <td><?php echo e($latestAuthorizationEvent?->notes ?: ($providerRequest->statusEvents->first()?->notes ?? ($isOncology ? 'Primera validacion pendiente por Centro Oncologico.' : 'Sin observaciones'))); ?></td>
                    <?php if($isInpatientPharmacy): ?>
                      <td><?php echo e(data_get($providerRequest->payload, 'delivery_remission', 'Pendiente')); ?></td>
                    <?php endif; ?>
                  </tr>
                  <?php if($cancellation = data_get($providerRequest->payload, 'cancellation')): ?>
                    <tr class="operational-cancellation-row">
                      <td colspan="<?php echo e($isOncology ? 12 : ($isInpatientPharmacy ? 15 : 14)); ?>">
                        <strong><?php echo e(data_get($cancellation, 'message', 'Solicitud cancelada.')); ?></strong>
                        <?php if(data_get($cancellation, 'actor')): ?>
                          Rechazado por <?php echo e(data_get($cancellation, 'actor')); ?>.
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr>
                    <td colspan="<?php echo e($isOncology ? 12 : ($isInpatientPharmacy ? 15 : 14)); ?>">No hay solicitudes para esta vista.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php elseif($section === 'calendar' && $isHospitalization): ?>
        <?php $calendarMode = 'hospitalization'; ?>
        <?php echo $__env->make('operational.sections.service-calendar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php elseif(in_array($section, ['services-pending', 'services-history', 'services-preparation', 'services-scheduled', 'service-create', 'service-format', 'mixes', 'mix-history', 'calendar', 'infusion-rooms', 'infusion-room-calendar', 'infusion-room-catalog', 'support', 'support-ai', 'support-analytics'], true)): ?>
        <?php echo $__env->make('operational.sections.oncology', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php else: ?>
        <?php $editingPatient = $patientRows->firstWhere('id', (int) request('edit_patient')); ?>
        <section class="operational-native-table-card operational-patients-card" data-operational-patient-catalog>
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Catalogo compartido</p>
              <h2>Catalogo de pacientes</h2>
              <span><?php echo e($unitName); ?> - Pacientes vinculados a todas las areas operativas</span>
            </div>
            <div class="operational-heading-actions">
              <strong><?php echo e($patientRows->count()); ?> pacientes</strong>
              <a class="operational-primary-button" href="<?php echo e(route('operational.patients.index', array_merge($patientCatalogRouteParameters, ['modal' => 'patient']))); ?>"><span aria-hidden="true">+</span> Nuevo paciente</a>
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
                <?php $__empty_1 = true; $__currentLoopData = $patientRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr>
                    <td><strong><?php echo e($patient->first_name ?: str($patient->full_name)->before(' ')); ?></strong></td>
                    <td><strong><?php echo e($patient->last_name ?: str($patient->full_name)->after(' ')); ?></strong></td>
                    <td><?php echo e($patient->birth_date ? (int) $patient->birth_date->age : data_get($patient->metadata, 'age', 'N/A')); ?></td>
                    <td><strong><?php echo e($patient->curp ?? data_get($patient->metadata, 'curp', 'Sin CURP')); ?></strong></td>
                    <td><?php echo e(data_get($patient->metadata, 'nss_federal', 'Sin NSS')); ?></td>
                    <td><?php echo e(data_get($patient->metadata, 'nss_estatal', 'Sin NSS')); ?></td>
                    <td><?php echo e($patient->platform_number ?? 'USR-'.str_pad((string) $patient->id, 6, '0', STR_PAD_LEFT)); ?></td>
                    <td><?php echo e(data_get($patient->metadata, 'state', 'Mexico')); ?></td>
                    <td><a class="operational-secondary-button" href="<?php echo e(route('operational.patients.index', array_merge($patientCatalogRouteParameters, ['edit_patient' => $patient->id]))); ?>">Editar</a></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr>
                    <td colspan="9">Sin pacientes registrados para esta unidad.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <div id="alta-paciente" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['operational-modal', 'is-open' => $section === 'patient-create' || request('modal') === 'patient' || (bool) $editingPatient]); ?>" role="dialog" aria-modal="true" aria-labelledby="alta-paciente-title">
          <a class="operational-modal-backdrop" href="<?php echo e(route('operational.patients.index', $patientCatalogRouteParameters)); ?>" aria-label="Cerrar"></a>
          <form class="operational-modal-card" method="post" action="<?php echo e($editingPatient ? route('operational.patients.update', $editingPatient) : route('operational.patients.store')); ?>">
            <?php echo csrf_field(); ?>
            <?php if($editingPatient): ?> <?php echo method_field('patch'); ?> <?php endif; ?>
            <?php if($contextUnit): ?><input type="hidden" name="unit" value="<?php echo e($contextUnit->id); ?>"><?php endif; ?>
            <header>
              <div>
                <h2 id="alta-paciente-title"><?php echo e($editingPatient ? 'Editar paciente' : 'Alta de paciente'); ?></h2>
                <p><?php echo e($institutionName); ?> - Catalogo de pacientes</p>
              </div>
              <a href="<?php echo e(route('operational.patients.index', $patientCatalogRouteParameters)); ?>">Cerrar</a>
            </header>

            <div class="operational-modal-body">
              <label>Nombre<input name="first_name" type="text" required value="<?php echo e(old('first_name', $editingPatient?->first_name)); ?>"></label>
              <label>Apellidos<input name="last_name" type="text" required value="<?php echo e(old('last_name', $editingPatient?->last_name)); ?>"></label>
              <label>Fecha de nacimiento<input name="birth_date" type="date" value="<?php echo e(old('birth_date', $editingPatient?->birth_date?->format('Y-m-d'))); ?>"></label>
              <label>Sexo<select name="sex"><option value="">Sin especificar</option><option value="female" <?php if(old('sex', $editingPatient?->sex) === 'female'): echo 'selected'; endif; ?>>Femenino</option><option value="male" <?php if(old('sex', $editingPatient?->sex) === 'male'): echo 'selected'; endif; ?>>Masculino</option><option value="other" <?php if(old('sex', $editingPatient?->sex) === 'other'): echo 'selected'; endif; ?>>Otro</option></select></label>
              <label>CURP<input name="curp" type="text" maxlength="18" value="<?php echo e(old('curp', $editingPatient?->curp)); ?>"></label>
              <label>Numero de seguridad social federal<input name="nss_federal" type="text" value="<?php echo e(old('nss_federal', data_get($editingPatient?->metadata, 'nss_federal'))); ?>"></label>
              <label>Numero de seguridad social estatal<input name="nss_estatal" type="text" value="<?php echo e(old('nss_estatal', data_get($editingPatient?->metadata, 'nss_estatal'))); ?>"></label>
              <label>Numero de usuario de la plataforma<input name="platform_number" type="text" value="<?php echo e(old('platform_number', $editingPatient?->platform_number)); ?>"></label>
              <label>Telefono<input name="phone" type="text" value="<?php echo e(old('phone', $editingPatient?->phone)); ?>"></label>
              <label>Correo<input name="email" type="email" value="<?php echo e(old('email', $editingPatient?->email)); ?>"></label>
              <label>Entidad Federativa<input name="state" type="text" value="<?php echo e(old('state', data_get($editingPatient?->metadata, 'state', 'MEXICO'))); ?>"></label>
            </div>

            <footer>
              <a href="<?php echo e(route('operational.patients.index', $patientCatalogRouteParameters)); ?>">Cancelar</a>
              <button type="submit"><?php echo e($editingPatient ? 'Guardar cambios' : 'Guardar paciente'); ?></button>
            </footer>
          </form>
        </div>
      <?php endif; ?>
    </section>
  </div>

  <?php
    $incomingOncologyRequest = $isOncology
      ? $pendingProviderRequests->firstWhere('id', (int) request('assign_request'))
      : null;
  ?>
  <?php if($isOncology && (request()->boolean('new_infusion') || $incomingOncologyRequest)): ?>
    <?php
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
    ?>
    <?php echo $__env->make('doctor.partials.oncology-mixture-request-modal', [
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
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
  <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Modulo operativo'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/operational/dashboard.blade.php ENDPATH**/ ?>