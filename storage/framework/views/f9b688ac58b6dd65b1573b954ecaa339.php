<?php
  $serviceRows = $section === 'services-history' ? $historicalProviderRequests : $pendingProviderRequests;
  $mixRows = $section === 'mix-history'
    ? $providerRequests->whereIn('status', ['delivered', 'cancelled', 'rejected'])
    : $providerRequests->whereNotIn('status', ['delivered', 'cancelled', 'rejected']);
  $workflowRows = $section === 'services-scheduled'
    ? ($scheduledProviderRequests ?? collect())
    : ($preparationProviderRequests ?? collect());
?>

<?php if(in_array($section, ['services-preparation', 'services-scheduled'], true)): ?>
  <?php $isScheduledList = $section === 'services-scheduled'; ?>
  <section class="operational-native-table-card operational-section-card" data-oncology-workflow-list>
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow"><?php echo e($isScheduledList ? 'PROGRAMADAS' : 'EN PREPARACION'); ?></p>
        <h2>
          <?php if($isScheduledList): ?>
            Infusiones programadas
          <?php else: ?>
            Solicitudes en preparaci&oacute;n
          <?php endif; ?>
        </h2>
        <span>
          <?php if($isScheduledList): ?>
            Solicitudes con sala y horario confirmados
          <?php else: ?>
            Solicitudes guardadas pendientes de asignaci&oacute;n de sala
          <?php endif; ?>
        </span>
      </div>
      <div class="operational-heading-actions">
        <strong><?php echo e($workflowRows->count()); ?> <?php echo e($workflowRows->count() === 1 ? 'solicitud' : 'solicitudes'); ?></strong>
        <button class="operational-secondary-button" type="button" data-oncology-workflow-sort aria-label="Cambiar orden por fecha">Ordenar por fecha</button>
      </div>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table" data-oncology-workflow-table>
        <thead>
          <tr>
            <th>Folio</th><th>Paciente</th><th>Servicio</th><th>M&eacute;dico</th>
            <?php if($isScheduledList): ?><th>Sala</th><th>Sill&oacute;n o cama</th><th>Fecha</th><th>Hora</th><?php else: ?><th>Fecha de solicitud</th><?php endif; ?>
            <th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $workflowRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php $assignment = data_get($item->payload, 'infusion_assignment', []); ?>
            <tr data-oncology-workflow-row data-sort-date="<?php echo e($isScheduledList ? data_get($assignment, 'application_date').' '.data_get($assignment, 'starts_at') : $item->requested_at?->toDateTimeString()); ?>">
              <td><strong><?php echo e($item->external_id ?? 'ONC-'.$item->id); ?></strong></td>
              <td><?php echo e($item->patient?->full_name ?? 'Sin paciente'); ?></td>
              <td><?php echo e(data_get($item->payload, 'service', 'Quimioterapia')); ?></td>
              <td><?php echo e(data_get($item->payload, 'doctor', 'Sin medico')); ?></td>
              <?php if($isScheduledList): ?>
                <td><?php echo e(data_get($assignment, 'room_number', 'Sin sala')); ?></td>
                <td><?php echo e(data_get($assignment, 'seat_label', 'Sin asignar')); ?></td>
                <td><?php echo e(filled(data_get($assignment, 'application_date')) ? \Illuminate\Support\Carbon::parse(data_get($assignment, 'application_date'))->format('d/m/Y') : 'Sin fecha'); ?></td>
                <td><?php echo e(data_get($assignment, 'starts_at', 'Sin hora')); ?></td>
              <?php else: ?>
                <td><?php echo e($item->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td>
              <?php endif; ?>
              <td>
                <span class="operational-chip">
                  <?php if($isScheduledList): ?>
                    Agendada
                  <?php else: ?>
                    En preparaci&oacute;n
                  <?php endif; ?>
                </span>
              </td>
              <td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id])); ?>">Ver solicitud</a></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="<?php echo e($isScheduledList ? 10 : 7); ?>">
                <?php if($isScheduledList): ?>
                  No hay infusiones programadas.
                <?php else: ?>
                  No hay solicitudes en preparaci&oacute;n.
                <?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php elseif(in_array($section, ['services-pending', 'services-history'], true)): ?>
  <?php $isPendingServices = $section === 'services-pending'; ?>
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow">AREA ADMINISTRATIVA</p>
        <h2><?php echo e($isPendingServices ? 'Solicitudes pendientes' : 'Historial de servicios'); ?></h2>
        <span>Solicitudes oncol&oacute;gicas enviadas por los m&eacute;dicos a <?php echo e($unitName); ?></span>
      </div>
      <a class="operational-primary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'new_infusion' => 1])); ?>">+ Nueva infusión</a>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table">
        <thead>
          <tr>
            <th>Folio</th><th>Paciente</th><th>Servicio</th><th>M&eacute;dico</th><th>Fecha requerida</th><th>Volumen</th><th>Estatus</th>
            <?php if($isPendingServices): ?><th>Ver solicitud</th><th>Asignar sala</th><?php else: ?><th>Acciones</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $serviceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td><strong><?php echo e($item->external_id ?? 'QT-'.$item->id); ?></strong></td>
              <td><?php echo e($item->patient?->full_name ?? 'Sin paciente'); ?></td>
              <td><?php echo e(data_get($item->payload, 'service', 'Oncologia medica')); ?></td>
              <td><?php echo e(data_get($item->payload, 'doctor', 'Sin medico')); ?></td>
              <td><?php echo e($item->required_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td>
              <td><?php echo e(data_get($item->payload, 'volume', 'N/A')); ?></td>
              <td><span class="operational-chip"><?php echo e($statusText($item->status)); ?></span></td>
              <td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id])); ?>">Ver</a></td>
              <?php if($isPendingServices): ?>
                <td>
                  <a class="operational-primary-button" href="<?php echo e(route('operational.dashboard', [
                    'area' => 'oncology',
                    'section' => 'services-pending',
                    'oncology_track' => 'infusions',
                    'unit' => $contextUnit?->id,
                    'assign_request' => $item->id,
                  ])); ?>">Asignar</a>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="<?php echo e($isPendingServices ? 9 : 8); ?>">Sin servicios para esta vista.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php elseif(in_array($section, ['service-create', 'service-format', 'support'], true)): ?>
  <?php $selectedRequest = $providerRequests->firstWhere('id', (int) request('request')); ?>
  <section class="operational-native-table-card operational-form-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">SOLICITUD DE ONCOLÃ“GICOS</p><h2><?php echo e($selectedRequest ? 'Detalle de solicitud' : 'Nuevo servicio'); ?></h2><span>Formato operativo para mezclas oncolÃ³gicas</span></div>
      <a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending', 'oncology_track' => 'infusions'])); ?>">AtrÃ¡s</a>
    </div>
    <form class="operational-detail-form" method="post" action="<?php echo e(route('operational.service-requests.store')); ?>">
      <?php echo csrf_field(); ?>
      <?php if(! $selectedRequest && ($outpatientPrescriptions ?? collect())->isNotEmpty()): ?>
        <label class="span-2">Receta de Consulta Externa
          <select name="prescription_id">
            <option value="">Sin receta vinculada</option>
            <?php $__currentLoopData = $outpatientPrescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prescription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($prescription->id); ?>" <?php if(old('prescription_id') == $prescription->id): echo 'selected'; endif; ?>>
                <?php echo e($prescription->code ?? 'RX-'.$prescription->id); ?> - <?php echo e($prescription->patient?->full_name ?? 'Sin paciente'); ?> - <?php echo e($prescription->doctor?->full_name ?? 'Sin medico'); ?> - <?php echo e($prescription->issued_at?->format('d/m/Y') ?? 'Sin fecha'); ?>

              </option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
          <small>Usa una receta emitida en Consulta Externa como origen clinico de la solicitud.</small>
        </label>
      <?php endif; ?>
      <?php if($selectedRequest && data_get($selectedRequest->payload, 'prescription_code')): ?>
        <div class="span-2 operational-chip">Receta Consulta Externa: <?php echo e(data_get($selectedRequest->payload, 'prescription_code')); ?></div>
      <?php endif; ?>
      <label>Paciente *<select name="patient_id" required><option value="">Selecciona paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>" <?php if($selectedRequest?->patient_id === $patient->id): echo 'selected'; endif; ?>><?php echo e($patient->full_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
      <label>Servicio *<input name="service" required value="<?php echo e(data_get($selectedRequest?->payload, 'service', 'OncologÃ­a mÃ©dica')); ?>"></label>
      <label>MÃ©dico *<input name="doctor" required value="<?php echo e(data_get($selectedRequest?->payload, 'doctor')); ?>"></label>
      <label>Volumen total ml<input name="volume" type="number" min="0" value="<?php echo e(data_get($selectedRequest?->payload, 'volume')); ?>"></label>
      <label>Fecha de entrega<input name="required_at" type="datetime-local" value="<?php echo e($selectedRequest?->required_at?->format('Y-m-d\TH:i')); ?>"></label>
      <label class="span-2">DiagnÃ³stico<textarea name="diagnosis"><?php echo e(data_get($selectedRequest?->payload, 'diagnosis')); ?></textarea></label>
      <fieldset class="span-2"><legend>Medicamentos y administraciÃ³n</legend><div class="operational-medication-grid"><?php $__currentLoopData = range(1, 4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $number): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span><?php echo e($number); ?></span><input placeholder="Medicamento"><input placeholder="Dosis"><select><option>IV</option><option>IM</option><option>SC</option></select><input type="date"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div></fieldset>
      <label class="span-2">Observaciones<textarea name="notes"><?php echo e(data_get($selectedRequest?->payload, 'notes')); ?></textarea></label>
      <?php if (! ($selectedRequest)): ?><button class="operational-primary-button span-2" type="submit">Guardar formato</button><?php endif; ?>
    </form>
  </section>
