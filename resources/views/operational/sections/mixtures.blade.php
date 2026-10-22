@push('styles')
  <link rel="stylesheet" href="{{ asset('css/operational-mixtures.css') }}?v={{ filemtime(public_path('css/operational-mixtures.css')) }}">
@endpush
@push('scripts')
  <script src="{{ asset('js/unit-request-interactions.js') }}?v={{ filemtime(public_path('js/unit-request-interactions.js')) }}" defer></script>
  <script src="{{ asset('js/operational-mixtures.js') }}?v={{ filemtime(public_path('js/operational-mixtures.js')) }}" defer></script>
@endpush
@php
  $mixRows = $providerRequests->whereIn('request_type', ['chemo', 'chemotherapy'])
      ->when($section === 'mixes', fn ($rows) => $rows->whereNotNull('required_at')->whereNotIn('status', \App\Support\OncologyMixtureBoard::CLOSED))
      ->when($section === 'mix-history', fn ($rows) => $rows->whereIn('status', \App\Support\OncologyMixtureBoard::CLOSED));
  $mixTitle = match ($section) { 'mixes' => 'Mezclas programadas', 'mix-history' => 'Historial de mezclas', default => 'Lista de solicitudes' };
@endphp
<section class="operational-native-table-card operational-mixtures-board" data-oncology-mixtures-board data-unit-request-board data-mixtures-filter="{{ $selectedMixFilter }}">
  <div class="operational-native-table-heading">
    <div>
      <h2>{{ $mixTitle }}</h2>
      <span>Central de mezclas · Solo mezclas oncológicas · {{ $mixRows->count() }} solicitudes</span>
    </div>
    <div class="operational-heading-actions">
    <label class="operational-mixtures-search"><span class="sr-only">Buscar mezcla, solicitud o paciente</span><input type="search" data-mixtures-search placeholder="Buscar solicitud o paciente"></label>
      <button type="button" class="operational-secondary-button" data-mixtures-export>Exportar CSV</button>
      <a class="operational-primary-button" href="{{ route('operational.dashboard', ['area' => 'oncology', 'section' => 'service-create', 'unit' => $contextUnit?->id]) }}">+ Agregar solicitud</a>
    </div>
  </div>
  <div class="operational-native-table-scroll">
    <table class="operational-native-table operational-mixtures-table" data-drsam-table-filter-empty>
      <caption class="sr-only">Estatus de mezclas oncológicas</caption>
      <thead><tr><th>Tipo</th><th>ID mezcla</th><th>No. solicitud</th><th>Institución</th><th>Hospital</th><th>Paciente</th><th>Fecha y hora de solicitud</th><th>Fecha y hora programada de entrega</th><th>Lote</th><th data-drsam-table-filter-skip-column>Ver</th><th>Mensajes</th><th>Aprobación</th><th>Ajustes</th><th>Estado de proceso</th></tr></thead>
      <tbody>
        @foreach ($mixRows as $item)
          @php
            $folio = $item->external_id ?: (string) $item->id;
            $category = \App\Support\OncologyMixtureBoard::category($item);
            [$approval, $approvalClass] = \App\Support\OncologyMixtureBoard::approval($item);
            $lot = data_get($item->payload, 'lot') ?? data_get($item->payload, 'batch') ?? data_get($item->payload, 'lot_number') ?? 'Por asignar';
          @endphp
          <tr data-mixture-row data-category="{{ $category }}" data-closed="{{ in_array($item->status, \App\Support\OncologyMixtureBoard::CLOSED, true) ? 'true' : 'false' }}"
              data-interaction-request="{{ $item->id }}" data-interaction-url="{{ route('operational.mixtures.interactions.index', $item) }}"
              data-request-folio="{{ $folio }}" data-message-count="{{ $item->message_count }}" data-adjustment-count="{{ $item->open_adjustment_count }}">
            <td><span class="mixture-badge is-oncology">Oncológica</span>@if (data_get($item->payload, 'demo'))<small class="mixture-demo">Demo</small>@endif</td>
            <td>{{ $item->id }}</td><td><strong>{{ $folio }}</strong></td>
            <td>{{ $item->medicalUnit?->institution?->name ?? 'Sin institución' }}</td><td>{{ $item->medicalUnit?->name ?? 'Sin hospital' }}</td>
            <td><strong>{{ $item->patient?->full_name ?? 'Sin paciente' }}</strong></td>
            <td>{{ $item->requested_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</td><td>{{ $item->required_at?->format('d/m/Y H:i') ?? 'Por programar' }}</td>
            <td>{{ $lot }}</td>
            <td><button type="button" class="operational-view-button" data-mixture-detail="mixture-detail-{{ $item->id }}" aria-label="Ver solicitud {{ $folio }}">Ver</button></td>
            <td><div class="unit-request-message-actions">
              <button type="button" class="unit-request-message-button {{ $item->message_count ? 'has-messages' : '' }}" data-request-interaction-open="message" aria-haspopup="dialog" aria-label="Ver mensajes de {{ $folio }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h16v12H9l-5 4Z"/><path d="M8 8h8M8 12h5"/></svg><span data-request-message-count @if (!$item->message_count) hidden @endif>{{ $item->message_count }}</span></button>
              <button type="button" class="unit-request-compose-button" data-request-interaction-open="message" data-request-compose aria-haspopup="dialog" aria-label="Escribir mensaje de {{ $folio }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4Z M22 2 11 13"/></svg></button>
            </div></td>
            <td><span class="mixture-badge is-{{ $approvalClass }}">{{ $approval }}</span></td>
            <td><button type="button" class="unit-request-adjustment-button {{ $item->open_adjustment_count ? 'has-adjustments' : '' }}" data-request-interaction-open="adjustment" aria-haspopup="dialog" aria-label="Ver ajustes de {{ $folio }}"><span data-request-adjustment-label>{{ $item->open_adjustment_count ? 'En ajuste ('.$item->open_adjustment_count.')' : 'Sin ajustes' }}</span></button></td>
            <td><span class="mixture-badge is-{{ $category }}">{{ \App\Support\OncologyMixtureBoard::statusLabel($item) }}</span></td>
          </tr>
        @endforeach
        <tr data-mixtures-empty @if ($mixRows->isNotEmpty()) hidden @endif><td colspan="14">No hay mezclas oncológicas con estos filtros.</td></tr>
      </tbody>
    </table>
  </div>
  <footer class="operational-mixtures-footer"><span data-mixtures-visible aria-live="polite">{{ $mixRows->count() }} solicitudes visibles</span><span>Desliza para consultar todas las columnas</span></footer>
</section>
@foreach ($mixRows as $item)
  <dialog class="operational-mixture-detail" id="mixture-detail-{{ $item->id }}" aria-labelledby="mixture-title-{{ $item->id }}">
    <header><div><small>Central de mezclas · Oncológica</small><h2 id="mixture-title-{{ $item->id }}">{{ $item->external_id ?: $item->id }}</h2></div><button type="button" data-mixture-detail-close aria-label="Cerrar detalle">Cerrar <span aria-hidden="true">×</span></button></header>
    <dl>
      <div><dt>Paciente</dt><dd>{{ $item->patient?->full_name ?? 'Sin paciente' }}</dd></div>
      <div><dt>Hospital</dt><dd>{{ $item->medicalUnit?->name ?? 'Sin hospital' }}</dd></div>
      <div><dt>Estado de proceso</dt><dd>{{ \App\Support\OncologyMixtureBoard::statusLabel($item) }}</dd></div>
      <div><dt>Entrega programada</dt><dd>{{ $item->required_at?->format('d/m/Y H:i') ?? 'Por programar' }}</dd></div>
      <div><dt>Servicio</dt><dd>{{ data_get($item->payload, 'service', 'Quimioterapia') }}</dd></div>
      <div><dt>Médico responsable</dt><dd>{{ data_get($item->payload, 'doctor', 'Por asignar') }}</dd></div>
      <div><dt>Observaciones</dt><dd>{{ data_get($item->payload, 'notes', 'Sin observaciones') }}</dd></div>
    </dl>
    <h3>Seguimiento</h3>
    <ol>@forelse ($item->statusEvents as $event)<li>{{ $event->occurred_at?->format('d/m/Y H:i') }} · {{ $statusText($event->status) }}</li>@empty<li>Sin movimientos registrados.</li>@endforelse</ol>
  </dialog>
@endforeach
@include('unit._request-interactions')
