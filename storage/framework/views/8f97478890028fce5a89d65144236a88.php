

<?php $__env->startSection('body_class', 'doctor-assistant-native-body'); ?>

<?php
  $statusLabels = [
    'active' => 'Activo',
    'scheduled' => 'Programada',
    'created' => 'Creado',
    'requested' => 'Solicitada',
    'pending' => 'Pendiente',
    'completed' => 'Completada',
    'cancelled' => 'Cancelada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $doctorInitials = collect(explode(' ', str_replace(['Dr.', 'Dra.'], '', $doctor->full_name)))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('');
  $activeSection = request('section', 'home');
  $isNptHistory = $activeSection === 'services' && request('type') === 'npt' && request('action') === 'history';
  // Valores seguros cuando otro apartado se renderiza con una confirmación de video en sesión.
  $videoPatientName = 'Paciente';
  $videoPatientNumber = 'Sin registro';
?>

<?php $__env->startSection('content'); ?>
  <div class="doctor-assistant-native-screen doctor-section-<?php echo e($activeSection); ?> <?php echo e($isNptHistory ? 'doctor-npt-history-page' : ''); ?>">
    <header class="doctor-assistant-native-topbar">
      <div class="doctor-assistant-native-brand">
        <span>+</span>
        <div>
          <strong>Asistente Clinico</strong>
          <small><?php echo e($doctor->full_name); ?> - <?php echo e($doctor->professional_license ?? 'Sin cedula'); ?></small>
        </div>
      </div>
      <div class="doctor-assistant-native-session">
        <a href="<?php echo e(route('orders.index')); ?>">Privada <small>Privada</small></a>
        <button type="button">
          <span><?php echo e($doctorInitials ?: 'MD'); ?></span>
          <strong><?php echo e(str_replace(['Dr. ', 'Dra. '], '', $doctor->full_name)); ?></strong>
          <small>Medico</small>
        </button>
      </div>
    </header>

    <main class="doctor-assistant-native-main">
      <aside class="doctor-assistant-native-menu" aria-label="Men&uacute; m&eacute;dico" data-doctor-menu hidden>
        <div class="doctor-menu-panel is-current" data-doctor-menu-panel="main">
          <button type="button" data-doctor-menu-open="consultation"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="5.5"/><path d="m15 15 4 4"/></svg>Consulta <b>&rsaquo;</b></button>
          <button type="button" data-doctor-menu-open="video-consultation"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="7" width="11" height="10" rx="1.5"/><path d="m15 10 5-3v10l-5-3z"/></svg>Video Consulta <b>&rsaquo;</b></button>
          <a class="<?php echo e($activeSection === 'agenda' ? 'is-active' : ''); ?>" href="<?php echo e(route('doctor.dashboard', ['section' => 'agenda'])); ?>"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4m8-4v4M4 10h16"/></svg>Agenda</a>
          <a class="<?php echo e($activeSection === 'patients' ? 'is-active' : ''); ?>" href="<?php echo e(route('doctor.dashboard', ['section' => 'patients'])); ?>"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="10" cy="8" r="3"/><path d="M4 19c.7-3.1 2.6-5 6-5s5.3 1.9 6 5M18 8v6m-3-3h6"/></svg>Pacientes</a>
          <a class="<?php echo e($activeSection === 'clinics' ? 'is-active' : ''); ?>" href="<?php echo e(route('doctor.dashboard', ['section' => 'clinics'])); ?>"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m4 10 8-6 8 6v10H4z"/><path d="M9 20v-6h6v6M8 10h.01m4 0h.01m4 0h.01"/></svg>Mis Consultorios</a>
          <button type="button" data-doctor-menu-open="services"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01"/></svg>Servicios <b>&rsaquo;</b></button>
        </div>

        <div class="doctor-menu-panel" data-doctor-menu-panel="consultation" hidden>
          <button type="button" class="doctor-menu-back" data-doctor-menu-back="main"><span>&larr;</span>Atr&aacute;s</button>
          <strong>Consulta</strong>
          <a href="<?php echo e(route('doctor.dashboard', ['section' => 'agenda', 'new' => 1, 'modality' => 'Presencial'])); ?>">Agendar</a>
          <a href="<?php echo e(route('doctor.dashboard', ['action' => 'consult-now'])); ?>#doctor-encounters">Iniciar Ahora</a>
        </div>

        <div class="doctor-menu-panel" data-doctor-menu-panel="video-consultation" hidden>
          <button type="button" class="doctor-menu-back" data-doctor-menu-back="main"><span>&larr;</span>Atr&aacute;s</button>
          <strong>Video Consulta</strong>
          <a href="<?php echo e(route('doctor.dashboard', ['section' => 'video', 'action' => 'schedule'])); ?>#doctor-video">Agendar</a>
          <a href="<?php echo e(route('doctor.dashboard', ['section' => 'video', 'action' => 'now'])); ?>#doctor-video">Iniciar Ahora</a>
        </div>

        <div class="doctor-menu-panel" data-doctor-menu-panel="services" hidden>
          <button type="button" class="doctor-menu-back" data-doctor-menu-back="main"><span>&larr;</span>Atr&aacute;s</button>
          <strong>Servicios</strong>
          <button type="button" data-doctor-menu-open="nutrition"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4v15m0 0-5-5m5 5 5-5"/></svg>Nutrici&oacute;n <b>&rsaquo;</b></button>
          <a href="<?php echo e(route('doctor.dashboard', ['section' => 'services', 'type' => 'clinical_labs'])); ?>"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3"/><path d="M8 15h8"/></svg>An&aacute;lisis Cl&iacute;nicos</a>
          <button type="button" data-doctor-menu-open="oncology"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.4 2.9 7.7 7 10 4.1-2.3 7-5.6 7-10V6z"/><path d="M12 8v6m-3-3h6"/></svg>Oncolog&iacute;a <b>&rsaquo;</b></button>
        </div>

        <div class="doctor-menu-panel" data-doctor-menu-panel="nutrition" hidden>
          <button type="button" class="doctor-menu-back" data-doctor-menu-back="services"><span>&larr;</span>Atr&aacute;s</button>
          <strong>Nutrici&oacute;n</strong>
          <strong>Nutrici&oacute;n parenteral</strong>
          <a href="<?php echo e(url('/doctor?section=services&type=npt&action=history')); ?>" data-npt-history-link>Ver solicitudes</a>
          <a class="<?php echo e(request('section') === 'services' && request('type') === 'npt' && request('action', 'request') === 'request' ? 'is-active' : ''); ?>" href="<?php echo e(route('doctor.dashboard', ['section' => 'services', 'type' => 'npt', 'action' => 'request'])); ?>">Solicitud de mezcla</a>
        </div>

        <div class="doctor-menu-panel" data-doctor-menu-panel="oncology" hidden>
          <button type="button" class="doctor-menu-back" data-doctor-menu-back="services"><span>&larr;</span>Atr&aacute;s</button>
          <strong>Oncolog&iacute;a</strong>
          <button type="button" data-doctor-menu-open="chemotherapy"><svg class="doctor-menu-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.4 2.9 7.7 7 10 4.1-2.3 7-5.6 7-10V6z"/><path d="M12 8v6m-3-3h6"/></svg>Quimioterapia <b>&rsaquo;</b></button>
        </div>

        <div class="doctor-menu-panel" data-doctor-menu-panel="chemotherapy" hidden>
          <button type="button" class="doctor-menu-back" data-doctor-menu-back="oncology"><span>&larr;</span>Atr&aacute;s</button>
          <strong>Oncolog&iacute;a / Quimioterapia</strong>
          <a href="<?php echo e(route('doctor.dashboard', ['section' => 'services', 'type' => 'chemo', 'action' => 'request'])); ?>">Solicitud de mezcla</a>
          <a href="<?php echo e(route('doctor.dashboard', ['section' => 'services', 'type' => 'chemo', 'action' => 'history'])); ?>">Ver solicitudes</a>
        </div>
      </aside>

      <?php if($activeSection === 'clinics'): ?>
        <section class="doctor-clinics-native-view">
          <?php if(session('status')): ?> <p class="doctor-workspace-notice"><?php echo e(session('status')); ?></p> <?php endif; ?>
          <?php if($errors->any()): ?> <p class="doctor-workspace-error"><?php echo e($errors->first()); ?></p> <?php endif; ?>
          <?php if(request()->filled('manage_schedule')): ?>
            <?php $managedClinic = $clinics->firstWhere('id', (int) request('manage_schedule')); $scheduleMonth = now(); ?>
            <section class="doctor-clinic-schedule">
              <header><a href="<?php echo e(route('doctor.dashboard', ['section' => 'clinics'])); ?>">← Atrás</a><div><strong>Configura tu disponibilidad</strong><small>Define los horarios en los que atenderás pacientes en cada consultorio.</small></div><span></span><em>● Disponibilidad actualizada</em><button>Descartar cambios</button><button class="is-primary">Guardar y publicar</button></header>
              <div class="doctor-schedule-steps"><article><b>1</b><div><strong>Configura tus horarios</strong><small>Define reglas semanales.</small></div></article><article><b>2</b><div><strong>Vista de tu disponibilidad</strong><small>Revisa cómo se verá tu agenda.</small></div></article><article><b>3</b><div><strong>Edita un día</strong><small>Modifica horarios específicos.</small></div></article></div>
              <div class="doctor-schedule-grid"><aside><strong>Reglas semanales</strong><small>Crea rangos de horario para cada día de la semana.</small><label>Año<select><option><?php echo e($scheduleMonth->year); ?></option></select></label><label>Mes<select><option><?php echo e($scheduleMonth->translatedFormat('F')); ?></option></select></label><?php $__currentLoopData = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div class="doctor-schedule-day" data-schedule-day><b><?php echo e(mb_substr($day, 0, 3)); ?></b><span><?php echo e($day); ?></span><em>Sin horario</em><div class="doctor-schedule-range" data-schedule-range hidden><input type="hidden" data-schedule-range-date><header><strong>09:00 - 13:00</strong><span><?php echo e($managedClinic?->name ?? 'Consultorio'); ?> · <i data-schedule-modality-label>Presencial</i></span><button type="button" data-schedule-range-close>Cerrar edición</button><button type="button" data-schedule-range-delete>Eliminar</button></header><label>Fecha seleccionada<input type="date" data-schedule-range-date-input readonly></label><label>Consultorio<select><option><?php echo e($managedClinic?->name ?? 'Consultorio'); ?></option></select></label><div><button type="button" class="is-active" data-schedule-modality="Presencial">♙ Presencial</button><button type="button" data-schedule-modality="Videoconsulta">▣ Videoconsulta</button><button type="button" data-schedule-modality="Ambas">♧ Ambas</button></div><label>Inicio<input type="time" value="09:00"></label><label>Fin<input type="time" value="13:00"></label></div><button type="button" data-schedule-range-toggle>＋ Agregar rango</button></div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></aside><main><div class="doctor-schedule-calendar-head"><button type="button" data-calendar-prev aria-label="Mes anterior">‹</button><select data-calendar-month aria-label="Mes"><?php $__currentLoopData = range(1, 12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($month); ?>" <?php if($month === now()->month): echo 'selected'; endif; ?>><?php echo e(\Carbon\Carbon::create()->month($month)->translatedFormat('F')); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><select data-calendar-year aria-label="Año"><?php $__currentLoopData = range(now()->year - 2, now()->year + 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($year); ?>" <?php if($year === now()->year): echo 'selected'; endif; ?>><?php echo e($year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><strong data-calendar-title><?php echo e(ucfirst($scheduleMonth->translatedFormat('F Y'))); ?></strong><button type="button" data-calendar-today>Hoy</button><button type="button" data-calendar-next aria-label="Mes siguiente">›</button></div><div class="doctor-schedule-calendar" data-schedule-calendar></div><p>ⓘ Haz clic en cualquier día para editar sus horarios.</p></main></div>
              <?php $scheduleRulesJson = $availabilityRules->where('doctor_clinic_id', $managedClinic?->id)->values()->map(fn ($rule) => ['weekday' => $rule->weekday, 'start' => substr($rule->start_time, 0, 5), 'end' => substr($rule->end_time, 0, 5), 'clinic' => $rule->clinic?->name ?? $managedClinic?->name, 'startDate' => $rule->recurrence_start?->toDateString(), 'endDate' => $rule->recurrence_end?->toDateString(), 'months' => $rule->selected_months ?: range(1, 12)])->toJson(); ?>
              <script type="application/json" id="schedule-persisted-rules"><?php echo $scheduleRulesJson; ?></script>
              <form method="post" action="<?php echo e(route('doctor.availability.store')); ?>" data-schedule-persist class="doctor-schedule-save-form">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="doctor_clinic_id" value="<?php echo e($managedClinic?->id); ?>"><input type="hidden" name="weekday" data-persist-weekday value="1"><input type="hidden" name="recurrence_start" data-persist-date value="<?php echo e(now()->toDateString()); ?>"><input type="hidden" name="recurrence_end"><input type="hidden" name="period_mode" value="all">
                <strong>Guardar rango seleccionado</strong><small>Se aplicará al día y periodo que hayas elegido.</small>
                <label>Desde<input type="time" name="start_time" value="09:00" required></label><label>Hasta<input type="time" name="end_time" value="13:00" required></label>
                <label>Tipo de consulta<select name="mode"><option value="in_person">Presencial</option><option value="virtual">Videoconsulta</option><option value="hybrid">Ambas</option></select></label>
                <label>Aplicar a<select name="period_mode" data-persist-period><option value="all">Todos los meses y años</option><option value="year">Un año específico</option><option value="month">Un mes específico</option><option value="range">Un rango de fechas</option></select></label>
                <label data-persist-year>Año<input type="number" name="period_year" value="<?php echo e(now()->year); ?>" min="1900" max="2100"></label><label data-persist-month>Mes<select name="period_month"><?php $__currentLoopData = range(1, 12); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($month); ?>" <?php if($month === now()->month): echo 'selected'; endif; ?>><?php echo e(\Carbon\Carbon::create()->month($month)->translatedFormat('F')); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                <label data-persist-start hidden>Desde<input type="month" name="period_start" value="<?php echo e(now()->format('Y-m')); ?>"></label><label data-persist-end hidden>Hasta<input type="month" name="period_end" value="<?php echo e(now()->format('Y-m')); ?>"></label><button class="is-primary" type="submit">Guardar rango</button>
              </form>
            </section>
          <?php else: ?>
          <?php
            $clinicRent = $clinics->sum(fn ($clinic) => (float) data_get($clinic->metadata, 'rent', 0));
            $clinicCosts = $clinics->sum(fn ($clinic) => collect(['utilities', 'fixed_phone', 'mobile_phone', 'property_tax', 'maintenance', 'staff', 'supplies'])->sum(fn ($key) => (float) data_get($clinic->metadata, $key, 0)));
          ?>
          <section class="doctor-clinic-overview">
            <div class="doctor-clinic-overview-actions"><div><strong>Hola, Doctor</strong><a href="<?php echo e(route('doctor.dashboard', ['section' => 'agenda'])); ?>">Ver calendario general de horarios &rarr;</a></div><span></span><a href="<?php echo e(route('doctor.dashboard')); ?>">Regresar</a><a href="<?php echo e(route('doctor.dashboard')); ?>">Inicio</a><button type="button" data-clinic-create-toggle>+ Nuevo consultorio</button></div>
            <div class="doctor-clinic-totals"><article><small>Renta mensual total</small><strong>$<?php echo e(number_format($clinicRent, 0)); ?></strong><span>En todos los consultorios</span></article><article><small>Gastos mensuales totales</small><strong>$<?php echo e(number_format($clinicCosts, 0)); ?></strong><span>Costos operativos</span></article><article><small>Total mensual</small><strong>$<?php echo e(number_format($clinicRent + $clinicCosts, 0)); ?></strong><span>Renta + gastos</span></article></div>
          </section>
          <form method="post" action="<?php echo e($selectedClinic ? route('doctor.clinics.update', $selectedClinic) : route('doctor.clinics.store')); ?>" class="doctor-clinic-native-form" data-clinic-form <?php if(!($errors->any() || $selectedClinic)): ?> hidden <?php endif; ?>>
            <?php echo csrf_field(); ?> <?php if($selectedClinic): ?> <?php echo method_field('PATCH'); ?> <?php endif; ?>
            <input type="hidden" name="location_type" value="in_person">
            <div class="doctor-clinic-section">
              <div class="doctor-clinic-section-title"><strong>Consultorio</strong><button type="button" data-clinic-toggle="details">Cerrar informaci&oacute;n</button></div>
              <div class="doctor-clinic-section-body" data-clinic-panel="details">
                <label>Nombre del consultorio<input name="name" value="<?php echo e(old('name', $selectedClinic?->name)); ?>" placeholder="Ej. Consultorio privado norte" required></label>
                <div class="wide doctor-clinic-location">
                  <strong>El consultorio est&aacute; dentro de alg&uacute;n hospital o cl&iacute;nica, o es un domicilio particular. En caso de ser un domicilio particular entonces registra Particular</strong>
                  <label><input type="radio" name="metadata[location_scope]" value="particular" <?php if(old('metadata.location_scope', data_get($selectedClinic?->metadata, 'location_scope', 'particular')) === 'particular'): echo 'checked'; endif; ?>> Particular</label>
                  <label><input type="radio" name="metadata[location_scope]" value="facility" <?php if(old('metadata.location_scope', data_get($selectedClinic?->metadata, 'location_scope')) === 'facility'): echo 'checked'; endif; ?>> Hospital o Cl&iacute;nica</label>
                </div>
                <label>Prefijo telef&oacute;nico<input placeholder="Buscar pa&iacute;s o prefijo"><select name="metadata[phone_prefix]"><option value="+52">+52 M&eacute;xico</option></select></label>
                <label>N&uacute;mero de tel&eacute;fono<input name="metadata[phone_number]" value="<?php echo e(old('metadata.phone_number', data_get($selectedClinic?->metadata, 'phone_number'))); ?>" placeholder="N&uacute;mero de tel&eacute;fono"></label>
              </div>
            </div>
            <div class="doctor-clinic-section">
              <div class="doctor-clinic-section-title"><strong>Direcci&oacute;n del Consultorio</strong><button type="button" data-clinic-toggle="address">Cerrar informaci&oacute;n</button></div>
              <div class="doctor-clinic-section-body doctor-clinic-address" data-clinic-panel="address">
                <label>Calle<input name="metadata[street]" value="<?php echo e(old('metadata.street', data_get($selectedClinic?->metadata, 'street'))); ?>" placeholder="Calle"></label>
                <label>N&uacute;mero exterior<input name="metadata[exterior_number]" placeholder="N&uacute;mero exterior"></label>
                <label>N&uacute;mero interior<input name="metadata[interior_number]" placeholder="N&uacute;mero interior"></label>
                <label>Colonia<input name="metadata[neighborhood]" placeholder="Colonia"></label>
                <label>Alcald&iacute;a o Municipio<input name="metadata[municipality]" placeholder="Alcald&iacute;a o municipio"></label>
                <label>Estado<input name="metadata[state]" placeholder="Estado"></label>
                <label>Pa&iacute;s<input name="metadata[country]" value="M&eacute;xico"></label>
                <label>C&oacute;digo Postal<input name="metadata[postal_code]" placeholder="C&oacute;digo Postal"></label>
                <input type="hidden" name="address" value="<?php echo e(old('address', $selectedClinic?->address)); ?>" data-clinic-address-value>
              </div>
            </div>
            <div class="doctor-clinic-section">
              <div class="doctor-clinic-section-title"><strong>Administraci&oacute;n del consultorio</strong><button type="button" data-clinic-toggle="operations">Abrir informaci&oacute;n</button></div>
              <div class="doctor-clinic-section-body doctor-clinic-operations" data-clinic-panel="operations" hidden>
                <?php $__currentLoopData = ['rent' => 'Renta mensual', 'utilities' => 'Servicios y suministros', 'fixed_phone' => 'Telefona fija', 'mobile_phone' => 'Telefona mvil', 'property_tax' => 'Predial', 'maintenance' => 'Mantenimiento', 'staff' => 'Personal', 'supplies' => 'Insumos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <label><?php echo e($label); ?><input type="number" min="0" step="0.01" name="metadata[<?php echo e($field); ?>]" placeholder="0.00"></label>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </div>
            <div class="doctor-clinic-form-actions"><button type="submit"><?php echo e($selectedClinic ? 'Guardar cambios' : 'Guardar consultorio'); ?></button><button type="reset">Limpiar</button></div>
          </form>
          <?php if($clinics->isEmpty()): ?>
            <div class="doctor-clinic-empty"><span></span><strong>A&uacute;n no tienes consultorios registrados</strong><p>Agrega tu primer consultorio para comenzar a administrar horarios y citas.</p><button type="button" data-focus-clinic>Crear primer consultorio</button></div>
          <?php else: ?>
            <div class="doctor-clinic-list">
              <?php $__currentLoopData = $clinics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <article class="doctor-clinic-card"><div class="doctor-clinic-card-main"><span class="doctor-clinic-card-icon">⌂</span><div><small><?php echo e($statusText($clinic->status)); ?> · Consultorio</small><strong><?php echo e($clinic->name); ?></strong><em><?php echo e(data_get($clinic->metadata, 'location_scope') === 'facility' ? 'Hospital o clínica' : 'Particular'); ?></em><button type="button">Ver calendario</button></div></div><dl><div><dt>Dirección</dt><dd><?php echo e($clinic->address ?: 'México'); ?></dd><dt>Atención</dt><dd>Presencial y videoconsulta</dd></div><div><dt>Teléfono</dt><dd><?php echo e(data_get($clinic->metadata, 'phone_number', 'Sin teléfono registrado')); ?></dd><dt>Zona horaria</dt><dd>America/Mexico_City</dd></div></dl><div class="doctor-clinic-card-metrics"><div><b>Horarios activos</b><strong><?php echo e($clinic->availabilityRules->count()); ?></strong><small>slots configurados</small></div><div><b>Citas hoy</b><strong>0</strong><small>consultas agendadas</small></div><div><b>Ocupación hoy</b><strong>0%</strong><small>Disponible</small></div></div><div class="doctor-clinic-menu"><button type="button" aria-label="Opciones de <?php echo e($clinic->name); ?>" data-clinic-menu-toggle>⋮</button><div data-clinic-menu hidden><a href="<?php echo e(route('doctor.dashboard', ['section' => 'clinics', 'edit_clinic' => $clinic->id])); ?>">Editar consultorio</a><a href="<?php echo e(route('doctor.dashboard', ['section' => 'clinics', 'manage_schedule' => $clinic->id])); ?>">Administrar horarios</a><button type="button">Ver consultas</button><button type="button">Reportes</button><form method="post" action="<?php echo e(route('doctor.clinics.destroy', $clinic)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button type="submit">Eliminar consultorio</button></form></div></div></article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php endif; ?>
          <?php endif; ?>
        </section>
      <?php endif; ?>

      <?php if($activeSection === 'agenda'): ?>
        <?php
          $agendaAnchor = now();
          $agendaWeekStart = $agendaAnchor->copy()->startOfWeek();
          $agendaWeekEnd = $agendaWeekStart->copy()->endOfWeek();
          $agendaDays = collect(range(0, 6))->map(fn ($offset) => $agendaWeekStart->copy()->addDays($offset));
          $agendaMonthStart = $agendaAnchor->copy()->startOfMonth();
          $agendaCalendarStart = $agendaMonthStart->copy()->startOfWeek();
          $agendaMonthDays = collect(range(0, 41))->map(fn ($offset) => $agendaCalendarStart->copy()->addDays($offset));
          $agendaDayNames = ['Lun', 'Mar', 'Mi', 'Jue', 'Vie', 'Sb', 'Dom'];
          $agendaMonthNames = [1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'];
          $agendaAppointments = $appointments->filter(fn ($appointment) => $appointment->starts_at && $appointment->starts_at->betweenIncluded($agendaWeekStart, $agendaWeekEnd));
          // La agenda de demostración conserva una cuadrícula útil incluso cuando
          // la cuenta todavía no tiene citas reales en la semana actual.
          if ($agendaAppointments->isEmpty()) {
            $agendaAppointments = collect([
              (object) ['starts_at' => $agendaWeekStart->copy()->addDay()->setTime(9, 0), 'ends_at' => $agendaWeekStart->copy()->addDay()->setTime(9, 30), 'patient' => (object) ['full_name' => 'María González'], 'reason' => 'Consulta de seguimiento', 'modality' => 'Presencial', 'status' => 'scheduled', 'metadata' => []],
              (object) ['starts_at' => $agendaWeekStart->copy()->addDays(2)->setTime(11, 0), 'ends_at' => $agendaWeekStart->copy()->addDays(2)->setTime(11, 30), 'patient' => (object) ['full_name' => 'Carlos Ramírez'], 'reason' => 'Valoración inicial', 'modality' => 'Video llamada', 'status' => 'scheduled', 'metadata' => []],
              (object) ['starts_at' => $agendaWeekStart->copy()->addDays(4)->setTime(16, 0), 'ends_at' => $agendaWeekStart->copy()->addDays(4)->setTime(17, 0), 'patient' => (object) ['full_name' => 'Lucía Hernández'], 'reason' => 'Revisión de resultados', 'modality' => 'Presencial', 'status' => 'pending', 'metadata' => []],
            ]);
          }
        ?>
        <section class="doctor-agenda-native" data-agenda-root>
          <div class="doctor-agenda-title">
            <div><strong>Calendario</strong><span>Visualiza y gestiona tus citas y actividades</span><small><?php echo e($agendaAppointments->count()); ?> citas visibles</small></div>
            <div><button type="button" class="is-primary" data-agenda-new-toggle>+ Nueva cita</button><a href="<?php echo e(route('doctor.dashboard')); ?>" aria-label="Inicio"></a></div>
          </div>
          <div class="doctor-agenda-toolbar">
            <button type="button" class="is-active" data-agenda-kind=""><svg viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="5"/></svg>Todas</button>
            <button type="button" data-agenda-kind="Presencial"><svg viewBox="0 0 16 16" aria-hidden="true"><path d="M3 13V5l5-3 5 3v8M6 13V9h4v4"/></svg>Presenciales</button>
            <button type="button" data-agenda-kind="Video llamada"><svg viewBox="0 0 16 16" aria-hidden="true"><rect x="2" y="4" width="8" height="8" rx="1"/><path d="m10 7 4-2v6l-4-2z"/></svg>Videoconsultas</button>
            <?php $__currentLoopData = $clinics->take(4); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <button type="button" data-agenda-clinic="<?php echo e($clinic->id); ?>"><i style="--dot: <?php echo e(['#15bdb5','#9a4bd3','#ee9700','#2d7fe8'][$index] ?? '#15bdb5'); ?>"></i><?php echo e($clinic->name); ?></button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </div>
          <div class="doctor-agenda-new" data-agenda-new hidden>
            <form method="post" action="<?php echo e(route('doctor.appointments.store')); ?>">
              <?php echo csrf_field(); ?>
              <label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Consultorio<select name="doctor_clinic_id" required><option value="">Seleccionar consultorio</option><?php $__currentLoopData = $clinics->where('status', 'active'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($clinic->id); ?>"><?php echo e($clinic->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Fecha y hora<input type="datetime-local" name="starts_at" required></label>
              <label>Duraci&oacute;n<select name="duration"><option value="20">20 minutos</option><option value="30" selected>30 minutos</option><option value="45">45 minutos</option><option value="60">60 minutos</option></select></label>
              <label>Modalidad<select name="modality"><option>Presencial</option><option>Video llamada</option></select></label>
              <label>Motivo<input name="reason" placeholder="Motivo de la consulta"></label>
              <div><button type="submit">Guardar cita</button><button type="button" data-agenda-new-close>Cerrar</button></div>
            </form>
          </div>
          <div class="doctor-agenda-layout">
            <aside>
              <div class="doctor-agenda-mini">
                <header><button type="button"></button><strong><?php echo e($agendaMonthNames[$agendaAnchor->month]); ?> <b><?php echo e($agendaAnchor->year); ?></b></strong><button type="button"></button></header>
                <div class="week"><?php $__currentLoopData = ['L','M','M','J','V','S','D']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><b><?php echo e($label); ?></b><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
                <div class="days">
                  <?php $__currentLoopData = $agendaMonthDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span class="<?php echo e(!$day->isSameMonth($agendaAnchor) ? 'is-out' : ''); ?> <?php echo e($day->isSameDay($agendaAnchor) ? 'is-today' : ''); ?>"><?php echo e($day->day); ?></span>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </div>
              <div class="doctor-agenda-filters">
                <strong>Filtros</strong>
                <input type="search" placeholder="Buscar paciente, consultorio o modalidad" data-agenda-search>
                <label>Consultorio<select data-agenda-clinic-filter><option value="">Todos los consultorios</option><?php $__currentLoopData = $clinics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($clinic->id); ?>"><?php echo e($clinic->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                <label>Modalidad<select data-agenda-modality-filter><option value="">Todas</option><option>Presencial</option><option>Video llamada</option></select></label>
                <label>Estado<select data-agenda-status-filter><option value="">Todos</option><?php $__currentLoopData = ['scheduled'=>'Programada','pending'=>'Pendiente','completed'=>'Completada','cancelled'=>'Cancelada']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>"><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                <label>Especialidad / Servicio<select><option>Todos</option><option><?php echo e($doctor->specialty ?: 'Consulta general'); ?></option></select></label>
                <button type="button" data-agenda-clear>Limpiar filtros</button>
              </div>
            </aside>
            <div class="doctor-agenda-week">
              <div class="doctor-agenda-week-head">
                <span>Hora</span>
                <?php $__currentLoopData = $agendaDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <div class="<?php echo e($day->isSameDay($agendaAnchor) ? 'is-today' : ''); ?>"><b><?php echo e($agendaDayNames[$loop->index]); ?></b><strong><?php echo e($day->day); ?></strong></div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
              <div class="doctor-agenda-time-grid">
                <?php $__currentLoopData = range(8, 18); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hour): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <span class="time-label" style="--row: <?php echo e($hour - 8); ?>"><?php echo e(sprintf('%02d:00', $hour)); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php $__currentLoopData = range(0, 6); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayIndex): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><i class="day-line" style="--day: <?php echo e($dayIndex); ?>"></i><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php $__currentLoopData = $agendaAppointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <?php
                    $dayIndex = $agendaWeekStart->diffInDays($appointment->starts_at->copy()->startOfDay());
                    $minutesFromEight = (($appointment->starts_at->hour - 8) * 60) + $appointment->starts_at->minute;
                    $duration = max(30, $appointment->ends_at ? $appointment->starts_at->diffInMinutes($appointment->ends_at) : 30);
                    $clinicId = data_get($appointment->metadata, 'doctor_clinic_id', '');
                    $clinicName = $clinics->firstWhere('id', (int) $clinicId)?->name ?? $appointment->medicalUnit?->name ?? 'Privada';
                  ?>
                  <article class="doctor-agenda-event" style="--day: <?php echo e($dayIndex); ?>; --start: <?php echo e(max(0, $minutesFromEight)); ?>; --duration: <?php echo e($duration); ?>" data-agenda-event data-modality="<?php echo e($appointment->modality); ?>" data-status="<?php echo e($appointment->status); ?>" data-clinic="<?php echo e($clinicId); ?>" data-search="<?php echo e(mb_strtolower(($appointment->patient?->full_name ?? '').' '.$clinicName.' '.$appointment->modality)); ?>">
                    <strong><?php echo e($appointment->starts_at->format('H:i')); ?> - <?php echo e($appointment->ends_at?->format('H:i') ?? $appointment->starts_at->copy()->addMinutes(30)->format('H:i')); ?></strong>
                    <b><?php echo e($appointment->patient?->full_name ?? 'Paciente pendiente'); ?></b>
                    <span><?php echo e($appointment->reason ?: ($appointment->specialty ?: 'Consulta')); ?></span>
                    <small><?php echo e($clinicName); ?> · <?php echo e($appointment->modality ?: 'Presencial'); ?></small>
                    <em><?php echo e(in_array($appointment->status, ['scheduled','created']) ? 'Confirmada' : $statusText($appointment->status)); ?></em>
                  </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
            </div>
          </div>
        </section>
      <?php endif; ?>

      <section id="clinical-query" class="doctor-assistant-native-intro">
        <span class="doctor-assistant-native-spark">+</span>
        <h1>&iquest;En qu&eacute; puedo ayudarte hoy?</h1>
        <p>Soy tu asistente clinico con IA. Puedo ayudarte a explorar informacion, encontrar evidencia y resumir notas.</p>
        <div class="doctor-assistant-native-suggestions">
          <button type="button">Resumir historia clinica</button>
          <button type="button">Buscar guias clinicas</button>
          <button type="button">Interacciones de farmacos</button>
          <button type="button">Sugerir diagnostico diferencial</button>
        </div>
        <button class="doctor-assistant-native-more" type="button">Ver mas sugerencias</button>
      </section>

      <?php if($activeSection !== 'home'): ?>
      <section class="doctor-assistant-native-panels">
        <article id="doctor-agenda" class="doctor-assistant-native-card">
          <div class="doctor-assistant-native-heading">
            <h2>Agenda</h2>
            <span><?php echo e($appointments->count()); ?></span>
          </div>
          <div class="doctor-assistant-native-list">
            <?php $__empty_1 = true; $__currentLoopData = $appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <div>
                <strong><?php echo e($appointment->starts_at?->format('d/m/Y H:i') ?? 'Fecha pendiente'); ?></strong>
                <span><?php echo e($appointment->patient?->full_name ?? 'Paciente pendiente'); ?></span>
                <span><?php echo e($appointment->specialty ?? $doctor->specialty ?? 'Consulta'); ?> / <?php echo e($appointment->modality ?? 'modalidad pendiente'); ?></span>
                <em><?php echo e($statusText($appointment->status)); ?></em>
                <?php if($appointment->patient_id && ! in_array($appointment->status, ['completed', 'cancelled'], true)): ?>
                  <form method="post" action="<?php echo e(route('doctor.encounters.store')); ?>" class="doctor-appointment-action">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="patient_id" value="<?php echo e($appointment->patient_id); ?>">
                    <input type="hidden" name="appointment_id" value="<?php echo e($appointment->id); ?>">
                    <input type="hidden" name="reason" value="<?php echo e($appointment->reason); ?>">
                    <button type="submit">Abrir consulta</button>
                  </form>
                  <form method="post" action="<?php echo e(route('doctor.appointments.status', $appointment)); ?>" class="doctor-appointment-action">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="status" value="cancelled">
                    <button type="submit">Cancelar cita</button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>No hay citas registradas.</p>
            <?php endif; ?>
          </div>
        </article>

        <article id="doctor-services" class="doctor-assistant-native-card">
          <div class="doctor-assistant-native-heading">
            <h2>Servicios contratados</h2>
            <span><?php echo e($services->count()); ?></span>
          </div>
          <div class="doctor-assistant-native-list">
            <?php $__empty_1 = true; $__currentLoopData = $services; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <div>
                <strong><?php echo e($service['name']); ?></strong>
                <span><?php echo e($service['category']); ?> / <?php echo e($service['specialty']); ?></span>
                <span><?php echo e($service['context']); ?>  <?php echo e($service['starts_at']?->format('d/m/Y') ?? 'Sin vigencia definida'); ?><?php if($service['ends_at']): ?> - <?php echo e($service['ends_at']->format('d/m/Y')); ?><?php endif; ?></span>
                <em><?php echo e($statusText($service['status'])); ?></em>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>Sin servicios operativos asignados.</p>
            <?php endif; ?>
          </div>
        </article>

        <article id="doctor-units" class="doctor-assistant-native-card">
          <div class="doctor-assistant-native-heading">
            <h2>Perfil medico</h2>
            <span><?php echo e($statusText($doctor->status)); ?></span>
          </div>
          <dl class="doctor-assistant-native-profile">
            <div><dt>Cedula</dt><dd><?php echo e($doctor->professional_license ?? 'Sin cedula'); ?></dd></div>
            <div><dt>Especialidad</dt><dd><?php echo e($doctor->specialty ?? 'Especialidad no registrada'); ?></dd></div>
            <div><dt>Servicio</dt><dd><?php echo e($doctor->service_name ?? 'Sin servicio principal'); ?></dd></div>
            <div>
              <dt>Unidad</dt>
              <dd>
                <?php echo e($doctor->medicalUnit?->name ?? 'Sin unidad'); ?>

                <?php if($doctor->medicalUnit?->institution): ?>
                  <span><?php echo e($doctor->medicalUnit->institution->name); ?></span>
                <?php endif; ?>
              </dd>
            </div>
          </dl>
        </article>
      </section>

      <section id="doctor-patients" class="doctor-assistant-native-table-card">
        <div class="doctor-patients-searchbar">
          <span aria-hidden="true">?</span>
          <input type="search" placeholder="Buscar paciente, ID, diagnstico o historial" data-patient-global-filter>
          <a href="<?php echo e(route('doctor.dashboard')); ?>" aria-label="Inicio"></a>
        </div>
        <div class="doctor-patients-titlebar">
          <div><strong>Catlogo de pacientes</strong><small>Administra altas, edicin y enlace con usuarios de la plataforma.</small></div>
          <button type="button" data-patient-create-toggle>Nuevo Paciente</button>
        </div>
        <div class="doctor-patients-create" data-patient-create <?php if(!($selectedPatient || $errors->any())): ?> hidden <?php endif; ?>>
          <form method="post" action="<?php echo e($selectedPatient ? route('doctor.patients.update', $selectedPatient) : route('doctor.patients.store')); ?>" class="doctor-patient-create-form">
            <?php echo csrf_field(); ?> <?php if($selectedPatient): ?> <?php echo method_field('PATCH'); ?> <?php endif; ?>
            <label>Usuario de la plataforma<input name="platform_number" value="<?php echo e(old('platform_number', $selectedPatient?->platform_number)); ?>" placeholder="Escribe el n&uacute;mero de usuario de plataforma"></label>
            <label>Nombre<input name="first_name" value="<?php echo e(old('first_name', $selectedPatient?->first_name ?: ($selectedPatient ? str($selectedPatient->full_name)->before(' ') : ''))); ?>" placeholder="Nombre del paciente" required></label>
            <label>Apellido<input name="last_name" value="<?php echo e(old('last_name', $selectedPatient?->last_name ?: ($selectedPatient ? str($selectedPatient->full_name)->after(' ') : ''))); ?>" placeholder="Apellido del paciente" required></label>
            <label>CURP<input name="curp" value="<?php echo e(old('curp', $selectedPatient?->curp)); ?>" placeholder="CURP" maxlength="18"></label>
            <label>Fecha de nacimiento<input type="date" name="birth_date" value="<?php echo e(old('birth_date', $selectedPatient?->birth_date?->toDateString())); ?>"></label>
            <label>Sexo<select name="sex"><option value="">Sin dato</option><option <?php if(old('sex', $selectedPatient?->sex) === 'Femenino'): echo 'selected'; endif; ?>>Femenino</option><option <?php if(old('sex', $selectedPatient?->sex) === 'Masculino'): echo 'selected'; endif; ?>>Masculino</option><option <?php if(old('sex', $selectedPatient?->sex) === 'Otro'): echo 'selected'; endif; ?>>Otro</option></select></label>
            <label>Ubicacin
              <select name="patient_location">
                <option value="private" <?php if(old('patient_location', data_get($selectedPatient?->metadata, 'patient_location', 'private')) === 'private'): echo 'selected'; endif; ?>>Consultorio privado</option>
                <?php $__currentLoopData = $clinics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="clinic:<?php echo e($clinic->id); ?>" <?php if(old('patient_location', data_get($selectedPatient?->metadata, 'patient_location')) === 'clinic:'.$clinic->id): echo 'selected'; endif; ?>><?php echo e($clinic->name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </label>
            <label>Padecimientos familiares<input name="family_conditions" value="<?php echo e(old('family_conditions', data_get($selectedPatient?->metadata, 'family_conditions'))); ?>" placeholder="Padecimientos familiares"></label>
            <label>Estatus<select name="status"><option value="active" <?php if(old('status', $selectedPatient?->status ?? 'active') === 'active'): echo 'selected'; endif; ?>>Activo</option><option value="inactive" <?php if(old('status', $selectedPatient?->status) === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
            <div class="doctor-patient-create-actions">
              <button type="button" <?php if(!$selectedPatient): echo 'disabled'; endif; ?>>Enlazar</button>
              <button type="submit"><?php echo e($selectedPatient ? 'Guardar paciente' : 'Guardar paciente'); ?></button>
              <button type="reset">Limpiar</button>
              <a href="<?php echo e(route('doctor.dashboard', ['section' => 'patients'])); ?>#doctor-patients">Cerrar</a>
            </div>
          </form>
        </div>
        <div class="doctor-patients-table-heading">
          <div><strong>Pacientes - Agenda Privada</strong><small><?php echo e($patients->count()); ?> pacientes visibles</small></div>
        </div>
        <div class="doctor-assistant-native-table-scroll">
          <table class="doctor-assistant-native-table doctor-patients-table" data-patients-table>
            <thead>
              <tr class="doctor-patients-filters">
                <th><input type="search" placeholder="Filtrar ID" data-patient-filter="0"></th>
                <th><input type="search" placeholder="Filtrar paciente" data-patient-filter="1"></th>
                <th><input type="search" placeholder="Usuario" data-patient-filter="2"></th>
                <th><input type="search" placeholder="Nacimiento" data-patient-filter="3"></th>
                <th><input type="search" placeholder="Sexo / edad" data-patient-filter="4"></th>
                <th><select data-patient-filter="5"><option value="">Todos</option><option value="Agendada">Agendada</option><option value="Activo">Activo</option><option value="Inactivo">Inactivo</option></select></th>
                <th><input type="search" placeholder="Unidad" data-patient-filter="6"></th>
                <th><input type="search" placeholder="Historial" data-patient-filter="7"></th>
                <th><input type="search" placeholder="&uacute;ltima atenci&oacute;n" data-patient-filter="8"></th>
                <th><input type="search" placeholder="Prxima cita" data-patient-filter="9"></th>
                <th><button type="button" data-patient-filter-clear>Limpiar</button></th>
              </tr>
              <tr>
                <th>ID</th>
                <th>Paciente</th>
                <th>Usuario plataforma</th>
                <th>Nacimiento</th>
                <th>Sexo / edad</th>
                <th>Estatus</th>
                <th>Unidad</th>
                <th>Historial</th>
                <th>&uacute;ltima atenci&oacute;n</th>
                <th>Prxima cita</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                  $now = now();
                  $nextAppointment = $patient->appointments
                    ->filter(fn ($appointment) => $appointment->starts_at && $appointment->starts_at->gte($now) && !in_array($appointment->status, ['cancelled', 'completed'], true))
                    ->sortBy('starts_at')
                    ->first();
                  $lastAppointment = $patient->appointments
                    ->filter(fn ($appointment) => $appointment->starts_at && $appointment->starts_at->lt($now))
                    ->sortByDesc('starts_at')
                    ->first();
                  $patientStatus = $nextAppointment ? 'Agendada' : ($patient->status === 'inactive' ? 'Inactivo' : 'Activo');
                  $patientUnit = $nextAppointment?->medicalUnit?->name
                    ?? $lastAppointment?->medicalUnit?->name
                    ?? 'Privada';
                  $patientAge = $patient->birth_date?->age;
                ?>
                <tr>
                  <td><strong><?php echo e($patient->platform_number ?? $patient->id); ?></strong></td>
                  <td>
                    <strong><?php echo e($patient->full_name); ?></strong>
                    <span>EXP-<?php echo e($patient->platform_number ?? str_pad((string) $patient->id, 10, '0', STR_PAD_LEFT)); ?></span>
                  </td>
                  <td><?php echo e($patient->platform_number ?? 'Sin usuario'); ?></td>
                  <td><?php echo e($patient->birth_date?->format('Y-m-d') ?? 'Sin dato'); ?></td>
                  <td><?php echo e($patient->sex ?? 'Sin dato'); ?><?php echo e($patientAge !== null ? ' '.$patientAge.' aos' : ''); ?></td>
                  <td><span class="doctor-patient-status doctor-patient-status-<?php echo e(strtolower($patientStatus)); ?>"><?php echo e($patientStatus); ?></span></td>
                  <td><?php echo e($patientUnit); ?></td>
                  <td><?php echo e($patient->appointments->count()); ?> citas - <?php echo e($patient->doctor_encounters_count); ?> consultas - <?php echo e($patient->appointments->where('modality', 'video')->count()); ?> video consultas</td>
                  <td><?php echo e($lastAppointment ? 'Consulta '.$lastAppointment->starts_at->format('d M Y H:i') : 'Sin atenci&oacute;n registrada'); ?></td>
                  <td><?php echo e($nextAppointment ? $nextAppointment->starts_at->format('d M Y H:i') : 'Sin cita programada'); ?></td>
                  <td><a class="doctor-patient-edit" href="<?php echo e(route('doctor.dashboard', ['section' => 'patients', 'patient' => $patient->id]).'#doctor-patients'); ?>">Editar</a></td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                  <td colspan="11">No hay pacientes vinculados a este mdico.</td>
                </tr>
              <?php endif; ?>
              <tr data-patient-empty-filter hidden><td colspan="11">No hay pacientes para los filtros actuales.</td></tr>
            </tbody>
          </table>
        </div>
        <?php if($selectedPatient): ?>
          <article class="doctor-patient-expedient" hidden>
            <div class="doctor-assistant-native-heading">
              <div><h2>Expediente clnico</h2><p><?php echo e($selectedPatient->full_name); ?>  <?php echo e($selectedPatient->platform_number); ?></p></div>
              <a class="doctor-workspace-secondary" href="<?php echo e(route('doctor.dashboard', ['section' => 'patients']).'#doctor-patients'); ?>">Regresar</a>
            </div>
            <form method="post" action="<?php echo e(route('doctor.patients.update', $selectedPatient)); ?>" class="doctor-workspace-form">
              <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
              <label>Usuario de plataforma<input name="platform_number" value="<?php echo e($selectedPatient->platform_number); ?>"></label>
              <label>Nombre<input name="first_name" value="<?php echo e($selectedPatient->first_name ?: str($selectedPatient->full_name)->before(' ')); ?>" required></label>
              <label>Apellido<input name="last_name" value="<?php echo e($selectedPatient->last_name ?: str($selectedPatient->full_name)->after(' ')); ?>" required></label>
              <label>CURP<input name="curp" value="<?php echo e($selectedPatient->curp); ?>" maxlength="18"></label>
              <label>Nacimiento<input type="date" name="birth_date" value="<?php echo e($selectedPatient->birth_date?->toDateString()); ?>"></label>
              <label>Sexo<select name="sex"><option value="">Sin dato</option><?php $__currentLoopData = ['Femenino','Masculino','Otro']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sex): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option <?php if($selectedPatient->sex === $sex): echo 'selected'; endif; ?>><?php echo e($sex); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Telfono<input name="phone" value="<?php echo e($selectedPatient->phone); ?>"></label>
              <label>Correo<input type="email" name="email" value="<?php echo e($selectedPatient->email); ?>"></label>
              <label class="wide">Domicilio<input name="address" value="<?php echo e($selectedPatient->address); ?>"></label>
              <label>Estatus<select name="status"><option value="active" <?php if($selectedPatient->status === 'active'): echo 'selected'; endif; ?>>Activo</option><option value="inactive" <?php if($selectedPatient->status === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
              <label class="wide">Antecedentes y observaciones<textarea name="general_observations" rows="3"><?php echo e($selectedPatient->general_observations); ?></textarea></label>
              <button type="submit">Guardar cambios</button>
            </form>
            <div class="doctor-patient-history-grid">
              <section><h3>Historial de consultas</h3><?php $__empty_1 = true; $__currentLoopData = $selectedPatient->clinicalEncounters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $encounter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><p><strong><?php echo e($encounter->started_at?->format('d/m/Y H:i')); ?></strong><span><?php echo e($encounter->reason ?: 'Consulta'); ?>  <?php echo e($encounter->assessment ?: $statusText($encounter->status)); ?></span></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p>Sin consultas.</p><?php endif; ?></section>
              <section><h3>Recetas</h3><?php $__empty_1 = true; $__currentLoopData = $selectedPatient->prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prescription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><p><strong><?php echo e($prescription->code); ?></strong><span><?php echo e($prescription->items->pluck('medication_name')->implode(', ') ?: 'Sin medicamentos'); ?></span></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p>Sin recetas.</p><?php endif; ?></section>
              <section><h3>Citas</h3><?php $__empty_1 = true; $__currentLoopData = $selectedPatient->appointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><p><strong><?php echo e($appointment->starts_at?->format('d/m/Y H:i')); ?></strong><span><?php echo e($appointment->reason ?: 'Sin motivo'); ?>  <?php echo e($statusText($appointment->status)); ?></span></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p>Sin citas.</p><?php endif; ?></section>
              <section><h3>Notas cl&iacute;nicas</h3><?php $__empty_1 = true; $__currentLoopData = $selectedPatient->clinicalRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><p><strong><?php echo e($record->title); ?></strong><span><?php echo e($record->summary); ?></span></p><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p>Sin notas.</p><?php endif; ?></section>
            </div>
            <details class="doctor-inline-editor">
              <summary>Agregar nota al expediente</summary>
              <form method="post" action="<?php echo e(route('doctor.patients.records.store', $selectedPatient)); ?>" class="doctor-workspace-form">
                <?php echo csrf_field(); ?>
                <label>Tipo<select name="record_type" required><option value="clinical_note">Nota cl&iacute;nica</option><option value="diagnosis">Diagn&oacute;stico</option><option value="follow_up">Seguimiento</option><option value="study">Estudio</option></select></label>
                <label>Fecha<input type="datetime-local" name="recorded_at" value="<?php echo e(now()->format('Y-m-d\\TH:i')); ?>" required></label>
                <label class="wide">Ttulo<input name="title" required></label>
                <label class="wide">Resumen<textarea name="summary" rows="4" required></textarea></label>
                <button type="submit">Guardar nota</button>
              </form>
            </details>
          </article>
        <?php endif; ?>
      </section>

      <section id="doctor-prescriptions" class="doctor-assistant-native-table-card doctor-workspace-card">
        <div class="doctor-assistant-native-heading">
          <div><h2>Recetas médicas</h2><p>Emite la receta y envía los medicamentos al surtimiento de Farmacia Externa.</p></div>
          <span><?php echo e($prescriptions->count() + $clinicalRecords->count()); ?></span>
        </div>
        <details class="doctor-prescription-editor" <?php if(request('section') === 'prescriptions' || $errors->has('items')): ?> open <?php endif; ?>>
          <summary>Nueva receta</summary>
          <form method="post" action="<?php echo e(route('doctor.prescriptions.store')); ?>" class="doctor-workspace-form doctor-prescription-form" data-prescription-form>
            <?php echo csrf_field(); ?>
            <div class="doctor-prescription-basics">
              <label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>" <?php if(old('patient_id') == $patient->id): echo 'selected'; endif; ?>><?php echo e($patient->full_name); ?> - <?php echo e($patient->platform_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                            <label>Fecha<input type="date" name="issued_at" value="<?php echo e(old('issued_at', now()->toDateString())); ?>" required></label>
              <label>Consulta vinculada<select name="clinical_encounter_id"><option value="">Sin vincular</option><?php $__currentLoopData = $encounters->where('status', 'in_progress'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $encounter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($encounter->id); ?>" <?php if(old('clinical_encounter_id') == $encounter->id): echo 'selected'; endif; ?>><?php echo e($encounter->patient?->full_name); ?> - <?php echo e($encounter->started_at?->format('d/m/Y H:i')); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label class="wide">Diagnóstico<input name="diagnosis" value="<?php echo e(old('diagnosis')); ?>" required></label>
              <label class="wide">Notas generales<textarea name="notes" rows="2"><?php echo e(old('notes')); ?></textarea></label>
            </div>
            <div class="doctor-prescription-table-scroll">
              <table class="doctor-prescription-table">
                <thead><tr><th>No.</th><th>Clave CNIS</th><th>Medicamento</th><th>Dosis</th><th>Presentación</th><th>Vía</th><th>Frecuencia</th><th>Duración</th><th>Cantidad</th><th>Indicaciones</th><th></th></tr></thead>
                <tbody data-prescription-items></tbody>
              </table>
            </div>
            <div class="doctor-prescription-actions"><button type="button" class="doctor-workspace-secondary" data-add-medication>+ Medicamento</button><button type="submit">Guardar receta</button></div>
          </form>
        </details>
        <div class="doctor-assistant-native-columns">
          <div class="doctor-assistant-native-list">
            <?php $__empty_1 = true; $__currentLoopData = $prescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prescription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <div class="doctor-prescription-record">
                <strong><?php echo e($prescription->code ?? 'Sin folio'); ?></strong>
                <span><?php echo e($prescription->patient?->full_name ?? 'Sin paciente'); ?> / <?php echo e($prescription->issued_at?->format('d/m/Y') ?? 'Sin fecha'); ?></span>
                <span><?php echo e($prescription->items->pluck('medication_name')->filter()->implode(', ') ?: 'Sin medicamentos'); ?></span>
                <em><?php echo e($statusText($prescription->status)); ?></em>
                <details class="doctor-inline-editor">
                  <summary>Ver y editar receta</summary>
                  <form method="post" action="<?php echo e(route('doctor.prescriptions.update', $prescription)); ?>" class="doctor-prescription-update-form">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <label>Fecha<input type="date" name="issued_at" value="<?php echo e($prescription->issued_at?->toDateString()); ?>" required></label>
                    <label>Diagn&oacute;stico<input name="diagnosis" value="<?php echo e(data_get($prescription->metadata, 'diagnosis')); ?>" required></label>
                    <label class="wide">Notas<textarea name="notes" rows="2"><?php echo e($prescription->notes); ?></textarea></label>
                    <div class="doctor-prescription-table-scroll wide">
                      <table class="doctor-prescription-table"><thead><tr><th>CNIS</th><th>Medicamento</th><th>Dosis</th><th>Presentaci&oacute;n</th><th>V&iacute;a</th><th>Frecuencia</th><th>Duraci&oacute;n</th><th>Cantidad</th><th>Indicaciones</th></tr></thead><tbody>
                        <?php $__currentLoopData = $prescription->items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $itemIndex => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <tr>
                            <td><input name="items[<?php echo e($itemIndex); ?>][cnis]" value="<?php echo e(data_get($item->metadata, 'cnis')); ?>"></td>
                            <td><input name="items[<?php echo e($itemIndex); ?>][medication_name]" value="<?php echo e($item->medication_name); ?>" required></td>
                            <td><input name="items[<?php echo e($itemIndex); ?>][dose]" value="<?php echo e($item->dose); ?>"></td>
                            <td><input name="items[<?php echo e($itemIndex); ?>][presentation]" value="<?php echo e(data_get($item->metadata, 'presentation')); ?>"></td>
                            <td><input name="items[<?php echo e($itemIndex); ?>][route]" value="<?php echo e(data_get($item->metadata, 'route')); ?>"></td>
                            <td><input name="items[<?php echo e($itemIndex); ?>][frequency]" value="<?php echo e($item->frequency); ?>"></td>
                            <td><input name="items[<?php echo e($itemIndex); ?>][duration]" value="<?php echo e($item->duration); ?>"></td>
                            <td><input type="number" min="1" name="items[<?php echo e($itemIndex); ?>][quantity]" value="<?php echo e(data_get($item->metadata, 'quantity', 1)); ?>" required></td>
                            <td><textarea name="items[<?php echo e($itemIndex); ?>][instructions]" rows="2"><?php echo e($item->instructions); ?></textarea></td>
                          </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </tbody></table>
                    </div>
                    <?php if($prescription->status !== 'filled'): ?><button type="submit">Guardar cambios</button><?php else: ?> <p class="wide">La receta ya fue surtida y se conserva como documento clnico.</p><?php endif; ?>
                  </form>
                </details>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>No hay recetas emitidas.</p>
            <?php endif; ?>
          </div>
          <div class="doctor-assistant-native-list">
            <?php $__empty_1 = true; $__currentLoopData = $clinicalRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <div>
                <strong><?php echo e($record->title ?? 'Nota clinica'); ?></strong>
                <span><?php echo e($record->patient?->full_name ?? 'Sin paciente'); ?> / <?php echo e($record->recorded_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></span>
                <p><?php echo e($record->summary ?? 'Sin resumen capturado.'); ?></p>
              </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <p>No hay notas clinicas capturadas.</p>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <?php if(request('section') === 'services' && request('type') === 'npt' && request('action') === 'history'): ?>
        <?php $nptRequests = $providerRequests->where('request_type', 'npt'); ?>
        <section class="doctor-npt-history">
          <header><div><strong>Historial de Solicitudes</strong><small>Servicio médico integral</small></div><a href="<?php echo e(route('doctor.dashboard', ['section' => 'services', 'type' => 'npt', 'action' => 'request'])); ?>">← Atrás</a></header>
          <div class="doctor-npt-history-title"><div><strong>Solicitudes — Nutrición Parenteral PRODIFEM</strong><small>Historial de solicitudes enviadas por hospitales</small></div><b><?php echo e($nptRequests->whereIn('status', ['pending', 'requested'])->count()); ?> pendientes</b></div>
          <div class="doctor-npt-history-table"><table><thead><tr><th>Folio</th><th>Fecha solicitud</th><th>Paciente</th><th>Registro</th><th>Unidad</th><th>Volumen ml</th><th>Médico</th><th>Autorizaciones</th><th>Mezcla</th><th>Visualizar solicitud</th><th>Estado</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $nptRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $request): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($request->external_id); ?></td><td><?php echo e($request->requested_at?->format('d/m/Y H:i')); ?></td><td><?php echo e($request->patient?->full_name); ?></td><td><?php echo e($request->patient?->platform_number); ?></td><td>Privada</td><td><?php echo e(data_get($request->payload, 'clinical_format.total_volume', '—')); ?></td><td><?php echo e($doctor->full_name); ?></td><td>Operativa: <?php echo e($statusText(data_get($request->payload, 'authorizations.operational'))); ?></td><td><?php echo e(data_get($request->payload, 'clinical_format.npt_type', '—')); ?></td><td>Ver solicitud</td><td><?php echo e($statusText($request->status)); ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="11">Sin solicitudes registradas.</td></tr><?php endif; ?></tbody></table></div>
          <section class="doctor-npt-ai"><div><strong>Asistente IA</strong><small>Análisis del historial de solicitudes enviadas por hospitales</small></div><b>Historial PRODIFEM</b><p><strong>Sin pregunta activa.</strong><span>Las respuestas se generarán con la información visible de este historial.</span></p></section>
        </section>
      <?php endif; ?>

      <section id="doctor-requests" class="doctor-assistant-native-table-card doctor-workspace-card">
        <div class="doctor-assistant-native-heading">
          <div><h2>Solicitudes cl&iacute;nicas</h2><p>Env&iacute;a estudios, nutrici&oacute;n parenteral y mezclas oncol&oacute;gicas al &aacute;rea operativa de la unidad.</p></div>
          <span><?php echo e($providerRequests->count()); ?> solicitudes</span>
        </div>
        <div class="doctor-request-types">
          <?php if(request('section') === 'services' && request('type') && !$availableRequestTypes->contains(request('type'))): ?>
            <p class="doctor-workspace-error">Este servicio no est asignado al perfil operativo seleccionado.</p>
          <?php endif; ?>
          <?php $__currentLoopData = [
            'clinical_labs' => ['Estudios y laboratorio', 'Solicitud de estudios, anlisis clnicos o interconsulta de laboratorio.'],
            'npt' => ['Nutricin parenteral', 'Formato de solicitud de mezcla para soporte nutricional.'],
            'chemo' => ['Oncologa / Quimioterapia', 'Solicitud de mezcla oncolgica y autorizacin operativa.'],
          ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $requestType => [$requestTitle, $requestDescription]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!$availableRequestTypes->contains($requestType)) continue; ?>
            <details class="doctor-request-card" data-request-type="<?php echo e($requestType); ?>" <?php if((request('section') === 'requests' && old('request_type') === $requestType) || (request('section') === 'services' && request('type') === $requestType && request('action', 'request') === 'request')): ?> open <?php endif; ?>>
              <summary><strong><?php echo e($requestTitle); ?></strong><span><?php echo e($requestDescription); ?></span></summary>
              <form method="post" action="<?php echo e(route('doctor.service_requests.store')); ?>" class="doctor-workspace-form doctor-specialized-request-form" enctype="multipart/form-data">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="request_type" value="<?php echo e($requestType); ?>">
                <label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?> - <?php echo e($patient->platform_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                <label>Servicio<input name="service" value="<?php echo e($requestTitle); ?>" required></label>
                <label>Fecha requerida<input type="datetime-local" name="required_at"></label>
                <label>Prioridad<select name="priority"><option value="routine">Rutina</option><option value="urgent">Urgente</option></select></label>
                <label class="wide">Diagn&oacute;stico<textarea name="diagnosis" rows="3" required></textarea></label>
                <?php echo $__env->make('doctor.partials.specialized-request-fields', compact('requestType', 'doctor'), array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <label class="wide">Indicaciones y observaciones<textarea name="notes" rows="3"></textarea></label>
                <button type="submit">Enviar solicitud</button>
              </form>
            </details>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <div class="doctor-assistant-native-table-scroll">
          <table class="doctor-assistant-native-table"><thead><tr><th>Folio</th><th>Fecha</th><th>Paciente</th><th>Tipo</th><th>Servicio</th><th>Autorizaciones</th><th>Estatus</th><th>Detalle</th></tr></thead><tbody>
            <?php $__empty_1 = true; $__currentLoopData = $providerRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $providerRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><strong><?php echo e($providerRequest->external_id); ?></strong></td>
                <td><?php echo e($providerRequest->requested_at?->format('d/m/Y H:i')); ?></td>
                <td><?php echo e($providerRequest->patient?->full_name); ?></td>
                <td><?php echo e(['clinical_labs'=>'Estudios','npt'=>'NPT','chemo'=>'Oncologa'][$providerRequest->request_type] ?? $providerRequest->request_type); ?></td>
                <td><?php echo e(data_get($providerRequest->payload, 'service')); ?></td>
                <td>Operativa: <?php echo e($statusText(data_get($providerRequest->payload, 'authorizations.operational'))); ?><br>Farmacia: <?php echo e($statusText(data_get($providerRequest->payload, 'authorizations.pharmacy'))); ?></td>
                <td><?php echo e($statusText($providerRequest->status)); ?></td>
                <td><details class="doctor-inline-editor"><summary>Ver solicitud</summary><p><strong>Diagn&oacute;stico:</strong> <?php echo e(data_get($providerRequest->payload, 'diagnosis')); ?></p><p><strong>Indicaciones:</strong> <?php echo e(data_get($providerRequest->payload, 'notes') ?: 'Sin observaciones'); ?></p><?php if($providerRequest->request_type === 'npt'): ?><p><strong>Volumen total:</strong> <?php echo e(data_get($providerRequest->payload, 'clinical_format.total_volume')); ?> ml</p><p><strong>V&iacute;a:</strong> <?php echo e(data_get($providerRequest->payload, 'clinical_format.route')); ?></p><?php elseif($providerRequest->request_type === 'chemo'): ?><p><strong>Medicamentos:</strong> <?php echo e(collect(data_get($providerRequest->payload, 'clinical_format.medications', []))->pluck('medication')->filter()->join(', ')); ?></p><?php endif; ?></details></td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="8">Sin solicitudes cl&iacute;nicas registradas.</td></tr><?php endif; ?>
          </tbody></table>
        </div>
      </section>

      <section id="doctor-availability" class="doctor-assistant-native-table-card doctor-workspace-card">
        <div class="doctor-assistant-native-heading">
          <div><h2>Consultorios y disponibilidad</h2><p>Configura los horarios que Consulta Externa puede utilizar al generar citas.</p></div>
          <span><?php echo e($availabilityRules->count()); ?> horarios</span>
        </div>
        <?php if(session('status')): ?> <p class="doctor-workspace-notice"><?php echo e(session('status')); ?></p> <?php endif; ?>
        <?php if($errors->any()): ?> <p class="doctor-workspace-error"><?php echo e($errors->first()); ?></p> <?php endif; ?>
        <details class="doctor-prescription-editor" <?php if(request('section') === 'agenda'): ?> open <?php endif; ?>>
          <summary>Nueva cita</summary>
          <form method="post" action="<?php echo e(route('doctor.appointments.store')); ?>" class="doctor-workspace-form doctor-workspace-encounter">
            <?php echo csrf_field(); ?>
            <label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?> - <?php echo e($patient->platform_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label>Consultorio<select name="doctor_clinic_id" required><option value="">Seleccionar consultorio</option><?php $__currentLoopData = $clinics->where('status', 'active'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($clinic->id); ?>"><?php echo e($clinic->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label>Fecha y hora<input type="datetime-local" name="starts_at" required></label>
            <label>Duracion<select name="duration"><option value="20">20 minutos</option><option value="30" selected>30 minutos</option><option value="45">45 minutos</option><option value="60">60 minutos</option></select></label>
            <label>Modalidad<select name="modality"><option>Presencial</option><option>Video llamada</option></select></label>
            <label>Motivo<input name="reason" placeholder="Motivo de la consulta"></label>
            <button type="submit">Guardar cita</button>
          </form>
        </details>
        <div class="doctor-workspace-grid">
          <form method="post" action="<?php echo e(route('doctor.clinics.store')); ?>" class="doctor-workspace-form">
            <?php echo csrf_field(); ?>
            <strong>Nuevo consultorio</strong>
            <label>Nombre<input name="name" required placeholder="Ej. Consultorio 5"></label>
            <label>Tipo<select name="location_type"><option value="in_person">Presencial</option><option value="virtual">Virtual</option><option value="hybrid">Híbrido</option></select></label>
            <label class="wide">Dirección<input name="address" placeholder="Dirección o enlace de atención"></label>
            <button type="submit">Guardar consultorio</button>
          </form>
          <form method="post" action="<?php echo e(route('doctor.availability.store')); ?>" class="doctor-workspace-form">
            <?php echo csrf_field(); ?>
            <strong>Publicar horario</strong>
            <label>Consultorio<select name="doctor_clinic_id" required><option value="">Seleccionar</option><?php $__currentLoopData = $clinics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($clinic->id); ?>"><?php echo e($clinic->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label>Día<select name="weekday"><?php $__currentLoopData = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($day); ?>"><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label>Inicio<input type="time" name="start_time" value="08:00" required></label>
            <label>Fin<input type="time" name="end_time" value="16:00" required></label>
            <input type="hidden" name="mode" value="in_person">
            <label>Vigente desde<input type="date" name="recurrence_start" value="<?php echo e(now()->toDateString()); ?>" required></label>
            <label>Hasta<input type="date" name="recurrence_end"></label>
            <button type="submit">Publicar horario</button>
          </form>
        </div>
        <div class="doctor-assistant-native-table-scroll">
          <table class="doctor-assistant-native-table">
            <thead><tr><th>Consultorio</th><th>Tipo</th><th>Direccion</th><th>Estatus</th><th>Acciones</th></tr></thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $clinics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
              <tr>
                <td><strong><?php echo e($clinic->name); ?></strong></td>
                <td><?php echo e(['in_person' => 'Presencial', 'virtual' => 'Virtual', 'hybrid' => 'Hibrido'][$clinic->location_type] ?? $clinic->location_type); ?></td>
                <td><?php echo e($clinic->address ?: 'Sin direccion registrada'); ?></td>
                <td><?php echo e($statusText($clinic->status)); ?></td>
                <td>
                  <details class="doctor-inline-editor" <?php if(request('clinic') == $clinic->id): ?> open <?php endif; ?>>
                    <summary>Editar</summary>
                    <form method="post" action="<?php echo e(route('doctor.clinics.update', $clinic)); ?>" class="doctor-workspace-form">
                      <?php echo csrf_field(); ?>
                      <?php echo method_field('PATCH'); ?>
                      <label>Nombre<input name="name" value="<?php echo e($clinic->name); ?>" required></label>
                      <label>Tipo<select name="location_type"><option value="in_person" <?php if($clinic->location_type === 'in_person'): echo 'selected'; endif; ?>>Presencial</option><option value="virtual" <?php if($clinic->location_type === 'virtual'): echo 'selected'; endif; ?>>Virtual</option><option value="hybrid" <?php if($clinic->location_type === 'hybrid'): echo 'selected'; endif; ?>>Hibrido</option></select></label>
                      <label class="wide">Direccion<input name="address" value="<?php echo e($clinic->address); ?>"></label>
                      <label>Estatus<select name="status"><option value="active" <?php if($clinic->status === 'active'): echo 'selected'; endif; ?>>Activo</option><option value="inactive" <?php if($clinic->status === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
                      <button type="submit">Guardar cambios</button>
                    </form>
                  </details>
                  <form method="post" action="<?php echo e(route('doctor.clinics.destroy', $clinic)); ?>" class="doctor-inline-delete" onsubmit="return confirm('Eliminar o inactivar este consultorio?')">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="doctor-workspace-secondary"><?php echo e($clinic->availabilityRules->isNotEmpty() ? 'Inactivar' : 'Eliminar'); ?></button>
                  </form>
                </td>
              </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
              <tr><td colspan="5">No hay consultorios registrados.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="doctor-assistant-native-table-scroll">
          <table class="doctor-assistant-native-table"><thead><tr><th>Consultorio</th><th>Día</th><th>Horario</th><th>Vigencia</th><th>Estatus</th><th>Acciones</th></tr></thead><tbody>
          <?php $__empty_1 = true; $__currentLoopData = $availabilityRules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr><td><strong><?php echo e($rule->clinic?->name); ?></strong></td><td><?php echo e([1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'][$rule->weekday] ?? $rule->weekday); ?></td><td><?php echo e(substr($rule->start_time,0,5)); ?> - <?php echo e(substr($rule->end_time,0,5)); ?></td><td><?php echo e($rule->recurrence_start?->format('d/m/Y')); ?> - <?php echo e($rule->recurrence_end?->format('d/m/Y') ?? 'Abierta'); ?></td><td><?php echo e($statusText($rule->status)); ?></td><td><form method="post" action="<?php echo e(route('doctor.availability.destroy', $rule)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?><button class="doctor-workspace-secondary" type="submit">Quitar</button></form></td></tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?> <tr><td colspan="6">No hay horarios publicados.</td></tr> <?php endif; ?>
          </tbody></table>
        </div>
      </section>

      <?php if($activeSection === 'video' && request('action', 'schedule') === 'schedule'): ?>
        <?php
          $videoPatient = $patients->first();
          $videoPatientName = $videoPatient?->full_name ?? 'Claudia Beatriz Salinas Vega';
          $videoPatientNumber = $videoPatient?->platform_number ?? '100000001';
          $videoClinic = $clinics->first();
        ?>

            <?php if(! $videoScheduleComplete): ?>
        <section id="doctor-video" class="doctor-video-scheduler" aria-labelledby="doctor-video-scheduler-title">
          <?php if($errors->any()): ?>
            <p class="doctor-workspace-error"><?php echo e($errors->first()); ?></p>
          <?php endif; ?>
          <div class="doctor-video-scheduler-header">
            <a class="doctor-video-back" href="<?php echo e(route('doctor.dashboard')); ?>">← Atrás</a>
            <div class="doctor-video-scheduler-heading">
              <h2 id="doctor-video-scheduler-title">Agendar videollamada</h2>
              <p>Programa una videoconsulta con un paciente.</p>
            </div>
            <ol class="doctor-video-steps" aria-label="Progreso de agendamiento">
              <li class="is-active"><span>1</span> Paciente</li>
              <li><span>2</span> Datos</li>
              <li><span>3</span> Confirmar</li>
              <li><span>4</span> Listo</li>
            </ol>
          </div>

          <form method="post" action="<?php echo e(route('doctor.appointments.store')); ?>" data-video-schedule-wizard>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="doctor_clinic_id" value="<?php echo e($videoClinic?->id); ?>">
            <input type="hidden" name="modality" value="Video llamada">
            <input type="hidden" name="starts_at" data-video-starts-at>
            <input type="hidden" name="duration" value="20" data-video-duration>
          <div class="doctor-video-scheduler-panel" data-video-wizard-step="1">
            <div class="doctor-video-step-title">
              <span>1</span>
              <div>
                <h3>Seleccionar paciente</h3>
                <p>Busca y selecciona el paciente que tendrá la videoconsulta.</p>
              </div>
            </div>

            <label class="doctor-video-patient-field">
              Paciente
              <select name="patient_id" aria-label="Paciente para videoconsulta" required data-video-patient-select>
                <?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <option value="<?php echo e($patient->id); ?>" data-name="<?php echo e($patient->full_name); ?>" data-number="<?php echo e($patient->platform_number); ?>" <?php if($loop->first): echo 'selected'; endif; ?>><?php echo e($patient->platform_number); ?> - <?php echo e($patient->full_name); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <option value="">Sin pacientes disponibles</option>
                <?php endif; ?>
              </select>
            </label>

            <div class="doctor-video-patient-summary">
              <strong><?php echo e($videoPatientName); ?> <span><?php echo e($videoPatientNumber); ?> - EXP-24091</span></strong>
              <div class="doctor-video-patient-data">
                <div><small>Servicio</small><b>Consulta privada</b></div>
                <div><small>Unidad</small><b>Privada</b></div>
                <div><small>Diagnóstico</small><b>Primera vez</b></div>
                <div><small>Ubicación</small><b>Angeles Lomas</b></div>
              </div>
            </div>

            <div class="doctor-video-scheduler-actions">
              <button type="button" data-video-next="1">Continuar</button>
            </div>
          </div>
          <div class="doctor-video-scheduler-panel" data-video-wizard-step="2" hidden>
            <div class="doctor-video-step-title"><span>2</span><div><h3>Seleccionar tipo de consulta</h3><p>Define el motivo y el horario de la videoconsulta.</p></div></div>
            <div class="doctor-video-form-fields">
              <label>Tipo de consulta<select data-video-consultation-type><option>Primera vez</option><option>Seguimiento</option><option>Revisi&oacute;n de resultados</option></select></label>
              <label>Motivo de la consulta<textarea data-video-reason placeholder="Escribe el motivo de la videoconsulta"></textarea></label>
              <div class="doctor-video-field-grid"><label>Fecha<input type="date" value="<?php echo e(now()->format('Y-m-d')); ?>" data-video-date></label><label>Hora<input type="time" value="10:00" data-video-time></label></div>
              <label>Duraci&oacute;n<select data-video-duration-select><option value="20">20 minutos</option><option value="30">30 minutos</option><option value="45">45 minutos</option><option value="60">60 minutos</option></select></label>
            </div>
            <div class="doctor-video-scheduler-actions"><button type="button" class="doctor-video-plain-button" data-video-back="1">Atr&aacute;s</button><button type="button" data-video-next="2">Continuar</button></div>
          </div>
          <div class="doctor-video-scheduler-panel" data-video-wizard-step="3" hidden>
            <div class="doctor-video-step-title"><span>3</span><div><h3>Verificar datos y agendar</h3><p>Confirma la informaci&oacute;n antes de crear la videoconsulta.</p></div></div>
            <div class="doctor-video-confirm-box">
              <div class="doctor-video-confirm-card"><small>Paciente</small><strong data-video-summary-patient><?php echo e($videoPatientName); ?></strong><span data-video-summary-number><?php echo e($videoPatientNumber); ?></span></div>
              <div class="doctor-video-confirm-card"><small>Fecha</small><strong data-video-summary-date></strong></div>
              <div class="doctor-video-confirm-card"><small>Hora</small><strong data-video-summary-time></strong></div>
              <div class="doctor-video-confirm-card"><small>Duraci&oacute;n</small><strong data-video-summary-duration></strong></div>
              <div class="doctor-video-confirm-card"><small>Tipo de consulta</small><strong data-video-summary-type></strong></div>
              <div class="doctor-video-confirm-card"><small>Motivo</small><strong data-video-summary-reason></strong></div>
            </div>
            <input type="hidden" name="reason" data-video-reason-input>
            <div class="doctor-video-scheduler-actions"><button type="button" class="doctor-video-plain-button" data-video-back="2">Atr&aacute;s</button><button type="submit" class="doctor-video-submit">Agendar videollamada</button></div>
          </div>
          </form>
        </section>
        <?php else: ?>
        <section id="doctor-video" class="doctor-video-scheduler" aria-labelledby="doctor-video-scheduler-title">
          <div class="doctor-video-scheduler-header">
            <a class="doctor-video-back" href="<?php echo e(route('doctor.dashboard', ['section' => 'video'])); ?>">&larr; Atr&aacute;s</a>
            <div class="doctor-video-scheduler-heading"><h2 id="doctor-video-scheduler-title">Agendar videollamada</h2><p>Programa una videoconsulta con un paciente.</p></div>
            <ol class="doctor-video-steps" aria-label="Progreso de agendamiento"><li class="is-complete"><span>1</span> Paciente</li><li class="is-complete"><span>2</span> Datos</li><li class="is-complete"><span>3</span> Confirmar</li><li class="is-active"><span>4</span> Listo</li></ol>
          </div>
          <div class="doctor-video-scheduler-panel doctor-video-ready">
            <span class="doctor-video-ready-check">&#10003;</span>
            <h3>Videoconsulta agendada</h3>
            <p>La videoconsulta ha sido agendada correctamente y qued&oacute; registrada en la agenda.</p>
            <div class="doctor-video-confirm-box">
              <div class="doctor-video-confirm-card"><small>Paciente</small><strong><?php echo e($scheduledVideoAppointment?->patient?->full_name ?? $videoPatientName); ?></strong><span><?php echo e($scheduledVideoAppointment?->patient?->platform_number ?? $videoPatientNumber); ?></span></div>
              <div class="doctor-video-confirm-card"><small>Fecha</small><strong><?php echo e($scheduledVideoAppointment?->starts_at?->format('d/m/Y') ?? now()->format('d/m/Y')); ?></strong></div>
              <div class="doctor-video-confirm-card"><small>Hora</small><strong><?php echo e($scheduledVideoAppointment?->starts_at?->format('H:i') ?? '10:00'); ?> h</strong></div>
              <div class="doctor-video-confirm-card"><small>Duraci&oacute;n</small><strong>20 minutos</strong></div>
              <div class="doctor-video-confirm-card"><small>Tipo de consulta</small><strong>Primera vez</strong></div>
              <div class="doctor-video-confirm-card"><small>Motivo</small><strong><?php echo e($scheduledVideoAppointment?->reason ?: 'Sin motivo'); ?></strong></div>
            </div>
            <div class="doctor-video-scheduler-actions"><a href="<?php echo e(route('doctor.dashboard', ['section' => 'agenda', 'reset_video_schedule' => 1])); ?>">Ver en agenda</a><a class="doctor-video-plain-button" href="<?php echo e(route('doctor.dashboard', ['section' => 'video', 'action' => 'schedule', 'reset_video_schedule' => 1])); ?>">Agendar otra videoconsulta</a></div>
          </div>
        </section>
        <?php endif; ?>
      <?php else: ?>
      <section id="doctor-video" class="doctor-assistant-native-table-card doctor-workspace-card">
        <div class="doctor-assistant-native-heading"><div><h2>Video Consulta</h2><p>Agenda una videollamada o inicia una consulta inmediata.</p></div><span><?php echo e($appointments->where('modality', 'Video llamada')->count()); ?></span></div>
        <div class="doctor-video-actions">
          <details class="doctor-inline-editor" <?php if(request('section') === 'video' && request('action', 'schedule') === 'schedule'): ?> open <?php endif; ?>>
            <summary>Agendar videollamada</summary>
            <form method="post" action="<?php echo e(route('doctor.appointments.store')); ?>" class="doctor-workspace-form">
              <?php echo csrf_field(); ?>
              <label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?> - <?php echo e($patient->platform_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Consultorio virtual<select name="doctor_clinic_id" required><option value="">Seleccionar</option><?php $__currentLoopData = $clinics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $clinic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($clinic->id); ?>"><?php echo e($clinic->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Fecha y hora<input type="datetime-local" name="starts_at" required></label>
              <label>Duraci&oacute;n<select name="duration"><option value="20">20 minutos</option><option value="30" selected>30 minutos</option><option value="45">45 minutos</option><option value="60">60 minutos</option></select></label>
              <label class="wide">Motivo<textarea name="reason" rows="3"></textarea></label>
              <input type="hidden" name="modality" value="Video llamada">
              <button type="submit">Agendar videollamada</button>
            </form>
          </details>
          <details class="doctor-inline-editor" <?php if(request('section') === 'video' && request('action') === 'now'): ?> open <?php endif; ?>>
            <summary>Iniciar videoconsulta</summary>
            <form method="post" action="<?php echo e(route('doctor.encounters.store')); ?>" class="doctor-workspace-form">
              <?php echo csrf_field(); ?>
              <label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?> - <?php echo e($patient->platform_number); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label class="wide">Motivo<textarea name="reason" rows="3"></textarea></label>
              <input type="hidden" name="modality" value="Video llamada">
              <button type="submit">Iniciar ahora</button>
            </form>
          </details>
        </div>
        <div class="doctor-assistant-native-table-scroll"><table class="doctor-assistant-native-table"><thead><tr><th>Fecha</th><th>Paciente</th><th>Motivo</th><th>Estatus</th><th>Acci&oacute;n</th></tr></thead><tbody>
          <?php $__empty_1 = true; $__currentLoopData = $appointments->where('modality', 'Video llamada'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <tr><td><?php echo e($appointment->starts_at?->format('d/m/Y H:i')); ?></td><td><?php echo e($appointment->patient?->full_name); ?></td><td><?php echo e($appointment->reason ?: 'Sin motivo'); ?></td><td><?php echo e($statusText($appointment->status)); ?></td><td><?php if(!in_array($appointment->status, ['completed','cancelled'], true)): ?><form method="post" action="<?php echo e(route('doctor.encounters.store')); ?>"><?php echo csrf_field(); ?><input type="hidden" name="patient_id" value="<?php echo e($appointment->patient_id); ?>"><input type="hidden" name="appointment_id" value="<?php echo e($appointment->id); ?>"><input type="hidden" name="reason" value="<?php echo e($appointment->reason); ?>"><input type="hidden" name="modality" value="Video llamada"><button type="submit">Entrar</button></form><?php endif; ?></td></tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5">No hay videollamadas programadas.</td></tr><?php endif; ?>
        </tbody></table></div>
      </section>
      <?php endif; ?>

      <section id="doctor-encounters" class="doctor-assistant-native-table-card doctor-workspace-card">
        <div class="doctor-assistant-native-heading"><div><h2>Encuentro clínico</h2><p>Consulta, diagnóstico y plan de tratamiento en un mismo expediente.</p></div><span><?php echo e($encounters->count()); ?></span></div>
        <form method="post" action="<?php echo e(route('doctor.encounters.store')); ?>" class="doctor-workspace-form doctor-workspace-encounter">
          <?php echo csrf_field(); ?>
          <label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option><?php $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($patient->id); ?>"><?php echo e($patient->full_name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
          <label>Modalidad<select name="modality"><option>Presencial</option><option>Video llamada</option></select></label>
          <label>Motivo<input name="reason" placeholder="Motivo de consulta"></label>
          <label>Síntomas<input name="symptoms" placeholder="Síntomas principales"></label>
          <label>Diagnóstico<input name="assessment" placeholder="V&iacute;aloración inicial"></label>
          <label class="wide">Plan de tratamiento<textarea name="treatment_plan" rows="2"></textarea></label>
          <button type="submit">Iniciar consulta</button>
        </form>
        <div class="doctor-assistant-native-table-scroll"><table class="doctor-assistant-native-table"><thead><tr><th>Paciente</th><th>Inicio</th><th>Motivo</th><th>Diagnóstico</th><th>Estatus</th><th>Acciones</th></tr></thead><tbody>
        <?php $__empty_1 = true; $__currentLoopData = $encounters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $encounter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
          <tr><td><strong><?php echo e($encounter->patient?->full_name); ?></strong><small><?php echo e(data_get($encounter->metadata, 'modality', 'Presencial')); ?></small></td><td><?php echo e($encounter->started_at?->format('d/m/Y H:i')); ?></td><td><?php echo e($encounter->reason ?: 'Sin captura'); ?></td><td><?php echo e($encounter->assessment ?: 'Pendiente'); ?></td><td><?php echo e($statusText($encounter->status)); ?></td><td><?php if($encounter->status !== 'completed'): ?><a class="doctor-workspace-secondary" href="#encounter-<?php echo e($encounter->id); ?>">Editar consulta</a><?php else: ?> Completada <?php endif; ?></td></tr>
          <?php if($encounter->status !== 'completed'): ?>
            <tr id="encounter-<?php echo e($encounter->id); ?>" class="doctor-encounter-editor-row"><td colspan="6">
              <form method="post" action="<?php echo e(route('doctor.encounters.update', $encounter)); ?>" class="doctor-clinical-form">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <label>Motivo<input name="reason" value="<?php echo e($encounter->reason); ?>"></label>
                <label>S&iacute;ntomas<textarea name="symptoms" rows="3"><?php echo e($encounter->symptoms); ?></textarea></label>
                <fieldset class="doctor-vital-grid"><legend>Signos vitales</legend>
                  <label>Presin arterial<input name="vital_signs[blood_pressure]" value="<?php echo e(data_get($encounter->vital_signs, 'blood_pressure')); ?>"></label>
                  <label>Frecuencia cardiaca<input name="vital_signs[heart_rate]" value="<?php echo e(data_get($encounter->vital_signs, 'heart_rate')); ?>"></label>
                  <label>Temperatura<input name="vital_signs[temperature]" value="<?php echo e(data_get($encounter->vital_signs, 'temperature')); ?>"></label>
                  <label>Peso<input name="vital_signs[weight]" value="<?php echo e(data_get($encounter->vital_signs, 'weight')); ?>"></label>
                </fieldset>
                <label>Antecedentes<textarea name="background[summary]" rows="3"><?php echo e(data_get($encounter->background, 'summary')); ?></textarea></label>
                <label>Exploracin fsica<textarea name="examination" rows="3"><?php echo e($encounter->examination); ?></textarea></label>
                <label>Diagn&oacute;stico<textarea name="assessment" rows="3" required><?php echo e($encounter->assessment); ?></textarea></label>
                <label>Plan de tratamiento<textarea name="treatment_plan" rows="3"><?php echo e($encounter->treatment_plan); ?></textarea></label>
                <label>Notas<textarea name="notes" rows="3"><?php echo e($encounter->notes); ?></textarea></label>
                <div class="doctor-clinical-actions">
                  <button type="submit">Guardar avance</button>
                  <button type="submit" formaction="<?php echo e(route('doctor.encounters.complete', $encounter)); ?>">Finalizar consulta</button>
                </div>
              </form>
            </td></tr>
          <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?> <tr><td colspan="6">No hay consultas clínicas registradas.</td></tr> <?php endif; ?>
        </tbody></table></div>
      </section>

      <?php endif; ?>

      <form class="doctor-assistant-native-composer">
        <button type="button" data-doctor-menu-button aria-expanded="false" aria-label="Abrir men&uacute; m&eacute;dico">&#8942;</button>
        <label>
          <span>+</span>
          <input placeholder="Preg&uacute;ntame">
        </label>
        <button type="button">Mic</button>
        <button type="button">Enviar</button>
      </form>
    </main>
  </div>

  <template id="doctor-prescription-item-template">
    <tr>
      <td data-item-number></td>
      <td><input name="items[__INDEX__][cnis]" placeholder="CNIS"></td>
      <td><select name="items[__INDEX__][medication_catalog_item_id]" data-medication-select><option value="">Captura libre</option><?php $__currentLoopData = $medications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $medication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($medication->id); ?>" data-name="<?php echo e($medication->generic_name ?: $medication->name); ?>" data-cnis="<?php echo e($medication->cnis); ?>" data-presentation="<?php echo e($medication->presentation); ?>"><?php echo e($medication->generic_name ?: $medication->name); ?> <?php if($medication->cnis): ?> - <?php echo e($medication->cnis); ?> <?php endif; ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><input name="items[__INDEX__][medication_name]" placeholder="Medicamento" required data-medication-name></td>
      <td><input name="items[__INDEX__][dose]" placeholder="Dosis"></td>
      <td><input name="items[__INDEX__][presentation]" placeholder="Presentación" data-presentation></td>
      <td><input name="items[__INDEX__][route]" placeholder="Vía"></td>
      <td><input name="items[__INDEX__][frequency]" placeholder="Cada 8 h"></td>
      <td><input name="items[__INDEX__][duration]" placeholder="7 días"></td>
      <td><input type="number" min="1" max="999" name="items[__INDEX__][quantity]" value="1" required></td>
      <td><textarea name="items[__INDEX__][instructions]" rows="2" placeholder="Indicaciones"></textarea></td>
      <td><button type="button" class="doctor-prescription-remove" data-remove-medication>−</button></td>
    </tr>
  </template>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const menu = document.querySelector('[data-doctor-menu]');
  const menuButton = document.querySelector('[data-doctor-menu-button]');
  const showDoctorMenuPanel = name => {
    menu?.querySelectorAll('[data-doctor-menu-panel]').forEach(panel => {
      panel.toggleAttribute('hidden', panel.dataset.doctorMenuPanel !== name);
    });
  };
  menuButton?.addEventListener('click', () => {
    const opening = menu?.hasAttribute('hidden');
    menu?.toggleAttribute('hidden', !opening);
    menuButton.setAttribute('aria-expanded', opening ? 'true' : 'false');
    if (opening) showDoctorMenuPanel('main');
  });
  menu?.querySelectorAll('[data-doctor-menu-open]').forEach(button => button.addEventListener('click', () => {
    showDoctorMenuPanel(button.dataset.doctorMenuOpen);
  }));
  menu?.querySelectorAll('[data-doctor-menu-back]').forEach(button => button.addEventListener('click', () => {
    showDoctorMenuPanel(button.dataset.doctorMenuBack);
  }));
  document.querySelector('[data-npt-history-link]')?.addEventListener('click', event => {
    event.preventDefault();
    window.location.assign(event.currentTarget.href);
  });
  document.addEventListener('click', event => {
    if (!menu || menu.hasAttribute('hidden') || menu.contains(event.target) || menuButton?.contains(event.target)) return;
    menu.setAttribute('hidden', '');
    menuButton?.setAttribute('aria-expanded', 'false');
    showDoctorMenuPanel('main');
  });

  const videoWizard = document.querySelector('[data-video-schedule-wizard]');
  if (videoWizard) {
    const panels = [...videoWizard.querySelectorAll('[data-video-wizard-step]')];
    const steps = [...document.querySelectorAll('.doctor-video-steps li')];
    const patient = videoWizard.querySelector('[data-video-patient-select]');
    const consultationType = videoWizard.querySelector('[data-video-consultation-type]');
    const reason = videoWizard.querySelector('[data-video-reason]');
    const date = videoWizard.querySelector('[data-video-date]');
    const time = videoWizard.querySelector('[data-video-time]');
    const duration = videoWizard.querySelector('[data-video-duration-select]');
    const startsAt = videoWizard.querySelector('[data-video-starts-at]');
    const durationInput = videoWizard.querySelector('[data-video-duration]');
    const reasonInput = videoWizard.querySelector('[data-video-reason-input]');
    const setText = (selector, value) => {
      const target = videoWizard.querySelector(selector);
      if (target) target.textContent = value;
    };
    const updateVideoSummary = () => {
      const selected = patient?.options[patient.selectedIndex];
      setText('[data-video-summary-patient]', selected?.dataset.name || selected?.textContent || 'Paciente');
      setText('[data-video-summary-number]', selected?.dataset.number || '');
      setText('[data-video-summary-date]', date?.value ? new Intl.DateTimeFormat('es-MX', { day: '2-digit', month: 'short', year: 'numeric' }).format(new Date(`${date.value}T00:00:00`)) : '');
      setText('[data-video-summary-time]', time?.value ? `${time.value} h` : '');
      setText('[data-video-summary-duration]', duration?.value ? `${duration.value} minutos` : '');
      setText('[data-video-summary-type]', consultationType?.value || '');
      setText('[data-video-summary-reason]', reason?.value.trim() || 'Sin motivo');
      if (startsAt) startsAt.value = date?.value && time?.value ? `${date.value}T${time.value}` : '';
      if (durationInput) durationInput.value = duration?.value || '20';
      if (reasonInput) reasonInput.value = reason?.value || '';
    };
    const showVideoStep = step => {
      panels.forEach(panel => panel.toggleAttribute('hidden', Number(panel.dataset.videoWizardStep) !== step));
      steps.forEach((item, index) => {
        item.classList.toggle('is-active', index === step - 1);
        item.classList.toggle('is-complete', index < step - 1);
      });
      updateVideoSummary();
    };
    videoWizard.querySelectorAll('[data-video-next]').forEach(button => button.addEventListener('click', () => {
      const step = Number(button.dataset.videoNext);
      if (step === 1 && !patient?.value) {
        patient?.focus();
        return;
      }
      if (step === 2 && (!date?.value || !time?.value)) {
        (!date?.value ? date : time)?.focus();
        return;
      }
      showVideoStep(step + 1);
    }));
    videoWizard.querySelectorAll('[data-video-back]').forEach(button => button.addEventListener('click', () => showVideoStep(Number(button.dataset.videoBack))));
    [patient, consultationType, date, time, duration].forEach(control => control?.addEventListener('change', updateVideoSummary));
    reason?.addEventListener('input', updateVideoSummary);
    updateVideoSummary();
  }
  document.querySelectorAll('[data-clinic-toggle]').forEach(button => button.addEventListener('click', () => {
    const panel = document.querySelector(`[data-clinic-panel="${button.dataset.clinicToggle}"]`);
    if (!panel) return;
    panel.toggleAttribute('hidden');
    button.textContent = panel.hasAttribute('hidden') ? 'Abrir información' : 'Cerrar información';
  }));
  document.querySelector('[data-clinic-create-toggle]')?.addEventListener('click', () => {
    const form = document.querySelector('[data-clinic-form]');
    if (!form) return;
    form.removeAttribute('hidden');
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    form.querySelector('input[name="name"]')?.focus({ preventScroll: true });
  });
  document.querySelectorAll('[data-clinic-menu-toggle]').forEach(button => button.addEventListener('click', () => {
    const menu = button.parentElement?.querySelector('[data-clinic-menu]');
    if (!menu) return;
    document.querySelectorAll('[data-clinic-menu]').forEach(item => { if (item !== menu) item.setAttribute('hidden', ''); });
    const opening = menu.hasAttribute('hidden');
    menu.toggleAttribute('hidden', !opening);
    if (!opening) return;
    const trigger = button.getBoundingClientRect();
    const width = 186;
    const left = trigger.right + width + 12 <= window.innerWidth
      ? trigger.right + 8
      : Math.max(8, trigger.left - width - 8);
    menu.style.position = 'fixed';
    menu.style.left = `${left}px`;
    menu.style.right = 'auto';
    menu.style.top = `${Math.max(8, Math.min(trigger.top - 8, window.innerHeight - 230))}px`;
    menu.style.bottom = 'auto';
  }));
  document.querySelectorAll('[data-schedule-range-toggle]').forEach(button => button.addEventListener('click', () => {
    const range = button.parentElement?.querySelector('[data-schedule-range]');
    range?.removeAttribute('hidden');
  }));
  document.querySelectorAll('[data-schedule-range-close]').forEach(button => button.addEventListener('click', () => {
    button.closest('[data-schedule-range]')?.setAttribute('hidden', '');
  }));
  document.querySelectorAll('[data-schedule-modality]').forEach(button => button.addEventListener('click', () => {
    const range = button.closest('[data-schedule-range]');
    range?.querySelectorAll('[data-schedule-modality]').forEach(option => option.classList.toggle('is-active', option === button));
    const label = range?.querySelector('[data-schedule-modality-label]');
    if (label) label.textContent = button.dataset.scheduleModality;
  }));
  const scheduleDays = [...document.querySelectorAll('[data-schedule-day]')];
  const scheduledWeekdays = new Set();
  const isoDate = date => date.toISOString().slice(0, 10);
  const weekdayIndex = date => (date.getDay() + 6) % 7;
  const updateScheduleDay = (index, date, { open = false } = {}) => {
    const day = scheduleDays[index];
    if (!day) return;
    const range = day.querySelector('[data-schedule-range]');
    const dateInput = range?.querySelector('[data-schedule-range-date-input]');
    const hiddenDate = range?.querySelector('[data-schedule-range-date]');
    if (dateInput) dateInput.value = isoDate(date);
    if (hiddenDate) hiddenDate.value = isoDate(date);
    if (open) range?.removeAttribute('hidden');
  };
  document.querySelectorAll('[data-schedule-range-toggle]').forEach(button => button.addEventListener('click', () => {
    const index = scheduleDays.indexOf(button.closest('[data-schedule-day]'));
    if (index < 0) return;
    scheduledWeekdays.add(index);
    const currentDate = button.closest('[data-schedule-day]')?.querySelector('[data-schedule-range-date-input]')?.value;
    updateScheduleDay(index, currentDate ? new Date(`${currentDate}T00:00:00`) : new Date(), { open: true });
    button.closest('[data-schedule-day]')?.querySelector('em')?.replaceChildren('09:00 - 13:00');
    document.querySelector('[data-schedule-calendar]')?.dispatchEvent(new Event('schedule:changed'));
  }));
  document.querySelectorAll('[data-schedule-range-delete]').forEach(button => button.addEventListener('click', () => {
    const day = button.closest('[data-schedule-day]');
    const index = scheduleDays.indexOf(day);
    if (index < 0) return;
    scheduledWeekdays.delete(index);
    day?.querySelector('[data-schedule-range]')?.setAttribute('hidden', '');
    day?.querySelector('em')?.replaceChildren('Sin horario');
    document.querySelector('[data-schedule-calendar]')?.dispatchEvent(new Event('schedule:changed'));
  }));
  const persistedRulesNode = document.getElementById('schedule-persisted-rules');
  const persistedScheduleRules = persistedRulesNode ? JSON.parse(persistedRulesNode.textContent || '[]') : [];
  const scheduleCalendar = document.querySelector('[data-schedule-calendar]');
  if (scheduleCalendar) {
    const monthSelect = document.querySelector('[data-calendar-month]');
    const yearSelect = document.querySelector('[data-calendar-year]');
    const title = document.querySelector('[data-calendar-title]');
    const selectedDate = document.querySelector('[data-calendar-selected-date]');
    const locale = 'es-MX';
    let visible = new Date(Number(yearSelect?.value), Number(monthSelect?.value) - 1, 1);
    let selected = new Date();
    const sameDay = (one, other) => one.toDateString() === other.toDateString();
    const updateSelectedLabel = () => {
      if (selectedDate) selectedDate.textContent = new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(selected);
    };
    const renderCalendar = () => {
      const year = visible.getFullYear();
      const month = visible.getMonth();
      if (monthSelect) monthSelect.value = String(month + 1);
      if (yearSelect) yearSelect.value = String(year);
      if (title) title.textContent = new Intl.DateTimeFormat(locale, { month: 'long', year: 'numeric' }).format(visible);
      scheduleCalendar.replaceChildren();
      ['DOM', 'LUN', 'MAR', 'MIE', 'JUE', 'VIE', 'SAB'].forEach(day => {
        const heading = document.createElement('b');
        heading.textContent = day;
        scheduleCalendar.append(heading);
      });
      const first = new Date(year, month, 1);
      const start = new Date(year, month, 1 - first.getDay());
      for (let index = 0; index < 42; index += 1) {
        const day = new Date(start);
        day.setDate(start.getDate() + index);
        const cell = document.createElement('button');
        cell.type = 'button';
        cell.textContent = String(day.getDate());
        cell.classList.toggle('is-out', day.getMonth() !== month);
        cell.classList.toggle('is-selected', sameDay(day, selected));
        cell.classList.toggle('has-schedule', scheduledWeekdays.has(weekdayIndex(day)));
        cell.setAttribute('aria-label', new Intl.DateTimeFormat(locale, { dateStyle: 'full' }).format(day));
        const dayValue = isoDate(day);
        const entries = persistedScheduleRules.filter(rule => {
          const ruleWeekday = rule.weekday === 7 ? 0 : Number(rule.weekday);
          return day.getDay() === ruleWeekday
            && (!rule.startDate || dayValue >= rule.startDate)
            && (!rule.endDate || dayValue <= rule.endDate)
            && (rule.months || []).map(Number).includes(day.getMonth() + 1);
        });
        entries.forEach(rule => {
          const entry = document.createElement('span');
          entry.textContent = `${rule.start}-${rule.end}\n${rule.clinic}`;
          entry.style.cssText = 'display:block;margin-top:5px;padding:4px;border:1px solid #16bdb5;border-left-width:3px;border-radius:7px;background:#e9fbf8;color:#06355a;font-size:8px;line-height:1.35;white-space:pre-line;font-weight:800;';
          cell.append(entry);
        });
        cell.addEventListener('click', () => {
          selected = day;
          updateScheduleDay(weekdayIndex(day), day, { open: true });
          const persistDate = document.querySelector('[data-persist-date]');
          const persistWeekday = document.querySelector('[data-persist-weekday]');
          if (persistDate) persistDate.value = isoDate(day);
          if (persistWeekday) persistWeekday.value = String(weekdayIndex(day) + 1);
          updateSelectedLabel();
          renderCalendar();
        });
        scheduleCalendar.append(cell);
      }
    };
    document.querySelector('[data-calendar-prev]')?.addEventListener('click', () => { visible.setMonth(visible.getMonth() - 1); renderCalendar(); });
    document.querySelector('[data-calendar-next]')?.addEventListener('click', () => { visible.setMonth(visible.getMonth() + 1); renderCalendar(); });
    document.querySelector('[data-calendar-today]')?.addEventListener('click', () => { selected = new Date(); visible = new Date(selected.getFullYear(), selected.getMonth(), 1); updateSelectedLabel(); renderCalendar(); });
    monthSelect?.addEventListener('change', () => { visible = new Date(visible.getFullYear(), Number(monthSelect.value) - 1, 1); renderCalendar(); });
    yearSelect?.addEventListener('change', () => { visible = new Date(Number(yearSelect.value), visible.getMonth(), 1); renderCalendar(); });
    updateSelectedLabel();
    renderCalendar();
    scheduleCalendar.addEventListener('schedule:changed', renderCalendar);
  }
  const persistPeriod = document.querySelector('[data-persist-period]');
  const persistYear = document.querySelector('[data-persist-year]');
  const persistMonth = document.querySelector('[data-persist-month]');
  const persistStart = document.querySelector('[data-persist-start]');
  const persistEnd = document.querySelector('[data-persist-end]');
  const refreshPersistPeriod = () => {
    const mode = persistPeriod?.value || 'all';
    if (persistYear) persistYear.hidden = mode === 'all' || mode === 'range';
    if (persistMonth) persistMonth.hidden = mode !== 'month';
    if (persistStart) persistStart.hidden = mode !== 'range';
    if (persistEnd) persistEnd.hidden = mode !== 'range';
  };
  persistPeriod?.addEventListener('change', refreshPersistPeriod);
  refreshPersistPeriod();
  document.querySelector('[data-focus-clinic]')?.addEventListener('click', () => {
    document.querySelector('[data-clinic-form] input[name="name"]')?.focus();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  });
  document.querySelector('[data-clinic-form]')?.addEventListener('submit', () => {
    const form = document.querySelector('[data-clinic-form]');
    const parts = ['street', 'exterior_number', 'interior_number', 'neighborhood', 'municipality', 'state', 'country', 'postal_code']
      .map(key => form.querySelector(`[name="metadata[${key}]"]`)?.value?.trim()).filter(Boolean);
    form.querySelector('[data-clinic-address-value]').value = parts.join(', ');
  });

  const patientCreate = document.querySelector('[data-patient-create]');
  document.querySelector('[data-patient-create-toggle]')?.addEventListener('click', () => {
    if (!patientCreate) return;
    const willOpen = patientCreate.hasAttribute('hidden');
    patientCreate.toggleAttribute('hidden', !willOpen);
    if (willOpen) {
      patientCreate.scrollIntoView({ behavior: 'smooth', block: 'start' });
      patientCreate.querySelector('input:not([type="hidden"])')?.focus({ preventScroll: true });
    }
  });
  document.querySelector('[data-patient-create-close]')?.addEventListener('click', () => {
    patientCreate?.setAttribute('hidden', '');
  });

  const agendaRoot = document.querySelector('[data-agenda-root]');
  if (agendaRoot) {
    const agendaParams = new URLSearchParams(window.location.search);
    const newAppointment = agendaRoot.querySelector('[data-agenda-new]');
    if (agendaParams.get('new') === '1') {
      newAppointment?.removeAttribute('hidden');
      const modality = newAppointment?.querySelector('[name="modality"]');
      if (modality && agendaParams.get('modality')) modality.value = agendaParams.get('modality');
      newAppointment?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
    const agendaEvents = [...agendaRoot.querySelectorAll('[data-agenda-event]')];
    const agendaSearch = agendaRoot.querySelector('[data-agenda-search]');
    const clinicFilter = agendaRoot.querySelector('[data-agenda-clinic-filter]');
    const modalityFilter = agendaRoot.querySelector('[data-agenda-modality-filter]');
    const statusFilter = agendaRoot.querySelector('[data-agenda-status-filter]');
    const applyAgendaFilters = () => {
      const search = String(agendaSearch?.value || '').toLocaleLowerCase('es');
      agendaEvents.forEach(event => {
        const visible = (!search || event.dataset.search.includes(search))
          && (!clinicFilter?.value || event.dataset.clinic === clinicFilter.value)
          && (!modalityFilter?.value || event.dataset.modality === modalityFilter.value)
          && (!statusFilter?.value || event.dataset.status === statusFilter.value);
        event.hidden = !visible;
      });
    };
    [agendaSearch, clinicFilter, modalityFilter, statusFilter].forEach(control => {
      control?.addEventListener(control.tagName === 'INPUT' ? 'input' : 'change', applyAgendaFilters);
    });
    agendaRoot.querySelectorAll('[data-agenda-kind]').forEach(button => button.addEventListener('click', () => {
      agendaRoot.querySelectorAll('[data-agenda-kind]').forEach(item => item.classList.remove('is-active'));
      button.classList.add('is-active');
      if (modalityFilter) modalityFilter.value = button.dataset.agendaKind || '';
      applyAgendaFilters();
    }));
    agendaRoot.querySelectorAll('[data-agenda-clinic]').forEach(button => button.addEventListener('click', () => {
      if (clinicFilter) clinicFilter.value = button.dataset.agendaClinic || '';
      applyAgendaFilters();
    }));
    agendaRoot.querySelector('[data-agenda-clear]')?.addEventListener('click', () => {
      [agendaSearch, clinicFilter, modalityFilter, statusFilter].forEach(control => { if (control) control.value = ''; });
      applyAgendaFilters();
    });
    agendaRoot.querySelector('[data-agenda-new-toggle]')?.addEventListener('click', () => newAppointment?.toggleAttribute('hidden'));
    agendaRoot.querySelector('[data-agenda-new-close]')?.addEventListener('click', () => newAppointment?.setAttribute('hidden', ''));
  }

  const requestedServiceType = <?php echo json_encode(request('section') === 'services' ? request('type') : null, 15, 512) ?>;
  const requestedServiceAction = <?php echo json_encode(request('action', 'request'), 512) ?>;
  if (requestedServiceType) {
    const requestCard = document.querySelector(`[data-request-type="${CSS.escape(requestedServiceType)}"]`);
    if (requestCard && requestedServiceAction === 'request') requestCard.open = true;
    const target = requestedServiceAction === 'history'
      ? document.querySelector('#doctor-requests table')
      : requestCard;
    window.requestAnimationFrame(() => target?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
  }

  const patientsTable = document.querySelector('[data-patients-table]');
  const patientGlobalFilter = document.querySelector('[data-patient-global-filter]');
  const patientColumnFilters = [...document.querySelectorAll('[data-patient-filter]')];
  const patientRows = patientsTable
    ? [...patientsTable.querySelectorAll('tbody tr:not([data-patient-empty-filter])')]
    : [];
  const patientEmptyFilter = patientsTable?.querySelector('[data-patient-empty-filter]');
  const normalizePatientText = value => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLocaleLowerCase('es-MX')
    .trim();
  const applyPatientFilters = () => {
    const globalValue = normalizePatientText(patientGlobalFilter?.value);
    let visibleRows = 0;

    patientRows.forEach(row => {
      const globalMatches = !globalValue || normalizePatientText(row.textContent).includes(globalValue);
      const columnsMatch = patientColumnFilters.every(filter => {
        const value = normalizePatientText(filter.value);
        if (!value) return true;
        const cell = row.cells[Number(filter.dataset.patientFilter)];
        return normalizePatientText(cell?.textContent).includes(value);
      });
      const visible = globalMatches && columnsMatch;
      row.hidden = !visible;
      if (visible) visibleRows++;
    });

    if (patientEmptyFilter) patientEmptyFilter.hidden = visibleRows !== 0;
  };
  patientGlobalFilter?.addEventListener('input', applyPatientFilters);
  patientColumnFilters.forEach(filter => {
    filter.addEventListener(filter.matches('select') ? 'change' : 'input', applyPatientFilters);
  });
  document.querySelector('[data-patient-filter-clear]')?.addEventListener('click', () => {
    if (patientGlobalFilter) patientGlobalFilter.value = '';
    patientColumnFilters.forEach(filter => { filter.value = ''; });
    applyPatientFilters();
  });

  const body = document.querySelector('[data-prescription-items]');
  const template = document.getElementById('doctor-prescription-item-template');
  if (!body || !template) return;
  let index = 0;
  const renumber = () => body.querySelectorAll('tr').forEach((row, position) => row.querySelector('[data-item-number]').textContent = position + 1);
  const addRow = () => {
    const fragment = template.content.cloneNode(true);
    fragment.querySelectorAll('[name]').forEach(field => field.name = field.name.replace('__INDEX__', index));
    index++;
    body.appendChild(fragment);
    renumber();
  };
  document.querySelector('[data-add-medication]')?.addEventListener('click', addRow);
  body.addEventListener('click', event => {
    if (!event.target.matches('[data-remove-medication]')) return;
    if (body.rows.length > 1) event.target.closest('tr').remove();
    renumber();
  });
  body.addEventListener('change', event => {
    if (!event.target.matches('[data-medication-select]')) return;
    const option = event.target.selectedOptions[0];
    const row = event.target.closest('tr');
    if (!option?.value) return;
    row.querySelector('[data-medication-name]').value = option.dataset.name || '';
    row.querySelector('[name$="[cnis]"]').value = option.dataset.cnis || '';
    row.querySelector('[data-presentation]').value = option.dataset.presentation || '';
  });
  addRow();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Asistente Clinico'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views\doctor\dashboard.blade.php ENDPATH**/ ?>