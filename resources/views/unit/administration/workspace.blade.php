@push('styles')
  <link rel="stylesheet" href="{{ asset('css/unit-administration.css') }}?v={{ filemtime(public_path('css/unit-administration.css')) }}">
@endpush
@php
  $demoState = \App\Http\Controllers\Unit\AdministrationDemoController::publicState($unit, \App\Support\UnitAdministrationDemo::state($unit));
@endphp
<div class="unit-administration" data-administration-workspace>
  <section class="unit-service-panel-header unit-catalog-info-card admin-heading">
    <div><h2 id="unit-management-title">{{ $managementContent['label'] }}</h2><p data-admin-description>{{ $managementContent['description'] }}</p><div class="unit-service-panel-badges"><span>Administración</span><span class="admin-demo">Demo</span></div></div>
  </section>
  <div class="admin-filter-buttons" data-admin-filter-buttons role="group" aria-label="Filtrar {{ $managementContent['label'] }}"></div>
  <div class="admin-toolbar">
    <div class="admin-filters" data-admin-filters></div>
    <div class="admin-actions" data-admin-primary-actions></div>
  </div>
  <div class="admin-notice" role="status" data-admin-notice hidden></div>
  <div data-admin-content></div>
  <p class="admin-footnote">Datos de demostración · Importes en MXN · Los cambios se conservan en la demo de esta unidad.</p>
  <noscript>Activa JavaScript para utilizar los formularios de Administración.</noscript>
</div>
<dialog class="admin-dialog unit-administration" data-admin-dialog aria-labelledby="admin-dialog-title">
  <header><div><span class="admin-demo">Demo</span><h2 id="admin-dialog-title"></h2></div><button type="button" data-admin-close aria-label="Cerrar ventana" title="Cerrar">&times;</button></header>
  <div data-admin-dialog-content></div>
</dialog>
<script>
  window.unitAdministrationConfig = {{ Illuminate\Support\Js::from([
    'tab' => $managementTab, 'state' => $demoState, 'unitName' => $unit->name,
    'storeUrl' => route('unit.administration-demo.store', ['unit' => $unit->id]),
    'stateUrl' => route('unit.administration-demo.show', ['unit' => $unit->id]),
    'invoiceBase' => url('unit/administration-demo/invoices'), 'unit' => $unit->id,
    'baseUrl' => route('unit.dashboard', ['unit' => $unit->id, 'section' => 'administration']),
    'csrf' => csrf_token(), 'today' => now()->toDateString(), 'insurerFilter' => request()->string('insurer')->toString(),
  ]) }};
</script>
<script src="{{ asset('js/unit-administration.js') }}?v={{ filemtime(public_path('js/unit-administration.js')) }}" defer></script>
