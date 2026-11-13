@extends('pharmacy-store.distributor.layout')
@section('distributor-content')
<header class="distributor-heading"><div><p class="distributor-eyebrow">Operación / Solicitudes</p><h1>Solicitudes y pedidos</h1><p>Consulta las solicitudes de la farmacia que incluyen tus productos.</p></div></header>
<div class="distributor-metrics"><div><strong>{{ $orderCounts->sum() }}</strong><span>Solicitudes</span></div><div><strong>{{ $orderCounts->get('submitted', 0) + $orderCounts->get('reviewing', 0) }}</strong><span>Pendientes de revisión</span></div><div><strong>{{ $orderCounts->get('ready', 0) }}</strong><span>Listas para seguimiento</span></div></div>
<section class="distributor-card">
    <form class="distributor-filters" method="GET" role="search"><label class="distributor-search">@include('pharmacy-store.distributor.icon', ['icon' => 'search'])<span class="distributor-sr-only">Buscar folio o producto</span><input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar folio o producto" maxlength="200"></label><input type="hidden" name="order_status" value="{{ request('order_status', 'all') }}">
        @if(!empty($columnFilters['selected']))<input type="hidden" name="columns" value="{{ json_encode($columnFilters['selected']) }}">@endif
        <button class="distributor-button secondary">Buscar</button>
    </form>
    @include('pharmacy-store.distributor.orders-table')
    @include('pharmacy-store.distributor.pagination', ['paginator' => $orders, 'unit' => 'solicitudes'])
</section>
<p class="distributor-footnote">Se muestran únicamente tus productos y su subtotal de referencia. El superadministrador coordina los estados; las solicitudes no representan cobros ni entregas confirmadas.</p>
@endsection
