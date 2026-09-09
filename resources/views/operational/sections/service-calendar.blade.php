@php
  $calendarMode = $calendarMode ?? 'oncology';
  $isHospitalizationCalendar = $calendarMode === 'hospitalization';
  $calendarRequests = $calendarProviderRequests ?? $providerRequests;

  if ($isHospitalizationCalendar) {
    $hospitalizationTrackTypes = match ($hospitalizationTrack ?? 'all') {
      'nutrition' => ['npt', 'nutrition'],
      'imports' => ['import'],
      default => ['npt', 'nutrition', 'import'],
    };
    $calendarRequests = $calendarRequests
      ->whereIn('request_type', $hospitalizationTrackTypes)
      ->values();
    $calendarEvents = $calendarRequests
      ->map(function ($item) {
        $payload = $item->payload ?? [];
        $schedule = data_get($payload, 'hospitalization_schedule', []);
        $date = data_get($schedule, 'application_date')
          ?: $item->required_at?->format('Y-m-d')
          ?: $item->requested_at?->format('Y-m-d');

        if (! $date) {
          return null;
        }

        $startsAt = data_get($schedule, 'starts_at')
          ?: $item->required_at?->format('H:i')
          ?: $item->requested_at?->format('H:i');
        $hour = is_string($startsAt) && preg_match('/^(\d{2}):/', $startsAt, $matches)
          ? (int) $matches[1]
          : null;
        $shift = match (true) {
          $hour === null => 'Sin turno',
          $hour < 13 => 'Matutino',
          $hour < 20 => 'Vespertino',
          default => 'Nocturno',
        };
        $status = match (true) {
          in_array($item->status, ['cancelled', 'rejected'], true) => 'cancelled',
          $item->status === 'delivered' => 'completed',
          in_array($item->status, ['accepted', 'preparing', 'in_route'], true) || filled($schedule) => 'scheduled',
          default => 'pending',
        };
        $statusLabel = match ($status) {
          'cancelled' => 'Cancelado',
          'completed' => 'Completado',
          'scheduled' => 'Programado',
          default => 'Pendiente',
        };
        $service = data_get($payload, 'service') ?: match ($item->request_type) {
          'npt' => "Nutrici\u{00F3}n parenteral",
          'nutrition' => "Nutrici\u{00F3}n cl\u{00ED}nica",
          'import' => 'Medicamento especial',
          default => "Atenci\u{00F3}n hospitalaria",
        };
        $room = data_get($schedule, 'room')
          ?: data_get($schedule, 'bed')
          ?: data_get($payload, 'room')
          ?: data_get($payload, 'bed')
          ?: data_get($payload, 'ward')
          ?: "\u{00C1}rea por asignar";
        $folio = $item->external_id ?? 'OP-HOSP-'.str_pad((string) $item->id, 4, '0', STR_PAD_LEFT);
        $requestType = match ($item->request_type) {
          'npt' => "Mezcla de nutrici\u{00F3}n parenteral",
          'nutrition' => 'Mezcla nutricional',
          'import' => 'Medicamento especial',
          default => 'Mezcla hospitalaria',
        };
        $patientSex = match ($item->patient?->sex) {
          'female' => 'Femenino',
          'male' => 'Masculino',
          'other' => 'Otro',
          default => 'Sin dato',
        };
        $rawMedications = collect(
          data_get($payload, 'mixture_medications')
          ?: data_get($payload, 'prescription_items')
          ?: data_get($payload, 'medications')
          ?: []
        )->filter(fn ($medication) => is_array($medication))->values();
        $volume = data_get($payload, 'volume');
        $formattedVolume = filled($volume)
          ? (str_contains(strtolower((string) $volume), 'ml') ? (string) $volume : $volume.' ml')
          : 'Por definir';
        $medications = $rawMedications->map(fn ($medication) => [
          'medication' => data_get($medication, 'medication_name', data_get($medication, 'name', data_get($medication, 'product_name', $service))),
          'dose' => data_get($medication, 'dose', 'Por definir'),
          'diluent' => data_get($medication, 'diluent', $item->request_type === 'import' ? 'No aplica' : 'Solucion base'),
          'finalVolume' => data_get($medication, 'final_volume', data_get($medication, 'volume', $formattedVolume)),
          'route' => data_get($medication, 'route', data_get($payload, 'route', 'IV')),
          'duration' => data_get($medication, 'infusion_duration', data_get($medication, 'duration', data_get($payload, 'duration', 'Por definir'))),
          'time' => data_get($medication, 'hour', $startsAt ?: 'Sin horario'),
        ])->values();

        if ($medications->isEmpty()) {
          $medications = collect([[
            'medication' => data_get($payload, 'medication', $service),
            'dose' => data_get($payload, 'dose', 'Por definir'),
            'diluent' => data_get($payload, 'diluent', $item->request_type === 'import' ? 'No aplica' : 'Solucion base'),
            'finalVolume' => $formattedVolume,
            'route' => data_get($payload, 'route', 'IV'),
            'duration' => data_get($payload, 'duration', 'Por definir'),
            'time' => $startsAt ?: 'Sin horario',
          ]]);
        }

        $patientAge = $item->patient?->birth_date?->age ?? data_get($item->patient?->metadata, 'age');
        $patientWeight = data_get($item->patient?->metadata, 'weight');
        $patientHeight = data_get($item->patient?->metadata, 'height');
        $patientSurface = data_get($item->patient?->metadata, 'body_surface_area');

        return [
          'key' => 'hospitalization-'.$item->id,
          'id' => $item->id,
          'requestId' => $item->id,
          'date' => $date,
          'folio' => $folio,
          'service' => $service,
          'patient' => $item->patient?->full_name ?? 'Paciente sin nombre',
          'curp' => $item->patient?->curp,
          'doctor' => data_get($payload, 'doctor', 'Medico por asignar'),
          'room' => $room,
          'time' => $startsAt ?: 'Sin horario',
          'shift' => $shift,
          'status' => $status,
          'statusLabel' => $statusLabel,
          'detailLabel' => 'Ver solicitud',
          'mixture' => [
            'title' => 'Solicitud de mezcla hospitalaria',
            'folio' => $folio,
            'type' => $requestType,
            'status' => $statusLabel,
            'selectedMedicationIndex' => 0,
            'patient' => [
              'name' => $item->patient?->full_name ?? 'Paciente sin nombre',
              'age' => filled($patientAge) ? $patientAge." a\u{00F1}os" : 'Sin dato',
              'sex' => $patientSex,
              'diagnosis' => data_get($payload, 'diagnosis', "Sin diagn\u{00F3}stico registrado"),
              'weight' => filled($patientWeight) ? $patientWeight.' kg' : 'Sin dato',
              'height' => filled($patientHeight) ? $patientHeight.' m' : 'Sin dato',
              'bodySurface' => filled($patientSurface) ? $patientSurface.' m2' : 'Sin dato',
            ],
            'clinical' => [
              'doctor' => data_get($payload, 'doctor', "M\u{00E9}dico por asignar"),
              'requestingService' => data_get($payload, 'requesting_service', $service),
              'room' => $room,
              'shift' => $shift,
              'priority' => data_get($payload, 'priority', 'Normal'),
              'date' => $date,
            ],
            'observations' => data_get($payload, 'notes', 'Sin observaciones registradas.'),
            'medications' => $medications->all(),
          ],
        ];
      })
      ->filter()
      ->values();
  } else {
    $calendarEvents = $calendarRequests
    ->flatMap(function ($item) use ($contextUnit, $oncologyTrack) {
      $payload = $item->payload ?? [];
      $assignment = data_get($item->payload, 'infusion_assignment', []);
      $baseDate = data_get($assignment, 'application_date') ?: $item->required_at?->format('Y-m-d');

      if (! $baseDate) {
        return [];
      }

      $referenceMedications = collect([
        ['medication_name' => 'Paclitaxel', 'dose' => '300 mg', 'diluent' => 'Sol. salina', 'final_volume' => '500 ml', 'route' => 'IV', 'duration' => '3 h', 'hour' => '08:00'],
        ['medication_name' => "Ondansetr\u{00F3}n", 'dose' => '8 mg', 'diluent' => 'Sol. salina', 'final_volume' => '50 ml', 'route' => 'IV', 'duration' => '15 min', 'hour' => '07:30'],
        ['medication_name' => 'Dexametasona', 'dose' => '8 mg', 'diluent' => 'Sol. salina', 'final_volume' => '50 ml', 'route' => 'IV', 'duration' => '15 min', 'hour' => '07:40'],
      ]);
      $rawMedications = collect(data_get($payload, 'mixture_medications') ?: data_get($payload, 'prescription_items') ?: [])
        ->filter(fn ($medication) => is_array($medication))
        ->values();

      if ($rawMedications->isEmpty()) {
        $rawMedications = $referenceMedications;
      }

      $mixtureSchedule = data_get($payload, 'mixture_schedule', []);
      $baseStart = data_get($assignment, 'starts_at') ?: $item->required_at?->format('H:i');
      $medications = $rawMedications->map(function ($medication, $index) use ($referenceMedications, $mixtureSchedule, $baseDate, $baseStart, $payload) {
        $reference = $referenceMedications->get($index, $referenceMedications->first());
        $schedule = data_get($mixtureSchedule, (string) $index, []);

        return [
          'medication' => data_get($medication, 'medication_name', data_get($reference, 'medication_name', 'Medicamento '.($index + 1))),
          'dose' => data_get($medication, 'dose', data_get($reference, 'dose', 'Por definir')),
          'diluent' => data_get($medication, 'diluent', data_get($reference, 'diluent', 'Sol. salina')),
          'finalVolume' => data_get($medication, 'final_volume', data_get($medication, 'volume', data_get($reference, 'final_volume', data_get($payload, 'volume', 'Por definir')))),
          'route' => data_get($medication, 'route', data_get($reference, 'route', 'IV')),
          'duration' => data_get($medication, 'infusion_duration', data_get($medication, 'duration', data_get($reference, 'duration', 'Por definir'))),
          'time' => data_get($schedule, 'starts_at', data_get($medication, 'hour', data_get($reference, 'hour', $baseStart ?: '08:00'))),
          'date' => data_get($schedule, 'application_date', $baseDate),
        ];
      })->values();

      $isMixtureCalendar = ($oncologyTrack ?? 'infusions') === 'mixes';
      $eventMedicationIndexes = $isMixtureCalendar ? $medications->keys() : collect([0]);

      return $eventMedicationIndexes->map(function ($medicationIndex) use ($item, $payload, $assignment, $baseDate, $baseStart, $medications, $mixtureSchedule, $isMixtureCalendar, $contextUnit, $oncologyTrack) {
        $selectedMedication = $medications->get($medicationIndex, $medications->first());
        $date = $isMixtureCalendar ? data_get($selectedMedication, 'date', $baseDate) : $baseDate;
        $hasIndividualSchedule = filled(data_get($mixtureSchedule, $medicationIndex.'.application_date'));
        $status = match (true) {
          in_array($item->status, ['cancelled', 'rejected'], true) => 'cancelled',
          $item->status === 'delivered' => 'completed',
          filled($assignment) || $hasIndividualSchedule => 'scheduled',
          default => 'pending',
        };
        $statusLabel = match ($status) {
          'cancelled' => 'Cancelado',
          'completed' => 'Completado',
          'scheduled' => 'Programado',
          default => 'Pendiente',
        };
        $startsAt = $isMixtureCalendar ? data_get($selectedMedication, 'time', $baseStart) : $baseStart;
        $hour = is_string($startsAt) && preg_match('/^(\d{2}):/', $startsAt, $matches)
          ? (int) $matches[1]
          : null;
        $shift = match (true) {
          $hour === null => 'Sin turno',
          $hour < 13 => 'Matutino',
          $hour < 20 => 'Vespertino',
          default => 'Nocturno',
        };
        $patientSex = match ($item->patient?->sex) {
          'female' => 'Femenino',
          'male' => 'Masculino',
          'other' => 'Otro',
          default => 'Femenino',
        };
        $patientWeight = data_get($item->patient?->metadata, 'weight');
        $patientHeight = data_get($item->patient?->metadata, 'height');
        $patientSurface = data_get($item->patient?->metadata, 'body_surface_area');
        $requestFolio = $item->external_id ?? 'QT-'.$item->id;
        $mixtureFolio = $isMixtureCalendar ? $requestFolio.'-M'.str_pad((string) ($medicationIndex + 1), 2, '0', STR_PAD_LEFT) : $requestFolio;
        $room = data_get($assignment, 'room_number', data_get($payload, 'room', 'Sala 1'));
        $doctor = data_get($payload, 'doctor', "Dr. Carlos M\u{00E9}ndez");
        $patientName = $item->patient?->full_name ?? "Mar\u{00ED}a Gonz\u{00E1}lez L\u{00F3}pez";

        return [
          'key' => $item->id.'-'.$medicationIndex,
          'id' => $item->id,
          'requestId' => $item->id,
          'medicationIndex' => $medicationIndex,
          'date' => $date,
          'folio' => $mixtureFolio,
          'requestFolio' => $requestFolio,
          'service' => data_get($payload, 'service', 'Quimioterapia'),
          'medication' => data_get($selectedMedication, 'medication'),
          'patient' => $patientName,
          'curp' => $item->patient?->curp,
          'doctor' => $doctor,
          'room' => $room,
          'time' => $startsAt ?: 'Sin horario',
          'endsAt' => data_get($assignment, 'ends_at'),
          'shift' => $shift,
          'status' => $status,
          'statusLabel' => $statusLabel,
          'actionLabel' => filled($assignment) ? 'Reprogramar' : 'Agendar sala',
          'actionUrl' => route('operational.dashboard', [
            'area' => 'oncology',
            'section' => 'calendar',
            'oncology_track' => $oncologyTrack ?? 'infusions',
            'unit' => $contextUnit?->id,
            'schedule_request' => $item->id,
          ]),
          'scheduleUpdateUrl' => route('operational.mixture-schedules.update', $item),
          'mixture' => [
            'folio' => $mixtureFolio,
            'type' => "Mezcla oncol\u{00F3}gica",
            'status' => $statusLabel,
            'selectedMedicationIndex' => $medicationIndex,
            'patient' => [
              'name' => $patientName,
              'age' => ($item->patient?->birth_date?->age ?? data_get($item->patient?->metadata, 'age', 52))." a\u{00F1}os",
              'sex' => $patientSex,
              'diagnosis' => data_get($payload, 'diagnosis', "C\u{00E1}ncer de mama"),
              'weight' => filled($patientWeight) ? $patientWeight.' kg' : '68 kg',
              'height' => filled($patientHeight) ? $patientHeight.' m' : '1.62 m',
              'bodySurface' => filled($patientSurface) ? $patientSurface." m\u{00B2}" : "1.72 m\u{00B2}",
            ],
            'clinical' => [
              'doctor' => $doctor,
              'requestingService' => data_get($payload, 'requesting_service', "Centro Oncol\u{00F3}gico"),
              'room' => $room,
              'shift' => $shift,
              'priority' => data_get($payload, 'priority', 'Normal'),
              'date' => $date,
            ],
            'observations' => data_get($payload, 'notes', "Programar y validar mezcla de acuerdo con protocolo oncol\u{00F3}gico."),
            'medications' => $medications->all(),
          ],
        ];
      });
    })
    ->filter()
    ->values();
  }

  $requestedMonth = request('calendar_month');
  $firstEventDate = data_get($calendarEvents->first(), 'date');
  $requestedMonthParts = explode('-', (string) $requestedMonth);
  $requestedMonthIsValid = preg_match('/^\d{4}-\d{2}$/', (string) $requestedMonth)
    && checkdate((int) ($requestedMonthParts[1] ?? 0), 1, (int) ($requestedMonthParts[0] ?? 0));
  $calendarInitialDate = $requestedMonthIsValid
    ? \Illuminate\Support\Carbon::createFromFormat('Y-m', $requestedMonth)->startOfMonth()
    : ($firstEventDate ? \Illuminate\Support\Carbon::parse($firstEventDate)->startOfMonth() : now()->startOfMonth());
  $calendarServices = $calendarEvents->pluck('service')->filter()->unique()->sort()->values();
  $calendarDoctors = $calendarEvents->pluck('doctor')->filter()->unique()->sort()->values();
  $calendarRooms = ($isHospitalizationCalendar ? collect() : $infusionRooms->pluck('unit_number'))
    ->merge($calendarEvents->pluck('room')->reject(fn ($room) => $room === 'Sin sala'))
    ->filter()
    ->unique()
    ->sort()
    ->values();
  $calendarRoomLock = $calendarRoomLock ?? null;
  if (filled($calendarRoomLock)) {
    $calendarRooms = collect([$calendarRoomLock]);
  }
  $calendarYears = collect(range(now()->year - 3, now()->year + 3))
    ->merge($calendarEvents->pluck('date')->map(fn ($date) => (int) substr($date, 0, 4)))
    ->push($calendarInitialDate->year)
    ->unique()
    ->sort()
    ->values();
  $selectedCalendarRequest = $isHospitalizationCalendar
    ? null
    : $calendarRequests->firstWhere('id', (int) request('schedule_request'));
  $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 0 => 'Domingo'];
