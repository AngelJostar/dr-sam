@extends('layouts.app', ['title' => 'Farmacia Digital'])

@section('body_class', 'orders-store-native-body')

@php
  $statusLabels = [
    'created' => 'Creado',
    'received' => 'Recibido',
    'preparing' => 'Preparacion',
    'in_route' => 'En camino',
    'delivered' => 'Entregado',
    'cancelled' => 'Cancelado',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $patientInitials = $patient ? collect(explode(' ', $patient->full_name))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') : 'PX';
  $productRows = $products->getCollection();
  $featured = $productRows->take(3);
  $recommended = $productRows->skip(3)->take(4);
  if ($recommended->isEmpty()) {
    $recommended = $productRows->take(4);
  }
@endphp

@section('content')
  <div class="orders-store-native-screen">
    <header class="orders-store-native-topbar">
      <a class="orders-store-native-brand" href="{{ route('orders.index') }}">
        <span>+</span>
        <div>
          <strong>Farmacia Digital</strong>
          <small>Pagina de inicio</small>
        </div>
      </a>

      <form class="orders-store-native-search" method="get" action="{{ route('orders.index') }}">
        <span>Q</span>
        <input name="search" value="{{ request('search') }}" placeholder="Buscar medicamentos, categorias o sintomas">
      </form>

      <nav class="orders-store-native-nav" aria-label="Navegacion farmacia paciente">
        <a class="is-active" href="{{ route('orders.index') }}">Inicio</a>
        <a href="#orders-catalog">Medicamentos</a>
        <a href="#orders-cart">Carrito <span>0</span></a>
        <a href="#orders-history">Pedidos</a>
      </nav>

      <a class="orders-store-native-home" href="{{ route('dashboard') }}">Pagina de inicio</a>

      @if ($patient)
        <a class="orders-store-native-patient" href="{{ route('patient.dashboard') }}">
          <span>{{ $patientInitials }}</span>
          <strong>{{ $patient->full_name }}</strong>
          <small>Paciente</small>
        </a>
      @endif
    </header>

    <main class="orders-store-native-main">
      @if (session('status'))
        <div class="notice success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="notice danger">
          <strong>No se pudo crear el pedido.</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <section class="orders-store-native-hero">
        <div>
          <p class="eyebrow">Compra segura y protegida</p>
          <h1>Medicamentos y entregas para tu tratamiento</h1>
          <p>
            @if ($patient)
              {{ $patient->full_name }} / Expediente {{ $patient->platform_number ?? 'sin folio' }}
            @else
              Catalogo digital de farmacia
            @endif
          </p>
        </div>
        <div class="orders-store-native-hero-card">
          <strong>{{ $products->total() }}</strong>
          <span>productos disponibles</span>
        </div>
      </section>

      <section class="orders-store-native-section">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Promociones</p>
            <h2>Ofertas activas</h2>
          </div>
          <a href="#orders-catalog">Ver todos</a>
        </div>
        <div class="orders-store-native-products">
          @forelse ($featured as $product)
            @include('orders.partials.store-product-card', ['product' => $product, 'compact' => true])
          @empty
            <p class="empty-state">No hay ofertas activas por ahora.</p>
          @endforelse
        </div>
      </section>

      <section class="orders-store-native-section">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Seleccion para ti</p>
            <h2>Recomendados</h2>
          </div>
          <a href="#orders-catalog">Ver todos</a>
        </div>
        <div class="orders-store-native-products is-four">
          @forelse ($recommended as $product)
            @include('orders.partials.store-product-card', ['product' => $product, 'compact' => true])
          @empty
            <p class="empty-state">No hay recomendados disponibles.</p>
          @endforelse
        </div>
      </section>

      <section id="orders-catalog" class="orders-store-native-catalog">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Catalogo</p>
            <h2>Medicamentos</h2>
          </div>
          <span>{{ $products->total() }} productos</span>
        </div>

        <div class="orders-store-native-category-row">
          @foreach (['Todas', 'Sistema nervioso', 'Antiinfecciosos', 'Aparato digestivo', 'Varios / medicamentos'] as $category)
            <a class="{{ $loop->first ? 'is-active' : '' }}" href="{{ route('orders.index') }}">{{ $category }}</a>
          @endforeach
        </div>

        <div class="orders-store-native-products is-four">
          @forelse ($products as $product)
            @include('orders.partials.store-product-card', ['product' => $product, 'compact' => false])
          @empty
            <p class="empty-state">No hay productos con esos filtros.</p>
          @endforelse
        </div>
        <div class="pagination-wrap">{{ $products->links() }}</div>
      </section>

      <section id="orders-history" class="orders-store-native-history">
        <div class="orders-store-native-heading">
          <div>
            <p class="eyebrow">Seguimiento</p>
            <h2>Mis pedidos</h2>
          </div>
          <span>{{ $orders->total() }}</span>
        </div>
        <div class="orders-store-native-list">
          @forelse ($orders as $order)
            <article>
              <div>
                <strong>{{ $order->order_number ?? 'Pedido sin folio' }}</strong>
                <span>{{ $order->ordered_at?->format('d/m/Y H:i') ?? 'Sin fecha' }} / {{ $order->channel }}</span>
              </div>
              <span>{{ $statusText($order->status) }}</span>
              <strong>${{ number_format((float) $order->total, 2) }}</strong>
            </article>
          @empty
            <p class="empty-state">Todavia no hay pedidos registrados.</p>
          @endforelse
        </div>
        <div class="pagination-wrap">{{ $orders->links() }}</div>
      </section>
    </main>
  </div>
@endsection
