<dialog class="expedient-tracking-dialog" data-expedient-tracking>
  <header><h2>♧ &nbsp; Mi pedido de medicamento</h2><button type="button" data-tracking-close aria-label="Cerrar dashboard">×</button></header>
  <div class="tracking-identity"><div><small>Paciente</small><strong>Pendiente de vincular</strong></div><div><small>Medicamento</small><strong data-tracking-medicine></strong></div><div><small>Folio</small><strong data-tracking-folio></strong></div><div><strong data-tracking-count></strong><small data-tracking-remaining></small></div><b class="tracking-percent" data-tracking-percent></b></div>
  <div class="flight-route" data-flight-route role="progressbar" aria-label="Seguimiento del medicamento" aria-valuemin="1" aria-valuemax="6">
    <div class="flight-route__motion">
      <div class="flight-route__rail"><div class="flight-route__fill"></div></div>
      <div class="flight-route__steps">@for ($step = 1; $step <= 6; $step++)<span class="flight-route__step" data-step="{{ $step }}"></span>@endfor</div>
      <span class="flight-route__plane" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M21.5 16.5v-2l-8-5V4.5a1.5 1.5 0 0 0-3 0v5l-8 5v2l8-2.5v5l-2 1.5V22l3.5-1 3.5 1v-1.5l-2-1.5v-5l8 2.5Z"/></svg></span>
    </div>
    <div class="flight-route__information"><strong data-flight-current></strong><span data-flight-remaining></span></div>
  </div>
  <ol class="tracking-stages" data-tracking-stages></ol>
  <div class="tracking-info-strip"><div><small>Tiempo restante estimado</small><b>Pendiente de confirmar</b></div><div><small>Transportista</small><b>Pendiente de asignar</b></div><div><small>Hospital receptor</small><b>Pendiente de vincular</b></div><div><small>Condiciones de transporte</small><b>Pendiente de confirmar</b></div></div>
  <div class="tracking-detail-grid">
    <section><h3>Última actualización</h3><p data-tracking-current></p><small>Expediente de demostración. Sin fecha confirmada.</small><h3>Próxima acción</h3><p data-tracking-next></p></section>
    <section><h3>Historial de movimientos</h3><ul data-tracking-history></ul></section>
    <section><h3>Documentos</h3><ul class="tracking-document-list">@foreach (['Factura comercial', 'Certificado de análisis', 'Permiso sanitario', 'Guía de embarque'] as $document)<li>{{ $document }} <small>Pendiente</small></li>@endforeach</ul><h3>Ayuda</h3><p>Para conocer la fecha de entrega, consulta al responsable de tu pedido.</p></section>
  </div>
  <footer>Vista de demostración. El porcentaje representa etapas del proceso, no tiempo restante.</footer>
</dialog>
@push('scripts')<script src="{{ asset('js/expedient-tracking.js') }}?v={{ filemtime(public_path('js/expedient-tracking.js')) }}" defer></script>@endpush
