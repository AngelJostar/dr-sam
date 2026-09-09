@php
  $serviceRows = $section === 'services-history' ? $historicalProviderRequests : $pendingProviderRequests;
  $mixRows = $section === 'mix-history'
    ? $providerRequests->whereIn('status', ['delivered', 'cancelled', 'rejected'])
    : $providerRequests->whereNotIn('status', ['delivered', 'cancelled', 'rejected']);
  $workflowRows = $section === 'services-scheduled'
    ? ($scheduledProviderRequests ?? collect())
    : ($preparationProviderRequests ?? collect());
@endphp

@if (in_array($section, ['services-preparation', 'services-scheduled'], true))
  @php $isScheduledList = $section === 'services-scheduled'; @endphp
  <section class="operational-native-table-card operational-section-card" data-oncology-workflow-list>
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow">{{ $isScheduledList ? 'PROGRAMADAS' : 'EN PREPARACION' }}</p>
        <h2>
          @if ($isScheduledList)
            Infusiones programadas
          @else
            Solicitudes en preparaci&oacute;n
          @endif
        </h2>
        <span>
          @if ($isScheduledList)
            Solicitudes con sala y horario confirmados
          @else
            Solicitudes guardadas pendientes de asignaci&oacute;n de sala
          @endif
        </span>
      </div>
      <div class="operational-heading-actions">
        <strong>{{ $workflowRows->count() }} {{ $workflowRows->count() === 1 ? 'solicitud' : 'solicitudes' }}</strong>
        <button class="operational-secondary-button" type="button" data-oncology-workflow-sort aria-label="Cambiar orden por fecha">Ordenar por fecha</button>
      </div>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table" data-oncology-workflow-table>
        <thead>
          <tr>
            <th>Folio</th><th>Paciente</th><th>Servicio</th><th>M&eacute;dico</th>
            @if ($isScheduledList)<th>Sala</th><th>Sill&oacute;n o cama</th><th>Fecha</th><th>Hora</th>@else<th>Fecha de solicitud</th>@endif
            <th>Estado</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($workflowRows as $item)
            @php $assignment = data_get($item->payload, 'infusion_assignment', []); @endphp
            <tr data-oncology-workflow-row data-sort-date="{{ $isScheduledList ? data_get($assignment, 'application_date').' '.data_get($assignment, 'starts_at') : $item->requested_at?->toDateTimeString() }}">
              <td><strong>{{ $item->external_id ?? 'ONC-'.$item->id }}</strong></td>
              <td>{{ $item->patient?->full_name ?? 'Sin paciente' }}</td>
              <td>{{ data_get($item->payload, 'service', 'Quimioterapia') }}</td>
              <td>{{ data_get($item->payload, 'doctor', 'Sin medico') }}</td>
              @if ($isScheduledList)
                <td>{{ data_get($assignment, 'room_number', 'Sin sala') }}</td>
                <td>{{ data_get($assignment, 'seat_label', 'Sin asignar') }}</td>
                <td>{{ filled(data_get($assignment, 'application_date')) ? \Illuminate\Support\Carbon::parse(data_get($assignment, 'application_date'))->format('d/m/Y') : 'Sin fecha' }}</td>
                <td>{{ data_get($assignment, 'starts_at', 'Sin hora') }}</td>
              @else
                <td>{{ $item->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
              @endif
              <td>
                <span class="operational-chip">
                  @if ($isScheduledList)
                    Agendada
                  @else
                    En preparaci&oacute;n
                  @endif
                </span>
              </td>
              <td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id]) }}">Ver solicitud</a></td>
            </tr>
          @empty
            <tr>
              <td colspan="{{ $isScheduledList ? 10 : 7 }}">
                @if ($isScheduledList)
                  No hay infusiones programadas.
                @else
                  No hay solicitudes en preparaci&oacute;n.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@elseif (in_array($section, ['services-pending', 'services-history'], true))
  @php $isPendingServices = $section === 'services-pending'; @endphp
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow">AREA ADMINISTRATIVA</p>
        <h2>{{ $isPendingServices ? 'Solicitudes pendientes' : 'Historial de servicios' }}</h2>
        <span>Solicitudes oncol&oacute;gicas enviadas por los m&eacute;dicos a {{ $unitName }}</span>
      </div>
      <a class="operational-primary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'calendar', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'new_infusion' => 1]) }}">+ Nueva infusión</a>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table">
        <thead>
          <tr>
            <th>Folio</th><th>Paciente</th><th>Servicio</th><th>M&eacute;dico</th><th>Fecha requerida</th><th>Volumen</th><th>Estatus</th>
            @if ($isPendingServices)<th>Ver solicitud</th><th>Asignar sala</th>@else<th>Acciones</th>@endif
          </tr>
        </thead>
        <tbody>
          @forelse ($serviceRows as $item)
            <tr>
              <td><strong>{{ $item->external_id ?? 'QT-'.$item->id }}</strong></td>
              <td>{{ $item->patient?->full_name ?? 'Sin paciente' }}</td>
              <td>{{ data_get($item->payload, 'service', 'Oncologia medica') }}</td>
              <td>{{ data_get($item->payload, 'doctor', 'Sin medico') }}</td>
              <td>{{ $item->required_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
              <td>{{ data_get($item->payload, 'volume', 'N/A') }}</td>
              <td><span class="operational-chip">{{ $statusText($item->status) }}</span></td>
              <td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id]) }}">Ver</a></td>
              @if ($isPendingServices)
                <td>
                  <a class="operational-primary-button" href="{{ route('operational.dashboard', [
                    'area' => 'oncology',
                    'section' => 'services-pending',
                    'oncology_track' => 'infusions',
                    'unit' => $contextUnit?->id,
                    'assign_request' => $item->id,
                  ]) }}">Asignar</a>
                </td>
              @endif
            </tr>
          @empty
            <tr><td colspan="{{ $isPendingServices ? 9 : 8 }}">Sin servicios para esta vista.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@elseif (in_array($section, ['service-create', 'service-format', 'support'], true))
  @php $selectedRequest = $providerRequests->firstWhere('id', (int) request('request')); @endphp
  <section class="operational-native-table-card operational-form-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">SOLICITUD DE ONCOLÃ“GICOS</p><h2>{{ $selectedRequest ? 'Detalle de solicitud' : 'Nuevo servicio' }}</h2><span>Formato operativo para mezclas oncolÃ³gicas</span></div>
      <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'services-pending', 'oncology_track' => 'infusions']) }}">AtrÃ¡s</a>
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
@elseif ($section === 'support-ai')
  <section class="operational-native-table-card operational-section-card" data-oncology-support-ai>
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow">SOPORTE</p>
        <h2>Asistencia con IA</h2>
        <span>Solicitudes oncol&oacute;gicas disponibles para revisi&oacute;n y apoyo cl&iacute;nico.</span>
      </div>
      <strong>{{ $providerRequests->count() }} solicitudes</strong>
    </div>
    <div class="operational-native-table-scroll">
      <table class="operational-native-table">
        <thead><tr><th>Folio</th><th>Paciente</th><th>Servicio</th><th>M&eacute;dico</th><th>Estado</th><th>Acci&oacute;n</th></tr></thead>
        <tbody>
          @forelse($providerRequests->take(12) as $item)
            <tr>
              <td><strong>{{ $item->external_id ?? 'SOL-'.$item->id }}</strong></td>
              <td>{{ $item->patient?->full_name ?? 'Paciente sin nombre' }}</td>
              <td>{{ data_get($item->payload, 'service', 'Oncologia medica') }}</td>
              <td>{{ data_get($item->payload, 'doctor', 'Sin medico') }}</td>
              <td><span class="operational-chip">{{ $statusText($item->status) }}</span></td>
              <td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id]) }}">Abrir solicitud</a></td>
            </tr>
          @empty
            <tr><td colspan="6">No hay solicitudes disponibles para asistencia.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@elseif ($section === 'support-analytics')
  @php
    $supportStatusRows = collect([
      ['label' => 'Pendientes', 'count' => $pendingProviderRequests->count()],
      ['label' => 'Programadas', 'count' => $scheduledProviderRequests->count()],
      ['label' => 'En preparacion', 'count' => $preparationProviderRequests->count()],
      ['label' => 'Finalizadas', 'count' => $providerRequests->whereIn('status', ['delivered', 'cancelled', 'rejected'])->count()],
    ]);
  @endphp
  <section data-oncology-support-analytics>
    <div class="operational-native-metrics">
      @foreach($supportStatusRows as $index => $statusRow)
        <article @class(['is-active' => $index === 0])>
          <span>{{ $statusRow['label'] }}</span>
          <strong>{{ $statusRow['count'] }}</strong>
          <small>Solicitudes oncol&oacute;gicas</small>
        </article>
      @endforeach
    </div>
    <section class="operational-native-table-card operational-section-card">
      <div class="operational-native-table-heading">
        <div>
          <p class="eyebrow">SOPORTE</p>
          <h2>Anal&iacute;ticas</h2>
          <span>Resumen del flujo de solicitudes de infusi&oacute;n y mezclas oncol&oacute;gicas.</span>
        </div>
        <strong>{{ $providerRequests->count() }} solicitudes</strong>
      </div>
      <div class="operational-native-table-scroll">
        <table class="operational-native-table">
          <thead><tr><th>Indicador</th><th>Cantidad</th><th>Participaci&oacute;n</th></tr></thead>
          <tbody>
            @forelse($supportStatusRows as $statusRow)
              @php $statusShare = $providerRequests->count() > 0 ? round(($statusRow['count'] / $providerRequests->count()) * 100, 1) : 0; @endphp
              <tr><td><strong>{{ $statusRow['label'] }}</strong></td><td>{{ $statusRow['count'] }}</td><td>{{ number_format($statusShare, 1) }}%</td></tr>
            @empty
              <tr><td colspan="3">No hay informaci&oacute;n disponible.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </section>
