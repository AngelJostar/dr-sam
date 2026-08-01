@php
  $serviceRows = $section === 'services-history' ? $historicalProviderRequests : $pendingProviderRequests;
  $mixRows = $section === 'mix-history'
    ? $providerRequests->whereIn('status', ['delivered', 'cancelled', 'rejected'])
    : $providerRequests->whereNotIn('status', ['delivered', 'cancelled', 'rejected']);
@endphp

@if (in_array($section, ['services-pending', 'services-history'], true))
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">AREA ADMINISTRATIVA</p><h2>{{ $section === 'services-history' ? 'Historial de servicios' : 'Servicios pendientes' }}</h2><span>Solicitudes oncolÃ³gicas recibidas por {{ $unitName }}</span></div>
      <a class="operational-primary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'service-create']) }}">+ Nuevo servicio</a>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table">
        <thead><tr><th>Folio</th><th>Paciente</th><th>Servicio</th><th>MÃ©dico</th><th>Fecha requerida</th><th>Volumen</th><th>Estatus</th><th>Acciones</th></tr></thead>
        <tbody>
          @forelse ($serviceRows as $item)
            <tr><td><strong>{{ $item->external_id ?? 'QT-'.$item->id }}</strong></td><td>{{ $item->patient?->full_name ?? 'Sin paciente' }}</td><td>{{ data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica') }}</td><td>{{ data_get($item->payload, 'doctor', 'Sin mÃ©dico') }}</td><td>{{ $item->required_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td><td>{{ data_get($item->payload, 'volume', 'N/A') }}</td><td><span class="operational-chip">{{ $statusText($item->status) }}</span></td><td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'service-format', 'request' => $item->id]) }}">Ver</a></td></tr>
          @empty
            <tr><td colspan="8">Sin servicios para esta vista.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@elseif (in_array($section, ['service-create', 'service-format'], true))
  @php $selectedRequest = $providerRequests->firstWhere('id', (int) request('request')); @endphp
  <section class="operational-native-table-card operational-form-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">SOLICITUD DE ONCOLÃ“GICOS</p><h2>{{ $selectedRequest ? 'Detalle de solicitud' : 'Nuevo servicio' }}</h2><span>Formato operativo para mezclas oncolÃ³gicas</span></div>
      <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending']) }}">AtrÃ¡s</a>
    </div>
    <form class="operational-detail-form" method="post" action="{{ route('operational.service-requests.store') }}">
      @csrf
      @if (! $selectedRequest && ($outpatientPrescriptions ?? collect())->isNotEmpty())
        <label class="span-2">Receta de Consulta Externa
          <select name="prescription_id">
            <option value="">Sin receta vinculada</option>
            @foreach($outpatientPrescriptions as $prescription)
              <option value="{{ $prescription->id }}" @selected(old('prescription_id') == $prescription->id)>
                {{ $prescription->code ?? 'RX-'.$prescription->id }} - {{ $prescription->patient?->full_name ?? 'Sin paciente' }} - {{ $prescription->doctor?->full_name ?? 'Sin medico' }} - {{ $prescription->issued_at?->format('d/m/Y') ?? 'Sin fecha' }}
              </option>
            @endforeach
          </select>
          <small>Usa una receta emitida en Consulta Externa como origen clinico de la solicitud.</small>
        </label>
      @endif
      @if ($selectedRequest && data_get($selectedRequest->payload, 'prescription_code'))
        <div class="span-2 operational-chip">Receta Consulta Externa: {{ data_get($selectedRequest->payload, 'prescription_code') }}</div>
      @endif
      <label>Paciente *<select name="patient_id" required><option value="">Selecciona paciente</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" @selected($selectedRequest?->patient_id === $patient->id)>{{ $patient->full_name }}</option>@endforeach</select></label>
      <label>Servicio *<input name="service" required value="{{ data_get($selectedRequest?->payload, 'service', 'OncologÃ­a mÃ©dica') }}"></label>
      <label>MÃ©dico *<input name="doctor" required value="{{ data_get($selectedRequest?->payload, 'doctor') }}"></label>
      <label>Volumen total ml<input name="volume" type="number" min="0" value="{{ data_get($selectedRequest?->payload, 'volume') }}"></label>
      <label>Fecha de entrega<input name="required_at" type="datetime-local" value="{{ $selectedRequest?->required_at?->format('Y-m-d\TH:i') }}"></label>
      <label class="span-2">DiagnÃ³stico<textarea name="diagnosis">{{ data_get($selectedRequest?->payload, 'diagnosis') }}</textarea></label>
      <fieldset class="span-2"><legend>Medicamentos y administraciÃ³n</legend><div class="operational-medication-grid">@foreach(range(1, 4) as $number)<span>{{ $number }}</span><input placeholder="Medicamento"><input placeholder="Dosis"><select><option>IV</option><option>IM</option><option>SC</option></select><input type="date">@endforeach</div></fieldset>
      <label class="span-2">Observaciones<textarea name="notes">{{ data_get($selectedRequest?->payload, 'notes') }}</textarea></label>
      @unless($selectedRequest)<button class="operational-primary-button span-2" type="submit">Guardar formato</button>@endunless
    </form>
  </section>
@elseif (in_array($section, ['mixes', 'mix-history'], true))
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading"><div><p class="eyebrow">AREA OPERATIVA</p><h2>{{ $section === 'mix-history' ? 'Historial de mezclas' : 'Mezclas programadas' }}</h2><span>PreparaciÃ³n y seguimiento de tratamientos</span></div><strong>{{ $mixRows->count() }} mezclas</strong></div>
    <div class="operational-native-table-scroll"><table class="operational-native-table"><thead><tr><th>Folio</th><th>Paciente</th><th>Mezcla</th><th>ProgramaciÃ³n</th><th>MÃ©dico</th><th>Estado</th><th>AcciÃ³n</th></tr></thead><tbody>@forelse($mixRows as $item)<tr><td><strong>{{ $item->external_id }}</strong></td><td>{{ $item->patient?->full_name }}</td><td>{{ data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica') }}</td><td>{{ $item->required_at?->format('d/m/Y H:i') ?? 'Por programar' }}</td><td>{{ data_get($item->payload, 'doctor', 'Sin mÃ©dico') }}</td><td><span class="operational-chip">{{ $statusText($item->status) }}</span></td><td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'service-format', 'request' => $item->id]) }}">Abrir</a></td></tr>@empty<tr><td colspan="7">Sin mezclas registradas.</td></tr>@endforelse</tbody></table></div>
  </section>
@elseif ($section === 'calendar')
  @php
    $selectedCalendarRequest = $providerRequests->firstWhere('id', (int) request('schedule_request'));
    $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'MiÃ©rcoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'SÃ¡bado', 0 => 'Domingo'];
  @endphp
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading"><div><p class="eyebrow">PROGRAMACIÃ“N</p><h2>Calendario de servicios</h2><span>Agenda de las salas de infusiÃ³n registradas en la unidad</span></div><strong>{{ $infusionRooms->count() }} salas</strong></div>
    <div class="operational-calendar-grid">
      @forelse($providerRequests->sortBy('required_at') as $item)
        @php
          $assignment = data_get($item->payload, 'infusion_assignment');
        @endphp
        <article>
          <time>{{ data_get($assignment, 'application_date') ? \Illuminate\Support\Carbon::parse(data_get($assignment, 'application_date'))->format('d M') : ($item->required_at?->format('d M') ?? 'S/F') }}</time>
          <div>
            <strong>{{ $item->patient?->full_name ?? 'Sin paciente' }}</strong>
            <span>{{ data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica') }} Â· {{ data_get($item->payload, 'doctor', 'Sin mÃ©dico') }}</span>
            <span>{{ data_get($assignment, 'room_number', 'Sala pendiente') }}@if(data_get($assignment, 'starts_at')) Â· {{ data_get($assignment, 'starts_at') }}â€“{{ data_get($assignment, 'ends_at') }}@endif</span>
          </div>
          <div class="operational-calendar-actions">
            <span class="operational-chip">{{ $assignment ? 'Agendado' : $statusText($item->status) }}</span>
            <a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'unit' => $contextUnit?->id, 'schedule_request' => $item->id]) }}">{{ $assignment ? 'Reprogramar' : 'Agendar sala' }}</a>
          </div>
        </article>
      @empty
        <p>Sin servicios programados.</p>
      @endforelse
    </div>
  </section>
  @if($selectedCalendarRequest)
    @php
      $currentAssignment = data_get($selectedCalendarRequest->payload, 'infusion_assignment', []);
    @endphp
    <section class="operational-native-table-card operational-form-card">
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">AGENDAR SALA DE INFUSIÃ“N</p><h2>{{ $selectedCalendarRequest->patient?->full_name ?? 'Paciente sin nombre' }}</h2><span>{{ $selectedCalendarRequest->external_id ?? 'QT-'.$selectedCalendarRequest->id }} Â· {{ data_get($selectedCalendarRequest->payload, 'service', 'OncologÃ­a mÃ©dica') }}</span></div>
        <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'unit' => $contextUnit?->id]) }}">Cerrar</a>
      </div>
      <form class="operational-detail-form" method="post" action="{{ route('operational.infusion-assignments.update', $selectedCalendarRequest) }}">
        @csrf
        @method('patch')
        <label>Sala de infusiÃ³n *
          <select name="procedure_area_id" required>
            <option value="">Selecciona una sala</option>
            @foreach($infusionRooms->where('status', 'active') as $room)
              <option value="{{ $room->id }}" @selected((int) old('procedure_area_id', data_get($currentAssignment, 'procedure_area_id')) === $room->id)>{{ $room->unit_number }} Â· {{ $room->location }} Â· capacidad {{ $room->simultaneous_capacity }}</option>
            @endforeach
          </select>
        </label>
        <label>Fecha de aplicaciÃ³n *<input type="date" name="application_date" required value="{{ old('application_date', data_get($currentAssignment, 'application_date', $selectedCalendarRequest->required_at?->format('Y-m-d'))) }}"></label>
        <label>Hora de inicio *<input type="time" name="starts_at" required value="{{ old('starts_at', data_get($currentAssignment, 'starts_at', $selectedCalendarRequest->required_at?->format('H:i') ?? '08:00')) }}"></label>
        <label>Hora de fin *<input type="time" name="ends_at" required value="{{ old('ends_at', data_get($currentAssignment, 'ends_at', '10:00')) }}"></label>
        <div class="span-2 operational-room-availability">
          <strong>Horarios configurados</strong>
          @forelse($infusionRooms->where('status', 'active') as $room)
            <span><b>{{ $room->unit_number }}</b>: {{ $room->schedules->map(fn ($schedule) => ($dayNames[$schedule->day_of_week] ?? 'DÃ­a').' '.substr((string) $schedule->starts_at, 0, 5).'-'.substr((string) $schedule->ends_at, 0, 5))->implode(', ') ?: 'Sin horario disponible' }}</span>
          @empty
            <span>No hay salas activas. Registra una desde Salas de infusiÃ³n.</span>
          @endforelse
        </div>
        <button class="operational-primary-button span-2" type="submit" @disabled($infusionRooms->where('status', 'active')->isEmpty())>Guardar programaciÃ³n</button>
      </form>
    </section>
  @endif
