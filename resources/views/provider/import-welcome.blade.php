<section class="import-welcome">
  <header><div><h2>Bienvenido</h2><p>Resumen de operaciones de importación · Datos de demostración</p></div><time>{{ now()->format('d M Y') }}</time></header>
  <div class="import-kpi-grid">
    @foreach (['Expedientes en curso' => 13, 'En documentación' => 5, 'Permiso Cofepris' => 3, 'En tránsito' => 8] as $label => $total)
      <article><span aria-hidden="true">▤</span><div><h3>{{ $label }}</h3><b>{{ $total }}</b></div></article>
    @endforeach
  </div>
  <div class="import-analytics-grid">
    <article><h3>Estatus de expedientes</h3><div class="import-donut-layout">
      <div class="import-donut" role="img" aria-label="28 expedientes en total"><span><b>28</b><small>Total</small></span></div>
      <ul>@foreach (['En documentación' => 10, 'Permiso Cofepris' => 6, 'En tránsito' => 8, 'Entregado' => 3, 'Cancelado' => 1] as $label => $total)<li>{{ $label }} <b>{{ $total }}</b></li>@endforeach</ul>
    </div></article>
    <article><h3>Actividad reciente</h3><ul class="import-activity-list">
      @foreach ([['Se actualizó expediente IMP-2026-011', 'Tocilizumab 004', 'Hoy 10:24'], ['Permiso Cofepris autorizado', 'IMP-2026-008', 'Hoy 09:18'], ['Llegada a aduana', 'IMP-2026-006', 'Ayer 16:02'], ['Documento cargado', 'IMP-2026-012', 'Ayer 11:47']] as [$activity, $detail, $time])
        <li><span aria-hidden="true">●</span><div>{{ $activity }}<small>{{ $detail }}</small></div><time>{{ $time }}</time></li>
      @endforeach
    </ul></article>
  </div>
</section>
