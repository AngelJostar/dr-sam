@php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'operation' => ['Operación', 'areas'],
    'clinical' => ['Clínicos', 'doctors'],
    'pharmacy' => ['Farmacia', 'pharmacy'],
  ];
@endphp

@include('unit._catalog_carousel', ['carouselMenu' => 'catalog', 'carouselFilter' => 'group', 'catalogCarouselItems' => $catalogCarouselItems])