@elseif ($section === 'infusion-rooms')
  @php
    $editingRoom = $infusionRooms->firstWhere('id', (int) request('edit_room'));
    $showRoomForm = request()->boolean('new_room') || (bool) $editingRoom;
    $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'MiÃ©rcoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'SÃ¡bado', 0 => 'Domingo'];
    $dayKeys = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 0 => 'sunday'];
  @endphp
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">INFRAESTRUCTURA</p><h2>Salas de infusiÃ³n</h2><span>Ãreas habilitadas en {{ $unitName }}</span></div>
      <div class="operational-heading-actions"><strong>{{ $infusionRooms->count() }} salas</strong><a class="operational-primary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-rooms', 'unit' => $contextUnit?->id, 'new_room' => 1]) }}">+ Nueva sala</a></div>
    </div>
    <div class="operational-native-table-scroll"><table class="operational-native-table">
      <thead><tr><th>UbicaciÃ³n</th><th>Piso</th><th>NÃºmero</th><th>Capacidad simultÃ¡nea</th><th>Responsable</th><th>Horario</th><th>Estatus</th><th>Acciones</th></tr></thead>
      <tbody>
        @forelse($infusionRooms as $room)
          <tr><td><strong>{{ $room->location ?: 'Sin ubicaciÃ³n' }}</strong></td><td>{{ $room->floor ?: 'N/A' }}</td><td>{{ $room->unit_number ?: 'N/A' }}</td><td>{{ $room->simultaneous_capacity }} pacientes</td><td>{{ $room->responsible_name ?: 'Sin responsable' }}</td><td>{{ $room->schedules->map(fn ($schedule) => $dayNames[$schedule->day_of_week] ?? 'DÃ­a')->implode(', ') ?: 'Sin horario' }}</td><td><span class="operational-chip">{{ $room->status === 'active' ? 'Activo' : 'Inactivo' }}</span></td><td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-rooms', 'unit' => $contextUnit?->id, 'edit_room' => $room->id]) }}">Editar</a></td></tr>
        @empty
          <tr><td colspan="8">Sin salas de infusiÃ³n registradas para esta unidad.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </section>
  @if($showRoomForm)
    <section class="operational-native-table-card operational-form-card">
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">SALA DE INFUSIÃ“N</p><h2>{{ $editingRoom ? 'Editar sala de infusiÃ³n' : 'Nueva sala de infusiÃ³n' }}</h2><span>Configura capacidad, responsable y horario de atenciÃ³n.</span></div>
        <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-rooms', 'unit' => $contextUnit?->id]) }}">Regresar</a>
      </div>
      <form class="operational-detail-form" method="post" action="{{ $editingRoom ? route('operational.infusion-rooms.update', $editingRoom) : route('operational.infusion-rooms.store') }}">
        @csrf
        @if($editingRoom) @method('put') @endif
        <input type="hidden" name="unit" value="{{ $contextUnit?->id }}">
        <label>UbicaciÃ³n *<input name="location" required value="{{ old('location', $editingRoom?->location) }}"></label>
        <label>Piso<input name="floor" value="{{ old('floor', $editingRoom?->floor) }}"></label>
        <label>NÃºmero de unidad *<input name="unit_number" required value="{{ old('unit_number', $editingRoom?->unit_number) }}" placeholder="SI-01"></label>
        <label>Capacidad simultÃ¡nea *<input name="simultaneous_capacity" type="number" min="1" max="99" required value="{{ old('simultaneous_capacity', $editingRoom?->simultaneous_capacity ?? 1) }}"></label>
        <label>Responsable de Ã¡rea<input name="responsible_name" value="{{ old('responsible_name', $editingRoom?->responsible_name) }}"></label>
        <label>Estatus<select name="status"><option value="active" @selected(old('status', $editingRoom?->status ?? 'active') === 'active')>Activo</option><option value="inactive" @selected(old('status', $editingRoom?->status) === 'inactive')>Inactivo</option></select></label>
        <fieldset class="span-2 operational-room-schedule"><legend>Horario de atenciÃ³n de la sala</legend>
          @foreach($dayKeys as $dayNumber => $dayKey)
            @php($daySchedule = $editingRoom?->schedules->firstWhere('day_of_week', $dayNumber))
            <div class="operational-room-schedule-row">
              <label><input type="checkbox" name="schedule[{{ $dayKey }}][enabled]" value="1" @checked(old("schedule.$dayKey.enabled", (bool) $daySchedule))> {{ $dayNames[$dayNumber] }}</label>
              <label>Hora inicio<input type="time" name="schedule[{{ $dayKey }}][start]" value="{{ old("schedule.$dayKey.start", $daySchedule?->starts_at ? substr((string) $daySchedule->starts_at, 0, 5) : '08:00') }}"></label>
              <label>Hora fin<input type="time" name="schedule[{{ $dayKey }}][end]" value="{{ old("schedule.$dayKey.end", $daySchedule?->ends_at ? substr((string) $daySchedule->ends_at, 0, 5) : '16:00') }}"></label>
            </div>
          @endforeach
        </fieldset>
        <button class="operational-primary-button span-2" type="submit">{{ $editingRoom ? 'Guardar cambios' : 'Guardar sala' }}</button>
      </form>
    </section>
  @endif
@endif
