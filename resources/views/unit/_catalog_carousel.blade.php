<section class="unit-service-carousel-card unit-catalog-carousel-card" data-unit-catalog-navigation data-unit-carousel-menu="{{ $carouselMenu }}" data-unit-carousel-filter-kind="{{ $carouselFilter }}">
  <button type="button" class="unit-service-carousel-arrow" data-unit-catalog-carousel-prev aria-label="Catalogo anterior">&lsaquo;</button>
  <div class="unit-service-carousel unit-catalog-carousel" data-unit-catalog-carousel role="group" aria-label="Filtros del menú">
    @foreach ($catalogCarouselItems as $filterValue => [$catalogLabel, $catalogIcon])
      <button type="button"
              @class(['unit-service-carousel-button', 'unit-catalog-carousel-button', 'is-active' => $filterValue === 'all'])
              data-unit-carousel-filter="{{ $filterValue }}"
              aria-pressed="{{ $filterValue === 'all' ? 'true' : 'false' }}">
        <span class="unit-service-carousel-icon" aria-hidden="true">
          @switch($catalogIcon)
            @case('catalog')
              <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
              @break
            @case('active')
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
              @break
            @case('pending')
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
              @break
            @case('inactive')
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M5.6 5.6 18.4 18.4"/></svg>
              @break
            @case('users')
              <svg viewBox="0 0 24 24"><path d="M16 21a6 6 0 0 0-12 0"/><circle cx="10" cy="8" r="4"/><path d="M22 21a5 5 0 0 0-4-4.9M17 4.3a4 4 0 0 1 0 7.4"/></svg>
              @break
            @case('patients')
              <svg viewBox="0 0 24 24"><path d="M19 21a7 7 0 0 0-14 0"/><circle cx="12" cy="8" r="4"/><path d="M12 14v4M10 16h4"/></svg>
              @break
            @case('doctors')
              <svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg>
              @break
            @case('specialties')
              <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3A1.7 1.7 0 0 0 14 21v.2h-4V21a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14h-.2v-4H3a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1a1.7 1.7 0 0 0 1.9.3A1.7 1.7 0 0 0 10 3v-.2h4V3a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.2v4H21a1.7 1.7 0 0 0-1.6 1Z"/></svg>
              @break
            @case('pharmacy')
              <svg viewBox="0 0 24 24"><path d="M5 4h6a4 4 0 0 1 0 8H5V4Z"/><path d="M5 20V4M10 12l7 8M18 14l-6 6"/></svg>
              @break
            @case('areas')
              <svg viewBox="0 0 24 24"><path d="M4 20h16"/><path d="M7 20V8h4v12M13 20V4h4v16"/><path d="M9 12h.01M15 8h.01"/></svg>
              @break
            @default
              <svg viewBox="0 0 24 24"><path d="m10.5 20.5-7-7a5 5 0 0 1 7-7l7 7a5 5 0 0 1-7 7Z"/><path d="m8.5 11.5 7-7a5 5 0 0 1 7 7l-7 7"/><path d="m7 17 10-10"/></svg>
          @endswitch
        </span>
        <strong>{{ $catalogLabel }}</strong>
      </button>
    @endforeach
  </div>
  <button type="button" class="unit-service-carousel-arrow" data-unit-catalog-carousel-next aria-label="Catalogo siguiente">&rsaquo;</button>
</section>

<script>
  (() => {
    const initialize = () => {
      const navigation = document.querySelector('[data-unit-catalog-navigation]');
      if (!navigation) return;
      const carousel = navigation.querySelector('[data-unit-catalog-carousel]');
      const upperButtons = [...navigation.querySelectorAll('[data-unit-carousel-filter]')];
      const lowerSelector = {
        status: '[data-unit-catalog-status]',
        group: '[data-unit-catalog-group]',
        procedure: '[data-procedure-category]',
      }[navigation.dataset.unitCarouselFilterKind];
      const lowerButtons = [...document.querySelectorAll(lowerSelector)];
      const valueOf = (button) => button.dataset.unitCatalogStatus ?? button.dataset.unitCatalogGroup ?? button.dataset.procedureCategory;
      const sync = (value) => upperButtons.forEach((button) => {
        const active = button.dataset.unitCarouselFilter === value;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      upperButtons.forEach((button) => button.addEventListener('click', () => {
        const lower = lowerButtons.find((candidate) => valueOf(candidate) === button.dataset.unitCarouselFilter);
        lower?.click();
        sync(button.dataset.unitCarouselFilter);
      }));
      lowerButtons.forEach((button) => button.addEventListener('click', () => sync(valueOf(button))));
      const initial = lowerButtons.find((button) => button.classList.contains('is-active'));
      if (initial) sync(valueOf(initial));

      navigation.querySelector('[data-unit-catalog-carousel-prev]')?.addEventListener('click', () => carousel.scrollBy({ left: -320, behavior: 'smooth' }));
      navigation.querySelector('[data-unit-catalog-carousel-next]')?.addEventListener('click', () => carousel.scrollBy({ left: 320, behavior: 'smooth' }));
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
  })();
</script>
