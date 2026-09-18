@php
  $catalogCarouselItems = [
    'all' => ['Catálogo', 'catalog'],
    'consulting' => ['Consultorios', 'doctors'],
    'infusion' => ['Salas de infusión', 'areas'],
    'operating' => ['Quirófanos', 'areas'],
    'uci' => ['UCI', 'areas'],
    'uti' => ['UTI', 'areas'],
    'recovery' => ['Recuperación', 'areas'],
  ];
@endphp

@include('unit._catalog_carousel', ['carouselMenu' => 'procedure-areas', 'carouselFilter' => 'procedure', 'catalogCarouselItems' => $catalogCarouselItems])
