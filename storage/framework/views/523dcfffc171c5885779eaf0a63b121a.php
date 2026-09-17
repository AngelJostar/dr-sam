

<?php $__env->startSection('body_class', 'unit-native-body'); ?>

<?php
  $statusLabels = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'available' => 'Disponible',
    'scheduled' => 'Programada',
    'completed' => 'Completada',
    'requested' => 'Solicitada',
    'accepted' => 'Aceptada',
    'delivered' => 'Entregada',
    'rejected' => 'Rechazada',
    'pending' => 'Pendiente',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $unitCode = $unit->code ?? $unit->clues ?? 'Sin clave';
  $unitLocation = collect([$unit->city, $unit->municipality, $unit->state])->filter()->implode(', ') ?: 'Sin ubicacion';
  $sectionLabels = [
    'profile' => 'Perfil',
    'services' => 'Servicios integrales',
    'catalog' => 'Catalogo de la unidad',
    'users' => 'Usuarios',
    'patients' => 'Pacientes',
    'doctors' => 'Medicos',
    'specialties' => 'Especialidades',
    'external-pharmacy' => 'Farmacia Externa',
    'procedure-areas' => 'Areas de Procedimiento',
    'medications' => 'Medicamentos',
  ];
  $sectionEyebrow = strtoupper($sectionLabels[$section] ?? 'Servicios integrales');
  $menu = [
    'profile' => ['user', 'Perfil'],
    'services' => ['minus', 'Servicios integrales'],
    'users' => ['users', 'Usuarios'],
    'patients' => ['patient', 'Pacientes'],
    'doctors' => ['stethoscope', 'Medicos'],
    'specialties' => ['star', 'Especialidades'],
    'external-pharmacy' => ['rx', 'Farmacia Externa'],
    'procedure-areas' => ['areas', 'Areas de Procedimiento'],
    'medications' => ['pill', 'Medicamentos'],
  ];
  $catalogSections = ['catalog', 'users', 'patients', 'doctors', 'specialties', 'external-pharmacy', 'procedure-areas', 'medications'];
  $serviceChoices = ['Nutricion enteral', 'Nutricion parenteral', 'Quimioterapias', 'Hemodinamia', 'Analisis Clinicos', 'Histopatologia', 'Tomografia y resonancia', 'Hemodialisis', 'Mantenimiento', 'RPBI', 'Limpieza', 'Lavanderia', 'Dietas', 'Traslado terrestre y aereo'];
  $areaChoices = [
    'Enfermeria' => 'Seguimiento clinico y operativo del servicio.',
    'Farmacia intrahospitalaria' => 'Gestion de medicamentos, mezclas y soporte farmaceutico.',
    'Farmacia Externa' => 'Inventario, recetas, movimientos y almacenes de farmacia externa.',
    'Centro Oncologico' => 'Operacion y seguimiento de servicios oncologicos.',
    'Consulta Externa' => 'Atencion ambulatoria y coordinacion de servicios externos.',
  ];
?>

