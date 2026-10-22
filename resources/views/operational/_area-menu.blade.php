<nav class="operational-native-menu operational-area-menu">
  @foreach (\App\Support\UnitAreaCatalog::navigation() as $type => $definition)
    <a href="{{ \App\Support\UnitAreaCatalog::operationUrl($type, $menuUnit?->id) }}" @class(['is-active' => ($activeArea ?? '') === $type])>
      <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 20h16M7 20V8h4v12M13 20V4h4v16M9 12h.01M15 8h.01"/></svg></span>
      <span class="operational-area-label">{{ $definition['label'] }}</span>
    </a>
  @endforeach
  <a href="{{ route('operational.patients.index', $menuUnit ? ['unit' => $menuUnit->id] : []) }}" @class(['is-active' => ($activeArea ?? '') === 'patients'])><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><span class="operational-area-label">Catálogo de pacientes</span></a>
</nav>
