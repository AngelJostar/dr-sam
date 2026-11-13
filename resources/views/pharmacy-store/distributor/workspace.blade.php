@extends('pharmacy-store.distributor.layout')
@section('distributor-content')
@php
    $titles = ['summary' => ['Resumen', 'Tu operación en un solo lugar.'], 'inventory' => ['Inventario', 'Controla las existencias de cada presentación.'], 'deliveries' => ['Entregas', 'Solicitudes pendientes de coordinación.'], 'store' => ['Tienda digital', 'Consulta qué productos de tu catálogo se muestran en la farmacia.'], 'reports' => ['Reportes', 'Indicadores de tu catálogo y solicitudes acumuladas.'], 'settings' => ['Configuración', 'Administra los datos de tu distribuidor.']];
@endphp
<header class="distributor-heading"><div><p class="distributor-eyebrow">{{ in_array($section, ['store', 'reports', 'settings']) ? 'Administración' : 'Operación' }} / {{ $titles[$section][0] }}</p><h1>{{ $titles[$section][0] }}</h1><p>{{ $titles[$section][1] }}</p></div><a class="distributor-button secondary" href="{{ route('provider.pharmacy.products') }}">Ver catálogo @include('pharmacy-store.distributor.icon', ['icon' => 'chevron'])</a></header>

@if($section === 'summary' || $section === 'reports')
    <section data-admin-filter-panel="stock">
        <div class="distributor-metrics"><div><strong>{{ $metrics['products'] }}</strong><span>Productos</span></div><div><strong>{{ $metrics['presentations'] }}</strong><span>Presentaciones</span></div><div class="distributor-warning"><strong>{{ $metrics['empty'] }}</strong><span>Sin existencias</span></div></div>
        <div class="distributor-two-columns">
            <section class="distributor-card distributor-panel"><header><h2>Estado del catálogo</h2>@include('pharmacy-store.distributor.icon', ['icon' => 'reports'])</header>
                @foreach(['Activas' => $metrics['active'], 'Inactivas' => $metrics['presentations'] - $metrics['active'], 'Sin existencias' => $metrics['empty']] as $label => $value)
                    <div class="distributor-meter"><div><span>{{ $label }}</span><strong>{{ $value }}</strong></div><div class="distributor-meter-track"><span style="width: {{ $metrics['presentations'] ? round($value / $metrics['presentations'] * 100) : 0 }}%"></span></div></div>
                @endforeach
                <a class="distributor-text-link" href="{{ route('provider.pharmacy.workspace', 'inventory') }}">Administrar inventario →</a>
            </section>
            <section class="distributor-card distributor-panel"><header><h2>{{ $section === 'summary' ? 'Requieren atención' : 'Valor del inventario' }}</h2>@include('pharmacy-store.distributor.icon', ['icon' => 'inventory'])</header>
                @if($section === 'summary')
                    @forelse($attentionProducts as $product)<a class="distributor-attention-row" href="{{ route('provider.pharmacy.products', ['edit' => $product->id]) }}"><span>{{ $product->name }}</span><span class="distributor-stock-empty">{{ $product->stock }} ud.</span></a>@empty<div class="distributor-empty compact"><p>No hay presentaciones con existencias bajas.</p></div>@endforelse
                @else
                    <p class="distributor-large-number">${{ number_format($metrics['value'], 2) }} <small>MXN</small></p><p class="distributor-muted">{{ number_format($metrics['units']) }} unidades en catálogo.</p><p class="distributor-footnote">Valor calculado con el precio actual por presentación y sus existencias. No corresponde a ventas ni ingresos.</p>
                @endif
            </section>
        </div>
    </section>
    <section class="distributor-card distributor-panel" data-admin-filter-panel="orders"><header><div><h2>Solicitudes de la farmacia</h2><p class="distributor-muted">{{ $orders->count() }} solicitudes con tus productos.</p></div><a class="distributor-button secondary" href="{{ route('provider.pharmacy.orders') }}">Ver solicitudes</a></header>
        @if($section === 'reports')
            <div class="distributor-report-grid">@foreach(\App\Services\DistributorWorkspace::ORDER_STATUSES as $key => $label)<div><strong>{{ $orderCounts->get($key, 0) }}</strong><span>{{ $label }}</span></div>@endforeach</div>
            <p class="distributor-footnote">Subtotal de productos solicitados (excluye cancelaciones): <strong>${{ number_format($orders->where('status', '!=', 'cancelled')->sum('subtotal'), 2) }} MXN</strong>. Importe de referencia, sin cobros procesados.</p>
        @else
            @include('pharmacy-store.distributor.orders-table', ['orders' => $orders->take(5)])
        @endif
    </section>
