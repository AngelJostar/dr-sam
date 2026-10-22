<section class="operational-native-table-card">
  <div class="operational-native-table-heading">
    <div>
      <h2>{{ $sectionLabel }}</h2>
      <span>{{ $unit->name }} · {{ $area['label'] }}
        @if ($areaSection === 'entry' && $entries->isNotEmpty())
          · {{ $entries->count() }} ingresos
          @if ($entries->contains(fn ($entry) => data_get($entry->metadata, 'demo')))
            · Incluye datos de demostración
          @endif
        @endif
      </span>
    </div>
    @if ($areaSection === 'calendar')
      <nav class="operational-heading-actions" aria-label="Mes del calendario de cirugías">
        <a class="operational-secondary-button" aria-label="Mes anterior" href="{{ $sectionUrl('calendar', ['month' => $calendarMonth->copy()->subMonth()->format('Y-m')]) }}">‹</a>
        <strong>{{ $calendarMonth->locale('es')->translatedFormat('F Y') }}</strong>
        <a class="operational-secondary-button" aria-label="Mes siguiente" href="{{ $sectionUrl('calendar', ['month' => $calendarMonth->copy()->addMonth()->format('Y-m')]) }}">›</a>
        <a class="operational-secondary-button" href="{{ $sectionUrl('calendar') }}">Hoy</a>
      </nav>
    @endif
  </div>
  @if ($areaSection === 'calendar')
    <div class="operational-native-table-scroll">
      <table class="operational-native-table operational-surgery-calendar" aria-label="Calendario de cirugías">
        <thead><tr>@foreach (['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'] as $weekday)<th scope="col" data-drsam-table-filter-skip-column>{{ $weekday }}</th>@endforeach</tr></thead>
        <tbody>@foreach ($calendarDays->chunk(7) as $week)<tr>
          @foreach ($week as $day)<td @class(['is-outside-month' => $day->month !== $calendarMonth->month, 'is-today' => $day->isToday()])>
            <time datetime="{{ $day->format('Y-m-d') }}">{{ $day->day }}</time>
            @foreach ($appointments->filter(fn ($appointment) => $appointment->starts_at->isSameDay($day)) as $appointment)
              <details class="operational-surgery-event"><summary>{{ $appointment->starts_at->format('H:i') }} · {{ $appointment->patient?->full_name ?? 'Sin paciente' }}</summary>
                <p>{{ $appointment->procedureArea?->unit_number }} · {{ $appointment->doctor?->full_name ?? 'Sin médico asignado' }}</p>
                <p>{{ $appointment->reason }}</p>
                <p>{{ ['scheduled'=>'Programada', 'confirmed'=>'Confirmada', 'completed'=>'Completada', 'cancelled'=>'Cancelada', 'in_progress'=>'En curso', 'pending'=>'Pendiente'][$appointment->status] ?? $appointment->status }}</p>
              </details>
            @endforeach
          </td>@endforeach
        </tr>@endforeach</tbody>
      </table>
    </div>
    @if ($appointments->filter(fn ($appointment) => $appointment->starts_at->month === $calendarMonth->month)->isEmpty())<p class="operational-area-empty">No hay cirugías programadas este mes.</p>@endif
  @elseif ($areaSection === 'nutrition')
    <div class="operational-native-table-scroll"><table class="operational-native-table" data-drsam-table-filter-empty>
      <thead><tr><th>No. solicitud</th><th>Paciente</th><th>Fecha de solicitud</th><th>Entrega programada</th><th>Estado de proceso</th></tr></thead>
      <tbody>@forelse ($nutritionRequests as $record)<tr>
        <td>{{ $record->external_id ?: $record->id }}</td><td>{{ $record->patient?->full_name ?? 'Sin paciente' }}</td>
        <td>{{ $record->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td><td>{{ $record->required_at?->format('d/m/Y H:i') ?? 'Por programar' }}</td>
        <td>{{ \App\Support\OncologyMixtureBoard::statusLabel($record) }}</td>
      </tr>@empty<tr><td colspan="5">No hay solicitudes de nutrición parenteral registradas en esta unidad.</td></tr>@endforelse</tbody>
    </table></div>
  @elseif ($areaSection === 'entry')
    @include('operational.sections.entry-table')
  @else
    @php
      $bedLabel = $type === 'pediatric-icu' ? 'Cuna' : 'Cama';
      $columns = [$bedLabel, 'Ubicación', 'Paciente', 'Estado'];
    @endphp
    <div class="operational-native-table-scroll"><table class="operational-native-table">
      <thead><tr>@foreach ($columns as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
      <tbody><tr><td colspan="{{ count($columns) }}">Aún no hay {{ strtolower($bedLabel) }}s registradas en {{ $area['label'] }}.</td></tr></tbody>
    </table></div>
  @endif
</section>
