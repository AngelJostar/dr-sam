@php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'record' => ['Expediente', 'record'],
    'active' => ['Activos', 'active'],
    'inactive' => ['Inactivos', 'inactive'],
    'pre-admission' => ['Preingreso', 'pending'],
    'admissions' => ['Admisiones', 'patients'],
    'transfers-discharges' => ['Traslados/Altas', 'transfer'],
  ];
@endphp

@include('unit._catalog_carousel', ['carouselMenu' => 'patients', 'carouselFilter' => 'status', 'catalogCarouselItems' => $catalogCarouselItems])
