@php
  $catalogCarouselItems = ['all' => ['Catálogo', 'catalog']]
      + collect(\App\Support\UnitAreaCatalog::navigation())->map(fn ($area) => [$area['label'], $area['icon']])->all();
@endphp

@include('unit._catalog_carousel', ['carouselMenu' => 'procedure-areas', 'carouselFilter' => 'procedure', 'catalogCarouselItems' => $catalogCarouselItems])
