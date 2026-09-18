<div class="institution-catalog-toolbar institution-service-filter-toolbar"
     data-service-filter-scope="{{ $filterScope }}">
  <div class="institution-catalog-filter-buttons" role="group" aria-label="Filtrar registros del servicio">
    <button type="button" class="is-active" data-service-row-filter="all" aria-pressed="true">
      <span>Todos</span>
      <b>{{ $totalCount }}</b>
    </button>
    <button type="button" data-service-row-filter="active" aria-pressed="false">
      <span>Activos</span>
      <b>{{ $activeCount }}</b>
    </button>
    <button type="button" data-service-row-filter="inactive" aria-pressed="false">
      <span>Inactivos</span>
      <b>{{ $inactiveCount }}</b>
    </button>
  </div>

  <div class="institution-catalog-toolbar-actions">
    <label class="institution-catalog-search">
      <span class="sr-only">{{ $searchPlaceholder }}</span>
      <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
      <input type="search" data-service-row-search placeholder="{{ $searchPlaceholder }}">
    </label>
    <button type="button" class="institution-catalog-primary-action" data-service-create-open>
      <span aria-hidden="true">+</span> Nuevo servicio
    </button>
  </div>
</div>
