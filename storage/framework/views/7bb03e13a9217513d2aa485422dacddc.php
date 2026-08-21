

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
?>

<?php $__env->startSection('content'); ?>
  <div class="operational-native-screen">
    <aside class="operational-native-sidebar" aria-label="Navegacion operativa">
      <div class="operational-native-brand">
        <span class="operational-native-mark" aria-hidden="true">+</span>
        <div>
          <strong>Area Operativa</strong>
          <span><?php echo e($institutionName); ?></span>
        </div>
      </div>

      <nav class="operational-native-menu operational-legacy-menu">
        <?php $__currentLoopData = $moduleGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php $isActiveGroup = $groupKey === $areaKey; ?>
          <section class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $isActiveGroup, 'is-oncology' => $groupKey === 'oncology']); ?>">
            <h2 class="operational-legacy-group-title">
              <span class="operational-legacy-group-icon" aria-hidden="true"><?php echo e($groupKey === 'nursing' ? '⊕' : ($groupKey === 'oncology' ? '⊕' : '☩')); ?></span>
              <?php echo e($group); ?>

            </h2>
            <?php if($groupKey === 'oncology'): ?>
              <div class="operational-legacy-subgroup is-administrative">
                <small class="operational-menu-caption">AREA ADMINISTRATIVA</small>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'services-pending']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'services-pending'])); ?>">Servicios pendientes</a>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'services-history']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'services-history'])); ?>">Historial de servicios</a>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'service-create']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'service-create'])); ?>">Nuevo servicio</a>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'service-format']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'service-format'])); ?>">Formato de solicitud</a>
              </div>
              <div class="operational-legacy-subgroup is-operational">
                <small class="operational-menu-caption">AREA OPERATIVA</small>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'mixes']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'mixes'])); ?>">Mezclas programadas</a>
                <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'mix-history']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'mix-history'])); ?>">Historial de mezclas</a>
              </div>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['operational-legacy-calendar', 'is-selected' => $isActiveGroup && $section === 'calendar']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'calendar'])); ?>">Calendario de servicios</a>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'patients']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'patients'])); ?>">Pacientes</a>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'patient-create']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'patient-create'])); ?>">Alta de pacientes</a>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'infusion-rooms']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'infusion-rooms'])); ?>">Salas de infusion</a>
            <?php else: ?>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'pending']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'pending'])); ?>">Solicitudes pendientes</a>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'history']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'history'])); ?>">Historial de solicitudes</a>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'patients']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'patients'])); ?>">Pacientes</a>
              <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-selected' => $isActiveGroup && $section === 'patient-create']); ?>" href="<?php echo e(route('operational.dashboard', ['area' => $groupKey, 'section' => 'patient-create'])); ?>">Alta de paciente</a>
            <?php endif; ?>
          </section>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

      <header class="operational-native-header">
        <div>
          <p class="eyebrow"><?php echo e(strtoupper($areaLabel)); ?></p>
          <h1>Modulo Area Operativa - <?php echo e($areaLabel); ?></h1>
          <p><?php echo e($areaLabel); ?> - <?php echo e($areaLabel); ?> - <?php echo e($institutionName); ?></p>
        </div>

        <form class="operational-native-controls">
          <label>
            Operar como
            <select>
              <option><?php echo e($areaLabel); ?> - <?php echo e($institutionName); ?> - <?php echo e($areaRoleLabel); ?> - <?php echo e($areaLabel); ?></option>
            </select>
          </label>
          <label>
            Rol
            <select>
              <option><?php echo e($areaLabel); ?></option>
            </select>
          </label>
          <label class="span-2">
            Unidad
            <select>
              <option><?php echo e($unitName); ?> - <?php echo e($unitCode); ?></option>
            </select>
          </label>
          <time><?php echo e(strtolower(now()->format('d M Y'))); ?></time>
        </form>
      </header>

      <?php if(in_array($section, ['pending', 'history'], true)): ?>
        <section class="operational-native-metrics" aria-label="Resumen operativo">
          <?php $__currentLoopData = $metricCards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <article class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $label === $areaMetricLabel]); ?>">
              <span><?php echo e($label); ?></span>
              <strong><?php echo e(number_format($value)); ?></strong>
              <small><?php echo e($loop->last ? 'Solicitudes visibles' : 'Pendientes de validacion'); ?></small>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </section>

        <section class="operational-native-table-card">
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Historial de solicitudes</p>
              <h2>Solicitudes enviadas por hospitales</h2>
              <span>Area operativa de <?php echo e($institutionName); ?> - <?php echo e($section === 'history' ? 'Historial de solicitudes enviadas por hospitales' : 'Solicitudes pendientes de '.$areaLabel); ?></span>
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
                          <button type="button" class="operational-disabled-action" disabled title="Requiere autorización de Enfermería y Farmacia intrahospitalaria">Pendiente</button>
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
      <?php elseif(in_array($section, ['services-pending', 'services-history', 'service-create', 'service-format', 'mixes', 'mix-history', 'calendar', 'infusion-rooms'], true)): ?>
        <?php echo $__env->make('operational.sections.oncology', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php else: ?>
        <?php ($editingPatient = $patientRows->firstWhere('id', (int) request('edit_patient'))); ?>
        <section class="operational-native-table-card operational-patients-card">
          <div class="operational-native-table-heading">
            <div>
              <p class="eyebrow">Consulta externa</p>
              <h2>Catalogo de pacientes</h2>
              <span><?php echo e($institutionName); ?> - Pacientes registrados</span>
            </div>
            <div class="operational-heading-actions">
              <strong><?php echo e($patientRows->count()); ?> pacientes</strong>
              <a class="operational-primary-button" href="<?php echo e(route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients', 'modal' => 'patient'])); ?>"><span aria-hidden="true">+</span> Nuevo paciente</a>
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
                    <td><a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients', 'edit_patient' => $patient->id])); ?>">Editar</a></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr>
                    <td colspan="9">Sin pacientes registrados para esta area operativa.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>

        <div id="alta-paciente" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['operational-modal', 'is-open' => $section === 'patient-create' || request('modal') === 'patient' || (bool) $editingPatient]); ?>" role="dialog" aria-modal="true" aria-labelledby="alta-paciente-title">
          <a class="operational-modal-backdrop" href="<?php echo e(route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients'])); ?>" aria-label="Cerrar"></a>
          <form class="operational-modal-card" method="post" action="<?php echo e($editingPatient ? route('operational.patients.update', $editingPatient) : route('operational.patients.store')); ?>">
            <?php echo csrf_field(); ?>
            <?php if($editingPatient): ?> <?php echo method_field('patch'); ?> <?php endif; ?>
            <input type="hidden" name="area" value="<?php echo e($areaKey); ?>">
            <header>
              <div>
                <h2 id="alta-paciente-title"><?php echo e($editingPatient ? 'Editar paciente' : 'Alta de paciente'); ?></h2>
                <p><?php echo e($institutionName); ?> - Catalogo de pacientes</p>
              </div>
              <a href="<?php echo e(route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients'])); ?>">Cerrar</a>
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
              <a href="<?php echo e(route('operational.dashboard', ['area' => $areaKey, 'section' => 'patients'])); ?>">Cancelar</a>
              <button type="submit"><?php echo e($editingPatient ? 'Guardar cambios' : 'Guardar paciente'); ?></button>
            </footer>
          </form>
        </div>
      <?php endif; ?>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Area operativa'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views\operational\dashboard.blade.php ENDPATH**/ ?>