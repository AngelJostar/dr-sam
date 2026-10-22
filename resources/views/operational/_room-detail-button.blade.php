<x-operational-detail :id="'room-information-'.$room->id" :title="'Sala de infusión '.$room->unit_number" :context="$unitName" :label="$roomViewLabel ?? 'Ver'">
  <dl>
    <div><dt>ID</dt><dd>ID-{{ str_pad((string) $room->id, 4, '0', STR_PAD_LEFT) }}</dd></div>
    <div><dt>Número de sala</dt><dd>{{ $room->unit_number ?: 'Sin número' }}</dd></div>
    <div><dt>Ubicación</dt><dd>{{ $room->location ?: 'Sin ubicación' }}</dd></div>
    <div><dt>Piso</dt><dd>{{ $room->floor ?: 'Sin especificar' }}</dd></div>
    <div><dt>Sillones de infusión</dt><dd>{{ $room->simultaneous_capacity }}</dd></div>
    <div><dt>Responsable</dt><dd>{{ $room->responsible_name ?: 'Sin responsable' }}</dd></div>
    <div class="span-2"><dt>Horario de atención</dt><dd>{{ $room->schedules->map(fn ($schedule) => ([1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 0=>'Domingo'][$schedule->day_of_week] ?? 'Día').' '.substr((string) $schedule->starts_at, 0, 5).'-'.substr((string) $schedule->ends_at, 0, 5))->implode(' | ') ?: 'Sin horario configurado' }}</dd></div>
    <div><dt>Estatus</dt><dd>{{ $room->status === 'active' ? 'Activo' : 'Inactivo' }}</dd></div>
  </dl>
</x-operational-detail>
