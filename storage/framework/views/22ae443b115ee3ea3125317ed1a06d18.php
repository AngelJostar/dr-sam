

<?php $__env->startSection('body_class', 'operational-native-body outpatient-module-body'); ?>

<?php
  $unitName = $unit?->name ?? 'H.G. CHIMALHUACAN';
  $unitCode = $unit?->code ?? $unit?->clues ?? 'MCIMB001841';
  $institutionName = $unit?->institution?->name ?? 'IMSS BIENESTAR ESTADO DE MEXICO';
  $statusLabels = ['scheduled' => 'Agendada', 'in_progress' => 'En consulta', 'completed' => 'Atendida', 'cancelled' => 'Cancelada'];
  $sectionNavigation = [
    ['section' => 'agenda', 'label' => 'Consulta externa'],
    ['section' => 'prescriptions', 'label' => 'Recetas'],
    ['section' => 'rooms', 'label' => 'Catalogo de consultorios'],
  ];
  $patientCatalogRouteParameters = $unit ? ['unit' => $unit->id] : [];
  $areaDefaultRoutes = [
    'nursing' => ['area' => 'nursing', 'section' => 'pending'],
    'oncology' => ['area' => 'oncology', 'section' => 'calendar', 'oncology_track' => 'infusions'],
    'inpatient-pharmacy' => ['area' => 'inpatient-pharmacy', 'section' => 'pending'],
  ];
?>