@elseif (in_array($section, ['mixes', 'mix-history'], true))
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading"><div><p class="eyebrow">MODULO OPERATIVO</p><h2>{{ $section === 'mix-history' ? 'Historial de mezclas' : 'Mezclas programadas' }}</h2><span>PreparaciÃ³n y seguimiento de tratamientos</span></div><strong>{{ $mixRows->count() }} mezclas</strong></div>
    <div class="operational-native-table-scroll"><table class="operational-native-table"><thead><tr><th>Folio</th><th>Paciente</th><th>Mezcla</th><th>ProgramaciÃ³n</th><th>MÃ©dico</th><th>Estado</th><th>AcciÃ³n</th></tr></thead><tbody>@forelse($mixRows as $item)<tr><td><strong>{{ $item->external_id }}</strong></td><td>{{ $item->patient?->full_name }}</td><td>{{ data_get($item->payload, 'service', 'OncologÃ­a mÃ©dica') }}</td><td>{{ $item->required_at?->format('d/m/Y H:i') ?? 'Por programar' }}</td><td>{{ data_get($item->payload, 'doctor', 'Sin mÃ©dico') }}</td><td><span class="operational-chip">{{ $statusText($item->status) }}</span></td><td><a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'support', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'request' => $item->id]) }}">Abrir</a></td></tr>@empty<tr><td colspan="7">Sin mezclas registradas.</td></tr>@endforelse</tbody></table></div>
  </section>
