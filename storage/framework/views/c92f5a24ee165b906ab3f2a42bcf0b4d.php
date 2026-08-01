<?php
  $serviceRows = $section === 'services-history' ? $historicalProviderRequests : $pendingProviderRequests;
  $mixRows = $section === 'mix-history'
    ? $providerRequests->whereIn('status', ['delivered', 'cancelled', 'rejected'])
    : $providerRequests->whereNotIn('status', ['delivered', 'cancelled', 'rejected']);
?>

<?php if(in_array($section, ['services-pending', 'services-history'], true)): ?>
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">AREA ADMINISTRATIVA</p><h2><?php echo e($section === 'services-history' ? 'Historial de servicios' : 'Servicios pendientes'); ?></h2><span>Solicitudes oncolÃ³gicas recibidas por <?php echo e($unitName); ?></span></div>
      <a class="operational-primary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'service-create'])); ?>">+ Nuevo servicio</a>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table">
        <thead><tr><th>Folio</th><th>Paciente</th><th>Servicio</th><th>MÃ©dico</th><th>Fecha requerida</th><th>Volumen</th><th>Estatus</th><th>Acciones</th></tr></thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $serviceRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr><td><strong><?php echo e($item->external_id ?? 'QT-'.$item->id); ?></strong></td><td><?php echo e($item->patient?->full_name ?? 'Sin paciente'); ?></td><td><?php echo e(data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica')); ?></td><td><?php echo e(data_get($item->payload, 'doctor', 'Sin mÃ©dico')); ?></td><td><?php echo e($item->required_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td><td><?php echo e(data_get($item->payload, 'volume', 'N/A')); ?></td><td><span class="operational-chip"><?php echo e($statusText($item->status)); ?></span></td><td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'service-format', 'request' => $item->id])); ?>">Ver</a></td></tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr><td colspan="8">Sin servicios para esta vista.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
<?php elseif(in_array($section, ['service-create', 'service-format'], true)): ?>
  <?php $selectedRequest = $providerRequests->firstWhere('id', (int) request('request')); ?>
  <section class="operational-native-table-card operational-form-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">SOLICITUD DE ONCOLÃ“GICOS</p><h2><?php echo e($selectedRequest ? 'Detalle de solicitud' : 'Nuevo servicio'); ?></h2><span>Formato operativo para mezclas oncolÃ³gicas</span></div>
      <a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending'])); ?>">AtrÃ¡s</a>
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
<?php elseif(in_array($section, ['mixes', 'mix-history'], true)): ?>
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading"><div><p class="eyebrow">AREA OPERATIVA</p><h2><?php echo e($section === 'mix-history' ? 'Historial de mezclas' : 'Mezclas programadas'); ?></h2><span>PreparaciÃ³n y seguimiento de tratamientos</span></div><strong><?php echo e($mixRows->count()); ?> mezclas</strong></div>
    <div class="operational-native-table-scroll"><table class="operational-native-table"><thead><tr><th>Folio</th><th>Paciente</th><th>Mezcla</th><th>ProgramaciÃ³n</th><th>MÃ©dico</th><th>Estado</th><th>AcciÃ³n</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $mixRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><strong><?php echo e($item->external_id); ?></strong></td><td><?php echo e($item->patient?->full_name); ?></td><td><?php echo e(data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica')); ?></td><td><?php echo e($item->required_at?->format('d/m/Y H:i') ?? 'Por programar'); ?></td><td><?php echo e(data_get($item->payload, 'doctor', 'Sin mÃ©dico')); ?></td><td><span class="operational-chip"><?php echo e($statusText($item->status)); ?></span></td><td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'service-format', 'request' => $item->id])); ?>">Abrir</a></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="7">Sin mezclas registradas.</td></tr><?php endif; ?></tbody></table></div>
  </section>
<?php elseif($section === 'calendar'): ?>
  <?php
    $selectedCalendarRequest = $providerRequests->firstWhere('id', (int) request('schedule_request'));
    $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'MiÃ©rcoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'SÃ¡bado', 0 => 'Domingo'];
  ?>
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading"><div><p class="eyebrow">PROGRAMACIÃ“N</p><h2>Calendario de servicios</h2><span>Agenda de las salas de infusiÃ³n registradas en la unidad</span></div><strong><?php echo e($infusionRooms->count()); ?> salas</strong></div>
    <div class="operational-calendar-grid">
      <?php $__empty_1 = true; $__currentLoopData = $providerRequests->sortBy('required_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
        <?php
          $assignment = data_get($item->payload, 'infusion_assignment');
        ?>
        <article>
          <time><?php echo e(data_get($assignment, 'application_date') ? \Illuminate\Support\Carbon::parse(data_get($assignment, 'application_date'))->format('d M') : ($item->required_at?->format('d M') ?? 'S/F')); ?></time>
          <div>
            <strong><?php echo e($item->patient?->full_name ?? 'Sin paciente'); ?></strong>
            <span><?php echo e(data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica')); ?> Â· <?php echo e(data_get($item->payload, 'doctor', 'Sin mÃ©dico')); ?></span>
            <span><?php echo e(data_get($assignment, 'room_number', 'Sala pendiente')); ?><?php if(data_get($assignment, 'starts_at')): ?> Â· <?php echo e(data_get($assignment, 'starts_at')); ?>â€“<?php echo e(data_get($assignment, 'ends_at')); ?><?php endif; ?></span>
          </div>
          <div class="operational-calendar-actions">
            <span class="operational-chip"><?php echo e($assignment ? 'Agendado' : $statusText($item->status)); ?></span>
            <a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'unit' => $contextUnit?->id, 'schedule_request' => $item->id])); ?>"><?php echo e($assignment ? 'Reprogramar' : 'Agendar sala'); ?></a>
          </div>
        </article>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
        <p>Sin servicios programados.</p>
      <?php endif; ?>
    </div>
  </section>
  <?php if($selectedCalendarRequest): ?>
    <?php
      $currentAssignment = data_get($selectedCalendarRequest->payload, 'infusion_assignment', []);
    ?>
    <section class="operational-native-table-card operational-form-card">
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">AGENDAR SALA DE INFUSIÃ“N</p><h2><?php echo e($selectedCalendarRequest->patient?->full_name ?? 'Paciente sin nombre'); ?></h2><span><?php echo e($selectedCalendarRequest->external_id ?? 'QT-'.$selectedCalendarRequest->id); ?> Â· <?php echo e(data_get($selectedCalendarRequest->payload, 'service', 'OncologÃ­a mÃ©dica')); ?></span></div>
        <a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'unit' => $contextUnit?->id])); ?>">Cerrar</a>
      </div>
      <form class="operational-detail-form" method="post" action="<?php echo e(route('operational.infusion-assignments.update', $selectedCalendarRequest)); ?>">
        <?php echo csrf_field(); ?>
        <?php echo method_field('patch'); ?>
        <label>Sala de infusiÃ³n *
          <select name="procedure_area_id" required>
            <option value="">Selecciona una sala</option>
            <?php $__currentLoopData = $infusionRooms->where('status', 'active'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($room->id); ?>" <?php if((int) old('procedure_area_id', data_get($currentAssignment, 'procedure_area_id')) === $room->id): echo 'selected'; endif; ?>><?php echo e($room->unit_number); ?> Â· <?php echo e($room->location); ?> Â· capacidad <?php echo e($room->simultaneous_capacity); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>Fecha de aplicaciÃ³n *<input type="date" name="application_date" required value="<?php echo e(old('application_date', data_get($currentAssignment, 'application_date', $selectedCalendarRequest->required_at?->format('Y-m-d')))); ?>"></label>
        <label>Hora de inicio *<input type="time" name="starts_at" required value="<?php echo e(old('starts_at', data_get($currentAssignment, 'starts_at', $selectedCalendarRequest->required_at?->format('H:i') ?? '08:00'))); ?>"></label>
        <label>Hora de fin *<input type="time" name="ends_at" required value="<?php echo e(old('ends_at', data_get($currentAssignment, 'ends_at', '10:00'))); ?>"></label>
        <div class="span-2 operational-room-availability">
          <strong>Horarios configurados</strong>
          <?php $__empty_1 = true; $__currentLoopData = $infusionRooms->where('status', 'active'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <span><b><?php echo e($room->unit_number); ?></b>: <?php echo e($room->schedules->map(fn ($schedule) => ($dayNames[$schedule->day_of_week] ?? 'DÃ­a').' '.substr((string) $schedule->starts_at, 0, 5).'-'.substr((string) $schedule->ends_at, 0, 5))->implode(', ') ?: 'Sin horario disponible'); ?></span>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <span>No hay salas activas. Registra una desde Salas de infusiÃ³n.</span>
          <?php endif; ?>
        </div>
        <button class="operational-primary-button span-2" type="submit" <?php if($infusionRooms->where('status', 'active')->isEmpty()): echo 'disabled'; endif; ?>>Guardar programaciÃ³n</button>
      </form>
    </section>
  <?php endif; ?>
<?php elseif($section === 'infusion-rooms'): ?>
  <?php
    $editingRoom = $infusionRooms->firstWhere('id', (int) request('edit_room'));
    $showRoomForm = request()->boolean('new_room') || (bool) $editingRoom;
    $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'MiÃ©rcoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'SÃ¡bado', 0 => 'Domingo'];
    $dayKeys = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 0 => 'sunday'];
  ?>
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">INFRAESTRUCTURA</p><h2>Salas de infusiÃ³n</h2><span>Ãreas habilitadas en <?php echo e($unitName); ?></span></div>
      <div class="operational-heading-actions"><strong><?php echo e($infusionRooms->count()); ?> salas</strong><a class="operational-primary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-rooms', 'unit' => $contextUnit?->id, 'new_room' => 1])); ?>">+ Nueva sala</a></div>
    </div>
    <div class="operational-native-table-scroll"><table class="operational-native-table">
      <thead><tr><th>UbicaciÃ³n</th><th>Piso</th><th>NÃºmero</th><th>Capacidad simultÃ¡nea</th><th>Responsable</th><th>Horario</th><th>Estatus</th><th>Acciones</th></tr></thead>
      <tbody>
        <?php $__empty_1 = true; $__currentLoopData = $infusionRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr><td><strong><?php echo e($room->location ?: 'Sin ubicaciÃ³n'); ?></strong></td><td><?php echo e($room->floor ?: 'N/A'); ?></td><td><?php echo e($room->unit_number ?: 'N/A'); ?></td><td><?php echo e($room->simultaneous_capacity); ?> pacientes</td><td><?php echo e($room->responsible_name ?: 'Sin responsable'); ?></td><td><?php echo e($room->schedules->map(fn ($schedule) => $dayNames[$schedule->day_of_week] ?? 'DÃ­a')->implode(', ') ?: 'Sin horario'); ?></td><td><span class="operational-chip"><?php echo e($room->status === 'active' ? 'Activo' : 'Inactivo'); ?></span></td><td><a class="operational-view-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-rooms', 'unit' => $contextUnit?->id, 'edit_room' => $room->id])); ?>">Editar</a></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
          <tr><td colspan="8">Sin salas de infusiÃ³n registradas para esta unidad.</td></tr>
        <?php endif; ?>
      </tbody>
    </table></div>
  </section>
  <?php if($showRoomForm): ?>
    <section class="operational-native-table-card operational-form-card">
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">SALA DE INFUSIÃ“N</p><h2><?php echo e($editingRoom ? 'Editar sala de infusiÃ³n' : 'Nueva sala de infusiÃ³n'); ?></h2><span>Configura capacidad, responsable y horario de atenciÃ³n.</span></div>
        <a class="operational-secondary-button" href="<?php echo e(route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-rooms', 'unit' => $contextUnit?->id])); ?>">Regresar</a>
      </div>
      <form class="operational-detail-form" method="post" action="<?php echo e($editingRoom ? route('operational.infusion-rooms.update', $editingRoom) : route('operational.infusion-rooms.store')); ?>">
        <?php echo csrf_field(); ?>
        <?php if($editingRoom): ?> <?php echo method_field('put'); ?> <?php endif; ?>
        <input type="hidden" name="unit" value="<?php echo e($contextUnit?->id); ?>">
        <label>UbicaciÃ³n *<input name="location" required value="<?php echo e(old('location', $editingRoom?->location)); ?>"></label>
        <label>Piso<input name="floor" value="<?php echo e(old('floor', $editingRoom?->floor)); ?>"></label>
        <label>NÃºmero de unidad *<input name="unit_number" required value="<?php echo e(old('unit_number', $editingRoom?->unit_number)); ?>" placeholder="SI-01"></label>
        <label>Capacidad simultÃ¡nea *<input name="simultaneous_capacity" type="number" min="1" max="99" required value="<?php echo e(old('simultaneous_capacity', $editingRoom?->simultaneous_capacity ?? 1)); ?>"></label>
        <label>Responsable de Ã¡rea<input name="responsible_name" value="<?php echo e(old('responsible_name', $editingRoom?->responsible_name)); ?>"></label>
        <label>Estatus<select name="status"><option value="active" <?php if(old('status', $editingRoom?->status ?? 'active') === 'active'): echo 'selected'; endif; ?>>Activo</option><option value="inactive" <?php if(old('status', $editingRoom?->status) === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
        <fieldset class="span-2 operational-room-schedule"><legend>Horario de atenciÃ³n de la sala</legend>
          <?php $__currentLoopData = $dayKeys; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayNumber => $dayKey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($daySchedule = $editingRoom?->schedules->firstWhere('day_of_week', $dayNumber)); ?>
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
<?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/operational/sections/oncology.blade.php ENDPATH**/ ?>