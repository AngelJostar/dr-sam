<section class="unit-service-carousel-card unit-catalog-carousel-card" data-carousel-style="compact" data-unit-profile-carousel aria-label="Perfil de la unidad">
  <button type="button" class="unit-service-carousel-arrow" data-profile-prev aria-label="Anterior">&lsaquo;</button>
  <div class="unit-service-carousel unit-catalog-carousel" role="group" aria-label="Secciones del perfil">
    @foreach ($profileSections as $key => [$label, $icon])
      <button type="button" @class(['unit-service-carousel-button', 'unit-catalog-carousel-button', 'is-active' => $key === 'general']) data-profile-section="{{ $key }}" aria-pressed="{{ $key === 'general' ? 'true' : 'false' }}">
        <span class="unit-service-carousel-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="{{ $icon }}"/></svg></span>
        <strong>{{ $label }}</strong>
      </button>
    @endforeach
  </div>
  <button type="button" class="unit-service-carousel-arrow" data-profile-next aria-label="Siguiente">&rsaquo;</button>
</section>
@push('scripts')
  <script src="{{ asset('js/unit-profile.js') }}?v={{ filemtime(public_path('js/unit-profile.js')) }}" defer></script>
@endpush