<?php $__env->startSection('content'); ?>
  <div class="unit-native-screen">
    <aside class="unit-native-sidebar" aria-label="Navegacion unidad">
      <nav class="unit-native-menu">
        <?php $__currentLoopData = $menu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => [$icon, $label]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <a class="<?php echo e($section === $key ? 'is-active' : ''); ?>" href="<?php echo e(route('unit.dashboard', ['section' => $key])); ?>">
            <span aria-hidden="true">
              <?php switch($icon):
                case ('user'): ?>
                  <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>
                  <?php break; ?>
                <?php case ('minus'): ?>
                  <svg viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
                  <?php break; ?>
                <?php case ('users'): ?>
                  <svg viewBox="0 0 24 24"><path d="M16 21a6 6 0 0 0-12 0"/><circle cx="10" cy="8" r="4"/><path d="M22 21a5 5 0 0 0-4-4.9"/><path d="M17 4.3a4 4 0 0 1 0 7.4"/></svg>
                  <?php break; ?>
                <?php case ('patient'): ?>
                  <svg viewBox="0 0 24 24"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="8" r="4"/><path d="M12 14v4M10 16h4"/></svg>
                  <?php break; ?>
                <?php case ('stethoscope'): ?>
                  <svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg>
                  <?php break; ?>
                <?php case ('star'): ?>
                  <svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 18.2l-5.6 3 1.1-6.2L3 10.6l6.2-.9Z"/></svg>
                  <?php break; ?>
                <?php case ('rx'): ?>
                  <svg viewBox="0 0 24 24"><path d="M5 4h6a4 4 0 0 1 0 8H5V4Z"/><path d="M5 20V4M10 12l7 8M18 14l-6 6"/></svg>
                  <?php break; ?>
                <?php case ('pill'): ?>
                  <svg viewBox="0 0 24 24"><path d="m10.5 20.5-7-7a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7Z"/><path d="m8.5 11.5 7-7a5 5 0 0 1 7 7l-7 7"/><path d="m7 17 10-10"/></svg>
                  <?php break; ?>
                <?php default: ?>
                  <svg viewBox="0 0 24 24"><path d="M4 20h16"/><path d="M7 20V8h4v12M13 20V4h4v16"/><path d="M9 12h.01M15 8h.01"/></svg>
              <?php endswitch; ?>
            </span>
            <?php echo e($label); ?>

          </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      </nav>
    </aside>

    <header class="unit-native-topbar">
      <strong>Unidad</strong>
      <span><?php echo e($unit->institution?->name ?? 'IMSS Bienestar Estado de Mexico'); ?></span>
      <div class="unit-native-topbar-user">
        <button type="button" aria-label="Notificaciones">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        </button>
        <div>
          <strong>Usuario activo</strong>
          <small><?php echo e($unit->state ?? $unit->entity ?? 'Estado de Mexico'); ?></small>
        </div>
        <form method="post" action="<?php echo e(route('logout')); ?>" class="unit-native-logout">
          <?php echo csrf_field(); ?>
          <button type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
            Cerrar sesion
          </button>
        </form>
      </div>
    </header>

    <section class="unit-native-workspace">
      <?php if($section !== 'medications' && session('status')): ?>
        <div class="notice success"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <?php if($section !== 'medications' && !($section === 'doctors' && old('_doctor_form')) && $errors->any()): ?>
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if($section === 'profile'): ?>
        <header class="unit-native-header">
          <div>
            <p class="eyebrow"><?php echo e($sectionEyebrow); ?></p>
            <h1><?php echo e($unit->name); ?></h1>
            <p><?php echo e($unitCode); ?> - <?php echo e($unitLocation); ?></p>
          </div>
          <div class="unit-native-selector">
            <label>
              Unidad
              <select>
                <option><?php echo e($unit->name); ?> - <?php echo e($unitCode); ?></option>
              </select>
            </label>
            <time><?php echo e(now()->format('d M Y')); ?></time>
          </div>
        </header>
      <?php endif; ?>

      <?php if(in_array($section, $catalogSections, true)): ?>
        <?php echo $__env->make('unit.carousels.'.$section, array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php endif; ?>

      <?php if($section === 'profile'): ?>
        <?php
          $profile = $unit->metadata['profile'] ?? [];
          $profileImage = $profile['image_path'] ?? null;
          $profileName = $profile['public_name'] ?? $unit->name;
          $profileInfo = $profile['general_info'] ?? '';
          $profileServices = $profile['services'] ?? $unit->contractedServices->pluck('service.name')->filter()->implode(', ');
          $profileNews = $profile['news'] ?? '';
          $profileSocial = $profile['social_text'] ?? '';
          $profileStationery = $profile['stationery_note'] ?? '';
          $initials = str($profileName)->explode(' ')->filter()->take(2)->map(fn ($word) => str($word)->substr(0, 1))->implode('');
        ?>
        <div class="unit-profile-layout" data-unit-profile>
          <section class="unit-profile-card">
            <header><h2>Perfil de la unidad</h2><p>Imagen, servicios y novedades</p></header>
            <form method="post" action="<?php echo e(route('unit.profile.update')); ?>" enctype="multipart/form-data" class="unit-profile-form">
              <?php echo csrf_field(); ?>
              <?php echo method_field('patch'); ?>
              <label>Imagen de perfil y papelerÃ­a</label>
              <div class="unit-profile-image-row">
                <div class="unit-profile-image-box" data-profile-image-preview><?php if($profileImage): ?><img src="<?php echo e(asset('storage/'.$profileImage)); ?>" alt="Imagen de <?php echo e($profileName); ?>"><?php else: ?><span>Sin imagen</span><?php endif; ?></div>
                <div><input type="file" name="profile_image" accept="image/png,image/jpeg,image/webp" data-profile-image-input><label class="unit-profile-remove"><input type="checkbox" name="remove_image" value="1"> Quitar imagen</label></div>
              </div>
              <label>Nombre pÃºblico de la unidad<input name="public_name" value="<?php echo e(old('public_name', $profileName)); ?>" data-profile-name required></label>
              <label>InformaciÃ³n general<textarea name="general_info" data-profile-info><?php echo e(old('general_info', $profileInfo)); ?></textarea></label>
              <label>Servicios de la unidad<textarea name="services" data-profile-services><?php echo e(old('services', $profileServices)); ?></textarea></label>
              <label>Novedades<textarea name="news" data-profile-news><?php echo e(old('news', $profileNews)); ?></textarea></label>
              <label>Texto para redes sociales<textarea name="social_text" data-profile-social><?php echo e(old('social_text', $profileSocial)); ?></textarea></label>
              <label>Nota para papelerÃ­a<textarea name="stationery_note" data-profile-stationery><?php echo e(old('stationery_note', $profileStationery)); ?></textarea></label>
              <button type="submit">Guardar perfil</button>
            </form>
          </section>
          <section class="unit-profile-card unit-profile-preview-card">
            <header><h2>Vista previa</h2><p>Redes sociales y papelerÃ­a</p></header>
            <div class="unit-profile-preview">
              <div class="unit-profile-cover"><strong data-profile-initials><?php echo e(strtoupper($initials)); ?></strong></div>
              <div class="unit-profile-preview-copy">
                <small><?php echo e($unitCode); ?> - <?php echo e(strtoupper($unitLocation)); ?></small><h2 data-profile-preview-name><?php echo e($profileName); ?></h2>
                <p data-profile-preview-info><?php echo e($profileInfo ?: 'InformaciÃ³n general pendiente.'); ?></p>
                <strong>SERVICIOS</strong><p data-profile-preview-services><?php echo e($profileServices ?: 'Sin servicios registrados.'); ?></p>
                <strong>NOVEDADES</strong><p data-profile-preview-news><?php echo e($profileNews ?: 'Sin novedades registradas.'); ?></p>
                <article><strong>REDES SOCIALES</strong><p data-profile-preview-social><?php echo e($profileSocial ?: 'InformaciÃ³n general pendiente.'); ?></p></article>
                <article><strong>PAPELERÃA</strong><p data-profile-preview-stationery><?php echo e($profileStationery ?: 'PapelerÃ­a institucional de la unidad.'); ?></p></article>
              </div>
            </div>
          </section>
        </div>
        <script>
          (() => {
            const root = document.querySelector('[data-unit-profile]');
            const bind = (input, output, fallback) => input.addEventListener('input', () => output.textContent = input.value.trim() || fallback);
            const name = root.querySelector('[data-profile-name]');
            bind(name, root.querySelector('[data-profile-preview-name]'), <?php echo json_encode($unit->name, 15, 512) ?>);
            bind(root.querySelector('[data-profile-info]'), root.querySelector('[data-profile-preview-info]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-services]'), root.querySelector('[data-profile-preview-services]'), 'Sin servicios registrados.');
            bind(root.querySelector('[data-profile-news]'), root.querySelector('[data-profile-preview-news]'), 'Sin novedades registradas.');
            bind(root.querySelector('[data-profile-social]'), root.querySelector('[data-profile-preview-social]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-stationery]'), root.querySelector('[data-profile-preview-stationery]'), 'PapelerÃ­a institucional de la unidad.');
            name.addEventListener('input', () => root.querySelector('[data-profile-initials]').textContent = name.value.trim().split(/\s+/).slice(0, 2).map(word => word[0] || '').join('').toUpperCase());
            root.querySelector('[data-profile-image-input]').addEventListener('change', (event) => { const file = event.target.files[0]; if (!file) return; root.querySelector('[data-profile-image-preview]').innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Vista previa">`; });
          })();
        </script>
      <?php elseif($section === 'services'): ?>
        <?php
          $institutionServiceOrder = [
              'consulta-externa',
              'hemodinamia',
              'laboratorio',
              'nutricion-parenteral',
              'quimioterapias',
              'medicamentos-importacion',
              'farmacia-digital',
              'farmacia-externa',
              'central-de-mezclas',
              'mantenimiento-equipo-medico',
              'osteosintesis',
          ];
          $institutionServiceOrderIndex = array_flip($institutionServiceOrder);
          $unitServiceKey = function ($contract) use ($institutionServiceOrderIndex) {
              $externalId = str($contract->service?->external_id ?? '')->lower()->slug('-')->toString();
              if (array_key_exists($externalId, $institutionServiceOrderIndex)) {
                  return $externalId;
              }

              $serviceText = str(collect([
                  $contract->service?->external_id,
                  $contract->service?->name,
                  $contract->service?->category,
                  $contract->service?->specialty,
              ])->filter()->implode(' '))->lower()->toString();

              return match (true) {
                  str_contains($serviceText, 'consulta') => 'consulta-externa',
                  str_contains($serviceText, 'hemodinam') => 'hemodinamia',
                  str_contains($serviceText, 'laboratorio') || str_contains($serviceText, 'analisis') => 'laboratorio',
                  str_contains($serviceText, 'nutricion') => 'nutricion-parenteral',
                  str_contains($serviceText, 'central') && str_contains($serviceText, 'mezcla') => 'central-de-mezclas',
                  str_contains($serviceText, 'quimio') || str_contains($serviceText, 'oncolo') => 'quimioterapias',
                  str_contains($serviceText, 'mantenimiento') || str_contains($serviceText, 'equipo') => 'mantenimiento-equipo-medico',
                  str_contains($serviceText, 'osteo') => 'osteosintesis',
                  default => $externalId ?: str($contract->service?->name ?? 'servicio')->slug('-')->toString(),
              };
          };
          $unitServiceContracts = $unit->contractedServices
              ->filter(fn ($contract) => $contract->service && $contract->status === 'active')
              ->sortBy(function ($contract) use ($institutionServiceOrderIndex, $unitServiceKey) {
                  $serviceKey = $unitServiceKey($contract);

                  return str_pad((string) ($institutionServiceOrderIndex[$serviceKey] ?? 999), 3, '0', STR_PAD_LEFT)
                      .'|'.str($contract->service?->name ?? '')->lower()->toString();
              })
              ->values();
          $unitServiceIcon = function ($contract) {
            $serviceText = str(($contract->service?->name ?? '').' '.($contract->service?->category ?? '').' '.($contract->service?->specialty ?? ''))->lower()->toString();

            return match (true) {
              str_contains($serviceText, 'consulta') => 'consultation',
              str_contains($serviceText, 'hemodinam') || str_contains($serviceText, 'cardio') => 'heart',
              str_contains($serviceText, 'laboratorio') || str_contains($serviceText, 'analisis') => 'microscope',
              str_contains($serviceText, 'mezcla') => 'mixtures',
              str_contains($serviceText, 'nutricion') => 'nutrition',
              str_contains($serviceText, 'quimio') || str_contains($serviceText, 'oncolo') => 'infusion',
              str_contains($serviceText, 'mantenimiento') || str_contains($serviceText, 'equipo') => 'maintenance',
              str_contains($serviceText, 'osteo') => 'osteosynthesis',
              str_contains($serviceText, 'farmacia externa') => 'pharmacy',
              default => 'service',
            };
          };
          $consultationAppointments = $appointments->values();
          $consultationContract = $unitServiceContracts->first(fn ($contract) => $unitServiceKey($contract) === 'consulta-externa');
          $requestedServiceContractId = (string) request('service', '');
          $activeServiceContract = $unitServiceContracts->first(
              fn ($contract) => (string) $contract->id === $requestedServiceContractId
          ) ?? $unitServiceContracts->first();
          $activeServiceContractId = $requestedServiceContractId === 'catalog'
              ? 'catalog'
              : (string) ($activeServiceContract?->id ?? '');
          $activeServiceIsConsultation = $activeServiceContract
              && $unitServiceKey($activeServiceContract) === 'consulta-externa';
          $consultationMenu = [
              'home' => ['Inicio', 'home'],
              'agenda' => ['Agenda', 'calendar'],
              'rooms' => ['Consultorios', 'room'],
              'doctors' => ['Medicos', 'doctor'],
              'specialties' => ['Especialidades', 'specialty'],
              'patients' => ['Pacientes', 'patients'],
              'prescriptions' => ['Recetas', 'prescription'],
          ];
          $consultationSpecialties = $consultationAppointments->pluck('specialty')
              ->merge($unit->doctors->pluck('specialty'))
              ->filter()
              ->unique()
              ->sort()
              ->values();
          $storedConsultationRooms = collect(data_get($unit->metadata, 'procedure_areas', []))
              ->where('type', 'consulting')
              ->values();
          $consultationCalendarRooms = $consultationRooms->map(function ($room) use ($consultationAppointments, $storedConsultationRooms, $unit) {
              $roomAppointments = $consultationAppointments
                  ->where('procedure_area_id', $room->id)
                  ->sortByDesc('starts_at')
                  ->values();
              $assignedDoctorId = data_get($room->metadata, 'assigned_doctor_id')
                  ?: data_get($room->metadata, 'doctor_id');
              $assignedDoctor = $assignedDoctorId
                  ? $unit->doctors->firstWhere('id', (int) $assignedDoctorId)
                  : null;
              $assignedDoctor ??= $roomAppointments->first()?->doctor;
              $storedRoom = $storedConsultationRooms->first(fn ($candidate) => (
                  (string) data_get($candidate, 'id') === (string) $room->external_id
                  || (string) data_get($candidate, 'unit_number') === (string) $room->unit_number
              ));
              $editId = $room->external_id ?: data_get($storedRoom, 'id');
              $specialty = data_get($room->metadata, 'specialty')
                  ?: $assignedDoctor?->specialty
                  ?: $roomAppointments->pluck('specialty')->filter()->first()
                  ?: 'Consulta externa';

              return [
                  'id' => (string) $room->id,
                  'external_id' => $room->external_id ?: 'ID '.$room->id,
                  'number' => $room->unit_number ?: 'Consultorio '.$room->id,
                  'name' => data_get($room->metadata, 'name', $room->unit_number ?: 'Consultorio '.$room->id),
                  'specialty' => $specialty,
                  'location' => $room->location ?: 'Consulta externa',
                  'floor' => $room->floor ?: 'Sin piso',
                  'capacity' => max(1, (int) ($room->simultaneous_capacity ?: 1)),
                  'responsible' => $room->responsible_name ?: 'Sin responsable asignado',
                  'doctor_id' => $assignedDoctor ? (string) $assignedDoctor->id : null,
                  'doctor_name' => $assignedDoctor?->full_name ?: 'Sin medico asignado',
                  'doctor_license' => $assignedDoctor?->professional_license ?: 'Sin cedula registrada',
                  'status' => $room->status ?: 'inactive',
                  'status_label' => $room->status === 'active' ? 'Activo' : 'Inactivo',
                  'registered' => true,
                  'schedulable' => $room->status === 'active',
                  'edit_url' => $editId
                      ? route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'edit' => $editId])
                      : route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'catalog' => 'consulting']),
                  'schedules' => $room->schedules
                      ->where('active', true)
                      ->map(fn ($schedule) => [
                          'day' => (int) $schedule->day_of_week,
                          'start' => substr((string) $schedule->starts_at, 0, 5),
                          'end' => substr((string) $schedule->ends_at, 0, 5),
                      ])
                      ->values()
                      ->all(),
              ];
          })->values();
          if ($consultationCalendarRooms->isEmpty()) {
              $consultationCalendarRooms = collect([[
                  'id' => 'general',
                  'external_id' => 'Sin ID',
                  'number' => 'Consultorio general',
                  'name' => 'Consultorio general',
                  'specialty' => 'Consulta externa',
                  'location' => $unitLocation,
                  'floor' => 'Sin piso',
                  'capacity' => 1,
                  'responsible' => 'Sin responsable asignado',
                  'doctor_id' => null,
                  'doctor_name' => 'Sin medico asignado',
                  'doctor_license' => 'Sin cedula registrada',
                  'status' => 'inactive',
                  'status_label' => 'Sin registro',
                  'registered' => false,
                  'schedulable' => false,
                  'edit_url' => route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'create' => 'consulting']),
                  'schedules' => [],
              ]]);
          }
          $consultationRoomSpecialties = $consultationSpecialties
              ->merge($consultationCalendarRooms->pluck('specialty'))
              ->filter()
              ->unique()
              ->sort()
              ->values();
          $registeredConsultationRooms = $consultationCalendarRooms
              ->where('registered', true)
              ->values();
          $consultationCalendarPatients = $consultationPatientCatalog->map(fn ($patient) => [
              'id' => (string) $patient->id,
              'name' => $patient->full_name,
              'curp' => $patient->curp ?: 'Sin CURP',
              'platform_number' => $patient->platform_number ?: 'Sin expediente',
              'nss' => data_get($patient->metadata, 'nss') ?: 'Sin NSS',
              'sex' => $patient->sex ?: 'Sin especificar',
              'age' => $patient->birth_date?->age,
              'phone' => $patient->phone ?: 'Sin telefono',
              'email' => $patient->email ?: 'Sin correo',
          ])->values();
          $consultationCalendarDoctors = $unit->doctors
              ->where('status', 'active')
              ->map(fn ($doctor) => [
                  'id' => (string) $doctor->id,
                  'name' => $doctor->full_name,
                  'specialty' => $doctor->specialty ?: 'Consulta externa',
                  'license' => $doctor->professional_license ?: 'Sin cedula registrada',
                  'availability' => $doctor->availabilityRules
                      ->where('status', 'published')
                      ->map(fn ($rule) => [
                          'weekday' => (int) $rule->weekday,
                          'start' => substr((string) $rule->start_time, 0, 5),
                          'end' => substr((string) $rule->end_time, 0, 5),
                          'start_date' => $rule->recurrence_start?->toDateString(),
                          'end_date' => $rule->recurrence_end?->toDateString(),
                          'months' => $rule->selected_months ?: range(1, 12),
                      ])
                      ->values()
                      ->all(),
              ])
              ->values();
          $consultationCalendarRoomIds = $consultationCalendarRooms->pluck('id');
          $consultationCalendarAppointments = $consultationAppointments
              ->filter(fn ($appointment) => $appointment->starts_at)
              ->map(function ($appointment) use ($consultationCalendarRooms, $consultationCalendarRoomIds) {
                  $requestedRoomId = (string) ($appointment->procedure_area_id
                      ?? data_get($appointment->metadata, 'procedure_area_id')
                      ?? '');
                  $roomId = $consultationCalendarRoomIds->contains($requestedRoomId)
                      ? $requestedRoomId
                      : (string) $consultationCalendarRooms->first()['id'];
                  $assignedRoom = $consultationCalendarRooms->firstWhere('id', $requestedRoomId);
                  [$calendarStatusLabel, $calendarStatusKey] = match ($appointment->status) {
                      'confirmed' => ['Confirmada', 'confirmed'],
                      'in_progress' => ['En consulta', 'in-progress'],
                      'completed' => ['Finalizada', 'completed'],
                      'cancelled', 'no_show' => ['Cancelada', 'cancelled'],
                      default => ['En espera', 'waiting'],
                  };
                  $endsAt = $appointment->ends_at ?: $appointment->starts_at->copy()->addMinutes(30);

                  return [
                      'id' => (string) $appointment->id,
                      'folio' => data_get($appointment->metadata, 'folio')
                          ?: 'CE-'.$appointment->starts_at->format('Y').'-'.str_pad((string) $appointment->id, 6, '0', STR_PAD_LEFT),
                      'date' => $appointment->starts_at->toDateString(),
                      'time' => $appointment->starts_at->format('H:i'),
                      'ends_at' => $endsAt->format('H:i'),
                      'duration' => max(20, (int) $appointment->starts_at->diffInMinutes($endsAt)),
                      'room_id' => $roomId,
                      'room_number' => $appointment->procedureArea?->unit_number ?: data_get($assignedRoom, 'number'),
                      'patient_id' => (string) $appointment->patient_id,
                      'patient' => $appointment->patient?->full_name ?: 'Paciente sin nombre',
                      'patient_curp' => $appointment->patient?->curp ?: 'Sin CURP',
                      'patient_platform' => $appointment->patient?->platform_number ?: 'Sin expediente',
                      'patient_sex' => $appointment->patient?->sex ?: 'Sin especificar',
                      'patient_age' => $appointment->patient?->birth_date?->age,
                      'patient_phone' => $appointment->patient?->phone ?: 'Sin telefono',
                      'doctor_id' => (string) $appointment->doctor_id,
                      'doctor' => $appointment->doctor?->full_name ?: 'Medico por asignar',
                      'doctor_license' => $appointment->doctor?->professional_license ?: 'Sin cedula registrada',
                      'specialty' => $appointment->specialty ?: 'Consulta externa',
                      'reason' => $appointment->reason ?: 'Consulta medica',
                      'notes' => data_get($appointment->metadata, 'notes')
                          ?: data_get($appointment->metadata, 'last_status_note'),
                      'modality' => $appointment->modality ?: 'Presencial',
                      'location' => $appointment->procedureArea?->unit_number ?: ($appointment->location ?: 'Consulta externa'),
                      'status_value' => $appointment->status,
                      'status' => $calendarStatusKey,
                      'status_label' => $calendarStatusLabel,
                      'created_at_label' => $appointment->created_at?->format('d/m/Y H:i') ?: 'Sin fecha',
                      'history' => $appointment->statusEvents->map(fn ($event) => [
                          'from_status' => $event->from_status,
                          'to_status' => $event->to_status,
                          'notes' => $event->notes,
                          'date' => $event->created_at?->format('d/m/Y H:i') ?: 'Sin fecha',
                      ])->values(),
                  ];
              })
              ->values();
          $requestedCalendarDate = request()->string('calendar_date')->toString();
          $consultationCalendarDefaultDate = preg_match('/^\d{4}-\d{2}-\d{2}$/', $requestedCalendarDate)
              ? $requestedCalendarDate
              : now()->toDateString();
          if (! $consultationCalendarAppointments->contains('date', $consultationCalendarDefaultDate)
              && $consultationCalendarAppointments->isNotEmpty()) {
              $consultationCalendarDefaultDate = $consultationCalendarAppointments->sortByDesc('date')->first()['date'];
          }
          $consultationRequestType = function ($appointment) {
            $requestText = str(data_get($appointment->metadata, 'request_type') ?? $appointment->reason ?? '')->lower()->toString();

            return match (true) {
              str_contains($requestText, 'seguimiento') => 'Seguimiento',
              str_contains($requestText, 'control') => 'Control cronico',
              str_contains($requestText, 'estudio') => 'Estudios complementarios',
              default => 'Primera vez',
            };
          };
          $consultationPriority = function ($appointment) {
            $priority = str(data_get($appointment->metadata, 'priority') ?? '')->lower()->toString();

            return match ($priority) {
              'high', 'urgent', 'alta' => ['Alta', 'high'],
              'low', 'baja' => ['Baja', 'low'],
              default => ['Media', 'medium'],
            };
          };
          $consultationStatus = function (?string $status) {
            return match ($status) {
              'in_progress' => ['En atencion', 'attention'],
              'confirmed' => ['En validacion', 'validation'],
              'pending', 'requested' => ['Pendiente', 'pending'],
              'completed' => ['Completada', 'completed'],
              'cancelled', 'no_show' => ['Cancelada', 'cancelled'],
              default => ['Programada', 'scheduled'],
            };
          };
          $providerPayloadValue = function ($value, string $fallback = '-') {
              if (is_array($value)) {
                  $value = data_get($value, 'number')
                      ?? data_get($value, 'folio')
                      ?? data_get($value, 'id')
                      ?? data_get($value, 'code');
              }

              return filled($value) ? $value : $fallback;
          };
          $nutritionType = function ($providerRequest) {
              return in_array($providerRequest->request_type, ['chemo', 'chemotherapy'], true)
                  ? ['Oncologica', 'oncology']
                  : ['Nutricional', 'nutrition'];
          };
          $nutritionOperationalStatus = function ($providerRequest) use ($statusText) {
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
          $nutritionFilterCategory = function ($providerRequest) {
              return match ($providerRequest->status) {
                  'requested', 'draft', 'pending', 'accepted' => 'pending',
                  'preparing' => 'preparing',
                  'in_route' => 'route',
                  'delivered' => 'delivered',
                  'cancelled', 'rejected' => 'history',
                  default => 'pending',
              };
          };
          $nutritionApprovalStatus = function ($providerRequest) {
              $explicitStatus = str(
                  data_get($providerRequest->payload, 'approval_status')
                  ?? data_get($providerRequest->payload, 'approval.status')
                  ?? ''
              )->lower()->toString();
              $authorizations = collect(data_get($providerRequest->payload, 'authorizations', []))
                  ->map(fn ($status) => str($status)->lower()->toString())
                  ->filter();

              if (in_array($explicitStatus, ['rejected', 'denied', 'rechazada'], true)
                  || $authorizations->contains('rejected')
                  || in_array($providerRequest->status, ['cancelled', 'rejected'], true)) {
                  return ['Rechazada', 'rejected'];
              }

              if (in_array($explicitStatus, ['approved', 'authorized', 'aprobada'], true)
                  || ($authorizations->isNotEmpty() && $authorizations->every(fn ($status) => $status === 'approved'))
                  || in_array($providerRequest->status, ['preparing', 'in_route', 'delivered'], true)) {
                  return ['Aprobada', 'approved'];
              }

              return ['Pendiente', 'pending'];
          };
          $providerRequestPriority = function ($providerRequest) {
              $priority = str(data_get($providerRequest->payload, 'priority') ?? '')->lower()->toString();

              return match ($priority) {
                  'high', 'urgent', 'alta' => ['Alta', 'high'],
                  'low', 'baja' => ['Baja', 'low'],
                  default => ['Media', 'medium'],
              };
          };
          $providerRequestTypeLabel = function ($providerRequest) {
              $label = data_get($providerRequest->payload, 'request_type_label')
                  ?? data_get($providerRequest->payload, 'service_name');

              return filled($label)
                  ? (string) $label
                  : str($providerRequest->request_type ?? 'Solicitud')->replace(['_', '-'], ' ')->title()->toString();
          };
        ?>

        <div class="unit-service-dashboard" data-unit-service-dashboard>
          <?php if($unitServiceContracts->isNotEmpty()): ?>
            <section class="unit-service-carousel-card">
              <button type="button" class="unit-service-carousel-arrow" data-unit-service-carousel-prev aria-label="Servicio anterior">&lsaquo;</button>
              <div class="unit-service-carousel" data-unit-service-carousel aria-label="Servicios integrales">
                <button type="button"
                        class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-service-carousel-button', 'unit-service-catalog-link', 'is-active' => $activeServiceContractId === 'catalog']); ?>"
                        data-unit-service-tab="catalog"
                        data-unit-service-catalog-link>
                  <span class="unit-service-carousel-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                  </span>
                  <strong>Catalogo</strong>
                </button>
                <?php $__currentLoopData = $unitServiceContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <?php
                    $serviceIcon = $unitServiceIcon($contract);
                    $serviceName = $contract->service?->name ?? 'Servicio sin nombre';
                  ?>
                  <button type="button"
                          class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-service-carousel-button', 'is-active' => (string) $contract->id === $activeServiceContractId]); ?>"
                          data-unit-service-tab="<?php echo e($contract->id); ?>"
                          data-unit-service-key="<?php echo e($unitServiceKey($contract)); ?>">
                    <span class="unit-service-carousel-icon is-<?php echo e($serviceIcon); ?>" aria-hidden="true">
                      <?php switch($serviceIcon):
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
                        <?php case ('pharmacy'): ?>
                          <svg viewBox="0 0 24 24"><path d="M5 4h6a4 4 0 0 1 0 8H5V4Z"/><path d="M5 20V4M10 12l7 8M18 14l-6 6"/></svg>
                          <?php break; ?>
                        <?php default: ?>
                          <svg viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
                      <?php endswitch; ?>
                    </span>
                    <strong><?php echo e($serviceName); ?></strong>
                  </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
              <button type="button" class="unit-service-carousel-arrow" data-unit-service-carousel-next aria-label="Servicio siguiente">&rsaquo;</button>
            </section>

            <div class="unit-service-panels">
              <section class="unit-catalog-screen" data-unit-service-panel="catalog" <?php if($activeServiceContractId !== 'catalog'): ?> hidden <?php endif; ?>>
                <header class="unit-service-panel-header unit-catalog-info-card">
                  <div class="unit-service-panel-copy">
                    <h2>Catálogo de servicios integrales</h2>
                    <p>Servicios habilitados para <?php echo e($unit->name); ?></p>
                    <div class="unit-service-panel-badges">
                      <span class="unit-native-status">Activo</span>
                      <span><?php echo e($unitServiceContracts->count()); ?> servicios disponibles</span>
                    </div>
                  </div>
                </header>
                <div class="unit-catalog-toolbar">
                  <label class="unit-catalog-search">
                    <span class="sr-only">Buscar servicio</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                    <input type="search" placeholder="Buscar servicio" data-unit-service-catalog-search>
                  </label>
                </div>
                <section class="unit-native-table-card unit-catalog-content-card">
                  <div class="unit-native-table-scroll">
                    <table class="unit-native-table">
                      <thead><tr><th>Servicio</th><th>Categoría</th><th>Especialidad</th><th>Estatus</th><th>Acciones</th></tr></thead>
                      <tbody>
                        <?php $__currentLoopData = $unitServiceContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <tr data-unit-service-catalog-row>
                            <td><strong><?php echo e($contract->service?->name ?? 'Servicio sin nombre'); ?></strong></td>
                            <td><?php echo e($contract->service?->category ?? 'Sin categoría'); ?></td>
                            <td><?php echo e($contract->service?->specialty ?? 'Servicio general'); ?></td>
                            <td><span class="unit-native-status"><?php echo e($statusText($contract->status)); ?></span></td>
                            <td><button type="button" class="unit-native-button" data-unit-open-service="<?php echo e($contract->id); ?>">Abrir</button></td>
                          </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <tr data-unit-service-catalog-empty hidden><td colspan="5" class="unit-native-empty">No se encontraron servicios.</td></tr>
                      </tbody>
                    </table>
                  </div>
                </section>
              </section>
              <?php $__currentLoopData = $unitServiceContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $service = $contract->service;
                  $serviceName = $service?->name ?? 'Servicio sin nombre';
                  $serviceKey = $unitServiceKey($contract);
                  $operationSearch = str(($service?->name ?? '').' '.($service?->category ?? '').' '.($service?->specialty ?? ''))->lower()->toString();
                  $isConsultationService = $serviceKey === 'consulta-externa';
                  $isNutritionService = $serviceKey === 'nutricion-parenteral';
                  $isChemotherapyService = $serviceKey === 'quimioterapias';
                  $isCentralMixturesService = $serviceKey === 'central-de-mezclas';
                  $usesMixtureTable = $isNutritionService || $isChemotherapyService || $isCentralMixturesService;
                  $providerRequestTypes = match ($serviceKey) {
                    'nutricion-parenteral' => ['npt', 'nutrition'],
                    'quimioterapias' => ['chemo', 'chemotherapy'],
                    'medicamentos-importacion' => ['import', 'medication-import', 'medicamentos-importacion'],
                    'farmacia-digital' => ['pharmacy', 'digital-pharmacy', 'order', 'delivery', 'farmacia-digital'],
                    'central-de-mezclas' => ['npt', 'nutrition', 'chemo', 'chemotherapy'],
                    'laboratorio' => ['lab', 'laboratory', 'clinical-lab', 'clinical-laboratory'],
                    'hemodinamia' => ['hemodynamics', 'hemodinamia'],
                    'mantenimiento-equipo-medico' => ['maintenance', 'equipment-maintenance'],
                    'osteosintesis' => ['osteosynthesis', 'osteosintesis'],
                    default => [$serviceKey],
                  };
                  $serviceProviderRequests = $providerRequests
                    ->filter(fn ($providerRequest) => in_array(
                      str($providerRequest->request_type ?? '')->lower()->slug('-')->toString(),
                      $providerRequestTypes,
                      true
                    ))
                    ->values();
                  $nutritionRequestCounts = collect(['pending', 'preparing', 'route', 'delivered', 'history'])
                    ->mapWithKeys(fn ($category) => [
                      $category => $serviceProviderRequests
                        ->filter(fn ($providerRequest) => $nutritionFilterCategory($providerRequest) === $category)
                        ->count(),
                    ]);
                  $operationRecords = $isConsultationService ? $consultationAppointments : $serviceProviderRequests;
                  $operationTypeFor = $isConsultationService
                    ? fn ($record) => $consultationRequestType($record)
                    : ($usesMixtureTable
                      ? fn ($record) => $nutritionType($record)[0]
                      : fn ($record) => $providerRequestTypeLabel($record));
                  $operationPriorityFor = $isConsultationService
                    ? fn ($record) => $consultationPriority($record)
                    : fn ($record) => $providerRequestPriority($record);
                  $operationStatusFor = $isConsultationService
                    ? fn ($record) => $consultationStatus($record->status)
                    : fn ($record) => $nutritionOperationalStatus($record);
                  $operationDateFor = $isConsultationService
                    ? fn ($record) => $record->starts_at
                    : fn ($record) => $record->requested_at;
                  $operationTypeOptions = $operationRecords->map($operationTypeFor)->filter()->unique()->sort()->values();
                  $operationPriorityOptions = $operationRecords->map(fn ($record) => $operationPriorityFor($record)[0])->filter()->unique()->sort()->values();
                  $operationStatusOptions = $operationRecords->map(fn ($record) => $operationStatusFor($record)[0])->filter()->unique()->sort()->values();
                  $operationTotal = $operationRecords->count();
                  $operationToday = $operationRecords->filter(fn ($record) => $operationDateFor($record)?->isToday())->count();
                  $operationInProcess = $operationRecords->whereIn('status', ['confirmed', 'in_progress', 'accepted', 'preparing', 'in_route'])->count();
                  $operationCompleted = $operationRecords->whereIn('status', ['completed', 'delivered'])->count();
                  $operationPending = $operationRecords->whereIn('status', ['scheduled', 'pending', 'requested', 'draft'])->count();
                  $completedDurations = $operationRecords
                    ->whereIn('status', ['completed', 'delivered'])
                    ->map(function ($record) use ($isConsultationService) {
                      $startedAt = $isConsultationService ? $record->created_at : $record->requested_at;

                      return $startedAt && $record->updated_at ? $startedAt->diffInMinutes($record->updated_at) : null;
                    })
                    ->filter(fn ($minutes) => $minutes !== null);
                  $averageMinutes = $completedDurations->isNotEmpty() ? (int) round($completedDurations->average()) : null;
                  $averageTimeLabel = $averageMinutes === null
                    ? '--'
                    : ($averageMinutes >= 60
                      ? intdiv($averageMinutes, 60).'h '.($averageMinutes % 60).'m'
                      : $averageMinutes.' min');
                  $operationPercentage = fn (int $count) => $operationTotal > 0
                    ? number_format(($count / $operationTotal) * 100, 1).'%'
                    : '0%';
                  $operationArea = str_contains($operationSearch, 'oncolo') || str_contains($operationSearch, 'quimio')
                    ? 'oncology'
                    : (str_contains($operationSearch, 'farmac') ? 'inpatient-pharmacy' : 'nursing');
                  $operationHref = $isConsultationService
                    ? route('outpatient.dashboard', ['unit' => $unit->id])
                    : route('operational.dashboard', ['area' => $operationArea, 'section' => 'history', 'unit' => $unit->id, 'service' => $contract->service_id]);
                  $serviceActionLabel = match ($serviceKey) {
                    'medicamentos-importacion' => 'Gestionar importaciones',
                    'farmacia-digital' => 'Gestionar pedidos',
                    default => 'Abrir modulo operativo',
                  };
                ?>
                <section class="<?php echo \Illuminate\Support\Arr::toCssClasses([
                         'unit-service-panel',
                         'is-unified-service',
                         'is-consultation-service' => $isConsultationService,
                         'is-mixture-request-service' => $isNutritionService || $isChemotherapyService,
                         ]); ?>"
                         data-unit-service-panel="<?php echo e($contract->id); ?>"
                         data-unit-service-layout="<?php echo e($serviceKey); ?>"
                         data-unit-service-key="<?php echo e($serviceKey); ?>"
                         <?php if($isConsultationService): ?> data-unit-consultation-panel <?php endif; ?>
                         <?php if((string) $contract->id !== $activeServiceContractId): ?> hidden <?php endif; ?>>
                  <header class="unit-service-panel-header" data-unit-service-info>
                    <div class="unit-service-panel-copy">
                      <h2><?php echo e($serviceName); ?></h2>
                      <p><?php echo e($isConsultationService ? 'Atencion medica · Consulta Externa' : (($service?->category ?? 'Sin categoria').' · '.($service?->specialty ?? 'Servicio general'))); ?></p>
                      <div class="unit-service-panel-badges">
                        <span class="unit-native-status"><?php echo e($statusText($contract->status)); ?></span>
                        <span>1 hospital habilitado</span>
                      </div>
                    </div>
                    <div class="unit-service-panel-actions">
                      <button type="button"
                              class="unit-service-operation-toggle"
                              data-unit-service-operation-toggle
                              aria-expanded="false">Ver Operacion</button>
                      <a href="<?php echo e(route('unit.services.report', $contract)); ?>">Descargar reporte</a>
                      <button type="button" data-open-service-catalog="<?php echo e($contract->id); ?>">Informacion del contrato</button>
                    </div>
                  </header>

                  <?php if($isConsultationService): ?>
                    <section class="unit-consultation-submenu-card"
                             data-unit-consultation-submenu
                             data-unit-consultation-toolbar>
                      <div class="unit-consultation-submenu"
                           data-unit-consultation-carousel
                           role="tablist"
                           aria-label="Secciones de consulta externa">
                        <?php $__currentLoopData = $consultationMenu; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultationKey => [$consultationLabel, $consultationIcon]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                          <button type="button"
                                  class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-consultation-submenu-button', 'is-active' => $loop->first]); ?>"
                                  data-unit-consultation-tab="<?php echo e($consultationKey); ?>"
                                  role="tab"
                                  aria-selected="<?php echo e($loop->first ? 'true' : 'false'); ?>">
                            <span aria-hidden="true">
                              <?php switch($consultationIcon):
                                case ('home'): ?>
                                  <svg viewBox="0 0 24 24"><path d="m3 11 9-8 9 8"/><path d="M5 10v11h14V10"/><path d="M9 21v-7h6v7"/></svg>
                                  <?php break; ?>
                                <?php case ('calendar'): ?>
                                  <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                                  <?php break; ?>
                                <?php case ('room'): ?>
                                  <svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/><path d="M13 12h.01"/></svg>
                                  <?php break; ?>
                                <?php case ('doctor'): ?>
                                  <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/><path d="M12 14v5M9.5 16.5h5"/></svg>
                                  <?php break; ?>
                                <?php case ('specialty'): ?>
                                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg>
                                  <?php break; ?>
                                <?php case ('patients'): ?>
                                  <svg viewBox="0 0 24 24"><path d="M16 21a6 6 0 0 0-12 0"/><circle cx="10" cy="8" r="4"/><path d="M22 21a5 5 0 0 0-4-4.9M17 4.3a4 4 0 0 1 0 7.4"/></svg>
                                  <?php break; ?>
                                <?php default: ?>
                                  <svg viewBox="0 0 24 24"><path d="M6 3h12v18H6z"/><path d="M9 3V1h6v2M9 8h6M9 12h6M9 16h4"/></svg>
                              <?php endswitch; ?>
                            </span>
                            <strong><?php echo e($consultationLabel); ?></strong>
                          </button>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                      </div>
                      <button type="button"
                              class="unit-nutrition-new-request unit-consultation-submenu-action"
                              data-unit-calendar-new>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        Agendar nueva cita
                      </button>
                    </section>
                  <?php endif; ?>

                  <div data-unit-service-overview <?php if($isConsultationService): ?> data-unit-consultation-view="home" <?php endif; ?>>
                    <?php if($isConsultationService): ?>
                      <section class="unit-consultation-calendar"
                               data-unit-consultation-calendar
                               data-default-date="<?php echo e($consultationCalendarDefaultDate); ?>">
                        <div class="unit-consultation-calendar-toolbar" data-unit-service-controls>
                          <div class="unit-consultation-calendar-modes" role="tablist" aria-label="Vista del calendario">
                            <?php $__currentLoopData = ['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mes', 'list' => 'Lista']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $calendarMode => $calendarModeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                              <button type="button"
                                      class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $loop->first]); ?>"
                                      data-unit-calendar-mode="<?php echo e($calendarMode); ?>"
                                      role="tab"
                                      aria-selected="<?php echo e($loop->first ? 'true' : 'false'); ?>"><?php echo e($calendarModeLabel); ?></button>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                          </div>

                          <div class="unit-consultation-calendar-date-nav">
                            <button type="button" data-unit-calendar-previous aria-label="Fecha anterior" title="Fecha anterior">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <label>
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                              <input type="date" value="<?php echo e($consultationCalendarDefaultDate); ?>" data-unit-calendar-date aria-label="Fecha del calendario">
                            </label>
                            <button type="button" data-unit-calendar-next aria-label="Fecha siguiente" title="Fecha siguiente">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                          </div>

                          <label class="unit-consultation-calendar-filter">
                            <span>Especialidad</span>
                            <select data-unit-calendar-specialty>
                              <option value="">Todas las especialidades</option>
                              <?php $__currentLoopData = $consultationSpecialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultationSpecialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($consultationSpecialty); ?>"><?php echo e($consultationSpecialty); ?></option>
                              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                          </label>
                          <label class="unit-consultation-calendar-filter">
                            <span>Consultorio</span>
                            <select data-unit-calendar-room>
                              <option value="">Todos los consultorios</option>
                              <?php $__currentLoopData = $consultationCalendarRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $calendarRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($calendarRoom['id']); ?>"><?php echo e($calendarRoom['name']); ?></option>
                              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                          </label>
                        </div>

                        <div class="unit-consultation-calendar-content" data-unit-service-content>
                          <div class="unit-consultation-calendar-layout">
                            <div class="unit-consultation-calendar-stage" data-unit-calendar-stage aria-live="polite"></div>

                            <aside class="unit-consultation-calendar-aside">
                              <section class="unit-consultation-mini-calendar" aria-label="Calendario mensual">
                                <header>
                                  <button type="button" data-unit-calendar-mini-previous aria-label="Mes anterior" title="Mes anterior">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                  </button>
                                  <strong data-unit-calendar-mini-label></strong>
                                  <button type="button" data-unit-calendar-mini-next aria-label="Mes siguiente" title="Mes siguiente">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                  </button>
                                </header>
                                <div class="unit-consultation-mini-weekdays" aria-hidden="true">
                                  <?php $__currentLoopData = ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $weekday): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <span><?php echo e($weekday); ?></span>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <div class="unit-consultation-mini-days" data-unit-calendar-mini-days></div>
                              </section>

                              <section class="unit-consultation-calendar-summary" aria-label="Resumen del dia">
                                <h3>Resumen del dia</h3>
                                <div><span class="is-total"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg> Total de citas</span><strong data-unit-calendar-summary="total">0</strong></div>
                                <div><span><i class="is-confirmed"></i> Confirmadas</span><strong data-unit-calendar-summary="confirmed">0</strong></div>
                                <div><span><i class="is-waiting"></i> En espera</span><strong data-unit-calendar-summary="waiting">0</strong></div>
                                <div><span><i class="is-in-progress"></i> En consulta</span><strong data-unit-calendar-summary="in-progress">0</strong></div>
                                <div><span><i class="is-completed"></i> Finalizadas</span><strong data-unit-calendar-summary="completed">0</strong></div>
                                <div><span><i class="is-cancelled"></i> Canceladas</span><strong data-unit-calendar-summary="cancelled">0</strong></div>
                                <a href="<?php echo e(route('unit.services.report', $contract)); ?>">
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20h16M7 16h10M9 16V8h6v8M12 3v5"/></svg>
                                  Ver reporte del dia
                                </a>
                              </section>
                            </aside>
                          </div>

                          <footer class="unit-consultation-calendar-legend">
                            <div>
                              <span><i class="is-confirmed"></i> Confirmada</span>
                              <span><i class="is-waiting"></i> En espera</span>
                              <span><i class="is-in-progress"></i> En consulta</span>
                              <span><i class="is-completed"></i> Finalizada</span>
                              <span><i class="is-cancelled"></i> Cancelada</span>
                            </div>
                            <p><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg> Selecciona una cita para consultar sus detalles.</p>
                          </footer>
                        </div>

                        <dialog class="unit-consultation-flow-dialog unit-consultation-appointment-dialog" data-unit-calendar-dialog aria-labelledby="unit-appointment-management-title">
                          <div class="unit-consultation-flow-shell">
                            <header class="unit-consultation-flow-header">
                              <span class="unit-consultation-flow-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                              </span>
                              <div>
                                <h3 id="unit-appointment-management-title">Gestionar cita existente</h3>
                                <p>Consulta y administra los detalles de la cita</p>
                              </div>
                              <button type="button" class="unit-consultation-flow-close" data-unit-calendar-dialog-close aria-label="Cerrar" title="Cerrar">&times;</button>
                            </header>

                            <section class="unit-consultation-appointment-patient" aria-label="Paciente de la cita">
                              <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                              <div>
                                <strong data-unit-calendar-dialog-field="patient"></strong>
                                <p><span data-unit-calendar-dialog-field="patient_sex"></span><i>|</i><span data-unit-calendar-dialog-field="patient_age"></span><i>|</i><span>CURP: <b data-unit-calendar-dialog-field="patient_curp"></b></span></p>
                                <p><span>Expediente: <b data-unit-calendar-dialog-field="patient_platform"></b></span><i>|</i><span>Tel: <b data-unit-calendar-dialog-field="patient_phone"></b></span></p>
                              </div>
                            </section>

                            <div class="unit-consultation-appointment-tabs" role="tablist" aria-label="Detalle de la cita">
                              <button type="button" class="is-active" role="tab" id="unit-appointment-information-tab" aria-selected="true" aria-controls="unit-appointment-information-panel" data-unit-calendar-dialog-tab="information">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6z"/><path d="M15 3v4h4M9 12h6M9 16h6"/></svg>
                                Informaci&oacute;n
                              </button>
                              <button type="button" role="tab" id="unit-appointment-history-tab" aria-selected="false" aria-controls="unit-appointment-history-panel" data-unit-calendar-dialog-tab="history">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                                Historial
                              </button>
                            </div>

                            <section id="unit-appointment-information-panel" role="tabpanel" aria-labelledby="unit-appointment-information-tab" data-unit-calendar-dialog-panel="information">
                              <dl class="unit-consultation-detail-list">
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h9l3 3v15H6z"/><path d="M15 3v4h4M9 11h6M9 15h6"/></svg>Folio</dt><dd data-unit-calendar-dialog-field="folio"></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M6 20a6 6 0 0 1 12 0"/></svg>Tipo de consulta</dt><dd data-unit-calendar-dialog-field="modality"></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/></svg>Especialidad</dt><dd data-unit-calendar-dialog-field="specialty"></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"/><path d="M6 20a6 6 0 0 1 12 0"/></svg>M&eacute;dico</dt><dd><strong data-unit-calendar-dialog-field="doctor"></strong><small data-unit-calendar-dialog-field="doctor_license"></small></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h14v18H5z"/><path d="M9 3v18M12 12h.01"/></svg>Consultorio</dt><dd data-unit-calendar-dialog-field="room"></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>Fecha y hora</dt><dd data-unit-calendar-dialog-field="date_time"></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="m8 12 3 3 5-6"/></svg>Estatus</dt><dd><span class="unit-consultation-status-chip" data-unit-calendar-dialog-field="status"></span></dd></div>
                                <div><dt><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h10v17H7z"/><path d="M9 2h6v4H9zM10 11h4M10 15h4"/></svg>Notas</dt><dd data-unit-calendar-dialog-field="notes"></dd></div>
                              </dl>
                            </section>

                            <section class="unit-consultation-appointment-history" id="unit-appointment-history-panel" role="tabpanel" aria-labelledby="unit-appointment-history-tab" data-unit-calendar-dialog-panel="history" hidden>
                              <ol data-unit-calendar-dialog-history></ol>
                            </section>

                            <footer class="unit-consultation-appointment-footer">
                              <div class="unit-consultation-flow-actions">
                                <button type="button" class="is-primary" data-unit-calendar-edit>
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 4-1 10-10-3-3L5 16l-1 4Z"/><path d="m13 8 3 3"/></svg>
                                  Editar cita
                                </button>
                                <button type="button" data-unit-calendar-reschedule>
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                                  Reprogramar
                                </button>
                                <button type="button" data-unit-calendar-reminder>
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 18-8-8 18-2-8-8-2Z"/><path d="m11 13 4-4"/></svg>
                                  Enviar recordatorio
                                </button>
                                <button type="button" class="is-danger-outline" data-unit-calendar-cancel>
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg>
                                  Cancelar cita
                                </button>
                              </div>
                              <p class="unit-consultation-reminder-feedback" data-unit-calendar-reminder-feedback role="status" hidden></p>
                              <p class="unit-consultation-appointment-warning">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/></svg>
                                <span>Al cancelar la cita podr&aacute;s registrar el motivo de cancelaci&oacute;n.<small>Esta informaci&oacute;n quedar&aacute; en el historial del paciente.</small></span>
                              </p>
                            </footer>
                          </div>
                        </dialog>

                        <dialog class="unit-consultation-flow-dialog" data-unit-calendar-edit-dialog aria-labelledby="unit-appointment-edit-title">
                          <form method="post"
                                class="unit-consultation-flow-shell"
                                data-unit-calendar-edit-form
                                data-action-template="<?php echo e(route('unit.appointments.update', ['appointment' => '__appointment__'])); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <input type="hidden" name="unit" value="<?php echo e($unit->id); ?>">
                            <input type="hidden" name="patient_id">
                            <input type="hidden" name="specialty">
                            <input type="hidden" name="modality">
                            <input type="hidden" name="duration">
                            <input type="hidden" name="reason">

                            <header class="unit-consultation-flow-header">
                              <span class="unit-consultation-flow-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/><path d="m9 16 5-5 2 2-5 5H9v-2Z"/></svg>
                              </span>
                              <div>
                                <h3 id="unit-appointment-edit-title" data-unit-calendar-edit-title>Editar cita</h3>
                                <p>Modifica la informaci&oacute;n de la cita seleccionada.</p>
                              </div>
                              <button type="button" class="unit-consultation-flow-close" data-unit-calendar-edit-close aria-label="Cerrar" title="Cerrar">&times;</button>
                            </header>

                            <div class="unit-consultation-form-grid">
                              <label><span>Fecha <b>*</b></span><input type="date" name="appointment_date" required></label>
                              <label><span>Hora <b>*</b></span><input type="time" name="appointment_time" step="1800" required></label>
                              <label>
                                <span>M&eacute;dico <b>*</b></span>
                                <select name="doctor_id" required>
                                  <?php $__currentLoopData = $consultationCalendarDoctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $calendarDoctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($calendarDoctor['id']); ?>"><?php echo e($calendarDoctor['name']); ?></option>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                              </label>
                              <label>
                                <span>N&uacute;mero de consultorio <b>*</b></span>
                                <select name="procedure_area_id" required>
                                  <?php $__currentLoopData = $consultationCalendarRooms->where('schedulable', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $calendarRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($calendarRoom['id']); ?>"><?php echo e($calendarRoom['name']); ?></option>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                              </label>
                              <label class="is-wide">
                                <span>Estatus <b>*</b></span>
                                <select name="status" required>
                                  <option value="scheduled">En espera</option>
                                  <option value="confirmed">Confirmada</option>
                                  <option value="in_progress">En consulta</option>
                                  <option value="completed">Finalizada</option>
                                </select>
                              </label>
                              <label class="is-wide">
                                <span>Notas</span>
                                <textarea name="notes" maxlength="500" rows="4" placeholder="Agrega indicaciones o comentarios para la cita."></textarea>
                              </label>
                            </div>

                            <footer class="unit-consultation-flow-actions">
                              <button type="button" class="is-secondary" data-unit-calendar-edit-close>Descartar cambios</button>
                              <button type="submit" class="is-primary">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h12l2 2v16H5z"/><path d="M8 3v6h8V3M9 16h6"/></svg>
                                Guardar cambios
                              </button>
                            </footer>
                          </form>
                        </dialog>

                        <dialog class="unit-consultation-flow-dialog unit-consultation-cancel-dialog" data-unit-calendar-cancel-dialog aria-labelledby="unit-appointment-cancel-title">
                          <form method="post"
                                class="unit-consultation-flow-shell"
                                data-unit-calendar-cancel-form
                                data-action-template="<?php echo e(route('unit.appointments.status', ['appointment' => '__appointment__'])); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <input type="hidden" name="unit" value="<?php echo e($unit->id); ?>">
                            <input type="hidden" name="status" value="cancelled">

                            <header class="unit-consultation-flow-header is-centered">
                              <span class="unit-consultation-warning-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M12 3 2.8 20h18.4L12 3Z"/><path d="M12 9v5M12 17h.01"/></svg>
                              </span>
                              <button type="button" class="unit-consultation-flow-close" data-unit-calendar-cancel-close aria-label="Cerrar" title="Cerrar">&times;</button>
                              <h3 id="unit-appointment-cancel-title">&iquest;Deseas cancelar esta cita?</h3>
                              <p>Esta acci&oacute;n no se puede deshacer. El horario quedar&aacute; disponible para otros pacientes.</p>
                            </header>

                            <dl class="unit-consultation-cancel-summary">
                              <div><dt>Paciente</dt><dd data-unit-calendar-cancel-field="patient"></dd></div>
                              <div><dt>Fecha</dt><dd data-unit-calendar-cancel-field="date"></dd></div>
                              <div><dt>Hora</dt><dd data-unit-calendar-cancel-field="time"></dd></div>
                              <div><dt>Consultorio</dt><dd data-unit-calendar-cancel-field="room"></dd></div>
                              <div><dt>M&eacute;dico</dt><dd data-unit-calendar-cancel-field="doctor"></dd></div>
                            </dl>

                            <label class="unit-consultation-cancel-reason">
                              <span>Motivo de cancelaci&oacute;n <b>*</b></span>
                              <textarea name="notes" required maxlength="500" rows="4" placeholder="Escribe el motivo de la cancelaci&oacute;n..."></textarea>
                            </label>

                            <footer class="unit-consultation-flow-actions">
                              <button type="button" class="is-secondary" data-unit-calendar-cancel-close>Volver</button>
                              <button type="submit" class="is-danger">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg>
                                Confirmar cancelaci&oacute;n
                              </button>
                            </footer>
                          </form>
                        </dialog>

                        <dialog class="unit-consultation-flow-dialog unit-consultation-new-dialog"
                                data-unit-calendar-new-dialog
                                aria-labelledby="unit-new-appointment-title"
                                <?php if($errors->any() && old('patient_id')): ?> data-open-on-load <?php endif; ?>>
                          <form method="post" action="<?php echo e(route('unit.appointments.store')); ?>" class="unit-consultation-flow-shell" data-unit-calendar-new-form>
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="unit" value="<?php echo e($unit->id); ?>">
                            <input type="hidden" name="patient_id" value="<?php echo e(old('patient_id')); ?>" data-unit-new-field="patient_id">

                            <header class="unit-consultation-flow-header">
                              <span class="unit-consultation-flow-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14M12 12v6M9 15h6"/></svg>
                              </span>
                              <div>
                                <h3 id="unit-new-appointment-title">Agendar nueva cita</h3>
                                <p>Completa la informaci&oacute;n para programar la cita del paciente.</p>
                              </div>
                              <button type="button" class="unit-consultation-flow-close" data-unit-calendar-new-close aria-label="Cerrar" title="Cerrar">&times;</button>
                            </header>

                            <p class="unit-consultation-flow-error" data-unit-new-error <?php if(! ($errors->any() && old('patient_id'))): ?> hidden <?php endif; ?>><?php echo e($errors->first()); ?></p>

                            <div class="unit-consultation-booking-layout" data-unit-new-reference-layout>
                              <div class="unit-consultation-booking-column is-main">
                                <section class="unit-consultation-booking-section is-patient">
                                  <header class="unit-consultation-booking-section-head">
                                    <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                                    <h4>Paciente</h4>
                                  </header>
                                  <label class="unit-consultation-patient-search" data-unit-patient-autocomplete="search">
                                    <span>Buscar paciente</span>
                                    <span class="unit-consultation-search-control">
                                      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                                      <input type="search"
                                             data-unit-new-patient-search
                                             role="combobox"
                                             aria-autocomplete="list"
                                             aria-haspopup="listbox"
                                             aria-expanded="false"
                                             aria-controls="unit-new-patient-results"
                                             autocomplete="off"
                                             placeholder="Nombre, CURP o expediente...">
                                    </span>
                                    <span class="unit-consultation-patient-results"
                                          id="unit-new-patient-results"
                                          data-unit-new-patient-results
                                          role="listbox"
                                          aria-label="Pacientes sugeridos"
                                          aria-live="polite"
                                          hidden></span>
                                  </label>
                                  <label class="unit-consultation-platform-field" data-unit-patient-autocomplete="platform">
                                    <span>N&uacute;mero de ID de la plataforma <b>*</b></span>
                                    <input type="text"
                                           name="platform_number"
                                           value="<?php echo e(old('platform_number')); ?>"
                                           data-unit-new-field="platform_number"
                                           data-unit-new-platform-number
                                           role="combobox"
                                           aria-autocomplete="list"
                                           aria-haspopup="listbox"
                                           aria-expanded="false"
                                           aria-controls="unit-new-platform-results"
                                           maxlength="80"
                                           autocomplete="off"
                                           placeholder="Ej. 100000001"
                                           required>
                                    <span class="unit-consultation-patient-results is-platform"
                                          id="unit-new-platform-results"
                                          data-unit-new-platform-results
                                          role="listbox"
                                          aria-label="Identificadores de plataforma sugeridos"
                                          aria-live="polite"
                                          hidden></span>
                                  </label>
                                  <div class="unit-consultation-selected-patient" data-unit-new-selected-patient hidden>
                                    <span class="unit-consultation-patient-avatar" data-unit-new-selected="patient_initials" aria-hidden="true"></span>
                                    <div><strong data-unit-new-selected="patient"></strong><small data-unit-new-selected="patient_meta"></small></div>
                                    <button type="button" data-unit-new-patient-clear aria-label="Cambiar paciente" title="Cambiar paciente">&times;</button>
                                  </div>
                                  <button type="button" class="unit-consultation-patient-details-toggle" data-unit-new-patient-details-toggle aria-expanded="false" hidden>
                                    Ver m&aacute;s detalles del paciente <span aria-hidden="true">&#8964;</span>
                                  </button>
                                  <dl class="unit-consultation-patient-details" data-unit-new-patient-details hidden>
                                    <div><dt>ID plataforma</dt><dd data-unit-new-patient-detail="platform_number"></dd></div>
                                    <div><dt>CURP</dt><dd data-unit-new-patient-detail="curp"></dd></div>
                                    <div><dt>NSS</dt><dd data-unit-new-patient-detail="nss"></dd></div>
                                    <div><dt>Contacto</dt><dd data-unit-new-patient-detail="contact"></dd></div>
                                  </dl>
                                </section>

                                <hr class="unit-consultation-booking-separator">

                                <section class="unit-consultation-booking-section is-consultation-data">
                                  <header class="unit-consultation-booking-section-head">
                                    <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg></span>
                                    <h4>Datos de la consulta</h4>
                                  </header>
                                  <div class="unit-consultation-field-label">Modalidad</div>
                                  <div class="unit-consultation-modality-options">
                                    <label>
                                      <input type="radio" name="modality" value="Presencial" <?php if(old('modality', 'Presencial') === 'Presencial'): echo 'checked'; endif; ?>>
                                      <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v16"/><path d="M8 7h3M8 11h3M8 15h3M15 21v-5h2v5"/></svg></span>
                                      <strong>Presencial</strong>
                                      <i aria-hidden="true"></i>
                                    </label>
                                    <label>
                                      <input type="radio" name="modality" value="Video llamada" <?php if(old('modality') === 'Video llamada'): echo 'checked'; endif; ?>>
                                      <span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3"/></svg></span>
                                      <strong>Teleconsulta</strong>
                                      <i aria-hidden="true"></i>
                                    </label>
                                  </div>
                                  <div class="unit-consultation-details-grid">
                                    <label><span>Especialidad <b>*</b></span><select name="specialty" data-unit-new-field="specialty" required><option value="">Selecciona una especialidad</option><?php $__currentLoopData = $consultationSpecialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultationSpecialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($consultationSpecialty); ?>" <?php if(old('specialty') === $consultationSpecialty): echo 'selected'; endif; ?>><?php echo e($consultationSpecialty); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                                    <label><span>Profesional de salud <b>*</b></span><select name="doctor_id" data-unit-new-field="doctor_id" data-old-value="<?php echo e(old('doctor_id')); ?>" required disabled><option value="">Selecciona un profesional</option></select></label>
                                    <label class="is-wide"><span>Consultorio <b>*</b></span><select name="procedure_area_id" data-unit-new-field="procedure_area_id" required><option value="">Selecciona un consultorio</option><?php $__currentLoopData = $consultationCalendarRooms->where('schedulable', true); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $calendarRoom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($calendarRoom['id']); ?>" <?php if((string) old('procedure_area_id') === (string) $calendarRoom['id']): echo 'selected'; endif; ?>><?php echo e($calendarRoom['name']); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                                    <label><span>Prioridad <b>*</b></span><select name="priority" data-unit-new-field="priority" required><option value="routine" <?php if(old('priority', 'routine') === 'routine'): echo 'selected'; endif; ?>>Rutina</option><option value="urgent" <?php if(old('priority') === 'urgent'): echo 'selected'; endif; ?>>Urgente</option></select></label>
                                    <label><span>Duraci&oacute;n <b>*</b></span><select name="duration" data-unit-new-field="duration" required><?php $__currentLoopData = [20, 30, 45, 60]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $duration): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($duration); ?>" <?php if((int) old('duration', 30) === $duration): echo 'selected'; endif; ?>><?php echo e($duration); ?> minutos</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
                                    <label class="is-wide"><span>Motivo de la consulta <b>*</b></span><textarea name="reason" data-unit-new-field="reason" maxlength="500" rows="2" required placeholder="Escribe brevemente el motivo de la cita"><?php echo e(old('reason')); ?></textarea><small><span data-unit-new-counter="reason"><?php echo e(mb_strlen((string) old('reason'))); ?></span>/500</small></label>
                                    <label class="is-wide"><span>Notas adicionales</span><textarea name="notes" maxlength="500" rows="2" placeholder="Indicaciones o informaci&oacute;n adicional..."><?php echo e(old('notes')); ?></textarea><small><span data-unit-new-counter="notes"><?php echo e(mb_strlen((string) old('notes'))); ?></span>/500</small></label>
                                  </div>
                                  <div class="unit-consultation-modality-info" data-unit-new-modality-info>
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l2-2a5 5 0 0 0-7-7l-1.1 1.1"/><path d="M14 11a5 5 0 0 0-7.5-.5l-2 2a5 5 0 0 0 7 7l1.1-1.1"/></svg>
                                    <span></span>
                                  </div>
                                </section>
                              </div>

                              <aside class="unit-consultation-booking-column is-schedule" aria-label="Fecha, horario y confirmacion">
                                <section class="unit-consultation-booking-section is-date-time">
                                  <header class="unit-consultation-booking-section-head">
                                    <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg></span>
                                    <h4>Fecha y horario</h4>
                                  </header>
                                  <label class="unit-consultation-date-field"><span>Fecha de la cita <b>*</b></span><input type="date" name="appointment_date" data-unit-new-field="appointment_date" min="<?php echo e(now()->toDateString()); ?>" value="<?php echo e(old('appointment_date', now()->toDateString())); ?>" required></label>
                                  <p class="unit-consultation-timezone">Hora de Ciudad de M&eacute;xico &middot; UTC-6</p>
                                  <div class="unit-consultation-booking-week" data-unit-new-week aria-label="D&iacute;as de la semana"></div>
                                  <div class="unit-consultation-slots-head"><span>Horarios disponibles</span><strong data-unit-new-slot-count>0 opciones</strong></div>
                                  <div class="unit-consultation-available-slots"><div data-unit-new-slots><p class="unit-consultation-no-slots">Selecciona especialidad, profesional, consultorio y fecha para consultar horarios.</p></div></div>
                                  <label class="unit-consultation-time-select"><span>Hora seleccionada</span><select name="appointment_time" data-unit-new-field="appointment_time" data-unit-new-time data-old-value="<?php echo e(old('appointment_time')); ?>" aria-required="true" tabindex="-1" disabled><option value="">Selecciona un horario disponible</option></select></label>
                                  <p class="unit-consultation-slot-legend">Los horarios tachados no est&aacute;n disponibles.</p>
                                </section>

                                <hr class="unit-consultation-booking-separator">

                                <section class="unit-consultation-booking-section is-confirmation">
                                  <header class="unit-consultation-booking-section-head">
                                    <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg></span>
                                    <h4>Confirmaci&oacute;n al paciente</h4>
                                  </header>
                                  <fieldset class="unit-consultation-notifications">
                                    <legend class="unit-visually-hidden">Canales de confirmaci&oacute;n</legend>
                                    <label><input type="hidden" name="notify_email" value="0"><input type="checkbox" name="notify_email" value="1" <?php if((bool) old('notify_email', true)): echo 'checked'; endif; ?>><span>Enviar confirmaci&oacute;n por correo<small data-unit-new-notification-note>Incluye los datos de la consulta.</small></span></label>
                                    <label><input type="hidden" name="notify_sms" value="0"><input type="checkbox" name="notify_sms" value="1" <?php if((bool) old('notify_sms', true)): echo 'checked'; endif; ?>><span>Enviar confirmaci&oacute;n por SMS<small>Se utilizar&aacute; el tel&eacute;fono registrado.</small></span></label>
                                  </fieldset>
                                  <label class="unit-consultation-contact-field"><span>Correo electr&oacute;nico</span><input type="text" data-unit-new-contact-email readonly placeholder="Selecciona un paciente"></label>
                                  <p class="unit-consultation-contact-note">Se utilizar&aacute; &uacute;nicamente para esta cita.</p>
                                </section>
                              </aside>
                            </div>

                            <footer class="unit-consultation-booking-actions">
                              <div class="unit-consultation-booking-summary" aria-live="polite">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/><path d="m9 15 2 2 4-4"/></svg>
                                <div><strong data-unit-new-summary="primary">Elige una fecha y un horario</strong><small data-unit-new-summary="detail">Presencial &middot; 30 min</small></div>
                              </div>
                              <div class="unit-consultation-booking-action-buttons">
                                <button type="button" class="is-secondary" data-unit-new-reset>Limpiar</button>
                                <button type="submit" class="is-primary"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>Agendar cita</button>
                              </div>
                            </footer>
                          </form>
                        </dialog>
                      </section>
                    <?php elseif($isNutritionService || $isChemotherapyService): ?>
                      <section class="unit-nutrition-request-board"
                               data-unit-nutrition-requests
                               data-unit-request-board
                               data-request-service="<?php echo e($isChemotherapyService ? 'chemotherapy' : 'nutrition'); ?>">
                        <div class="unit-nutrition-request-toolbar" data-unit-service-controls>
                          <div class="unit-nutrition-request-tabs"
                               role="tablist"
                               aria-label="Filtrar solicitudes de <?php echo e($isChemotherapyService ? 'quimioterapia' : 'nutricion parenteral'); ?>">
                            <button type="button" class="is-active" data-unit-nutrition-filter="all" data-unit-request-filter="all" role="tab" aria-selected="true">Todos</button>
                            <button type="button" data-unit-nutrition-filter="pending" data-unit-request-filter="pending" role="tab" aria-selected="false">
                              Pendientes <span><?php echo e($nutritionRequestCounts->get('pending', 0)); ?></span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="preparing" data-unit-request-filter="preparing" role="tab" aria-selected="false">
                              En preparaci&oacute;n <span><?php echo e($nutritionRequestCounts->get('preparing', 0)); ?></span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="route" data-unit-request-filter="route" role="tab" aria-selected="false">
                              En ruta <span><?php echo e($nutritionRequestCounts->get('route', 0)); ?></span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="delivered" data-unit-request-filter="delivered" role="tab" aria-selected="false">
                              Entregadas <span><?php echo e($nutritionRequestCounts->get('delivered', 0)); ?></span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="history" data-unit-request-filter="history" role="tab" aria-selected="false">Historial</button>
                          </div>
                          <?php if($isNutritionService): ?>
                            <button type="button" class="unit-nutrition-new-request" data-unit-nutrition-open>
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                              Solicitud de mezcla
                            </button>
                          <?php else: ?>
                            <a class="unit-nutrition-new-request"
                               data-unit-chemotherapy-request
                               href="<?php echo e(route('operational.dashboard', [
                                 'area' => 'oncology',
                                 'section' => 'calendar',
                                 'oncology_track' => 'infusions',
                                 'unit' => $unit->id,
                                 'new_infusion' => 1,
                               ])); ?>">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                              Solicitud de mezcla
                            </a>
                          <?php endif; ?>
                        </div>

                        <div class="unit-nutrition-request-table-wrap" data-unit-service-content data-drsam-table-filter-skip>
                          <table class="unit-native-table unit-nutrition-request-table">
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
                                <th>Lote</th>
                                <th>Ver</th>
                                <th>Aprobaci&oacute;n</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php $__empty_1 = true; $__currentLoopData = $serviceProviderRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nutritionRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
                                  [$nutritionTypeLabel, $nutritionTypeClass] = $nutritionType($nutritionRequest);
                                  [$nutritionStatusLabel, $nutritionStatusClass] = $nutritionOperationalStatus($nutritionRequest);
                                  [$nutritionApprovalLabel, $nutritionApprovalClass] = $nutritionApprovalStatus($nutritionRequest);
                                  $nutritionCategory = $nutritionFilterCategory($nutritionRequest);
                                  $nutritionMixId = $providerPayloadValue(data_get($nutritionRequest->payload, 'mix_id')
                                    ?? data_get($nutritionRequest->payload, 'mixture_id')
                                    ?? data_get($nutritionRequest->payload, 'clinical_format.mix_id')
                                    ?? $nutritionRequest->id);
                                  $nutritionRequestNumber = $providerPayloadValue(data_get($nutritionRequest->payload, 'request_number')
                                    ?? data_get($nutritionRequest->payload, 'request_no')
                                    ?? $nutritionRequest->external_id
                                    ?? ($isChemotherapyService ? 'QT-' : 'NPT-').str_pad((string) $nutritionRequest->id, 4, '0', STR_PAD_LEFT));
                                  $nutritionLot = $providerPayloadValue(data_get($nutritionRequest->payload, 'lot')
                                    ?? data_get($nutritionRequest->payload, 'batch')
                                    ?? data_get($nutritionRequest->payload, 'remission.lot')
                                    ?? data_get($nutritionRequest->payload, 'remission.batch'));
                                  $nutritionHospital = $nutritionRequest->medicalUnit?->name ?? $unit->name;
                                  $nutritionRoute = route('operational.dashboard', [
                                    'area' => $isChemotherapyService ? 'oncology' : 'nursing',
                                    'section' => 'support',
                                    'unit' => $unit->id,
                                    'request' => $nutritionRequest->id,
                                  ]);
                                ?>
                                <tr data-unit-nutrition-row data-unit-request-row data-category="<?php echo e($nutritionCategory); ?>">
                                  <td><span class="unit-consultation-chip is-type-<?php echo e($nutritionTypeClass); ?>"><?php echo e($nutritionTypeLabel); ?></span></td>
                                  <td><strong><?php echo e($nutritionMixId); ?></strong></td>
                                  <td><strong><?php echo e($nutritionRequestNumber); ?></strong></td>
                                  <td><strong><?php echo e($nutritionHospital); ?></strong><small><?php echo e($unit->clues ?? $unit->code ?? 'Sin CLUES'); ?></small></td>
                                  <td><strong><?php echo e($nutritionRequest->patient?->full_name ?? 'Sin paciente'); ?></strong></td>
                                  <td><?php echo e($nutritionRequest->requested_at?->format('d/m/Y H:i') ?? '-'); ?></td>
                                  <td><?php echo e($nutritionRequest->required_at?->format('d/m/Y H:i') ?? '-'); ?></td>
                                  <td>
                                    <button type="button"
                                            class="unit-consultation-chip is-status-<?php echo e($nutritionStatusClass); ?>"
                                            data-unit-nutrition-status="<?php echo e($nutritionCategory); ?>"><?php echo e($nutritionStatusLabel); ?></button>
                                  </td>
                                  <td><?php echo e($nutritionLot); ?></td>
                                  <td>
                                    <a class="unit-nutrition-request-view" href="<?php echo e($nutritionRoute); ?>" aria-label="Ver solicitud <?php echo e($nutritionRequestNumber); ?>">
                                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                      Ver
                                    </a>
                                  </td>
                                  <td><span class="unit-nutrition-approval is-<?php echo e($nutritionApprovalClass); ?>"><i aria-hidden="true"></i><?php echo e($nutritionApprovalLabel); ?></span></td>
                                </tr>
                              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr data-unit-nutrition-empty data-unit-request-empty>
                                  <td colspan="11"><?php echo e($isChemotherapyService ? 'No se encontraron solicitudes de quimioterapia.' : 'No se encontraron solicitudes.'); ?></td>
                                </tr>
                              <?php endif; ?>
                              <?php if($serviceProviderRequests->isNotEmpty()): ?>
                                <tr data-unit-nutrition-no-results data-unit-request-no-results hidden><td colspan="11">No se encontraron solicitudes para este filtro.</td></tr>
                              <?php endif; ?>
                            </tbody>
                          </table>
                        </div>

                        <?php if($isNutritionService): ?>
                          <dialog class="unit-consultation-flow-dialog unit-nutrition-request-dialog"
                                  data-unit-nutrition-dialog
                                  aria-labelledby="unit-nutrition-request-title-<?php echo e($contract->id); ?>"
                                  <?php if(old('request_context') === 'nutrition_mixture' && (string) old('service_contract_id') === (string) $contract->id): ?> data-open-on-load <?php endif; ?>>
                            <form method="post" action="<?php echo e(route('unit.nutrition-requests.store')); ?>" class="unit-consultation-flow-shell">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="unit" value="<?php echo e($unit->id); ?>">
                            <input type="hidden" name="service_contract_id" value="<?php echo e($contract->id); ?>">
                            <input type="hidden" name="request_context" value="nutrition_mixture">

                            <header class="unit-consultation-flow-header">
                              <span class="unit-consultation-flow-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M8 2h8M9 2v6l-4 9a4 4 0 0 0 3.7 5h6.6A4 4 0 0 0 19 17l-4-9V2"/><path d="M8 14h8"/></svg>
                              </span>
                              <div>
                                <h3 id="unit-nutrition-request-title-<?php echo e($contract->id); ?>">Solicitud de mezcla</h3>
                                <p>Nutrici&oacute;n Parenteral</p>
                              </div>
                              <button type="button" class="unit-consultation-flow-close" data-unit-nutrition-close aria-label="Cerrar" title="Cerrar">&times;</button>
                            </header>

                            <div class="unit-consultation-form-grid unit-nutrition-request-form-grid">
                              <label>
                                <span>Paciente <b>*</b></span>
                                <select name="patient_id" required>
                                  <option value="">Selecciona un paciente</option>
                                  <?php $__currentLoopData = $consultationPatientCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nutritionPatient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($nutritionPatient->id); ?>" <?php if((string) old('patient_id') === (string) $nutritionPatient->id): echo 'selected'; endif; ?>>
                                      <?php echo e($nutritionPatient->full_name); ?><?php echo e($nutritionPatient->platform_number ? ' - '.$nutritionPatient->platform_number : ''); ?>

                                    </option>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                              </label>
                              <label>
                                <span>M&eacute;dico responsable <b>*</b></span>
                                <select name="doctor_id" required>
                                  <option value="">Selecciona un m&eacute;dico</option>
                                  <?php $__currentLoopData = $unit->doctors->where('status', 'active'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nutritionDoctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($nutritionDoctor->id); ?>" <?php if((string) old('doctor_id') === (string) $nutritionDoctor->id): echo 'selected'; endif; ?>>
                                      <?php echo e($nutritionDoctor->full_name); ?><?php echo e($nutritionDoctor->specialty ? ' - '.$nutritionDoctor->specialty : ''); ?>

                                    </option>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                              </label>
                              <label><span>Servicio cl&iacute;nico <b>*</b></span><input name="clinical_service" value="<?php echo e(old('clinical_service', 'Nutricion clinica')); ?>" maxlength="180" required></label>
                              <label>
                                <span>Prioridad <b>*</b></span>
                                <select name="priority" required>
                                  <option value="routine" <?php if(old('priority', 'routine') === 'routine'): echo 'selected'; endif; ?>>Rutina</option>
                                  <option value="urgent" <?php if(old('priority') === 'urgent'): echo 'selected'; endif; ?>>Urgente</option>
                                </select>
                              </label>
                              <label><span>Fecha y hora de entrega <b>*</b></span><input type="datetime-local" name="delivery_at" min="<?php echo e(now()->format('Y-m-d\TH:i')); ?>" value="<?php echo e(old('delivery_at', now()->addDay()->format('Y-m-d\TH:i'))); ?>" required></label>
                              <label>
                                <span>V&iacute;a de administraci&oacute;n <b>*</b></span>
                                <select name="route" required>
                                  <option value="Central" <?php if(old('route', 'Central') === 'Central'): echo 'selected'; endif; ?>>Central</option>
                                  <option value="Periferica" <?php if(old('route') === 'Periferica'): echo 'selected'; endif; ?>>Perif&eacute;rica</option>
                                </select>
                              </label>
                              <label>
                                <span>Tipo de NPT <b>*</b></span>
                                <select name="npt_type" required>
                                  <option value="Individualizada" <?php if(old('npt_type', 'Individualizada') === 'Individualizada'): echo 'selected'; endif; ?>>Individualizada</option>
                                  <option value="Tricamara" <?php if(old('npt_type') === 'Tricamara'): echo 'selected'; endif; ?>>Tric&aacute;mara</option>
                                  <option value="Pediatrica" <?php if(old('npt_type') === 'Pediatrica'): echo 'selected'; endif; ?>>Pedi&aacute;trica</option>
                                </select>
                              </label>
                              <label><span>Volumen total (ml) <b>*</b></span><input type="number" name="total_volume" min="0.01" max="100000" step="0.01" value="<?php echo e(old('total_volume')); ?>" required></label>
                              <label><span>Tiempo de infusi&oacute;n (h) <b>*</b></span><input type="number" name="infusion_hours" min="0.01" max="168" step="0.01" value="<?php echo e(old('infusion_hours', 24)); ?>" required></label>
                              <label class="is-wide"><span>Diagn&oacute;stico <b>*</b></span><textarea name="diagnosis" maxlength="2000" rows="3" required placeholder="Describe el diagn&oacute;stico cl&iacute;nico..."><?php echo e(old('diagnosis')); ?></textarea></label>
                              <label class="is-wide"><span>Componentes de la mezcla</span><textarea name="components" maxlength="3000" rows="3" placeholder="Detalla amino&aacute;cidos, l&iacute;pidos, glucosa, electrolitos y otros componentes..."><?php echo e(old('components')); ?></textarea></label>
                              <label class="is-wide"><span>Indicaciones y observaciones</span><textarea name="notes" maxlength="3000" rows="3" placeholder="Agrega indicaciones especiales para preparaci&oacute;n o entrega..."><?php echo e(old('notes')); ?></textarea></label>
                            </div>

                            <footer class="unit-consultation-flow-actions">
                              <button type="button" class="is-secondary" data-unit-nutrition-close>Cancelar</button>
                              <button type="submit" class="is-primary">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3h12l2 2v16H5z"/><path d="M8 3v6h8V3M9 16h6"/></svg>
                                Enviar solicitud
                              </button>
                            </footer>
                            </form>
                          </dialog>
                        <?php endif; ?>
                      </section>
                    <?php else: ?>
                      <section class="unit-service-generic-board" data-unit-request-board>
                        <div class="unit-nutrition-request-toolbar" data-unit-service-controls>
                          <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar solicitudes de <?php echo e($serviceName); ?>">
                            <button type="button" class="is-active" data-unit-request-filter="all" role="tab" aria-selected="true">Todos</button>
                            <button type="button" data-unit-request-filter="pending" role="tab" aria-selected="false">
                              Pendientes <span><?php echo e($nutritionRequestCounts->get('pending', 0)); ?></span>
                            </button>
                            <button type="button" data-unit-request-filter="preparing,route" role="tab" aria-selected="false">
                              En proceso <span><?php echo e($nutritionRequestCounts->get('preparing', 0) + $nutritionRequestCounts->get('route', 0)); ?></span>
                            </button>
                            <button type="button" data-unit-request-filter="delivered" role="tab" aria-selected="false">
                              Completadas <span><?php echo e($nutritionRequestCounts->get('delivered', 0)); ?></span>
                            </button>
                            <button type="button" data-unit-request-filter="history" role="tab" aria-selected="false">Historial</button>
                          </div>
                          <a class="unit-nutrition-new-request" href="<?php echo e($operationHref); ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M14 7l5 5-5 5"/></svg>
                            <?php echo e($serviceActionLabel); ?>

                          </a>
                        </div>

                        <div class="unit-nutrition-request-table-wrap unit-service-generic-table-wrap" data-unit-service-content data-drsam-table-filter-skip>
                          <table class="unit-native-table unit-service-generic-request-table">
                          <thead>
                            <tr>
                              <th>Solicitud</th>
                              <th>Tipo</th>
                              <th>Hospital</th>
                              <th>Paciente</th>
                              <th>Fecha y hora</th>
                              <th>Prioridad</th>
                              <th>Responsable</th>
                              <th>Estatus</th>
                              <th>Ver</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $serviceProviderRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceProviderRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                              <?php
                                $genericCategory = $nutritionFilterCategory($serviceProviderRequest);
                                $genericFolio = $serviceProviderRequest->external_id
                                  ?? strtoupper(str($serviceKey)->limit(4, '')->toString()).'-'.str_pad((string) $serviceProviderRequest->id, 5, '0', STR_PAD_LEFT);
                                $genericType = match ($serviceKey) {
                                  'medicamentos-importacion' => 'Importacion',
                                  'farmacia-digital' => 'Pedido y entrega',
                                  default => $providerRequestTypeLabel($serviceProviderRequest),
                                };
                                [$genericPriorityLabel, $genericPriorityClass] = $providerRequestPriority($serviceProviderRequest);
                                [$genericStatusLabel, $genericStatusClass] = $nutritionOperationalStatus($serviceProviderRequest);
                                $genericHospital = $serviceProviderRequest->medicalUnit?->name ?? $unit->name;
                                $genericResponsible = data_get($serviceProviderRequest->payload, 'responsible_name')
                                  ?? $serviceProviderRequest->provider?->name
                                  ?? 'Sin responsable';
                                $genericRoute = route('operational.dashboard', [
                                  'area' => $operationArea,
                                  'section' => 'history',
                                  'unit' => $unit->id,
                                  'service' => $contract->service_id,
                                  'request' => $serviceProviderRequest->id,
                                ]);
                              ?>
                              <tr data-unit-request-row data-category="<?php echo e($genericCategory); ?>">
                                <td><strong><?php echo e($genericFolio); ?></strong></td>
                                <td><?php echo e($genericType); ?></td>
                                <td><strong><?php echo e($genericHospital); ?></strong><small><?php echo e($unit->clues ?? $unit->code ?? 'Sin CLUES'); ?></small></td>
                                <td><strong><?php echo e($serviceProviderRequest->patient?->full_name ?? 'Paciente pendiente'); ?></strong></td>
                                <td><?php echo e($serviceProviderRequest->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td>
                                <td><span class="unit-consultation-chip is-priority-<?php echo e($genericPriorityClass); ?>"><?php echo e($genericPriorityLabel); ?></span></td>
                                <td><?php echo e($genericResponsible); ?></td>
                                <td><span class="unit-consultation-chip is-status-<?php echo e($genericStatusClass); ?>"><?php echo e($genericStatusLabel); ?></span></td>
                                <td><a class="unit-nutrition-request-view" href="<?php echo e($genericRoute); ?>" aria-label="Ver solicitud <?php echo e($genericFolio); ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>Ver</a></td>
                              </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                              <tr data-unit-request-empty><td colspan="9">No se encontraron solicitudes para <?php echo e($serviceName); ?>.</td></tr>
                            <?php endif; ?>
                            <?php if($serviceProviderRequests->isNotEmpty()): ?>
                              <tr data-unit-request-no-results hidden><td colspan="9">No se encontraron solicitudes para este filtro.</td></tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                        </div>
                      </section>
                    <?php endif; ?>
                  </div>

                  <?php if($isConsultationService): ?>
                    <div class="unit-consultation-subview unit-consultation-room-workspace"
                         data-unit-consultation-view="rooms"
                         data-unit-consultation-rooms
                         data-default-date="<?php echo e($consultationCalendarDefaultDate); ?>"
                         hidden>
                      <aside class="unit-consultation-room-directory">
                        <header>
                          <h3>Consultorios activos</h3>
                          <a href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'create' => 'consulting'])); ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Nuevo consultorio
                          </a>
                        </header>
                        <label class="unit-consultation-room-search">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                          <input type="search" data-unit-room-search aria-label="Buscar consultorio" placeholder="Buscar por numero o especialidad..." autocomplete="off">
                        </label>
                        <div class="unit-consultation-room-list" data-unit-room-list role="listbox" aria-label="Consultorios registrados">
                          <?php $__empty_1 = true; $__currentLoopData = $registeredConsultationRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <button type="button"
                                    class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-consultation-room-list-item', 'is-active' => $loop->first]); ?>"
                                    data-unit-room-id="<?php echo e($room['id']); ?>"
                                    data-search="<?php echo e($room['number'].' '.$room['name'].' '.$room['specialty'].' '.$room['floor']); ?>"
                                    role="option"
                                    aria-selected="<?php echo e($loop->first ? 'true' : 'false'); ?>">
                              <span class="unit-consultation-room-list-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/><path d="M13 12h.01"/></svg>
                              </span>
                              <span class="unit-consultation-room-list-copy">
                                <strong><?php echo e($room['name']); ?></strong>
                                <small><?php echo e($room['specialty']); ?> - Piso <?php echo e($room['floor']); ?></small>
                              </span>
                              <span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-consultation-room-state', 'is-inactive' => $room['status'] !== 'active']); ?>">
                                <i aria-hidden="true"></i><?php echo e($room['status_label']); ?>

                              </span>
                              <span class="unit-consultation-room-list-arrow" aria-hidden="true">&rsaquo;</span>
                            </button>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="unit-consultation-room-list-empty">No hay consultorios registrados.</p>
                          <?php endif; ?>
                          <p class="unit-consultation-room-list-empty" data-unit-room-search-empty hidden>No hay coincidencias.</p>
                        </div>
                      </aside>

                      <section class="unit-consultation-room-detail" data-unit-room-detail <?php if($registeredConsultationRooms->isEmpty()): ?> hidden <?php endif; ?>>
                        <header class="unit-consultation-room-detail-header">
                          <div class="unit-consultation-room-heading">
                            <span aria-hidden="true">
                              <svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/><path d="M13 12h.01"/></svg>
                            </span>
                            <div><h3 data-unit-room-field="name">Consultorio</h3><p><?php echo e($unit->name); ?></p></div>
                          </div>
                          <div class="unit-consultation-room-actions">
                            <a href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'catalog' => 'consulting'])); ?>" data-unit-room-edit>
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 4.5-1 10-10a2.1 2.1 0 0 0-3-3l-10 10z"/><path d="m14 7 3 3"/></svg>
                              Editar consultorio
                            </a>
                            <div class="unit-consultation-room-more">
                              <button type="button" data-unit-room-more aria-expanded="false">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/></svg>
                                Mas acciones
                                <span aria-hidden="true">&#9662;</span>
                              </button>
                              <div data-unit-room-more-menu hidden>
                                <button type="button" data-unit-room-show-calendar>
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                                  Ver en calendario
                                </button>
                                <a href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'catalog' => 'consulting'])); ?>">
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="M8 9h8M8 13h8M8 17h5"/></svg>
                                  Administrar catalogo
                                </a>
                              </div>
                            </div>
                          </div>
                        </header>

                        <div class="unit-consultation-room-badges" aria-label="Informacion general del consultorio">
                          <span class="is-status" data-unit-room-status><i aria-hidden="true"></i>Activo</span>
                          <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 3v6a4 4 0 0 0 8 0V3"/><path d="M9 13v2a5 5 0 0 0 10 0v-2"/><circle cx="19" cy="9" r="2"/></svg><b data-unit-room-field="specialty">Consulta externa</b></span>
                          <span><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M3 20a6 6 0 0 1 12 0M14 16a5 5 0 0 1 7 4"/></svg>Capacidad: <b data-unit-room-field="capacity">1 paciente</b></span>
                          <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.2 7-12a7 7 0 1 0-14 0c0 6.8 7 12 7 12Z"/><circle cx="12" cy="9" r="2"/></svg><b data-unit-room-field="location">Sin ubicacion</b></span>
                        </div>

                        <div class="unit-consultation-room-metrics">
                          <article>
                            <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg></span>
                            <div><small>Pacientes hoy</small><strong data-unit-room-metric="patients">0 / 0</strong><em>Citas programadas</em></div>
                          </article>
                          <article>
                            <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                            <div><small>Horario de atencion</small><strong data-unit-room-metric="schedule">08:00 - 16:00</strong><em data-unit-room-metric="days">Lunes a Viernes</em></div>
                          </article>
                          <article>
                            <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/></svg></span>
                            <div><small>Medico asignado</small><strong data-unit-room-metric="doctor">Sin medico asignado</strong><em data-unit-room-metric="license">Sin cedula registrada</em></div>
                          </article>
                          <article>
                            <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 3h12v18H6z"/><path d="M9 3V1h6v2M9 8h6M9 12h6M9 16h4"/></svg></span>
                            <div><small>Especialidad</small><strong data-unit-room-metric="specialty">Consulta externa</strong><em>Consulta externa</em></div>
                          </article>
                        </div>

                        <section class="unit-consultation-room-agenda">
                          <header>
                            <div><h4>Agenda de hoy</h4><p data-unit-room-agenda-date>Sin fecha seleccionada</p></div>
                            <div class="unit-consultation-room-agenda-controls">
                              <label>Especialidad
                                <select data-unit-room-specialty>
                                  <option value="">Todas</option>
                                  <?php $__currentLoopData = $consultationRoomSpecialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $roomSpecialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($roomSpecialty); ?>"><?php echo e($roomSpecialty); ?></option>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                              </label>
                              <label>Numero de consultorio
                                <select data-unit-room-select>
                                  <?php $__currentLoopData = $registeredConsultationRooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($room['id']); ?>"><?php echo e($room['name']); ?></option>
                                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                              </label>
                              <div class="unit-consultation-room-date-nav" aria-label="Navegacion de fecha">
                                <button type="button" data-unit-room-date-previous aria-label="Dia anterior">&lsaquo;</button>
                                <button type="button" data-unit-room-date-today>
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                                  Hoy
                                </button>
                                <button type="button" data-unit-room-date-next aria-label="Dia siguiente">&rsaquo;</button>
                              </div>
                            </div>
                          </header>
                          <div class="unit-consultation-room-agenda-table-wrap" data-drsam-table-filter-skip>
                            <table class="unit-consultation-room-agenda-table">
                              <thead>
                                <tr><th>Hora</th><th>Paciente</th><th>Edad</th><th>Motivo de consulta</th><th>Estatus</th><th>Acciones</th></tr>
                              </thead>
                              <tbody data-unit-room-agenda-body></tbody>
                            </table>
                          </div>
                        </section>
                      </section>

                      <section class="unit-consultation-room-empty" data-unit-room-empty <?php if($registeredConsultationRooms->isNotEmpty()): ?> hidden <?php endif; ?>>
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/></svg></span>
                        <h3>Sin consultorios registrados</h3>
                        <p>Agrega el primer consultorio para comenzar a organizar la agenda de Consulta externa.</p>
                        <a href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'create' => 'consulting'])); ?>">Nuevo consultorio</a>
                      </section>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="doctors" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Medicos</h3><p>Personal medico adscrito a la unidad.</p></div>
                        <a href="<?php echo e(route('unit.dashboard', ['section' => 'doctors'])); ?>">Administrar medicos</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Medico</th><th>Cedula</th><th>Especialidad</th><th>Subespecialidad</th><th>Servicio</th><th>Estatus</th></tr></thead>
                          <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $unit->doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                              <tr>
                                <td><strong><?php echo e($doctor->full_name); ?></strong><small><?php echo e($doctor->user?->email ?? 'Sin correo asociado'); ?></small></td>
                                <td><?php echo e($doctor->professional_license ?? 'Sin cedula'); ?></td>
                                <td><?php echo e($doctor->specialty ?? 'Sin especialidad'); ?></td>
                                <td><?php echo e($doctor->subspecialty ?? 'Sin subespecialidad'); ?></td>
                                <td><?php echo e($doctor->service_name ?? 'Consulta externa'); ?></td>
                                <td><span class="unit-native-status"><?php echo e($statusText($doctor->status)); ?></span></td>
                              </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                              <tr><td colspan="6" class="unit-native-empty">Sin medicos adscritos.</td></tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="specialties" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Especialidades</h3><p>Especialidades disponibles en la agenda de la unidad.</p></div>
                        <a href="<?php echo e(route('unit.dashboard', ['section' => 'specialties'])); ?>">Ver catalogo institucional</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Especialidad</th><th>Medicos adscritos</th><th>Citas registradas</th><th>Citas de hoy</th><th>Estatus</th></tr></thead>
                          <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $consultationSpecialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $consultationSpecialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                              <?php
                                $specialtyDoctors = $unit->doctors->where('specialty', $consultationSpecialty)->count();
                                $specialtyAppointments = $consultationAppointments->where('specialty', $consultationSpecialty);
                              ?>
                              <tr>
                                <td><strong><?php echo e($consultationSpecialty); ?></strong></td>
                                <td><?php echo e($specialtyDoctors); ?></td>
                                <td><?php echo e($specialtyAppointments->count()); ?></td>
                                <td><?php echo e($specialtyAppointments->filter(fn ($appointment) => $appointment->starts_at?->isToday())->count()); ?></td>
                                <td><span class="unit-native-status">Activo</span></td>
                              </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                              <tr><td colspan="5" class="unit-native-empty">Sin especialidades disponibles.</td></tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="patients" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Pacientes</h3><p>Pacientes con citas registradas en esta unidad.</p></div>
                        <a href="<?php echo e(route('unit.dashboard', ['section' => 'patients'])); ?>">Ver catalogo de pacientes</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Paciente</th><th>CURP</th><th>Telefono</th><th>Correo</th><th>Ultima cita</th><th>Estatus</th></tr></thead>
                          <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                              <?php
                                $latestPatientAppointment = $patient->appointments->first();
                              ?>
                              <tr>
                                <td><strong><?php echo e($patient->full_name); ?></strong><small><?php echo e($patient->platform_number ?? 'Sin numero de plataforma'); ?></small></td>
                                <td><?php echo e($patient->curp ?? 'Sin CURP'); ?></td>
                                <td><?php echo e($patient->phone ?? 'Sin telefono'); ?></td>
                                <td><?php echo e($patient->email ?? 'Sin correo'); ?></td>
                                <td><?php echo e($latestPatientAppointment?->starts_at?->format('d/m/Y H:i') ?? 'Sin cita'); ?></td>
                                <td><span class="unit-native-status"><?php echo e($statusText($patient->status)); ?></span></td>
                              </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                              <tr><td colspan="6" class="unit-native-empty">Sin pacientes vinculados a consulta externa.</td></tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="prescriptions" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Recetas</h3><p>Recetas emitidas por los medicos de la unidad.</p></div>
                        <a href="<?php echo e(route('outpatient.dashboard', ['unit' => $unit->id, 'section' => 'prescriptions'])); ?>">Abrir modulo de recetas</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Folio</th><th>Fecha</th><th>Paciente</th><th>Medico</th><th>Medicamentos</th><th>Estatus</th></tr></thead>
                          <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $consultationPrescriptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $prescription): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                              <tr>
                                <td><strong><?php echo e($prescription->code ?? 'REC-'.str_pad((string) $prescription->id, 5, '0', STR_PAD_LEFT)); ?></strong></td>
                                <td><?php echo e($prescription->issued_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td>
                                <td><?php echo e($prescription->patient?->full_name ?? 'Sin paciente'); ?></td>
                                <td><?php echo e($prescription->doctor?->full_name ?? 'Sin medico'); ?></td>
                                <td><?php echo e($prescription->items->pluck('medication_name')->filter()->join(', ') ?: 'Sin medicamentos'); ?></td>
                                <td><span class="unit-native-status"><?php echo e($statusText($prescription->status)); ?></span></td>
                              </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                              <tr><td colspan="6" class="unit-native-empty">Sin recetas emitidas en esta unidad.</td></tr>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div class="unit-service-operation-view"
                       data-unit-service-operation
                       <?php if($isConsultationService): ?> data-unit-consultation-view="agenda" <?php endif; ?>
                       data-operation-url="<?php echo e($operationHref); ?>"
                       hidden>
                    <div class="unit-service-operation-live">
                      <span><i aria-hidden="true"></i> Operacion en tiempo real</span>
                      <div>
                        <small>Actualizado <?php echo e(now()->format('d/m/Y H:i')); ?></small>
                        <a href="<?php echo e($operationHref); ?>">Abrir modulo operativo</a>
                      </div>
                    </div>

                    <div class="unit-service-operation-metrics" aria-label="Resumen operativo de <?php echo e($serviceName); ?>">
                      <article class="is-today">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 11h6M9 15h4"/><path d="M5 4h14v17H5z"/><path d="M9 4V2M15 4V2"/></svg></span>
                        <div><small>Solicitudes hoy</small><strong><?php echo e($operationToday); ?></strong><em><?php echo e($operationTotal); ?> registradas</em></div>
                      </article>
                      <article class="is-process">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/></svg></span>
                        <div><small>En proceso</small><strong><?php echo e($operationInProcess); ?></strong><em><?php echo e($operationPercentage($operationInProcess)); ?></em></div>
                      </article>
                      <article class="is-completed">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span>
                        <div><small>Completadas</small><strong><?php echo e($operationCompleted); ?></strong><em><?php echo e($operationPercentage($operationCompleted)); ?></em></div>
                      </article>
                      <article class="is-pending">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <div><small>Pendientes</small><strong><?php echo e($operationPending); ?></strong><em><?php echo e($operationPercentage($operationPending)); ?></em></div>
                      </article>
                      <article class="is-average">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"/><path d="M9 2h6M12 5v3M12 13l3-2"/></svg></span>
                        <div><small>Tiempo promedio</small><strong><?php echo e($averageTimeLabel); ?></strong><em>Casos completados</em></div>
                      </article>
                    </div>

                    <div class="unit-service-operation-filters" data-unit-operation-filters>
                      <label>Fecha
                        <input type="date" data-unit-operation-filter="date">
                      </label>
                      <label>Hospital
                        <select data-unit-operation-filter="hospital">
                          <option value="">Todos</option>
                          <option value="<?php echo e($unit->name); ?>"><?php echo e($unit->name); ?></option>
                        </select>
                      </label>
                      <label>Tipo de solicitud
                        <select data-unit-operation-filter="type">
                          <option value="">Todos</option>
                          <?php $__currentLoopData = $operationTypeOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operationTypeOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($operationTypeOption); ?>"><?php echo e($operationTypeOption); ?></option>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                      </label>
                      <label>Prioridad
                        <select data-unit-operation-filter="priority">
                          <option value="">Todas</option>
                          <?php $__currentLoopData = $operationPriorityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operationPriorityOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($operationPriorityOption); ?>"><?php echo e($operationPriorityOption); ?></option>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                      </label>
                      <label>Estatus
                        <select data-unit-operation-filter="status">
                          <option value="">Todos</option>
                          <?php $__currentLoopData = $operationStatusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $operationStatusOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($operationStatusOption); ?>"><?php echo e($operationStatusOption); ?></option>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                      </label>
                      <label class="unit-service-operation-search">Buscar solicitud
                        <span>
                          <input type="search" placeholder="Folio, paciente o responsable..." data-unit-operation-filter="search">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                        </span>
                      </label>
                    </div>

                    <div class="unit-native-table-scroll">
                    <?php if($isConsultationService): ?>
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
                          <?php $__empty_1 = true; $__currentLoopData = $consultationAppointments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $appointment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                              $folioYear = $appointment->created_at?->format('Y') ?? now()->format('Y');
                              $folio = data_get($appointment->metadata, 'folio') ?? 'CE-'.$folioYear.'-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT);
                              $appointmentType = $consultationRequestType($appointment);
                              [$priorityLabel, $priorityClass] = $consultationPriority($appointment);
                              [$consultationStatusLabel, $consultationStatusClass] = $consultationStatus($appointment->status);
                              $patientMeta = $appointment->patient?->curp ?? 'Sin CURP';
                              $appointmentSearch = $appointment->patient?->full_name ?? $folio;
                              $appointmentRoute = route('outpatient.dashboard', ['unit' => $unit->id, 'search' => $appointmentSearch]);
                              $appointmentHospital = $appointment->medicalUnit?->name ?? $unit->name;
                            ?>
                            <tr data-unit-operation-row
                                data-date="<?php echo e($appointment->starts_at?->format('Y-m-d')); ?>"
                                data-hospital="<?php echo e($appointmentHospital); ?>"
                                data-type="<?php echo e($appointmentType); ?>"
                                data-priority="<?php echo e($priorityLabel); ?>"
                                data-status="<?php echo e($consultationStatusLabel); ?>"
                                data-search="<?php echo e(collect([$folio, $appointment->patient?->full_name, $patientMeta, $appointment->doctor?->full_name, $appointmentType])->filter()->implode(' ')); ?>">
                              <td><strong class="unit-consultation-folio"><?php echo e($folio); ?></strong></td>
                              <td><?php echo e($appointment->starts_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td>
                              <td><strong><?php echo e($appointment->patient?->full_name ?? 'Paciente pendiente'); ?></strong><small>CURP: <?php echo e($patientMeta); ?></small></td>
                              <td><strong><?php echo e($appointmentHospital); ?></strong></td>
                              <td><?php echo e($appointmentType); ?></td>
                              <td><span class="unit-consultation-chip is-priority-<?php echo e($priorityClass); ?>"><?php echo e($priorityLabel); ?></span></td>
                              <td><?php echo e($appointment->doctor?->full_name ?? 'Sin responsable'); ?></td>
                              <td><button type="button" class="unit-consultation-chip is-status-<?php echo e($consultationStatusClass); ?>" data-unit-status-filter="<?php echo e($consultationStatusLabel); ?>"><?php echo e($consultationStatusLabel); ?></button></td>
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
                            <tr data-unit-operation-empty><td colspan="10" class="unit-native-empty">Sin consultas externas registradas.</td></tr>
                          <?php endif; ?>
                          <?php if($consultationAppointments->isNotEmpty()): ?>
                            <tr data-unit-operation-no-results hidden><td colspan="10" class="unit-native-empty">No hay solicitudes que coincidan con los filtros.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    <?php elseif($usesMixtureTable): ?>
                      <?php
                        $mixtureRequests = $serviceProviderRequests;
                        $mixtureRouteArea = ($isChemotherapyService || $isCentralMixturesService) ? 'oncology' : 'nursing';
                        $mixtureEmptyMessage = match (true) {
                          $isChemotherapyService => 'Sin solicitudes de quimioterapia registradas.',
                          $isCentralMixturesService => 'Sin solicitudes de mezclas registradas.',
                          default => 'Sin solicitudes de nutricion parenteral registradas.',
                        };
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
                              [$mixtureTypeLabel, $mixtureTypeClass] = $nutritionType($mixtureRequest);
                              [$mixturePriorityLabel, $mixturePriorityClass] = $providerRequestPriority($mixtureRequest);
                              [$mixtureStatusLabel, $mixtureStatusClass] = $nutritionOperationalStatus($mixtureRequest);
                              $mixId = $providerPayloadValue(data_get($mixtureRequest->payload, 'mix_id')
                                  ?? data_get($mixtureRequest->payload, 'mixture_id')
                                  ?? data_get($mixtureRequest->payload, 'clinical_format.mix_id')
                                  ?? $mixtureRequest->id);
                              $requestNumber = $providerPayloadValue(data_get($mixtureRequest->payload, 'request_number')
                                  ?? data_get($mixtureRequest->payload, 'request_no')
                                  ?? $mixtureRequest->external_id
                                  ?? ($isChemotherapyService ? 'QT-' : 'NPT-').str_pad((string) $mixtureRequest->id, 4, '0', STR_PAD_LEFT));
                              $remission = $providerPayloadValue(data_get($mixtureRequest->payload, 'remission')
                                  ?? data_get($mixtureRequest->payload, 'delivery_remission')
                                  ?? data_get($mixtureRequest->payload, 'remission_number'));
                              $lot = $providerPayloadValue(data_get($mixtureRequest->payload, 'lot')
                                  ?? data_get($mixtureRequest->payload, 'batch')
                                  ?? data_get($mixtureRequest->payload, 'remission.lot')
                                  ?? data_get($mixtureRequest->payload, 'remission.batch'));
                              $mixtureRoute = route('operational.dashboard', ['area' => $mixtureRouteArea, 'section' => 'support', 'unit' => $unit->id, 'request' => $mixtureRequest->id]);
                              $mixtureHospital = $mixtureRequest->medicalUnit?->name ?? $unit->name;
                            ?>
                            <tr data-unit-operation-row
                                data-date="<?php echo e($mixtureRequest->requested_at?->format('Y-m-d')); ?>"
                                data-hospital="<?php echo e($mixtureHospital); ?>"
                                data-type="<?php echo e($mixtureTypeLabel); ?>"
                                data-priority="<?php echo e($mixturePriorityLabel); ?>"
                                data-status="<?php echo e($mixtureStatusLabel); ?>"
                                data-search="<?php echo e(collect([$mixId, $requestNumber, $mixtureRequest->patient?->full_name, $mixtureRequest->provider?->name, $remission, $lot])->filter()->implode(' ')); ?>">
                              <td><span class="unit-consultation-chip is-type-<?php echo e($mixtureTypeClass); ?>"><?php echo e($mixtureTypeLabel); ?></span></td>
                              <td><?php echo e($mixId); ?></td>
                              <td><?php echo e($requestNumber); ?></td>
                              <td><?php echo e($mixtureHospital); ?></td>
                              <td><?php echo e($mixtureRequest->patient?->full_name ?? 'Sin paciente'); ?></td>
                              <td><?php echo e($mixtureRequest->requested_at?->format('Y-m-d H:i') ?? '-'); ?></td>
                              <td><?php echo e($mixtureRequest->required_at?->format('Y-m-d H:i') ?? '-'); ?></td>
                              <td><button type="button" class="unit-consultation-chip is-status-<?php echo e($mixtureStatusClass); ?>" data-unit-status-filter="<?php echo e($mixtureStatusLabel); ?>"><?php echo e($mixtureStatusLabel); ?></button></td>
                              <td><?php echo e($remission); ?></td>
                              <td><?php echo e($lot); ?></td>
                              <td><a class="unit-nutrition-view-link" href="<?php echo e($mixtureRoute); ?>">Ver</a></td>
                            </tr>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr data-unit-operation-empty><td colspan="11" class="unit-native-empty"><?php echo e($mixtureEmptyMessage); ?></td></tr>
                          <?php endif; ?>
                          <?php if($mixtureRequests->isNotEmpty()): ?>
                            <tr data-unit-operation-no-results hidden><td colspan="11" class="unit-native-empty">No hay solicitudes que coincidan con los filtros.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    <?php else: ?>
                      <table class="unit-native-table unit-consultation-table unit-service-generic-operation-table">
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
                          <?php $__empty_1 = true; $__currentLoopData = $serviceProviderRequests; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceProviderRequest): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                              $genericFolio = $serviceProviderRequest->external_id
                                ?? strtoupper(str($serviceKey)->limit(4, '')->toString()).'-'.str_pad((string) $serviceProviderRequest->id, 5, '0', STR_PAD_LEFT);
                              $genericType = $providerRequestTypeLabel($serviceProviderRequest);
                              [$genericPriorityLabel, $genericPriorityClass] = $providerRequestPriority($serviceProviderRequest);
                              [$genericStatusLabel, $genericStatusClass] = $nutritionOperationalStatus($serviceProviderRequest);
                              $genericHospital = $serviceProviderRequest->medicalUnit?->name ?? $unit->name;
                              $genericResponsible = data_get($serviceProviderRequest->payload, 'responsible_name')
                                ?? $serviceProviderRequest->provider?->name
                                ?? 'Sin responsable';
                              $genericRoute = route('operational.dashboard', [
                                'area' => $operationArea,
                                'section' => 'history',
                                'unit' => $unit->id,
                                'service' => $contract->service_id,
                                'request' => $serviceProviderRequest->id,
                              ]);
                            ?>
                            <tr data-unit-operation-row
                                data-date="<?php echo e($serviceProviderRequest->requested_at?->format('Y-m-d')); ?>"
                                data-hospital="<?php echo e($genericHospital); ?>"
                                data-type="<?php echo e($genericType); ?>"
                                data-priority="<?php echo e($genericPriorityLabel); ?>"
                                data-status="<?php echo e($genericStatusLabel); ?>"
                                data-search="<?php echo e(collect([$genericFolio, $serviceProviderRequest->patient?->full_name, $genericResponsible, $genericType])->filter()->implode(' ')); ?>">
                              <td><strong class="unit-consultation-folio"><?php echo e($genericFolio); ?></strong></td>
                              <td><?php echo e($serviceProviderRequest->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha'); ?></td>
                              <td><strong><?php echo e($serviceProviderRequest->patient?->full_name ?? 'Paciente pendiente'); ?></strong></td>
                              <td><strong><?php echo e($genericHospital); ?></strong></td>
                              <td><?php echo e($genericType); ?></td>
                              <td><span class="unit-consultation-chip is-priority-<?php echo e($genericPriorityClass); ?>"><?php echo e($genericPriorityLabel); ?></span></td>
                              <td><?php echo e($genericResponsible); ?></td>
                              <td><button type="button" class="unit-consultation-chip is-status-<?php echo e($genericStatusClass); ?>" data-unit-status-filter="<?php echo e($genericStatusLabel); ?>"><?php echo e($genericStatusLabel); ?></button></td>
                              <td><?php echo e($serviceProviderRequest->updated_at?->format('d/m/Y H:i') ?? 'Sin actualizacion'); ?></td>
                              <td><div class="unit-consultation-actions"><a href="<?php echo e($genericRoute); ?>">Ver</a><a href="<?php echo e($genericRoute); ?>">Actualizar</a></div></td>
                            </tr>
                          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr data-unit-operation-empty><td colspan="10" class="unit-native-empty">Sin solicitudes registradas para <?php echo e($serviceName); ?>.</td></tr>
                          <?php endif; ?>
                          <?php if($serviceProviderRequests->isNotEmpty()): ?>
                            <tr data-unit-operation-no-results hidden><td colspan="10" class="unit-native-empty">No hay solicitudes que coincidan con los filtros.</td></tr>
                          <?php endif; ?>
                        </tbody>
                      </table>
                    <?php endif; ?>
                  </div>
                    <footer class="unit-service-operation-footer">
                      <span>Mostrando <strong data-unit-operation-visible-count><?php echo e($operationTotal); ?></strong> de <?php echo e($operationTotal); ?> solicitudes</span>
                      <span>La informacion corresponde al estado actual del servicio.</span>
                    </footer>
                  </div>
                </section>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php else: ?>
            <section class="unit-native-table-card">
              <div class="unit-native-table-heading">
                <h2>Servicios integrales</h2>
                <p>Sin servicios habilitados para esta unidad.</p>
              </div>
            </section>
          <?php endif; ?>
        </div>

        <?php $__currentLoopData = $unitServiceContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <?php
            $catalogServiceName = $contract->service?->name ?? 'Servicio sin nombre';
            $catalogProvider = data_get($contract->metadata, 'provider', 'Operacion clinica');
            $catalogKey = $contract->service?->code ?? str($catalogServiceName)->slug();
          ?>
          <dialog class="unit-service-catalog-dialog" data-service-catalog-dialog="<?php echo e($contract->id); ?>">
            <header><div><h2>Catalogo de productos/Servicios</h2><p><?php echo e($catalogServiceName); ?> - <?php echo e($catalogProvider); ?></p></div><button type="button" data-close-service-catalog>Cerrar</button></header>
            <div class="unit-service-catalog-facts">
              <article><small>Servicio</small><strong><?php echo e($catalogServiceName); ?></strong></article>
              <article><small>Proveedor</small><strong><?php echo e($catalogProvider); ?></strong></article>
              <article><small>Elementos habilitados</small><strong>1</strong></article>
            </div>
            <div class="unit-service-catalog-scroll">
              <table>
                <thead><tr><th>Clave</th><th>Producto / servicio</th><th>Tipo</th><th>Proveedor</th><th>Detalle</th><th>Estatus</th></tr></thead>
                <tbody><tr><td><?php echo e($catalogKey); ?></td><td><strong><?php echo e($catalogServiceName); ?></strong><small><?php echo e($contract->service?->specialty ?? 'Servicio general'); ?></small></td><td><?php echo e($contract->service?->category ?? 'Sin categoria'); ?></td><td><?php echo e($catalogProvider); ?></td><td>Vigencia <?php echo e($contract->starts_at?->format('d/m/Y') ?? 'sin inicio'); ?> - <?php echo e($contract->ends_at?->format('d/m/Y') ?? 'sin vencimiento'); ?></td><td><span class="unit-native-status"><?php echo e($statusText($contract->status)); ?></span></td></tr></tbody>
              </table>
            </div>
          </dialog>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <script>
          (() => {
            const root = document.querySelector('[data-unit-service-dashboard]');
            if (root) {
              const carousel = root.querySelector('[data-unit-service-carousel]');
              const tabs = [...root.querySelectorAll('[data-unit-service-tab]')];
              const panels = [...root.querySelectorAll('[data-unit-service-panel]')];
              const serviceCatalogSearch = root.querySelector('[data-unit-service-catalog-search]');
              const serviceCatalogRows = [...root.querySelectorAll('[data-unit-service-catalog-row]')];
              const consultationSubmenu = root.querySelector('[data-unit-consultation-submenu]');
              const consultationTabs = [...root.querySelectorAll('[data-unit-consultation-tab]')];
              const consultationViews = [...root.querySelectorAll('[data-unit-consultation-view]')];
              const consultationCalendar = root.querySelector('[data-unit-consultation-calendar]');
              const consultationRoomsWorkspace = root.querySelector('[data-unit-consultation-rooms]');
              const consultationCalendarRooms = <?php echo json_encode($consultationCalendarRooms, 15, 512) ?>;
              const consultationCalendarPatients = <?php echo json_encode($consultationCalendarPatients, 15, 512) ?>;
              const consultationCalendarDoctors = <?php echo json_encode($consultationCalendarDoctors, 15, 512) ?>;
              const consultationCalendarAppointments = <?php echo json_encode($consultationCalendarAppointments, 15, 512) ?>;
              const escapeMarkup = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
              }[character]));
              const normalize = (value) => (value || '')
                .toString()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim()
                .toLowerCase();
              const applyFilters = (operation) => {
                const filters = Object.fromEntries(
                  [...operation.querySelectorAll('[data-unit-operation-filter]')]
                    .map((input) => [input.dataset.unitOperationFilter, normalize(input.value)])
                );
                const rows = [...operation.querySelectorAll('[data-unit-operation-row]')];
                let visible = 0;

                rows.forEach((row) => {
                  const matches = ['date', 'hospital', 'type', 'priority', 'status'].every((key) => (
                    !filters[key] || normalize(row.dataset[key]) === filters[key]
                  )) && (!filters.search || normalize(row.dataset.search).includes(filters.search));
                  row.hidden = !matches;
                  if (matches) visible += 1;
                });

                const noResults = operation.querySelector('[data-unit-operation-no-results]');
                if (noResults) noResults.hidden = visible !== 0;
                const count = operation.querySelector('[data-unit-operation-visible-count]');
                if (count) count.textContent = visible;
              };
              const initializeConsultationCalendar = () => {
                if (!consultationCalendar) return;

                const stage = consultationCalendar.querySelector('[data-unit-calendar-stage]');
                const dateInput = consultationCalendar.querySelector('[data-unit-calendar-date]');
                const specialtyInput = consultationCalendar.querySelector('[data-unit-calendar-specialty]');
                const roomInput = consultationCalendar.querySelector('[data-unit-calendar-room]');
                const miniLabel = consultationCalendar.querySelector('[data-unit-calendar-mini-label]');
                const miniDays = consultationCalendar.querySelector('[data-unit-calendar-mini-days]');
                const dialog = consultationCalendar.querySelector('[data-unit-calendar-dialog]');
                const editDialog = consultationCalendar.querySelector('[data-unit-calendar-edit-dialog]');
                const cancelDialog = consultationCalendar.querySelector('[data-unit-calendar-cancel-dialog]');
                const newDialog = consultationCalendar.querySelector('[data-unit-calendar-new-dialog]');
                const appointmentDialogTabs = [...(dialog?.querySelectorAll('[data-unit-calendar-dialog-tab]') || [])];
                const appointmentDialogPanels = [...(dialog?.querySelectorAll('[data-unit-calendar-dialog-panel]') || [])];
                const appointmentHistory = dialog?.querySelector('[data-unit-calendar-dialog-history]');
                const reminderFeedback = dialog?.querySelector('[data-unit-calendar-reminder-feedback]');
                const editForm = editDialog?.querySelector('[data-unit-calendar-edit-form]');
                const cancelForm = cancelDialog?.querySelector('[data-unit-calendar-cancel-form]');
                const newForm = newDialog?.querySelector('[data-unit-calendar-new-form]');
                const newAppointmentButton = root.querySelector('[data-unit-calendar-new]');
                const dialogHost = consultationCalendar.closest('[data-unit-consultation-panel]');
                [dialog, editDialog, cancelDialog, newDialog].forEach((modal) => {
                  if (modal && dialogHost) dialogHost.append(modal);
                });
                const parseDate = (value) => {
                  const [year, month, day] = value.split('-').map(Number);
                  return new Date(year, month - 1, day);
                };
                const dateKey = (date) => [
                  date.getFullYear(),
                  String(date.getMonth() + 1).padStart(2, '0'),
                  String(date.getDate()).padStart(2, '0'),
                ].join('-');
                const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({
                  '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;',
                }[character]));
                const state = {
                  mode: 'day',
                  date: consultationCalendar.dataset.defaultDate,
                  miniDate: parseDate(consultationCalendar.dataset.defaultDate),
                };
                const statusLabels = {
                  confirmed: 'Confirmada',
                  waiting: 'En espera',
                  'in-progress': 'En consulta',
                  completed: 'Finalizada',
                  cancelled: 'Cancelada',
                };
                const eventStatusLabels = {
                  scheduled: 'En espera',
                  confirmed: 'Confirmada',
                  in_progress: 'En consulta',
                  completed: 'Finalizada',
                  cancelled: 'Cancelada',
                  no_show: 'No asisti\u00f3',
                };
                const filteredAppointments = () => consultationCalendarAppointments.filter((appointment) => (
                  (!specialtyInput.value || appointment.specialty === specialtyInput.value)
                  && (!roomInput.value || appointment.room_id === roomInput.value)
                ));
                const appointmentsForDate = (value) => filteredAppointments()
                  .filter((appointment) => appointment.date === value)
                  .sort((left, right) => left.time.localeCompare(right.time));
                const visibleRooms = () => consultationCalendarRooms.filter((room) => (
                  !roomInput.value || room.id === roomInput.value
                ));
                const timeToMinutes = (time) => {
                  const [hours, minutes] = time.split(':').map(Number);
                  return (hours * 60) + minutes;
                };
                const slotFor = (time) => Math.floor(timeToMinutes(time) / 30) * 30;
                const formatTime = (minutes) => `${String(Math.floor(minutes / 60)).padStart(2, '0')}:${String(minutes % 60).padStart(2, '0')}`;
                const calendarSlots = () => {
                  const appointments = filteredAppointments();
                  const firstAppointment = appointments.length ? Math.min(...appointments.map((appointment) => slotFor(appointment.time))) : 8 * 60;
                  const lastAppointment = appointments.length ? Math.max(...appointments.map((appointment) => slotFor(appointment.ends_at || appointment.time))) : 15 * 60;
                  const start = Math.min(8 * 60, firstAppointment);
                  const end = Math.max(15 * 60, lastAppointment);
                  const slots = [];
                  for (let cursor = start; cursor <= end; cursor += 30) slots.push(cursor);
                  return slots;
                };
                const appointmentButton = (appointment, compact = false) => `
                  <button type="button"
                          class="unit-consultation-calendar-event is-${escapeHtml(appointment.status)}${compact ? ' is-compact' : ''}${appointment.room_number ? ' has-room-number' : ''}"
                          data-unit-calendar-appointment="${escapeHtml(appointment.id)}">
                    <time>${escapeHtml(appointment.time)}</time>
                    <span><strong>${escapeHtml(appointment.patient)}</strong><small>${escapeHtml(appointment.reason)}</small></span>
                    ${appointment.room_number ? `<span class="unit-consultation-calendar-room-number" title="Consultorio ${escapeHtml(appointment.room_number)}">${escapeHtml(appointment.room_number)}</span>` : ''}
                  </button>`;
                const renderDay = () => {
                  const rooms = visibleRooms();
                  const dayAppointments = appointmentsForDate(state.date);
                  const slots = calendarSlots();
                  const cells = slots.map((slot) => {
                    const roomCells = rooms.map((room) => {
                      const roomAppointments = dayAppointments.filter((appointment) => (
                        appointment.room_id === room.id && slotFor(appointment.time) === slot
                      ));
                      return `<div class="unit-consultation-calendar-cell">${roomAppointments.map((appointment) => appointmentButton(appointment)).join('')}</div>`;
                    }).join('');
                    return `<div class="unit-consultation-calendar-time">${formatTime(slot)}</div>${roomCells}`;
                  }).join('');
                  stage.innerHTML = `
                    <div class="unit-consultation-day-grid" style="--unit-calendar-columns:${Math.max(rooms.length, 1)}">
                      <div class="unit-consultation-calendar-corner">Hora</div>
                      ${rooms.map((room) => `<div class="unit-consultation-calendar-room"><strong>${escapeHtml(room.name)}</strong><small>${escapeHtml(room.specialty)}</small></div>`).join('')}
                      ${cells}
                    </div>
                    ${dayAppointments.length ? '' : '<p class="unit-consultation-calendar-empty">No hay citas programadas para este dia con los filtros seleccionados.</p>'}`;
                };
                const weekStart = (date) => {
                  const start = new Date(date);
                  const day = start.getDay();
                  start.setDate(start.getDate() - (day === 0 ? 6 : day - 1));
                  return start;
                };
                const renderWeek = () => {
                  const start = weekStart(parseDate(state.date));
                  const days = Array.from({ length: 7 }, (_, index) => {
                    const date = new Date(start);
                    date.setDate(start.getDate() + index);
                    return date;
                  });
                  const slots = calendarSlots();
                  const cells = slots.map((slot) => {
                    const dayCells = days.map((day) => {
                      const appointments = appointmentsForDate(dateKey(day)).filter((appointment) => slotFor(appointment.time) === slot);
                      return `<div class="unit-consultation-calendar-cell">${appointments.map((appointment) => appointmentButton(appointment, true)).join('')}</div>`;
                    }).join('');
                    return `<div class="unit-consultation-calendar-time">${formatTime(slot)}</div>${dayCells}`;
                  }).join('');
                  stage.innerHTML = `
                    <div class="unit-consultation-week-grid">
                      <div class="unit-consultation-calendar-corner">Hora</div>
                      ${days.map((day) => `<button type="button" class="unit-consultation-calendar-room${dateKey(day) === state.date ? ' is-selected' : ''}" data-unit-calendar-select-date="${dateKey(day)}"><strong>${day.toLocaleDateString('es-MX', { weekday: 'short' })}</strong><small>${day.toLocaleDateString('es-MX', { day: 'numeric', month: 'short' })}</small></button>`).join('')}
                      ${cells}
                    </div>`;
                };
                const renderMonth = () => {
                  const selected = parseDate(state.date);
                  const first = new Date(selected.getFullYear(), selected.getMonth(), 1);
                  first.setDate(first.getDate() - first.getDay());
                  const days = Array.from({ length: 42 }, (_, index) => {
                    const date = new Date(first);
                    date.setDate(first.getDate() + index);
                    return date;
                  });
                  stage.innerHTML = `
                    <div class="unit-consultation-month-grid">
                      ${['Dom', 'Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab'].map((day) => `<strong class="unit-consultation-month-weekday">${day}</strong>`).join('')}
                      ${days.map((day) => {
                        const key = dateKey(day);
                        const appointments = appointmentsForDate(key);
                        const outside = day.getMonth() !== selected.getMonth();
                        return `<div class="unit-consultation-month-day${outside ? ' is-outside' : ''}${key === state.date ? ' is-selected' : ''}">
                          <button type="button" data-unit-calendar-select-date="${key}">${day.getDate()}</button>
                          ${appointments.slice(0, 3).map((appointment) => appointmentButton(appointment, true)).join('')}
                          ${appointments.length > 3 ? `<small class="unit-consultation-month-more">+${appointments.length - 3} citas</small>` : ''}
                        </div>`;
                      }).join('')}
                    </div>`;
                };
                const renderList = () => {
                  const appointments = appointmentsForDate(state.date);
                  stage.innerHTML = appointments.length ? `
                    <div class="unit-consultation-calendar-list">
                      ${appointments.map((appointment) => {
                        const room = consultationCalendarRooms.find((item) => item.id === appointment.room_id);
                        return `<button type="button" data-unit-calendar-appointment="${escapeHtml(appointment.id)}">
                          <time>${escapeHtml(appointment.time)}<small>${escapeHtml(appointment.ends_at)}</small></time>
                          <span><strong>${escapeHtml(appointment.patient)}</strong><small>${escapeHtml(appointment.reason)}</small></span>
                          <span><strong>${escapeHtml(room?.name || appointment.location)}</strong><small>${escapeHtml(appointment.specialty)}</small></span>
                          <em class="is-${escapeHtml(appointment.status)}">${escapeHtml(appointment.status_label)}</em>
                        </button>`;
                      }).join('')}
                    </div>` : '<p class="unit-consultation-calendar-list-empty">No hay citas programadas para este dia con los filtros seleccionados.</p>';
                };
                const renderMiniCalendar = () => {
                  const month = new Date(state.miniDate.getFullYear(), state.miniDate.getMonth(), 1);
                  miniLabel.textContent = month.toLocaleDateString('es-MX', { month: 'long', year: 'numeric' });
                  const first = new Date(month);
                  first.setDate(first.getDate() - first.getDay());
                  const today = dateKey(new Date());
                  miniDays.innerHTML = Array.from({ length: 42 }, (_, index) => {
                    const day = new Date(first);
                    day.setDate(first.getDate() + index);
                    const key = dateKey(day);
                    const classes = [
                      day.getMonth() !== month.getMonth() ? 'is-outside' : '',
                      key === state.date ? 'is-selected' : '',
                      key === today ? 'is-today' : '',
                      appointmentsForDate(key).length ? 'has-appointments' : '',
                    ].filter(Boolean).join(' ');
                    return `<button type="button" class="${classes}" data-unit-calendar-select-date="${key}" aria-label="${day.toLocaleDateString('es-MX', { day: 'numeric', month: 'long', year: 'numeric' })}">${day.getDate()}</button>`;
                  }).join('');
                };
                const renderSummary = () => {
                  const appointments = appointmentsForDate(state.date);
                  ['confirmed', 'waiting', 'in-progress', 'completed', 'cancelled'].forEach((status) => {
                    const target = consultationCalendar.querySelector(`[data-unit-calendar-summary="${status}"]`);
                    if (target) target.textContent = appointments.filter((appointment) => appointment.status === status).length;
                  });
                  consultationCalendar.querySelector('[data-unit-calendar-summary="total"]').textContent = appointments.length;
                };
                const render = () => {
                  dateInput.value = state.date;
                  consultationCalendar.querySelectorAll('[data-unit-calendar-mode]').forEach((button) => {
                    const active = button.dataset.unitCalendarMode === state.mode;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                  });
                  ({ day: renderDay, week: renderWeek, month: renderMonth, list: renderList }[state.mode] || renderDay)();
                  renderMiniCalendar();
                  renderSummary();
                };
                const selectDate = (value) => {
                  state.date = value;
                  state.miniDate = parseDate(value);
                  render();
                };
                const moveDate = (direction) => {
                  const date = parseDate(state.date);
                  if (state.mode === 'month') date.setMonth(date.getMonth() + direction);
                  else date.setDate(date.getDate() + (state.mode === 'week' ? 7 * direction : direction));
                  selectDate(dateKey(date));
                };
                let activeAppointment = null;
                let selectedPatient = null;
                const appointmentById = (id) => consultationCalendarAppointments.find((item) => item.id === String(id));
                const roomById = (id) => consultationCalendarRooms.find((item) => item.id === String(id));
                const doctorById = (id) => consultationCalendarDoctors.find((item) => item.id === String(id));
                const patientById = (id) => consultationCalendarPatients.find((item) => item.id === String(id));
                const formatLongDate = (value) => {
                  const formatted = parseDate(value).toLocaleDateString('es-MX', {
                    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                  });
                  return formatted.charAt(0).toUpperCase() + formatted.slice(1);
                };
                const patientMeta = (patient) => [
                  patient?.curp,
                  patient?.age ? `${patient.age} a\u00f1os` : null,
                  patient?.sex,
                ].filter(Boolean).join(' | ');
                const patientInitials = (patient) => (patient?.name || '')
                  .split(/\s+/)
                  .filter(Boolean)
                  .slice(0, 2)
                  .map((part) => part.charAt(0))
                  .join('')
                  .toUpperCase();
                const setDialogField = (field, value) => {
                  const target = dialog?.querySelector(`[data-unit-calendar-dialog-field="${field}"]`);
                  if (target) target.textContent = value || 'Sin registro';
                };
                const setAppointmentDialogTab = (key) => {
                  appointmentDialogTabs.forEach((tab) => {
                    const active = tab.dataset.unitCalendarDialogTab === key;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                  });
                  appointmentDialogPanels.forEach((panel) => {
                    panel.hidden = panel.dataset.unitCalendarDialogPanel !== key;
                  });
                };
                const renderAppointmentHistory = (appointment) => {
                  if (!appointmentHistory) return;
                  const events = Array.isArray(appointment.history) ? appointment.history : [];
                  const entries = events.map((event) => {
                    const from = eventStatusLabels[event.from_status] || event.from_status;
                    const to = eventStatusLabels[event.to_status] || event.to_status || 'Actualizada';
                    return {
                      title: from ? `${from} a ${to}` : `Estatus: ${to}`,
                      detail: event.notes || 'Cambio de estatus registrado.',
                      date: event.date,
                    };
                  });
                  entries.push({
                    title: 'Cita registrada',
                    detail: `${formatLongDate(appointment.date)} | ${appointment.time} - ${appointment.ends_at}`,
                    date: appointment.created_at_label,
                  });
                  appointmentHistory.innerHTML = entries.map((entry) => `
                    <li>
                      <span aria-hidden="true"></span>
                      <div><strong>${escapeHtml(entry.title)}</strong><p>${escapeHtml(entry.detail)}</p></div>
                      <time>${escapeHtml(entry.date)}</time>
                    </li>`).join('');
                };
                const setFormValue = (form, name, value) => {
                  const control = form?.elements.namedItem(name);
                  if (control) control.value = value ?? '';
                };
                const actionFor = (form, appointment) => form?.dataset.actionTemplate
                  ?.replace('__appointment__', encodeURIComponent(appointment.id));
                const closeOnBackdrop = (modal) => modal?.addEventListener('click', (event) => {
                  if (event.target === modal) modal.close();
                });

                const openAppointment = (id) => {
                  const appointment = appointmentById(id);
                  if (!appointment || !dialog) return;
                  activeAppointment = appointment;
                  const room = roomById(appointment.room_id);
                  setDialogField('folio', appointment.folio);
                  setDialogField('patient', appointment.patient);
                  setDialogField('patient_sex', appointment.patient_sex);
                  setDialogField('patient_age', appointment.patient_age ? `${appointment.patient_age} a\u00f1os` : 'Edad no registrada');
                  setDialogField('patient_curp', appointment.patient_curp);
                  setDialogField('patient_platform', appointment.patient_platform);
                  setDialogField('patient_phone', appointment.patient_phone);
                  setDialogField('modality', appointment.modality || 'Presencial');
                  setDialogField('specialty', appointment.specialty);
                  setDialogField('doctor', appointment.doctor);
                  setDialogField('doctor_license', appointment.doctor_license);
                  setDialogField('room', room?.name || appointment.location);
                  setDialogField('date_time', `${formatLongDate(appointment.date)} | ${appointment.time} - ${appointment.ends_at}`);
                  setDialogField('notes', appointment.notes || appointment.reason || 'Sin notas registradas.');
                  const status = dialog.querySelector('[data-unit-calendar-dialog-field="status"]');
                  if (status) {
                    status.className = `unit-consultation-status-chip is-${appointment.status}`;
                    status.textContent = appointment.status_label || statusLabels[appointment.status];
                  }
                  const locked = ['cancelled', 'completed', 'no_show'].includes(appointment.status_value);
                  dialog.querySelectorAll('[data-unit-calendar-edit], [data-unit-calendar-reschedule], [data-unit-calendar-reminder], [data-unit-calendar-cancel]')
                    .forEach((button) => { button.disabled = locked; });
                  if (reminderFeedback) reminderFeedback.hidden = true;
                  renderAppointmentHistory(appointment);
                  setAppointmentDialogTab('information');
                  dialog.showModal();
                };

                const openEditAppointment = (appointment, reschedule = false) => {
                  if (!appointment || !editDialog || !editForm) return;
                  editForm.action = actionFor(editForm, appointment);
                  setFormValue(editForm, 'patient_id', appointment.patient_id);
                  setFormValue(editForm, 'specialty', appointment.specialty);
                  setFormValue(editForm, 'modality', appointment.modality || 'Presencial');
                  setFormValue(editForm, 'duration', [20, 30, 45, 60].includes(Number(appointment.duration)) ? appointment.duration : 30);
                  setFormValue(editForm, 'reason', appointment.reason);
                  setFormValue(editForm, 'appointment_date', appointment.date);
                  setFormValue(editForm, 'appointment_time', appointment.time);
                  setFormValue(editForm, 'doctor_id', appointment.doctor_id);
                  setFormValue(editForm, 'procedure_area_id', appointment.room_id);
                  const allowedStatuses = {
                    scheduled: ['scheduled', 'confirmed', 'in_progress', 'completed'],
                    confirmed: ['confirmed', 'in_progress', 'completed'],
                    in_progress: ['in_progress', 'completed'],
                  }[appointment.status_value] || [appointment.status_value];
                  const statusControl = editForm.elements.namedItem('status');
                  [...(statusControl?.options || [])].forEach((option) => {
                    option.disabled = !allowedStatuses.includes(option.value);
                  });
                  setFormValue(editForm, 'status', appointment.status_value || 'scheduled');
                  setFormValue(editForm, 'notes', appointment.notes);
                  const title = editDialog.querySelector('[data-unit-calendar-edit-title]');
                  if (title) title.textContent = reschedule ? 'Reprogramar cita' : 'Editar cita';
                  dialog?.close();
                  editDialog.showModal();
                  if (reschedule) editForm.elements.namedItem('appointment_date')?.focus();
                };

                const openCancelAppointment = (appointment) => {
                  if (!appointment || !cancelDialog || !cancelForm) return;
                  cancelForm.action = actionFor(cancelForm, appointment);
                  const room = roomById(appointment.room_id);
                  const values = {
                    patient: appointment.patient,
                    date: formatLongDate(appointment.date),
                    time: `${appointment.time} - ${appointment.ends_at}`,
                    room: room?.name || appointment.location,
                    doctor: appointment.doctor,
                  };
                  Object.entries(values).forEach(([field, value]) => {
                    const target = cancelDialog.querySelector(`[data-unit-calendar-cancel-field="${field}"]`);
                    if (target) target.textContent = value || 'Sin registro';
                  });
                  setFormValue(cancelForm, 'notes', '');
                  dialog?.close();
                  cancelDialog.showModal();
                  cancelForm.elements.namedItem('notes')?.focus();
                };

                const newField = (name) => newForm?.querySelector(`[data-unit-new-field="${name}"]`)
                  || newForm?.elements.namedItem(name);
                const wizardError = newDialog?.querySelector('[data-unit-new-error]');
                const showWizardError = (message = '') => {
                  if (!wizardError) return;
                  wizardError.textContent = message;
                  wizardError.hidden = !message;
                };
                const setWizardText = (attribute, field, value) => {
                  newDialog?.querySelectorAll(`[${attribute}="${field}"]`).forEach((target) => {
                    target.textContent = value || 'Sin registro';
                  });
                };
                const patientAutocompletes = {
                  search: {
                    input: newDialog?.querySelector('[data-unit-new-patient-search]'),
                    results: newDialog?.querySelector('[data-unit-new-patient-results]'),
                  },
                  platform: {
                    input: newDialog?.querySelector('[data-unit-new-platform-number]'),
                    results: newDialog?.querySelector('[data-unit-new-platform-results]'),
                  },
                };
                const closePatientResults = (exceptType = null) => {
                  Object.entries(patientAutocompletes).forEach(([type, autocomplete]) => {
                    if (type === exceptType) return;
                    if (autocomplete.results) autocomplete.results.hidden = true;
                    autocomplete.input?.setAttribute('aria-expanded', 'false');
                    autocomplete.input?.removeAttribute('aria-activedescendant');
                  });
                };
                const renderPatientResults = (type = 'search', query = '') => {
                  const autocomplete = patientAutocompletes[type];
                  if (!autocomplete?.input || !autocomplete.results) return;
                  const needle = normalize(query);
                  const matches = consultationCalendarPatients.map((patient) => {
                    const values = type === 'platform'
                      ? [patient.platform_number]
                      : [patient.name, patient.curp, patient.platform_number, patient.nss];
                    if (type === 'platform' && normalize(patient.platform_number) === 'sin expediente') return null;
                    const normalizedValues = values.map(normalize);
                    const exact = needle && normalizedValues.some((value) => value === needle);
                    const prefix = needle && normalizedValues.some((value) => value.startsWith(needle));
                    const includes = !needle || normalizedValues.some((value) => value.includes(needle));
                    if (!includes) return null;
                    return { patient, score: exact ? 0 : (prefix ? 1 : 2) };
                  }).filter(Boolean).sort((left, right) => (
                    left.score - right.score || left.patient.name.localeCompare(right.patient.name, 'es')
                  )).slice(0, 8).map(({ patient }) => patient);
                  autocomplete.results.innerHTML = matches.length
                    ? matches.map((patient) => {
                      const primary = type === 'platform' ? patient.platform_number : patient.name;
                      const secondary = type === 'platform'
                        ? `${patient.name} | ${patient.curp}`
                        : `${patient.curp} | ID: ${patient.platform_number}`;
                      return `<button type="button" id="${autocomplete.results.id}-option-${escapeHtml(patient.id)}" data-unit-new-patient="${escapeHtml(patient.id)}" role="option" aria-selected="false" tabindex="-1"><strong>${escapeHtml(primary)}</strong><small>${escapeHtml(secondary)}</small></button>`;
                    }).join('')
                    : '<small class="is-empty" role="status">No se encontraron pacientes con esos datos.</small>';
                  closePatientResults(type);
                  autocomplete.results.hidden = false;
                  autocomplete.input.setAttribute('aria-expanded', 'true');
                  autocomplete.input.removeAttribute('aria-activedescendant');
                };
                const hideSelectedPatient = () => {
                  selectedPatient = null;
                  newField('patient_id').value = '';
                  const selected = newDialog?.querySelector('[data-unit-new-selected-patient]');
                  const detailsToggle = newDialog?.querySelector('[data-unit-new-patient-details-toggle]');
                  const details = newDialog?.querySelector('[data-unit-new-patient-details]');
                  const email = newDialog?.querySelector('[data-unit-new-contact-email]');
                  if (selected) selected.hidden = true;
                  if (email) email.value = '';
                  if (detailsToggle) {
                    detailsToggle.hidden = true;
                    detailsToggle.setAttribute('aria-expanded', 'false');
                  }
                  if (details) details.hidden = true;
                };
                const selectPatient = (id, { preserveError = false } = {}) => {
                  selectedPatient = patientById(id);
                  if (!selectedPatient) return;
                  const search = newDialog?.querySelector('[data-unit-new-patient-search]');
                  const selected = newDialog?.querySelector('[data-unit-new-selected-patient]');
                  const detailsToggle = newDialog?.querySelector('[data-unit-new-patient-details-toggle]');
                  newField('patient_id').value = selectedPatient.id;
                  newField('platform_number').value = selectedPatient.platform_number === 'Sin expediente' ? '' : selectedPatient.platform_number;
                  if (search) search.value = selectedPatient.name;
                  closePatientResults();
                  if (selected) selected.hidden = false;
                  if (detailsToggle) detailsToggle.hidden = false;
                  setWizardText('data-unit-new-selected', 'patient_initials', patientInitials(selectedPatient));
                  setWizardText('data-unit-new-selected', 'patient', selectedPatient.name);
                  setWizardText('data-unit-new-selected', 'patient_meta', `${patientMeta(selectedPatient)} | ID: ${selectedPatient.platform_number}`);
                  setWizardText('data-unit-new-patient-detail', 'platform_number', selectedPatient.platform_number);
                  setWizardText('data-unit-new-patient-detail', 'curp', selectedPatient.curp);
                  setWizardText('data-unit-new-patient-detail', 'nss', selectedPatient.nss);
                  setWizardText('data-unit-new-patient-detail', 'contact', `${selectedPatient.phone} | ${selectedPatient.email}`);
                  const email = newDialog?.querySelector('[data-unit-new-contact-email]');
                  if (email) email.value = selectedPatient.email === 'Sin correo' ? '' : selectedPatient.email;
                  if (!preserveError) showWizardError();
                };
                const clearPatient = () => {
                  hideSelectedPatient();
                  newField('platform_number').value = '';
                  const search = newDialog?.querySelector('[data-unit-new-patient-search]');
                  if (search) {
                    search.value = '';
                    search.focus();
                  }
                  renderPatientResults('search');
                };
                const activatePatientResult = (type, direction) => {
                  const autocomplete = patientAutocompletes[type];
                  const options = [...(autocomplete?.results?.querySelectorAll('[data-unit-new-patient]') || [])];
                  if (!autocomplete?.input || options.length === 0) return;
                  const currentIndex = options.findIndex((option) => option.classList.contains('is-active'));
                  const nextIndex = currentIndex < 0
                    ? (direction > 0 ? 0 : options.length - 1)
                    : (currentIndex + direction + options.length) % options.length;
                  options.forEach((option, index) => {
                    const active = index === nextIndex;
                    option.classList.toggle('is-active', active);
                    option.setAttribute('aria-selected', active ? 'true' : 'false');
                  });
                  autocomplete.input.setAttribute('aria-activedescendant', options[nextIndex].id);
                  options[nextIndex].scrollIntoView({ block: 'nearest' });
                };
                const handlePatientAutocompleteKeydown = (type, event) => {
                  const autocomplete = patientAutocompletes[type];
                  if (!autocomplete?.input || !autocomplete.results) return;
                  if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                    event.preventDefault();
                    if (autocomplete.results.hidden) renderPatientResults(type, autocomplete.input.value);
                    activatePatientResult(type, event.key === 'ArrowDown' ? 1 : -1);
                    return;
                  }
                  if (event.key === 'Enter' && !autocomplete.results.hidden) {
                    const option = autocomplete.results.querySelector('[data-unit-new-patient].is-active')
                      || autocomplete.results.querySelector('[data-unit-new-patient]');
                    if (!option) return;
                    event.preventDefault();
                    selectPatient(option.dataset.unitNewPatient);
                    return;
                  }
                  if (event.key === 'Escape') {
                    event.preventDefault();
                    closePatientResults();
                  } else if (event.key === 'Tab') {
                    closePatientResults();
                  }
                };
                const populateDoctors = ({ preserveTime = false } = {}) => {
                  const specialty = newField('specialty')?.value || '';
                  const select = newField('doctor_id');
                  if (!select) return;
                  const previous = select.dataset.oldValue || select.value;
                  const doctors = consultationCalendarDoctors.filter((doctor) => (
                    specialty && normalize(doctor.specialty) === normalize(specialty)
                  ));
                  select.innerHTML = `<option value="">${doctors.length ? 'Selecciona un medico' : 'Sin medicos disponibles'}</option>${doctors
                    .map((doctor) => `<option value="${escapeHtml(doctor.id)}">${escapeHtml(doctor.name)}</option>`).join('')}`;
                  select.disabled = doctors.length === 0;
                  if (doctors.some((doctor) => doctor.id === previous)) select.value = previous;
                  select.dataset.oldValue = '';
                  if (!preserveTime) {
                    newField('appointment_time').value = '';
                    newField('appointment_time').dataset.oldValue = '';
                  }
                };
                const renderBookingWeek = () => {
                  const week = newDialog?.querySelector('[data-unit-new-week]');
                  const dateField = newField('appointment_date');
                  if (!week || !dateField?.value) return;
                  const selectedDate = parseDate(dateField.value);
                  const start = weekStart(selectedDate);
                  const dayCount = [0, 6].includes(selectedDate.getDay()) ? 7 : 5;
                  const days = Array.from({ length: dayCount }, (_, index) => {
                    const date = new Date(start);
                    date.setDate(start.getDate() + index);
                    return date;
                  });
                  week.style.setProperty('--unit-booking-days', String(dayCount));
                  week.innerHTML = days.map((day) => {
                    const value = dateKey(day);
                    const selected = value === dateField.value;
                    const disabled = Boolean(dateField.min && value < dateField.min);
                    const weekday = day.toLocaleDateString('es-MX', { weekday: 'short' }).replace('.', '').toUpperCase();
                    return `<button type="button" data-unit-new-day="${value}" aria-pressed="${selected ? 'true' : 'false'}" title="${escapeHtml(formatLongDate(value))}"${disabled ? ' disabled' : ''}><span>${escapeHtml(weekday)}</span><strong>${day.getDate()}</strong></button>`;
                  }).join('');
                };
                const renderModalityInfo = () => {
                  const modality = newForm?.querySelector('input[name="modality"]:checked')?.value || 'Presencial';
                  const isRemote = modality === 'Video llamada';
                  const info = newDialog?.querySelector('[data-unit-new-modality-info] span');
                  const notificationNote = newDialog?.querySelector('[data-unit-new-notification-note]');
                  if (info) info.textContent = isRemote
                    ? 'El enlace de acceso se genera al confirmar la cita.'
                    : 'La consulta se atendera en el consultorio seleccionado.';
                  if (notificationNote) notificationNote.textContent = isRemote
                    ? 'Incluye el enlace para la teleconsulta.'
                    : 'Incluye los datos de la consulta.';
                };
                const renderBookingSummary = () => {
                  const doctor = doctorById(newField('doctor_id')?.value);
                  const room = roomById(newField('procedure_area_id')?.value);
                  const date = newField('appointment_date')?.value;
                  const time = newField('appointment_time')?.value;
                  const duration = Number(newForm?.elements.namedItem('duration')?.value || 30);
                  const modality = newForm?.querySelector('input[name="modality"]:checked')?.value || 'Presencial';
                  const dateLabel = newDialog?.querySelector('[data-unit-new-date-label]');
                  const preview = newDialog?.querySelector('[data-unit-new-selection-preview]');
                  const summaryPrimary = newDialog?.querySelector('[data-unit-new-summary="primary"]');
                  const summaryDetail = newDialog?.querySelector('[data-unit-new-summary="detail"]');
                  if (dateLabel) dateLabel.textContent = date ? formatLongDate(date) : 'Selecciona una fecha';
                  if (preview) preview.hidden = !doctor || !room || !date || !time;
                  setWizardText('data-unit-new-selection', 'date', date ? formatLongDate(date) : '');
                  setWizardText('data-unit-new-selection', 'time', time ? `${time} - ${formatTime(timeToMinutes(time) + duration)}` : '');
                  setWizardText('data-unit-new-selection', 'doctor', doctor?.name);
                  setWizardText('data-unit-new-selection', 'room', room?.name);
                  setWizardText('data-unit-new-selection', 'specialty', newField('specialty')?.value);
                  if (summaryPrimary) {
                    const shortDate = date
                      ? parseDate(date).toLocaleDateString('es-MX', { weekday: 'short', day: 'numeric', month: 'short' }).replace('.', '')
                      : '';
                    summaryPrimary.textContent = date
                      ? `${shortDate}${time ? ` \u00b7 ${time} - ${formatTime(timeToMinutes(time) + duration)}` : ' \u00b7 Elige un horario'}`
                      : 'Elige una fecha y un horario';
                  }
                  if (summaryDetail) summaryDetail.textContent = `${modality === 'Video llamada' ? 'Teleconsulta' : 'Presencial'} \u00b7 ${duration} min`;
                  renderBookingWeek();
                  renderModalityInfo();
                };
                const populateAppointmentTimes = (slots, { preserveOld = false, placeholder = 'Selecciona un horario disponible' } = {}) => {
                  const select = newField('appointment_time');
                  if (!select) return '';
                  const previous = select.dataset.oldValue || select.value;
                  const duration = Number(newForm?.elements.namedItem('duration')?.value || 30);
                  select.innerHTML = `<option value="">${escapeHtml(placeholder)}</option>${slots.map((slot) => (
                    `<option value="${escapeHtml(slot)}">${escapeHtml(slot)} - ${escapeHtml(formatTime(timeToMinutes(slot) + duration))}</option>`
                  )).join('')}`;
                  select.disabled = slots.length === 0;
                  select.value = slots.includes(previous) ? previous : '';
                  if (!preserveOld) select.dataset.oldValue = '';
                  return select.value;
                };
                const renderAvailableSlots = () => {
                  const container = newDialog?.querySelector('[data-unit-new-slots]');
                  const slotCount = newDialog?.querySelector('[data-unit-new-slot-count]');
                  const doctor = doctorById(newField('doctor_id')?.value);
                  const room = roomById(newField('procedure_area_id')?.value);
                  const dateValue = newField('appointment_date')?.value;
                  renderBookingSummary();
                  if (!container) return;
                  if (!doctor || !room || !dateValue) {
                    populateAppointmentTimes([], { preserveOld: true });
                    if (slotCount) slotCount.textContent = '0 opciones';
                    container.innerHTML = '<p class="unit-consultation-no-slots">Selecciona especialidad, medico, consultorio y fecha para consultar horarios.</p>';
                    renderBookingSummary();
                    return;
                  }
                  const selectedDate = parseDate(dateValue);
                  const weekday = selectedDate.getDay();
                  const isoWeekday = weekday === 0 ? 7 : weekday;
                  const month = selectedDate.getMonth() + 1;
                  const duration = Number(newForm?.elements.namedItem('duration')?.value || 30);
                  const roomSchedules = Array.isArray(room.schedules) ? room.schedules : [];
                  const roomWindows = roomSchedules.length
                    ? roomSchedules.filter((schedule) => Number(schedule.day) === isoWeekday)
                    : [{ start: '08:00', end: '16:00' }];
                  const doctorAvailability = Array.isArray(doctor.availability) ? doctor.availability : [];
                  const doctorWindows = doctorAvailability.length ? doctorAvailability.filter((rule) => (
                    Number(rule.weekday) === isoWeekday
                    && (!rule.start_date || rule.start_date <= dateValue)
                    && (!rule.end_date || rule.end_date >= dateValue)
                    && (!Array.isArray(rule.months) || rule.months.map(Number).includes(month))
                  )) : [{ start: '00:00', end: '23:59' }];
                  const appointments = consultationCalendarAppointments.filter((appointment) => (
                    appointment.date === dateValue
                    && !['cancelled', 'no_show'].includes(appointment.status_value)
                  ));
                  const candidatesByTime = new Map();
                  roomWindows.forEach((roomWindow) => {
                    const roomStart = timeToMinutes(roomWindow.start);
                    const roomEnd = timeToMinutes(roomWindow.end);
                    for (let cursor = Math.ceil(roomStart / 30) * 30; cursor + duration <= roomEnd; cursor += 30) {
                      const candidate = new Date(`${dateValue}T${formatTime(cursor)}:00`);
                      const doctorAvailable = doctorWindows.some((doctorWindow) => (
                        cursor >= timeToMinutes(doctorWindow.start)
                        && cursor + duration <= timeToMinutes(doctorWindow.end)
                      ));
                      const overlapping = appointments.filter((appointment) => (
                        timeToMinutes(appointment.time) < cursor + duration
                        && timeToMinutes(appointment.ends_at || appointment.time) > cursor
                      ));
                      const doctorBusy = overlapping.some((appointment) => appointment.doctor_id === doctor.id);
                      const roomBusy = overlapping.filter((appointment) => appointment.room_id === room.id).length >= Number(room.capacity || 1);
                      const time = formatTime(cursor);
                      const available = candidate > new Date() && doctorAvailable && !doctorBusy && !roomBusy;
                      const previous = candidatesByTime.get(time);
                      candidatesByTime.set(time, { time, available: available || Boolean(previous?.available) });
                    }
                  });
                  const candidates = [...candidatesByTime.values()].sort((left, right) => left.time.localeCompare(right.time));
                  const availableSlots = candidates.filter((slot) => slot.available).map((slot) => slot.time);
                  const selectedTime = populateAppointmentTimes(availableSlots, {
                    placeholder: availableSlots.length ? 'Selecciona un horario disponible' : 'Sin horarios disponibles',
                  });
                  if (slotCount) slotCount.textContent = `${availableSlots.length} ${availableSlots.length === 1 ? 'opcion' : 'opciones'}`;
                  container.innerHTML = candidates.length
                    ? candidates.map((slot) => `<button type="button" class="${slot.time === selectedTime ? 'is-selected' : ''}" data-unit-new-slot="${slot.time}" aria-pressed="${slot.time === selectedTime ? 'true' : 'false'}" aria-label="${slot.time}, ${slot.available ? 'disponible' : 'no disponible'}"${slot.available ? '' : ' disabled'}><span aria-hidden="true"></span><time>${slot.time}</time><small>${slot.available ? 'Disponible' : 'No disponible'}</small></button>`).join('')
                    : '<p class="unit-consultation-no-slots">No hay horarios disponibles para esta fecha. Selecciona otra fecha, medico o consultorio.</p>';
                  renderBookingSummary();
                };
                const validateNewAppointment = () => {
                  if (!selectedPatient) {
                    showWizardError('Selecciona un paciente del catalogo para agendar la cita.');
                    newDialog?.querySelector('[data-unit-new-patient-search]')?.focus();
                    return false;
                  }
                  if (normalize(newField('platform_number')?.value) !== normalize(selectedPatient.platform_number)) {
                    showWizardError('El numero de ID de la plataforma debe corresponder al paciente seleccionado.');
                    newField('platform_number')?.focus();
                    return false;
                  }
                  const required = ['platform_number', 'specialty', 'doctor_id', 'procedure_area_id', 'appointment_date', 'duration', 'priority', 'reason'];
                  const invalid = required.map((name) => newField(name)).find((field) => !field?.value || !field.checkValidity());
                  if (invalid) {
                    showWizardError('Completa todos los campos obligatorios de la cita.');
                    invalid.reportValidity();
                    return false;
                  }
                  if (!newField('appointment_time').value) {
                    showWizardError('Selecciona un horario disponible para agendar la cita.');
                    const firstAvailableSlot = newDialog?.querySelector('[data-unit-new-slot]:not(:disabled)');
                    (firstAvailableSlot || newField('appointment_date'))?.focus();
                    return false;
                  }
                  return true;
                };
                const resetNewAppointment = () => {
                  newForm?.reset();
                  selectedPatient = null;
                  newField('patient_id').value = '';
                  newField('platform_number').value = '';
                  newField('appointment_time').value = '';
                  newField('appointment_time').dataset.oldValue = '';
                  const today = dateKey(new Date());
                  const requestedDate = state.date >= today ? state.date : today;
                  newField('appointment_date').min = today;
                  newField('appointment_date').value = requestedDate;
                  const search = newDialog?.querySelector('[data-unit-new-patient-search]');
                  const selected = newDialog?.querySelector('[data-unit-new-selected-patient]');
                  const detailsToggle = newDialog?.querySelector('[data-unit-new-patient-details-toggle]');
                  const details = newDialog?.querySelector('[data-unit-new-patient-details]');
                  const preview = newDialog?.querySelector('[data-unit-new-selection-preview]');
                  const email = newDialog?.querySelector('[data-unit-new-contact-email]');
                  if (search) search.value = '';
                  if (email) email.value = '';
                  Object.values(patientAutocompletes).forEach((autocomplete) => {
                    if (autocomplete.results) autocomplete.results.innerHTML = '';
                  });
                  closePatientResults();
                  if (selected) selected.hidden = true;
                  if (detailsToggle) {
                    detailsToggle.hidden = true;
                    detailsToggle.setAttribute('aria-expanded', 'false');
                  }
                  if (details) details.hidden = true;
                  if (preview) preview.hidden = true;
                  newDialog?.querySelectorAll('[data-unit-new-counter]').forEach((counter) => { counter.textContent = '0'; });
                  populateDoctors();
                  renderAvailableSlots();
                  showWizardError();
                };

                consultationCalendar.querySelectorAll('[data-unit-calendar-mode]').forEach((button) => button.addEventListener('click', () => {
                  state.mode = button.dataset.unitCalendarMode;
                  render();
                }));
                consultationCalendar.querySelector('[data-unit-calendar-previous]')?.addEventListener('click', () => moveDate(-1));
                consultationCalendar.querySelector('[data-unit-calendar-next]')?.addEventListener('click', () => moveDate(1));
                dateInput.addEventListener('change', () => dateInput.value && selectDate(dateInput.value));
                [specialtyInput, roomInput].forEach((input) => input.addEventListener('change', render));
                consultationCalendar.querySelector('[data-unit-calendar-mini-previous]')?.addEventListener('click', () => {
                  state.miniDate = new Date(state.miniDate.getFullYear(), state.miniDate.getMonth() - 1, 1);
                  renderMiniCalendar();
                });
                consultationCalendar.querySelector('[data-unit-calendar-mini-next]')?.addEventListener('click', () => {
                  state.miniDate = new Date(state.miniDate.getFullYear(), state.miniDate.getMonth() + 1, 1);
                  renderMiniCalendar();
                });
                consultationCalendar.addEventListener('click', (event) => {
                  const dateButton = event.target.closest('[data-unit-calendar-select-date]');
                  if (dateButton) selectDate(dateButton.dataset.unitCalendarSelectDate);
                  const appointmentButtonTarget = event.target.closest('[data-unit-calendar-appointment]');
                  if (appointmentButtonTarget) openAppointment(appointmentButtonTarget.dataset.unitCalendarAppointment);
                });
                dialog?.querySelector('[data-unit-calendar-dialog-close]')?.addEventListener('click', () => dialog.close());
                appointmentDialogTabs.forEach((tab) => {
                  tab.addEventListener('click', () => setAppointmentDialogTab(tab.dataset.unitCalendarDialogTab));
                });
                dialog?.querySelector('[data-unit-calendar-edit]')?.addEventListener('click', () => openEditAppointment(activeAppointment));
                dialog?.querySelector('[data-unit-calendar-reschedule]')?.addEventListener('click', () => openEditAppointment(activeAppointment, true));
                dialog?.querySelector('[data-unit-calendar-reminder]')?.addEventListener('click', () => {
                  if (!activeAppointment || !reminderFeedback) return;
                  reminderFeedback.textContent = activeAppointment.patient_phone === 'Sin telefono'
                    ? 'El paciente no tiene un telefono registrado para preparar el recordatorio.'
                    : `El canal de mensajeria aun no esta configurado. Telefono de contacto: ${activeAppointment.patient_phone}.`;
                  reminderFeedback.hidden = false;
                });
                dialog?.querySelector('[data-unit-calendar-cancel]')?.addEventListener('click', () => openCancelAppointment(activeAppointment));
                editDialog?.querySelectorAll('[data-unit-calendar-edit-close]').forEach((button) => button.addEventListener('click', () => editDialog.close()));
                cancelDialog?.querySelectorAll('[data-unit-calendar-cancel-close]').forEach((button) => button.addEventListener('click', () => cancelDialog.close()));
                newDialog?.querySelectorAll('[data-unit-calendar-new-close]').forEach((button) => button.addEventListener('click', () => newDialog.close()));
                [dialog, editDialog, cancelDialog, newDialog].forEach(closeOnBackdrop);
                newAppointmentButton?.addEventListener('click', () => {
                  resetNewAppointment();
                  newDialog?.showModal();
                  newDialog?.querySelector('[data-unit-new-patient-search]')?.focus();
                });
                Object.entries(patientAutocompletes).forEach(([type, autocomplete]) => {
                  autocomplete.input?.addEventListener('focus', () => renderPatientResults(type, autocomplete.input.value));
                  autocomplete.input?.addEventListener('keydown', (event) => handlePatientAutocompleteKeydown(type, event));
                  autocomplete.results?.addEventListener('click', (event) => {
                    const patientButton = event.target.closest('[data-unit-new-patient]');
                    if (patientButton) selectPatient(patientButton.dataset.unitNewPatient);
                  });
                });
                patientAutocompletes.search.input?.addEventListener('input', (event) => {
                  if (selectedPatient && normalize(event.target.value) !== normalize(selectedPatient.name)) {
                    hideSelectedPatient();
                    if (patientAutocompletes.platform.input) patientAutocompletes.platform.input.value = '';
                  }
                  renderPatientResults('search', event.target.value);
                });
                newDialog?.querySelector('[data-unit-new-patient-clear]')?.addEventListener('click', clearPatient);
                patientAutocompletes.platform.input?.addEventListener('input', (event) => {
                  const patient = consultationCalendarPatients.find((item) => normalize(item.platform_number) === normalize(event.target.value));
                  if (patient) {
                    selectPatient(patient.id);
                    return;
                  }
                  if (selectedPatient) {
                    hideSelectedPatient();
                    if (patientAutocompletes.search.input) patientAutocompletes.search.input.value = '';
                  }
                  renderPatientResults('platform', event.target.value);
                });
                newDialog?.addEventListener('click', (event) => {
                  if (!event.target.closest('[data-unit-patient-autocomplete]')) closePatientResults();
                });
                newDialog?.querySelector('[data-unit-new-patient-details-toggle]')?.addEventListener('click', (event) => {
                  const details = newDialog.querySelector('[data-unit-new-patient-details]');
                  const expanded = event.currentTarget.getAttribute('aria-expanded') === 'true';
                  event.currentTarget.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                  if (details) details.hidden = expanded;
                });
                newField('specialty')?.addEventListener('change', () => {
                  populateDoctors();
                  renderAvailableSlots();
                });
                newDialog?.querySelectorAll('input[name="modality"]').forEach((input) => input.addEventListener('change', () => {
                  renderBookingSummary();
                  showWizardError();
                }));
                ['doctor_id', 'procedure_area_id', 'appointment_date', 'duration'].forEach((name) => newField(name)?.addEventListener('change', () => {
                  newField('appointment_time').value = '';
                  newField('appointment_time').dataset.oldValue = '';
                  renderAvailableSlots();
                }));
                newDialog?.querySelector('[data-unit-new-week]')?.addEventListener('click', (event) => {
                  const day = event.target.closest('[data-unit-new-day]');
                  if (!day || day.disabled) return;
                  newField('appointment_date').value = day.dataset.unitNewDay;
                  newField('appointment_time').value = '';
                  newField('appointment_time').dataset.oldValue = '';
                  renderAvailableSlots();
                  showWizardError();
                });
                newDialog?.querySelectorAll('[data-unit-new-date-shift]').forEach((button) => button.addEventListener('click', () => {
                  const date = parseDate(newField('appointment_date').value || dateKey(new Date()));
                  date.setDate(date.getDate() + Number(button.dataset.unitNewDateShift));
                  const value = dateKey(date);
                  if (value < newField('appointment_date').min) return;
                  newField('appointment_date').value = value;
                  newField('appointment_time').value = '';
                  newField('appointment_time').dataset.oldValue = '';
                  renderAvailableSlots();
                }));
                newField('appointment_time')?.addEventListener('change', (event) => {
                  newDialog.querySelectorAll('[data-unit-new-slot]').forEach((button) => {
                    const selected = button.dataset.unitNewSlot === event.target.value;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                  });
                  renderBookingSummary();
                  showWizardError();
                });
                newDialog?.querySelector('[data-unit-new-slots]')?.addEventListener('click', (event) => {
                  const slot = event.target.closest('[data-unit-new-slot]');
                  if (!slot || slot.disabled) return;
                  newField('appointment_time').value = slot.dataset.unitNewSlot;
                  newDialog.querySelectorAll('[data-unit-new-slot]').forEach((button) => {
                    const selected = button === slot;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                  });
                  renderBookingSummary();
                  showWizardError();
                });
                newDialog?.querySelector('[data-unit-new-reset]')?.addEventListener('click', () => {
                  resetNewAppointment();
                  newDialog?.querySelector('[data-unit-new-patient-search]')?.focus();
                });
                newDialog?.querySelectorAll('textarea[maxlength]').forEach((textarea) => textarea.addEventListener('input', () => {
                  const counter = newDialog.querySelector(`[data-unit-new-counter="${textarea.name}"]`);
                  if (counter) counter.textContent = String(textarea.value.length);
                }));
                [editForm, cancelForm].forEach((form) => form?.addEventListener('submit', (event) => {
                  if (!form.checkValidity()) return;
                  const submitter = event.submitter;
                  if (submitter) {
                    submitter.disabled = true;
                    submitter.setAttribute('aria-busy', 'true');
                  }
                }));
                newForm?.addEventListener('submit', (event) => {
                  if (!validateNewAppointment()) {
                    event.preventDefault();
                    return;
                  }
                  const submitter = event.submitter;
                  if (submitter) {
                    submitter.disabled = true;
                    submitter.setAttribute('aria-busy', 'true');
                  }
                });
                populateDoctors({ preserveTime: true });
                renderAvailableSlots();
                if (newDialog?.hasAttribute('data-open-on-load')) {
                  const serverError = wizardError?.textContent || '';
                  const restoredPatient = patientById(newField('patient_id')?.value);
                  if (restoredPatient) selectPatient(restoredPatient.id, { preserveError: true });
                  populateDoctors({ preserveTime: true });
                  renderAvailableSlots();
                  showWizardError(serverError);
                  newDialog.showModal();
                }
                consultationCalendar.appointmentFlow = {
                  open: (id) => openAppointment(id),
                  edit: (id) => openEditAppointment(appointmentById(id)),
                  reschedule: (id) => openEditAppointment(appointmentById(id), true),
                  cancel: (id) => openCancelAppointment(appointmentById(id)),
                };
                consultationCalendar.selectRoom = (roomId, date = state.date) => {
                  if ([...roomInput.options].some((option) => option.value === String(roomId))) {
                    roomInput.value = String(roomId);
                  }
                  selectDate(date);
                };
                render();
              };
              const initializeConsultationRooms = () => {
                if (!consultationRoomsWorkspace) return;

                const rooms = consultationCalendarRooms.filter((room) => room.registered !== false);
                const searchInput = consultationRoomsWorkspace.querySelector('[data-unit-room-search]');
                const searchEmpty = consultationRoomsWorkspace.querySelector('[data-unit-room-search-empty]');
                const roomSelect = consultationRoomsWorkspace.querySelector('[data-unit-room-select]');
                const specialtySelect = consultationRoomsWorkspace.querySelector('[data-unit-room-specialty]');
                const agendaBody = consultationRoomsWorkspace.querySelector('[data-unit-room-agenda-body]');
                const agendaDate = consultationRoomsWorkspace.querySelector('[data-unit-room-agenda-date]');
                const editLink = consultationRoomsWorkspace.querySelector('[data-unit-room-edit]');
                const moreButton = consultationRoomsWorkspace.querySelector('[data-unit-room-more]');
                const moreMenu = consultationRoomsWorkspace.querySelector('[data-unit-room-more-menu]');
                const roomButtons = [...consultationRoomsWorkspace.querySelectorAll('[data-unit-room-id]')];
                if (!rooms.length) return;

                const parseDate = (value) => {
                  const [year, month, day] = value.split('-').map(Number);
                  return new Date(year, month - 1, day);
                };
                const dateKey = (date) => [
                  date.getFullYear(),
                  String(date.getMonth() + 1).padStart(2, '0'),
                  String(date.getDate()).padStart(2, '0'),
                ].join('-');
                const longDate = (value) => {
                  const label = parseDate(value).toLocaleDateString('es-MX', {
                    weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
                  });
                  return label.charAt(0).toUpperCase() + label.slice(1);
                };
                const dayLabels = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
                const state = {
                  roomId: rooms[0].id,
                  date: consultationRoomsWorkspace.dataset.defaultDate,
                  specialty: '',
                };
                const roomById = (id) => rooms.find((room) => room.id === String(id));
                const roomAppointments = (roomId, date, specialty = '') => consultationCalendarAppointments
                  .filter((appointment) => (
                    appointment.room_id === String(roomId)
                    && appointment.date === date
                    && (!specialty || appointment.specialty === specialty)
                  ))
                  .sort((left, right) => left.time.localeCompare(right.time));
                const setRoomField = (field, value) => {
                  consultationRoomsWorkspace.querySelectorAll(`[data-unit-room-field="${field}"]`).forEach((target) => {
                    target.textContent = value || 'Sin registro';
                  });
                };
                const setRoomMetric = (field, value) => {
                  const target = consultationRoomsWorkspace.querySelector(`[data-unit-room-metric="${field}"]`);
                  if (target) target.textContent = value || 'Sin registro';
                };
                const scheduleSummary = (room) => {
                  const schedules = Array.isArray(room.schedules) ? room.schedules : [];
                  const currentDay = parseDate(state.date).getDay();
                  const current = schedules.find((schedule) => Number(schedule.day) === currentDay)
                    || schedules[0]
                    || { start: '08:00', end: '16:00' };
                  const days = [...new Set(schedules.map((schedule) => Number(schedule.day)))].sort((a, b) => a - b);
                  let daysLabel = 'Lunes a Viernes';
                  if (days.length && days.join(',') !== '1,2,3,4,5') {
                    daysLabel = days.map((day) => dayLabels[day]).join(', ');
                  }

                  return { hours: `${current.start} - ${current.end}`, days: daysLabel };
                };
                const closeRowMenus = (except = null) => {
                  consultationRoomsWorkspace.querySelectorAll('[data-unit-room-row-menu]').forEach((menu) => {
                    if (menu !== except) menu.hidden = true;
                  });
                  consultationRoomsWorkspace.querySelectorAll('[data-unit-room-appointment-menu]').forEach((button) => {
                    const menu = button.parentElement?.querySelector('[data-unit-room-row-menu]');
                    button.setAttribute('aria-expanded', menu && !menu.hidden ? 'true' : 'false');
                  });
                };
                const renderAgenda = (room) => {
                  const allAppointments = roomAppointments(room.id, state.date);
                  const appointments = roomAppointments(room.id, state.date, state.specialty);
                  const schedule = scheduleSummary(room);
                  const assignedAppointment = allAppointments.find((appointment) => appointment.doctor);
                  agendaDate.textContent = longDate(state.date);
                  setRoomMetric('patients', `${allAppointments.length} / ${room.capacity}`);
                  setRoomMetric('schedule', schedule.hours);
                  setRoomMetric('days', schedule.days);
                  setRoomMetric('doctor', assignedAppointment?.doctor || room.doctor_name);
                  setRoomMetric('license', assignedAppointment?.doctor_license || room.doctor_license);
                  setRoomMetric('specialty', room.specialty);

                  agendaBody.innerHTML = appointments.length
                    ? appointments.map((appointment) => {
                      const locked = ['cancelled', 'no_show', 'completed'].includes(appointment.status_value);
                      const disabled = locked ? ' disabled' : '';
                      return `<tr data-unit-room-agenda-row="${escapeMarkup(appointment.id)}">
                        <td><time>${escapeMarkup(appointment.time)}</time></td>
                        <td><strong>${escapeMarkup(appointment.patient)}</strong><small>ID. ${escapeMarkup(appointment.patient_platform)}</small></td>
                        <td>${appointment.patient_age ? `${escapeMarkup(appointment.patient_age)} a&ntilde;os` : 'Sin edad'}</td>
                        <td>${escapeMarkup(appointment.reason)}</td>
                        <td><span class="unit-consultation-room-appointment-state is-${escapeMarkup(appointment.status)}"><i aria-hidden="true"></i>${escapeMarkup(appointment.status_label)}</span></td>
                        <td>
                          <div class="unit-consultation-room-row-actions">
                            <button type="button" class="is-view" data-unit-room-appointment-view="${escapeMarkup(appointment.id)}">Ver</button>
                            <div class="unit-consultation-room-row-more">
                              <button type="button" data-unit-room-appointment-menu aria-label="Mas acciones para ${escapeMarkup(appointment.patient)}" aria-expanded="false">&#8942;</button>
                              <div data-unit-room-row-menu hidden>
                                <button type="button" data-unit-room-appointment-action="edit" data-appointment-id="${escapeMarkup(appointment.id)}"${disabled}>Editar cita</button>
                                <button type="button" data-unit-room-appointment-action="reschedule" data-appointment-id="${escapeMarkup(appointment.id)}"${disabled}>Reprogramar</button>
                                <button type="button" class="is-danger" data-unit-room-appointment-action="cancel" data-appointment-id="${escapeMarkup(appointment.id)}"${disabled}>Cancelar cita</button>
                              </div>
                            </div>
                          </div>
                        </td>
                      </tr>`;
                    }).join('')
                    : '<tr><td colspan="6" class="unit-consultation-room-agenda-empty">No hay citas programadas para este consultorio y fecha.</td></tr>';
                  closeRowMenus();
                };
                const render = () => {
                  const room = roomById(state.roomId) || rooms[0];
                  state.roomId = room.id;
                  setRoomField('name', room.name);
                  setRoomField('specialty', room.specialty);
                  setRoomField('capacity', `${room.capacity} ${Number(room.capacity) === 1 ? 'paciente' : 'pacientes'}`);
                  setRoomField('location', `Piso ${room.floor} - ${room.location}`);
                  const status = consultationRoomsWorkspace.querySelector('[data-unit-room-status]');
                  if (status) {
                    status.className = `is-status${room.status === 'active' ? '' : ' is-inactive'}`;
                    status.innerHTML = `<i aria-hidden="true"></i>${escapeMarkup(room.status_label)}`;
                  }
                  if (editLink) editLink.href = room.edit_url;
                  if (roomSelect) roomSelect.value = room.id;
                  roomButtons.forEach((button) => {
                    const active = button.dataset.unitRoomId === room.id;
                    button.classList.toggle('is-active', active);
                    button.setAttribute('aria-selected', active ? 'true' : 'false');
                  });
                  renderAgenda(room);
                };
                const filterRoomList = () => {
                  const needle = normalize(searchInput?.value);
                  let visible = 0;
                  roomButtons.forEach((button) => {
                    const matches = !needle || normalize(button.dataset.search).includes(needle);
                    button.hidden = !matches;
                    if (matches) visible += 1;
                  });
                  if (searchEmpty) searchEmpty.hidden = visible !== 0;
                };
                const moveDate = (days) => {
                  const date = parseDate(state.date);
                  date.setDate(date.getDate() + days);
                  state.date = dateKey(date);
                  render();
                };

                searchInput?.addEventListener('input', filterRoomList);
                roomSelect?.addEventListener('change', () => {
                  state.roomId = roomSelect.value;
                  render();
                });
                specialtySelect?.addEventListener('change', () => {
                  state.specialty = specialtySelect.value;
                  render();
                });
                consultationRoomsWorkspace.querySelector('[data-unit-room-date-previous]')?.addEventListener('click', () => moveDate(-1));
                consultationRoomsWorkspace.querySelector('[data-unit-room-date-next]')?.addEventListener('click', () => moveDate(1));
                consultationRoomsWorkspace.querySelector('[data-unit-room-date-today]')?.addEventListener('click', () => {
                  state.date = dateKey(new Date());
                  render();
                });
                consultationRoomsWorkspace.querySelector('[data-unit-room-list]')?.addEventListener('click', (event) => {
                  const button = event.target.closest('[data-unit-room-id]');
                  if (!button) return;
                  state.roomId = button.dataset.unitRoomId;
                  render();
                });
                moreButton?.addEventListener('click', () => {
                  const willOpen = moreMenu?.hidden ?? false;
                  if (moreMenu) moreMenu.hidden = !willOpen;
                  moreButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                });
                consultationRoomsWorkspace.querySelector('[data-unit-room-show-calendar]')?.addEventListener('click', () => {
                  if (moreMenu) moreMenu.hidden = true;
                  moreButton?.setAttribute('aria-expanded', 'false');
                  consultationCalendar?.selectRoom?.(state.roomId, state.date);
                  setConsultationSection('home');
                });
                consultationRoomsWorkspace.addEventListener('click', (event) => {
                  const viewButton = event.target.closest('[data-unit-room-appointment-view]');
                  if (viewButton) {
                    consultationCalendar?.appointmentFlow?.open(viewButton.dataset.unitRoomAppointmentView);
                    return;
                  }
                  const menuButton = event.target.closest('[data-unit-room-appointment-menu]');
                  if (menuButton) {
                    const menu = menuButton.parentElement?.querySelector('[data-unit-room-row-menu]');
                    const willOpen = menu?.hidden ?? false;
                    closeRowMenus(menu);
                    if (menu) menu.hidden = !willOpen;
                    menuButton.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                    return;
                  }
                  const actionButton = event.target.closest('[data-unit-room-appointment-action]');
                  if (actionButton && !actionButton.disabled) {
                    closeRowMenus();
                    consultationCalendar?.appointmentFlow?.[actionButton.dataset.unitRoomAppointmentAction]?.(actionButton.dataset.appointmentId);
                  }
                });
                document.addEventListener('click', (event) => {
                  if (moreMenu && !event.target.closest('.unit-consultation-room-more')) {
                    moreMenu.hidden = true;
                    moreButton?.setAttribute('aria-expanded', 'false');
                  }
                  if (!event.target.closest('.unit-consultation-room-row-more')) closeRowMenus();
                });
                consultationRoomsWorkspace.addEventListener('keydown', (event) => {
                  if (event.key !== 'Escape') return;
                  if (moreMenu) moreMenu.hidden = true;
                  moreButton?.setAttribute('aria-expanded', 'false');
                  closeRowMenus();
                });
                consultationRoomsWorkspace.refresh = render;
                render();
              };
              const setConsultationSection = (key) => {
                consultationTabs.forEach((tab) => {
                  const active = tab.dataset.unitConsultationTab === key;
                  tab.classList.toggle('is-active', active);
                  tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                consultationViews.forEach((view) => {
                  view.hidden = view.dataset.unitConsultationView !== key;
                });

                const consultationPanel = root.querySelector('[data-unit-consultation-panel]');
                consultationPanel?.classList.toggle('is-rooms-view', key === 'rooms');
                const toggle = consultationPanel?.querySelector('[data-unit-service-operation-toggle]');
                if (toggle) {
                  const agendaOpen = key === 'agenda';
                  toggle.setAttribute('aria-expanded', agendaOpen ? 'true' : 'false');
                  toggle.textContent = agendaOpen ? 'Volver al resumen' : 'Ver Operacion';
                }

                if (key === 'agenda') {
                  const operation = consultationPanel?.querySelector('[data-unit-service-operation]');
                  if (operation) applyFilters(operation);
                }
                if (key === 'rooms') consultationRoomsWorkspace?.refresh?.();
              };
              const initializeRequestBoards = () => {
                root.querySelectorAll('[data-unit-request-board]').forEach((board) => {
                  const filters = [...board.querySelectorAll('[data-unit-request-filter]')];
                  const rows = [...board.querySelectorAll('[data-unit-request-row]')];
                  const noResults = board.querySelector('[data-unit-request-no-results]');
                  const dialog = board.querySelector('[data-unit-nutrition-dialog]');
                  const openButton = board.querySelector('[data-unit-nutrition-open]');
                  const showNutritionDialog = () => {
                    if (!dialog || dialog.open) return;
                    dialog.showModal();
                    window.requestAnimationFrame(() => {
                      const shell = dialog.querySelector('.unit-consultation-flow-shell');
                      if (shell) shell.scrollTop = 0;
                      dialog.querySelector('[data-unit-nutrition-close]')?.focus({ preventScroll: true });
                    });
                  };

                  const applyRequestFilter = (filter) => {
                    const categories = filter === 'all' ? [] : filter.split(',');
                    let visible = 0;
                    filters.forEach((button) => {
                      const active = button.dataset.unitRequestFilter === filter;
                      button.classList.toggle('is-active', active);
                      button.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    rows.forEach((row) => {
                      const matches = filter === 'all' || categories.includes(row.dataset.category);
                      row.hidden = !matches;
                      if (matches) visible += 1;
                    });
                    if (noResults) noResults.hidden = rows.length === 0 || visible > 0;
                    board.dataset.activeFilter = filter;
                  };

                  filters.forEach((button) => button.addEventListener('click', () => {
                    applyRequestFilter(button.dataset.unitRequestFilter);
                  }));
                  board.querySelectorAll('[data-unit-nutrition-status]').forEach((button) => {
                    button.addEventListener('click', () => applyRequestFilter(button.dataset.unitNutritionStatus));
                  });
                  openButton?.addEventListener('click', showNutritionDialog);
                  dialog?.querySelectorAll('[data-unit-nutrition-close]').forEach((button) => {
                    button.addEventListener('click', () => dialog.close());
                  });
                  dialog?.addEventListener('click', (event) => {
                    if (event.target === dialog) dialog.close();
                  });
                  if (dialog?.hasAttribute('data-open-on-load')) showNutritionDialog();
                  applyRequestFilter('all');
                });
              };
              const bindOperation = (panel) => {
                const overview = panel.querySelector('[data-unit-service-overview]');
                const operation = panel.querySelector('[data-unit-service-operation]');
                const toggle = panel.querySelector('[data-unit-service-operation-toggle]');
                if (!overview || !operation || !toggle) return;

                toggle.addEventListener('click', () => {
                  if (panel.hasAttribute('data-unit-consultation-panel')) {
                    setConsultationSection(operation.hidden ? 'agenda' : 'home');
                    return;
                  }
                  const willOpen = operation.hidden;
                  operation.hidden = !willOpen;
                  overview.hidden = willOpen;
                  toggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
                  toggle.textContent = willOpen ? 'Volver al resumen' : 'Ver Operacion';
                  if (willOpen) applyFilters(operation);
                });
                operation.querySelectorAll('[data-unit-operation-filter]').forEach((input) => {
                  input.addEventListener(input.matches('input[type="search"]') ? 'input' : 'change', () => applyFilters(operation));
                });
                operation.querySelectorAll('[data-unit-status-filter]').forEach((button) => {
                  button.addEventListener('click', () => {
                    const status = operation.querySelector('[data-unit-operation-filter="status"]');
                    if (!status) return;
                    status.value = button.dataset.unitStatusFilter;
                    applyFilters(operation);
                  });
                });
              };
              const setActive = (id) => {
                tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.unitServiceTab === id));
                panels.forEach((panel) => panel.hidden = panel.dataset.unitServicePanel !== id);
                const activeTab = tabs.find((tab) => tab.dataset.unitServiceTab === id);
                if (consultationSubmenu) consultationSubmenu.hidden = activeTab?.dataset.unitServiceKey !== 'consulta-externa';
              };
              initializeConsultationCalendar();
              initializeConsultationRooms();
              initializeRequestBoards();
              panels.forEach(bindOperation);
              consultationTabs.forEach((tab) => tab.addEventListener('click', () => {
                setConsultationSection(tab.dataset.unitConsultationTab);
                tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
              }));
              tabs.forEach((tab) => tab.addEventListener('click', () => {
                setActive(tab.dataset.unitServiceTab);
                const url = new URL(window.location.href);
                url.searchParams.set('service', tab.dataset.unitServiceTab);
                window.history.replaceState({}, '', url);
                tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
              }));
              serviceCatalogSearch?.addEventListener('input', () => {
                const query = serviceCatalogSearch.value.trim().toLocaleLowerCase('es');
                let visible = 0;
                serviceCatalogRows.forEach((row) => {
                  row.hidden = query !== '' && !row.innerText.toLocaleLowerCase('es').includes(query);
                  if (!row.hidden) visible++;
                });
                const empty = root.querySelector('[data-unit-service-catalog-empty]');
                if (empty) empty.hidden = visible !== 0;
              });
              root.querySelectorAll('[data-unit-open-service]').forEach((button) => button.addEventListener('click', () => {
                tabs.find((tab) => tab.dataset.unitServiceTab === button.dataset.unitOpenService)?.click();
              }));
              root.querySelector('[data-unit-service-carousel-prev]')?.addEventListener('click', () => carousel.scrollBy({ left: -260, behavior: 'smooth' }));
              root.querySelector('[data-unit-service-carousel-next]')?.addEventListener('click', () => carousel.scrollBy({ left: 260, behavior: 'smooth' }));
            }

            document.querySelectorAll('[data-open-service-catalog]').forEach((button) => {
              button.addEventListener('click', () => document.querySelector(`[data-service-catalog-dialog="${button.dataset.openServiceCatalog}"]`).showModal());
            });
            document.querySelectorAll('[data-service-catalog-dialog]').forEach((dialog) => {
              dialog.querySelector('[data-close-service-catalog]').addEventListener('click', () => dialog.close());
              dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
            });
          })();
        </script>
      <?php endif; ?>

      <?php if($section === 'catalog'): ?>
        <?php
          $procedureAreaCount = collect(data_get($unit->metadata, 'procedure_areas', []))->count();
          $catalogModules = collect([
            ['section' => 'users', 'name' => 'Usuarios', 'description' => 'Perfiles, areas y autorizaciones operativas.', 'group' => 'operation', 'group_label' => 'Operacion', 'count' => $unit->operationalProfiles->count()],
            ['section' => 'patients', 'name' => 'Pacientes', 'description' => 'Pacientes atendidos y vinculados con la unidad.', 'group' => 'clinical', 'group_label' => 'Clinico', 'count' => $patients->count()],
            ['section' => 'doctors', 'name' => 'Medicos', 'description' => 'Personal medico adscrito y sus autorizaciones.', 'group' => 'clinical', 'group_label' => 'Clinico', 'count' => $unit->doctors->count()],
            ['section' => 'specialties', 'name' => 'Especialidades', 'description' => 'Especialidades disponibles desde el catalogo institucional.', 'group' => 'clinical', 'group_label' => 'Clinico', 'count' => $specialties->count()],
            ['section' => 'external-pharmacy', 'name' => 'Farmacia Externa', 'description' => 'Insumos institucionales habilitados para consulta.', 'group' => 'pharmacy', 'group_label' => 'Farmacia', 'count' => $externalPharmacyCatalog->count()],
            ['section' => 'procedure-areas', 'name' => 'Areas de Procedimiento', 'description' => 'Consultorios, salas y subunidades asistenciales.', 'group' => 'operation', 'group_label' => 'Operacion', 'count' => $procedureAreaCount],
            ['section' => 'medications', 'name' => 'Medicamentos', 'description' => 'Catalogo farmacologico institucional y universal.', 'group' => 'pharmacy', 'group_label' => 'Farmacia', 'count' => $medicationCatalog->count()],
          ]);
        ?>
        <div class="unit-catalog-screen" data-unit-catalog-screen>
          <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
            <div class="unit-service-panel-copy">
              <h2>Catalogo de la unidad</h2>
              <p>Accesos operativos, clinicos, asistenciales y farmaceuticos</p>
              <div class="unit-service-panel-badges">
                <span class="unit-native-status">Activo</span>
                <span><?php echo e($catalogModules->count()); ?> catalogos disponibles</span>
              </div>
            </div>
          </section>

          <div class="unit-catalog-toolbar" data-unit-catalog-controls>
            <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar catalogos">
              <button type="button" class="is-active" data-unit-catalog-group="all" role="tab" aria-selected="true">Todos</button>
              <button type="button" data-unit-catalog-group="operation" role="tab" aria-selected="false">Operacion</button>
              <button type="button" data-unit-catalog-group="clinical" role="tab" aria-selected="false">Clinicos</button>
              <button type="button" data-unit-catalog-group="pharmacy" role="tab" aria-selected="false">Farmacia</button>
            </div>
            <div class="unit-catalog-toolbar-actions">
              <label class="unit-catalog-search">
                <span class="sr-only">Buscar catalogo</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" placeholder="Buscar catalogo" data-unit-catalog-search>
              </label>
              <button type="button" class="unit-catalog-secondary-action" data-unit-catalog-export>Descargar catalogo</button>
            </div>
          </div>

          <section class="unit-native-table-card unit-catalog-content-card" data-unit-catalog-content>
            <div class="unit-native-table-scroll">
              <table class="unit-native-table unit-catalog-overview-table" data-unit-catalog-table>
                <thead><tr><th>Catalogo</th><th>Ambito</th><th>Registros</th><th>Estatus</th><th>Acciones</th></tr></thead>
                <tbody>
                  <?php $__currentLoopData = $catalogModules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr data-unit-catalog-row data-group="<?php echo e($module['group']); ?>" data-status="active">
                      <td><strong><?php echo e($module['name']); ?></strong><small><?php echo e($module['description']); ?></small></td>
                      <td><?php echo e($module['group_label']); ?></td>
                      <td><?php echo e($module['count']); ?></td>
                      <td><span class="unit-native-status">Disponible</span></td>
                      <td><a class="unit-native-button" href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => $module['section']])); ?>">Abrir</a></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  <tr data-unit-catalog-empty hidden><td colspan="5" class="unit-native-empty">No se encontraron catalogos.</td></tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      <?php endif; ?>

      <?php if($section === 'users'): ?>
        <div class="unit-catalog-screen" data-unit-catalog-screen>
          <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
            <div class="unit-service-panel-copy">
              <h2>Usuarios</h2>
              <p>Gestion de perfiles, areas de trabajo y autorizaciones de la unidad</p>
              <div class="unit-service-panel-badges">
                <span class="unit-native-status">Activo</span>
                <span><?php echo e($unit->operationalProfiles->count()); ?> usuarios registrados</span>
              </div>
            </div>
          </section>

          <div class="unit-catalog-toolbar" data-unit-catalog-controls>
            <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar usuarios operativos">
              <button type="button" class="is-active" data-unit-catalog-status="all" role="tab" aria-selected="true">Todos</button>
              <button type="button" data-unit-catalog-status="active" role="tab" aria-selected="false">Activos <span><?php echo e($unit->operationalProfiles->where('status', 'active')->count()); ?></span></button>
              <button type="button" data-unit-catalog-status="inactive" role="tab" aria-selected="false">Inactivos <span><?php echo e($unit->operationalProfiles->where('status', 'inactive')->count()); ?></span></button>
            </div>
            <div class="unit-catalog-toolbar-actions">
              <label class="unit-catalog-search">
                <span class="sr-only">Buscar usuario</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" placeholder="Buscar usuario" data-unit-catalog-search>
              </label>
              <button type="button" class="unit-catalog-secondary-action" data-operational-export>Descargar catalogo</button>
              <button type="button" class="unit-nutrition-new-request" data-unit-scroll-target="unit-operational-form"><span aria-hidden="true">+</span>Nuevo usuario</button>
            </div>
          </div>

          <div class="unit-native-two-column" data-unit-catalog-content>
          <section class="unit-native-form-card" id="unit-operational-form">
            <header>
              <h2 data-operational-form-title>Alta de usuario operativo</h2>
              <p>Actualiza informacion, rol, asignacion de area operativa y autorizaciones del usuario</p>
            </header>
            <form method="post" action="<?php echo e(route('unit.operational-users.store')); ?>" data-operational-user-form>
              <?php echo csrf_field(); ?>
              <input type="hidden" name="_method" value="patch" disabled data-operational-method>
              <label class="wide">Nombre completo<input name="name" required></label>
              <label>Usuario o correo<input name="username" required></label>
              <label>Contrasena<input name="password" type="password"></label>
              <label>Rol operativo<select name="role_label"><option>Responsable de Area</option><option>Operador</option></select></label>
              <label>Area autorizadora<select name="authority"><option>Direccion General</option><option>Direccion Administrativa</option></select></label>
              <label>Servicio asignado<select name="service"><?php $__currentLoopData = $unit->contractedServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option><?php echo e($contract->service?->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><option>Nutricion parenteral</option></select></label>

              <fieldset class="wide">
                <legend>Asignacion de Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  <?php $__currentLoopData = $operationalAreas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label>
                      <input type="radio" name="operational_area_id" value="<?php echo e($area->id); ?>" <?php if($loop->first): echo 'checked'; endif; ?> required>
                      <span><strong><?php echo e($area->label); ?></strong><small><?php echo e($areaChoices[$area->label] ?? 'Operacion y seguimiento del area.'); ?></small></span>
                    </label>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </fieldset>

              <fieldset class="wide">
                <legend>Acciones en Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  <?php $__currentLoopData = ['history' => ['Visualizar historial', 'Consultar solicitudes del modulo Area Operativa.'], 'detail' => ['Visualizar detalle', 'Abrir una solicitud y revisar su informacion.'], 'manage' => ['Administrar area operativa', 'Operar inventario, recetas, movimientos y almacenes.'], 'reports' => ['Descargar reportes', 'Exportar reportes del area operativa.']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission => [$action, $copy]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label>
                      <input type="checkbox" name="permissions[]" value="<?php echo e($permission); ?>" <?php if($loop->index < 2): echo 'checked'; endif; ?>>
                      <span><strong><?php echo e($action); ?></strong><small><?php echo e($copy); ?></small></span>
                    </label>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </fieldset>

              <button type="submit" data-operational-submit>Crear usuario</button><button type="button" data-operational-reset>Regresar</button>
            </form>
          </section>

          <section class="unit-native-table-card">
            <div class="unit-native-table-heading">
              <div><h2>Usuarios de la unidad</h2><p><?php echo e($unit->operationalProfiles->count()); ?> usuarios</p></div>
            </div>
            <div class="unit-native-user-list">
              <?php $__empty_1 = true; $__currentLoopData = $unit->operationalProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <article data-operational-user-row data-unit-catalog-row data-status="<?php echo e($profile->status); ?>">
                  <div>
                    <h3><?php echo e($profile->area?->label ?? 'Area Operativa'); ?> - <?php echo e($unit->name); ?></h3>
                    <p><?php echo e($profile->role_label ?? 'Responsable de Area'); ?> - <?php echo e($profile->user?->username ?? 'sin-usuario'); ?></p>
                    <p>Asignacion Area Operativa: <?php echo e($profile->area?->label ?? 'Sin area'); ?></p>
                    <p>Acciones Area Operativa: <?php echo e(collect($profile->permissions)->map(fn ($permission) => ['history' => 'Visualizar historial', 'detail' => 'Visualizar detalle', 'manage' => 'Administrar area', 'reports' => 'Descargar reportes'][$permission] ?? $permission)->implode(', ')); ?></p>
                  </div>
                  <span class="unit-native-status"><?php echo e($statusText($profile->status)); ?></span>
                  <button type="button" data-edit-operational-user data-url="<?php echo e(route('unit.operational-users.update', $profile)); ?>" data-name="<?php echo e($profile->user?->name); ?>" data-username="<?php echo e($profile->user?->username); ?>" data-password="" data-role="<?php echo e($profile->role_label); ?>" data-authority="<?php echo e(data_get($profile->metadata, 'authority', 'Direccion Administrativa')); ?>" data-service="<?php echo e(data_get($profile->metadata, 'service', 'Nutricion parenteral')); ?>" data-area="<?php echo e($profile->operational_area_id); ?>" data-permissions='<?php echo json_encode($profile->permissions ?? [], 15, 512) ?>'>Editar</button>
                  <form method="post" action="<?php echo e(route('unit.operational-users.destroy', $profile)); ?>" onsubmit="return confirm('Â¿Eliminar este perfil operativo?')"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button type="submit">Eliminar</button></form>
                </article>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <p class="unit-native-empty">Sin usuarios operativos registrados.</p>
              <?php endif; ?>
              <p class="unit-native-empty unit-catalog-inline-empty" data-unit-catalog-empty hidden>No se encontraron usuarios.</p>
            </div>
          </section>
        </div>
        </div>
        <script>
          (() => {
            const form = document.querySelector('[data-operational-user-form]'); const method = form.querySelector('[data-operational-method]'); const title = document.querySelector('[data-operational-form-title]'); const submit = form.querySelector('[data-operational-submit]');
            const reset = () => { form.reset(); form.action = <?php echo json_encode(route('unit.operational-users.store'), 15, 512) ?>; method.disabled = true; title.textContent = 'Alta de usuario operativo'; submit.textContent = 'Crear usuario'; form.elements.password.value = ''; };
            document.querySelectorAll('[data-edit-operational-user]').forEach((button) => button.addEventListener('click', () => { const data = button.dataset; form.action = data.url; method.disabled = false; title.textContent = 'Editar usuario operativo'; submit.textContent = 'Guardar cambios'; ['name','username','password'].forEach(key => form.elements[key].value = data[key] || ''); form.elements.role_label.value = data.role; form.elements.authority.value = data.authority; form.elements.service.value = data.service; form.querySelectorAll('[name="operational_area_id"]').forEach(input => input.checked = input.value === data.area); const permissions = JSON.parse(data.permissions || '[]'); form.querySelectorAll('[name="permissions[]"]').forEach(input => input.checked = permissions.includes(input.value)); window.scrollTo({top: 0, behavior: 'smooth'}); }));
            document.querySelector('[data-operational-reset]').addEventListener('click', reset);
            document.querySelector('[data-operational-export]').addEventListener('click', () => { const rows = [['Usuario operativo']]; document.querySelectorAll('[data-operational-user-row]').forEach(row => rows.push([row.innerText.replace(/\s+/g, ' ').trim()])); const blob = new Blob([rows.map(row => row.map(value => `"${value.replaceAll('"','""')}"`).join(',')).join('\n')], {type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='usuarios-operativos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          })();
        </script>
      <?php endif; ?>

      <?php if($section === 'patients'): ?>
        <div class="unit-catalog-screen" data-unit-catalog-screen>
          <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
            <div class="unit-service-panel-copy">
              <h2>Pacientes</h2>
              <p>Pacientes atendidos y vinculados con los servicios de la unidad</p>
              <div class="unit-service-panel-badges">
                <span class="unit-native-status">Activo</span>
                <span><?php echo e($patients->count()); ?> pacientes registrados</span>
              </div>
            </div>
          </section>

          <div class="unit-catalog-toolbar" data-unit-catalog-controls>
            <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar pacientes">
              <button type="button" class="is-active" data-unit-catalog-status="all" role="tab" aria-selected="true">Todos</button>
              <button type="button" data-unit-catalog-status="active" role="tab" aria-selected="false">Activos <span><?php echo e($patients->where('status', 'active')->count()); ?></span></button>
              <button type="button" data-unit-catalog-status="inactive" role="tab" aria-selected="false">Inactivos <span><?php echo e($patients->where('status', 'inactive')->count()); ?></span></button>
            </div>
            <div class="unit-catalog-toolbar-actions">
              <label class="unit-catalog-search">
                <span class="sr-only">Buscar paciente</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" placeholder="Buscar paciente" data-unit-catalog-search>
              </label>
              <button type="button" class="unit-catalog-secondary-action" data-unit-catalog-export>Descargar catalogo</button>
            </div>
          </div>

          <section class="unit-native-table-card unit-catalog-content-card" data-unit-catalog-content>
            <div class="unit-native-table-scroll">
              <table class="unit-native-table" data-unit-catalog-table>
                <thead><tr><th>Paciente</th><th>Servicio</th><th>Medico / Area</th><th>Estatus</th><th>Actualizacion</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $patients; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $patient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php $lastAppointment = $patient->appointments->first(); ?>
                    <tr data-unit-catalog-row data-status="<?php echo e($patient->status); ?>">
                      <td><strong><?php echo e($patient->full_name); ?></strong><span><?php echo e($patient->record_number ?? $patient->platform_number ?? 'Sin expediente'); ?></span></td>
                      <td><?php echo e($lastAppointment?->specialty ?? 'Sin servicio'); ?></td>
                      <td><?php echo e($lastAppointment?->doctor?->full_name ?? 'Sin medico'); ?></td>
                      <td><span class="unit-native-status"><?php echo e($statusText($patient->status)); ?></span></td>
                      <td><?php echo e($patient->updated_at?->format('d/m/Y')); ?></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="5" class="unit-native-empty">Sin pacientes dados de alta por el area operativa.</td></tr>
                  <?php endif; ?>
                  <tr data-unit-catalog-empty hidden><td colspan="5" class="unit-native-empty">No se encontraron pacientes.</td></tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      <?php endif; ?>

      <?php if($section === 'doctors'): ?>
        <div class="unit-catalog-screen" data-unit-catalog-screen>
          <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
            <div class="unit-service-panel-copy">
              <h2>Medicos</h2>
              <p>Personal medico, especialidades y autorizaciones de servicio</p>
              <div class="unit-service-panel-badges">
                <span class="unit-native-status">Activo</span>
                <span><?php echo e($unit->doctors->count()); ?> medicos adscritos</span>
              </div>
            </div>
          </section>

          <div class="unit-catalog-toolbar" data-unit-catalog-controls>
            <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar medicos">
              <button type="button" class="is-active" data-unit-catalog-status="all" role="tab" aria-selected="true">Todos</button>
              <button type="button" data-unit-catalog-status="active" role="tab" aria-selected="false">Activos <span><?php echo e($unit->doctors->where('status', 'active')->count()); ?></span></button>
              <button type="button" data-unit-catalog-status="pending" role="tab" aria-selected="false">Pendientes <span><?php echo e($unit->doctors->where('status', 'pending')->count()); ?></span></button>
              <button type="button" data-unit-catalog-status="inactive" role="tab" aria-selected="false">Inactivos <span><?php echo e($unit->doctors->where('status', 'inactive')->count()); ?></span></button>
            </div>
            <div class="unit-catalog-toolbar-actions">
              <label class="unit-catalog-search">
                <span class="sr-only">Buscar medico</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" placeholder="Buscar medico" data-unit-catalog-search>
              </label>
              <button type="button" class="unit-catalog-secondary-action" data-doctor-export>Descargar catalogo</button>
              <button type="button" class="unit-nutrition-new-request" data-open-doctor-dialog><span aria-hidden="true">+</span>Nuevo medico</button>
            </div>
          </div>

        <section class="unit-native-table-card unit-catalog-content-card" data-unit-catalog-content>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-doctors-table" data-unit-catalog-table>
              <thead><tr><th>Medico</th><th>Cedula</th><th>Especialidad</th><th>Servicio</th><th>Autorizacion</th><th>Acciones</th></tr></thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $unit->doctors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $doctor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr data-doctor-row data-unit-catalog-row data-status="<?php echo e($doctor->status); ?>">
                    <td><strong><?php echo e($doctor->full_name); ?></strong><small><?php echo e($doctor->subspecialty ?? 'Atencion clinica'); ?></small><span>Usuario plataforma: <?php echo e($doctor->user?->username ?? data_get($doctor->metadata, 'platform_user', 'Sin usuario de plataforma')); ?></span></td>
                    <td><?php echo e($doctor->professional_license ?? 'Sin cedula'); ?></td>
                    <td><?php echo e($doctor->specialty ?? 'Sin especialidad'); ?></td>
                    <td><?php echo e($doctor->service_name ?? 'Sin servicio'); ?></td>
                    <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-native-status', 'is-warning' => $doctor->status === 'pending']); ?>"><?php echo e($statusText($doctor->status)); ?></span></td>
                    <td><button type="button">Editar autorizaciones</button><form method="post" action="<?php echo e(route('unit.doctors.destroy', $doctor)); ?>" onsubmit="return confirm('Â¿Eliminar este medico?')"><?php echo csrf_field(); ?> <?php echo method_field('delete'); ?><button type="submit">Eliminar</button></form></td>
                  </tr>
                  <tr class="unit-doctor-authorization-row" data-doctor-authorization hidden><td colspan="6">
                    <form method="post" action="<?php echo e(route('unit.doctors.authorizations.update', $doctor)); ?>" class="unit-doctor-authorization-form">
                      <?php echo csrf_field(); ?> <?php echo method_field('put'); ?>
                      <header><strong>Editar autorizaciones</strong><span><?php echo e($doctor->full_name); ?></span></header>
                      <label>Estatus de autorizacion<select name="status"><option value="active" <?php if($doctor->status === 'active'): echo 'selected'; endif; ?>>Autorizado</option><option value="pending" <?php if($doctor->status === 'pending'): echo 'selected'; endif; ?>>Pendiente</option><option value="inactive" <?php if($doctor->status === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option></select></label>
                      <fieldset><legend>Servicios autorizados</legend><div class="unit-native-choice-grid">
                        <?php
                          $authorizedServices = data_get($doctor->metadata, 'services', [$doctor->service_name]);
                        ?>
                        <?php $__empty_2 = true; $__currentLoopData = $unit->contractedServices->pluck('service.name')->filter()->unique()->values(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                          <label><input type="checkbox" name="services[]" value="<?php echo e($serviceName); ?>" <?php if(in_array($serviceName, $authorizedServices, true)): echo 'checked'; endif; ?>><span><strong><?php echo e($serviceName); ?></strong><small>Servicio habilitado para este medico.</small></span></label>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                          <?php $__currentLoopData = $serviceChoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serviceName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><input type="checkbox" name="services[]" value="<?php echo e($serviceName); ?>" <?php if(in_array($serviceName, $authorizedServices, true)): echo 'checked'; endif; ?>><span><strong><?php echo e($serviceName); ?></strong><small>Servicio habilitado para este medico.</small></span></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endif; ?>
                      </div></fieldset>
                      <div class="unit-doctor-authorization-actions"><button type="submit">Guardar autorizaciones</button><button type="button" data-close-doctor-authorization>Regresar</button></div>
                    </form>
                  </td></tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="6" class="unit-native-empty">Sin medicos adscritos registrados.</td></tr>
                <?php endif; ?>
                <tr data-unit-catalog-empty hidden><td colspan="6" class="unit-native-empty">No se encontraron medicos.</td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <dialog class="unit-doctor-create-dialog" data-doctor-dialog aria-labelledby="unit-doctor-dialog-title">
          <form method="post" action="<?php echo e(route('unit.doctors.store')); ?>" class="unit-doctor-dialog-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="_doctor_form" value="1">
            <header class="unit-doctor-dialog-header">
              <div><h2 id="unit-doctor-dialog-title">Nuevo medico adscrito</h2><p>Complete la informacion del medico para registrarlo en el sistema.</p></div>
              <button type="button" class="unit-doctor-dialog-close" data-close-doctor-dialog aria-label="Cerrar" title="Cerrar">&times;</button>
            </header>
            <div class="unit-doctor-dialog-body">
              <?php if(old('_doctor_form') && $errors->any()): ?>
                <div class="unit-doctor-dialog-errors" role="alert"><strong>No se pudo guardar el medico.</strong><ul><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
              <?php endif; ?>
              <label><span>Nombre <em aria-hidden="true">*</em></span><input name="first_name" value="<?php echo e(old('first_name')); ?>" placeholder="Ej. Juan" required></label>
              <label><span>Apellido <em aria-hidden="true">*</em></span><input name="last_name" value="<?php echo e(old('last_name')); ?>" placeholder="Ej. Perez" required></label>
              <label><span>Usuario de la plataforma</span><input name="platform_user" value="<?php echo e(old('platform_user')); ?>" placeholder="Ej. juan.perez"></label>
              <label><span>Cedula profesional <em aria-hidden="true">*</em></span><input name="professional_license" value="<?php echo e(old('professional_license')); ?>" placeholder="Ej. 12345678" required></label>
              <label><span>Especialidad <em aria-hidden="true">*</em></span><select name="specialty" required><option value="">Selecciona una especialidad</option><?php $__currentLoopData = $specialties->pluck('specialty')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($specialty); ?>" <?php if(old('specialty') === $specialty): echo 'selected'; endif; ?>><?php echo e($specialty); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
              <label>Subespecialidad<input name="subspecialty" value="<?php echo e(old('subspecialty')); ?>"></label>
              <fieldset class="wide">
                <legend>Servicio adscrito <em aria-hidden="true">*</em></legend>
                <div class="unit-native-choice-grid">
                  <?php $__empty_1 = true; $__currentLoopData = $unit->contractedServices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <label><input type="checkbox" name="services[]" value="<?php echo e($contract->service?->name); ?>" <?php if(in_array($contract->service?->name, (array) old('services', old('_doctor_form') ? [] : [$unit->contractedServices->first()?->service?->name]), true)): echo 'checked'; endif; ?>><span><strong><?php echo e($contract->service?->name); ?></strong><small>Servicio habilitado para adscripcion medica.</small></span></label>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php $__currentLoopData = $serviceChoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $choice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><label><input type="checkbox" name="services[]" value="<?php echo e($choice); ?>" <?php if(in_array($choice, (array) old('services', old('_doctor_form') ? [] : [$serviceChoices[0] ?? null]), true)): echo 'checked'; endif; ?>><span><strong><?php echo e($choice); ?></strong><small>Servicio habilitado para adscripcion medica.</small></span></label><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  <?php endif; ?>
                </div>
              </fieldset>
            </div>
            <footer class="unit-doctor-dialog-actions"><button type="button" data-close-doctor-dialog>Cancelar</button><button type="submit" class="is-primary">Guardar</button></footer>
          </form>
        </dialog>
        <dialog class="unit-doctor-success-dialog" data-doctor-success-dialog aria-labelledby="unit-doctor-success-title">
          <button type="button" class="unit-doctor-dialog-close" data-close-doctor-success aria-label="Cerrar" title="Cerrar">&times;</button>
          <span class="unit-doctor-success-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="m7 12 3 3 7-7"/></svg></span>
          <h2 id="unit-doctor-success-title">Guardado con éxito</h2>
          <p>El medico fue registrado correctamente.</p>
          <button type="button" class="unit-doctor-success-action" data-close-doctor-success>Cerrar</button>
        </dialog>
        </div>
        <script>
          document.querySelector('[data-doctor-export]').addEventListener('click', () => { const rows=[['Medico','Cedula','Especialidad','Servicio','Autorizacion']]; document.querySelectorAll('[data-doctor-row]').forEach(row => rows.push([...row.cells].slice(0,5).map(cell => cell.innerText.trim()))); const blob=new Blob([rows.map(row=>row.map(value=>`"${value.replaceAll('"','""')}"`).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='catalogo-medicos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          document.querySelectorAll('[data-doctor-row]').forEach(row => { const panel = row.nextElementSibling; const editButton = row.querySelector('td:last-child > button'); editButton?.addEventListener('click', () => { document.querySelectorAll('[data-doctor-authorization]').forEach(item => item.hidden = item !== panel); panel.hidden = false; }); panel?.querySelector('[data-close-doctor-authorization]')?.addEventListener('click', () => panel.hidden = true); });
          const doctorDialog = document.querySelector('[data-doctor-dialog]');
          const doctorSuccessDialog = document.querySelector('[data-doctor-success-dialog]');
          document.querySelector('[data-open-doctor-dialog]')?.addEventListener('click', () => doctorDialog?.showModal());
          doctorDialog?.querySelectorAll('[data-close-doctor-dialog]').forEach(button => button.addEventListener('click', () => doctorDialog.close()));
          doctorSuccessDialog?.querySelectorAll('[data-close-doctor-success]').forEach(button => button.addEventListener('click', () => doctorSuccessDialog.close()));
          <?php if(old('_doctor_form') && $errors->any()): ?>
            doctorDialog?.showModal();
          <?php elseif(session('doctor_created')): ?>
            doctorSuccessDialog?.showModal();
          <?php endif; ?>
        </script>
      <?php endif; ?>

      <?php if($section === 'specialties'): ?>
        <div class="unit-catalog-screen" data-unit-catalog-screen>
          <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
            <div class="unit-service-panel-copy">
              <h2>Especialidades</h2>
              <p>Especialidades habilitadas desde el catalogo de la institucion</p>
              <div class="unit-service-panel-badges">
                <span class="unit-native-status">Activo</span>
                <span><?php echo e($specialties->count()); ?> especialidades habilitadas</span>
              </div>
            </div>
          </section>

          <div class="unit-catalog-toolbar" data-unit-catalog-controls>
            <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar especialidades">
              <button type="button" class="is-active" data-unit-catalog-status="all" role="tab" aria-selected="true">Todas</button>
              <button type="button" data-unit-catalog-status="active" role="tab" aria-selected="false">Activas <span><?php echo e($specialties->where('status', 'active')->count()); ?></span></button>
              <button type="button" data-unit-catalog-status="inactive" role="tab" aria-selected="false">Inactivas <span><?php echo e($specialties->where('status', 'inactive')->count()); ?></span></button>
            </div>
            <div class="unit-catalog-toolbar-actions">
              <label class="unit-catalog-search">
                <span class="sr-only">Buscar especialidad</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" placeholder="Buscar especialidad" data-unit-catalog-search>
              </label>
              <button type="button" class="unit-catalog-secondary-action" data-unit-catalog-export>Descargar catalogo</button>
            </div>
          </div>

          <section class="unit-native-table-card unit-catalog-content-card" data-unit-catalog-content>
            <div class="unit-native-table-scroll">
              <table class="unit-native-table" data-unit-catalog-table>
                <thead><tr><th>No.</th><th>Especialidad</th><th>Estatus</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $specialties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr data-unit-catalog-row data-status="<?php echo e($service->status); ?>">
                      <td><?php echo e($loop->iteration); ?></td>
                      <td><strong><?php echo e($service->specialty ?? $service->name); ?></strong><span><?php echo e($unit->institution?->name ?? 'Institucion'); ?> - Catalogo institucional</span></td>
                      <td><span class="unit-native-status"><?php echo e($statusText($service->status)); ?></span></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="3" class="unit-native-empty">Sin especialidades habilitadas.</td></tr>
                  <?php endif; ?>
                  <tr data-unit-catalog-empty hidden><td colspan="3" class="unit-native-empty">No se encontraron especialidades.</td></tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      <?php endif; ?>

      <?php if($section === 'procedure-areas'): ?>
        <?php
          $procedureCatalogs = [
            'consulting' => ['title' => 'Catalogo de consultorios', 'create' => 'Nuevo consultorio', 'singular' => 'consultorio', 'button' => 'Consultorios', 'empty' => 'Sin consultorios registrados.'],
            'infusion' => ['title' => 'Catalogo de salas de infusion', 'create' => 'Nueva sala de infusion', 'singular' => 'sala de infusion', 'button' => 'Salas de infusion', 'empty' => 'Sin salas de infusion registradas.'],
            'operating' => ['title' => 'Catalogo de quirofanos', 'create' => 'Nuevo quirofano', 'singular' => 'quirofano', 'button' => 'Quirofanos', 'empty' => 'Sin quirofanos registrados.'],
            'uci' => ['title' => 'Catalogo de UCI', 'create' => 'Nueva UCI', 'singular' => 'UCI', 'button' => 'UCI', 'empty' => 'Sin UCI registradas.'],
            'uti' => ['title' => 'Catalogo de UTI', 'create' => 'Nueva UTI', 'singular' => 'UTI', 'button' => 'UTI', 'empty' => 'Sin UTI registradas.'],
            'recovery' => ['title' => 'Catalogo de salas de recuperacion', 'create' => 'Nueva sala de recuperacion', 'singular' => 'sala de recuperacion', 'button' => 'Sala de recuperacion', 'empty' => 'Sin salas de recuperacion registradas.'],
          ];
          $procedureAreas = collect(data_get($unit->metadata, 'procedure_areas', []));
          $allProcedureAreas = $procedureAreas
              ->filter(fn ($area) => array_key_exists((string) data_get($area, 'type'), $procedureCatalogs))
              ->values();
          $requestedProcedureCatalog = request('create') ?: request('catalog');
          $editingProcedureArea = request('edit') ? $procedureAreas->firstWhere('id', request('edit')) : null;
          $editingProcedureCatalog = data_get($editingProcedureArea, 'type');
          $activeProcedureCatalog = $requestedProcedureCatalog === 'all'
              ? 'all'
              : (($requestedProcedureCatalog && array_key_exists($requestedProcedureCatalog, $procedureCatalogs))
                  ? $requestedProcedureCatalog
                  : (($editingProcedureCatalog && array_key_exists($editingProcedureCatalog, $procedureCatalogs)) ? $editingProcedureCatalog : 'all'));
          $scheduleDays = ['monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miercoles', 'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sabado', 'sunday' => 'Domingo'];
          $oldProcedureCatalog = (string) old('type', '');
          $initialProcedureCreateCatalog = array_key_exists($oldProcedureCatalog, $procedureCatalogs)
              ? $oldProcedureCatalog
              : (array_key_exists($activeProcedureCatalog, $procedureCatalogs) ? $activeProcedureCatalog : array_key_first($procedureCatalogs));
          $openProcedureCreateDialog = array_key_exists((string) request('create'), $procedureCatalogs)
              || ($errors->any() && ! $editingProcedureArea && array_key_exists($oldProcedureCatalog, $procedureCatalogs));
          $openProcedureEditDialog = (bool) $editingProcedureArea;
          $procedureAreaPayload = $procedureAreas->mapWithKeys(fn ($area) => [(string) data_get($area, 'id') => $area])->all();
          $editingProcedureLabel = $editingProcedureCatalog && array_key_exists($editingProcedureCatalog, $procedureCatalogs)
              ? $procedureCatalogs[$editingProcedureCatalog]['singular']
              : 'subunidad';
          $editingResponsible = old('responsible', data_get($editingProcedureArea, 'responsible', ''));
        ?>
        <div class="unit-catalog-screen unit-procedure-screen" data-unit-catalog-screen>
          <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
            <div class="unit-service-panel-copy">
              <h2>Areas de Procedimiento</h2>
              <p>Consultorios, salas y subunidades asistenciales de la unidad</p>
              <div class="unit-service-panel-badges">
                <span class="unit-native-status">Activo</span>
                <span><?php echo e($allProcedureAreas->count()); ?> subunidades registradas</span>
              </div>
            </div>
          </section>

          <div class="unit-catalog-toolbar unit-procedure-toolbar" data-unit-catalog-controls>
            <div class="unit-nutrition-request-tabs" data-procedure-carousel role="tablist" aria-label="Filtrar areas de procedimiento">
              <button type="button"
                      class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeProcedureCatalog === 'all']); ?>"
                      data-procedure-category="all"
                      role="tab"
                      aria-selected="<?php echo e($activeProcedureCatalog === 'all' ? 'true' : 'false'); ?>">Todos</button>
              <?php $__currentLoopData = $procedureCatalogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catalogKey => $catalog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <button type="button"
                        class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $catalogKey === $activeProcedureCatalog]); ?>"
                        data-procedure-category="<?php echo e($catalogKey); ?>"
                        role="tab"
                        aria-selected="<?php echo e($catalogKey === $activeProcedureCatalog ? 'true' : 'false'); ?>"><?php echo e($catalog['button']); ?></button>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            <div class="unit-catalog-toolbar-actions">
              <label class="unit-catalog-search">
                <span class="sr-only">Buscar subunidad</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" placeholder="Buscar subunidad" data-procedure-search>
              </label>
              <button type="button" class="unit-catalog-secondary-action" data-procedure-toolbar-export>Descargar catalogo</button>
              <?php $__currentLoopData = $procedureCatalogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catalogKey => $catalog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="unit-nutrition-new-request"
                   href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'create' => $catalogKey])); ?>"
                   data-procedure-create
                   data-procedure-create-toolbar="<?php echo e($catalogKey); ?>"
                   data-procedure-create-category="<?php echo e($catalogKey); ?>"
                   data-procedure-create-label="<?php echo e(ucfirst($catalog['singular'])); ?>"
                   <?php if(($activeProcedureCatalog === 'all' ? array_key_first($procedureCatalogs) : $activeProcedureCatalog) !== $catalogKey): ?> hidden <?php endif; ?>><span aria-hidden="true">+</span><?php echo e($catalog['create']); ?></a>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          </div>

        <div class="unit-procedure-catalogs" data-unit-catalog-content>
          <section class="unit-native-table-card unit-procedure-card" data-procedure-catalog="all" <?php if($activeProcedureCatalog !== 'all'): ?> hidden <?php endif; ?>>
            <div class="unit-native-table-scroll">
              <table class="unit-native-table unit-procedure-table">
                <caption class="sr-only">Catalogo de subunidades</caption>
                <thead><tr><th>Ubicacion</th><th>Piso</th><th>Numero de unidad</th><th>Capacidad simultanea</th><th>Responsable de area</th><th>Horario de atencion</th><th>Acciones</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $allProcedureAreas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php ($areaCatalog = $procedureCatalogs[(string) data_get($area, 'type')] ?? null); ?>
                    <?php ($activeSchedule = collect($area['schedule'] ?? [])->filter(fn ($day) => data_get($day, 'enabled'))->map(fn ($day, $key) => ($scheduleDays[$key] ?? ucfirst($key)).' '.data_get($day, 'start', '08:00').' - '.data_get($day, 'end', '16:00'))->implode(', ')); ?>
                    <tr data-procedure-row><td><?php echo e($area['location']); ?></td><td><?php echo e($area['floor']); ?></td><td><strong><?php echo e($area['unit_number']); ?></strong><small><?php echo e($areaCatalog['button'] ?? 'Subunidad'); ?></small></td><td><?php echo e($area['capacity']); ?> <?php echo e($area['capacity'] == 1 ? 'paciente simultaneo' : 'pacientes simultaneos'); ?></td><td><?php echo e($area['responsible']); ?></td><td><?php echo e($activeSchedule ?: 'Sin horario'); ?></td><td><a class="unit-native-button" href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'edit' => $area['id']])); ?>" data-procedure-edit data-procedure-edit-id="<?php echo e($area['id']); ?>" data-procedure-edit-action="<?php echo e(route('unit.procedure-areas.update', $area['id'])); ?>" data-procedure-edit-label="<?php echo e($areaCatalog['singular'] ?? 'subunidad'); ?>">Editar</a></td></tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="7" class="unit-native-empty">Sin subunidades registradas.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
          <?php $__currentLoopData = $procedureCatalogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $catalogKey => $catalog): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php ($catalogAreas = $procedureAreas->where('type', $catalogKey)->values()); ?>
            <section class="unit-native-table-card unit-procedure-card" data-procedure-catalog="<?php echo e($catalogKey); ?>" <?php if($catalogKey !== $activeProcedureCatalog): ?> hidden <?php endif; ?>>
              <div class="unit-native-table-scroll">
                <table class="unit-native-table unit-procedure-table">
                  <caption class="sr-only"><?php echo e($catalog['title']); ?></caption>
                  <thead><tr><th>Ubicacion</th><th>Piso</th><th>Numero de unidad</th><th>Capacidad simultanea</th><th>Responsable de area</th><th>Horario de atencion</th><th>Acciones</th></tr></thead>
                  <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $catalogAreas; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $area): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                      <?php ($activeSchedule = collect($area['schedule'] ?? [])->filter(fn ($day) => data_get($day, 'enabled'))->map(fn ($day, $key) => ($scheduleDays[$key] ?? ucfirst($key)).' '.data_get($day, 'start', '08:00').' - '.data_get($day, 'end', '16:00'))->implode(', ')); ?>
                      <tr data-procedure-row><td><?php echo e($area['location']); ?></td><td><?php echo e($area['floor']); ?></td><td><strong><?php echo e($area['unit_number']); ?></strong><small><?php echo e($catalog['button']); ?></small></td><td><?php echo e($area['capacity']); ?> <?php echo e($area['capacity'] == 1 ? 'paciente simultaneo' : 'pacientes simultaneos'); ?></td><td><?php echo e($area['responsible']); ?></td><td><?php echo e($activeSchedule ?: 'Sin horario'); ?></td><td><a class="unit-native-button" href="<?php echo e(route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'edit' => $area['id']])); ?>" data-procedure-edit data-procedure-edit-id="<?php echo e($area['id']); ?>" data-procedure-edit-action="<?php echo e(route('unit.procedure-areas.update', $area['id'])); ?>" data-procedure-edit-label="<?php echo e($catalog['singular']); ?>">Editar</a></td></tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                      <tr><td colspan="7" class="unit-native-empty"><?php echo e($catalog['empty']); ?></td></tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </section>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        </div>

        <dialog class="unit-procedure-dialog"
                data-procedure-create-dialog
                aria-labelledby="unit-procedure-dialog-title"
                <?php if($openProcedureCreateDialog): ?> data-open-on-load <?php endif; ?>>
          <form method="post" action="<?php echo e(route('unit.procedure-areas.store')); ?>" class="unit-procedure-dialog-form" data-procedure-create-form>
            <?php echo csrf_field(); ?>
            <header class="unit-procedure-dialog-header">
              <span class="unit-procedure-dialog-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10"/><path d="M8 7h4M8 11h4M8 15h4M2 21h20"/></svg>
              </span>
              <div>
                <strong id="unit-procedure-dialog-title">Nueva subunidad</strong>
                <span data-procedure-create-dialog-label><?php echo e(ucfirst($procedureCatalogs[$initialProcedureCreateCatalog]['singular'])); ?></span>
              </div>
              <button type="button" class="unit-procedure-dialog-close" data-procedure-dialog-close aria-label="Cerrar" title="Cerrar">&times;</button>
            </header>

            <div class="unit-procedure-dialog-body">
              <input type="hidden" name="unit" value="<?php echo e($unit->id); ?>">
              <input type="hidden" name="type" value="<?php echo e($initialProcedureCreateCatalog); ?>" data-procedure-create-type>

              <?php if($errors->any() && array_key_exists($oldProcedureCatalog, $procedureCatalogs)): ?>
                <div class="unit-procedure-dialog-error wide" role="alert" data-procedure-edit-error>
                  <strong>No fue posible guardar la subunidad.</strong>
                  <span><?php echo e($errors->first()); ?></span>
                </div>
              <?php endif; ?>

              <label>Ubicacion<input name="location" value="<?php echo e(old('location')); ?>" maxlength="180" autocomplete="off" autofocus required></label>
              <label>Piso<input name="floor" value="<?php echo e(old('floor')); ?>" maxlength="40" autocomplete="off" required></label>
              <label>Numero de unidad<input name="unit_number" value="<?php echo e(old('unit_number')); ?>" maxlength="80" autocomplete="off" required></label>
              <label>Capacidad simultanea de pacientes<input type="number" min="1" max="999" name="capacity" value="<?php echo e(old('capacity')); ?>" required></label>
              <label class="wide">Responsable de area<select name="responsible"><option value="" <?php if(old('responsible', '') === ''): echo 'selected'; endif; ?>>Sin responsable asignado</option><?php $__currentLoopData = $unit->operationalProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($profile->user?->name); ?>" <?php if(old('responsible') === $profile->user?->name): echo 'selected'; endif; ?>><?php echo e($profile->user?->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>

              <fieldset class="wide">
                <legend>Horario de atencion de la unidad</legend>
                <div class="unit-procedure-schedule">
                  <?php $__currentLoopData = $scheduleDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayKey => $dayLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($dayEnabled = (bool) old("schedule.$dayKey.enabled", $loop->iteration <= 5)); ?>
                    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-enabled' => $dayEnabled]); ?>" data-procedure-schedule-day>
                      <label><input type="hidden" name="schedule[<?php echo e($dayKey); ?>][enabled]" value="0"><input type="checkbox" name="schedule[<?php echo e($dayKey); ?>][enabled]" value="1" <?php if($dayEnabled): echo 'checked'; endif; ?> data-procedure-schedule-toggle><?php echo e($dayLabel); ?></label>
                      <span aria-hidden="true">+</span>
                      <label>Hora inicio<input type="time" name="schedule[<?php echo e($dayKey); ?>][start]" value="<?php echo e(old("schedule.$dayKey.start", '08:00')); ?>"></label>
                      <label>Hora fin<input type="time" name="schedule[<?php echo e($dayKey); ?>][end]" value="<?php echo e(old("schedule.$dayKey.end", '16:00')); ?>"></label>
                    </div>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </fieldset>
            </div>

            <footer class="unit-procedure-dialog-actions">
              <button type="button" data-procedure-dialog-close>Cancelar</button>
              <button type="submit" class="is-primary">Guardar subunidad</button>
            </footer>
          </form>
        </dialog>

        <dialog class="unit-procedure-dialog"
                data-procedure-edit-dialog
                aria-labelledby="unit-procedure-edit-dialog-title"
                <?php if($openProcedureEditDialog): ?> data-open-on-load <?php endif; ?>>
          <form method="post"
                action="<?php echo e($editingProcedureArea ? route('unit.procedure-areas.update', data_get($editingProcedureArea, 'id')) : ''); ?>"
                class="unit-procedure-dialog-form"
                data-procedure-edit-form>
            <?php echo csrf_field(); ?>
            <?php echo method_field('put'); ?>
            <header class="unit-procedure-dialog-header">
              <span class="unit-procedure-dialog-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 9h2a2 2 0 0 1 2 2v10"/><path d="M8 7h4M8 11h4M8 15h4M2 21h20"/></svg>
              </span>
              <div>
                <strong id="unit-procedure-edit-dialog-title" data-procedure-edit-title>Editar <?php echo e($editingProcedureLabel); ?></strong>
                <span data-procedure-edit-dialog-label>Actualiza la informacion de la unidad seleccionada.</span>
              </div>
              <button type="button" class="unit-procedure-dialog-close" data-procedure-edit-dialog-close aria-label="Cerrar" title="Cerrar">&times;</button>
            </header>

            <div class="unit-procedure-dialog-body">
              <input type="hidden" name="unit" value="<?php echo e($unit->id); ?>">
              <input type="hidden" name="type" value="<?php echo e(old('type', $editingProcedureCatalog)); ?>" data-procedure-edit-type>

              <?php if($openProcedureEditDialog && $errors->any()): ?>
                <div class="unit-procedure-dialog-error wide" role="alert">
                  <strong>No fue posible guardar los cambios.</strong>
                  <span><?php echo e($errors->first()); ?></span>
                </div>
              <?php endif; ?>

              <label>Ubicacion<input name="location" value="<?php echo e(old('location', data_get($editingProcedureArea, 'location', ''))); ?>" maxlength="180" autocomplete="off" autofocus required></label>
              <label>Piso<input name="floor" value="<?php echo e(old('floor', data_get($editingProcedureArea, 'floor', ''))); ?>" maxlength="40" autocomplete="off" required></label>
              <label>Numero de unidad<input name="unit_number" value="<?php echo e(old('unit_number', data_get($editingProcedureArea, 'unit_number', ''))); ?>" maxlength="80" autocomplete="off" required></label>
              <label>Capacidad simultanea de pacientes<input type="number" min="1" max="999" name="capacity" value="<?php echo e(old('capacity', data_get($editingProcedureArea, 'capacity', ''))); ?>" required></label>
              <label class="wide">Responsable de area<select name="responsible"><option value="" <?php if(in_array($editingResponsible, ['', 'Sin responsable'], true)): echo 'selected'; endif; ?>>Sin responsable asignado</option><?php $__currentLoopData = $unit->operationalProfiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $profile): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($profile->user?->name); ?>" <?php if($editingResponsible === $profile->user?->name): echo 'selected'; endif; ?>><?php echo e($profile->user?->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>

              <fieldset class="wide">
                <legend>Horario de atencion de la unidad</legend>
                <div class="unit-procedure-schedule">
                  <?php $__currentLoopData = $scheduleDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dayKey => $dayLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php ($dayEnabled = (bool) old("schedule.$dayKey.enabled", data_get($editingProcedureArea, "schedule.$dayKey.enabled", false))); ?>
                    <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-enabled' => $dayEnabled]); ?>" data-procedure-edit-schedule-day data-procedure-day="<?php echo e($dayKey); ?>">
                      <label><input type="hidden" name="schedule[<?php echo e($dayKey); ?>][enabled]" value="0"><input type="checkbox" name="schedule[<?php echo e($dayKey); ?>][enabled]" value="1" <?php if($dayEnabled): echo 'checked'; endif; ?> data-procedure-edit-schedule-toggle><?php echo e($dayLabel); ?></label>
                      <span aria-hidden="true">+</span>
                      <label>Hora inicio<input type="time" name="schedule[<?php echo e($dayKey); ?>][start]" value="<?php echo e(old("schedule.$dayKey.start", data_get($editingProcedureArea, "schedule.$dayKey.start", '08:00'))); ?>"></label>
                      <label>Hora fin<input type="time" name="schedule[<?php echo e($dayKey); ?>][end]" value="<?php echo e(old("schedule.$dayKey.end", data_get($editingProcedureArea, "schedule.$dayKey.end", '16:00'))); ?>"></label>
                    </div>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
              </fieldset>
            </div>

            <footer class="unit-procedure-dialog-actions">
              <button type="button" data-procedure-edit-dialog-close>Cancelar</button>
              <button type="submit" class="is-primary">Guardar cambios</button>
            </footer>
          </form>
        </dialog>
        <script>
          (() => {
            const carousel = document.querySelector('[data-procedure-carousel]');
            const categoryButtons = [...document.querySelectorAll('[data-procedure-category]')];
            const catalogCards = [...document.querySelectorAll('[data-procedure-catalog]')];
            const createToolbarActions = [...document.querySelectorAll('[data-procedure-create-toolbar]')];
            const procedureSearch = document.querySelector('[data-procedure-search]');
            const exportButton = document.querySelector('[data-procedure-toolbar-export]');
            const createDialog = document.querySelector('[data-procedure-create-dialog]');
            const createForm = createDialog?.querySelector('[data-procedure-create-form]');
            const createType = createDialog?.querySelector('[data-procedure-create-type]');
            const createLabel = createDialog?.querySelector('[data-procedure-create-dialog-label]');
            const editDialog = document.querySelector('[data-procedure-edit-dialog]');
            const editForm = editDialog?.querySelector('[data-procedure-edit-form]');
            const editType = editDialog?.querySelector('[data-procedure-edit-type]');
            const editTitle = editDialog?.querySelector('[data-procedure-edit-title]');
            const procedureAreaData = <?php echo e(Illuminate\Support\Js::from($procedureAreaPayload)); ?>;
            const applyProcedureSearch = () => {
              const query = procedureSearch?.value.trim().toLocaleLowerCase('es') || '';
              catalogCards.forEach((card) => card.querySelectorAll('[data-procedure-row]').forEach((row) => {
                row.hidden = query !== '' && !row.innerText.toLocaleLowerCase('es').includes(query);
              }));
            };
            const setActiveCatalog = (key) => {
              categoryButtons.forEach((button) => {
                const active = button.dataset.procedureCategory === key;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
              });
              document.querySelectorAll('[data-unit-carousel-menu="procedure-areas"] [data-unit-carousel-filter]').forEach((button) => {
                const active = button.dataset.unitCarouselFilter === key;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
              });
              catalogCards.forEach((card) => { card.hidden = card.dataset.procedureCatalog !== key; });
              const actionKey = key === 'all' ? <?php echo json_encode(array_key_first($procedureCatalogs), 15, 512) ?> : key;
              createToolbarActions.forEach((action) => { action.hidden = action.dataset.procedureCreateToolbar !== actionKey; });
              applyProcedureSearch();
            };

            const syncScheduleRows = (dialog, toggleSelector, rowSelector) => {
              dialog?.querySelectorAll(toggleSelector).forEach((checkbox) => {
                checkbox.closest(rowSelector)?.classList.toggle('is-enabled', checkbox.checked);
              });
            };

            const cleanCreateUrl = () => {
              const url = new URL(window.location.href);
              if (!url.searchParams.has('create')) return;
              url.searchParams.delete('create');
              if (createType?.value) url.searchParams.set('catalog', createType.value);
              window.history.replaceState({}, '', url);
            };

            const cleanEditUrl = () => {
              const url = new URL(window.location.href);
              if (!url.searchParams.has('edit')) return;
              url.searchParams.delete('edit');
              if (editType?.value) url.searchParams.set('catalog', editType.value);
              window.history.replaceState({}, '', url);
            };

            const fillEditForm = (button) => {
              const area = procedureAreaData[button.dataset.procedureEditId];
              if (!area || !editForm) return false;

              editForm.reset();
              editForm.action = button.dataset.procedureEditAction;
              editForm.elements.namedItem('type').value = area.type || '';
              editForm.elements.namedItem('location').value = area.location || '';
              editForm.elements.namedItem('floor').value = area.floor || '';
              editForm.elements.namedItem('unit_number').value = area.unit_number || '';
              editForm.elements.namedItem('capacity').value = area.capacity || '';
              editForm.elements.namedItem('responsible').value = area.responsible === 'Sin responsable' ? '' : (area.responsible || '');
              editDialog.querySelector('[data-procedure-edit-error]')?.setAttribute('hidden', '');
              if (editTitle) editTitle.textContent = `Editar ${button.dataset.procedureEditLabel}`;

              editDialog.querySelectorAll('[data-procedure-edit-schedule-day]').forEach((row) => {
                const schedule = area.schedule?.[row.dataset.procedureDay] || {};
                const checkbox = row.querySelector('[data-procedure-edit-schedule-toggle]');
                const startsAt = row.querySelector('input[name$="[start]"]');
                const endsAt = row.querySelector('input[name$="[end]"]');
                checkbox.checked = schedule.enabled === true || schedule.enabled === 1 || schedule.enabled === '1';
                startsAt.value = schedule.start || '08:00';
                endsAt.value = schedule.end || '16:00';
              });

              syncScheduleRows(editDialog, '[data-procedure-edit-schedule-toggle]', '[data-procedure-edit-schedule-day]');
              return true;
            };

            categoryButtons.forEach((button) => button.addEventListener('click', () => setActiveCatalog(button.dataset.procedureCategory)));
            document.querySelector('[data-procedure-carousel-prev]')?.addEventListener('click', () => carousel?.scrollBy({ left: -260, behavior: 'smooth' }));
            document.querySelector('[data-procedure-carousel-next]')?.addEventListener('click', () => carousel?.scrollBy({ left: 260, behavior: 'smooth' }));
            procedureSearch?.addEventListener('input', applyProcedureSearch);

            document.querySelectorAll('[data-procedure-create]').forEach((button) => {
              button.addEventListener('click', (event) => {
                if (!createDialog?.showModal) return;
                event.preventDefault();
                createForm?.reset();
                const key = button.dataset.procedureCreateCategory;
                if (createType) createType.value = key;
                if (createLabel) createLabel.textContent = button.dataset.procedureCreateLabel;
                setActiveCatalog(key);
                window.history.replaceState({}, '', button.href);
                syncScheduleRows(createDialog, '[data-procedure-schedule-toggle]', '[data-procedure-schedule-day]');
                createDialog.showModal();
                window.requestAnimationFrame(() => createDialog.querySelector('[autofocus]')?.focus({ preventScroll: true }));
              });
            });

            document.querySelectorAll('[data-procedure-edit]').forEach((button) => {
              button.addEventListener('click', (event) => {
                if (!editDialog?.showModal || !fillEditForm(button)) return;
                event.preventDefault();
                setActiveCatalog(editType.value);
                window.history.replaceState({}, '', button.href);
                editDialog.showModal();
                window.requestAnimationFrame(() => editDialog.querySelector('[autofocus]')?.focus({ preventScroll: true }));
              });
            });

            createDialog?.querySelectorAll('[data-procedure-dialog-close]').forEach((button) => {
              button.addEventListener('click', () => createDialog.close());
            });
            createDialog?.addEventListener('click', (event) => {
              if (event.target === createDialog) createDialog.close();
            });
            createDialog?.addEventListener('close', cleanCreateUrl);
            createDialog?.querySelectorAll('[data-procedure-schedule-toggle]').forEach((checkbox) => {
              checkbox.addEventListener('change', () => syncScheduleRows(createDialog, '[data-procedure-schedule-toggle]', '[data-procedure-schedule-day]'));
            });
            createForm?.addEventListener('reset', () => window.requestAnimationFrame(() => syncScheduleRows(createDialog, '[data-procedure-schedule-toggle]', '[data-procedure-schedule-day]')));

            editDialog?.querySelectorAll('[data-procedure-edit-dialog-close]').forEach((button) => {
              button.addEventListener('click', () => editDialog.close());
            });
            editDialog?.addEventListener('click', (event) => {
              if (event.target === editDialog) editDialog.close();
            });
            editDialog?.addEventListener('close', cleanEditUrl);
            editDialog?.querySelectorAll('[data-procedure-edit-schedule-toggle]').forEach((checkbox) => {
              checkbox.addEventListener('change', () => syncScheduleRows(editDialog, '[data-procedure-edit-schedule-toggle]', '[data-procedure-edit-schedule-day]'));
            });

            if (createDialog?.hasAttribute('data-open-on-load') && !createDialog.open) {
              createDialog.showModal();
              window.requestAnimationFrame(() => createDialog.querySelector('[autofocus]')?.focus({ preventScroll: true }));
            }

            if (editDialog?.hasAttribute('data-open-on-load') && !editDialog.open) {
              syncScheduleRows(editDialog, '[data-procedure-edit-schedule-toggle]', '[data-procedure-edit-schedule-day]');
              editDialog.showModal();
              window.requestAnimationFrame(() => editDialog.querySelector('[autofocus]')?.focus({ preventScroll: true }));
            }

            exportButton?.addEventListener('click', () => {
              const card = catalogCards.find((item) => !item.hidden);
              if (!card) return;
              const headers = [...card.querySelectorAll('thead th')].map((cell) => cell.innerText.trim());
              const rows = [...card.querySelectorAll('[data-procedure-row]:not([hidden])')]
                .map((row) => [...row.cells].map((cell) => cell.innerText.replace(/\s+/g, ' ').trim()));
              const csv = [headers, ...rows].map((row) => row.map((value) => `"${value.replaceAll('"', '""')}"`).join(',')).join('\n');
              const link = document.createElement('a');
              link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
              link.download = `${card.dataset.procedureCatalog}.csv`;
              link.click();
              URL.revokeObjectURL(link.href);
            });
          })();
        </script>
      <?php endif; ?>

      <?php if($section === 'external-pharmacy'): ?>
        <div class="unit-catalog-screen" data-unit-catalog-screen>
          <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
            <div class="unit-service-panel-copy">
              <h2>Farmacia Externa</h2>
              <p>Medicamentos institucionales disponibles para consulta de la unidad</p>
              <div class="unit-service-panel-badges">
                <span class="unit-native-status">Activo</span>
                <span><strong data-pharmacy-visible data-unit-catalog-visible><?php echo e($externalPharmacyCatalog->count()); ?></strong> de <?php echo e($externalPharmacyCatalog->count()); ?> medicamentos visibles</span>
              </div>
            </div>
          </section>

          <div class="unit-catalog-toolbar" data-unit-catalog-controls>
            <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar farmacia externa">
              <button type="button" class="is-active" data-unit-catalog-status="all" role="tab" aria-selected="true">Todos</button>
              <button type="button" data-unit-catalog-status="active" role="tab" aria-selected="false">Activos <span><?php echo e($externalPharmacyCatalog->where('status', 'active')->count()); ?></span></button>
              <button type="button" data-unit-catalog-status="inactive" role="tab" aria-selected="false">Inactivos <span><?php echo e($externalPharmacyCatalog->where('status', 'inactive')->count()); ?></span></button>
            </div>
            <div class="unit-catalog-toolbar-actions">
              <label class="unit-catalog-search">
                <span class="sr-only">Buscar medicamento</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" placeholder="Buscar medicamento" data-unit-catalog-search>
              </label>
              <button type="button" class="unit-catalog-secondary-action" data-unit-catalog-export>Descargar catalogo</button>
            </div>
          </div>

          <section class="unit-native-table-card unit-catalog-content-card" data-unit-catalog-content>
            <div class="unit-native-table-scroll">
              <table class="unit-native-table unit-external-pharmacy-table" data-pharmacy-table data-unit-catalog-table>
                <thead><tr><th>CNIS</th><th>Insumo</th><th>Grupo</th><th>Descripcion</th><th>Cobertura</th><th>Estatus</th></tr></thead>
                <tbody>
                  <?php $__empty_1 = true; $__currentLoopData = $externalPharmacyCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr data-pharmacy-row data-unit-catalog-row data-status="<?php echo e($item->status); ?>">
                      <td><?php echo e($item->cnis ?? 'Sin CNIS'); ?></td>
                      <td><strong><?php echo e($item->name); ?></strong><small>Farmacia Externa</small></td>
                      <td><?php echo e(data_get($item->metadata, 'group', 'Sin grupo')); ?></td>
                      <td><?php echo e($item->presentation ?? $item->generic_name ?? 'Sin descripcion'); ?></td>
                      <td><?php echo e(data_get($item->metadata, 'coverage', 'Unidades moviles, Nucleos basicos, CESSA')); ?></td>
                      <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-native-status', 'is-warning' => $item->status === 'inactive']); ?>"><?php echo e($statusText($item->status)); ?></span><form method="post" action="<?php echo e(route('unit.external-pharmacy.status', $item)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('patch'); ?><input type="hidden" name="status" value="<?php echo e($item->status === 'active' ? 'inactive' : 'active'); ?>"><button type="submit"><?php echo e($item->status === 'active' ? 'Desactivar' : 'Activar'); ?></button></form></td>
                    </tr>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="unit-native-empty">Sin medicamentos institucionales para esta unidad.</td></tr>
                  <?php endif; ?>
                  <tr data-unit-catalog-empty hidden><td colspan="6" class="unit-native-empty">No se encontraron medicamentos.</td></tr>
                </tbody>
              </table>
            </div>
          </section>
        </div>
      <?php endif; ?>

      <?php if($section === 'medications'): ?>
        <?php echo $__env->make('unit._medications_catalog', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php endif; ?>

      <?php if(in_array($section, $catalogSections, true) && $section !== 'medications'): ?>
        <script>
          (() => {
            document.querySelectorAll('[data-unit-catalog-screen]').forEach((root) => {
              const rows = [...root.querySelectorAll('[data-unit-catalog-row]')];
              const statusButtons = [...root.querySelectorAll('[data-unit-catalog-status]')];
              const groupButtons = [...root.querySelectorAll('[data-unit-catalog-group]')];
              const search = root.querySelector('[data-unit-catalog-search]');
              const visible = root.querySelector('[data-unit-catalog-visible]');
              const empty = root.querySelector('[data-unit-catalog-empty]');
              let activeStatus = 'all';
              let activeGroup = 'all';

              const applyFilters = () => {
                const query = search?.value.trim().toLocaleLowerCase('es') || '';
                let visibleRows = 0;
                rows.forEach((row) => {
                  const matchesStatus = activeStatus === 'all' || row.dataset.status === activeStatus;
                  const matchesGroup = activeGroup === 'all' || row.dataset.group === activeGroup;
                  const matchesSearch = query === '' || row.innerText.toLocaleLowerCase('es').includes(query);
                  const show = matchesStatus && matchesGroup && matchesSearch;
                  row.hidden = !show;
                  if (show) visibleRows++;
                  if (!show && row.nextElementSibling?.matches('[data-doctor-authorization]')) {
                    row.nextElementSibling.hidden = true;
                  }
                });
                if (visible) visible.textContent = visibleRows;
                if (empty) empty.hidden = visibleRows !== 0 || rows.length === 0;
              };

              const activate = (buttons, selected) => buttons.forEach((button) => {
                const active = button === selected;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-selected', active ? 'true' : 'false');
              });

              statusButtons.forEach((button) => button.addEventListener('click', () => {
                activeStatus = button.dataset.unitCatalogStatus;
                activate(statusButtons, button);
                applyFilters();
              }));
              groupButtons.forEach((button) => button.addEventListener('click', () => {
                activeGroup = button.dataset.unitCatalogGroup;
                activate(groupButtons, button);
                applyFilters();
              }));
              search?.addEventListener('input', applyFilters);

              root.querySelectorAll('[data-unit-scroll-target]').forEach((button) => {
                button.addEventListener('click', () => {
                  const target = document.getElementById(button.dataset.unitScrollTarget);
                  target?.scrollIntoView({ behavior: 'smooth', block: 'start' });
                  window.setTimeout(() => target?.querySelector('input, select, button')?.focus({ preventScroll: true }), 350);
                });
              });

              root.querySelector('[data-unit-catalog-export]')?.addEventListener('click', () => {
                const table = root.querySelector('[data-unit-catalog-table]');
                if (!table) return;
                const headers = [...table.querySelectorAll('thead tr:first-child th')].map((cell) => cell.innerText.trim());
                const dataRows = rows.filter((row) => !row.hidden && row.cells)
                  .map((row) => [...row.cells].map((cell) => cell.innerText.replace(/\s+/g, ' ').trim()));
                const csv = [headers, ...dataRows]
                  .map((row) => row.map((value) => `"${value.replaceAll('"', '""')}"`).join(','))
                  .join('\n');
                const link = document.createElement('a');
                link.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8' }));
                link.download = `catalogo-${<?php echo json_encode($section, 15, 512) ?>}.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
              });

              applyFilters();
            });
          })();
        </script>
      <?php endif; ?>
    </section>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Unidad medica'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views/unit/dashboard.blade.php ENDPATH**/ ?>