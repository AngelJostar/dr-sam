@php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'active' => ['Activos', 'active'],
    'inactive' => ['Inactivos', 'inactive'],
  ];
@endphp

@include('unit._catalog_carousel', ['carouselMenu' => 'patients', 'carouselFilter' => 'status', 'catalogCarouselItems' => $catalogCarouselItems])
