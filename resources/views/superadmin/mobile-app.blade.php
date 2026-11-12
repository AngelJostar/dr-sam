@extends('layouts.app', ['title' => 'Administración de Klini móvil'])

@section('body_class', 'superadmin-native-body superadmin-catalog-body')

@push('styles')
  <link rel="stylesheet" href="{{ asset('css/superadmin-mobile-management.css') }}?v={{ filemtime(public_path('css/superadmin-mobile-management.css')) }}">
@endpush

@section('content')
  <div class="superadmin-native-screen">
    @include('superadmin._topbar')
    @include('superadmin._sidebar', ['active' => 'mobile-app'])

    <section class="superadmin-native-workspace mobile-admin" tabindex="0" aria-label="Administración de Klini móvil">
      <h1 class="superadmin-native-page-title">Aplicación Móvil</h1>
      @include('superadmin._filter-carousel', [
        'carouselOptions' => $sections, 'carouselSelected' => $section, 'carouselParameter' => 'section',
        'carouselLabel' => 'Administración de la aplicación móvil',
        'carouselQueries' => collect($sections)->mapWithKeys(fn ($label, $key) => [$key => ['section' => $key, 'q' => null]])->all(),
        'carouselShortLabels' => ['navigation' => 'Menús', 'texts' => 'Botones y textos', 'subscriptions' => 'Billetera y planes', 'notifications' => 'Avisos y equipos', 'native' => 'App nativa'],
        'carouselIcons' => ['overview' => 'dashboard', 'navigation' => 'module', 'texts' => 'admin-document', 'forms' => 'admin-document', 'users' => 'usuarios', 'catalogs' => 'admin-medication', 'communities' => 'usuarios', 'subscriptions' => 'admin-subscription', 'advertising' => 'publicidad', 'notifications' => 'alertas', 'health' => 'insurance-life', 'native' => 'aplicacion movil'],
      ])
      <section class="superadmin-native-catalog-card">
        <header class="superadmin-native-section-heading">
          <div><h2>Aplicación Móvil · {{ $sections[$section] }}</h2><p>Centro de administración de Klini móvil</p></div>
          <div class="superadmin-native-heading-actions">
            <a href="{{ route('superadmin.mobile-app', ['section' => $section]) }}">Actualizar</a>
            @if (in_array($section, ['navigation', 'texts', 'forms'], true))
              <button type="submit" form="mobile-configuration">Guardar cambios</button>
            @endif
          </div>
        </header>
        @if ($section === 'overview')
          <div class="mobile-admin-metrics">
            @foreach ($metrics as $label => $value)<article><small>{{ $label }}</small><strong>{{ $value }}</strong></article>@endforeach
          </div>
          <div class="mobile-admin-note">Administra los controles conectados al portal móvil y consulta el alcance de cada sección. Los cambios de navegación y textos se aplican al recargar la aplicación.</div>
        @endif
        @if (in_array($section, ['navigation', 'texts', 'forms'], true))
          @include('superadmin.mobile._editor')
        @elseif ($section === 'users')
          @include('superadmin.mobile._users')
        @elseif ($section === 'notifications')
          <div class="mobile-admin-note">Registro de dispositivos de la aplicación. El permiso de notificaciones lo controla cada usuario desde su teléfono. El envío de campañas todavía no está integrado.</div>
          <div class="superadmin-native-catalog-scroll"><table class="superadmin-native-catalog-table">
            <thead><tr><th>Usuario</th><th>Dispositivo</th><th>Plataforma</th><th>Registro</th><th>Última conexión</th></tr></thead>
            <tbody>@forelse ($devices as $device)
              <tr><td>{{ $device->user?->name ?? 'Usuario no disponible' }}</td><td>{{ $device->device_name ?: 'Sin nombre' }}</td><td>{{ $device->platform }}</td><td>{{ $device->enabled ? 'Habilitado' : 'Deshabilitado' }}</td><td>{{ $device->last_seen_at?->format('d/m/Y H:i') ?? 'Sin conexión registrada' }}</td></tr>
            @empty<tr><td colspan="5">Todavía no hay dispositivos registrados.</td></tr>@endforelse</tbody>
          </table></div><div class="mobile-admin-pagination">{{ $devices->links() }}</div>
        @else
          <div class="superadmin-native-catalog-scroll"><table class="superadmin-native-catalog-table mobile-admin-area-table">
            <thead><tr><th>Sección</th><th>Controles y alcance</th><th>Estado</th><th>Administrar</th></tr></thead>
            <tbody>@foreach ($areas as $area)
              @continue($section !== 'overview' && $area['section'] !== $section)
              <tr><td><strong>{{ $area['title'] }}</strong></td><td>{{ $area['description'] }}</td><td><span class="mobile-admin-status {{ $area['status'] === 'Conectado' ? 'is-connected' : '' }}">{{ $area['status'] }}</span></td><td>
                @if ($section === 'overview')<a href="{{ route('superadmin.mobile-app', ['section' => $area['section']]) }}">Ver controles</a>
                @elseif (isset($area['catalog']))<a href="{{ route('superadmin.catalog', $area['catalog']) }}">Abrir gestión</a>
                @elseif (isset($area['route']))<a href="{{ route($area['route']) }}">Abrir gestión</a>
                @else<span class="mobile-admin-muted">Requiere integración</span>@endif
              </td></tr>
            @endforeach</tbody>
          </table></div>
          @if ($section === 'native')
            <div class="mobile-admin-note">El módulo nativo de paciente incluye perfil, citas, historial, recetas y documentos. El de médico incluye agenda, pacientes, notas, recetas, estudios y mezclas. Sus datos se consultan en los módulos clínicos; sus textos nativos aún no reciben cambios desde este editor.</div>
            <div class="mobile-admin-links"><a href="{{ route('superadmin.catalog', 'doctors') }}">Médicos</a><a href="{{ route('superadmin.catalog', 'patients') }}">Pacientes</a><a href="{{ route('superadmin.catalog', 'access') }}">Accesos</a></div>
          @endif
        @endif
      </section>
    </section>
    @include('superadmin._feedback')
  </div>
@endsection