@elseif($section === 'inventory')
    <div class="distributor-metrics"><div><strong>{{ number_format($metrics['units']) }}</strong><span>Unidades en catálogo</span></div><div class="distributor-warning"><strong>{{ $metrics['empty'] }}</strong><span>Presentaciones agotadas</span></div><div><strong>{{ $metrics['low'] }}</strong><span>Existencias bajas · 1–5 ud.</span></div></div>
    <section class="distributor-card">@include('pharmacy-store.distributor.filters')
        @include('pharmacy-store.distributor.column-filter-summary')
        <div class="distributor-table-scroll"><table class="distributor-table"><thead><tr>
            <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'product', 'label' => 'Producto y presentación'])</th>
            <th scope="col" class="numeric">@include('pharmacy-store.distributor.column-header', ['column' => 'stock', 'label' => 'Existencias'])</th>
            <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'status', 'label' => 'Estado'])</th>
            <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'updated', 'label' => 'Última actualización'])</th>
            <th scope="col">Ajustar</th>
        </tr></thead><tbody>
            @forelse($products as $product)<tr><td><strong>{{ $workspace->productName($product) }}</strong><small>{{ $product->presentation ?: $workspace->presentation($product) }}</small></td><td class="numeric">@if($product->stock === 0)<span class="distributor-stock-empty">0 · Agotado</span>@else{{ $product->stock }} ud.@endif</td><td><span class="distributor-state {{ $product->status === 'active' ? '' : 'muted' }}">{{ $product->status === 'active' ? 'Activo' : 'Inactivo' }}</span></td><td>{{ $product->updated_at?->format('d/m/Y · H:i') }}</td><td><button class="distributor-icon-button" type="button" data-open-distributor-dialog="distributor-stock-{{ $product->id }}" aria-label="Ajustar existencias de {{ $product->name }}">@include('pharmacy-store.distributor.icon', ['icon' => 'edit'])</button></td></tr>
            @empty<tr><td colspan="5"><div class="distributor-empty"><h2>Sin presentaciones para mostrar</h2><p>Cambia los filtros o agrega productos a tu catálogo.</p></div></td></tr>@endforelse
        </tbody></table></div>
        @include('pharmacy-store.distributor.pagination', ['paginator' => $products, 'unit' => 'presentaciones'])
    </section>
@elseif($section === 'deliveries')
    <div class="distributor-callout">@include('pharmacy-store.distributor.icon', ['icon' => 'deliveries'])<div><strong>Seguimiento de solicitudes</strong><p>El superadministrador coordina la atención. El estado «Listo para seguimiento» no confirma un envío; todavía no hay guías ni entregas registradas en este módulo.</p></div></div>
    <section class="distributor-card">@include('pharmacy-store.distributor.orders-table') @include('pharmacy-store.distributor.pagination', ['paginator' => $orders, 'unit' => 'solicitudes pendientes de coordinación'])</section>
@elseif($section === 'store')
    <div class="distributor-metrics"><div><strong>{{ $visibleIds->count() }}</strong><span>Presentaciones visibles</span></div><div><strong>{{ $metrics['presentations'] - $visibleIds->count() }}</strong><span>No visibles</span></div><div><span class="distributor-tag {{ $provider->digital_pharmacy_enabled ? '' : 'muted' }}">{{ $provider->digital_pharmacy_enabled ? 'Publicación autorizada' : 'Autorización pendiente' }}</span></div></div>
    @if(!$settings->enabled)<div class="distributor-callout">@include('pharmacy-store.distributor.icon', ['icon' => 'store'])<p>La farmacia digital está desactivada por el superadministrador. Tu catálogo permanece disponible para editarlo.</p></div>@endif
    <section class="distributor-card">
    @include('pharmacy-store.distributor.column-filter-summary')
    <div class="distributor-table-scroll"><table class="distributor-table"><thead><tr>
        <th scope="col" class="distributor-sku-column">@include('pharmacy-store.distributor.column-header', ['column' => 'sku', 'label' => 'SKU'])</th>
        <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'product', 'label' => 'Producto'])</th>
        <th scope="col" class="numeric">@include('pharmacy-store.distributor.column-header', ['column' => 'price', 'label' => 'Precio · MXN'])</th>
        <th scope="col" class="numeric">@include('pharmacy-store.distributor.column-header', ['column' => 'stock', 'label' => 'Existencias'])</th>
        <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'visibility', 'label' => 'Publicación en la tienda'])</th>
        <th scope="col">Editar</th>
        <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'status', 'label' => 'Estado'])</th>
    </tr></thead><tbody>
        @forelse($products as $product)
            <tr>
                <td class="distributor-sku-column">{{ $product->catalogItem?->sku ?: ($product->sku ?: 'Sin SKU') }}</td>
                <td><div class="distributor-store-product">@if($product->uploadedStoreImage())<img loading="lazy" src="{{ route('provider.pharmacy.products.image', $product) }}" alt="">@else<span class="distributor-product-symbol">@include('pharmacy-store.distributor.icon', ['icon' => 'pill'])</span>@endif<div><strong>{{ $product->name }}</strong><small>{{ $product->presentation }}</small></div></div></td>
                <td class="numeric">${{ number_format((float)$product->price, 2) }}</td>
                <td class="numeric">{{ $product->stock }} ud.</td>
                <td><span class="distributor-state {{ $visibleIds->contains($product->id) ? '' : 'muted' }}">{{ $visibleIds->contains($product->id) ? 'Visible' : 'No visible' }}</span>@if(!$visibleIds->contains($product->id))<small>{{ !$settings->enabled ? 'Tienda desactivada' : (!$provider->digital_pharmacy_enabled ? 'Autorización pendiente' : ($product->status !== 'active' ? 'Presentación inactiva' : 'No elegible para el catálogo público')) }}</small>@endif</td>
                <td><a class="distributor-icon-button" href="{{ route('provider.pharmacy.products', ['edit' => $product->id]) }}" aria-label="Editar {{ $product->name }}">@include('pharmacy-store.distributor.icon', ['icon' => 'edit'])</a></td>
                <td>@include('pharmacy-store.distributor.product-status-switch')</td>
            </tr>
        @empty<tr><td colspan="7"><div class="distributor-empty"><h2>Sin productos en esta vista</h2><p>Selecciona otra opción en el carrusel o prepara tu catálogo.</p></div></td></tr>@endforelse
    </tbody></table></div>@include('pharmacy-store.distributor.pagination', ['paginator' => $products, 'unit' => 'presentaciones'])</section>