<?php elseif($section === 'support-ai'): ?>
  <section class="operational-native-table-card operational-section-card" data-oncology-support-ai>
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow">SOPORTE</p>
        <h2>Asistencia con IA</h2>
        <span>Solicitudes oncol&oacute;gicas disponibles para revisi&oacute;n y apoyo cl&iacute;nico.</span>
      </div>
      <strong><?php echo e($providerRequests->count()); ?> solicitudes</strong>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table">
        <thead><tr><th>Folio</th><th>Paciente</th><th>Servicio</th><th>M&eacute;dico</th><th>Estado</th><th>Acci&oacute;n</th></tr></thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $providerRequests->take(12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr>
              <td><strong><?php echo e($item->external_id ?? 'SOL-'.$item->id); ?></strong></td>
              <td><?php echo e($item->patient?->full_name ?? 'Paciente sin nombre'); ?></td>
              <td><?php echo e(data_get($item->payload, 'service', 'Oncologia medica')); ?></td>
              <td><?php echo e(data_get($item->payload, 'doctor', 'Sin medico')); ?></td>
              <td><span class="operational-chip"><?php echo e($statusText($item->status)); ?></span></td>
              <td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id])); ?>">Abrir solicitud</a></td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="6">No hay solicitudes disponibles para asistencia.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php elseif($section === 'support-analytics'): ?>
  <?php
    $supportStatusRows = collect([
      ['label' => 'Pendientes', 'count' => $pendingProviderRequests->count()],
      ['label' => 'Programadas', 'count' => $scheduledProviderRequests->count()],
      ['label' => 'En preparacion', 'count' => $preparationProviderRequests->count()],
      ['label' => 'Finalizadas', 'count' => $providerRequests->whereIn('status', ['delivered', 'cancelled', 'rejected'])->count()],
    ]);
  ?>
  <section data-oncology-support-analytics>
    <div class="operational-native-metrics">
      <?php $__currentLoopData = $supportStatusRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $statusRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <article class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $index === 0]); ?>">
          <span><?php echo e($statusRow['label']); ?></span>
          <strong><?php echo e($statusRow['count']); ?></strong>
          <small>Solicitudes oncol&oacute;gicas</small>
        </article>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <section class="operational-native-table-card operational-section-card">
      <div class="operational-native-table-heading">
        <div>
          <p class="eyebrow">SOPORTE</p>
          <h2>Anal&iacute;ticas</h2>
          <span>Resumen del flujo de solicitudes de infusi&oacute;n y mezclas oncol&oacute;gicas.</span>
        </div>
        <strong><?php echo e($providerRequests->count()); ?> solicitudes</strong>
      </div>
      <div class="operational-native-table-scroll">
        <table class="operational-native-table">
          <thead><tr><th>Indicador</th><th>Cantidad</th><th>Participaci&oacute;n</th></tr></thead>
          <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $supportStatusRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusRow): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <?php $statusShare = $providerRequests->count() > 0 ? round(($statusRow['count'] / $providerRequests->count()) * 100, 1) : 0; ?>
              <tr><td><strong><?php echo e($statusRow['label']); ?></strong></td><td><?php echo e($statusRow['count']); ?></td><td><?php echo e(number_format($statusShare, 1)); ?>%</td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="3">No hay informaci&oacute;n disponible.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </section>
