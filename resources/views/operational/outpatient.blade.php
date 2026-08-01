@extends('layouts.app', ['title' => 'Consulta Externa'])

@section('body_class', 'outpatient-module-body')

@php
  $unitName = $unit?->name ?? 'H.G. CHIMALHUACAN';
  $unitCode = $unit?->code ?? $unit?->clues ?? 'MCIMB001841';
  $statusLabels = ['scheduled' => 'Agendada', 'in_progress' => 'En consulta', 'completed' => 'Atendida', 'cancelled' => 'Cancelada'];
@endphp

@section('content')
  <div class="outpatient-module-screen">
    <aside class="outpatient-module-sidebar">
      <div class="outpatient-module-brand"><span>+</span><div><strong>Consulta Externa</strong><small>{{ $unitName }}</small></div></div>
      <nav>
        <a @class(['is-active' => $section === 'agenda']) href="{{ route('outpatient.dashboard') }}">▣ Consulta externa</a>
        <a @class(['is-active' => $section === 'prescriptions']) href="{{ route('outpatient.dashboard', ['section' => 'prescriptions']) }}">▤ Recetas</a>
        <a @class(['is-active' => $section === 'patients']) href="{{ route('outpatient.dashboard', ['section' => 'patients']) }}">♧ Catálogo de pacientes</a>
        <a @class(['is-active' => $section === 'rooms']) href="{{ route('outpatient.dashboard', ['section' => 'rooms']) }}">▥ Catálogo de consultorios</a>
      </nav>
    </aside>

    <main class="outpatient-module-main">
      @if(session('status'))<div class="notice success">{{ session('status') }}</div>@endif
      @if($errors->any())<div class="notice danger">{{ $errors->first() }}</div>@endif

      <header class="outpatient-module-header">
        <div><p class="eyebrow">MODULO CONSULTA EXTERNA</p><h1>{{ $section === 'agenda' ? 'Agenda de pacientes de la unidad' : match($section) {'prescriptions' => 'Recetas de consulta externa', 'patients' => 'Catálogo de pacientes', default => 'Catálogo de consultorios'} }}</h1><span>{{ $unitName }} - {{ $unitCode }}</span></div>
        <time>{{ now()->translatedFormat('l, d \d\e F \d\e Y') }}</time>
      </header>

      @if($section === 'agenda')
        <section class="outpatient-module-card">
          <div class="outpatient-module-card-head"><div><h2>Agenda institucional generada</h2><p>Citas sincronizadas con agenda institucional del médico.</p></div><div><strong>{{ $appointments->count() }} {{ $appointments->count() === 1 ? 'cita' : 'citas' }}</strong><a class="is-secondary" href="{{ route('outpatient.dashboard', ['admin' => 1]) }}">Administrar Calendario</a><a href="{{ route('outpatient.dashboard', ['new' => 1]) }}">+ Nueva consulta</a></div></div>
          @if(request('admin'))
            <section class="outpatient-calendar-admin">
              <form method="post" data-calendar-admin-form>
                @csrf @method('PATCH')
                <label>Día activo<select name="day_of_week"><option value="1">Lunes</option><option value="2">Martes</option><option value="3">Miércoles</option><option value="4">Jueves</option><option value="5">Viernes</option></select></label>
                <label>Especialidad<input name="specialty" required value="Medicina interna"></label>
                <label>Inicio<input name="starts_at" type="time" value="08:00" required></label>
                <label>Fin<input name="ends_at" type="time" value="14:00" required></label>
                <label>Duración<select name="duration"><option>20</option><option selected>30</option><option>45</option><option>60</option></select></label>
                <label>Consultorio<select data-calendar-room required><option value="">Seleccionar consultorio</option>@foreach($rooms as $room) @if(data_get($room, 'database_id'))<option value="{{ data_get($room, 'database_id') }}" data-specialty="{{ data_get($room, 'specialty') }}">{{ data_get($room, 'name', data_get($room, 'number')) }}</option>@endif @endforeach</select></label>
                <div class="span-2 outpatient-form-actions"><a href="{{ route('outpatient.dashboard') }}">Cancelar</a><button type="submit">Guardar slots</button></div>
              </form>
            </section>
          @endif
          @if(request('new'))
            <form class="outpatient-appointment-form" method="post" action="{{ route('outpatient.appointments.store') }}">
              @csrf
              <label class="span-2">Paciente<select name="patient_id" required data-outpatient-patient><option value="">Buscar paciente por nombre, usuario, CURP o NSS</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" data-platform="{{ $patient->platform_number }}" data-federal="{{ data_get($patient->metadata, 'nss_federal') }}" data-state="{{ data_get($patient->metadata, 'nss_estatal') }}" data-curp="{{ $patient->curp }}">{{ $patient->full_name }} · {{ $patient->platform_number }}</option>@endforeach</select></label>
              <label>Número de usuario de la plataforma<input data-patient-platform readonly></label>
              <label>CURP<input data-patient-curp readonly></label>
              <label>Número de seguridad social Federal<input data-patient-federal readonly></label>
              <label>Número de seguridad social estatal<input data-patient-state readonly></label>
              <label>Médico<select name="doctor_id" required><option value="">Seleccionar médico</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}">{{ $doctor->full_name }}{{ $doctor->availabilityRules->isNotEmpty() ? ' · agenda publicada' : '' }}</option>@endforeach</select></label>
              <label>Especialidad<input name="specialty" required></label>
              <label>Consultorio<select name="procedure_area_id"><option value="">Seleccionar consultorio</option>@foreach($rooms as $room)<option value="{{ data_get($room, 'database_id') }}">{{ data_get($room, 'number', data_get($room, 'location')) }}</option>@endforeach</select><input type="hidden" name="location" value="Consulta externa"></label>
              <label>Modalidad<select name="modality"><option>Presencial</option><option>Video llamada</option></select></label>
              <label>Fecha<input type="date" name="appointment_date" required></label>
              <label>Hora<input type="time" name="appointment_time" required><small>Se valida con el calendario del consultorio.</small></label>
              <label>Duración<select name="duration"><option value="20">20 minutos</option><option value="30" selected>30 minutos</option><option value="45">45 minutos</option><option value="60">1 hora</option></select></label>
              <label>Motivo<select name="reason"><option>Primera Vez</option><option>Seguimiento</option></select></label>
              <p class="span-2 outpatient-schedule-help">La fecha se valida contra la agenda del médico y la capacidad del consultorio.</p>
              <div class="span-2 outpatient-form-actions"><a href="{{ route('outpatient.dashboard') }}">Cancelar</a><button type="submit">Agregar consulta</button></div>
            </form>
          @endif
          <div class="outpatient-table-wrap"><table><thead><tr><th>Fecha</th><th>Hora</th><th>Paciente</th><th>Usuario plataforma</th><th>Médico</th><th>Especialidad</th><th>Consultorio</th><th>Modalidad</th><th>Estado</th></tr></thead><tbody>
            @forelse($appointments as $appointment)<tr><td>{{ $appointment->starts_at?->format('d/m/Y') }}</td><td><strong>{{ $appointment->starts_at?->format('H:i') }}</strong></td><td><strong>{{ $appointment->patient?->full_name }}</strong></td><td>{{ $appointment->patient?->platform_number ?? 'Sin usuario' }}</td><td>{{ $appointment->doctor?->full_name }}</td><td>{{ $appointment->specialty }}</td><td>{{ $appointment->location }}</td><td>{{ $appointment->modality }}</td><td><span class="outpatient-status">{{ $statusLabels[$appointment->status] ?? $appointment->status }}</span></td></tr>@empty<tr><td colspan="9">Sin consultas registradas.</td></tr>@endforelse
          </tbody></table></div>
        </section>
      @elseif($section === 'prescriptions')
        @php
          $editingPrescription = request('edit') ? $prescriptions->firstWhere('id', (int) request('edit')) : null;
          $viewingPrescription = request('view') ? $prescriptions->firstWhere('id', (int) request('view')) : null;
          $formItems = old('items', $editingPrescription?->items?->map(fn($item) => ['medication_catalog_item_id' => $item->medication_catalog_item_id, 'medication_name' => $item->medication_name, 'cnis' => data_get($item->metadata, 'cnis'), 'dose' => $item->dose, 'presentation' => data_get($item->metadata, 'presentation'), 'route' => data_get($item->metadata, 'route'), 'frequency' => $item->frequency, 'duration' => $item->duration, 'quantity' => data_get($item->metadata, 'quantity'), 'instructions' => $item->instructions])->all() ?? [['medication_name' => '']]);
        @endphp
        <section class="outpatient-module-card">
          <div class="outpatient-module-card-head"><div><h2>Recetas emitidas</h2><p>Formatos asociados a las consultas de la unidad.</p></div><div><strong>{{ $prescriptions->count() }} recetas</strong><a href="{{ route('outpatient.dashboard', ['section' => 'prescriptions', 'new' => 1]) }}">+ Nueva receta</a></div></div>
          <div class="outpatient-table-wrap"><table><thead><tr><th>Fecha</th><th>Paciente</th><th>Usuario plataforma</th><th>Médico</th><th>Especialidad</th><th>Receta</th><th>Estado</th></tr></thead><tbody>
          @forelse($prescriptions as $prescription)<tr><td>{{ $prescription->issued_at?->format('d/m/Y') }}</td><td><strong>{{ $prescription->patient?->full_name }}</strong></td><td>{{ $prescription->patient?->platform_number ?? 'Sin usuario' }}</td><td>{{ $prescription->doctor?->full_name }}</td><td>{{ $prescription->doctor?->specialty }}</td><td><strong>{{ $prescription->code ?? 'REC-'.$prescription->id }}</strong><small>{{ $prescription->items->count() }} medicamento(s)</small></td><td><span class="outpatient-status">{{ ucfirst($prescription->status) }}</span></td></tr>@empty<tr><td colspan="7">Sin recetas registradas.</td></tr>@endforelse
          </tbody></table></div>
        </section>
        @if($viewingPrescription)
          <section class="outpatient-module-card outpatient-prescription-detail"><div class="outpatient-module-card-head"><div><h2>Receta {{ $viewingPrescription->code }}</h2><p>{{ $viewingPrescription->patient?->full_name }} · Diagnóstico: {{ data_get($viewingPrescription->metadata, 'diagnosis') }}</p></div><a class="is-secondary" href="{{ route('outpatient.dashboard', ['section' => 'prescriptions']) }}">Cerrar</a></div><div class="outpatient-table-wrap"><table><thead><tr><th>No.</th><th>Clave CNIS</th><th>Medicamento</th><th>Dosis</th><th>Presentación</th><th>Vía</th><th>Frecuencia</th><th>Duración</th><th>Cantidad</th><th>Indicaciones</th></tr></thead><tbody>@foreach($viewingPrescription->items as $item)<tr><td>{{ $loop->iteration }}</td><td>{{ data_get($item->metadata, 'cnis') }}</td><td><strong>{{ $item->medication_name }}</strong></td><td>{{ $item->dose }}</td><td>{{ data_get($item->metadata, 'presentation') }}</td><td>{{ data_get($item->metadata, 'route') }}</td><td>{{ $item->frequency }}</td><td>{{ $item->duration }}</td><td>{{ data_get($item->metadata, 'quantity') }}</td><td>{{ $item->instructions }}</td></tr>@endforeach</tbody></table></div></section>
        @endif
        @if(request('new') || $editingPrescription)
          <section class="outpatient-module-card outpatient-prescription-form-card"><div class="outpatient-module-card-head"><div><h2>{{ $editingPrescription ? 'Editar receta médica' : 'Nueva receta médica' }}</h2><p>Formato para Consulta Externa y Farmacia Externa.</p></div><a class="is-secondary" href="{{ route('outpatient.dashboard', ['section' => 'prescriptions']) }}">Regresar</a></div>
            <form method="post" action="{{ $editingPrescription ? route('outpatient.prescriptions.update', $editingPrescription) : route('outpatient.prescriptions.store') }}" data-outpatient-prescription>@csrf @if($editingPrescription) @method('PATCH') @endif
              <div class="outpatient-prescription-fields"><label>Folio<input name="code" value="{{ old('code', $editingPrescription?->code) }}" placeholder="Se genera automáticamente"></label><label>Fecha<input type="date" name="issued_at" required value="{{ old('issued_at', $editingPrescription?->issued_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"></label><label>Paciente<select name="patient_id" required><option value="">Seleccionar paciente</option>@foreach($patients as $patient)<option value="{{ $patient->id }}" @selected((int) old('patient_id', $editingPrescription?->patient_id) === $patient->id)>{{ $patient->full_name }} · {{ $patient->platform_number }}</option>@endforeach</select></label><label>Médico tratante<select name="doctor_id" required><option value="">Seleccionar médico</option>@foreach($doctors as $doctor)<option value="{{ $doctor->id }}" @selected((int) old('doctor_id', $editingPrescription?->doctor_id) === $doctor->id)>{{ $doctor->full_name }} · {{ $doctor->specialty }}</option>@endforeach</select></label><label class="span-2">Diagnóstico<textarea name="diagnosis" required>{{ old('diagnosis', data_get($editingPrescription?->metadata, 'diagnosis')) }}</textarea></label><label>Estado<select name="status">@foreach(['active'=>'Activa','pending'=>'Pendiente','filled'=>'Surtida','cancelled'=>'Cancelada'] as $value=>$label)<option value="{{ $value }}" @selected(old('status', $editingPrescription?->status ?? 'active') === $value)>{{ $label }}</option>@endforeach</select></label><label>Notas<textarea name="notes">{{ old('notes', $editingPrescription?->notes) }}</textarea></label></div>
              <div class="outpatient-prescription-items"><h3>Medicamentos indicados</h3><div class="outpatient-table-wrap"><table><thead><tr><th>No.</th><th>Clave CNIS</th><th>Medicamento</th><th>Dosis</th><th>Presentación</th><th>Vía</th><th>Frecuencia</th><th>Duración</th><th>Cantidad</th><th>Indicaciones</th></tr></thead><tbody data-prescription-items>@foreach($formItems as $item)<tr data-prescription-item><td><span data-item-number>{{ $loop->iteration }}</span><button type="button" data-remove-prescription-item>−</button></td><td><input name="items[{{ $loop->index }}][cnis]" value="{{ data_get($item, 'cnis') }}"></td><td><input type="hidden" name="items[{{ $loop->index }}][medication_catalog_item_id]" value="{{ data_get($item, 'medication_catalog_item_id') }}"><input name="items[{{ $loop->index }}][medication_name]" list="outpatient-medications" required value="{{ data_get($item, 'medication_name') }}"></td><td><input name="items[{{ $loop->index }}][dose]" value="{{ data_get($item, 'dose') }}"></td><td><input name="items[{{ $loop->index }}][presentation]" value="{{ data_get($item, 'presentation') }}"></td><td><input name="items[{{ $loop->index }}][route]" value="{{ data_get($item, 'route') }}"></td><td><input name="items[{{ $loop->index }}][frequency]" value="{{ data_get($item, 'frequency') }}"></td><td><input name="items[{{ $loop->index }}][duration]" value="{{ data_get($item, 'duration') }}"></td><td><input name="items[{{ $loop->index }}][quantity]" value="{{ data_get($item, 'quantity') }}"></td><td><textarea name="items[{{ $loop->index }}][instructions]">{{ data_get($item, 'instructions') }}</textarea></td></tr>@endforeach</tbody></table></div><button type="button" class="outpatient-add-prescription-item" data-add-prescription-item>+</button></div>
              <datalist id="outpatient-medications">@foreach($medications as $medication)<option value="{{ $medication->generic_name ?? $medication->name }}">{{ $medication->code }}</option>@endforeach</datalist><div class="outpatient-form-actions"><a href="{{ route('outpatient.dashboard', ['section' => 'prescriptions']) }}">Cancelar</a><button type="submit">Guardar receta</button></div>
            </form>
          </section>
        @endif
      @elseif($section === 'patients')
        @php $editingPatient = request('edit') ? $patients->firstWhere('id', (int) request('edit')) : null; @endphp
        <section class="outpatient-module-card">
          <div class="outpatient-module-card-head"><div><p class="eyebrow">CONSULTA EXTERNA</p><h2>Catálogo de pacientes</h2><p>{{ $unitName }} - Pacientes registrados</p></div><a href="{{ route('outpatient.dashboard', ['section' => 'patients', 'new' => 1]) }}">+ Nuevo paciente</a></div>
          <div class="outpatient-table-wrap"><table><thead><tr><th>Nombre</th><th>Apellidos</th><th>Edad</th><th>CURP</th><th>NSS federal</th><th>NSS estatal</th><th>Usuario plataforma</th><th>Entidad federativa</th><th>Acciones</th></tr></thead><tbody>
          @forelse($patients as $patient)
            @php
              $names = trim($patient->first_name ?: str($patient->full_name)->before(' '));
              $lastNames = trim($patient->last_name ?: str($patient->full_name)->after(' '));
            @endphp
            <tr><td><strong>{{ $names }}</strong></td><td><strong>{{ $lastNames }}</strong></td><td>{{ $patient->birth_date?->age ?? data_get($patient->metadata, 'age', 'Sin edad') }}</td><td><strong>{{ $patient->curp ?? 'Sin CURP' }}</strong></td><td>{{ data_get($patient->metadata, 'nss_federal', 'Sin NSS') }}</td><td>{{ data_get($patient->metadata, 'nss_estatal', 'Sin NSS') }}</td><td>{{ $patient->platform_number ?? 'Sin usuario' }}</td><td>{{ data_get($patient->metadata, 'state', 'México') }}</td><td><a class="outpatient-row-action" href="{{ route('outpatient.dashboard', ['section' => 'patients', 'edit' => $patient->id]) }}">Editar</a></td></tr>
          @empty <tr><td colspan="9">Sin pacientes registrados.</td></tr> @endforelse
          </tbody></table></div>
        </section>

        @if(request('new') || $editingPatient)
          <section class="outpatient-module-card outpatient-patient-form-card">
            <div class="outpatient-module-card-head"><div><h2>{{ $editingPatient ? 'Editar paciente' : 'Alta de paciente' }}</h2><p>Información de identificación para Consulta Externa.</p></div></div>
            <form class="outpatient-patient-form" method="post" action="{{ $editingPatient ? route('outpatient.patients.update', $editingPatient) : route('outpatient.patients.store') }}">
              @csrf @if($editingPatient) @method('PATCH') @endif
              <label>Nombre<input name="first_name" required value="{{ old('first_name', $editingPatient?->first_name ?: ($editingPatient ? str($editingPatient->full_name)->before(' ') : '')) }}"></label>
              <label>Apellidos<input name="last_name" required value="{{ old('last_name', $editingPatient?->last_name ?: ($editingPatient ? str($editingPatient->full_name)->after(' ') : '')) }}"></label>
              <label>Edad<input name="age" type="number" min="0" max="130" required value="{{ old('age', $editingPatient?->birth_date?->age) }}"></label>
              <label>CURP<input name="curp" maxlength="18" required value="{{ old('curp', $editingPatient?->curp) }}"></label>
              <label>NSS federal<input name="nss_federal" value="{{ old('nss_federal', data_get($editingPatient?->metadata, 'nss_federal')) }}"></label>
              <label>NSS estatal<input name="nss_estatal" value="{{ old('nss_estatal', data_get($editingPatient?->metadata, 'nss_estatal')) }}"></label>
              <label>Usuario plataforma<input name="platform_number" value="{{ old('platform_number', $editingPatient?->platform_number) }}"></label>
              <label>Entidad federativa<input name="state" required value="{{ old('state', data_get($editingPatient?->metadata, 'state', 'México')) }}"></label>
              <div class="span-2 outpatient-form-actions"><a href="{{ route('outpatient.dashboard', ['section' => 'patients']) }}">Cancelar</a><button type="submit">Guardar paciente</button></div>
            </form>
          </section>
        @endif
      @else
        @php
          $editingRoom = request('edit') ? $rooms->firstWhere('database_id', (int) request('edit')) : null;
          $roomDays = ['monday' => [1, 'Lunes'], 'tuesday' => [2, 'Martes'], 'wednesday' => [3, 'Miércoles'], 'thursday' => [4, 'Jueves'], 'friday' => [5, 'Viernes'], 'saturday' => [6, 'Sábado'], 'sunday' => [0, 'Domingo']];
          $dayLabels = collect($roomDays)->mapWithKeys(fn ($value) => [$value[0] => $value[1]]);
        @endphp
        <section class="outpatient-module-card">
          <div class="outpatient-module-card-head"><div><p class="eyebrow">CONSULTA EXTERNA</p><h2>Catálogo de consultorios</h2><p>Consultorios registrados en la unidad</p></div><a href="{{ route('outpatient.dashboard', ['section' => 'rooms', 'new' => 1]) }}">+ Nuevo consultorio</a></div>
          <div class="outpatient-table-wrap"><table class="outpatient-room-catalog-table"><thead><tr><th>Consultorio</th><th>Clave</th><th>Ubicación</th><th>Especialidad</th><th>Tipo</th><th>Disponibilidad</th><th>Estado</th><th>Acciones</th></tr></thead><tbody>
          @forelse($rooms as $room)
            @php
              $dateRanges = collect(data_get($room, 'availability_ranges', []));
              $scheduleCount = $dateRanges->isNotEmpty() ? $dateRanges->count() : collect(data_get($room, 'schedule', []))->count();
              $availability = $scheduleCount ? $scheduleCount.' días / '.$scheduleCount.' rangos' : 'Sin rangos';
            @endphp
            <tr><td><strong>{{ data_get($room, 'name', data_get($room, 'number', 'Consultorio')) }}</strong></td><td>{{ data_get($room, 'id', data_get($room, 'number', 'N/A')) }}</td><td>{{ data_get($room, 'location', 'Consulta externa') }}</td><td>{{ data_get($room, 'specialty', 'Consulta externa') }}</td><td>{{ data_get($room, 'modality', 'Presencial') }}</td><td>{{ $availability ?: 'Sin disponibilidad' }}</td><td><span class="outpatient-status">{{ data_get($room, 'status') === 'inactive' ? 'Inactivo' : 'Activo' }}</span></td><td><div class="outpatient-row-actions"><a class="outpatient-row-action" href="{{ route('outpatient.dashboard', ['section' => 'rooms', 'edit' => data_get($room, 'database_id')]) }}">Editar</a>@if(data_get($room, 'database_id'))<form method="post" action="{{ route('outpatient.rooms.destroy', data_get($room, 'database_id')) }}" onsubmit="return confirm('¿Eliminar este consultorio?');">@csrf @method('DELETE')<button class="outpatient-row-action outpatient-row-danger" type="submit">Eliminar</button></form>@endif</div></td></tr>
          @empty<tr><td colspan="8">Sin consultorios registrados.</td></tr>@endforelse
          </tbody></table></div>
        </section>

        @if(request('new') || $editingRoom)
          <section class="outpatient-module-card outpatient-room-form-card">
            @php
              $initialRanges = collect(data_get($editingRoom, 'availability_ranges', []));
              if ($initialRanges->isEmpty() && $editingRoom) {
                $baseMonth = now()->startOfMonth();
                $initialRanges = collect(data_get($editingRoom, 'schedule', []))->map(function ($range) use ($baseMonth, $editingRoom) {
                  $date = $baseMonth->copy()->next((int) data_get($range, 'day'));
                  return ['date' => $date->toDateString(), 'start' => data_get($range, 'start'), 'end' => data_get($range, 'end'), 'duration' => 30, 'modality' => data_get($editingRoom, 'modality', 'Presencial')];
                });
              }
            @endphp
            <form class="outpatient-room-form" data-outpatient-room-form method="post" action="{{ $editingRoom ? route('outpatient.rooms.update', data_get($editingRoom, 'database_id')) : route('outpatient.rooms.store') }}">
              @csrf @if($editingRoom) @method('PATCH') @endif
              <h2>{{ $editingRoom ? 'Editar consultorio' : 'Nuevo consultorio' }}</h2>
              <div class="outpatient-room-fields">
                <label>Nombre<input name="name" required value="{{ old('name', data_get($editingRoom, 'name', data_get($editingRoom, 'number'))) }}"></label>
                <label>Clave<input name="code" required value="{{ old('code', data_get($editingRoom, 'id')) }}"></label>
                <label>Ubicación<input name="location" required value="{{ old('location', data_get($editingRoom, 'location', 'Consulta externa')) }}"></label>
                <label>Especialidad<select name="specialty" required>@foreach(['Medicina Interna','Medicina Familiar','Pediatría','Ginecología y Obstetricia','Consulta Externa'] as $specialty)<option @selected(old('specialty', data_get($editingRoom, 'specialty', 'Medicina Interna')) === $specialty)>{{ $specialty }}</option>@endforeach</select></label>
                <label>Tipo<select name="modality"><option @selected(old('modality', data_get($editingRoom, 'modality')) === 'Presencial')>Presencial</option><option @selected(old('modality', data_get($editingRoom, 'modality')) === 'Video llamada')>Video llamada</option></select></label>
                <label>Estado<select name="status"><option value="active" @selected(old('status', data_get($editingRoom, 'status', 'active')) === 'active')>Activo</option><option value="inactive" @selected(old('status', data_get($editingRoom, 'status')) === 'inactive')>Inactivo</option></select></label>
                <input type="hidden" name="capacity" value="{{ old('capacity', data_get($editingRoom, 'capacity', 1)) }}">
              </div>
              <input type="hidden" name="availability_ranges" data-room-ranges value="{{ $initialRanges->values()->toJson() }}">
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
              <div class="outpatient-form-actions"><a href="{{ route('outpatient.dashboard', ['section' => 'rooms']) }}">Cancelar</a><button type="submit">Guardar consultorio</button></div>
            </form>
          </section>
        @endif
      @endif
    </main>
  </div>
  <script>
    (() => {
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
        calendarForm.action = `{{ url('/outpatient/calendar') }}/${room.value}`;
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
@endsection
