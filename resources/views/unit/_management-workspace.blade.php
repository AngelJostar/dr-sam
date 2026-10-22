@php
  $managementSections = $section === 'administration' ? [
    'accounts' => [
      'label' => 'Cuentas hospitalarias',
      'description' => 'Cargos, pagos, saldos y cobertura hospitalaria.',
      'icon' => 'M5 3h14v18H5ZM8 7h8M8 11h8M8 15h5',
    ],
    'billing' => [
      'label' => 'Cobros y facturas',
      'description' => 'Cobros, comprobantes de pago y facturación de la unidad.',
      'icon' => 'M3 5h18v14H3ZM3 9h18M6 15h4',
    ],
    'insurers' => [
      'label' => 'Aseguradoras',
      'description' => 'Aseguradoras, convenios y coberturas hospitalarias.',
      'icon' => 'm12 3 8 3v5c0 5-4 8-8 10-4-2-8-5-8-10V6ZM8 12l3 3 5-6',
    ],
    'authorizations' => [
      'label' => 'Autorizaciones',
      'description' => 'Solicitudes, documentos y seguimiento de autorizaciones de cobertura.',
      'icon' => 'M14 3H5v18h14V8ZM14 3v5h5M8 12h8M8 16h5',
    ],
  ] : [
    'staff' => [
      'label' => 'Personal y turnos',
      'description' => 'Asignación operativa de profesionales y cobertura de servicios.',
      'icon' => 'M3 21v-3a5 5 0 0 1 10 0v3M16 14a5 5 0 0 1 5 5v2M8 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8M16 3a4 4 0 0 1 0 8',
    ],
    'units-services' => [
      'label' => 'Unidades y servicios',
      'description' => 'Organización de unidades, áreas y servicios hospitalarios.',
      'icon' => 'M3 21h18M5 21V9h6v12M11 21V3h8v18M7 12h2M7 16h2M14 7h2M14 11h2M14 15h2',
    ],
    'maintenance' => [
      'label' => 'Mantenimiento',
      'description' => 'Seguimiento de mantenimiento e incidencias de infraestructura y equipos.',
      'icon' => 'm14 6 4 4 3-3a6 6 0 0 1-8 7L6 21a2 2 0 0 1-3-3l7-7a6 6 0 0 1 7-8Z',
    ],
    'users-permissions' => [
      'label' => 'Usuarios y permisos',
      'description' => 'Usuarios, perfiles de acceso y permisos de la unidad.',
      'icon' => 'M5 21v-3a7 7 0 0 1 14 0v3ZM12 3a4 4 0 1 0 0 8 4 4 0 0 0 0-8',
    ],
  ];
  $managementTab = request()->query('tab', array_key_first($managementSections));
  if (!is_string($managementTab) || !array_key_exists($managementTab, $managementSections)) {
    $managementTab = array_key_first($managementSections);
  }
  $managementContent = $managementSections[$managementTab];
@endphp

<section class="unit-service-carousel-card unit-catalog-carousel-card" data-carousel-style="compact" data-unit-management-carousel aria-label="{{ $sectionLabels[$section] }} de la unidad">
  <button type="button" class="unit-service-carousel-arrow" data-management-prev aria-label="Anterior">&lsaquo;</button>
  <nav class="unit-service-carousel unit-catalog-carousel" aria-label="Secciones de {{ $sectionLabels[$section] }}">
    @foreach ($managementSections as $key => $item)
      <a @class(['unit-service-carousel-button', 'unit-catalog-carousel-button', 'is-active' => $managementTab === $key])
         href="{{ route('unit.dashboard', ['unit' => $unit->id, 'section' => $section, 'tab' => $key]) }}"
         @if ($managementTab === $key) aria-current="page" @endif>
        <span class="unit-service-carousel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="{{ $item['icon'] }}"/></svg></span>
        <strong>{{ $item['label'] }}</strong>
      </a>
    @endforeach
  </nav>
  <button type="button" class="unit-service-carousel-arrow" data-management-next aria-label="Siguiente">&rsaquo;</button>
</section>

@if ($section === 'administration')
  @include('unit.administration.workspace')
@else
<section class="unit-service-panel-header unit-catalog-info-card" aria-labelledby="unit-management-title">
  <div>
    <h2 id="unit-management-title">{{ $managementContent['label'] }}</h2>
    <p>{{ $managementContent['description'] }}</p>
    <div class="unit-service-panel-badges"><span>{{ $sectionLabels[$section] }}</span></div>
  </div>
</section>
@endif

<script>
  (() => {
    const carousel = document.querySelector('[data-unit-management-carousel]');
    const track = carousel.querySelector('.unit-service-carousel');
    const scroll = direction => track.scrollBy({ left: direction * 320, behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
    carousel.querySelector('[data-management-prev]').addEventListener('click', () => scroll(-1));
    carousel.querySelector('[data-management-next]').addEventListener('click', () => scroll(1));
  })();
</script>