@elseif ($section === 'calendar')
  @include('operational.sections.service-calendar')
@elseif ($section === 'infusion-rooms')
  <section class="operational-native-table-card operational-section-card" data-infusion-room-overview>
    <div class="operational-native-table-heading">
      <div>
        <p class="eyebrow">SALAS DE INFUSI&Oacute;N</p>
        <h2>Inicio de salas de infusi&oacute;n</h2>
        <span>Ocupaci&oacute;n y citas programadas en {{ $unitName }}</span>
      </div>
      <strong>{{ $infusionRooms->count() }} {{ $infusionRooms->count() === 1 ? 'sala' : 'salas' }}</strong>
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
          @forelse($infusionRooms as $room)
            @php
              $roomStats = $infusionRoomStats->get($room->id, ['today' => 0, 'week' => 0, 'month' => 0, 'monthly_occupancy' => 0]);
              $roomCapacity = max(1, (int) $room->simultaneous_capacity);
              $roomOccupancy = (float) data_get($roomStats, 'monthly_occupancy', 0);
            @endphp
            <tr>
              <td><strong>{{ $room->unit_number ?: 'Sin numero' }}</strong></td>
              <td><span class="operational-room-id">ID-{{ str_pad((string) $room->id, 4, '0', STR_PAD_LEFT) }}</span></td>
              <td>
                <strong>{{ $room->location ?: 'Sin ubicacion' }}</strong>
                @if(filled($room->floor))<small>Piso {{ $room->floor }}</small>@endif
              </td>
              <td>{{ $roomCapacity }} {{ $roomCapacity === 1 ? 'sillon' : 'sillones' }}</td>
              <td><strong class="operational-room-today-count">{{ data_get($roomStats, 'today', 0) }} de {{ $roomCapacity }} sillones</strong></td>
              <td>{{ data_get($roomStats, 'week', 0) }} citas</td>
              <td>{{ data_get($roomStats, 'month', 0) }} citas</td>
              <td>
                <div class="operational-room-occupancy" aria-label="{{ number_format($roomOccupancy, 1) }} por ciento de ocupacion mensual">
                  <strong>{{ number_format($roomOccupancy, 1) }}%</strong>
                  <span aria-hidden="true"><i style="width: {{ $roomOccupancy }}%"></i></span>
                </div>
              </td>
              <td>
                <a class="operational-view-button" href="{{ route('operational.dashboard', [
                  'area' => 'oncology',
                  'section' => 'infusion-room-calendar',
                  'oncology_track' => 'infusions',
                  'unit' => $contextUnit?->id,
                  'room' => $room->id,
                  'calendar_month' => now()->format('Y-m'),
                ]) }}">Ver calendario</a>
              </td>
              <td>
                <a class="operational-view-button" href="{{ route('operational.dashboard', [
                  'area' => 'oncology',
                  'section' => 'infusion-room-catalog',
                  'oncology_track' => 'infusions',
                  'unit' => $contextUnit?->id,
                  'room' => $room->id,
                ]) }}">Ver informaci&oacute;n</a>
              </td>
            </tr>
          @empty
            <tr><td colspan="10">No hay salas de infusi&oacute;n registradas para esta unidad.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
