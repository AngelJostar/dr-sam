@if(!empty($columnFilters['selected']))
    <div class="distributor-column-summary" role="status">
        <span>{{ count($columnFilters['selected']) }} {{ count($columnFilters['selected']) === 1 ? 'columna filtrada' : 'columnas filtradas' }}</span>
        <a class="distributor-reset" href="{{ request()->fullUrlWithoutQuery(['columns', 'page']) }}">Limpiar filtros de columnas</a>
    </div>
@endif
