<section class="import-analytics">
  <header><h2>Reportes y analíticas</h2><p>Información para la toma de decisiones · Datos de demostración</p></header>
  <div class="import-analytics-grid">
    <article><h3>Expedientes por estatus</h3><div class="import-donut-layout">
      <div class="import-donut" role="img" aria-label="28 expedientes: 10 en documentación, 6 permiso Cofepris, 8 en tránsito, 3 entregados y 1 cancelado"><span><b>28</b><small>Total</small></span></div>
      <ul><li><i style="background:#72a9ea"></i>En documentación <b>10</b></li><li><i style="background:#ffcc63"></i>Permiso Cofepris <b>6</b></li><li><i style="background:#008e87"></i>En tránsito <b>8</b></li><li><i style="background:#78cdbb"></i>Entregado <b>3</b></li><li><i style="background:#ff626f"></i>Cancelado <b>1</b></li></ul>
    </div></article>
    <article><h3>Importaciones por mes</h3><div class="import-month-bars" role="img" aria-label="Importaciones: mayo 5, junio 7, julio 9, agosto 10, septiembre 12">
      @foreach (['May' => 5, 'Jun' => 7, 'Jul' => 9, 'Ago' => 10, 'Sep' => 12] as $month => $total)<div><b>{{ $total }}</b><span style="height: {{ $total * 8 }}px"></span><small>{{ $month }}</small></div>@endforeach
    </div></article>
  </div>
</section>
