@extends('layouts.app', ['title' => 'Superadministrador'])

@section('body_class', 'superadmin-native-body superadmin-dashboard-body')

@php
  $statusLabels = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'suspended' => 'Suspendido',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');

  $metricDescriptions = [
    'Modulos activos' => "{$activeModules} modulos administrables",
    'Unidades' => 'Modulo Unidad e Institucion',
    'Medicos' => 'Catalogo medico',
    'Usuarios plataforma' => 'Medicos y pacientes',
    'Vendedores' => 'Soporte a medicos',
    'Hosp. privados' => 'Listado administrable',
    'Pacientes' => 'Catalogo de pacientes',
    'Usuarios de acceso' => 'Modulo de Acceso',
    'Servicios' => 'Contratados o habilitados',
    'Medicamentos' => 'Catalogo universal',
  ];

  $moduleDescriptions = [
    'community_admin' => 'Comunidades, miembros, eventos y publicaciones.',
    'institution' => 'Catalogo institucional, unidades, servicios, contratos y cobertura.',
    'operational' => 'Solicitudes, autorizaciones y seguimiento operativo.',
    'operational_outpatient' => 'Administración de la consulta externa.',
    'provider_medicines' => 'Administración de la distribución de medicamentos.',
    'provider_npt' => 'Catalogos de productos, solicitudes NPT y quimioterapias, estatus de central y reportes.',
    'provider_import' => 'Expedientes de importacion, documentos del paciente, permiso Cofepris, aduana y entrega en almacen.',
    'external_pharmacy' => 'Farmacia externa, pedidos y rutas de entrega.',
    'digital_pharmacy' => 'Catálogo, pedidos, surtido y trazabilidad.',
    'doctor' => 'Consulta medica, recetas, expediente clinico y seguimiento de pacientes.',
    'patient' => 'Portal de pacientes, pedidos, recetas y seguimiento personal.',
    'messenger' => 'Rutas, evidencias y confirmaciones de entrega.',
    'insurance_health' => 'Pacientes, autorizaciones, documentos y reportes.',
    'insurance_advisor' => 'Pólizas, carteras, entregas y seguimiento.',
    'orders' => 'Pedidos de paciente, carrito y solicitudes de farmacia.',
    'unit' => 'Agenda, inventario, solicitudes y administracion por unidad medica.',
    'superadmin' => 'Gobierno modular, catalogos globales, usuarios y permisos.',
  ];

  $moduleDepartments = [
    'community_admin' => 'Klini Wellness',
    'doctor' => 'Direccion Medica',
    'patient' => 'Atencion a Pacientes',
    'superadmin' => 'Gobierno modular',
  ];

@endphp