<?php elseif(in_array($section, ['mixes', 'mix-history'], true)): ?>
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading"><div><p class="eyebrow">MODULO OPERATIVO</p><h2><?php echo e($section === 'mix-history' ? 'Historial de mezclas' : 'Mezclas programadas'); ?></h2><span>PreparaciÃ³n y seguimiento de tratamientos</span></div><strong><?php echo e($mixRows->count()); ?> mezclas</strong></div>
    <div class="operational-native-table-scroll"><table class="operational-native-table"><thead><tr><th>Folio</th><th>Paciente</th><th>Mezcla</th><th>ProgramaciÃ³n</th><th>MÃ©dico</th><th>Estado</th><th>AcciÃ³n</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $mixRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><strong><?php echo e($item->external_id); ?></strong></td><td><?php echo e($item->patient?->full_name); ?></td><td><?php echo e(data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica')); ?></td><td><?php echo e($item->required_at?->format('d/m/Y H:i') ?? 'Por programar'); ?></td><td><?php echo e(data_get($item->payload, 'doctor', 'Sin mÃ©dico')); ?></td><td><span class="operational-chip"><?php echo e($statusText($item->status)); ?></span></td><td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id])); ?>">Abrir</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="7">Sin mezclas registradas.</td></tr><?php endif; ?></tbody></table></div>
  </section>
<?php elseif($section === 'calendar'): ?>
  <?php echo $__env->make('operational.sections.service-calendar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php elseif($section === 'infusion-rooms'): ?>
  <section class="operational-native-table-card operational-section-card" data-infusion-room-overview>
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow">SALAS DE INFUSI&Oacute;N</p>
        <h2>Inicio de salas de infusi&oacute;n</h2>
        <span>Ocupaci&oacute;n y citas programadas en <?php echo e($unitName); ?></span>
      </div>
      <strong><?php echo e($infusionRooms->count()); ?> <?php echo e($infusionRooms->count() === 1 ? 'sala' : 'salas'); ?></strong>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table operational-room-overview-table">
        <thead>
          <tr>
            <th>N&uacute;mero de sala</th>
            <th>ID</th>
            <th>Ubicaci&oacute;n</th>
            <th>Sillones de infusi&oacute;n</th>
            <th>Citas hoy</th>
            <th>Citas esta semana</th>
            <th>Citas este mes</th>
            <th>Ocupaci&oacute;n mensual</th>
            <th>Calendario</th>
            <th>Informaci&oacute;n general</th>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $infusionRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $roomStats = $infusionRoomStats->get($room->id, ['today' => 0, 'week' => 0, 'month' => 0, 'monthly_occupancy' => 0]);
              $roomCapacity = max(1, (int) $room->simultaneous_capacity);
              $roomOccupancy = (float) data_get($roomStats, 'monthly_occupancy', 0);
            ?>
            <tr>
              <td><strong><?php echo e($room->unit_number ?: 'Sin numero'); ?></strong></td>
              <td><span class="operational-room-id">ID-<?php echo e(str_pad((string) $room->id, 4, '0', STR_PAD_LEFT)); ?></span></td>
              <td>
                <strong><?php echo e($room->location ?: 'Sin ubicacion'); ?></strong>
                <?php if(filled($room->floor)): ?><small>Piso <?php echo e($room->floor); ?></small><?php endif; ?>
              </td>
              <td><?php echo e($roomCapacity); ?> <?php echo e($roomCapacity === 1 ? 'sillon' : 'sillones'); ?></td>
              <td><strong class="operational-room-today-count"><?php echo e(data_get($roomStats, 'today', 0)); ?> de <?php echo e($roomCapacity); ?> sillones</strong></td>
              <td><?php echo e(data_get($roomStats, 'week', 0)); ?> citas</td>
              <td><?php echo e(data_get($roomStats, 'month', 0)); ?> citas</td>
              <td>
                <div class="operational-room-occupancy" aria-label="<?php echo e(number_format($roomOccupancy, 1)); ?> por ciento de ocupacion mensual">
                  <strong><?php echo e(number_format($roomOccupancy, 1)); ?>%</strong>
                  <span aria-hidden="true"><i style="width: <?php echo e($roomOccupancy); ?>%"></i></span>
                </div>
              </td>
              <td>
                <a class="operational-view-button" href="<?php echo e(route('operational.dashboard', [
                  'area' => 'oncology',
                  'section' => 'infusion-room-calendar',
                  'oncology_track' => 'infusions',
                  'unit' => $contextUnit?->id,
                  'room' => $room->id,
                  'calendar_month' => now()->format('Y-m'),
                ])); ?>">Ver calendario</a>
              </td>
              <td>
                <a class="operational-view-button" href="<?php echo e(route('operational.dashboard', [
                  'area' => 'oncology',
                  'section' => 'infusion-room-catalog',
                  'oncology_track' => 'infusions',
                  'unit' => $contextUnit?->id,
                  'room' => $room->id,
                ])); ?>">Ver informaci&oacute;n</a>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="10">No hay salas de infusi&oacute;n registradas para esta unidad.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php elseif($section === 'infusion-room-calendar'): ?>
  <?php
    $calendarMode = 'oncology';
    $calendarTitle = 'Calendario de '.$selectedInfusionRoom->unit_number;
    $calendarSubtitle = 'Citas programadas para '.$selectedInfusionRoom->location;
    $calendarRoomLock = $selectedInfusionRoom->unit_number;
    $calendarReturnUrl = route('operational.dashboard', [
      'area' => 'oncology',
      'section' => 'infusion-rooms',
      'oncology_track' => 'infusions',
      'unit' => $contextUnit?->id,
    ]);
  ?>
  <?php echo $__env->make('operational.sections.service-calendar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php elseif($section === 'infusion-room-catalog'): ?>
  <?php
    $editingRoom = $infusionRooms->firstWhere('id', (int) request('edit_room'));
    $viewingRoom = $selectedInfusionRoom;
    $showRoomForm = request()->boolean('new_room') || (bool) $editingRoom;
    $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 0 => 'Domingo'];
    $dayKeys = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 0 => 'sunday'];
  ?>
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">CATALOGO</p><h2>Cat&aacute;logo de salas de infusi&oacute;n</h2><span>Informaci&oacute;n general y configuraci&oacute;n de las salas en <?php echo e($unitName); ?></span></div>
      <div class="operational-heading-actions"><strong><?php echo e($infusionRooms->count()); ?> salas</strong><a class="operational-primary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'new_room' => 1])); ?>">+ Nueva sala</a></div>
    </div>
    <div class="operational-native-table-scroll"><table class="operational-native-table">
      <thead><tr><th>N&uacute;mero de sala</th><th>ID</th><th>Ubicaci&oacute;n</th><th>Piso</th><th>Sillones</th><th>Responsable</th><th>Horario</th><th>Estatus</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $infusionRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr>
            <td><strong><?php echo e($room->unit_number ?: 'Sin numero'); ?></strong></td>
            <td>ID-<?php echo e(str_pad((string) $room->id, 4, '0', STR_PAD_LEFT)); ?></td>
            <td><?php echo e($room->location ?: 'Sin ubicacion'); ?></td>
            <td><?php echo e($room->floor ?: 'N/A'); ?></td>
            <td><?php echo e($room->simultaneous_capacity); ?></td>
            <td><?php echo e($room->responsible_name ?: 'Sin responsable'); ?></td>
            <td><?php echo e($room->schedules->map(fn ($schedule) => $dayNames[$schedule->day_of_week] ?? 'Dia')->implode(', ') ?: 'Sin horario'); ?></td>
            <td><span class="operational-chip"><?php echo e($room->status === 'active' ? 'Activo' : 'Inactivo'); ?></span></td>
            <td>
              <div class="operational-room-actions">
                <a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'room' => $room->id])); ?>">Ver</a>
                <a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'edit_room' => $room->id])); ?>">Editar</a>
              </div>
            </td>
          </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="9">Sin salas de infusi&oacute;n registradas para esta unidad.</td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </section>
  <?php if($viewingRoom && ! $showRoomForm): ?>
    <?php
      $viewingSchedule = $viewingRoom->schedules->map(
        fn ($schedule) => ($dayNames[$schedule->day_of_week] ?? 'Dia').' '.substr((string) $schedule->starts_at, 0, 5).'-'.substr((string) $schedule->ends_at, 0, 5)
      )->implode(' | ');
    ?>
    <section class="operational-native-table-card operational-room-detail" data-infusion-room-detail>
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">INFORMACI&Oacute;N GENERAL</p><h2><?php echo e($viewingRoom->unit_number); ?></h2><span>Ficha operativa de la sala de infusi&oacute;n</span></div>
        <a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'edit_room' => $viewingRoom->id])); ?>">Editar sala</a>
      </div>
      <dl class="operational-room-detail-grid">
        <div><dt>ID</dt><dd>ID-<?php echo e(str_pad((string) $viewingRoom->id, 4, '0', STR_PAD_LEFT)); ?></dd></div>
        <div><dt>N&uacute;mero de sala</dt><dd><?php echo e($viewingRoom->unit_number); ?></dd></div>
        <div><dt>Ubicaci&oacute;n</dt><dd><?php echo e($viewingRoom->location ?: 'Sin ubicacion'); ?></dd></div>
        <div><dt>Piso</dt><dd><?php echo e($viewingRoom->floor ?: 'Sin especificar'); ?></dd></div>
        <div><dt>Sillones de infusi&oacute;n</dt><dd><?php echo e($viewingRoom->simultaneous_capacity); ?></dd></div>
        <div><dt>Responsable</dt><dd><?php echo e($viewingRoom->responsible_name ?: 'Sin responsable'); ?></dd></div>
        <div class="span-2"><dt>Horario de atenci&oacute;n</dt><dd><?php echo e($viewingSchedule ?: 'Sin horario configurado'); ?></dd></div>
        <div><dt>Estatus</dt><dd><span class="operational-chip"><?php echo e($viewingRoom->status === 'active' ? 'Activo' : 'Inactivo'); ?></span></dd></div>
      </dl>
    </section>
  <?php endif; ?>
  <?php if($showRoomForm): ?>
    <section class="operational-native-table-card operational-form-card">
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">SALA DE INFUSI&Oacute;N</p><h2><?php echo e($editingRoom ? 'Editar sala de infusion' : 'Nueva sala de infusion'); ?></h2><span>Configura capacidad, responsable y horario de atenci&oacute;n.</span></div>
        <a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id])); ?>">Regresar</a>
      </div>
      <form class="operational-detail-form" method="post" action="<?php echo e($editingRoom ? route('operational.infusion-rooms.update', $editingRoom) : route('operational.infusion-rooms.store')); ?>">
        <?php echo csrf_field(); ?>
        <?php if($editingRoom): ?> <?php echo method_field('put'); ?> <?php endif; ?>
        <input type="hidden" name="unit" value="<?php echo e($contextUnit?->id); ?>">
        <label>Ubicaci&oacute;n *<input name="location" required value="<?php echo e(old('location', $editingRoom?->location)); ?>"></label>
        <label>Piso<input name="floor" value="<?php echo e(old('floor', $editingRoom?->floor)); ?>"></label>
        <label>N&uacute;mero de unidad *<input name="unit_number" required value="<?php echo e(old('unit_number', $editingRoom?->unit_number)); ?>" placeholder="SI-01"></label>
        <label>Capacidad simult&aacute;nea *<input name="simultaneous_capacity" type="number" min="1" max="99" required value="<?php echo e(old('simultaneous_capacity', $editingRoom?->simultaneous_capacity ?? 1)); ?>"></label>
        <label>Responsable de &aacute;rea<input name="responsible_name" value="<?php echo e(old('responsible_name', $editingRoom?->responsible_name)); ?>"></label>
        <label>Estatus<select name="status"><option value="active" <?php if(old('status', $editingRoom?->status ?? 'active') === 'active'): echo 'selected'; endif; ?>>Activo</option><option value="inactive" <?php if(old('status', $editingRoom?->status) === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
        <fieldset class="span-2 operational-room-schedule"><legend>Horario de atenci&oacute;n de la sala</legend>
          <?php $__currentLoopData = $dayKeys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayNumber => $dayKey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $daySchedule = $editingRoom?->schedules->firstWhere('day_of_week', $dayNumber); ?>
            <div class="operational-room-schedule-row">
              <label><input type="checkbox" name="schedule[<?php echo e($dayKey); ?>][enabled]" value="1" <?php if(old("schedule.$dayKey.enabled", (bool) $daySchedule)): echo 'checked'; endif; ?>> <?php echo e($dayNames[$dayNumber]); ?></label>
              <label>Hora inicio<input type="time" name="schedule[<?php echo e($dayKey); ?>][start]" value="<?php echo e(old("schedule.$dayKey.start", $daySchedule?->starts_at ? substr((string) $daySchedule->starts_at, 0, 5) : '08:00')); ?>"></label>
              <label>Hora fin<input type="time" name="schedule[<?php echo e($dayKey); ?>][end]" value="<?php echo e(old("schedule.$dayKey.end", $daySchedule?->ends_at ? substr((string) $daySchedule->ends_at, 0, 5) : '16:00')); ?>"></label>
            </div>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </fieldset>
        <button class="operational-primary-button span-2" type="submit"><?php echo e($editingRoom ? 'Guardar cambios' : 'Guardar sala'); ?></button>
      </form>
    </section>
  <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/operational/sections/oncology.blade.php ENDPATH**/ ?>