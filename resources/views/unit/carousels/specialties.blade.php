@php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'active' => ['Activas', 'active'],
    'inactive' => ['Inactivas', 'inactive'],
  ];
@endphp

@include('unit._catalog_carousel', ['carouselMenu' => 'specialties', 'carouselFilter' => 'status', 'catalogCarouselItems' => $catalogCarouselItems])