@elseif ($section === 'infusion-room-calendar')
  @php
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
  @endphp
  @include('operational.sections.service-calendar')
@elseif ($section === 'infusion-room-catalog')
  @php
    $editingRoom = $infusionRooms->firstWhere('id', (int) request('edit_room'));
    $viewingRoom = $selectedInfusionRoom;
    $showRoomForm = request()->boolean('new_room') || (bool) $editingRoom;
    $dayNames = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sabado', 0 => 'Domingo'];
    $dayKeys = [1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday', 0 => 'sunday'];
  @endphp
  <section class="operational-native-table-card operational-section-card">
    <div class="operational-native-table-heading">
      <div><p class="eyebrow">CATALOGO</p><h2>Cat&aacute;logo de salas de infusi&oacute;n</h2><span>Informaci&oacute;n general y configuraci&oacute;n de las salas en {{ $unitName }}</span></div>
      <div class="operational-heading-actions"><strong>{{ $infusionRooms->count() }} salas</strong><a class="operational-primary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'new_room' => 1]) }}">+ Nueva sala</a></div>
    </div>
    <div class="operational-native-table-scroll"><table class="operational-native-table">
      <thead><tr><th>N&uacute;mero de sala</th><th>ID</th><th>Ubicaci&oacute;n</th><th>Piso</th><th>Sillones</th><th>Responsable</th><th>Horario</th><th>Estatus</th><th>Acciones</th></tr></thead>
      <tbody>
        @forelse($infusionRooms as $room)
          <tr>
            <td><strong>{{ $room->unit_number ?: 'Sin numero' }}</strong></td>
            <td>ID-{{ str_pad((string) $room->id, 4, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $room->location ?: 'Sin ubicacion' }}</td>
            <td>{{ $room->floor ?: 'N/A' }}</td>
            <td>{{ $room->simultaneous_capacity }}</td>
            <td>{{ $room->responsible_name ?: 'Sin responsable' }}</td>
            <td>{{ $room->schedules->map(fn ($schedule) => $dayNames[$schedule->day_of_week] ?? 'Dia')->implode(', ') ?: 'Sin horario' }}</td>
            <td><span class="operational-chip">{{ $room->status === 'active' ? 'Activo' : 'Inactivo' }}</span></td>
            <td>
              <div class="operational-room-actions">
                <a class="operational-view-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'room' => $room->id]) }}">Ver</a>
                <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'edit_room' => $room->id]) }}">Editar</a>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9">Sin salas de infusi&oacute;n registradas para esta unidad.</td></tr>
        @endforelse
      </tbody>
    </table></div>
  </section>
  @if($viewingRoom && ! $showRoomForm)
    @php
      $viewingSchedule = $viewingRoom->schedules->map(
        fn ($schedule) => ($dayNames[$schedule->day_of_week] ?? 'Dia').' '.substr((string) $schedule->starts_at, 0, 5).'-'.substr((string) $schedule->ends_at, 0, 5)
      )->implode(' | ');
    @endphp
    <section class="operational-native-table-card operational-room-detail" data-infusion-room-detail>
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">INFORMACI&Oacute;N GENERAL</p><h2>{{ $viewingRoom->unit_number }}</h2><span>Ficha operativa de la sala de infusi&oacute;n</span></div>
        <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id, 'edit_room' => $viewingRoom->id]) }}">Editar sala</a>
      </div>
      <dl class="operational-room-detail-grid">
        <div><dt>ID</dt><dd>ID-{{ str_pad((string) $viewingRoom->id, 4, '0', STR_PAD_LEFT) }}</dd></div>
        <div><dt>N&uacute;mero de sala</dt><dd>{{ $viewingRoom->unit_number }}</dd></div>
        <div><dt>Ubicaci&oacute;n</dt><dd>{{ $viewingRoom->location ?: 'Sin ubicacion' }}</dd></div>
        <div><dt>Piso</dt><dd>{{ $viewingRoom->floor ?: 'Sin especificar' }}</dd></div>
        <div><dt>Sillones de infusi&oacute;n</dt><dd>{{ $viewingRoom->simultaneous_capacity }}</dd></div>
        <div><dt>Responsable</dt><dd>{{ $viewingRoom->responsible_name ?: 'Sin responsable' }}</dd></div>
        <div class="span-2"><dt>Horario de atenci&oacute;n</dt><dd>{{ $viewingSchedule ?: 'Sin horario configurado' }}</dd></div>
        <div><dt>Estatus</dt><dd><span class="operational-chip">{{ $viewingRoom->status === 'active' ? 'Activo' : 'Inactivo' }}</span></dd></div>
      </dl>
    </section>
  @endif
  @if($showRoomForm)
    <section class="operational-native-table-card operational-form-card">
      <div class="operational-native-table-heading">
        <div><p class="eyebrow">SALA DE INFUSI&Oacute;N</p><h2>{{ $editingRoom ? 'Editar sala de infusion' : 'Nueva sala de infusion' }}</h2><span>Configura capacidad, responsable y horario de atenci&oacute;n.</span></div>
        <a class="operational-secondary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'infusion-room-catalog', 'oncology_track' => 'infusions', 'unit' => $contextUnit?->id]) }}">Regresar</a>
      </div>
      <form class="operational-detail-form" method="post" action="{{ $editingRoom ? route('operational.infusion-rooms.update', $editingRoom) : route('operational.infusion-rooms.store') }}">
        @csrf
        @if($editingRoom) @method('put') @endif
        <input type="hidden" name="unit" value="{{ $contextUnit?->id }}">
        <label>Ubicaci&oacute;n *<input name="location" required value="{{ old('location', $editingRoom?->location) }}"></label>
        <label>Piso<input name="floor" value="{{ old('floor', $editingRoom?->floor) }}"></label>
        <label>N&uacute;mero de unidad *<input name="unit_number" required value="{{ old('unit_number', $editingRoom?->unit_number) }}" placeholder="SI-01"></label>
        <label>Capacidad simult&aacute;nea *<input name="simultaneous_capacity" type="number" min="1" max="99" required value="{{ old('simultaneous_capacity', $editingRoom?->simultaneous_capacity ?? 1) }}"></label>
        <label>Responsable de &aacute;rea<input name="responsible_name" value="{{ old('responsible_name', $editingRoom?->responsible_name) }}"></label>
        <label>Estatus<select name="status"><option value="active" @selected(old('status', $editingRoom?->status ?? 'active') === 'active')>Activo</option><option value="inactive" @selected(old('status', $editingRoom?->status) === 'inactive')>Inactivo</option></select></label>
        <fieldset class="span-2 operational-room-schedule"><legend>Horario de atenci&oacute;n de la sala</legend>
          @foreach($dayKeys as $dayNumber => $dayKey)
            @php $daySchedule = $editingRoom?->schedules->firstWhere('day_of_week', $dayNumber); @endphp
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
