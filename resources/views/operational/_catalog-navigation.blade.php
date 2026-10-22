<section class="operational-oncology-carousel" data-carousel-style="workspace" aria-label="Carrusel de {{ $catalogLabel }}" data-operational-catalog-carousel>
  <button class="operational-oncology-carousel-arrow" type="button" data-area-carousel-prev disabled aria-label="Anterior">‹</button>
  <div class="operational-oncology-carousel-track">
    @foreach ($catalogCarouselItems ?? [['label' => $catalogLabel, 'url' => $catalogUrl, 'active' => true]] as $tile)
    <a @class(['operational-oncology-filter', 'is-active' => $tile['active']]) href="{{ $tile['url'] }}" @if($tile['active']) aria-current="page" @endif>
      <span class="operational-oncology-filter-initial" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="{{ ($tile['icon'] ?? '') === 'calendar' ? 'M4 5h16v16H4ZM8 2v6M16 2v6M4 10h16' : 'M3 3h7v7H3ZM14 3h7v7h-7ZM3 14h7v7H3ZM14 14h7v7h-7Z' }}"/></svg></span>
      <span class="operational-oncology-filter-copy"><strong>{{ $tile['label'] }}</strong>@if($tile['active'])<small aria-label="Seleccionada">✓</small>@endif</span>
    </a>
    @endforeach
  </div>
  <button class="operational-oncology-carousel-arrow" type="button" data-area-carousel-next disabled aria-label="Siguiente">›</button>
  <div class="operational-carousel-indicators" aria-hidden="true">@foreach ($catalogCarouselItems ?? [['active' => true]] as $tile)<span @class(['is-active' => $tile['active']])></span>@endforeach</div>
</section>
<nav class="operational-section-tabs" aria-label="Filtros de {{ $catalogLabel }}">
  @if ($catalogSectionTabs ?? false)
    @foreach ($catalogSectionTabs as $tab)
      <a href="{{ $tab['url'] }}" @class(['is-active' => $tab['active']]) @if($tab['active']) aria-current="page" @endif>{{ $tab['label'] }}</a>
    @endforeach
  @else
  @foreach (['all' => 'Todos', 'active' => 'Activos', 'inactive' => 'Inactivos'] as $status => $label)
    <a href="{{ $catalogUrl }}{{ str_contains($catalogUrl, '?') ? '&' : '?' }}{{ $catalogStatusParameter }}={{ $status }}" @class(['is-active' => $catalogStatus === $status]) @if($catalogStatus === $status) aria-current="page" @endif>{{ $label }}</a>
  @endforeach
  @endif
</nav>
@once
@push('scripts')
  <script src="{{ asset('js/operational-area-carousel.js') }}?v={{ filemtime(public_path('js/operational-area-carousel.js')) }}" defer></script>
@endpush
@endonce
