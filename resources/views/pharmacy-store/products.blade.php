@extends('pharmacy-store.distributor.layout')
@section('distributor-content')
<header class="distributor-heading">
    <div><p class="distributor-eyebrow">Operación / Catálogo</p><h1>Catálogo propio</h1><p>Precios y existencias por presentación.</p></div>
    <div class="distributor-heading-actions"><button type="button" class="distributor-publication" data-open-distributor-dialog="distributor-publication">@include('pharmacy-store.distributor.icon', ['icon' => 'shield']) Publicación {{ $provider->digital_pharmacy_enabled ? 'autorizada' : 'pendiente' }}</button><a class="distributor-button" href="{{ route('provider.pharmacy.products', ['section' => 'create']) }}" data-open-distributor-dialog="distributor-product-new">@include('pharmacy-store.distributor.icon', ['icon' => 'plus']) Nuevo producto</a></div>
</header>
<div class="distributor-metrics" aria-label="Resumen del catálogo">
    <div><strong>{{ $metrics['products'] }}</strong><span>Productos</span></div>
    <div><strong>{{ $metrics['presentations'] }}</strong><span>Presentaciones</span></div>
    <div class="distributor-warning"><strong>{{ $metrics['empty'] }}</strong><span>Presentaciones<br>sin existencias</span></div>
</div>
<section class="distributor-card">
    @include('pharmacy-store.distributor.filters')
    @include('pharmacy-store.distributor.column-filter-summary')
    <div class="distributor-table-scroll">
        <table class="distributor-table distributor-catalog-table">
            <thead><tr>
                <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'product', 'label' => 'Producto y presentación'])</th>
                <th scope="col" class="numeric">@include('pharmacy-store.distributor.column-header', ['column' => 'price', 'label' => 'Precio por paquete · MXN'])</th>
                <th scope="col" class="numeric">@include('pharmacy-store.distributor.column-header', ['column' => 'stock', 'label' => 'Existencias'])</th>
                <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'status', 'label' => 'Estado'])</th>
                <th scope="col" class="distributor-edit-column">Editar</th>
            </tr></thead>
            @forelse($groups as $group)
                @php $groupId = 'product-group-'.$loop->index; $open = $loop->first || request()->filled('q') || !empty($columnFilters['selected']) || ($filters['stock'] ?? 'all') !== 'all'; $items = $group['items']; @endphp
                <tbody data-product-group>
                    <tr class="distributor-group-row"><th scope="row"><button class="distributor-group-toggle" type="button" aria-expanded="{{ $open ? 'true' : 'false' }}" aria-controls="{{ $groupId }}">
                        <span class="distributor-chevron">@include('pharmacy-store.distributor.icon', ['icon' => 'chevron'])</span><span class="distributor-product-symbol">@include('pharmacy-store.distributor.icon', ['icon' => 'pill'])</span>
                        <span><strong>{{ $group['name'] }}</strong><small>{{ $items->count() }} {{ $items->count() === 1 ? 'presentación' : 'presentaciones' }}@if($items->where('stock', 0)->count()) · {{ $items->where('stock', 0)->count() }} sin existencias @endif @if($group['laboratory']) · {{ $group['laboratory'] }}@endif</small></span>
                    </button></th><td class="numeric distributor-muted">${{ number_format((float)$items->min('price'), 2) }}@if($items->min('price') != $items->max('price')) – ${{ number_format((float)$items->max('price'), 2) }}@endif</td><td class="numeric distributor-muted">{{ $items->where('stock', '>', 0)->count() }} de {{ $items->count() }} disponibles</td><td><span class="distributor-state {{ $items->where('status', 'active')->isEmpty() ? 'muted' : '' }}">{{ $items->every(fn($item) => $item->status === 'active') ? 'Activo' : ($items->every(fn($item) => $item->status === 'inactive') ? 'Inactivo' : 'Mixto') }}</span></td><td></td></tr>
                </tbody>
                <tbody id="{{ $groupId }}" @if(!$open) hidden @endif>
                    @foreach($items as $product)
                        <tr><td class="distributor-presentation">{{ $workspace->presentation($product) }}</td><td class="numeric">${{ number_format((float)$product->price, 2) }}</td><td class="numeric">@if($product->stock === 0)<span class="distributor-stock-empty">0 · Agotado</span>@else{{ $product->stock }} ud.@endif</td><td><span class="distributor-state {{ $product->status !== 'active' ? 'muted' : '' }}">{{ $product->status === 'active' ? 'Activo' : 'Inactivo' }}</span></td><td class="distributor-edit-column"><a class="distributor-icon-button" href="{{ route('provider.pharmacy.products', ['edit' => $product->id]) }}" data-open-distributor-dialog="distributor-product-{{ $product->id }}" aria-label="Editar {{ $product->name }}">@include('pharmacy-store.distributor.icon', ['icon' => 'edit'])</a></td></tr>
                    @endforeach
                </tbody>
            @empty
                <tbody><tr><td colspan="5"><div class="distributor-empty">@include('pharmacy-store.distributor.icon', ['icon' => 'catalog'])<h2>{{ $metrics['presentations'] ? 'Sin coincidencias' : 'Tu catálogo empieza aquí' }}</h2><p>{{ $metrics['presentations'] ? 'Prueba otro nombre o cambia los filtros.' : 'Agrega tu primer producto y sus datos de presentación.' }}</p></div></td></tr></tbody>
            @endforelse
        </table>
    </div>
    @include('pharmacy-store.distributor.pagination', ['paginator' => $groups, 'unit' => 'productos en esta consulta'])
</section>
@endsection
@section('distributor-dialogs')
@include('pharmacy-store.distributor.product-dialog', ['product' => null])
@foreach($products->when($editingProduct, fn($items) => $items->push($editingProduct))->unique('id') as $product)
    @include('pharmacy-store.distributor.product-dialog', ['product' => $product])
@endforeach
<dialog id="distributor-publication" class="distributor-dialog distributor-notice-dialog" aria-labelledby="distributor-publication-title" data-publication-key="{{ $provider->id }}-{{ (int)$provider->digital_pharmacy_enabled }}" @if(!request()->hasAny(['section', 'edit']) && !$errors->any() && !session('status')) data-show-publication @endif>
    <header><div><span class="distributor-product-symbol">@include('pharmacy-store.distributor.icon', ['icon' => 'shield'])</span><h2 id="distributor-publication-title">Publicación {{ $provider->digital_pharmacy_enabled ? 'autorizada' : 'pendiente' }}</h2></div><button type="button" class="distributor-close" data-close-distributor-dialog aria-label="Cerrar mensaje" autofocus>×</button></header>
    <div class="distributor-dialog-content"><p>{{ $provider->digital_pharmacy_enabled ? 'Puedes gestionar tu catálogo aunque no esté visible en la tienda.' : 'Tu publicación está pendiente de autorización del superadministrador. Puedes seguir preparando tu catálogo.' }}</p></div>
</dialog>
@endsection
