@php $requestVolume = data_get($item->payload, 'clinical_format.total_volume', data_get($item->payload, 'volume')); @endphp
<x-operational-detail :id="'request-information-'.$item->id" :title="'Solicitud '.($item->external_id ?: $item->id)" :context="$item->patient?->full_name ?? 'Sin paciente'" :label="$requestViewLabel ?? 'Ver'" :accessible-label="($requestViewLabel ?? 'Ver').' '.($item->external_id ?: $item->id)" :wide="true">
  <dl>
    <div><dt>Servicio</dt><dd>{{ data_get($item->payload, 'service', 'Sin servicio') }}</dd></div>
    <div><dt>Médico responsable</dt><dd>{{ data_get($item->payload, 'doctor', 'Sin médico asignado') }}</dd></div>
    <div><dt>Fecha de solicitud</dt><dd>{{ $item->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</dd></div>
    <div><dt>Entrega programada</dt><dd>{{ $item->required_at?->format('d/m/Y H:i') ?? 'Por programar' }}</dd></div>
    <div><dt>Volumen total</dt><dd>{{ filled($requestVolume) ? $requestVolume.' ml' : 'Sin registro' }}</dd></div>
    <div><dt>Estado</dt><dd>{{ $statusText($item->status) }}</dd></div>
    <div class="span-2"><dt>Diagnóstico</dt><dd>{{ data_get($item->payload, 'diagnosis') ?: 'Sin diagnóstico registrado' }}</dd></div>
    @if (data_get($item->payload, 'prescription_code'))<div><dt>Receta de Consulta Externa</dt><dd>{{ data_get($item->payload, 'prescription_code') }}</dd></div>@endif
    @if ($assignment = data_get($item->payload, 'infusion_assignment'))
      <div><dt>Sala / sillón o cama</dt><dd>{{ data_get($assignment, 'room_number') }} · {{ data_get($assignment, 'seat_label') }}</dd></div>
      <div><dt>Fecha y hora de aplicación</dt><dd>{{ data_get($assignment, 'application_date') }} {{ data_get($assignment, 'starts_at') }}</dd></div>
    @endif
    <div class="span-2"><dt>Observaciones</dt><dd>{{ data_get($item->payload, 'notes') ?: 'Sin observaciones registradas' }}</dd></div>
  </dl>
  @php $requestMedications = collect(data_get($item->payload, 'mixture_medications') ?: data_get($item->payload, 'prescription_items') ?: data_get($item->payload, 'integration_items', [])); @endphp
  @if ($requestMedications->isNotEmpty())
    <h3>Medicamentos</h3>
    <div class="operational-native-table-scroll"><table class="operational-native-table"><thead><tr><th>Medicamento</th><th>Dosis</th><th>Vía</th><th>Indicaciones</th></tr></thead><tbody>
      @foreach ($requestMedications as $medication)<tr><td>{{ data_get($medication, 'medication_name') ?: data_get($medication, 'name', 'Sin nombre') }}</td><td>{{ data_get($medication, 'dose', 'Sin registro') }}</td><td>{{ data_get($medication, 'route', 'Sin registro') }}</td><td>{{ data_get($medication, 'instructions', 'Sin indicaciones') }}</td></tr>@endforeach
    </tbody></table></div>
  @endif
</x-operational-detail>
