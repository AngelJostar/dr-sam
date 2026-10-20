@php
  $carouselSelected = $carouselSelected ?? 'all';
  $carouselParameter = $carouselParameter ?? null;
@endphp
<nav class="superadmin-filter-carousel" aria-label="{{ $carouselLabel ?? 'Filtros del menú' }}" data-admin-carousel>
  <button type="button" class="superadmin-filter-arrow" data-admin-carousel-prev aria-label="Filtros anteriores" disabled>&lsaquo;</button>
  <div class="superadmin-filter-track" data-admin-carousel-track>
    @foreach ($carouselOptions as $value => $label)
      @php
        $selected = in_array((string) $value, $carouselSelectedValues ?? [$carouselSelected], true);
        $displayLabel = $carouselShortLabels[$value] ?? $label;
      @endphp
      @if ($carouselParameter)
        <a href="{{ request()->fullUrlWithQuery(array_merge(['page' => null, 'selected_module' => null, 'selected_institution' => null], $carouselQueries[$value] ?? [$carouselParameter => $value])) }}"
           @class(['superadmin-filter-tile', 'is-active' => $selected]) data-admin-filter="{{ $value }}"
           @if ($selected) aria-current="true" @endif title="{{ $label }}" aria-label="{{ $label }}">
      @else
        <button type="button" @class(['superadmin-filter-tile', 'is-active' => $selected])
                data-admin-filter="{{ $value }}" aria-pressed="{{ $selected ? 'true' : 'false' }}" title="{{ $label }}">
      @endif
        <span class="superadmin-filter-icon"><x-klini-icon :name="$carouselIcons[$value] ?? \Illuminate\Support\Str::ascii($label)" /></span>
        <span class="superadmin-filter-label">{{ $displayLabel }}</span>
        <span class="superadmin-filter-selected" aria-hidden="true">✓</span>
      @if ($carouselParameter) </a> @else </button> @endif
    @endforeach
  </div>
  <button type="button" class="superadmin-filter-arrow" data-admin-carousel-next aria-label="Filtros siguientes" disabled>&rsaquo;</button>
  <div class="superadmin-filter-indicators" aria-hidden="true">
    @foreach ($carouselOptions as $value => $label)
      <span @class(['is-active' => in_array((string) $value, $carouselSelectedValues ?? [$carouselSelected], true)]) data-admin-filter-dot="{{ $value }}"></span>
    @endforeach
  </div>
</nav>
