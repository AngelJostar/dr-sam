@extends('pharmacy-store.distributor.layout')
@section('distributor-content')
<header class="distributor-heading"><div><p class="distributor-eyebrow">Administración / Farmacia digital</p><h1>Catálogo de farmacia digital</h1><p>Selecciona los productos publicados por Klini y registra tu precio y existencias.</p></div></header>
<section class="distributor-card">
    <form method="GET" class="distributor-filters"><label class="distributor-search">Buscar producto o SKU<input name="q" type="search" maxlength="160" value="{{ request('q') }}" placeholder="Nombre, SKU, descripción o categoría"></label><input type="hidden" name="selection" value="{{ request('selection', 'all') }}"><button class="distributor-button secondary">Buscar</button></form>
    @include('pharmacy-store.distributor.column-filter-summary')
    <div class="distributor-table-scroll"><table class="distributor-table"><thead><tr>
        @foreach(['sku' => 'SKU', 'product' => 'Producto y descripción', 'category' => 'Categoría', 'price' => 'Tu precio · MXN', 'stock' => 'Tus existencias', 'selection' => 'Tu oferta'] as $column => $label)<th>@include('pharmacy-store.distributor.column-header', ['column' => $column, 'label' => $label])</th>@endforeach
        <th>Seleccionar</th></tr></thead><tbody>
    @forelse($records as $item)
        <?php $offer = $item->pharmacyOffers->first(); ?>
        <tr><td>{{ $item->sku }}</td><td><strong>{{ $item->name }}</strong><small>{{ $item->description ?: $item->presentation }}</small></td><td>{{ $item->pharmacyCategory() }}</td><td>{{ $offer ? '$'.number_format((float)$offer->price, 2) : 'Por registrar' }}</td><td>{{ $offer ? $offer->stock.' ud.' : '—' }}</td><td><span class="distributor-state {{ $offer?->status === 'active' ? '' : 'muted' }}">{{ $offer?->status === 'active' ? 'Seleccionado' : 'No seleccionado' }}</span></td><td><button type="button" class="distributor-button secondary" data-open-distributor-dialog="catalog-offer-{{ $item->id }}">{{ $offer ? 'Editar oferta' : 'Seleccionar producto' }}</button></td></tr>
    @empty<tr><td colspan="7"><div class="distributor-empty"><h2>No hay productos disponibles</h2><p>Aquí aparecen los productos activos con «Mostrar en farmacia digital» en ON.</p></div></td></tr>@endforelse
    </tbody></table></div>
    @include('pharmacy-store.distributor.pagination', ['paginator' => $records, 'unit' => 'productos'])
</section>
@endsection
@section('distributor-dialogs')
@foreach($records as $item)
    @php
        $offer = $item->pharmacyOffers->first();
        $reopen = $errors->catalogOffer->any() && (string)old('catalog_id') === (string)$item->id;
    @endphp
    <dialog id="catalog-offer-{{ $item->id }}" class="distributor-dialog distributor-notice-dialog" aria-labelledby="catalog-offer-title-{{ $item->id }}" @if($reopen) data-distributor-auto-open @endif>
        <header><div><h2 id="catalog-offer-title-{{ $item->id }}">{{ $offer ? 'Editar oferta' : 'Seleccionar producto' }}</h2><p>{{ $item->name }} · SKU {{ $item->sku }}</p></div><button type="button" class="distributor-close" data-close-distributor-dialog aria-label="Cerrar">×</button></header>
        <div class="distributor-dialog-content"><form id="catalog-offer-form-{{ $item->id }}" class="distributor-settings-form" action="{{ route('provider.pharmacy.catalog.offer', $item) }}" method="POST">
            @csrf @method('PUT')<input type="hidden" name="catalog_id" value="{{ $item->id }}">
            @if($reopen)<div class="distributor-errors" role="alert">@foreach($errors->catalogOffer->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            <label>Precio por presentación · MXN<input type="number" name="price" min="0.01" max="999999.99" step="0.01" required value="{{ $reopen ? old('price') : $offer?->price }}"></label>
            <label>Existencias<input type="number" name="stock" min="0" max="1000000" step="1" required value="{{ $reopen ? old('stock') : ($offer?->stock ?? 0) }}"></label>
            <label>Marca o laboratorio<input name="laboratory" maxlength="200" value="{{ $reopen ? old('laboratory') : $offer?->laboratory }}"></label>
            <label>Publicar mi oferta<select name="status"><option value="active" @selected(($reopen ? old('status') : $offer?->status) !== 'inactive')>ON · Seleccionado</option><option value="inactive" @selected(($reopen ? old('status') : $offer?->status) === 'inactive')>OFF · No seleccionado</option></select></label>
            <p class="distributor-footnote">Se mostrará el nombre y la ubicación pública de tu distribuidor. Puedes completar tu ubicación en Configuración.</p>
        </form></div><footer><button type="button" class="distributor-button secondary" data-close-distributor-dialog>Cancelar</button><button type="submit" class="distributor-button" form="catalog-offer-form-{{ $item->id }}">Guardar oferta</button></footer>
    </dialog>
@endforeach
@endsection