<?php $__env->startSection('content'); ?>
  <div class="operational-native-screen outpatient-operational-screen">
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
        <a href="<?php echo e(route('operational.dashboard', $areaDefaultRoutes['nursing'])); ?>">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 21V5a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v16"/><path d="M16 9h3a1 1 0 0 1 1 1v11"/><path d="M8 8h4M10 6v4M8 14h4M8 18h4"/></svg>
          </span>
          Hospitalizacion
          <i aria-hidden="true">›</i>
        </a>
        <a href="<?php echo e(route('operational.dashboard', $areaDefaultRoutes['oncology'])); ?>">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M4 6h16v15H4z"/><path d="M9 3h6v3H9z"/><path d="M12 10v7M8.5 13.5h7"/></svg>
          </span>
          Centro Oncologico
          <i aria-hidden="true">›</i>
        </a>
        <a href="<?php echo e(route('operational.dashboard', $areaDefaultRoutes['inpatient-pharmacy'])); ?>">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="m7 16 9-9a3 3 0 0 1 4 4l-9 9a3 3 0 0 1-4-4Z"/><path d="m12 11 4 4"/></svg>
          </span>
          Farmacia intrahospitalaria
          <i aria-hidden="true">›</i>
        </a>
        <a class="is-active" href="<?php echo e(route('outpatient.dashboard')); ?>">
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
        <a href="<?php echo e(route('operational.patients.index', $patientCatalogRouteParameters)); ?>">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          </span>
          Catalogo de pacientes
          <i aria-hidden="true">›</i>
        </a>
      </nav>
    </aside>

    <section class="operational-native-workspace outpatient-module-main">
      <?php if(session('status')): ?><div class="notice success"><?php echo e(session('status')); ?></div><?php endif; ?>
      <?php if($errors->any()): ?><div class="notice danger"><?php echo e($errors->first()); ?></div><?php endif; ?>

      <section class="operational-oncology-carousel operational-compact-carousel outpatient-module-carousel" aria-label="Filtros de Consulta Externa" data-outpatient-carousel>
        <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-prev aria-label="Anterior">‹</button>
        <div class="operational-oncology-carousel-track">
          <a class="operational-oncology-filter is-active" href="<?php echo e(route('outpatient.dashboard', ['section' => 'agenda'])); ?>">
            <span class="operational-oncology-filter-initial" aria-hidden="true">C</span>
            <span class="operational-oncology-filter-copy">
              <strong>Calendario</strong>
              <small>✓ Seleccionada</small>
            </span>
          </a>
        </div>
        <button class="operational-oncology-carousel-arrow" type="button" data-operational-carousel-next aria-label="Siguiente">›</button>
      </section>

      <nav class="operational-section-tabs outpatient-module-tabs" aria-label="Secciones de Consulta Externa">
        <?php $__currentLoopData = $sectionNavigation; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $navigationItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $section === $navigationItem['section']]); ?>" href="<?php echo e(route('outpatient.dashboard', ['section' => $navigationItem['section']])); ?>">
            <?php echo e($navigationItem['label']); ?>

          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </nav>

      <?php if($section === 'agenda'): ?>
        <section class="outpatient-module-card operational-native-table-card operational-section-card">
          <div class="outpatient-module-card-head operational-native-table-heading"><div><p class="eyebrow">CONSULTA EXTERNA</p><h2>Agenda institucional generada</h2><p>Citas sincronizadas con agenda institucional del médico.</p></div><div><strong><?php echo e($appointments->count()); ?> <?php echo e($appointments->count() === 1 ? 'cita' : 'citas'); ?></strong><a class="operational-secondary-button is-secondary" href="<?php echo e(route('outpatient.dashboard', ['admin' => 1])); ?>">Administrar Calendario</a><a class="operational-primary-button" href="<?php echo e(route('outpatient.dashboard', ['new' => 1])); ?>">+ Nueva consulta</a></div></div>
          <?php if(request('admin')): ?>
            <section class="outpatient-calendar-admin">
              <form method="post" data-calendar-admin-form>
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <label>Día activo<select name="day_of_week"><option value="1">Lunes</option><option value="2">Martes</option><option value="3">Miércoles</option><option value="4">Jueves</option><option value="5">Viernes</option></select></label>
                <label>Especialidad<input name="specialty" required value="Medicina interna"></label>
                <label>Inicio<input name="starts_at" type="time" value="08:00" required></label>
                <label>Fin<input name="ends_at" type="time" value="14:00" required></label>
                <label>Duración<select name="duration"><option>20</option><option selected>30</option><option>45</option><option>60</option></select></label>
                <label>Consultorio<select data-calendar-room required><option value="">Seleccionar consultorio</option><?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> <?php if(data_get($room, 'database_id')): ?><option value="<?php echo e(data_get($room, 'database_id')); ?>" data-specialty="<?php echo e(data_get($room, 'specialty')); ?>"><?php echo e(data_get($room, 'name', data_get($room, 'number'))); ?></option><?php endif; ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                <div class="span-2 outpatient-form-actions"><a href="<?php echo e(route('outpatient.dashboard')); ?>">Cancelar</a><button type="submit">Guardar slots</button></div>
              </form>
            </section>
          <?php endif; ?>
          <?php if(request('new')): ?>
            <form class="outpatient-appointment-form" method="post" action="<?php echo e(route('outpatient.appointments.store')); ?>">
              <?php echo csrf_field(); ?>
              <label class="span-2">Paciente<select name="patient_id" required data-outpatient-patient><option value="">Buscar paciente por nombre, usuario, CURP o NSS</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>" data-platform="<?php echo e($patient->platform_number); ?>" data-federal="<?php echo e(data_get($patient->metadata, 'nss_federal')); ?>" data-state="<?php echo e(data_get($patient->metadata, 'nss_estatal')); ?>" data-curp="<?php echo e($patient->curp); ?>"><?php echo e($patient->full_name); ?> · <?php echo e($patient->platform_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Número de usuario de la plataforma<input data-patient-platform readonly></label>
              <label>CURP<input data-patient-curp readonly></label>
              <label>Número de seguridad social Federal<input data-patient-federal readonly></label>
              <label>Número de seguridad social estatal<input data-patient-state readonly></label>
              <label>Médico<select name="doctor_id" required><option value="">Seleccionar médico</option><?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($doctor->id); ?>"><?php echo e($doctor->full_name); ?><?php echo e($doctor->availabilityRules->isNotEmpty() ? ' · agenda publicada' : ''); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Especialidad<input name="specialty" required></label>
              <label>Consultorio<select name="procedure_area_id"><option value="">Seleccionar consultorio</option><?php $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e(data_get($room, 'database_id')); ?>"><?php echo e(data_get($room, 'number', data_get($room, 'location'))); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><input type="hidden" name="location" value="Consulta externa"></label>
              <label>Modalidad<select name="modality"><option>Presencial</option><option>Video llamada</option></select></label>
              <label>Fecha<input type="date" name="appointment_date" required></label>
              <label>Hora<input type="time" name="appointment_time" required><small>Se valida con el calendario del consultorio.</small></label>
              <label>Duración<select name="duration"><option value="20">20 minutos</option><option value="30" selected>30 minutos</option><option value="45">45 minutos</option><option value="60">1 hora</option></select></label>
              <label>Motivo<select name="reason"><option>Primera Vez</option><option>Seguimiento</option></select></label>
              <p class="span-2 outpatient-schedule-help">La fecha se valida contra la agenda del médico y la capacidad del consultorio.</p>
              <div class="span-2 outpatient-form-actions"><a href="<?php echo e(route('outpatient.dashboard')); ?>">Cancelar</a><button type="submit">Agregar consulta</button></div>
            </form>
          <?php endif; ?>
          <div class="outpatient-table-wrap"><table><thead><tr><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Usuario plataforma</th><th>Médico</th><th>Especialidad</th><th>Consultorio</th><th>Modalidad</th><th>Estado</th></tr></thead><tbody>
            <?php $__empty_1 = true; $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($appointment->starts_at?->format('d/m/Y')); ?></td><td><strong><?php echo e($appointment->starts_at?->format('H:i')); ?></strong></td><td><strong><?php echo e($appointment->patient?->full_name); ?></strong></td><td><?php echo e($appointment->patient?->platform_number ?? 'Sin usuario'); ?></td><td><?php echo e($appointment->doctor?->full_name); ?></td><td><?php echo e($appointment->specialty); ?></td><td><?php echo e($appointment->location); ?></td><td><?php echo e($appointment->modality); ?></td><td><span class="outpatient-status"><?php echo e($statusLabels[$appointment->status] ?? $appointment->status); ?></span></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="9">Sin consultas registradas.</td></tr><?php endif; ?>
          </tbody></table></div>
        </section>
      <?php elseif($section === 'prescriptions'): ?>
        <?php
          $editingPrescription = request('edit') ? $prescriptions->firstWhere('id', (int) request('edit')) : null;
          $viewingPrescription = request('view') ? $prescriptions->firstWhere('id', (int) request('view')) : null;
          $formItems = old('items', $editingPrescription?->items?->map(fn($item) => ['medication_catalog_item_id' => $item->medication_catalog_item_id, 'medication_name' => $item->medication_name, 'cnis' => data_get($item->metadata, 'cnis'), 'dose' => $item->dose, 'presentation' => data_get($item->metadata, 'presentation'), 'route' => data_get($item->metadata, 'route'), 'frequency' => $item->frequency, 'duration' => $item->duration, 'quantity' => data_get($item->metadata, 'quantity'), 'instructions' => $item->instructions])->all() ?? [['medication_name' => '']]);
        ?>
        <section class="outpatient-module-card operational-native-table-card operational-section-card">
          <div class="outpatient-module-card-head operational-native-table-heading"><div><p class="eyebrow">CONSULTA EXTERNA</p><h2>Recetas emitidas</h2><p>Formatos asociados a las consultas de la unidad.</p></div><div><strong><?php echo e($prescriptions->count()); ?> recetas</strong><a class="operational-primary-button" href="<?php echo e(route('outpatient.dashboard', ['section' => 'prescriptions', 'new' => 1])); ?>">+ Nueva receta</a></div></div>
          <div class="outpatient-table-wrap"><table><thead><tr><th>Fecha</th><th>Paciente</th><th>Usuario plataforma</th><th>Médico</th><th>Especialidad</th><th>Receta</th><th>Estado</th></tr></thead><tbody>
          <?php $__empty_1 = true; $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prescription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($prescription->issued_at?->format('d/m/Y')); ?></td><td><strong><?php echo e($prescription->patient?->full_name); ?></strong></td><td><?php echo e($prescription->patient?->platform_number ?? 'Sin usuario'); ?></td><td><?php echo e($prescription->doctor?->full_name); ?></td><td><?php echo e($prescription->doctor?->specialty); ?></td><td><strong><?php echo e($prescription->code ?? 'REC-'.$prescription->id); ?></strong><small><?php echo e($prescription->items->count()); ?> medicamento(s)</small></td><td><span class="outpatient-status"><?php echo e(ucfirst($prescription->status)); ?></span></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="7">Sin recetas registradas.</td></tr><?php endif; ?>
          </tbody></table></div>
        </section>
        <?php if($viewingPrescription): ?>
          <section class="outpatient-module-card operational-native-table-card operational-section-card outpatient-prescription-detail"><div class="outpatient-module-card-head operational-native-table-heading"><div><h2>Receta <?php echo e($viewingPrescription->code); ?></h2><p><?php echo e($viewingPrescription->patient?->full_name); ?> · Diagnóstico: <?php echo e(data_get($viewingPrescription->metadata, 'diagnosis')); ?></p></div><a class="operational-secondary-button is-secondary" href="<?php echo e(route('outpatient.dashboard', ['section' => 'prescriptions'])); ?>">Cerrar</a></div><div class="outpatient-table-wrap"><table><thead><tr><th>No.</th><th>Clave CNIS</th><th>Medicamento</th><th>Dosis</th><th>Presentación</th><th>Vía</th><th>Frecuencia</th><th>Duración</th><th>Cantidad</th><th>Indicaciones</th></tr></thead><tbody><?php $__currentLoopData = $viewingPrescription->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr><td><?php echo e($loop->iteration); ?></td><td><?php echo e(data_get($item->metadata, 'cnis')); ?></td><td><strong><?php echo e($item->medication_name); ?></strong></td><td><?php echo e($item->dose); ?></td><td><?php echo e(data_get($item->metadata, 'presentation')); ?></td><td><?php echo e(data_get($item->metadata, 'route')); ?></td><td><?php echo e($item->frequency); ?></td><td><?php echo e($item->duration); ?></td><td><?php echo e(data_get($item->metadata, 'quantity')); ?></td><td><?php echo e($item->instructions); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table></div></section>
        <?php endif; ?>
        <?php if(request('new') || $editingPrescription): ?>
          <section class="outpatient-module-card operational-native-table-card operational-section-card outpatient-prescription-form-card"><div class="outpatient-module-card-head operational-native-table-heading"><div><h2><?php echo e($editingPrescription ? 'Editar receta médica' : 'Nueva receta médica'); ?></h2><p>Formato para Consulta Externa y Farmacia Externa.</p></div><a class="operational-secondary-button is-secondary" href="<?php echo e(route('outpatient.dashboard', ['section' => 'prescriptions'])); ?>">Regresar</a></div>
            <form method="post" action="<?php echo e($editingPrescription ? route('outpatient.prescriptions.update', $editingPrescription) : route('outpatient.prescriptions.store')); ?>" data-outpatient-prescription><?php echo csrf_field(); ?> <?php if($editingPrescription): ?> <?php echo method_field('PATCH'); ?> <?php endif; ?>
              <div class="outpatient-prescription-fields"><label>Folio<input name="code" value="<?php echo e(old('code', $editingPrescription?->code)); ?>" placeholder="Se genera automáticamente"></label><label>Fecha<input type="date" name="issued_at" required value="<?php echo e(old('issued_at', $editingPrescription?->issued_at?->format('Y-m-d') ?? now()->format('Y-m-d'))); ?>"></label><label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>" <?php if((int) old('patient_id', $editingPrescription?->patient_id) === $patient->id): echo 'selected'; endif; ?>><?php echo e($patient->full_name); ?> · <?php echo e($patient->platform_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label><label>Médico tratante<select name="doctor_id" required><option value="">Seleccionar médico</option><?php $__currentLoopData = $doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($doctor->id); ?>" <?php if((int) old('doctor_id', $editingPrescription?->doctor_id) === $doctor->id): echo 'selected'; endif; ?>><?php echo e($doctor->full_name); ?> · <?php echo e($doctor->specialty); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label><label class="span-2">Diagnóstico<textarea name="diagnosis" required><?php echo e(old('diagnosis', data_get($editingPrescription?->metadata, 'diagnosis'))); ?></textarea></label><label>Estado<select name="status"><?php $__currentLoopData = ['active'=>'Activa','pending'=>'Pendiente','filled'=>'Surtida','cancelled'=>'Cancelada']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(old('status', $editingPrescription?->status ?? 'active') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label><label>Notas<textarea name="notes"><?php echo e(old('notes', $editingPrescription?->notes)); ?></textarea></label></div>
              <div class="outpatient-prescription-items"><h3>Medicamentos indicados</h3><div class="outpatient-table-wrap"><table><thead><tr><th>No.</th><th>Clave CNIS</th><th>Medicamento</th><th>Dosis</th><th>Presentación</th><th>Vía</th><th>Frecuencia</th><th>Duración</th><th>Cantidad</th><th>Indicaciones</th></tr></thead><tbody data-prescription-items><?php $__currentLoopData = $formItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><tr data-prescription-item><td><span data-item-number><?php echo e($loop->iteration); ?></span><button type="button" data-remove-prescription-item>−</button></td><td><input name="items[<?php echo e($loop->index); ?>][cnis]" value="<?php echo e(data_get($item, 'cnis')); ?>"></td><td><input type="hidden" name="items[<?php echo e($loop->index); ?>][medication_catalog_item_id]" value="<?php echo e(data_get($item, 'medication_catalog_item_id')); ?>"><input name="items[<?php echo e($loop->index); ?>][medication_name]" list="outpatient-medications" required value="<?php echo e(data_get($item, 'medication_name')); ?>"></td><td><input name="items[<?php echo e($loop->index); ?>][dose]" value="<?php echo e(data_get($item, 'dose')); ?>"></td><td><input name="items[<?php echo e($loop->index); ?>][presentation]" value="<?php echo e(data_get($item, 'presentation')); ?>"></td><td><input name="items[<?php echo e($loop->index); ?>][route]" value="<?php echo e(data_get($item, 'route')); ?>"></td><td><input name="items[<?php echo e($loop->index); ?>][frequency]" value="<?php echo e(data_get($item, 'frequency')); ?>"></td><td><input name="items[<?php echo e($loop->index); ?>][duration]" value="<?php echo e(data_get($item, 'duration')); ?>"></td><td><input name="items[<?php echo e($loop->index); ?>][quantity]" value="<?php echo e(data_get($item, 'quantity')); ?>"></td><td><textarea name="items[<?php echo e($loop->index); ?>][instructions]"><?php echo e(data_get($item, 'instructions')); ?></textarea></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tbody></table></div><button type="button" class="outpatient-add-prescription-item" data-add-prescription-item>+</button></div>
              <datalist id="outpatient-medications"><?php $__currentLoopData = $medications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $medication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($medication->generic_name ?? $medication->name); ?>"><?php echo e($medication->code); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></datalist><div class="outpatient-form-actions"><a href="<?php echo e(route('outpatient.dashboard', ['section' => 'prescriptions'])); ?>">Cancelar</a><button type="submit">Guardar receta</button></div>
            </form>
          </section>
        <?php endif; ?>
      <?php else: ?>
        <?php
          $editingRoom = request('edit') ? $rooms->firstWhere('database_id', (int) request('edit')) : null;
          $roomDays = ['monday' => [1, 'Lunes'], 'tuesday' => [2, 'Martes'], 'wednesday' => [3, 'Miércoles'], 'thursday' => [4, 'Jueves'], 'friday' => [5, 'Viernes'], 'saturday' => [6, 'Sábado'], 'sunday' => [0, 'Domingo']];
          $dayLabels = collect($roomDays)->mapWithKeys(fn ($value) => [$value[0] => $value[1]]);
        ?>
        <section class="outpatient-module-card operational-native-table-card operational-section-card">
          <div class="outpatient-module-card-head operational-native-table-heading"><div><p class="eyebrow">CONSULTA EXTERNA</p><h2>Catálogo de consultorios</h2><p>Consultorios registrados en la unidad</p></div><a class="operational-primary-button" href="<?php echo e(route('outpatient.dashboard', ['section' => 'rooms', 'new' => 1])); ?>">+ Nuevo consultorio</a></div>
          <div class="outpatient-table-wrap"><table class="outpatient-room-catalog-table"><thead><tr><th>Consultorio</th><th>Clave</th><th>Ubicación</th><th>Especialidad</th><th>Tipo</th><th>Disponibilidad</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
          <?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $dateRanges = collect(data_get($room, 'availability_ranges', []));
              $scheduleCount = $dateRanges->isNotEmpty() ? $dateRanges->count() : collect(data_get($room, 'schedule', []))->count();
              $availability = $scheduleCount ? $scheduleCount.' días / '.$scheduleCount.' rangos' : 'Sin rangos';
            ?>
            <tr><td><strong><?php echo e(data_get($room, 'name', data_get($room, 'number', 'Consultorio'))); ?></strong></td><td><?php echo e(data_get($room, 'id', data_get($room, 'number', 'N/A'))); ?></td><td><?php echo e(data_get($room, 'location', 'Consulta externa')); ?></td><td><?php echo e(data_get($room, 'specialty', 'Consulta externa')); ?></td><td><?php echo e(data_get($room, 'modality', 'Presencial')); ?></td><td><?php echo e($availability ?: 'Sin disponibilidad'); ?></td><td><span class="outpatient-status"><?php echo e(data_get($room, 'status') === 'inactive' ? 'Inactivo' : 'Activo'); ?></span></td><td><div class="outpatient-row-actions"><a class="outpatient-row-action" href="<?php echo e(route('outpatient.dashboard', ['section' => 'rooms', 'edit' => data_get($room, 'database_id')])); ?>">Editar</a><?php if(data_get($room, 'database_id')): ?><form method="post" action="<?php echo e(route('outpatient.rooms.destroy', data_get($room, 'database_id'))); ?>" onsubmit="return confirm('¿Eliminar este consultorio?');"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="outpatient-row-action outpatient-row-danger" type="submit">Eliminar</button></form><?php endif; ?></div></td></tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="8">Sin consultorios registrados.</td></tr><?php endif; ?>
          </tbody></table></div>
        </section>

        <?php if(request('new') || $editingRoom): ?>
          <section class="outpatient-module-card operational-native-table-card operational-section-card outpatient-room-form-card">
            <?php
              $initialRanges = collect(data_get($editingRoom, 'availability_ranges', []));
              if ($initialRanges->isEmpty() && $editingRoom) {
                $baseMonth = now()->startOfMonth();
                $initialRanges = collect(data_get($editingRoom, 'schedule', []))->map(function ($range) use ($baseMonth, $editingRoom) {
                  $date = $baseMonth->copy()->next((int) data_get($range, 'day'));
                  return ['date' => $date->toDateString(), 'start' => data_get($range, 'start'), 'end' => data_get($range, 'end'), 'duration' => 30, 'modality' => data_get($editingRoom, 'modality', 'Presencial')];
                });
              }
            ?>
            <form class="outpatient-room-form" data-outpatient-room-form method="post" action="<?php echo e($editingRoom ? route('outpatient.rooms.update', data_get($editingRoom, 'database_id')) : route('outpatient.rooms.store')); ?>">
              <?php echo csrf_field(); ?> <?php if($editingRoom): ?> <?php echo method_field('PATCH'); ?> <?php endif; ?>
              <h2><?php echo e($editingRoom ? 'Editar consultorio' : 'Nuevo consultorio'); ?></h2>
              <div class="outpatient-room-fields">
                <label>Nombre<input name="name" required value="<?php echo e(old('name', data_get($editingRoom, 'name', data_get($editingRoom, 'number')))); ?>"></label>
                <label>Clave<input name="code" required value="<?php echo e(old('code', data_get($editingRoom, 'id'))); ?>"></label>
                <label>Ubicación<input name="location" required value="<?php echo e(old('location', data_get($editingRoom, 'location', 'Consulta externa'))); ?>"></label>
                <label>Especialidad<select name="specialty" required><?php $__currentLoopData = ['Medicina Interna','Medicina Familiar','Pediatría','Ginecología y Obstetricia','Consulta Externa']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if(old('specialty', data_get($editingRoom, 'specialty', 'Medicina Interna')) === $specialty): echo 'selected'; endif; ?>><?php echo e($specialty); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                <label>Tipo<select name="modality"><option <?php if(old('modality', data_get($editingRoom, 'modality')) === 'Presencial'): echo 'selected'; endif; ?>>Presencial</option><option <?php if(old('modality', data_get($editingRoom, 'modality')) === 'Video llamada'): echo 'selected'; endif; ?>>Video llamada</option></select></label>
                <label>Estado<select name="status"><option value="active" <?php if(old('status', data_get($editingRoom, 'status', 'active')) === 'active'): echo 'selected'; endif; ?>>Activo</option><option value="inactive" <?php if(old('status', data_get($editingRoom, 'status')) === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
                <input type="hidden" name="capacity" value="<?php echo e(old('capacity', data_get($editingRoom, 'capacity', 1))); ?>">
              </div>
              <input type="hidden" name="availability_ranges" data-room-ranges value="<?php echo e($initialRanges->values()->toJson()); ?>">
              <section class="outpatient-room-calendar" data-room-calendar>
                <div class="outpatient-room-calendar-head"><div><h3>Calendario de disponibilidad</h3><p>Define horarios disponibles por día. Puedes agregar varios rangos.</p></div><div><button type="button" data-calendar-prev>Anterior</button><strong data-calendar-label></strong><button type="button" data-calendar-next>Siguiente</button></div></div>
                <div class="outpatient-room-calendar-week"><span>LUN</span><span>MAR</span><span>MIÉ</span><span>JUE</span><span>VIE</span><span>SÁB</span><span>DOM</span></div>
                <div class="outpatient-room-calendar-grid" data-calendar-grid></div>
                <div class="outpatient-room-day-editor">
                  <div><strong data-selected-date></strong><span class="outpatient-status" data-range-count>0 rangos</span></div>
                  <div data-day-ranges></div>
                  <p data-empty-ranges>Agrega un rango para abrir disponibilidad.</p>
                  <div class="outpatient-room-range-fields">
                    <label>Inicio<input type="time" value="08:00" data-range-start></label>
                    <label>Fin<input type="time" value="14:00" data-range-end></label>
                    <label>Duración de consulta<select data-range-duration><option>20</option><option selected>30</option><option>45</option><option>60</option></select></label>
                    <label>Modalidad<select data-range-modality><option>Presencial</option><option>Video llamada</option></select></label>
                  </div>
                  <button type="button" class="outpatient-add-range" data-add-range>+ Agregar rango</button>
                </div>
              </section>
              <div class="outpatient-form-actions"><a href="<?php echo e(route('outpatient.dashboard', ['section' => 'rooms'])); ?>">Cancelar</a><button type="submit">Guardar consultorio</button></div>
            </form>
          </section>
        <?php endif; ?>
      <?php endif; ?>
    </section>
  </div>
  <script>
    (() => {
      document.querySelectorAll('[data-operational-carousel-prev], [data-operational-carousel-next]').forEach((button) => {
        button.addEventListener('click', () => {
          const carousel = button.closest('[data-outpatient-carousel]');
          const track = carousel?.querySelector('.operational-oncology-carousel-track');
          if (!track) return;

          track.scrollBy({
            left: button.matches('[data-operational-carousel-prev]') ? -260 : 260,
            behavior: 'smooth',
          });
        });
      });

      const patient = document.querySelector('[data-outpatient-patient]');
      const fillPatient = () => {
        const option = patient?.selectedOptions[0];
        if (!option) return;
        document.querySelector('[data-patient-platform]').value = option.dataset.platform || '';
        document.querySelector('[data-patient-curp]').value = option.dataset.curp || '';
        document.querySelector('[data-patient-federal]').value = option.dataset.federal || '';
        document.querySelector('[data-patient-state]').value = option.dataset.state || '';
      };
      patient?.addEventListener('change', fillPatient);
      fillPatient();

      const calendarForm = document.querySelector('[data-calendar-admin-form]');
      const room = calendarForm?.querySelector('[data-calendar-room]');
      room?.addEventListener('change', () => {
        calendarForm.action = `<?php echo e(url('/outpatient/calendar')); ?>/${room.value}`;
        const specialty = room.selectedOptions[0]?.dataset.specialty;
        if (specialty) calendarForm.elements.specialty.value = specialty;
      });
      calendarForm?.addEventListener('submit', event => {
        if (!room.value) event.preventDefault();
      });

      const prescriptionRows = document.querySelector('[data-prescription-items]');
      const prescriptionTemplate = prescriptionRows?.querySelector('[data-prescription-item]')?.cloneNode(true);
      const renumberPrescriptionRows = () => prescriptionRows?.querySelectorAll('[data-prescription-item]').forEach((row, index) => {
        row.querySelector('[data-item-number]').textContent = index + 1;
        row.querySelectorAll('[name]').forEach(field => field.name = field.name.replace(/items\[\d+\]/, `items[${index}]`));
      });
      document.querySelector('[data-add-prescription-item]')?.addEventListener('click', () => {
        if (!prescriptionTemplate) return;
        const row = prescriptionTemplate.cloneNode(true);
        row.querySelectorAll('input, textarea').forEach(field => field.value = '');
        prescriptionRows.appendChild(row);
        renumberPrescriptionRows();
      });
      prescriptionRows?.addEventListener('click', event => {
        if (!event.target.closest('[data-remove-prescription-item]')) return;
        if (prescriptionRows.querySelectorAll('[data-prescription-item]').length === 1) {
          event.target.closest('[data-prescription-item]').querySelectorAll('input, textarea').forEach(field => field.value = '');
          return;
        }
        event.target.closest('[data-prescription-item]').remove();
        renumberPrescriptionRows();
      });

      const roomCalendar = document.querySelector('[data-room-calendar]');
      if (roomCalendar) {
        const form = roomCalendar.closest('form');
        const rangesInput = form.querySelector('[data-room-ranges]');
        let ranges = JSON.parse(rangesInput.value || '[]');
        let cursor = new Date();
        cursor = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
        let selected = new Date(cursor.getFullYear(), cursor.getMonth(), Math.min(new Date().getDate(), new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0).getDate()));
        const iso = date => `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,'0')}-${String(date.getDate()).padStart(2,'0')}`;
        const dateLabel = date => new Intl.DateTimeFormat('es-MX', {weekday:'long', day:'numeric', month:'long', year:'numeric'}).format(date);
        const sync = () => { rangesInput.value = JSON.stringify(ranges); };
        const renderEditor = () => {
          const dayRanges = ranges.filter(range => range.date === iso(selected));
          roomCalendar.querySelector('[data-selected-date]').textContent = dateLabel(selected);
          roomCalendar.querySelector('[data-range-count]').textContent = `${dayRanges.length} ${dayRanges.length === 1 ? 'rango' : 'rangos'}`;
          roomCalendar.querySelector('[data-empty-ranges]').hidden = dayRanges.length > 0;
          roomCalendar.querySelector('[data-day-ranges]').innerHTML = dayRanges.map((range, index) => `<div class="outpatient-room-range"><strong>${range.start} - ${range.end}</strong><span>${range.duration} min · ${range.modality}</span><button type="button" data-remove-range="${index}">Eliminar</button></div>`).join('');
        };
        const renderCalendar = () => {
          roomCalendar.querySelector('[data-calendar-label]').textContent = new Intl.DateTimeFormat('es-MX', {month:'long', year:'numeric'}).format(cursor);
          const first = new Date(cursor.getFullYear(), cursor.getMonth(), 1);
          const offset = (first.getDay() + 6) % 7;
          const start = new Date(first); start.setDate(first.getDate() - offset);
          roomCalendar.querySelector('[data-calendar-grid]').innerHTML = Array.from({length:42}, (_, index) => {
            const day = new Date(start); day.setDate(start.getDate() + index);
            const count = ranges.filter(range => range.date === iso(day)).length;
            return `<button type="button" data-calendar-date="${iso(day)}" class="${day.getMonth() !== cursor.getMonth() ? 'is-outside' : ''} ${iso(day) === iso(selected) ? 'is-selected' : ''}"><strong>${day.getDate()}</strong><small>${count ? `${count} rango${count === 1 ? '' : 's'}` : 'Sin rango'}</small></button>`;
          }).join('');
          renderEditor();
        };
        roomCalendar.addEventListener('click', event => {
          const dateButton = event.target.closest('[data-calendar-date]');
          if (dateButton) {
            selected = new Date(`${dateButton.dataset.calendarDate}T12:00:00`);
            cursor = new Date(selected.getFullYear(), selected.getMonth(), 1);
            renderCalendar();
            return;
          }
          if (event.target.closest('[data-calendar-prev]')) { cursor.setMonth(cursor.getMonth()-1); selected = new Date(cursor); renderCalendar(); return; }
          if (event.target.closest('[data-calendar-next]')) { cursor.setMonth(cursor.getMonth()+1); selected = new Date(cursor); renderCalendar(); return; }
          const remove = event.target.closest('[data-remove-range]');
          if (remove) {
            const dayRanges = ranges.filter(range => range.date === iso(selected));
            const target = dayRanges[Number(remove.dataset.removeRange)];
            ranges = ranges.filter(range => range !== target);
            sync(); renderCalendar(); return;
          }
          if (event.target.closest('[data-add-range]')) {
            const startValue = roomCalendar.querySelector('[data-range-start]').value;
            const endValue = roomCalendar.querySelector('[data-range-end]').value;
            if (!startValue || !endValue || endValue <= startValue) return;
            ranges.push({date:iso(selected), start:startValue, end:endValue, duration:Number(roomCalendar.querySelector('[data-range-duration]').value), modality:roomCalendar.querySelector('[data-range-modality]').value});
            sync(); renderCalendar();
          }
        });
        renderCalendar();
      }
    })();
  </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Modulo operativo - Consulta Externa'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/operational/outpatient.blade.php ENDPATH**/ ?>