@section('content')
  <div class="superadmin-native-screen">
    @include('superadmin._topbar')
    @include('superadmin._sidebar', ['active' => 'dashboard'])

    <section class="superadmin-native-workspace" tabindex="0" aria-label="Contenido del Superadministrador">
      <div class="superadmin-dashboard-intro">
        <h1>Panel de administración</h1>
        <p>Vista general de la plataforma</p>
      </div>
      @include('superadmin._filter-carousel', [
        'carouselOptions' => ['all' => 'Todos', 'metrics' => 'Indicadores', 'modules' => 'Módulos', 'activity' => 'Actividad'],
      ])
      <section class="superadmin-native-metrics" aria-label="Indicadores superadministrador" data-admin-filter-panel="metrics">
        @foreach ($metrics as $label => $value)
          <article title="{{ $metricDescriptions[$label] ?? $label }}">
            <span>{{ ['Modulos activos' => 'Módulos activos', 'Medicos' => 'Médicos', 'Usuarios plataforma' => 'Usuarios de la plataforma', 'Hosp. privados' => 'Hospitales privados'][$label] ?? $label }}</span>
            <strong>{{ number_format($value) }}</strong>
          </article>
        @endforeach
      </section>

      <section class="superadmin-native-modules" data-admin-filter-panel="modules">
        <div class="superadmin-native-section-heading">
          <div>
            <h2>Estado de módulos</h2>
            <p>{{ $activeModules }} de {{ $totalModules }} módulos activos</p>
          </div>
          <a href="{{ route('superadmin.catalog', 'modules') }}">→ Administrar</a>
        </div>

        <div class="superadmin-native-module-grid">
          @foreach ($modules as $module)
            <article class="superadmin-native-module-card">
              <div class="superadmin-native-module-status">
                <span @class(['is-disabled' => ! $module->enabled])></span>
                {{ $module->enabled ? 'Activo' : 'Inactivo' }}
              </div>
              @php
                $panelRoute = config("drsam.modules.{$module->key}.route", $module->target);
              @endphp
              <h3>
                @if($panelRoute && \Illuminate\Support\Facades\Route::has($panelRoute))
                  <a class="klini-module-panel-link" href="{{ route($panelRoute) }}" aria-label="Entrar a {{ $module->label }}">{{ $module->label }}</a>
                @else
                  <a class="klini-module-panel-link" href="{{ route('superadmin.catalog', ['section' => 'modules', 'selected_module' => $module->key]) }}#module-config" aria-label="Abrir configuración de {{ $module->label }}">{{ $module->label }}</a>
                @endif
              </h3>
              <small>{{ $moduleDepartments[$module->key] ?? 'Direccion Administrativa' }}</small>
              <p>{{ $moduleDescriptions[$module->key] ?? 'Modulo administrable de plataforma.' }}</p>
              <div class="superadmin-native-module-footer">
                <strong>{{ count($module->roles ?? []) }} perfiles</strong>
                @if($panelRoute && \Illuminate\Support\Facades\Route::has($panelRoute))
                  <a class="klini-module-access" href="{{ route($panelRoute) }}" aria-label="Acceder a {{ $module->label }}">Acceder <span aria-hidden="true">→</span></a>
                @endif
                <a class="superadmin-module-edit" href="{{ route('superadmin.catalog', ['section' => 'modules', 'selected_module' => $module->key]) }}#module-config" aria-label="Editar {{ $module->label }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="m15 4 5 5M4 16 16 4a2 2 0 0 1 4 4L8 20H4z"/></svg>Editar</a>
              </div>
            </article>
          @endforeach
        </div>
        <div class="superadmin-module-pagination">
          <span>Mostrando {{ $modules->count() }} de {{ $modules->total() }} módulos</span>
          @if($modules->hasPages())
            <nav aria-label="Páginas de módulos">
              @if($modules->onFirstPage())<span class="is-disabled" aria-disabled="true">‹</span>@else<a href="{{ $modules->previousPageUrl() }}" rel="prev" aria-label="Página anterior">‹</a>@endif
              @foreach($modules->getUrlRange(1, $modules->lastPage()) as $page => $url)
                <a href="{{ $url }}" @if($page === $modules->currentPage()) aria-current="page" @endif aria-label="Página {{ $page }}">{{ $page }}</a>
              @endforeach
              @if($modules->hasMorePages())<a href="{{ $modules->nextPageUrl() }}" rel="next" aria-label="Página siguiente">›</a>@else<span class="is-disabled" aria-disabled="true">›</span>@endif
            </nav>
          @endif
        </div>
      </section>

      <section class="superadmin-native-bottom" data-admin-filter-panel="activity">
        <article>
          <div class="section-title compact-title">
            <div>
              <p class="eyebrow">Accesos</p>
              <h2>Usuarios recientes</h2>
            </div>
            <span>{{ $users->count() }}</span>
          </div>
          <div class="superadmin-native-user-list">
            @foreach ($users as $user)
              <form method="post" action="{{ route('superadmin.users.update', $user) }}">
                @csrf
                @method('patch')
                <div>
                  <strong>{{ $user->name }}</strong>
                  <span>{{ $user->username }} / {{ $roleLabels[$user->role] ?? $user->role }}</span>
                </div>
                <select name="status">
                  @foreach (['active', 'inactive', 'suspended'] as $status)
                    <option value="{{ $status }}" @selected($user->status === $status)>{{ $statusText($status) }}</option>
                  @endforeach
                </select>
                <button type="submit">Guardar</button>
              </form>
            @endforeach
          </div>
        </article>

        <article>
          <div class="section-title compact-title">
            <div>
              <p class="eyebrow">Bitacora</p>
              <h2>Auditoria reciente</h2>
            </div>
            <span>{{ $auditLogs->count() }}</span>
          </div>
          <div class="compact-list">
            @forelse ($auditLogs as $log)
              <div>
                <strong>{{ $log->event }}</strong>
                <span>{{ $log->user?->name ?? 'Sistema' }} / {{ $log->created_at?->format('d/m/Y H:i') }}</span>
              </div>
            @empty
              <p class="empty-state">Todavia no hay eventos de auditoria.</p>
            @endforelse
          </div>
        </article>
      </section>
    </section>
  </div>
@endsection
