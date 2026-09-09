@extends('layouts.app', ['title' => 'Unidad medica'])

@section('body_class', 'unit-native-body')

@php
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
    'services' => 'Servicios habilitados',
    'users' => 'Usuarios operativos',
    'patients' => 'Catalogo de pacientes',
    'doctors' => 'Catalogo de medicos',
    'specialties' => 'Catalogo de especialidades',
    'external-pharmacy' => 'Catalogo de farmacia externa',
    'procedure-areas' => 'Catalogo de areas de procedimiento',
    'medications' => 'Catalogo de medicamentos',
  ];
  $sectionEyebrow = strtoupper($sectionLabels[$section] ?? 'Servicios habilitados');
  $menu = [
    'profile' => ['user', 'Perfil'],
    'services' => ['minus', 'Servicios habilitados'],
    'users' => ['users', 'Usuarios operativos'],
    'patients' => ['patient', 'Catalogo de pacientes'],
    'doctors' => ['stethoscope', 'Catalogo de medicos'],
    'specialties' => ['star', 'Catalogo de especialidades'],
    'external-pharmacy' => ['rx', 'Catalogo de farmacia externa'],
    'procedure-areas' => ['areas', 'Catalogo de areas de procedimiento'],
    'medications' => ['pill', 'Catalogo de medicamentos'],
  ];
  $serviceChoices = ['Nutricion enteral', 'Nutricion parenteral', 'Quimioterapias', 'Hemodinamia', 'Analisis Clinicos', 'Histopatologia', 'Tomografia y resonancia', 'Hemodialisis', 'Mantenimiento', 'RPBI', 'Limpieza', 'Lavanderia', 'Dietas', 'Traslado terrestre y aereo'];
  $areaChoices = [
    'Enfermeria' => 'Seguimiento clinico y operativo del servicio.',
    'Farmacia intrahospitalaria' => 'Gestion de medicamentos, mezclas y soporte farmaceutico.',
    'Farmacia Externa' => 'Inventario, recetas, movimientos y almacenes de farmacia externa.',
    'Centro Oncologico' => 'Operacion y seguimiento de servicios oncologicos.',
    'Consulta Externa' => 'Atencion ambulatoria y coordinacion de servicios externos.',
  ];
@endphp