@elseif($section === 'settings')
    <section class="distributor-card distributor-panel" data-admin-filter-panel="profile"><header><h2>Datos del distribuidor</h2>@include('pharmacy-store.distributor.icon', ['icon' => 'settings'])</header><form class="distributor-settings-form" method="POST" action="{{ route('provider.pharmacy.settings') }}">@csrf @method('PATCH')<label>Nombre del distribuidor<input name="name" required maxlength="200" value="{{ old('name', $provider->name) }}"></label><label>Dirección pública de la farmacia<input name="pharmacy_address" maxlength="500" value="{{ old('pharmacy_address', $provider->pharmacy_address) }}" placeholder="Calle, colonia, ciudad y código postal"></label>
        <label>Latitud<input name="pharmacy_latitude" type="number" step="any" min="-90" max="90" value="{{ old('pharmacy_latitude', $provider->pharmacy_latitude) }}"></label>
        <label>Longitud<input name="pharmacy_longitude" type="number" step="any" min="-180" max="180" value="{{ old('pharmacy_longitude', $provider->pharmacy_longitude) }}"></label>
        <p class="distributor-footnote">Registra las coordenadas del punto de atención para calcular la distancia al usuario. Sin coordenadas se mostrará la dirección, sin indicar cercanía.</p>
        <button class="distributor-button" type="submit">Guardar cambios</button></form></section>
    <section class="distributor-card distributor-panel" data-admin-filter-panel="access"><header><h2>Cuenta de acceso</h2></header><dl class="distributor-definition-list"><div><dt>Usuario</dt><dd>{{ auth()->user()->username }}</dd></div><div><dt>Perfil</dt><dd>Distribuidor de medicamentos</dd></div><div><dt>Estado</dt><dd><span class="distributor-state">Activo</span></dd></div></dl><p class="distributor-footnote">Los permisos y las credenciales de acceso se administran desde superadministración.</p></section>
    <section class="distributor-card distributor-panel" data-admin-filter-panel="publication"><header><h2>Publicación del catálogo</h2>@include('pharmacy-store.distributor.icon', ['icon' => 'shield'])</header><dl class="distributor-definition-list"><div><dt>Autorización</dt><dd>{{ $provider->digital_pharmacy_enabled ? 'Autorizada' : 'Pendiente del superadministrador' }}</dd></div><div><dt>Farmacia digital</dt><dd>{{ $settings->enabled ? 'Activada' : 'Desactivada' }}</dd></div></dl><p class="distributor-footnote">Puedes actualizar productos y existencias. La autorización de publicación la gestiona el superadministrador.</p></section>
@endif
@endsection
@section('distributor-dialogs')
@if($section === 'inventory')
    @foreach($products as $product)
        <dialog class="distributor-dialog distributor-notice-dialog" id="distributor-stock-{{ $product->id }}" aria-labelledby="distributor-stock-title-{{ $product->id }}">
            <header><div><h2 id="distributor-stock-title-{{ $product->id }}">Ajustar existencias</h2><p>{{ $product->name }}</p></div><button type="button" class="distributor-close" data-close-distributor-dialog aria-label="Cerrar formulario">×</button></header>
            <div class="distributor-dialog-content"><form class="distributor-settings-form" id="stock-form-{{ $product->id }}" method="POST" action="{{ route('provider.pharmacy.inventory.update', $product) }}">@csrf @method('PATCH')<input type="hidden" name="current_stock" value="{{ $product->stock }}"><label>Existencias actuales<input name="stock" type="number" value="{{ $product->stock }}" min="0" max="1000000" step="1" required></label><p class="distributor-footnote">Establece la cantidad total disponible de esta presentación.</p></form></div>
            <footer><button class="distributor-button secondary" type="button" data-close-distributor-dialog>Cancelar</button><button class="distributor-button" type="submit" form="stock-form-{{ $product->id }}">Guardar existencias</button></footer>
        </dialog>
    @endforeach
@endif
@endsection
