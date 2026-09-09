<?php
  $mirrorAppointments = $appointments ?? collect();
  $mirrorProviderRequests = $providerRequests ?? collect();
  $unitServiceContractsByUnit = $unitServiceContractsByUnit ?? collect();

  $mirrorServiceIconFor = function ($contract) {
    return match ($contract->service?->external_id) {
      'consulta-externa' => 'consultation',
      'hemodinamia' => 'heart',
      'laboratorio' => 'microscope',
      'nutricion-parenteral' => 'nutrition',
      'quimioterapias' => 'infusion',
      'central-de-mezclas' => 'mixtures',
      'mantenimiento-equipo-medico' => 'maintenance',
      'osteosintesis' => 'osteosynthesis',
      default => 'service',
    };
  };

  $mirrorConsultationRequestType = function ($appointment) {
    $requestText = str(data_get($appointment->metadata, 'request_type') ?? $appointment->reason ?? '')->lower()->toString();

    return match (true) {
      str_contains($requestText, 'seguimiento') => 'Seguimiento',
      str_contains($requestText, 'control') => 'Control cronico',
      str_contains($requestText, 'estudio') => 'Estudios complementarios',
      default => 'Primera vez',
    };
  };

  $mirrorConsultationPriority = function ($appointment) {
    $priority = str(data_get($appointment->metadata, 'priority') ?? '')->lower()->toString();

    return match ($priority) {
      'high', 'urgent', 'alta' => ['Alta', 'high'],
      'low', 'baja' => ['Baja', 'low'],
      default => ['Media', 'medium'],
    };
  };

  $mirrorConsultationStatus = function (?string $status) {
    return match ($status) {
      'in_progress' => ['En atencion', 'attention'],
      'confirmed' => ['En validacion', 'validation'],
      'pending', 'requested' => ['Pendiente', 'pending'],
      'completed' => ['Completada', 'completed'],
      'cancelled', 'no_show' => ['Cancelada', 'cancelled'],
      default => ['Programada', 'scheduled'],
    };
  };

  $mirrorPayloadValue = function ($value, string $fallback = '-') {
    if (is_array($value)) {
      $value = data_get($value, 'number')
        ?? data_get($value, 'folio')
        ?? data_get($value, 'id')
        ?? data_get($value, 'code');
    }

    return filled($value) ? $value : $fallback;
  };

  $mirrorMixtureType = function ($providerRequest) {
    return in_array($providerRequest->request_type, ['chemo', 'chemotherapy'], true)
      ? ['Oncologica', 'oncology']
      : ['Nutricional', 'nutrition'];
  };

  $mirrorMixtureStatus = function ($providerRequest) use ($statusText) {
    return match ($providerRequest->status) {
      'accepted' => ['Inspeccionada', 'inspected'],
      'requested', 'draft', 'pending' => ['Pendiente', 'pending'],
      'preparing' => ['En preparacion', 'preparing'],
      'in_route' => ['En ruta', 'route'],
      'delivered' => ['Entregada', 'delivered'],
      'cancelled', 'rejected' => ['Cancelada', 'cancelled'],
      default => [$statusText($providerRequest->status), 'pending'],
    };
  };
?>

