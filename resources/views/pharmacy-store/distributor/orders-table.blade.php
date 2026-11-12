@include('pharmacy-store.distributor.column-filter-summary')
<div class="distributor-table-scroll"><table class="distributor-table"><thead><tr>
    <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'reference', 'label' => 'Folio / Fecha'])</th>
    <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'products', 'label' => 'Tus productos'])</th>
    <th scope="col" class="numeric">@include('pharmacy-store.distributor.column-header', ['column' => 'subtotal', 'label' => 'Tu subtotal · MXN'])</th>
    <th scope="col">@include('pharmacy-store.distributor.column-header', ['column' => 'order_status', 'label' => 'Estado administrativo'])</th>
</tr></thead><tbody>
    @forelse($orders as $order)<tr><td><strong>#{{ $order['id'] }}</strong><small>{{ $order['created_at']?->format('d/m/Y · H:i') }}</small></td><td>@foreach($order['items'] as $item)<div class="distributor-order-item">{{ $item['name'] ?? 'Producto' }}<small>{{ $item['quantity'] ?? 0 }} × ${{ number_format((float)($item['unit_price'] ?? 0), 2) }}</small></div>@endforeach</td><td class="numeric">${{ $order['subtotal'] }}</td><td><span class="distributor-tag {{ $order['status'] === 'cancelled' ? 'muted' : '' }}">{{ \App\Services\DistributorWorkspace::ORDER_STATUSES[$order['status']] ?? 'En seguimiento' }}</span></td></tr>
    @empty<tr><td colspan="4"><div class="distributor-empty">@include('pharmacy-store.distributor.icon', ['icon' => 'orders'])<h2>Sin solicitudes para mostrar</h2><p>Aquí aparecerán las solicitudes que incluyan tus productos.</p></div></td></tr>@endforelse
</tbody></table></div>