@endphp

<section
  class="operational-native-table-card operational-section-card operational-service-calendar"
  data-operational-service-calendar
  data-initial-date="{{ $calendarInitialDate->format('Y-m-d') }}"
  data-calendar-mode="{{ $calendarMode }}"
  data-oncology-track="{{ $oncologyTrack ?? 'infusions' }}"
>
  <div class="operational-native-table-heading operational-service-calendar-heading">
    <div>
      <p class="eyebrow">PROGRAMACI&Oacute;N</p>
      @if (filled($calendarTitle ?? null))
        <h2>{{ $calendarTitle }}</h2>
        <span>{{ $calendarSubtitle ?? 'Agenda de la sala de infusion seleccionada' }}</span>
      @endif
    </div>
    @if (filled($calendarReturnUrl ?? null))
      <a class="operational-secondary-button" href="{{ $calendarReturnUrl }}">Volver a salas</a>
    @elseif (! $isHospitalizationCalendar)
      <a class="operational-primary-button" href="{{ route('operational.dashboard', [
        'area' => 'oncology',
        'section' => 'calendar',
        'oncology_track' => 'infusions',
        'unit' => $contextUnit?->id,
        'calendar_month' => request('calendar_month'),
        'new_infusion' => 1,
      ]) }}">+ Nueva infusión</a>
    @endif
  </div>

  <div class="operational-service-calendar-filters" aria-label="Filtros del calendario">
    <label>Servicio
      <select data-service-calendar-filter="service">
        <option value="">Todos</option>
        @foreach($calendarServices as $service)<option value="{{ $service }}">{{ $service }}</option>@endforeach
      </select>
    </label>
    <label>{{ $isHospitalizationCalendar ? 'Area / cama' : 'Sala' }}
      <select data-service-calendar-filter="room" @disabled(filled($calendarRoomLock))>
        @unless(filled($calendarRoomLock))<option value="">Todas</option>@endunless
        @foreach($calendarRooms as $room)<option value="{{ $room }}" @selected($calendarRoomLock === $room)>{{ $room }}</option>@endforeach
      </select>
    </label>
    <label>M&eacute;dico o responsable
      <select data-service-calendar-filter="doctor">
        <option value="">Todos</option>
        @foreach($calendarDoctors as $doctor)<option value="{{ $doctor }}">{{ $doctor }}</option>@endforeach
      </select>
    </label>
    <label>Estatus
      <select data-service-calendar-filter="status">
        <option value="">Todos</option>
        <option value="scheduled">Programado</option>
        <option value="completed">Completado</option>
        <option value="pending">Pendiente</option>
        <option value="cancelled">Cancelado</option>
      </select>
    </label>
    <label>Turno
      <select data-service-calendar-filter="shift">
        <option value="">Todos</option>
        <option value="Matutino">Matutino</option>
        <option value="Vespertino">Vespertino</option>
        <option value="Nocturno">Nocturno</option>
        <option value="Sin turno">Sin turno</option>
      </select>
    </label>
    <label class="operational-service-calendar-search">Buscar por paciente o folio
      <span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input type="search" placeholder="Nombre, folio o CURP" data-service-calendar-search>
      </span>
    </label>
    <button class="operational-service-calendar-clear" type="button" data-service-calendar-clear>
      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7z"/><path d="m17 17 4 4m0-4-4 4"/></svg>
      Limpiar filtros
    </button>
  </div>

  <div class="operational-service-calendar-toolbar">
    <div class="operational-service-calendar-navigation">
      <button type="button" data-service-calendar-step="-1" aria-label="Mes anterior">‹</button>
      <select data-service-calendar-month aria-label="Mes">
        @foreach(['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'] as $index => $month)
          <option value="{{ $index }}" @selected($calendarInitialDate->month - 1 === $index)>{{ $month }}</option>
        @endforeach
      </select>
      <select data-service-calendar-year aria-label="A&ntilde;o">
        @foreach($calendarYears as $year)<option value="{{ $year }}" @selected($calendarInitialDate->year === $year)>{{ $year }}</option>@endforeach
      </select>
      <button type="button" data-service-calendar-step="1" aria-label="Mes siguiente">›</button>
      <button class="is-today" type="button" data-service-calendar-today>Hoy</button>
    </div>
    <div class="operational-service-calendar-legend" aria-label="Leyenda de estados">
      <span class="is-scheduled">Programado</span>
      <span class="is-completed">Completado</span>
      <span class="is-pending">Pendiente</span>
      <span class="is-cancelled">Cancelado</span>
    </div>
  </div>

  <div class="operational-service-calendar-scroll">
    <div class="operational-service-calendar-weekdays" aria-hidden="true">
      @foreach(['Dom','Lun','Mar','Mi&eacute;','Jue','Vie','S&aacute;b'] as $day)<span>{!! $day !!}</span>@endforeach
    </div>
    <div class="operational-service-calendar-grid" data-service-calendar-grid></div>
  </div>

  <p class="operational-service-calendar-note">
    <span aria-hidden="true">i</span>
    Haz clic en cualquier d&iacute;a para consultar su programaci&oacute;n en una ventana emergente.
  </p>

  <script type="application/json" data-service-calendar-events>{!! json_encode($calendarEvents, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>

  <dialog class="operational-service-calendar-dialog" data-service-calendar-dialog aria-labelledby="service-calendar-day-title">
    <header>
      <div><small>PROGRAMACI&Oacute;N DEL D&Iacute;A</small><h3 id="service-calendar-day-title" data-service-calendar-dialog-title>Servicios programados</h3></div>
      <button type="button" data-service-calendar-dialog-close aria-label="Cerrar">×</button>
    </header>
    <div class="operational-service-calendar-dialog-body" data-service-calendar-dialog-body></div>
    <footer>
      <button type="button" class="operational-secondary-button" data-service-calendar-dialog-close>Cerrar</button>
    </footer>
  </dialog>

  <dialog class="operational-service-calendar-dialog operational-oncology-mixture-dialog" data-mixture-detail-dialog aria-labelledby="mixture-request-title">
    <header>
      <div>
        <small>{{ $isHospitalizationCalendar ? 'HOSPITALIZACIÓN' : 'CENTRO ONCOLÓGICO' }}</small>
        <h3 id="mixture-request-title" data-mixture-dialog-title>{{ $isHospitalizationCalendar ? 'Solicitud de mezcla hospitalaria' : 'Solicitud de mezcla oncológica' }}</h3>
      </div>
      <button type="button" data-mixture-detail-close aria-label="Cerrar">×</button>
    </header>

    <div class="operational-oncology-mixture-body">
      <section class="operational-oncology-mixture-summary" aria-label="Resumen de solicitud">
        <div><small>Folio</small><strong data-mixture-folio></strong></div>
        <div><small>Tipo de solicitud</small><strong data-mixture-type></strong></div>
        <div><small>Medicamento</small><strong data-mixture-selected-medication></strong></div>
        <div><small>Estatus</small><span class="operational-oncology-mixture-status" data-mixture-status></span></div>
      </section>

      <section class="operational-oncology-mixture-section">
        <h4>Datos del paciente</h4>
        <div class="operational-oncology-mixture-fields is-patient">
          <div><small>Paciente</small><strong data-mixture-patient></strong></div>
          <div><small>Edad</small><strong data-mixture-age></strong></div>
          <div><small>Sexo</small><strong data-mixture-sex></strong></div>
          <div><small>Diagn&oacute;stico</small><strong data-mixture-diagnosis></strong></div>
          <div><small>Peso</small><strong data-mixture-weight></strong></div>
          <div><small>Talla</small><strong data-mixture-height></strong></div>
          <div><small>Superficie corporal</small><strong data-mixture-body-surface></strong></div>
        </div>
      </section>

      <section class="operational-oncology-mixture-section">
        <h4>Datos cl&iacute;nicos / solicitud</h4>
        <div class="operational-oncology-mixture-fields">
          <div><small>M&eacute;dico responsable</small><strong data-mixture-doctor></strong></div>
          <div><small>Servicio solicitante</small><strong data-mixture-requesting-service></strong></div>
          <div><small>Sala</small><strong data-mixture-room></strong></div>
          <div><small>Turno</small><strong data-mixture-shift></strong></div>
          <div><small>Prioridad</small><strong data-mixture-priority></strong></div>
          @if($isHospitalizationCalendar)
            <div class="operational-oncology-mixture-date-form is-readonly">
              <label>Fecha de programaci&oacute;n
                <span class="operational-mixture-date-control">
                  <input type="date" readonly data-mixture-date>
                  <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M8 3v4M16 3v4M3 10h18"/></svg>
                </span>
              </label>
            </div>
          @else
            <form class="operational-oncology-mixture-date-form" method="post" data-mixture-schedule-form>
              @csrf
              @method('patch')
              <input type="hidden" name="medication_index" data-mixture-medication-index>
              <input type="hidden" name="oncology_track" value="{{ $oncologyTrack ?? 'mixes' }}">
              <label>Fecha de programaci&oacute;n
                <span>
                  <input type="date" name="application_date" required data-mixture-date>
                </span>
              </label>
              <button type="submit">Guardar fecha</button>
            </form>
          @endif
        </div>
        <div class="operational-oncology-mixture-observations">
          <small>Observaciones</small>
          <p data-mixture-observations></p>
        </div>
      </section>

      <section class="operational-oncology-mixture-section">
        <h4>Medicamentos</h4>
        <div class="operational-oncology-mixture-table-wrap">
          <table class="operational-oncology-mixture-table">
            <thead><tr><th>Medicamento</th><th>Dosis</th><th>Diluyente</th><th>Volumen final</th><th>V&iacute;a</th><th>Duraci&oacute;n</th><th>Hora</th></tr></thead>
            <tbody data-mixture-medications></tbody>
          </table>
        </div>
        <p class="operational-oncology-mixture-note">
          <span aria-hidden="true">i</span>
          {{ $isHospitalizationCalendar ? 'Los medicamentos mostrados corresponden a la solicitud seleccionada.' : 'Cada medicamento de cada renglón y cada día del calendario corresponde a una mezcla distinta que se agrega a la programación.' }}
        </p>
      </section>
    </div>

    <footer>
      <button type="button" class="operational-secondary-button" data-mixture-detail-close>Cerrar</button>
    </footer>
  </dialog>
</section>

@if(! $isHospitalizationCalendar && $selectedCalendarRequest)
  @php $currentAssignment = data_get($selectedCalendarRequest->payload, 'infusion_assignment', []); @endphp
  <section class="operational-native-table-card operational-form-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">AGENDAR SALA DE INFUSI&Oacute;N</p><h2>{{ $selectedCalendarRequest->patient?->full_name ?? 'Paciente sin nombre' }}</h2><span>{{ $selectedCalendarRequest->external_id ?? 'QT-'.$selectedCalendarRequest->id }} · {{ data_get($selectedCalendarRequest->payload, 'service', 'Oncologia medica') }}</span></div>
      <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'oncology_track' => $oncologyTrack ?? 'infusions', 'unit' => $contextUnit?->id]) }}">Cerrar</a>
    </div>
    <form class="operational-detail-form" method="post" action="{{ route('operational.infusion-assignments.update', $selectedCalendarRequest) }}">
      @csrf
      @method('patch')
      <label>Sala de infusi&oacute;n *
        <select name="procedure_area_id" required>
          <option value="">Selecciona una sala</option>
          @foreach($infusionRooms->where('status', 'active') as $room)
            <option value="{{ $room->id }}" @selected((int) old('procedure_area_id', data_get($currentAssignment, 'procedure_area_id')) === $room->id)>{{ $room->unit_number }} · {{ $room->location }} · capacidad {{ $room->simultaneous_capacity }}</option>
          @endforeach
        </select>
      </label>
      <label>Fecha de aplicaci&oacute;n *<input type="date" name="application_date" required value="{{ old('application_date', data_get($currentAssignment, 'application_date', $selectedCalendarRequest->required_at?->format('Y-m-d'))) }}"></label>
      <label>Hora de inicio *<input type="time" name="starts_at" required value="{{ old('starts_at', data_get($currentAssignment, 'starts_at', $selectedCalendarRequest->required_at?->format('H:i') ?? '08:00')) }}"></label>
      <label>Hora de fin *<input type="time" name="ends_at" required value="{{ old('ends_at', data_get($currentAssignment, 'ends_at', '10:00')) }}"></label>
      <div class="span-2 operational-room-availability">
        <strong>Horarios configurados</strong>
        @forelse($infusionRooms->where('status', 'active') as $room)
          <span><b>{{ $room->unit_number }}</b>: {{ $room->schedules->map(fn ($schedule) => ($dayNames[$schedule->day_of_week] ?? 'Dia').' '.substr((string) $schedule->starts_at, 0, 5).'-'.substr((string) $schedule->ends_at, 0, 5))->implode(', ') ?: 'Sin horario disponible' }}</span>
        @empty
          <span>No hay salas activas. Registra una desde Salas de infusi&oacute;n.</span>
        @endforelse
      </div>
      <button class="operational-primary-button span-2" type="submit" @disabled($infusionRooms->where('status', 'active')->isEmpty())>Guardar programaci&oacute;n</button>
    </form>
  </section>
@endif