<?php $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mirrorUnit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
  <?php
    $mirrorContracts = $unitServiceContractsByUnit->get($mirrorUnit->id, collect());
    $mirrorUnitAppointments = $mirrorAppointments->where('medical_unit_id', $mirrorUnit->id)->values();
    $mirrorUnitProviderRequests = $mirrorProviderRequests->where('medical_unit_id', $mirrorUnit->id)->values();
    $mirrorUnitLocation = collect([
      $mirrorUnit->city ?? $mirrorUnit->municipality,
      $mirrorUnit->state ?? $mirrorUnit->entity,
    ])->filter()->implode(', ');
  ?>
  <dialog class="institution-unit-services-dialog" data-unit-services-dialog="<?php echo e($mirrorUnit->id); ?>">
    <header>
      <div>
        <strong>Servicios activos</strong>
        <h2><?php echo e($mirrorUnit->name); ?></h2>
        <p><?php echo e($mirrorUnit->clues ?? $mirrorUnit->code ?? 'Sin CLUES'); ?> - <?php echo e($mirrorUnitLocation ?: 'Sin ubicacion'); ?></p>
      </div>
      <form method="dialog" class="institution-unit-services-close-form">
        <button type="submit" data-unit-services-close aria-label="Cerrar">&times;</button>
      </form>
    </header>

    <div class="institution-unit-services-dialog-body">
      <?php if($mirrorContracts->isNotEmpty()): ?>
        <div class="unit-service-dashboard institution-unit-service-mirror" data-institution-unit-service-dashboard="<?php echo e($mirrorUnit->id); ?>">
          <section class="unit-service-carousel-card">
            <button type="button" class="unit-service-carousel-arrow" data-unit-service-carousel-prev aria-label="Servicio anterior">&lsaquo;</button>
            <div class="unit-service-carousel" data-unit-service-carousel aria-label="Servicios habilitados">
              <?php $__currentLoopData = $mirrorContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mirrorContract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $mirrorServiceIcon = $mirrorServiceIconFor($mirrorContract);
                  $mirrorServiceName = $mirrorContract->service?->name ?? 'Servicio sin nombre';
                  $mirrorServiceKey = $mirrorUnit->id.'-'.$mirrorContract->id;
                ?>
                <button type="button"
                        class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-service-carousel-button', 'is-active' => $loop->first]); ?>"
                        data-unit-service-tab="<?php echo e($mirrorServiceKey); ?>">
                  <span class="unit-service-carousel-icon is-<?php echo e($mirrorServiceIcon); ?>" aria-hidden="true">
                    <?php switch($mirrorServiceIcon):
                      case ('consultation'): ?>
                        <svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg>
                        <?php break; ?>
                      <?php case ('heart'): ?>
                        <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/><path d="M3 12h4l2-4 4 8 2-4h6"/></svg>
                        <?php break; ?>
                      <?php case ('microscope'): ?>
                        <svg viewBox="0 0 24 24"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 0 0 7-7h-4a3 3 0 0 1-3 3"/><path d="m9 14 6-6"/><path d="m7 12 4 4"/><path d="M10 5 7 8l6 6 3-3Z"/></svg>
                        <?php break; ?>
                      <?php case ('nutrition'): ?>
                        <svg viewBox="0 0 24 24"><path d="M8 2h8"/><path d="M9 2v6l-4 9a4 4 0 0 0 3.7 5h6.6A4 4 0 0 0 19 17l-4-9V2"/><path d="M8 14h8"/></svg>
                        <?php break; ?>
                      <?php case ('infusion'): ?>
                        <svg viewBox="0 0 24 24"><path d="M9 2h6v9a3 3 0 0 1-6 0V2Z"/><path d="M12 14v8"/><path d="M8 22h8"/><path d="M9 6h6"/></svg>
                        <?php break; ?>
                      <?php case ('mixtures'): ?>
                        <svg viewBox="0 0 24 24"><path d="M9 3h6"/><path d="M10 3v6l-4.5 8.5A3 3 0 0 0 8.2 22h7.6a3 3 0 0 0 2.7-4.5L14 9V3"/><path d="M8 16h8"/></svg>
                        <?php break; ?>
                      <?php case ('maintenance'): ?>
                        <svg viewBox="0 0 24 24"><path d="m14.7 6.3 3-3a4 4 0 0 1-5 5l-7 7a2 2 0 0 0 2.8 2.8l7-7a4 4 0 0 1 5-5l-3 3"/></svg>
                        <?php break; ?>
                      <?php case ('osteosynthesis'): ?>
                        <svg viewBox="0 0 24 24"><path d="M8.5 8.5 15.5 15.5"/><path d="M6.5 11.5a3 3 0 1 1 3-5l8 8a3 3 0 1 1-5 3Z"/></svg>
                        <?php break; ?>
                      <?php default: ?>
                        <svg viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    <?php endswitch; ?>
                  </span>
                  <strong><?php echo e($mirrorServiceName); ?></strong>
                </button>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <button type="button" class="unit-service-carousel-arrow" data-unit-service-carousel-next aria-label="Servicio siguiente">&rsaquo;</button>
          </section>

          <div class="unit-service-panels">
            <?php $__currentLoopData = $mirrorContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mirrorContract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <?php
                $mirrorService = $mirrorContract->service;
                $mirrorServiceName = $mirrorService?->name ?? 'Servicio sin nombre';
                $mirrorServiceKey = $mirrorUnit->id.'-'.$mirrorContract->id;
                $mirrorIsConsultationService = $mirrorService?->external_id === 'consulta-externa';
                $mirrorIsNutritionService = $mirrorService?->external_id === 'nutricion-parenteral';
                $mirrorIsChemotherapyService = $mirrorService?->external_id === 'quimioterapias';
                $mirrorOperationSearch = str(($mirrorService?->name ?? '').' '.($mirrorService?->category ?? '').' '.($mirrorService?->specialty ?? ''))->lower()->toString();
                $mirrorOperationArea = str_contains($mirrorOperationSearch, 'oncolo') || str_contains($mirrorOperationSearch, 'quimio')
                  ? 'oncology'
                  : (str_contains($mirrorOperationSearch, 'farmac') ? 'inpatient-pharmacy' : 'nursing');
                $mirrorOperationHref = $mirrorIsConsultationService
                  ? route('outpatient.dashboard', ['unit' => $mirrorUnit->id])
                  : route('operational.dashboard', ['area' => $mirrorOperationArea, 'section' => 'history', 'unit' => $mirrorUnit->id, 'service' => $mirrorContract->service_id]);
              ?>
              <section class="unit-service-panel" data-unit-service-panel="<?php echo e($mirrorServiceKey); ?>" <?php if(! $loop->first): ?> hidden <?php endif; ?>>
                <header class="unit-service-panel-header">
                  <div>
                    <h2><?php echo e($mirrorServiceName); ?></h2>
                    <p><?php echo e($mirrorService?->category ?? 'Sin categoria'); ?> &middot; <?php echo e($mirrorService?->specialty ?? 'Servicio general'); ?></p>
                    <div class="unit-service-panel-badges">
                      <span class="unit-native-status"><?php echo e($statusText($mirrorContract->status)); ?></span>
                      <span>1 hospital habilitado</span>
                    </div>
                  </div>
                  <div class="unit-service-panel-actions">
                    <a href="<?php echo e($mirrorOperationHref); ?>">Ver operacion</a>
                    <a href="<?php echo e(route('unit.services.report', ['contract' => $mirrorContract, 'unit' => $mirrorUnit->id])); ?>">Descargar reporte</a>
                  </div>
                </header>

                <div class="unit-native-table-scroll">
                  <?php if($mirrorIsConsultationService): ?>
                    <table class="unit-native-table unit-consultation-table">
                      <thead>
                        <tr>
                          <th>Folio</th>
                          <th>Fecha y hora</th>
                          <th>Paciente</th>
                          <th>Hospital</th>
                          <th>Tipo de solicitud</th>
                          <th>Prioridad</th>
                          <th>Responsable</th>
                          <th>Estatus</th>
                          <th>Ultima actualizacion</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $mirrorUnitAppointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                          <?php
                            $folioYear = $appointment->created_at?->format('Y') ?? now()->format('Y');
                            $folio = data_get($appointment->metadata, 'folio') ?? 'CE-'.$folioYear.'-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT);
                            [$priorityLabel, $priorityClass] = $mirrorConsultationPriority($appointment);
                            [$consultationStatusLabel, $consultationStatusClass] = $mirrorConsultationStatus($appointment->status);
                            $patientMeta = $appointment->patient?->curp ?? 'Sin CURP';
                            $appointmentSearch = $appointment->patient?->full_name ?? $folio;
                            $appointmentRoute = route('outpatient.dashboard', ['unit' => $mirrorUnit->id, 'search' => $appointmentSearch]);
                          ?>
                          <tr>
                            <td><strong class="unit-consultation-folio"><?php echo e($folio); ?></strong></td>
                            <td><?php echo e($appointment->starts_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td>
                            <td><strong><?php echo e($appointment->patient?->full_name ?? 'Paciente pendiente'); ?></strong><small>CURP: <?php echo e($patientMeta); ?></small></td>
                            <td><strong><?php echo e($appointment->medicalUnit?->name ?? $mirrorUnit->name); ?></strong></td>
                            <td><?php echo e($mirrorConsultationRequestType($appointment)); ?></td>
                            <td><span class="unit-consultation-chip is-priority-<?php echo e($priorityClass); ?>"><?php echo e($priorityLabel); ?></span></td>
                            <td><?php echo e($appointment->doctor?->full_name ?? 'Sin responsable'); ?></td>
                            <td><span class="unit-consultation-chip is-status-<?php echo e($consultationStatusClass); ?>"><?php echo e($consultationStatusLabel); ?></span></td>
                            <td><?php echo e($appointment->updated_at?->format('d/m/Y H:i') ?? 'Sin actualizacion'); ?></td>
                            <td>
                              <div class="unit-consultation-actions">
                                <a href="<?php echo e($appointmentRoute); ?>">
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                  Ver
                                </a>
                                <a href="<?php echo e($appointmentRoute); ?>">
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 0 1-15.5 6.2"/><path d="M3 12A9 9 0 0 1 18.5 5.8"/><path d="M18 2v4h-4"/><path d="M6 22v-4h4"/></svg>
                                  Actualizar
                                </a>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                          <tr><td colspan="10" class="unit-native-empty">Sin consultas externas registradas.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  <?php elseif($mirrorIsNutritionService || $mirrorIsChemotherapyService): ?>
                    <?php
                      $mixtureRequests = $mirrorIsChemotherapyService
                        ? $mirrorUnitProviderRequests->whereIn('request_type', ['chemo', 'chemotherapy'])->values()
                        : $mirrorUnitProviderRequests->whereIn('request_type', ['npt', 'nutrition'])->values();
                      $mixtureRouteArea = $mirrorIsChemotherapyService ? 'oncology' : 'nursing';
                      $mixtureEmptyMessage = $mirrorIsChemotherapyService
                        ? 'Sin solicitudes de quimioterapia registradas.'
                        : 'Sin solicitudes de nutricion parenteral registradas.';
                    ?>
                    <table class="unit-native-table unit-nutrition-table">
                      <thead>
                        <tr>
                          <th>Tipo</th>
                          <th>ID mezcla</th>
                          <th>No. solicitud</th>
                          <th>Hospital</th>
                          <th>Paciente</th>
                          <th>Fecha y hora de solicitud</th>
                          <th>Fecha y hora programada de entrega</th>
                          <th>Estado operativo</th>
                          <th>Remision</th>
                          <th>Lote</th>
                          <th>Ver</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $mixtureRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mixtureRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                          <?php
                            [$mixtureTypeLabel, $mixtureTypeClass] = $mirrorMixtureType($mixtureRequest);
                            [$mixtureStatusLabel, $mixtureStatusClass] = $mirrorMixtureStatus($mixtureRequest);
                            $mixId = $mirrorPayloadValue(data_get($mixtureRequest->payload, 'mix_id')
                              ?? data_get($mixtureRequest->payload, 'mixture_id')
                              ?? data_get($mixtureRequest->payload, 'clinical_format.mix_id')
                              ?? $mixtureRequest->id);
                            $requestPrefix = $mirrorIsChemotherapyService ? 'QT-' : 'NPT-';
                            $requestNumber = $mirrorPayloadValue(data_get($mixtureRequest->payload, 'request_number')
                              ?? data_get($mixtureRequest->payload, 'request_no')
                              ?? $mixtureRequest->external_id
                              ?? $requestPrefix.str_pad((string) $mixtureRequest->id, 4, '0', STR_PAD_LEFT));
                            $remission = $mirrorPayloadValue(data_get($mixtureRequest->payload, 'remission')
                              ?? data_get($mixtureRequest->payload, 'delivery_remission')
                              ?? data_get($mixtureRequest->payload, 'remission_number'));
                            $lot = $mirrorPayloadValue(data_get($mixtureRequest->payload, 'lot')
                              ?? data_get($mixtureRequest->payload, 'batch')
                              ?? data_get($mixtureRequest->payload, 'remission.lot')
                              ?? data_get($mixtureRequest->payload, 'remission.batch'));
                            $mixtureRoute = route('operational.dashboard', ['area' => $mixtureRouteArea, 'section' => 'support', 'unit' => $mirrorUnit->id, 'request' => $mixtureRequest->id]);
                          ?>
                          <tr>
                            <td><span class="unit-consultation-chip is-type-<?php echo e($mixtureTypeClass); ?>"><?php echo e($mixtureTypeLabel); ?></span></td>
                            <td><?php echo e($mixId); ?></td>
                            <td><?php echo e($requestNumber); ?></td>
                            <td><?php echo e($mixtureRequest->medicalUnit?->name ?? $mirrorUnit->name); ?></td>
                            <td><?php echo e($mixtureRequest->patient?->full_name ?? 'Sin paciente'); ?></td>
                            <td><?php echo e($mixtureRequest->requested_at?->format('Y-m-d H:i') ?? '-'); ?></td>
                            <td><?php echo e($mixtureRequest->required_at?->format('Y-m-d H:i') ?? '-'); ?></td>
                            <td><span class="unit-consultation-chip is-status-<?php echo e($mixtureStatusClass); ?>"><?php echo e($mixtureStatusLabel); ?></span></td>
                            <td><?php echo e($remission); ?></td>
                            <td><?php echo e($lot); ?></td>
                            <td><a class="unit-nutrition-view-link" href="<?php echo e($mixtureRoute); ?>">Ver</a></td>
                          </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                          <tr><td colspan="11" class="unit-native-empty"><?php echo e($mixtureEmptyMessage); ?></td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  <?php else: ?>
                    <table class="unit-native-table unit-service-detail-table">
                      <thead>
                        <tr>
                          <th>Hospital</th>
                          <th>CLUES</th>
                          <th>Ubicacion</th>
                          <th>Vigencia</th>
                          <th>Contrato</th>
                          <th>Estatus</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td><strong><?php echo e($mirrorUnit->name); ?></strong><small><?php echo e($mirrorUnit->type ?? $mirrorUnit->typology ?? 'Hospital General'); ?></small></td>
                          <td><?php echo e($mirrorUnit->clues ?? $mirrorUnit->code ?? 'Sin CLUES'); ?></td>
                          <td><?php echo e($mirrorUnitLocation ?: 'Sin ubicacion'); ?></td>
                          <td><?php echo e($mirrorContract->starts_at?->format('d/m/Y') ?? 'Sin inicio'); ?> - <?php echo e($mirrorContract->ends_at?->format('d/m/Y') ?? 'Sin vencimiento'); ?></td>
                          <td><?php echo e($mirrorContract->contract_number ?? 'Sin contrato'); ?></td>
                          <td><span class="unit-native-status"><?php echo e($statusText($mirrorContract->status)); ?></span></td>
                        </tr>
                      </tbody>
                    </table>
                  <?php endif; ?>
                </div>
              </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
        </div>
      <?php else: ?>
        <section class="institution-unit-services-empty">
          <strong>Sin servicios activos</strong>
          <span>La unidad no tiene servicios habilitados para visualizar.</span>
        </section>
      <?php endif; ?>
    </div>
  </dialog>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH C:\laragon\www\dr-sam\resources\views\institution\_unit_services_mirror.blade.php ENDPATH**/ ?>