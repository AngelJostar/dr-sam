@php
  $entryColumns = ['Expediente', 'ID hospitalización', 'Nombre completo', 'Fecha de nacimiento', 'Tutor responsable', 'Fecha de ingreso', 'Procedencia', 'Motivo / diagnóstico de ingreso', 'Médico responsable', 'Indicación de egreso', 'Médico que autoriza egreso', 'Fecha de salida de '.$area['label'], 'Traslado a área', 'Días de estancia en '.$area['label']];
@endphp
<div class="operational-native-table-scroll">
  <table class="operational-native-table operational-entry-table" aria-label="Registro de entrada de {{ $area['label'] }}">
    <thead><tr>@foreach ($entryColumns as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead>
    <tbody>
      @forelse ($entries as $entry)
        @php
          $metadata = $entry->metadata ?? [];
          $patient = $entry->patient;
          $stayDays = $entry->admitted_at ? max(0, (int) $entry->admitted_at->copy()->startOfDay()->diffInDays(($entry->discharged_at ?? now())->copy()->startOfDay(), false)) : null;
        @endphp
        <tr data-entry-id="{{ $entry->id }}">
          <td>{{ data_get($patient->metadata, 'record_number') ?: $patient->platform_number ?: 'Sin expediente' }}</td>
          <td>{{ $metadata['folio'] ?? $entry->id }}</td>
          <td>{{ $patient->full_name }}@if ($metadata['demo'] ?? false)<small class="operational-entry-note">Demostración</small>@endif</td>
          <td>{{ $patient->birth_date?->format('d/m/Y') ?? 'Sin fecha' }}</td>
          <td>
            @if (! empty($metadata['guardian']))
              <x-operational-detail :id="'entry-guardian-'.$entry->id" title="Tutor responsable" :context="$patient->full_name" :accessible-label="'Ver tutor responsable de '.$patient->full_name">
                <p>{{ $metadata['guardian'] }}</p>
                @if (! empty($metadata['guardian_relationship']))<p><strong>Parentesco:</strong> {{ $metadata['guardian_relationship'] }}</p>@endif
              </x-operational-detail>
            @else Sin registro @endif
          </td>
          <td>{{ $entry->admitted_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
          <td>{{ $metadata['origin'] ?? 'Sin registro' }}</td>
          <td>
            @if ($entry->admission_diagnosis || $entry->reason)
              <x-operational-detail :id="'entry-diagnosis-'.$entry->id" title="Motivo / diagnóstico de ingreso" :context="$patient->full_name" :accessible-label="'Ver motivo de ingreso de '.$patient->full_name">
                @if ($entry->admission_diagnosis)<p>{{ $entry->admission_diagnosis }}</p>@endif
                @if ($entry->reason && $entry->reason !== $entry->admission_diagnosis)<p>{{ $entry->reason }}</p>@endif
              </x-operational-detail>
            @else Sin registro @endif
          </td>
          <td>{{ $entry->doctor?->full_name ?? $metadata['responsible_doctor'] ?? 'Sin asignar' }}</td>
          <td>
            @if (! empty($metadata['discharge_indication']))
              <x-operational-detail :id="'entry-discharge-'.$entry->id" title="Indicación de egreso" :context="$patient->full_name" :accessible-label="'Ver indicación de egreso de '.$patient->full_name">
                <p>{{ $metadata['discharge_indication'] }}</p>
              </x-operational-detail>
            @else {{ $entry->discharged_at ? 'Sin registro' : 'En estancia' }} @endif
          </td>
          <td>{{ $metadata['discharge_doctor'] ?? ($entry->discharged_at ? 'Sin registro' : 'Pendiente') }}</td>
          <td>{{ $entry->discharged_at?->format('d/m/Y H:i') ?? 'En estancia' }}</td>
          <td>{{ $metadata['transfer_area'] ?? 'Sin traslado' }}</td>
          <td data-stay-days="{{ $stayDays }}">{{ $stayDays === null ? 'Sin fecha de ingreso' : $stayDays.' '.($stayDays === 1 ? 'día' : 'días') }}</td>
        </tr>
      @empty
        <tr><td colspan="14">Aún no hay registros de entrada en {{ $area['label'] }}.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
