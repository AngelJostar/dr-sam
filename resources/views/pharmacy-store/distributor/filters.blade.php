<form method="GET" class="distributor-filters" role="search">
    @if(!empty($columnFilters['selected']))<input type="hidden" name="columns" value="{{ json_encode($columnFilters['selected']) }}">@endif
    <label class="distributor-search">@include('pharmacy-store.distributor.icon', ['icon' => 'search'])<span class="distributor-sr-only">Buscar producto o presentación</span><input type="search" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="200" placeholder="Buscar producto o presentación"></label>
    <label><span class="distributor-sr-only">Existencias</span><select name="stock" aria-label="Existencias"><option value="all">Existencias: todas</option>@foreach(['available' => 'Con existencias', 'empty' => 'Agotadas', 'low' => 'Existencias bajas (1–5)'] as $key => $label)<option value="{{ $key }}" @selected(($filters['stock'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
    <label><span class="distributor-sr-only">Estado</span><select name="status" aria-label="Estado"><option value="all">Estado: todos</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Activo</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactivo</option></select></label>
    <button class="distributor-button secondary" type="submit">Filtrar</button>
    @if(request()->filled('q') || request()->filled('stock') || request()->filled('status'))<a class="distributor-reset" href="{{ url()->current() }}">Limpiar</a>@endif
</form>
