@extends('layouts.app', ['title' => 'Distribuidor de medicamentos'])
@section('body_class', 'superadmin-native-body superadmin-catalog-body distributor-body')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/superadmin-workspace.css') }}">
<link rel="stylesheet" href="{{ asset('css/superadmin-catalog.css') }}">
<link rel="stylesheet" href="{{ asset('css/pharmacy-store-admin.css') }}">
<link rel="stylesheet" href="{{ asset('css/distributor-workspace.css') }}?v={{ filemtime(public_path('css/distributor-workspace.css')) }}">
@endpush
@push('scripts')
<script src="{{ asset('js/superadmin-workspace.js') }}" defer></script>
<script src="{{ asset('js/distributor-workspace.js') }}?v={{ filemtime(public_path('js/distributor-workspace.js')) }}" defer></script>
<script src="{{ asset('js/distributor-table-filters.js') }}?v={{ filemtime(public_path('js/distributor-table-filters.js')) }}" defer></script>
@endpush
@section('content')
@php
    $menu = [
        'Operación' => ['summary' => 'Resumen', 'catalog' => 'Catálogo propio', 'inventory' => 'Inventario', 'orders' => 'Solicitudes y pedidos', 'deliveries' => 'Entregas'],
        'Administración' => ['pharmacy-catalog' => 'Catálogo de farmacia digital', 'store' => 'Tienda digital', 'reports' => 'Reportes', 'settings' => 'Configuración'],
    ];
    $menuUrl = fn ($key) => match ($key) {
        'catalog' => route('provider.pharmacy.products'), 'orders' => route('provider.pharmacy.orders'), 'pharmacy-catalog' => route('provider.pharmacy.catalog'),
        default => route('provider.pharmacy.workspace', $key),
    };
    $carousel = match ($section) {
        'catalog' => ['list' => 'Catálogo propio', 'create' => 'Nuevo producto'],
        'pharmacy-catalog' => ['all' => 'Todos', 'selected' => 'Seleccionados', 'unselected' => 'Por seleccionar'],
        'inventory' => ['all' => 'Todas', 'available' => 'Con existencias', 'empty' => 'Agotadas', 'low' => 'Existencias bajas'],
        'orders' => ['all' => 'Todas', 'submitted' => 'Recibidas', 'reviewing' => 'En revisión', 'ready' => 'Seguimiento', 'cancelled' => 'Canceladas'],
        'deliveries' => ['all' => 'Pendientes', 'ready' => 'Seguimiento', 'reviewing' => 'En revisión', 'submitted' => 'Recibidas'],
        'store' => ['all' => 'Todas', 'visible' => 'Visibles', 'hidden' => 'No visibles'],
        'settings' => ['all' => 'General', 'profile' => 'Distribuidor', 'access' => 'Cuenta', 'publication' => 'Publicación'],
        default => ['all' => 'General', 'stock' => 'Catálogo', 'orders' => 'Solicitudes'],
    };
    $parameter = match ($section) { 'pharmacy-catalog' => 'selection', 'catalog' => 'section', 'inventory' => 'stock', 'orders', 'deliveries' => 'order_status', 'store' => 'visibility', default => null };
@endphp
<div class="superadmin-native-screen">
    <header class="superadmin-native-topbar">
        <div class="superadmin-native-brand"><x-klini-brand label="Distribuidor de medicamentos" /></div>
        <div class="superadmin-native-session"><x-klini-session-menu :show-profile="true" /></div>
    </header>
    <aside class="superadmin-native-sidebar">
        <nav class="superadmin-native-menu" aria-label="Menú del distribuidor">
            @foreach($menu as $group => $items)
                <p class="distributor-menu-label">{{ $group }}</p>
                @foreach($items as $key => $label)
                    <a href="{{ $menuUrl($key) }}" @class(['is-active' => $section === $key]) @if($section === $key) aria-current="page" @endif>
                        @include('pharmacy-store.distributor.icon', ['icon' => $key])<span>{{ $label }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>
        <div class="distributor-account">@include('pharmacy-store.distributor.icon', ['icon' => 'summary'])<div><strong>{{ $provider->name }}</strong><small>Cuenta de distribuidor</small></div></div>
    </aside>
    <main class="superadmin-native-workspace" tabindex="-1">
        @include('superadmin._filter-carousel', ['carouselOptions' => $carousel, 'carouselParameter' => $parameter, 'carouselSelected' => $parameter ? request($parameter, $section === 'catalog' ? 'list' : 'all') : 'all', 'carouselLabel' => 'Opciones del distribuidor'])
        <div class="distributor-content" data-drsam-table-filter-skip>
            @if(session('status'))<div class="distributor-flash" role="status">@include('pharmacy-store.distributor.icon', ['icon' => 'check'])<span>{{ session('status') }}</span><button type="button" data-dismiss-flash aria-label="Cerrar mensaje">×</button></div>@endif
            @if($errors->any())<div class="distributor-errors" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
            @yield('distributor-content')
        </div>
    </main>
</div>
@yield('distributor-dialogs')
@endsection