@section('content')
  <div class="unit-native-screen">
    <aside class="unit-native-sidebar" aria-label="Navegacion unidad">
      <nav class="unit-native-menu">
        @foreach ($menu as $key => [$icon, $label])
          <a class="{{ $section === $key ? 'is-active' : '' }}" href="{{ route('unit.dashboard', ['section' => $key]) }}">
            <span aria-hidden="true">
              @switch($icon)
                @case('user')
                  <svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="8" r="4"/></svg>
                  @break
                @case('minus')
                  <svg viewBox="0 0 24 24"><path d="M5 12h14"/></svg>
                  @break
                @case('users')
                  <svg viewBox="0 0 24 24"><path d="M16 21a6 6 0 0 0-12 0"/><circle cx="10" cy="8" r="4"/><path d="M22 21a5 5 0 0 0-4-4.9"/><path d="M17 4.3a4 4 0 0 1 0 7.4"/></svg>
                  @break
                @case('patient')
                  <svg viewBox="0 0 24 24"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="8" r="4"/><path d="M12 14v4M10 16h4"/></svg>
                  @break
                @case('stethoscope')
                  <svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg>
                  @break
                @case('star')
                  <svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 18.2l-5.6 3 1.1-6.2L3 10.6l6.2-.9Z"/></svg>
                  @break
                @case('rx')
                  <svg viewBox="0 0 24 24"><path d="M5 4h6a4 4 0 0 1 0 8H5V4Z"/><path d="M5 20V4M10 12l7 8M18 14l-6 6"/></svg>
                  @break
                @case('pill')
                  <svg viewBox="0 0 24 24"><path d="m10.5 20.5-7-7a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7Z"/><path d="m8.5 11.5 7-7a5 5 0 0 1 7 7l-7 7"/><path d="m7 17 10-10"/></svg>
                  @break
                @default
                  <svg viewBox="0 0 24 24"><path d="M4 20h16"/><path d="M7 20V8h4v12M13 20V4h4v16"/><path d="M9 12h.01M15 8h.01"/></svg>
              @endswitch
            </span>
            {{ $label }}
          </a>
        @endforeach
      </nav>
    </aside>

    <header class="unit-native-topbar">
      <strong>Unidad</strong>
      <span>{{ $unit->institution?->name ?? 'IMSS Bienestar Estado de Mexico' }}</span>
      <div class="unit-native-topbar-user">
        <button type="button" aria-label="Notificaciones">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        </button>
        <div>
          <strong>Usuario activo</strong>
          <small>{{ $unit->state ?? $unit->entity ?? 'Estado de Mexico' }}</small>
        </div>
        <form method="post" action="{{ route('logout') }}" class="unit-native-logout">
          @csrf
          <button type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
            Cerrar sesion
          </button>
        </form>
      </div>
    </header>

    <section class="unit-native-workspace">
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

      @if (! in_array($section, ['services', 'procedure-areas'], true))
        <header class="unit-native-header">
          <div>
            <p class="eyebrow">{{ $sectionEyebrow }}</p>
            <h1>{{ $unit->name }}</h1>
            <p>{{ $unitCode }} - {{ $unitLocation }}</p>
          </div>
          <div class="unit-native-selector">
            <label>
              Unidad
              <select>
                <option>{{ $unit->name }} - {{ $unitCode }}</option>
              </select>
            </label>
            <time>{{ now()->format('d M Y') }}</time>
          </div>
        </header>
      @endif

      @if ($section === 'profile')
        @php
          $profile = $unit->metadata['profile'] ?? [];
          $profileImage = $profile['image_path'] ?? null;
          $profileName = $profile['public_name'] ?? $unit->name;
          $profileInfo = $profile['general_info'] ?? '';
          $profileServices = $profile['services'] ?? $unit->contractedServices->pluck('service.name')->filter()->implode(', ');
          $profileNews = $profile['news'] ?? '';
          $profileSocial = $profile['social_text'] ?? '';
          $profileStationery = $profile['stationery_note'] ?? '';
          $initials = str($profileName)->explode(' ')->filter()->take(2)->map(fn ($word) => str($word)->substr(0, 1))->implode('');
        @endphp
        <div class="unit-profile-layout" data-unit-profile>
          <section class="unit-profile-card">
            <header><h2>Perfil de la unidad</h2><p>Imagen, servicios y novedades</p></header>
            <form method="post" action="{{ route('unit.profile.update') }}" enctype="multipart/form-data" class="unit-profile-form">
              @csrf
              @method('patch')
              <label>Imagen de perfil y papelerÃ­a</label>
              <div class="unit-profile-image-row">
                <div class="unit-profile-image-box" data-profile-image-preview>@if ($profileImage)<img src="{{ asset('storage/'.$profileImage) }}" alt="Imagen de {{ $profileName }}">@else<span>Sin imagen</span>@endif</div>
                <div><input type="file" name="profile_image" accept="image/png,image/jpeg,image/webp" data-profile-image-input><label class="unit-profile-remove"><input type="checkbox" name="remove_image" value="1"> Quitar imagen</label></div>
              </div>
              <label>Nombre pÃºblico de la unidad<input name="public_name" value="{{ old('public_name', $profileName) }}" data-profile-name required></label>
              <label>InformaciÃ³n general<textarea name="general_info" data-profile-info>{{ old('general_info', $profileInfo) }}</textarea></label>
              <label>Servicios de la unidad<textarea name="services" data-profile-services>{{ old('services', $profileServices) }}</textarea></label>
              <label>Novedades<textarea name="news" data-profile-news>{{ old('news', $profileNews) }}</textarea></label>
              <label>Texto para redes sociales<textarea name="social_text" data-profile-social>{{ old('social_text', $profileSocial) }}</textarea></label>
              <label>Nota para papelerÃ­a<textarea name="stationery_note" data-profile-stationery>{{ old('stationery_note', $profileStationery) }}</textarea></label>
              <button type="submit">Guardar perfil</button>
            </form>
          </section>
          <section class="unit-profile-card unit-profile-preview-card">
            <header><h2>Vista previa</h2><p>Redes sociales y papelerÃ­a</p></header>
            <div class="unit-profile-preview">
              <div class="unit-profile-cover"><strong data-profile-initials>{{ strtoupper($initials) }}</strong></div>
              <div class="unit-profile-preview-copy">
                <small>{{ $unitCode }} - {{ strtoupper($unitLocation) }}</small><h2 data-profile-preview-name>{{ $profileName }}</h2>
                <p data-profile-preview-info>{{ $profileInfo ?: 'InformaciÃ³n general pendiente.' }}</p>
                <strong>SERVICIOS</strong><p data-profile-preview-services>{{ $profileServices ?: 'Sin servicios registrados.' }}</p>
                <strong>NOVEDADES</strong><p data-profile-preview-news>{{ $profileNews ?: 'Sin novedades registradas.' }}</p>
                <article><strong>REDES SOCIALES</strong><p data-profile-preview-social>{{ $profileSocial ?: 'InformaciÃ³n general pendiente.' }}</p></article>
                <article><strong>PAPELERÃA</strong><p data-profile-preview-stationery>{{ $profileStationery ?: 'PapelerÃ­a institucional de la unidad.' }}</p></article>
              </div>
            </div>
          </section>
        </div>
        <script>
          (() => {
            const root = document.querySelector('[data-unit-profile]');
            const bind = (input, output, fallback) => input.addEventListener('input', () => output.textContent = input.value.trim() || fallback);
            const name = root.querySelector('[data-profile-name]');
            bind(name, root.querySelector('[data-profile-preview-name]'), @json($unit->name));
            bind(root.querySelector('[data-profile-info]'), root.querySelector('[data-profile-preview-info]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-services]'), root.querySelector('[data-profile-preview-services]'), 'Sin servicios registrados.');
            bind(root.querySelector('[data-profile-news]'), root.querySelector('[data-profile-preview-news]'), 'Sin novedades registradas.');
            bind(root.querySelector('[data-profile-social]'), root.querySelector('[data-profile-preview-social]'), 'InformaciÃ³n general pendiente.');
            bind(root.querySelector('[data-profile-stationery]'), root.querySelector('[data-profile-preview-stationery]'), 'PapelerÃ­a institucional de la unidad.');
            name.addEventListener('input', () => root.querySelector('[data-profile-initials]').textContent = name.value.trim().split(/\s+/).slice(0, 2).map(word => word[0] || '').join('').toUpperCase());
            root.querySelector('[data-profile-image-input]').addEventListener('change', (event) => { const file = event.target.files[0]; if (!file) return; root.querySelector('[data-profile-image-preview]').innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Vista previa">`; });
          })();
        </script>
      @elseif ($section === 'services')
        @php
          $institutionServiceOrder = [
              'consulta-externa',
              'hemodinamia',
              'laboratorio',
              'nutricion-parenteral',
              'quimioterapias',
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
              default => 'service',
            };
          };
          $consultationAppointments = $appointments->values();
          $consultationContract = $unitServiceContracts->first(fn ($contract) => $unitServiceKey($contract) === 'consulta-externa');
          $requestedServiceContractId = (string) request('service', '');
          $activeServiceContract = $unitServiceContracts->first(
              fn ($contract) => (string) $contract->id === $requestedServiceContractId
          ) ?? $unitServiceContracts->first();
          $activeServiceContractId = (string) ($activeServiceContract?->id ?? '');
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
                      'patient_id' => (string) $appointment->patient_id,
                      'patient' => $appointment->patient?->full_name ?: 'Paciente sin nombre',
                      'patient_curp' => $appointment->patient?->curp ?: 'Sin CURP',
                      'patient_platform' => $appointment->patient?->platform_number ?: 'Sin expediente',
                      'patient_sex' => $appointment->patient?->sex ?: 'Sin especificar',
                      'patient_age' => $appointment->patient?->birth_date?->age,
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
        @endphp

        <div class="unit-service-dashboard" data-unit-service-dashboard>
          @if ($unitServiceContracts->isNotEmpty())
            <section class="unit-service-carousel-card">
              <button type="button" class="unit-service-carousel-arrow" data-unit-service-carousel-prev aria-label="Servicio anterior">&lsaquo;</button>
              <div class="unit-service-carousel" data-unit-service-carousel aria-label="Servicios habilitados">
                @foreach ($unitServiceContracts as $contract)
                  @php
                    $serviceIcon = $unitServiceIcon($contract);
                    $serviceName = $contract->service?->name ?? 'Servicio sin nombre';
                  @endphp
                  <button type="button"
                          @class(['unit-service-carousel-button', 'is-active' => (string) $contract->id === $activeServiceContractId])
                          data-unit-service-tab="{{ $contract->id }}"
                          data-unit-service-key="{{ $unitServiceKey($contract) }}">
                    <span class="unit-service-carousel-icon is-{{ $serviceIcon }}" aria-hidden="true">
                      @switch($serviceIcon)
                        @case('consultation')
                          <svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg>
                          @break
                        @case('heart')
                          <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/><path d="M3 12h4l2-4 4 8 2-4h6"/></svg>
                          @break
                        @case('microscope')
                          <svg viewBox="0 0 24 24"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 0 0 7-7h-4a3 3 0 0 1-3 3"/><path d="m9 14 6-6"/><path d="m7 12 4 4"/><path d="M10 5 7 8l6 6 3-3Z"/></svg>
                          @break
                        @case('nutrition')
                          <svg viewBox="0 0 24 24"><path d="M8 2h8"/><path d="M9 2v6l-4 9a4 4 0 0 0 3.7 5h6.6A4 4 0 0 0 19 17l-4-9V2"/><path d="M8 14h8"/></svg>
                          @break
                        @case('infusion')
                          <svg viewBox="0 0 24 24"><path d="M9 2h6v9a3 3 0 0 1-6 0V2Z"/><path d="M12 14v8"/><path d="M8 22h8"/><path d="M9 6h6"/></svg>
                          @break
                        @case('mixtures')
                          <svg viewBox="0 0 24 24"><path d="M9 3h6"/><path d="M10 3v6l-4.5 8.5A3 3 0 0 0 8.2 22h7.6a3 3 0 0 0 2.7-4.5L14 9V3"/><path d="M8 16h8"/></svg>
                          @break
                        @case('maintenance')
                          <svg viewBox="0 0 24 24"><path d="m14.7 6.3 3-3a4 4 0 0 1-5 5l-7 7a2 2 0 0 0 2.8 2.8l7-7a4 4 0 0 1 5-5l-3 3"/></svg>
                          @break
                        @case('osteosynthesis')
                          <svg viewBox="0 0 24 24"><path d="M8.5 8.5 15.5 15.5"/><path d="M6.5 11.5a3 3 0 1 1 3-5l8 8a3 3 0 1 1-5 3Z"/></svg>
                          @break
                        @default
                          <svg viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
                      @endswitch
                    </span>
                    <strong>{{ $serviceName }}</strong>
                  </button>
                @endforeach
              </div>
              <button type="button" class="unit-service-carousel-arrow" data-unit-service-carousel-next aria-label="Servicio siguiente">&rsaquo;</button>
            </section>

            @if ($consultationContract)
              <section class="unit-consultation-submenu-card" data-unit-consultation-submenu @if (! $activeServiceIsConsultation) hidden @endif>
                <button type="button" class="unit-consultation-submenu-arrow" data-unit-consultation-prev aria-label="Opcion anterior">&lsaquo;</button>
                <div class="unit-consultation-submenu" data-unit-consultation-carousel aria-label="Secciones de consulta externa">
                  @foreach ($consultationMenu as $consultationKey => [$consultationLabel, $consultationIcon])
                    <button type="button"
                            @class(['unit-consultation-submenu-button', 'is-active' => $loop->first])
                            data-unit-consultation-tab="{{ $consultationKey }}"
                            aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                      <span aria-hidden="true">
                        @switch($consultationIcon)
                          @case('home')
                            <svg viewBox="0 0 24 24"><path d="m3 11 9-8 9 8"/><path d="M5 10v11h14V10"/><path d="M9 21v-7h6v7"/></svg>
                            @break
                          @case('calendar')
                            <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                            @break
                          @case('room')
                            <svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/><path d="M13 12h.01"/></svg>
                            @break
                          @case('doctor')
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5 21a7 7 0 0 1 14 0"/><path d="M12 14v5M9.5 16.5h5"/></svg>
                            @break
                          @case('specialty')
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14H2.8v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3V2.8h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg>
                            @break
                          @case('patients')
                            <svg viewBox="0 0 24 24"><path d="M16 21a6 6 0 0 0-12 0"/><circle cx="10" cy="8" r="4"/><path d="M22 21a5 5 0 0 0-4-4.9M17 4.3a4 4 0 0 1 0 7.4"/></svg>
                            @break
                          @default
                            <svg viewBox="0 0 24 24"><path d="M6 3h12v18H6z"/><path d="M9 3V1h6v2M9 8h6M9 12h6M9 16h4"/></svg>
                        @endswitch
                      </span>
                      <strong>{{ $consultationLabel }}</strong>
                    </button>
                  @endforeach
                </div>
                <button type="button" class="unit-consultation-submenu-arrow" data-unit-consultation-next aria-label="Opcion siguiente">&rsaquo;</button>
              </section>
            @endif

            <div class="unit-service-panels">
              @foreach ($unitServiceContracts as $contract)
                @php
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
                  $nutritionRequestCounts = collect(['pending', 'preparing', 'route', 'delivered'])
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
                @endphp
                <section @class([
                         'unit-service-panel',
                         'is-consultation-service' => $isConsultationService,
                         'is-mixture-request-service' => $isNutritionService || $isChemotherapyService,
                         ])
                         data-unit-service-panel="{{ $contract->id }}"
                         data-unit-service-key="{{ $serviceKey }}"
                         @if ($isConsultationService) data-unit-consultation-panel @endif
                         @if ((string) $contract->id !== $activeServiceContractId) hidden @endif>
                  <header class="unit-service-panel-header">
                    <div @class(['unit-consultation-calendar-title' => $isConsultationService])>
                      @if ($isConsultationService)
                        <span class="unit-consultation-calendar-title-icon" aria-hidden="true">
                          <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/><path d="M9 13h2M13 13h2M9 17h2M13 17h2"/></svg>
                        </span>
                      @endif
                      <div>
                        <h2>{{ $serviceName }}</h2>
                        <p>{{ $isConsultationService ? 'Agenda de consultas' : (($service?->category ?? 'Sin categoria').' · '.($service?->specialty ?? 'Servicio general')) }}</p>
                      <div class="unit-service-panel-badges">
                        <span class="unit-native-status">{{ $statusText($contract->status) }}</span>
                        <span>1 hospital habilitado</span>
                      </div>
                      </div>
                    </div>
                    <div class="unit-service-panel-actions">
                      @if ($isConsultationService)
                        <button type="button" class="unit-consultation-new-appointment" data-unit-calendar-new>
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                          Agendar nueva cita
                        </button>
                      @endif
                      <button type="button"
                              class="unit-service-operation-toggle"
                              data-unit-service-operation-toggle
                              aria-expanded="false">Ver Operacion</button>
                      <a href="{{ route('unit.services.report', $contract) }}">Descargar reporte</a>
                      <button type="button" data-open-service-catalog="{{ $contract->id }}">Informacion del contrato</button>
                    </div>
                  </header>

                  <div data-unit-service-overview @if ($isConsultationService) data-unit-consultation-view="home" @endif>
                    @if ($isConsultationService)
                      <section class="unit-consultation-calendar"
                               data-unit-consultation-calendar
                               data-default-date="{{ $consultationCalendarDefaultDate }}">
                        <div class="unit-consultation-calendar-toolbar">
                          <div class="unit-consultation-calendar-modes" role="tablist" aria-label="Vista del calendario">
                            @foreach (['day' => 'Dia', 'week' => 'Semana', 'month' => 'Mes', 'list' => 'Lista'] as $calendarMode => $calendarModeLabel)
                              <button type="button"
                                      @class(['is-active' => $loop->first])
                                      data-unit-calendar-mode="{{ $calendarMode }}"
                                      role="tab"
                                      aria-selected="{{ $loop->first ? 'true' : 'false' }}">{{ $calendarModeLabel }}</button>
                            @endforeach
                          </div>

                          <div class="unit-consultation-calendar-date-nav">
                            <button type="button" data-unit-calendar-previous aria-label="Fecha anterior" title="Fecha anterior">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            </button>
                            <label>
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                              <input type="date" value="{{ $consultationCalendarDefaultDate }}" data-unit-calendar-date aria-label="Fecha del calendario">
                            </label>
                            <button type="button" data-unit-calendar-next aria-label="Fecha siguiente" title="Fecha siguiente">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                            </button>
                          </div>

                          <label class="unit-consultation-calendar-filter">
                            <span>Especialidad</span>
                            <select data-unit-calendar-specialty>
                              <option value="">Todas las especialidades</option>
                              @foreach ($consultationSpecialties as $consultationSpecialty)
                                <option value="{{ $consultationSpecialty }}">{{ $consultationSpecialty }}</option>
                              @endforeach
                            </select>
                          </label>
                          <label class="unit-consultation-calendar-filter">
                            <span>Consultorio</span>
                            <select data-unit-calendar-room>
                              <option value="">Todos los consultorios</option>
                              @foreach ($consultationCalendarRooms as $calendarRoom)
                                <option value="{{ $calendarRoom['id'] }}">{{ $calendarRoom['name'] }}</option>
                              @endforeach
                            </select>
                          </label>
                        </div>

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
                                @foreach (['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'] as $weekday)
                                  <span>{{ $weekday }}</span>
                                @endforeach
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
                              <a href="{{ route('unit.services.report', $contract) }}">
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

                        <dialog class="unit-consultation-flow-dialog unit-consultation-appointment-dialog" data-unit-calendar-dialog aria-labelledby="unit-appointment-management-title">
                          <div class="unit-consultation-flow-shell">
                            <header class="unit-consultation-flow-header">
                              <span class="unit-consultation-flow-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                              </span>
                              <div>
                                <h3 id="unit-appointment-management-title">Gesti&oacute;n de cita</h3>
                                <p>Consulta Externa</p>
                              </div>
                              <button type="button" class="unit-consultation-flow-close" data-unit-calendar-dialog-close aria-label="Cerrar" title="Cerrar">&times;</button>
                            </header>

                            <dl class="unit-consultation-detail-list">
                              <div><dt>Folio</dt><dd data-unit-calendar-dialog-field="folio"></dd></div>
                              <div><dt>Paciente</dt><dd><strong data-unit-calendar-dialog-field="patient"></strong><small data-unit-calendar-dialog-field="patient_meta"></small></dd></div>
                              <div><dt>Especialidad</dt><dd data-unit-calendar-dialog-field="specialty"></dd></div>
                              <div><dt>M&eacute;dico</dt><dd><strong data-unit-calendar-dialog-field="doctor"></strong><small data-unit-calendar-dialog-field="doctor_license"></small></dd></div>
                              <div><dt>Consultorio</dt><dd data-unit-calendar-dialog-field="room"></dd></div>
                              <div><dt>Fecha</dt><dd data-unit-calendar-dialog-field="date"></dd></div>
                              <div><dt>Hora</dt><dd data-unit-calendar-dialog-field="time"></dd></div>
                              <div><dt>Estatus</dt><dd><span class="unit-consultation-status-chip" data-unit-calendar-dialog-field="status"></span></dd></div>
                              <div class="is-wide"><dt>Motivo de consulta</dt><dd data-unit-calendar-dialog-field="reason"></dd></div>
                            </dl>

                            <footer class="unit-consultation-flow-actions is-three">
                              <button type="button" class="is-secondary" data-unit-calendar-edit>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 4-1 10-10-3-3L5 16l-1 4Z"/><path d="m13 8 3 3"/></svg>
                                Editar cita
                              </button>
                              <button type="button" class="is-soft" data-unit-calendar-reschedule>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/></svg>
                                Reprogramar
                              </button>
                              <button type="button" class="is-danger-outline" data-unit-calendar-cancel>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13"/></svg>
                                Cancelar cita
                              </button>
                            </footer>
                          </div>
                        </dialog>

                        <dialog class="unit-consultation-flow-dialog" data-unit-calendar-edit-dialog aria-labelledby="unit-appointment-edit-title">
                          <form method="post"
                                class="unit-consultation-flow-shell"
                                data-unit-calendar-edit-form
                                data-action-template="{{ route('unit.appointments.update', ['appointment' => '__appointment__']) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="unit" value="{{ $unit->id }}">
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
                                  @foreach ($consultationCalendarDoctors as $calendarDoctor)
                                    <option value="{{ $calendarDoctor['id'] }}">{{ $calendarDoctor['name'] }}</option>
                                  @endforeach
                                </select>
                              </label>
                              <label>
                                <span>N&uacute;mero de consultorio <b>*</b></span>
                                <select name="procedure_area_id" required>
                                  @foreach ($consultationCalendarRooms->where('schedulable', true) as $calendarRoom)
                                    <option value="{{ $calendarRoom['id'] }}">{{ $calendarRoom['name'] }}</option>
                                  @endforeach
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
                                data-action-template="{{ route('unit.appointments.status', ['appointment' => '__appointment__']) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="unit" value="{{ $unit->id }}">
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

                        <dialog class="unit-consultation-flow-dialog unit-consultation-new-dialog" data-unit-calendar-new-dialog aria-labelledby="unit-new-appointment-title">
                          <form method="post" action="{{ route('unit.appointments.store') }}" class="unit-consultation-flow-shell" data-unit-calendar-new-form>
                            @csrf
                            <input type="hidden" name="unit" value="{{ $unit->id }}">
                            <input type="hidden" name="patient_id" data-unit-new-field="patient_id">
                            <input type="hidden" name="appointment_time" data-unit-new-field="appointment_time">
                            <input type="hidden" name="modality" value="Presencial">
                            <input type="hidden" name="duration" value="30">

                            <header class="unit-consultation-flow-header">
                              <span class="unit-consultation-flow-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14M12 12v6M9 15h6"/></svg>
                              </span>
                              <div>
                                <h3 id="unit-new-appointment-title" data-unit-new-title>Agendar nueva cita</h3>
                                <p data-unit-new-subtitle>Selecciona al paciente y los datos de la cita.</p>
                              </div>
                              <button type="button" class="unit-consultation-flow-close" data-unit-calendar-new-close aria-label="Cerrar" title="Cerrar">&times;</button>
                            </header>

                            <ol class="unit-consultation-wizard-progress" aria-label="Progreso de la cita">
                              <li class="is-active" data-unit-new-progress="1"><b>1</b><span>Paciente y datos</span></li>
                              <li data-unit-new-progress="2"><b>2</b><span>Horario</span></li>
                              <li data-unit-new-progress="3"><b>3</b><span>Confirmaci&oacute;n</span></li>
                            </ol>

                            <p class="unit-consultation-flow-error" data-unit-new-error hidden></p>

                            <section class="unit-consultation-wizard-step" data-unit-new-step="1">
                              <label class="unit-consultation-patient-search">
                                <span>Paciente <b>*</b></span>
                                <span class="unit-consultation-search-control">
                                  <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg>
                                  <input type="search" data-unit-new-patient-search autocomplete="off" placeholder="Buscar por nombre, CURP o n&uacute;mero de expediente...">
                                </span>
                                <span class="unit-consultation-patient-results" data-unit-new-patient-results hidden></span>
                              </label>
                              <div class="unit-consultation-selected-patient" data-unit-new-selected-patient hidden>
                                <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                                <div><strong data-unit-new-selected="patient"></strong><small data-unit-new-selected="patient_meta"></small></div>
                                <button type="button" data-unit-new-patient-clear aria-label="Cambiar paciente" title="Cambiar paciente">&times;</button>
                              </div>

                              <div class="unit-consultation-form-grid">
                                <label>
                                  <span>Especialidad <b>*</b></span>
                                  <select name="specialty" data-unit-new-field="specialty" required>
                                    <option value="">Selecciona una especialidad</option>
                                    @foreach ($consultationSpecialties as $consultationSpecialty)
                                      <option value="{{ $consultationSpecialty }}">{{ $consultationSpecialty }}</option>
                                    @endforeach
                                  </select>
                                </label>
                                <label>
                                  <span>M&eacute;dico <b>*</b></span>
                                  <select name="doctor_id" data-unit-new-field="doctor_id" required disabled>
                                    <option value="">Selecciona un m&eacute;dico</option>
                                  </select>
                                </label>
                                <label>
                                  <span>N&uacute;mero de consultorio <b>*</b></span>
                                  <select name="procedure_area_id" data-unit-new-field="procedure_area_id" required>
                                    <option value="">Selecciona un consultorio</option>
                                    @foreach ($consultationCalendarRooms->where('schedulable', true) as $calendarRoom)
                                      <option value="{{ $calendarRoom['id'] }}">{{ $calendarRoom['name'] }}</option>
                                    @endforeach
                                  </select>
                                </label>
                                <label><span>Fecha <b>*</b></span><input type="date" name="appointment_date" data-unit-new-field="appointment_date" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required></label>
                                <label class="is-wide">
                                  <span>Motivo de consulta <b>*</b></span>
                                  <textarea name="reason" data-unit-new-field="reason" maxlength="500" rows="4" required placeholder="Describe brevemente el motivo de la consulta..."></textarea>
                                </label>
                              </div>

                              <footer class="unit-consultation-flow-actions">
                                <button type="button" class="is-secondary" data-unit-calendar-new-close>Cancelar</button>
                                <button type="button" class="is-primary" data-unit-new-next="2">Continuar <span aria-hidden="true">&rsaquo;</span></button>
                              </footer>
                            </section>

                            <section class="unit-consultation-wizard-step" data-unit-new-step="2" hidden>
                              <article class="unit-consultation-wizard-patient-card">
                                <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span>
                                <div><small>Paciente seleccionado</small><strong data-unit-new-summary="patient"></strong><p data-unit-new-summary="patient_meta"></p></div>
                              </article>
                              <div class="unit-consultation-wizard-service-card">
                                <div><small>Especialidad</small><strong data-unit-new-summary="specialty"></strong></div>
                                <div><small>Consultorio</small><strong data-unit-new-summary="room"></strong></div>
                              </div>
                              <div class="unit-consultation-available-slots">
                                <h4>Horarios disponibles</h4>
                                <p>Selecciona un horario para la cita.</p>
                                <div data-unit-new-slots></div>
                              </div>
                              <label class="unit-consultation-wizard-notes">
                                <span>Notas para la cita (opcional)</span>
                                <textarea name="notes" maxlength="500" rows="4" placeholder="Motivo ampliado, indicaciones especiales, etc."></textarea>
                              </label>
                              <footer class="unit-consultation-flow-actions">
                                <button type="button" class="is-secondary" data-unit-new-back="1">&lsaquo; Atr&aacute;s</button>
                                <button type="button" class="is-primary" data-unit-new-next="3">Continuar <span aria-hidden="true">&rsaquo;</span></button>
                              </footer>
                            </section>

                            <section class="unit-consultation-wizard-step" data-unit-new-step="3" hidden>
                              <div class="unit-consultation-confirm-heading">
                                <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 4h14v17H5z"/><path d="M8 2v4M16 2v4M5 9h14"/><path d="m8 15 3 3 5-6"/></svg></span>
                                <div><h4>Resumen de la cita</h4><p>Revisa que la informaci&oacute;n sea correcta antes de confirmar.</p></div>
                              </div>
                              <dl class="unit-consultation-confirm-summary">
                                <div><dt>Paciente</dt><dd data-unit-new-confirm="patient"></dd></div>
                                <div><dt>Especialidad</dt><dd data-unit-new-confirm="specialty"></dd></div>
                                <div><dt>M&eacute;dico</dt><dd data-unit-new-confirm="doctor"></dd></div>
                                <div><dt>Consultorio</dt><dd data-unit-new-confirm="room"></dd></div>
                                <div><dt>Fecha</dt><dd data-unit-new-confirm="date"></dd></div>
                                <div><dt>Hora</dt><dd data-unit-new-confirm="time"></dd></div>
                                <div><dt>Motivo de consulta</dt><dd data-unit-new-confirm="reason"></dd></div>
                              </dl>
                              <footer class="unit-consultation-flow-actions">
                                <button type="button" class="is-secondary" data-unit-new-back="2">Atr&aacute;s</button>
                                <button type="submit" class="is-primary">Confirmar cita</button>
                              </footer>
                            </section>
                          </form>
                        </dialog>
                      </section>
                    @elseif ($isNutritionService || $isChemotherapyService)
                      <section class="unit-nutrition-request-board"
                               data-unit-nutrition-requests
                               data-request-service="{{ $isChemotherapyService ? 'chemotherapy' : 'nutrition' }}">
                        <div class="unit-nutrition-request-toolbar">
                          <div class="unit-nutrition-request-tabs"
                               role="tablist"
                               aria-label="Filtrar solicitudes de {{ $isChemotherapyService ? 'quimioterapia' : 'nutricion parenteral' }}">
                            <button type="button" class="is-active" data-unit-nutrition-filter="all" role="tab" aria-selected="true">Todos</button>
                            <button type="button" data-unit-nutrition-filter="pending" role="tab" aria-selected="false">
                              Pendientes <span>{{ $nutritionRequestCounts->get('pending', 0) }}</span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="preparing" role="tab" aria-selected="false">
                              En preparaci&oacute;n <span>{{ $nutritionRequestCounts->get('preparing', 0) }}</span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="route" role="tab" aria-selected="false">
                              En ruta <span>{{ $nutritionRequestCounts->get('route', 0) }}</span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="delivered" role="tab" aria-selected="false">
                              Entregadas <span>{{ $nutritionRequestCounts->get('delivered', 0) }}</span>
                            </button>
                            <button type="button" data-unit-nutrition-filter="history" role="tab" aria-selected="false">Historial</button>
                          </div>
                          @if ($isNutritionService)
                            <button type="button" class="unit-nutrition-new-request" data-unit-nutrition-open>
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                              Solicitud de mezcla
                            </button>
                          @else
                            <a class="unit-nutrition-new-request"
                               data-unit-chemotherapy-request
                               href="{{ route('operational.dashboard', [
                                 'area' => 'oncology',
                                 'section' => 'calendar',
                                 'oncology_track' => 'infusions',
                                 'unit' => $unit->id,
                                 'new_infusion' => 1,
                               ]) }}">
                              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                              Solicitud de mezcla
                            </a>
                          @endif
                        </div>

                        <div class="unit-nutrition-request-table-wrap" data-drsam-table-filter-skip>
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
                              @forelse ($serviceProviderRequests as $nutritionRequest)
                                @php
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
                                @endphp
                                <tr data-unit-nutrition-row data-category="{{ $nutritionCategory }}">
                                  <td><span class="unit-consultation-chip is-type-{{ $nutritionTypeClass }}">{{ $nutritionTypeLabel }}</span></td>
                                  <td><strong>{{ $nutritionMixId }}</strong></td>
                                  <td><strong>{{ $nutritionRequestNumber }}</strong></td>
                                  <td><strong>{{ $nutritionHospital }}</strong><small>{{ $unit->clues ?? $unit->code ?? 'Sin CLUES' }}</small></td>
                                  <td><strong>{{ $nutritionRequest->patient?->full_name ?? 'Sin paciente' }}</strong></td>
                                  <td>{{ $nutritionRequest->requested_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                  <td>{{ $nutritionRequest->required_at?->format('d/m/Y H:i') ?? '-' }}</td>
                                  <td>
                                    <button type="button"
                                            class="unit-consultation-chip is-status-{{ $nutritionStatusClass }}"
                                            data-unit-nutrition-status="{{ $nutritionCategory }}">{{ $nutritionStatusLabel }}</button>
                                  </td>
                                  <td>{{ $nutritionLot }}</td>
                                  <td>
                                    <a class="unit-nutrition-request-view" href="{{ $nutritionRoute }}" aria-label="Ver solicitud {{ $nutritionRequestNumber }}">
                                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                      Ver
                                    </a>
                                  </td>
                                  <td><span class="unit-nutrition-approval is-{{ $nutritionApprovalClass }}"><i aria-hidden="true"></i>{{ $nutritionApprovalLabel }}</span></td>
                                </tr>
                              @empty
                                <tr data-unit-nutrition-empty>
                                  <td colspan="11">{{ $isChemotherapyService ? 'No se encontraron solicitudes de quimioterapia.' : 'No se encontraron solicitudes.' }}</td>
                                </tr>
                              @endforelse
                              @if ($serviceProviderRequests->isNotEmpty())
                                <tr data-unit-nutrition-no-results hidden><td colspan="11">No se encontraron solicitudes para este filtro.</td></tr>
                              @endif
                            </tbody>
                          </table>
                        </div>

                        @if ($isNutritionService)
                          <dialog class="unit-consultation-flow-dialog unit-nutrition-request-dialog"
                                  data-unit-nutrition-dialog
                                  aria-labelledby="unit-nutrition-request-title-{{ $contract->id }}"
                                  @if (old('request_context') === 'nutrition_mixture' && (string) old('service_contract_id') === (string) $contract->id) data-open-on-load @endif>
                            <form method="post" action="{{ route('unit.nutrition-requests.store') }}" class="unit-consultation-flow-shell">
                            @csrf
                            <input type="hidden" name="unit" value="{{ $unit->id }}">
                            <input type="hidden" name="service_contract_id" value="{{ $contract->id }}">
                            <input type="hidden" name="request_context" value="nutrition_mixture">

                            <header class="unit-consultation-flow-header">
                              <span class="unit-consultation-flow-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M8 2h8M9 2v6l-4 9a4 4 0 0 0 3.7 5h6.6A4 4 0 0 0 19 17l-4-9V2"/><path d="M8 14h8"/></svg>
                              </span>
                              <div>
                                <h3 id="unit-nutrition-request-title-{{ $contract->id }}">Solicitud de mezcla</h3>
                                <p>Nutrici&oacute;n Parenteral</p>
                              </div>
                              <button type="button" class="unit-consultation-flow-close" data-unit-nutrition-close aria-label="Cerrar" title="Cerrar">&times;</button>
                            </header>

                            <div class="unit-consultation-form-grid unit-nutrition-request-form-grid">
                              <label>
                                <span>Paciente <b>*</b></span>
                                <select name="patient_id" required>
                                  <option value="">Selecciona un paciente</option>
                                  @foreach ($consultationPatientCatalog as $nutritionPatient)
                                    <option value="{{ $nutritionPatient->id }}" @selected((string) old('patient_id') === (string) $nutritionPatient->id)>
                                      {{ $nutritionPatient->full_name }}{{ $nutritionPatient->platform_number ? ' - '.$nutritionPatient->platform_number : '' }}
                                    </option>
                                  @endforeach
                                </select>
                              </label>
                              <label>
                                <span>M&eacute;dico responsable <b>*</b></span>
                                <select name="doctor_id" required>
                                  <option value="">Selecciona un m&eacute;dico</option>
                                  @foreach ($unit->doctors->where('status', 'active') as $nutritionDoctor)
                                    <option value="{{ $nutritionDoctor->id }}" @selected((string) old('doctor_id') === (string) $nutritionDoctor->id)>
                                      {{ $nutritionDoctor->full_name }}{{ $nutritionDoctor->specialty ? ' - '.$nutritionDoctor->specialty : '' }}
                                    </option>
                                  @endforeach
                                </select>
                              </label>
                              <label><span>Servicio cl&iacute;nico <b>*</b></span><input name="clinical_service" value="{{ old('clinical_service', 'Nutricion clinica') }}" maxlength="180" required></label>
                              <label>
                                <span>Prioridad <b>*</b></span>
                                <select name="priority" required>
                                  <option value="routine" @selected(old('priority', 'routine') === 'routine')>Rutina</option>
                                  <option value="urgent" @selected(old('priority') === 'urgent')>Urgente</option>
                                </select>
                              </label>
                              <label><span>Fecha y hora de entrega <b>*</b></span><input type="datetime-local" name="delivery_at" min="{{ now()->format('Y-m-d\TH:i') }}" value="{{ old('delivery_at', now()->addDay()->format('Y-m-d\TH:i')) }}" required></label>
                              <label>
                                <span>V&iacute;a de administraci&oacute;n <b>*</b></span>
                                <select name="route" required>
                                  <option value="Central" @selected(old('route', 'Central') === 'Central')>Central</option>
                                  <option value="Periferica" @selected(old('route') === 'Periferica')>Perif&eacute;rica</option>
                                </select>
                              </label>
                              <label>
                                <span>Tipo de NPT <b>*</b></span>
                                <select name="npt_type" required>
                                  <option value="Individualizada" @selected(old('npt_type', 'Individualizada') === 'Individualizada')>Individualizada</option>
                                  <option value="Tricamara" @selected(old('npt_type') === 'Tricamara')>Tric&aacute;mara</option>
                                  <option value="Pediatrica" @selected(old('npt_type') === 'Pediatrica')>Pedi&aacute;trica</option>
                                </select>
                              </label>
                              <label><span>Volumen total (ml) <b>*</b></span><input type="number" name="total_volume" min="0.01" max="100000" step="0.01" value="{{ old('total_volume') }}" required></label>
                              <label><span>Tiempo de infusi&oacute;n (h) <b>*</b></span><input type="number" name="infusion_hours" min="0.01" max="168" step="0.01" value="{{ old('infusion_hours', 24) }}" required></label>
                              <label class="is-wide"><span>Diagn&oacute;stico <b>*</b></span><textarea name="diagnosis" maxlength="2000" rows="3" required placeholder="Describe el diagn&oacute;stico cl&iacute;nico...">{{ old('diagnosis') }}</textarea></label>
                              <label class="is-wide"><span>Componentes de la mezcla</span><textarea name="components" maxlength="3000" rows="3" placeholder="Detalla amino&aacute;cidos, l&iacute;pidos, glucosa, electrolitos y otros componentes...">{{ old('components') }}</textarea></label>
                              <label class="is-wide"><span>Indicaciones y observaciones</span><textarea name="notes" maxlength="3000" rows="3" placeholder="Agrega indicaciones especiales para preparaci&oacute;n o entrega...">{{ old('notes') }}</textarea></label>
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
                        @endif
                      </section>
                    @else
                      <div class="unit-native-table-scroll">
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
                              <td><strong>{{ $unit->name }}</strong><small>{{ $unit->type ?? $unit->typology ?? 'Hospital General' }}</small></td>
                              <td>{{ $unit->clues ?? $unit->code ?? 'Sin CLUES' }}</td>
                              <td>{{ $unitLocation }}</td>
                              <td>{{ $contract->starts_at?->format('d/m/Y') ?? 'Sin inicio' }} - {{ $contract->ends_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
                              <td>{{ $contract->contract_number ?? 'Sin contrato' }}</td>
                              <td><span class="unit-native-status">{{ $statusText($contract->status) }}</span></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    @endif
                  </div>

                  @if ($isConsultationService)
                    <div class="unit-consultation-subview unit-consultation-room-workspace"
                         data-unit-consultation-view="rooms"
                         data-unit-consultation-rooms
                         data-default-date="{{ $consultationCalendarDefaultDate }}"
                         hidden>
                      <aside class="unit-consultation-room-directory">
                        <header>
                          <h3>Consultorios activos</h3>
                          <a href="{{ route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'create' => 'consulting']) }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            Nuevo consultorio
                          </a>
                        </header>
                        <label class="unit-consultation-room-search">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                          <input type="search" data-unit-room-search aria-label="Buscar consultorio" placeholder="Buscar por numero o especialidad..." autocomplete="off">
                        </label>
                        <div class="unit-consultation-room-list" data-unit-room-list role="listbox" aria-label="Consultorios registrados">
                          @forelse ($registeredConsultationRooms as $room)
                            <button type="button"
                                    @class(['unit-consultation-room-list-item', 'is-active' => $loop->first])
                                    data-unit-room-id="{{ $room['id'] }}"
                                    data-search="{{ $room['number'].' '.$room['name'].' '.$room['specialty'].' '.$room['floor'] }}"
                                    role="option"
                                    aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                              <span class="unit-consultation-room-list-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/><path d="M13 12h.01"/></svg>
                              </span>
                              <span class="unit-consultation-room-list-copy">
                                <strong>{{ $room['name'] }}</strong>
                                <small>{{ $room['specialty'] }} - Piso {{ $room['floor'] }}</small>
                              </span>
                              <span @class(['unit-consultation-room-state', 'is-inactive' => $room['status'] !== 'active'])>
                                <i aria-hidden="true"></i>{{ $room['status_label'] }}
                              </span>
                              <span class="unit-consultation-room-list-arrow" aria-hidden="true">&rsaquo;</span>
                            </button>
                          @empty
                            <p class="unit-consultation-room-list-empty">No hay consultorios registrados.</p>
                          @endforelse
                          <p class="unit-consultation-room-list-empty" data-unit-room-search-empty hidden>No hay coincidencias.</p>
                        </div>
                      </aside>

                      <section class="unit-consultation-room-detail" data-unit-room-detail @if ($registeredConsultationRooms->isEmpty()) hidden @endif>
                        <header class="unit-consultation-room-detail-header">
                          <div class="unit-consultation-room-heading">
                            <span aria-hidden="true">
                              <svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/><path d="M13 12h.01"/></svg>
                            </span>
                            <div><h3 data-unit-room-field="name">Consultorio</h3><p>{{ $unit->name }}</p></div>
                          </div>
                          <div class="unit-consultation-room-actions">
                            <a href="{{ route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'catalog' => 'consulting']) }}" data-unit-room-edit>
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
                                <a href="{{ route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'catalog' => 'consulting']) }}">
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
                                  @foreach ($consultationRoomSpecialties as $roomSpecialty)
                                    <option value="{{ $roomSpecialty }}">{{ $roomSpecialty }}</option>
                                  @endforeach
                                </select>
                              </label>
                              <label>Numero de consultorio
                                <select data-unit-room-select>
                                  @foreach ($registeredConsultationRooms as $room)
                                    <option value="{{ $room['id'] }}">{{ $room['name'] }}</option>
                                  @endforeach
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

                      <section class="unit-consultation-room-empty" data-unit-room-empty @if ($registeredConsultationRooms->isNotEmpty()) hidden @endif>
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M6 21V3h12v18M3 21h18"/><path d="M10 7h4v10h-4z"/></svg></span>
                        <h3>Sin consultorios registrados</h3>
                        <p>Agrega el primer consultorio para comenzar a organizar la agenda de Consulta externa.</p>
                        <a href="{{ route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'create' => 'consulting']) }}">Nuevo consultorio</a>
                      </section>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="doctors" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Medicos</h3><p>Personal medico adscrito a la unidad.</p></div>
                        <a href="{{ route('unit.dashboard', ['section' => 'doctors']) }}">Administrar medicos</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Medico</th><th>Cedula</th><th>Especialidad</th><th>Subespecialidad</th><th>Servicio</th><th>Estatus</th></tr></thead>
                          <tbody>
                            @forelse ($unit->doctors as $doctor)
                              <tr>
                                <td><strong>{{ $doctor->full_name }}</strong><small>{{ $doctor->user?->email ?? 'Sin correo asociado' }}</small></td>
                                <td>{{ $doctor->professional_license ?? 'Sin cedula' }}</td>
                                <td>{{ $doctor->specialty ?? 'Sin especialidad' }}</td>
                                <td>{{ $doctor->subspecialty ?? 'Sin subespecialidad' }}</td>
                                <td>{{ $doctor->service_name ?? 'Consulta externa' }}</td>
                                <td><span class="unit-native-status">{{ $statusText($doctor->status) }}</span></td>
                              </tr>
                            @empty
                              <tr><td colspan="6" class="unit-native-empty">Sin medicos adscritos.</td></tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="specialties" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Especialidades</h3><p>Especialidades disponibles en la agenda de la unidad.</p></div>
                        <a href="{{ route('unit.dashboard', ['section' => 'specialties']) }}">Ver catalogo institucional</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Especialidad</th><th>Medicos adscritos</th><th>Citas registradas</th><th>Citas de hoy</th><th>Estatus</th></tr></thead>
                          <tbody>
                            @forelse ($consultationSpecialties as $consultationSpecialty)
                              @php
                                $specialtyDoctors = $unit->doctors->where('specialty', $consultationSpecialty)->count();
                                $specialtyAppointments = $consultationAppointments->where('specialty', $consultationSpecialty);
                              @endphp
                              <tr>
                                <td><strong>{{ $consultationSpecialty }}</strong></td>
                                <td>{{ $specialtyDoctors }}</td>
                                <td>{{ $specialtyAppointments->count() }}</td>
                                <td>{{ $specialtyAppointments->filter(fn ($appointment) => $appointment->starts_at?->isToday())->count() }}</td>
                                <td><span class="unit-native-status">Activo</span></td>
                              </tr>
                            @empty
                              <tr><td colspan="5" class="unit-native-empty">Sin especialidades disponibles.</td></tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="patients" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Pacientes</h3><p>Pacientes con citas registradas en esta unidad.</p></div>
                        <a href="{{ route('unit.dashboard', ['section' => 'patients']) }}">Ver catalogo de pacientes</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Paciente</th><th>CURP</th><th>Telefono</th><th>Correo</th><th>Ultima cita</th><th>Estatus</th></tr></thead>
                          <tbody>
                            @forelse ($patients as $patient)
                              @php
                                $latestPatientAppointment = $patient->appointments->first();
                              @endphp
                              <tr>
                                <td><strong>{{ $patient->full_name }}</strong><small>{{ $patient->platform_number ?? 'Sin numero de plataforma' }}</small></td>
                                <td>{{ $patient->curp ?? 'Sin CURP' }}</td>
                                <td>{{ $patient->phone ?? 'Sin telefono' }}</td>
                                <td>{{ $patient->email ?? 'Sin correo' }}</td>
                                <td>{{ $latestPatientAppointment?->starts_at?->format('d/m/Y H:i') ?? 'Sin cita' }}</td>
                                <td><span class="unit-native-status">{{ $statusText($patient->status) }}</span></td>
                              </tr>
                            @empty
                              <tr><td colspan="6" class="unit-native-empty">Sin pacientes vinculados a consulta externa.</td></tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>

                    <div class="unit-consultation-subview" data-unit-consultation-view="prescriptions" hidden>
                      <header class="unit-consultation-subview-header">
                        <div><span>Consulta externa</span><h3>Recetas</h3><p>Recetas emitidas por los medicos de la unidad.</p></div>
                        <a href="{{ route('outpatient.dashboard', ['unit' => $unit->id, 'section' => 'prescriptions']) }}">Abrir modulo de recetas</a>
                      </header>
                      <div class="unit-native-table-scroll">
                        <table class="unit-native-table unit-consultation-subview-table">
                          <thead><tr><th>Folio</th><th>Fecha</th><th>Paciente</th><th>Medico</th><th>Medicamentos</th><th>Estatus</th></tr></thead>
                          <tbody>
                            @forelse ($consultationPrescriptions as $prescription)
                              <tr>
                                <td><strong>{{ $prescription->code ?? 'REC-'.str_pad((string) $prescription->id, 5, '0', STR_PAD_LEFT) }}</strong></td>
                                <td>{{ $prescription->issued_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                <td>{{ $prescription->patient?->full_name ?? 'Sin paciente' }}</td>
                                <td>{{ $prescription->doctor?->full_name ?? 'Sin medico' }}</td>
                                <td>{{ $prescription->items->pluck('medication_name')->filter()->join(', ') ?: 'Sin medicamentos' }}</td>
                                <td><span class="unit-native-status">{{ $statusText($prescription->status) }}</span></td>
                              </tr>
                            @empty
                              <tr><td colspan="6" class="unit-native-empty">Sin recetas emitidas en esta unidad.</td></tr>
                            @endforelse
                          </tbody>
                        </table>
                      </div>
                    </div>
                  @endif

                  <div class="unit-service-operation-view"
                       data-unit-service-operation
                       @if ($isConsultationService) data-unit-consultation-view="agenda" @endif
                       data-operation-url="{{ $operationHref }}"
                       hidden>
                    <div class="unit-service-operation-live">
                      <span><i aria-hidden="true"></i> Operacion en tiempo real</span>
                      <div>
                        <small>Actualizado {{ now()->format('d/m/Y H:i') }}</small>
                        <a href="{{ $operationHref }}">Abrir modulo operativo</a>
                      </div>
                    </div>

                    <div class="unit-service-operation-metrics" aria-label="Resumen operativo de {{ $serviceName }}">
                      <article class="is-today">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M9 11h6M9 15h4"/><path d="M5 4h14v17H5z"/><path d="M9 4V2M15 4V2"/></svg></span>
                        <div><small>Solicitudes hoy</small><strong>{{ $operationToday }}</strong><em>{{ $operationTotal }} registradas</em></div>
                      </article>
                      <article class="is-process">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0-2.3 5.7"/><path d="M20 4v7h-7"/></svg></span>
                        <div><small>En proceso</small><strong>{{ $operationInProcess }}</strong><em>{{ $operationPercentage($operationInProcess) }}</em></div>
                      </article>
                      <article class="is-completed">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg></span>
                        <div><small>Completadas</small><strong>{{ $operationCompleted }}</strong><em>{{ $operationPercentage($operationCompleted) }}</em></div>
                      </article>
                      <article class="is-pending">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <div><small>Pendientes</small><strong>{{ $operationPending }}</strong><em>{{ $operationPercentage($operationPending) }}</em></div>
                      </article>
                      <article class="is-average">
                        <span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="13" r="8"/><path d="M9 2h6M12 5v3M12 13l3-2"/></svg></span>
                        <div><small>Tiempo promedio</small><strong>{{ $averageTimeLabel }}</strong><em>Casos completados</em></div>
                      </article>
                    </div>

                    <div class="unit-service-operation-filters" data-unit-operation-filters>
                      <label>Fecha
                        <input type="date" data-unit-operation-filter="date">
                      </label>
                      <label>Hospital
                        <select data-unit-operation-filter="hospital">
                          <option value="">Todos</option>
                          <option value="{{ $unit->name }}">{{ $unit->name }}</option>
                        </select>
                      </label>
                      <label>Tipo de solicitud
                        <select data-unit-operation-filter="type">
                          <option value="">Todos</option>
                          @foreach ($operationTypeOptions as $operationTypeOption)
                            <option value="{{ $operationTypeOption }}">{{ $operationTypeOption }}</option>
                          @endforeach
                        </select>
                      </label>
                      <label>Prioridad
                        <select data-unit-operation-filter="priority">
                          <option value="">Todas</option>
                          @foreach ($operationPriorityOptions as $operationPriorityOption)
                            <option value="{{ $operationPriorityOption }}">{{ $operationPriorityOption }}</option>
                          @endforeach
                        </select>
                      </label>
                      <label>Estatus
                        <select data-unit-operation-filter="status">
                          <option value="">Todos</option>
                          @foreach ($operationStatusOptions as $operationStatusOption)
                            <option value="{{ $operationStatusOption }}">{{ $operationStatusOption }}</option>
                          @endforeach
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
                    @if ($isConsultationService)
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
                          @forelse ($consultationAppointments as $appointment)
                            @php
                              $folioYear = $appointment->created_at?->format('Y') ?? now()->format('Y');
                              $folio = data_get($appointment->metadata, 'folio') ?? 'CE-'.$folioYear.'-'.str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT);
                              $appointmentType = $consultationRequestType($appointment);
                              [$priorityLabel, $priorityClass] = $consultationPriority($appointment);
                              [$consultationStatusLabel, $consultationStatusClass] = $consultationStatus($appointment->status);
                              $patientMeta = $appointment->patient?->curp ?? 'Sin CURP';
                              $appointmentSearch = $appointment->patient?->full_name ?? $folio;
                              $appointmentRoute = route('outpatient.dashboard', ['unit' => $unit->id, 'search' => $appointmentSearch]);
                              $appointmentHospital = $appointment->medicalUnit?->name ?? $unit->name;
                            @endphp
                            <tr data-unit-operation-row
                                data-date="{{ $appointment->starts_at?->format('Y-m-d') }}"
                                data-hospital="{{ $appointmentHospital }}"
                                data-type="{{ $appointmentType }}"
                                data-priority="{{ $priorityLabel }}"
                                data-status="{{ $consultationStatusLabel }}"
                                data-search="{{ collect([$folio, $appointment->patient?->full_name, $patientMeta, $appointment->doctor?->full_name, $appointmentType])->filter()->implode(' ') }}">
                              <td><strong class="unit-consultation-folio">{{ $folio }}</strong></td>
                              <td>{{ $appointment->starts_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                              <td><strong>{{ $appointment->patient?->full_name ?? 'Paciente pendiente' }}</strong><small>CURP: {{ $patientMeta }}</small></td>
                              <td><strong>{{ $appointmentHospital }}</strong></td>
                              <td>{{ $appointmentType }}</td>
                              <td><span class="unit-consultation-chip is-priority-{{ $priorityClass }}">{{ $priorityLabel }}</span></td>
                              <td>{{ $appointment->doctor?->full_name ?? 'Sin responsable' }}</td>
                              <td><button type="button" class="unit-consultation-chip is-status-{{ $consultationStatusClass }}" data-unit-status-filter="{{ $consultationStatusLabel }}">{{ $consultationStatusLabel }}</button></td>
                              <td>{{ $appointment->updated_at?->format('d/m/Y H:i') ?? 'Sin actualizacion' }}</td>
                              <td>
                                <div class="unit-consultation-actions">
                                  <a href="{{ $appointmentRoute }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    Ver
                                  </a>
                                  <a href="{{ $appointmentRoute }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12a9 9 0 0 1-15.5 6.2"/><path d="M3 12A9 9 0 0 1 18.5 5.8"/><path d="M18 2v4h-4"/><path d="M6 22v-4h4"/></svg>
                                    Actualizar
                                  </a>
                                </div>
                              </td>
                            </tr>
                          @empty
                            <tr data-unit-operation-empty><td colspan="10" class="unit-native-empty">Sin consultas externas registradas.</td></tr>
                          @endforelse
                          @if ($consultationAppointments->isNotEmpty())
                            <tr data-unit-operation-no-results hidden><td colspan="10" class="unit-native-empty">No hay solicitudes que coincidan con los filtros.</td></tr>
                          @endif
                        </tbody>
                      </table>
                    @elseif ($usesMixtureTable)
                      @php
                        $mixtureRequests = $serviceProviderRequests;
                        $mixtureRouteArea = ($isChemotherapyService || $isCentralMixturesService) ? 'oncology' : 'nursing';
                        $mixtureEmptyMessage = match (true) {
                          $isChemotherapyService => 'Sin solicitudes de quimioterapia registradas.',
                          $isCentralMixturesService => 'Sin solicitudes de mezclas registradas.',
                          default => 'Sin solicitudes de nutricion parenteral registradas.',
                        };
                      @endphp
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
                          @forelse ($mixtureRequests as $mixtureRequest)
                            @php
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
                            @endphp
                            <tr data-unit-operation-row
                                data-date="{{ $mixtureRequest->requested_at?->format('Y-m-d') }}"
                                data-hospital="{{ $mixtureHospital }}"
                                data-type="{{ $mixtureTypeLabel }}"
                                data-priority="{{ $mixturePriorityLabel }}"
                                data-status="{{ $mixtureStatusLabel }}"
                                data-search="{{ collect([$mixId, $requestNumber, $mixtureRequest->patient?->full_name, $mixtureRequest->provider?->name, $remission, $lot])->filter()->implode(' ') }}">
                              <td><span class="unit-consultation-chip is-type-{{ $mixtureTypeClass }}">{{ $mixtureTypeLabel }}</span></td>
                              <td>{{ $mixId }}</td>
                              <td>{{ $requestNumber }}</td>
                              <td>{{ $mixtureHospital }}</td>
                              <td>{{ $mixtureRequest->patient?->full_name ?? 'Sin paciente' }}</td>
                              <td>{{ $mixtureRequest->requested_at?->format('Y-m-d H:i') ?? '-' }}</td>
                              <td>{{ $mixtureRequest->required_at?->format('Y-m-d H:i') ?? '-' }}</td>
                              <td><button type="button" class="unit-consultation-chip is-status-{{ $mixtureStatusClass }}" data-unit-status-filter="{{ $mixtureStatusLabel }}">{{ $mixtureStatusLabel }}</button></td>
                              <td>{{ $remission }}</td>
                              <td>{{ $lot }}</td>
                              <td><a class="unit-nutrition-view-link" href="{{ $mixtureRoute }}">Ver</a></td>
                            </tr>
                          @empty
                            <tr data-unit-operation-empty><td colspan="11" class="unit-native-empty">{{ $mixtureEmptyMessage }}</td></tr>
                          @endforelse
                          @if ($mixtureRequests->isNotEmpty())
                            <tr data-unit-operation-no-results hidden><td colspan="11" class="unit-native-empty">No hay solicitudes que coincidan con los filtros.</td></tr>
                          @endif
                        </tbody>
                      </table>
                    @else
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
                          @forelse ($serviceProviderRequests as $serviceProviderRequest)
                            @php
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
                            @endphp
                            <tr data-unit-operation-row
                                data-date="{{ $serviceProviderRequest->requested_at?->format('Y-m-d') }}"
                                data-hospital="{{ $genericHospital }}"
                                data-type="{{ $genericType }}"
                                data-priority="{{ $genericPriorityLabel }}"
                                data-status="{{ $genericStatusLabel }}"
                                data-search="{{ collect([$genericFolio, $serviceProviderRequest->patient?->full_name, $genericResponsible, $genericType])->filter()->implode(' ') }}">
                              <td><strong class="unit-consultation-folio">{{ $genericFolio }}</strong></td>
                              <td>{{ $serviceProviderRequest->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                              <td><strong>{{ $serviceProviderRequest->patient?->full_name ?? 'Paciente pendiente' }}</strong></td>
                              <td><strong>{{ $genericHospital }}</strong></td>
                              <td>{{ $genericType }}</td>
                              <td><span class="unit-consultation-chip is-priority-{{ $genericPriorityClass }}">{{ $genericPriorityLabel }}</span></td>
                              <td>{{ $genericResponsible }}</td>
                              <td><button type="button" class="unit-consultation-chip is-status-{{ $genericStatusClass }}" data-unit-status-filter="{{ $genericStatusLabel }}">{{ $genericStatusLabel }}</button></td>
                              <td>{{ $serviceProviderRequest->updated_at?->format('d/m/Y H:i') ?? 'Sin actualizacion' }}</td>
                              <td><div class="unit-consultation-actions"><a href="{{ $genericRoute }}">Ver</a><a href="{{ $genericRoute }}">Actualizar</a></div></td>
                            </tr>
                          @empty
                            <tr data-unit-operation-empty><td colspan="10" class="unit-native-empty">Sin solicitudes registradas para {{ $serviceName }}.</td></tr>
                          @endforelse
                          @if ($serviceProviderRequests->isNotEmpty())
                            <tr data-unit-operation-no-results hidden><td colspan="10" class="unit-native-empty">No hay solicitudes que coincidan con los filtros.</td></tr>
                          @endif
                        </tbody>
                      </table>
                    @endif
                  </div>
                    <footer class="unit-service-operation-footer">
                      <span>Mostrando <strong data-unit-operation-visible-count>{{ $operationTotal }}</strong> de {{ $operationTotal }} solicitudes</span>
                      <span>La informacion corresponde al estado actual del servicio.</span>
                    </footer>
                  </div>
                </section>
              @endforeach
            </div>
          @else
            <section class="unit-native-table-card">
              <div class="unit-native-table-heading">
                <h2>Servicios habilitados</h2>
                <p>Sin servicios habilitados para esta unidad.</p>
              </div>
            </section>
          @endif
        </div>

        @foreach ($unitServiceContracts as $contract)
          @php
            $catalogServiceName = $contract->service?->name ?? 'Servicio sin nombre';
            $catalogProvider = data_get($contract->metadata, 'provider', 'Operacion clinica');
            $catalogKey = $contract->service?->code ?? str($catalogServiceName)->slug();
          @endphp
          <dialog class="unit-service-catalog-dialog" data-service-catalog-dialog="{{ $contract->id }}">
            <header><div><h2>Catalogo de productos/Servicios</h2><p>{{ $catalogServiceName }} - {{ $catalogProvider }}</p></div><button type="button" data-close-service-catalog>Cerrar</button></header>
            <div class="unit-service-catalog-facts">
              <article><small>Servicio</small><strong>{{ $catalogServiceName }}</strong></article>
              <article><small>Proveedor</small><strong>{{ $catalogProvider }}</strong></article>
              <article><small>Elementos habilitados</small><strong>1</strong></article>
            </div>
            <div class="unit-service-catalog-scroll">
              <table>
                <thead><tr><th>Clave</th><th>Producto / servicio</th><th>Tipo</th><th>Proveedor</th><th>Detalle</th><th>Estatus</th></tr></thead>
                <tbody><tr><td>{{ $catalogKey }}</td><td><strong>{{ $catalogServiceName }}</strong><small>{{ $contract->service?->specialty ?? 'Servicio general' }}</small></td><td>{{ $contract->service?->category ?? 'Sin categoria' }}</td><td>{{ $catalogProvider }}</td><td>Vigencia {{ $contract->starts_at?->format('d/m/Y') ?? 'sin inicio' }} - {{ $contract->ends_at?->format('d/m/Y') ?? 'sin vencimiento' }}</td><td><span class="unit-native-status">{{ $statusText($contract->status) }}</span></td></tr></tbody>
              </table>
            </div>
          </dialog>
        @endforeach
        <script>
          (() => {
            const root = document.querySelector('[data-unit-service-dashboard]');
            if (root) {
              const carousel = root.querySelector('[data-unit-service-carousel]');
              const tabs = [...root.querySelectorAll('[data-unit-service-tab]')];
              const panels = [...root.querySelectorAll('[data-unit-service-panel]')];
              const consultationSubmenu = root.querySelector('[data-unit-consultation-submenu]');
              const consultationCarousel = root.querySelector('[data-unit-consultation-carousel]');
              const consultationTabs = [...root.querySelectorAll('[data-unit-consultation-tab]')];
              const consultationViews = [...root.querySelectorAll('[data-unit-consultation-view]')];
              const consultationCalendar = root.querySelector('[data-unit-consultation-calendar]');
              const consultationRoomsWorkspace = root.querySelector('[data-unit-consultation-rooms]');
              const consultationCalendarRooms = @json($consultationCalendarRooms);
              const consultationCalendarPatients = @json($consultationCalendarPatients);
              const consultationCalendarDoctors = @json($consultationCalendarDoctors);
              const consultationCalendarAppointments = @json($consultationCalendarAppointments);
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
                          class="unit-consultation-calendar-event is-${escapeHtml(appointment.status)}${compact ? ' is-compact' : ''}"
                          data-unit-calendar-appointment="${escapeHtml(appointment.id)}">
                    <time>${escapeHtml(appointment.time)}</time>
                    <span><strong>${escapeHtml(appointment.patient)}</strong><small>${escapeHtml(appointment.reason)}</small></span>
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
                const setDialogField = (field, value) => {
                  const target = dialog?.querySelector(`[data-unit-calendar-dialog-field="${field}"]`);
                  if (target) target.textContent = value || 'Sin registro';
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
                  setDialogField('patient_meta', patientMeta({
                    curp: appointment.patient_curp,
                    age: appointment.patient_age,
                    sex: appointment.patient_sex,
                  }));
                  setDialogField('specialty', appointment.specialty);
                  setDialogField('doctor', appointment.doctor);
                  setDialogField('doctor_license', appointment.doctor_license);
                  setDialogField('room', room?.name || appointment.location);
                  setDialogField('date', formatLongDate(appointment.date));
                  setDialogField('time', `${appointment.time} - ${appointment.ends_at}`);
                  setDialogField('reason', appointment.reason);
                  const status = dialog.querySelector('[data-unit-calendar-dialog-field="status"]');
                  if (status) {
                    status.className = `unit-consultation-status-chip is-${appointment.status}`;
                    status.textContent = appointment.status_label || statusLabels[appointment.status];
                  }
                  const locked = ['cancelled', 'completed', 'no_show'].includes(appointment.status_value);
                  dialog.querySelectorAll('[data-unit-calendar-edit], [data-unit-calendar-reschedule], [data-unit-calendar-cancel]')
                    .forEach((button) => { button.disabled = locked; });
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
                const setWizardStep = (step) => {
                  newDialog?.querySelectorAll('[data-unit-new-step]').forEach((panel) => {
                    panel.hidden = Number(panel.dataset.unitNewStep) !== step;
                  });
                  newDialog?.querySelectorAll('[data-unit-new-progress]').forEach((item) => {
                    const itemStep = Number(item.dataset.unitNewProgress);
                    item.classList.toggle('is-active', itemStep === step);
                    item.classList.toggle('is-complete', itemStep < step);
                  });
                  const titles = {
                    1: ['Agendar nueva cita', 'Selecciona al paciente y los datos de la cita.'],
                    2: ['Agendar nueva cita', 'Selecciona un horario disponible para la cita.'],
                    3: ['Confirmar nueva cita', 'Revisa la informaci\u00f3n antes de confirmar.'],
                  };
                  const title = newDialog?.querySelector('[data-unit-new-title]');
                  const subtitle = newDialog?.querySelector('[data-unit-new-subtitle]');
                  if (title) title.textContent = titles[step][0];
                  if (subtitle) subtitle.textContent = titles[step][1];
                  showWizardError();
                };
                const renderPatientResults = (query = '') => {
                  const results = newDialog?.querySelector('[data-unit-new-patient-results]');
                  if (!results) return;
                  const needle = normalize(query);
                  const matches = consultationCalendarPatients.filter((patient) => (
                    !needle || normalize([patient.name, patient.curp, patient.platform_number, patient.nss].join(' ')).includes(needle)
                  )).slice(0, 8);
                  results.innerHTML = matches.length
                    ? matches.map((patient) => `<button type="button" data-unit-new-patient="${escapeHtml(patient.id)}" role="option"><strong>${escapeHtml(patient.name)}</strong><small>${escapeHtml(patient.curp)} | ${escapeHtml(patient.platform_number)}</small></button>`).join('')
                    : '<small class="is-empty">No se encontraron pacientes con esos datos.</small>';
                  results.hidden = false;
                };
                const selectPatient = (id) => {
                  selectedPatient = patientById(id);
                  if (!selectedPatient) return;
                  const search = newDialog?.querySelector('[data-unit-new-patient-search]');
                  const results = newDialog?.querySelector('[data-unit-new-patient-results]');
                  const selected = newDialog?.querySelector('[data-unit-new-selected-patient]');
                  newField('patient_id').value = selectedPatient.id;
                  if (search) search.value = selectedPatient.name;
                  if (results) results.hidden = true;
                  if (selected) selected.hidden = false;
                  setWizardText('data-unit-new-selected', 'patient', selectedPatient.name);
                  setWizardText('data-unit-new-selected', 'patient_meta', patientMeta(selectedPatient));
                  showWizardError();
                };
                const clearPatient = () => {
                  selectedPatient = null;
                  newField('patient_id').value = '';
                  const search = newDialog?.querySelector('[data-unit-new-patient-search]');
                  const selected = newDialog?.querySelector('[data-unit-new-selected-patient]');
                  if (search) {
                    search.value = '';
                    search.focus();
                  }
                  if (selected) selected.hidden = true;
                  renderPatientResults();
                };
                const populateDoctors = () => {
                  const specialty = newField('specialty')?.value || '';
                  const select = newField('doctor_id');
                  if (!select) return;
                  const previous = select.value;
                  const doctors = consultationCalendarDoctors.filter((doctor) => (
                    specialty && normalize(doctor.specialty) === normalize(specialty)
                  ));
                  select.innerHTML = `<option value="">${doctors.length ? 'Selecciona un medico' : 'Sin medicos disponibles'}</option>${doctors
                    .map((doctor) => `<option value="${escapeHtml(doctor.id)}">${escapeHtml(doctor.name)}</option>`).join('')}`;
                  select.disabled = doctors.length === 0;
                  if (doctors.some((doctor) => doctor.id === previous)) select.value = previous;
                  newField('appointment_time').value = '';
                };
                const renderWizardSummary = () => {
                  const doctor = doctorById(newField('doctor_id')?.value);
                  const room = roomById(newField('procedure_area_id')?.value);
                  const date = newField('appointment_date')?.value;
                  const time = newField('appointment_time')?.value;
                  const duration = Number(newForm?.elements.namedItem('duration')?.value || 30);
                  setWizardText('data-unit-new-summary', 'patient', selectedPatient?.name);
                  setWizardText('data-unit-new-summary', 'patient_meta', patientMeta(selectedPatient));
                  setWizardText('data-unit-new-summary', 'specialty', newField('specialty')?.value);
                  setWizardText('data-unit-new-summary', 'room', room?.name);
                  setWizardText('data-unit-new-confirm', 'patient', selectedPatient?.name);
                  setWizardText('data-unit-new-confirm', 'specialty', newField('specialty')?.value);
                  setWizardText('data-unit-new-confirm', 'doctor', doctor?.name);
                  setWizardText('data-unit-new-confirm', 'room', room?.name);
                  setWizardText('data-unit-new-confirm', 'date', date ? formatLongDate(date) : '');
                  setWizardText('data-unit-new-confirm', 'time', time ? `${time} - ${formatTime(timeToMinutes(time) + duration)}` : '');
                  setWizardText('data-unit-new-confirm', 'reason', newField('reason')?.value);
                };
                const renderAvailableSlots = () => {
                  const container = newDialog?.querySelector('[data-unit-new-slots]');
                  const doctor = doctorById(newField('doctor_id')?.value);
                  const room = roomById(newField('procedure_area_id')?.value);
                  const dateValue = newField('appointment_date')?.value;
                  if (!container || !doctor || !room || !dateValue) return;
                  const selectedDate = parseDate(dateValue);
                  const weekday = selectedDate.getDay();
                  const isoWeekday = weekday === 0 ? 7 : weekday;
                  const month = selectedDate.getMonth() + 1;
                  const duration = Number(newForm?.elements.namedItem('duration')?.value || 30);
                  const roomSchedules = Array.isArray(room.schedules) ? room.schedules : [];
                  const roomWindows = roomSchedules.length
                    ? roomSchedules.filter((schedule) => Number(schedule.day) === weekday)
                    : [{ start: '08:00', end: '16:00' }];
                  const doctorAvailability = Array.isArray(doctor.availability) ? doctor.availability : [];
                  const doctorWindows = doctorAvailability.length ? doctorAvailability.filter((rule) => (
                    Number(rule.weekday) === isoWeekday
                    && (!rule.start_date || rule.start_date <= dateValue)
                    && (!rule.end_date || rule.end_date >= dateValue)
                    && (!Array.isArray(rule.months) || rule.months.map(Number).includes(month))
                  )) : [{ start: '00:00', end: '23:59' }];
                  const windows = roomWindows.flatMap((roomWindow) => doctorWindows.map((doctorWindow) => ({
                    start: Math.max(timeToMinutes(roomWindow.start), timeToMinutes(doctorWindow.start)),
                    end: Math.min(timeToMinutes(roomWindow.end), timeToMinutes(doctorWindow.end)),
                  }))).filter((window) => window.start + duration <= window.end);
                  const slots = [];
                  windows.forEach((window) => {
                    for (let cursor = Math.ceil(window.start / 30) * 30; cursor + duration <= window.end; cursor += 30) {
                      const candidate = new Date(`${dateValue}T${formatTime(cursor)}:00`);
                      if (candidate <= new Date()) continue;
                      const appointments = consultationCalendarAppointments.filter((appointment) => (
                        appointment.date === dateValue
                        && !['cancelled', 'no_show'].includes(appointment.status_value)
                        && timeToMinutes(appointment.time) < cursor + duration
                        && timeToMinutes(appointment.ends_at || appointment.time) > cursor
                      ));
                      const doctorBusy = appointments.some((appointment) => appointment.doctor_id === doctor.id);
                      const roomBusy = appointments.filter((appointment) => appointment.room_id === room.id).length >= Number(room.capacity || 1);
                      if (!doctorBusy && !roomBusy) slots.push(formatTime(cursor));
                    }
                  });
                  const uniqueSlots = [...new Set(slots)];
                  const selectedTime = newField('appointment_time').value;
                  if (!uniqueSlots.includes(selectedTime)) newField('appointment_time').value = '';
                  container.innerHTML = uniqueSlots.length
                    ? uniqueSlots.map((slot) => `<button type="button" class="${slot === selectedTime ? 'is-selected' : ''}" data-unit-new-slot="${slot}"><span aria-hidden="true">&#10003;</span>${slot}</button>`).join('')
                    : '<p class="unit-consultation-no-slots">No hay horarios disponibles para esta fecha. Regresa y selecciona otra fecha, medico o consultorio.</p>';
                };
                const validateWizardStepOne = () => {
                  if (!selectedPatient) {
                    showWizardError('Selecciona un paciente del catalogo para continuar.');
                    newDialog?.querySelector('[data-unit-new-patient-search]')?.focus();
                    return false;
                  }
                  const required = ['specialty', 'doctor_id', 'procedure_area_id', 'appointment_date', 'reason'];
                  const invalid = required.map((name) => newField(name)).find((field) => !field?.value || !field.checkValidity());
                  if (invalid) {
                    showWizardError('Completa los campos obligatorios para consultar los horarios.');
                    invalid.reportValidity();
                    return false;
                  }
                  return true;
                };
                const resetNewAppointment = () => {
                  newForm?.reset();
                  selectedPatient = null;
                  newField('patient_id').value = '';
                  newField('appointment_time').value = '';
                  const today = dateKey(new Date());
                  const requestedDate = state.date >= today ? state.date : today;
                  newField('appointment_date').min = today;
                  newField('appointment_date').value = requestedDate;
                  const search = newDialog?.querySelector('[data-unit-new-patient-search]');
                  const results = newDialog?.querySelector('[data-unit-new-patient-results]');
                  const selected = newDialog?.querySelector('[data-unit-new-selected-patient]');
                  if (search) search.value = '';
                  if (results) {
                    results.innerHTML = '';
                    results.hidden = true;
                  }
                  if (selected) selected.hidden = true;
                  populateDoctors();
                  setWizardStep(1);
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
                dialog?.querySelector('[data-unit-calendar-edit]')?.addEventListener('click', () => openEditAppointment(activeAppointment));
                dialog?.querySelector('[data-unit-calendar-reschedule]')?.addEventListener('click', () => openEditAppointment(activeAppointment, true));
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
                newDialog?.querySelector('[data-unit-new-patient-search]')?.addEventListener('input', (event) => renderPatientResults(event.target.value));
                newDialog?.querySelector('[data-unit-new-patient-search]')?.addEventListener('focus', (event) => renderPatientResults(event.target.value));
                newDialog?.querySelector('[data-unit-new-patient-results]')?.addEventListener('click', (event) => {
                  const patientButton = event.target.closest('[data-unit-new-patient]');
                  if (patientButton) selectPatient(patientButton.dataset.unitNewPatient);
                });
                newDialog?.querySelector('[data-unit-new-patient-clear]')?.addEventListener('click', clearPatient);
                newField('specialty')?.addEventListener('change', populateDoctors);
                ['doctor_id', 'procedure_area_id', 'appointment_date'].forEach((name) => newField(name)?.addEventListener('change', () => {
                  newField('appointment_time').value = '';
                }));
                newDialog?.querySelector('[data-unit-new-slots]')?.addEventListener('click', (event) => {
                  const slot = event.target.closest('[data-unit-new-slot]');
                  if (!slot) return;
                  newField('appointment_time').value = slot.dataset.unitNewSlot;
                  newDialog.querySelectorAll('[data-unit-new-slot]').forEach((button) => button.classList.toggle('is-selected', button === slot));
                  showWizardError();
                });
                newDialog?.querySelectorAll('[data-unit-new-next]').forEach((button) => button.addEventListener('click', () => {
                  const nextStep = Number(button.dataset.unitNewNext);
                  if (nextStep === 2) {
                    if (!validateWizardStepOne()) return;
                    renderWizardSummary();
                    renderAvailableSlots();
                  }
                  if (nextStep === 3) {
                    if (!newField('appointment_time').value) {
                      showWizardError('Selecciona un horario disponible para continuar.');
                      return;
                    }
                    renderWizardSummary();
                  }
                  setWizardStep(nextStep);
                }));
                newDialog?.querySelectorAll('[data-unit-new-back]').forEach((button) => button.addEventListener('click', () => {
                  setWizardStep(Number(button.dataset.unitNewBack));
                }));
                [editForm, cancelForm, newForm].forEach((form) => form?.addEventListener('submit', (event) => {
                  if (!form.checkValidity()) return;
                  const submitter = event.submitter;
                  if (submitter) {
                    submitter.disabled = true;
                    submitter.setAttribute('aria-busy', 'true');
                  }
                }));
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
              const initializeNutritionRequestBoards = () => {
                root.querySelectorAll('[data-unit-nutrition-requests]').forEach((board) => {
                  const filters = [...board.querySelectorAll('[data-unit-nutrition-filter]')];
                  const rows = [...board.querySelectorAll('[data-unit-nutrition-row]')];
                  const noResults = board.querySelector('[data-unit-nutrition-no-results]');
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

                  const applyNutritionFilter = (filter) => {
                    let visible = 0;
                    filters.forEach((button) => {
                      const active = button.dataset.unitNutritionFilter === filter;
                      button.classList.toggle('is-active', active);
                      button.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                    rows.forEach((row) => {
                      const matches = filter === 'all' || row.dataset.category === filter;
                      row.hidden = !matches;
                      if (matches) visible += 1;
                    });
                    if (noResults) noResults.hidden = rows.length === 0 || visible > 0;
                    board.dataset.activeFilter = filter;
                  };

                  filters.forEach((button) => button.addEventListener('click', () => {
                    applyNutritionFilter(button.dataset.unitNutritionFilter);
                  }));
                  board.querySelectorAll('[data-unit-nutrition-status]').forEach((button) => {
                    button.addEventListener('click', () => applyNutritionFilter(button.dataset.unitNutritionStatus));
                  });
                  openButton?.addEventListener('click', showNutritionDialog);
                  dialog?.querySelectorAll('[data-unit-nutrition-close]').forEach((button) => {
                    button.addEventListener('click', () => dialog.close());
                  });
                  dialog?.addEventListener('click', (event) => {
                    if (event.target === dialog) dialog.close();
                  });
                  if (dialog?.hasAttribute('data-open-on-load')) showNutritionDialog();
                  applyNutritionFilter('all');
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
              initializeNutritionRequestBoards();
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
              root.querySelector('[data-unit-service-carousel-prev]')?.addEventListener('click', () => carousel.scrollBy({ left: -260, behavior: 'smooth' }));
              root.querySelector('[data-unit-service-carousel-next]')?.addEventListener('click', () => carousel.scrollBy({ left: 260, behavior: 'smooth' }));
              root.querySelector('[data-unit-consultation-prev]')?.addEventListener('click', () => consultationCarousel?.scrollBy({ left: -360, behavior: 'smooth' }));
              root.querySelector('[data-unit-consultation-next]')?.addEventListener('click', () => consultationCarousel?.scrollBy({ left: 360, behavior: 'smooth' }));
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
      @endif

      @if ($section === 'users')
        <div class="unit-native-two-column">
          <section class="unit-native-form-card">
            <header>
              <h2 data-operational-form-title>Alta de usuario operativo</h2>
              <p>Actualiza informacion, rol, asignacion de area operativa y autorizaciones del usuario</p>
            </header>
            <form method="post" action="{{ route('unit.operational-users.store') }}" data-operational-user-form>
              @csrf
              <input type="hidden" name="_method" value="patch" disabled data-operational-method>
              <label class="wide">Nombre completo<input name="name" required></label>
              <label>Usuario o correo<input name="username" required></label>
              <label>Contrasena<input name="password" type="password"></label>
              <label>Rol operativo<select name="role_label"><option>Responsable de Area</option><option>Operador</option></select></label>
              <label>Area autorizadora<select name="authority"><option>Direccion General</option><option>Direccion Administrativa</option></select></label>
              <label>Servicio asignado<select name="service">@foreach ($unit->contractedServices as $contract)<option>{{ $contract->service?->name }}</option>@endforeach<option>Nutricion parenteral</option></select></label>

              <fieldset class="wide">
                <legend>Asignacion de Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  @foreach ($operationalAreas as $area)
                    <label>
                      <input type="radio" name="operational_area_id" value="{{ $area->id }}" @checked($loop->first) required>
                      <span><strong>{{ $area->label }}</strong><small>{{ $areaChoices[$area->label] ?? 'Operacion y seguimiento del area.' }}</small></span>
                    </label>
                  @endforeach
                </div>
              </fieldset>

              <fieldset class="wide">
                <legend>Acciones en Area Operativa</legend>
                <div class="unit-native-choice-grid">
                  @foreach (['history' => ['Visualizar historial', 'Consultar solicitudes del modulo Area Operativa.'], 'detail' => ['Visualizar detalle', 'Abrir una solicitud y revisar su informacion.'], 'manage' => ['Administrar area operativa', 'Operar inventario, recetas, movimientos y almacenes.'], 'reports' => ['Descargar reportes', 'Exportar reportes del area operativa.']] as $permission => [$action, $copy])
                    <label>
                      <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked($loop->index < 2)>
                      <span><strong>{{ $action }}</strong><small>{{ $copy }}</small></span>
                    </label>
                  @endforeach
                </div>
              </fieldset>

              <button type="submit" data-operational-submit>Crear usuario</button><button type="button" data-operational-reset>Regresar</button>
            </form>
          </section>

          <section class="unit-native-table-card">
            <div class="unit-native-table-heading unit-native-heading-action">
              <div><h2>Usuarios de la unidad</h2><p>{{ $unit->operationalProfiles->count() }} usuarios</p></div><button type="button" data-operational-export>&darr;&nbsp; Excel</button>
            </div>
            <div class="unit-native-user-list">
              @forelse ($unit->operationalProfiles as $profile)
                <article data-operational-user-row>
                  <div>
                    <h3>{{ $profile->area?->label ?? 'Area Operativa' }} - {{ $unit->name }}</h3>
                    <p>{{ $profile->role_label ?? 'Responsable de Area' }} - {{ $profile->user?->username ?? 'sin-usuario' }}</p>
                    <p>Asignacion Area Operativa: {{ $profile->area?->label ?? 'Sin area' }}</p>
                    <p>Acciones Area Operativa: {{ collect($profile->permissions)->map(fn ($permission) => ['history' => 'Visualizar historial', 'detail' => 'Visualizar detalle', 'manage' => 'Administrar area', 'reports' => 'Descargar reportes'][$permission] ?? $permission)->implode(', ') }}</p>
                  </div>
                  <span class="unit-native-status">{{ $statusText($profile->status) }}</span>
                  <button type="button" data-edit-operational-user data-url="{{ route('unit.operational-users.update', $profile) }}" data-name="{{ $profile->user?->name }}" data-username="{{ $profile->user?->username }}" data-password="" data-role="{{ $profile->role_label }}" data-authority="{{ data_get($profile->metadata, 'authority', 'Direccion Administrativa') }}" data-service="{{ data_get($profile->metadata, 'service', 'Nutricion parenteral') }}" data-area="{{ $profile->operational_area_id }}" data-permissions='@json($profile->permissions ?? [])'>Editar</button>
                  <form method="post" action="{{ route('unit.operational-users.destroy', $profile) }}" onsubmit="return confirm('Â¿Eliminar este perfil operativo?')">@csrf @method('delete')<button type="submit">Eliminar</button></form>
                </article>
              @empty
                <p class="unit-native-empty">Sin usuarios operativos registrados.</p>
              @endforelse
            </div>
          </section>
        </div>
        <script>
          (() => {
            const form = document.querySelector('[data-operational-user-form]'); const method = form.querySelector('[data-operational-method]'); const title = document.querySelector('[data-operational-form-title]'); const submit = form.querySelector('[data-operational-submit]');
            const reset = () => { form.reset(); form.action = @json(route('unit.operational-users.store')); method.disabled = true; title.textContent = 'Alta de usuario operativo'; submit.textContent = 'Crear usuario'; form.elements.password.value = ''; };
            document.querySelectorAll('[data-edit-operational-user]').forEach((button) => button.addEventListener('click', () => { const data = button.dataset; form.action = data.url; method.disabled = false; title.textContent = 'Editar usuario operativo'; submit.textContent = 'Guardar cambios'; ['name','username','password'].forEach(key => form.elements[key].value = data[key] || ''); form.elements.role_label.value = data.role; form.elements.authority.value = data.authority; form.elements.service.value = data.service; form.querySelectorAll('[name="operational_area_id"]').forEach(input => input.checked = input.value === data.area); const permissions = JSON.parse(data.permissions || '[]'); form.querySelectorAll('[name="permissions[]"]').forEach(input => input.checked = permissions.includes(input.value)); window.scrollTo({top: 0, behavior: 'smooth'}); }));
            document.querySelector('[data-operational-reset]').addEventListener('click', reset);
            document.querySelector('[data-operational-export]').addEventListener('click', () => { const rows = [['Usuario operativo']]; document.querySelectorAll('[data-operational-user-row]').forEach(row => rows.push([row.innerText.replace(/\s+/g, ' ').trim()])); const blob = new Blob([rows.map(row => row.map(value => `"${value.replaceAll('"','""')}"`).join(',')).join('\n')], {type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='usuarios-operativos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          })();
        </script>
      @endif

      @if ($section === 'patients')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div>
              <h2>Catalogo de pacientes</h2>
              <p>{{ $patients->count() }} pacientes</p>
            </div>
            <button type="button" data-doctor-export>&darr;&nbsp; Excel</button>
          </div>
          <p class="unit-native-card-copy">Pacientes dados de alta por el area operativa de cada hospital.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table">
              <thead><tr><th>Paciente</th><th>Servicio</th><th>Medico / Area</th><th>Estatus</th><th>Actualizacion</th></tr></thead>
              <tbody>
                @forelse ($patients as $patient)
                  @php $lastAppointment = $patient->appointments->first(); @endphp
                  <tr>
                    <td><strong>{{ $patient->full_name }}</strong><span>{{ $patient->record_number ?? $patient->platform_number ?? 'Sin expediente' }}</span></td>
                    <td>{{ $lastAppointment?->specialty ?? 'Sin servicio' }}</td>
                    <td>{{ $lastAppointment?->doctor?->full_name ?? 'Sin medico' }}</td>
                    <td><span class="unit-native-status">{{ $statusText($patient->status) }}</span></td>
                    <td>{{ $patient->updated_at?->format('d/m/Y') }}</td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="unit-native-empty">Sin pacientes dados de alta por el area operativa.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif

      @if ($section === 'doctors')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div>
              <h2>Catalogo de medicos adscritos</h2>
              <p>{{ $unit->doctors->count() }} medicos adscritos</p>
            </div>
            <button type="button" data-doctor-export>Excel</button>
          </div>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-doctors-table">
              <thead><tr><th>Medico</th><th>Cedula</th><th>Especialidad</th><th>Servicio</th><th>Autorizacion</th><th>Acciones</th></tr></thead>
              <tbody>
                @forelse ($unit->doctors as $doctor)
                  <tr data-doctor-row>
                    <td><strong>{{ $doctor->full_name }}</strong><small>{{ $doctor->subspecialty ?? 'Atencion clinica' }}</small><span>Usuario plataforma: {{ $doctor->user?->username ?? data_get($doctor->metadata, 'platform_user', 'Sin usuario de plataforma') }}</span></td>
                    <td>{{ $doctor->professional_license ?? 'Sin cedula' }}</td>
                    <td>{{ $doctor->specialty ?? 'Sin especialidad' }}</td>
                    <td>{{ $doctor->service_name ?? 'Sin servicio' }}</td>
                    <td><span @class(['unit-native-status', 'is-warning' => $doctor->status === 'pending'])>{{ $statusText($doctor->status === 'active' ? 'active' : 'pending') }}</span></td>
                    <td><button type="button">Editar autorizaciones</button><form method="post" action="{{ route('unit.doctors.destroy', $doctor) }}" onsubmit="return confirm('Â¿Eliminar este medico?')">@csrf @method('delete')<button type="submit">Eliminar</button></form></td>
                  </tr>
                  <tr class="unit-doctor-authorization-row" data-doctor-authorization hidden><td colspan="6">
                    <form method="post" action="{{ route('unit.doctors.authorizations.update', $doctor) }}" class="unit-doctor-authorization-form">
                      @csrf @method('put')
                      <header><strong>Editar autorizaciones</strong><span>{{ $doctor->full_name }}</span></header>
                      <label>Estatus de autorizacion<select name="status"><option value="active" @selected($doctor->status === 'active')>Autorizado</option><option value="pending" @selected($doctor->status === 'pending')>Pendiente</option><option value="inactive" @selected($doctor->status === 'inactive')>Inactivo</option></select></label>
                      <fieldset><legend>Servicios autorizados</legend><div class="unit-native-choice-grid">
                        @php
                          $authorizedServices = data_get($doctor->metadata, 'services', [$doctor->service_name]);
                        @endphp
                        @forelse ($unit->contractedServices->pluck('service.name')->filter()->unique()->values() as $serviceName)
                          <label><input type="checkbox" name="services[]" value="{{ $serviceName }}" @checked(in_array($serviceName, $authorizedServices, true))><span><strong>{{ $serviceName }}</strong><small>Servicio habilitado para este medico.</small></span></label>
                        @empty
                          @foreach ($serviceChoices as $serviceName)<label><input type="checkbox" name="services[]" value="{{ $serviceName }}" @checked(in_array($serviceName, $authorizedServices, true))><span><strong>{{ $serviceName }}</strong><small>Servicio habilitado para este medico.</small></span></label>@endforeach
                        @endforelse
                      </div></fieldset>
                      <div class="unit-doctor-authorization-actions"><button type="submit">Guardar autorizaciones</button><button type="button" data-close-doctor-authorization>Regresar</button></div>
                    </form>
                  </td></tr>
                @empty
                  <tr><td colspan="6" class="unit-native-empty">Sin medicos adscritos registrados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="unit-native-form-card unit-native-spaced">
          <header><h2>Alta de medico adscrito</h2><p>Catalogo local del hospital</p></header>
          <form method="post" action="{{ route('unit.doctors.store') }}" class="unit-doctor-create-form">
            @csrf
            <label>Nombre<input name="first_name" required></label><label>Apellido<input name="last_name" required></label>
            <label>Usuario de la plataforma<input name="platform_user"></label><label>Cedula profesional<input name="professional_license" required></label>
            <label>Especialidad<select name="specialty" required><option value="">Selecciona una especialidad</option>@foreach ($specialties->pluck('specialty')->filter()->unique()->sort() as $specialty)<option>{{ $specialty }}</option>@endforeach</select></label>
            <label>Subespecialidad<input name="subspecialty"></label>
            <fieldset class="wide">
              <legend>Servicio adscrito</legend>
              <div class="unit-native-choice-grid">
                @forelse ($unit->contractedServices as $contract)
                  <label><input type="checkbox" name="services[]" value="{{ $contract->service?->name }}" @checked($loop->first)><span><strong>{{ $contract->service?->name }}</strong><small>Servicio habilitado para adscripcion medica.</small></span></label>
                @empty
                  @foreach ($serviceChoices as $choice)<label><input type="checkbox" name="services[]" value="{{ $choice }}" @checked($loop->first)><span><strong>{{ $choice }}</strong><small>Servicio habilitado para adscripcion medica.</small></span></label>@endforeach
                @endforelse
              </div>
            </fieldset>
            <button type="submit">Guardar medico</button>
          </form>
        </section>
        <script>
          document.querySelector('[data-doctor-export]').addEventListener('click', () => { const rows=[['Medico','Cedula','Especialidad','Servicio','Autorizacion']]; document.querySelectorAll('[data-doctor-row]').forEach(row => rows.push([...row.cells].slice(0,5).map(cell => cell.innerText.trim()))); const blob=new Blob([rows.map(row=>row.map(value=>`"${value.replaceAll('"','""')}"`).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='catalogo-medicos.csv'; link.click(); URL.revokeObjectURL(link.href); });
          document.querySelectorAll('[data-doctor-row]').forEach(row => { const panel = row.nextElementSibling; const editButton = row.querySelector('td:last-child > button'); editButton?.addEventListener('click', () => { document.querySelectorAll('[data-doctor-authorization]').forEach(item => item.hidden = item !== panel); panel.hidden = false; }); panel?.querySelector('[data-close-doctor-authorization]')?.addEventListener('click', () => panel.hidden = true); });
        </script>
      @endif

      @if ($section === 'specialties')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading">
            <h2>Catalogo de especialidades</h2>
            <p>{{ $specialties->count() }} especialidades habilitadas</p>
          </div>
          <p class="unit-native-card-copy">Especialidades dadas de alta por la institucion padre de esta unidad.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table">
              <thead><tr><th>No.</th><th>Especialidad</th><th>Estatus</th></tr></thead>
              <tbody>
                @forelse ($specialties as $service)
                  <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $service->specialty ?? $service->name }}</strong><span>{{ $unit->institution?->name ?? 'Institucion' }} - Catalogo institucional</span></td>
                    <td><span class="unit-native-status">{{ $statusText($service->status) }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="3" class="unit-native-empty">Sin especialidades habilitadas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @endif

      @if ($section === 'procedure-areas')
        @php
          $procedureCatalogs = [
            'consulting' => ['title' => 'Catalogo de consultorios', 'create' => 'Nuevo consultorio', 'singular' => 'consultorio', 'button' => 'Consultorios', 'empty' => 'Sin consultorios registrados.'],
            'infusion' => ['title' => 'Catalogo de salas de infusion', 'create' => 'Nueva sala de infusion', 'singular' => 'sala de infusion', 'button' => 'Salas de infusion', 'empty' => 'Sin salas de infusion registradas.'],
            'operating' => ['title' => 'Catalogo de quirofanos', 'create' => 'Nuevo quirofano', 'singular' => 'quirofano', 'button' => 'Quirofanos', 'empty' => 'Sin quirofanos registrados.'],
            'uci' => ['title' => 'Catalogo de UCI', 'create' => 'Nueva UCI', 'singular' => 'UCI', 'button' => 'UCI', 'empty' => 'Sin UCI registradas.'],
            'uti' => ['title' => 'Catalogo de UTI', 'create' => 'Nueva UTI', 'singular' => 'UTI', 'button' => 'UTI', 'empty' => 'Sin UTI registradas.'],
            'recovery' => ['title' => 'Catalogo de salas de recuperacion', 'create' => 'Nueva sala de recuperacion', 'singular' => 'sala de recuperacion', 'button' => 'Sala de recuperacion', 'empty' => 'Sin salas de recuperacion registradas.'],
          ];
          $procedureAreas = collect(data_get($unit->metadata, 'procedure_areas', []));
          $requestedProcedureCatalog = request('create') ?: request('catalog');
          $editingProcedureArea = request('edit') ? $procedureAreas->firstWhere('id', request('edit')) : null;
          $editingProcedureCatalog = data_get($editingProcedureArea, 'type');
          $activeProcedureCatalog = ($requestedProcedureCatalog && array_key_exists($requestedProcedureCatalog, $procedureCatalogs))
              ? $requestedProcedureCatalog
              : (($editingProcedureCatalog && array_key_exists($editingProcedureCatalog, $procedureCatalogs)) ? $editingProcedureCatalog : array_key_first($procedureCatalogs));
          $scheduleDays = ['monday' => 'Lunes', 'tuesday' => 'Martes', 'wednesday' => 'Miercoles', 'thursday' => 'Jueves', 'friday' => 'Viernes', 'saturday' => 'Sabado', 'sunday' => 'Domingo'];
        @endphp
        <section class="unit-service-carousel-card unit-procedure-carousel-card">
          <button type="button" class="unit-service-carousel-arrow" data-procedure-carousel-prev aria-label="Categoria anterior">&lsaquo;</button>
          <div class="unit-service-carousel unit-procedure-carousel" data-procedure-carousel aria-label="Categorias de areas de procedimiento">
            @foreach ($procedureCatalogs as $catalogKey => $catalog)
              <button type="button"
                      @class(['unit-service-carousel-button', 'unit-procedure-carousel-button', 'is-active' => $catalogKey === $activeProcedureCatalog])
                      data-procedure-category="{{ $catalogKey }}">
                <span class="unit-service-carousel-icon" aria-hidden="true">
                  @switch($catalogKey)
                    @case('consulting')
                      <svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg>
                      @break
                    @case('infusion')
                      <svg viewBox="0 0 24 24"><path d="M9 2h6v9a3 3 0 0 1-6 0V2Z"/><path d="M12 14v8"/><path d="M8 22h8"/><path d="M9 6h6"/></svg>
                      @break
                    @case('operating')
                      <svg viewBox="0 0 24 24"><path d="M14.5 4.5 19 9"/><path d="m5 19 8.5-8.5"/><path d="m12 7 5 5"/><path d="M4 20h6"/></svg>
                      @break
                    @case('uci')
                      <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/><path d="M3 12h4l2-4 4 8 2-4h6"/></svg>
                      @break
                    @case('uti')
                      <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="12" rx="2"/><path d="M7 12h3l1.5-3 3 6 1.5-3h1"/><path d="M12 17v4"/><path d="M8 21h8"/></svg>
                      @break
                    @default
                      <svg viewBox="0 0 24 24"><path d="M3 11h18v8"/><path d="M3 7v12"/><path d="M7 11V7h5a3 3 0 0 1 3 3v1"/><path d="M7 15h14"/></svg>
                  @endswitch
                </span>
                <strong>{{ $catalog['button'] }}</strong>
              </button>
            @endforeach
          </div>
          <button type="button" class="unit-service-carousel-arrow" data-procedure-carousel-next aria-label="Categoria siguiente">&rsaquo;</button>
        </section>
        <div class="unit-procedure-catalogs">
          @foreach ($procedureCatalogs as $catalogKey => $catalog)
            @php($catalogAreas = $procedureAreas->where('type', $catalogKey)->values())
            @php($editingArea = request('edit') ? $catalogAreas->firstWhere('id', request('edit')) : null)
            <section class="unit-native-table-card unit-procedure-card" data-procedure-catalog="{{ $catalogKey }}" @if ($catalogKey !== $activeProcedureCatalog) hidden @endif>
              <div class="unit-native-table-heading unit-native-heading-action">
                <div><h2>{{ $catalog['title'] }}</h2><p>{{ $catalogAreas->count() }} subunidades</p></div>
                <div class="unit-procedure-heading-actions"><button type="button" data-procedure-export>Descargar catalogo</button><a class="is-primary" href="{{ route('unit.dashboard', ['section' => 'procedure-areas', 'create' => $catalogKey]) }}">{{ $catalog['create'] }}</a></div>
              </div>
              <div class="unit-native-table-scroll">
                <table class="unit-native-table unit-procedure-table">
                  <thead><tr><th>Ubicacion</th><th>Piso</th><th>Numero de unidad</th><th>Capacidad simultanea</th><th>Responsable de area</th><th>Horario de atencion</th><th>Acciones</th></tr></thead>
                  <tbody>
                    @forelse ($catalogAreas as $area)
                      @php($activeSchedule = collect($area['schedule'] ?? [])->filter(fn ($day) => data_get($day, 'enabled'))->map(fn ($day, $key) => ($scheduleDays[$key] ?? ucfirst($key)).' '.data_get($day, 'start', '08:00').' - '.data_get($day, 'end', '16:00'))->implode(', '))
                      <tr><td>{{ $area['location'] }}</td><td>{{ $area['floor'] }}</td><td><strong>{{ $area['unit_number'] }}</strong><small>{{ $catalog['button'] }}</small></td><td>{{ $area['capacity'] }} {{ $area['capacity'] == 1 ? 'paciente simultaneo' : 'pacientes simultaneos' }}</td><td>{{ $area['responsible'] }}</td><td>{{ $activeSchedule ?: 'Sin horario' }}</td><td><a class="unit-native-button" href="{{ route('unit.dashboard', ['section' => 'procedure-areas', 'edit' => $area['id']]) }}">Editar</a></td></tr>
                    @empty
                      <tr><td colspan="7" class="unit-native-empty">{{ $catalog['empty'] }}</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              @if (request('create') === $catalogKey || $editingArea)
                <form method="post" action="{{ $editingArea ? route('unit.procedure-areas.update', $editingArea['id']) : route('unit.procedure-areas.store') }}" class="unit-procedure-form">
                  @csrf @if($editingArea) @method('put') @endif<input type="hidden" name="type" value="{{ $catalogKey }}">
                  <header><strong>{{ $editingArea ? 'Editar '.$catalog['singular'] : 'Nueva subunidad' }}</strong><span>{{ ucfirst($catalog['singular']) }}</span></header>
                  <label>Ubicacion<input name="location" value="{{ $editingArea['location'] ?? '' }}" required></label><label>Piso<input name="floor" value="{{ $editingArea['floor'] ?? '' }}" required></label>
                  <label>Numero de unidad<input name="unit_number" value="{{ $editingArea['unit_number'] ?? '' }}" required></label><label>Capacidad simultanea de pacientes<input type="number" min="1" name="capacity" value="{{ $editingArea['capacity'] ?? '' }}" required></label>
                  <label class="wide">Responsable de area<select name="responsible"><option value="" @selected(($editingArea['responsible'] ?? '') === 'Sin responsable')>Sin responsable asignado</option>@foreach ($unit->operationalProfiles as $profile)<option value="{{ $profile->user?->name }}" @selected(($editingArea['responsible'] ?? '') === $profile->user?->name)>{{ $profile->user?->name }}</option>@endforeach</select></label>
                  <fieldset class="wide"><legend>Horario de atencion de la unidad</legend><div class="unit-procedure-schedule">
                    @foreach ($scheduleDays as $dayKey => $dayLabel) @php($dayEnabled = $editingArea ? data_get($editingArea, "schedule.$dayKey.enabled", false) : $loop->iteration <= 5)<div @class(['is-enabled' => $dayEnabled])><label><input type="checkbox" name="schedule[{{ $dayKey }}][enabled]" value="1" @checked($dayEnabled)>{{ $dayLabel }}</label><span>+</span><label>Hora inicio<input type="time" name="schedule[{{ $dayKey }}][start]" value="{{ data_get($editingArea, "schedule.$dayKey.start", '08:00') }}"></label><label>Hora fin<input type="time" name="schedule[{{ $dayKey }}][end]" value="{{ data_get($editingArea, "schedule.$dayKey.end", '16:00') }}"></label></div>@endforeach
                  </div></fieldset>
                  <div class="unit-procedure-form-actions wide"><button type="submit">{{ $editingArea ? 'Guardar cambios' : 'Guardar subunidad' }}</button><a href="{{ route('unit.dashboard', ['section' => 'procedure-areas', 'catalog' => $catalogKey]) }}">Regresar</a></div>
                </form>
              @endif
            </section>
          @endforeach
        </div>
        <script>
          (() => {
            const carousel = document.querySelector('[data-procedure-carousel]');
            const categoryButtons = [...document.querySelectorAll('[data-procedure-category]')];
            const catalogCards = [...document.querySelectorAll('[data-procedure-catalog]')];
            const setActiveCatalog = (key) => {
              categoryButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.procedureCategory === key));
              catalogCards.forEach((card) => { card.hidden = card.dataset.procedureCatalog !== key; });
            };

            categoryButtons.forEach((button) => button.addEventListener('click', () => setActiveCatalog(button.dataset.procedureCategory)));
            document.querySelector('[data-procedure-carousel-prev]')?.addEventListener('click', () => carousel?.scrollBy({ left: -260, behavior: 'smooth' }));
            document.querySelector('[data-procedure-carousel-next]')?.addEventListener('click', () => carousel?.scrollBy({ left: 260, behavior: 'smooth' }));

            document.querySelectorAll('[data-procedure-catalog]').forEach((card) => {
              card.querySelector('[data-procedure-export]')?.addEventListener('click', () => {
                const headers = [...card.querySelectorAll('th')].map((cell) => cell.innerText.trim());
                const link = document.createElement('a');
                link.href = URL.createObjectURL(new Blob([headers.join(',') + '\n'], { type: 'text/csv;charset=utf-8' }));
                link.download = `${card.dataset.procedureCatalog}.csv`;
                link.click();
                URL.revokeObjectURL(link.href);
              });
            });
          })();
        </script>
      @endif

      @if ($section === 'external-pharmacy')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div><h2>Catalogo de farmacia externa</h2>
            <p><span data-pharmacy-visible>{{ $externalPharmacyCatalog->count() }}</span> visibles de {{ $externalPharmacyCatalog->count() }} medicamentos institucionales - {{ $externalPharmacyCatalog->where('status', 'active')->count() }} activos / {{ $externalPharmacyCatalog->where('status', 'inactive')->count() }} inactivos</p></div>
            <button type="button" data-pharmacy-export>â†“&nbsp; Excel</button>
          </div>
          <p class="unit-native-card-copy">Medicamentos del catalogo institucional disponibles para consulta de la unidad.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-external-pharmacy-table" data-pharmacy-table>
              <thead>
                <tr><th>CNIS</th><th>Insumo</th><th>Grupo</th><th>Descripcion</th><th>Cobertura</th><th>Estatus</th></tr>
                <tr class="unit-native-filter-row"><th><input placeholder="Filtrar CNIS"></th><th><input placeholder="Filtrar insumo"></th><th><input placeholder="Filtrar grupo"></th><th><input placeholder="Filtrar descripcion"></th><th></th><th></th></tr>
              </thead>
              <tbody>
                @forelse ($externalPharmacyCatalog as $item)
                  <tr data-pharmacy-row>
                    <td>{{ $item->cnis ?? 'Sin CNIS' }}</td>
                    <td><strong>{{ $item->name }}</strong><small>Catalogo de farmacia externa</small></td>
                    <td>{{ data_get($item->metadata, 'group', 'Sin grupo') }}</td>
                    <td>{{ $item->presentation ?? $item->generic_name ?? 'Sin descripcion' }}</td>
                    <td>{{ data_get($item->metadata, 'coverage', 'Unidades moviles, Nucleos basicos, CESSA') }}</td>
                    <td><span @class(['unit-native-status', 'is-warning' => $item->status === 'inactive'])>{{ $statusText($item->status) }}</span><form method="post" action="{{ route('unit.external-pharmacy.status', $item) }}">@csrf @method('patch')<input type="hidden" name="status" value="{{ $item->status === 'active' ? 'inactive' : 'active' }}"><button type="submit">{{ $item->status === 'active' ? 'Desactivar' : 'Activar' }}</button></form></td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="unit-native-empty">Sin medicamentos institucionales para esta unidad.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
        <script>
          (() => { const table=document.querySelector('[data-pharmacy-table]'); const filters=[...table.querySelectorAll('.unit-native-filter-row input')]; const rows=[...table.querySelectorAll('[data-pharmacy-row]')]; const visible=document.querySelector('[data-pharmacy-visible]'); const apply=()=>{ let count=0; rows.forEach(row=>{ const cells=[...row.cells]; const show=filters.every((input,index)=>cells[index].innerText.toLowerCase().includes(input.value.trim().toLowerCase())); row.hidden=!show; if(show) count++; }); visible.textContent=count; }; filters.forEach(input=>input.addEventListener('input',apply)); document.querySelector('[data-pharmacy-export]').addEventListener('click',()=>{ const data=[['CNIS','Insumo','Grupo','Descripcion','Cobertura','Estatus'],...rows.filter(row=>!row.hidden).map(row=>[...row.cells].map(cell=>cell.innerText.trim()))]; const blob=new Blob([data.map(row=>row.map(value=>`"${value.replaceAll('"','""')}"`).join(',')).join('\n')],{type:'text/csv;charset=utf-8'}); const link=document.createElement('a'); link.href=URL.createObjectURL(blob); link.download='catalogo-farmacia-externa.csv'; link.click(); URL.revokeObjectURL(link.href); }); })();
        </script>
      @endif

      @if ($section === 'medications')
        <section class="unit-native-table-card">
          <div class="unit-native-table-heading unit-native-heading-action">
            <div>
              <h2>Catalogo de medicamentos</h2>
              <p><span data-medication-visible>{{ $medicationCatalog->count() }}</span> visibles de {{ $medicationCatalog->count() }} medicamentos - {{ $medicationCatalog->where('status', 'active')->count() }} activos</p>
            </div>
            <button type="button" data-medication-export>Descargar catalogo</button>
          </div>
          <p class="unit-native-card-copy">Medicamentos institucionales y universales disponibles para consulta de la unidad.</p>
          <div class="unit-native-table-scroll">
            <table class="unit-native-table unit-medication-catalog-table" data-medication-table>
              <thead>
                <tr><th>Clave CNIS</th><th>Medicamento</th><th>Nombre generico</th><th>Grupo terapeutico</th><th>Presentacion</th><th>Requisitos</th><th>Estatus</th></tr>
                <tr class="unit-native-filter-row"><th><input placeholder="Filtrar clave"></th><th><input placeholder="Filtrar medicamento"></th><th><input placeholder="Filtrar nombre generico"></th><th><input placeholder="Filtrar grupo"></th><th><input placeholder="Filtrar presentacion"></th><th></th><th></th></tr>
              </thead>
              <tbody>
                @forelse ($medicationCatalog as $item)
                  <tr data-medication-row>
                    <td>{{ $item->cnis ?? 'Sin clave' }}</td>
                    <td><strong>{{ $item->name }}</strong><small>{{ $item->description ?? 'Sin descripcion' }}</small></td>
                    <td>{{ $item->generic_name ?? $item->name }}</td>
                    <td>{{ $item->therapeutic_group ?? data_get($item->metadata, 'group', 'Sin grupo') }}</td>
                    <td>{{ $item->presentation ?? 'Sin presentacion' }}</td>
                    <td>
                      @if ($item->controlled)<span class="unit-native-status is-warning">Controlado</span>@endif
                      @if ($item->requires_prescription)<small>Requiere receta</small>@elseif (! $item->controlled)<small>Venta libre</small>@endif
                      @if ($item->cold_chain)<small>Cadena fria</small>@endif
                    </td>
                    <td><span @class(['unit-native-status', 'is-warning' => $item->status === 'inactive'])>{{ $statusText($item->status) }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="7" class="unit-native-empty">No hay medicamentos disponibles para esta unidad.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
        <script>
          (() => {
            const table = document.querySelector('[data-medication-table]');
            if (!table) return;
            const filters = [...table.querySelectorAll('.unit-native-filter-row input')];
            const rows = [...table.querySelectorAll('[data-medication-row]')];
            const visible = document.querySelector('[data-medication-visible]');
            const apply = () => {
              let count = 0;
              rows.forEach((row) => {
                const cells = [...row.cells];
                const show = filters.every((input, index) => cells[index].innerText.toLowerCase().includes(input.value.trim().toLowerCase()));
                row.hidden = !show;
                if (show) count++;
              });
              visible.textContent = count;
            };
            filters.forEach((input) => input.addEventListener('input', apply));
            document.querySelector('[data-medication-export]')?.addEventListener('click', () => {
              const data = [['Clave CNIS', 'Medicamento', 'Nombre generico', 'Grupo terapeutico', 'Presentacion', 'Requisitos', 'Estatus'], ...rows.filter((row) => !row.hidden).map((row) => [...row.cells].map((cell) => cell.innerText.trim()))];
              const blob = new Blob([data.map((row) => row.map((value) => `"${value.replaceAll('"', '""')}"`).join(',')).join('\n')], { type: 'text/csv;charset=utf-8' });
              const link = document.createElement('a');
              link.href = URL.createObjectURL(blob);
              link.download = 'catalogo-de-medicamentos.csv';
              link.click();
              URL.revokeObjectURL(link.href);
            });
          })();
        </script>
      @endif
    </section>
  </div>
@endsection
