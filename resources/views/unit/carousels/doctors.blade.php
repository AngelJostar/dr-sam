@php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'active' => ['Activos', 'active'],
    'pending' => ['Pendientes', 'pending'],
    'inactive' => ['Inactivos', 'inactive'],
  ];
@endphp

@include('unit._catalog_carousel', ['carouselMenu' => 'doctors', 'carouselFilter' => 'status', 'catalogCarouselItems' => $catalogCarouselItems])
