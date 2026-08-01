@extends('layouts.app', ['title' => 'Superadministrador'])

@section('body_class', 'superadmin-native-body')

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
    'institution' => 'Catalogo institucional, unidades, servicios, contratos y cobertura.',
    'operational' => 'Solicitudes, autorizaciones, historial operativo y reportes por area.',
    'provider_npt' => 'Catalogos de productos, solicitudes NPT y quimioterapias, estatus de central y reportes.',
    'provider_import' => 'Expedientes de importacion, documentos del paciente, permiso Cofepris, aduana y entrega en almacen.',
    'external_pharmacy' => 'Operacion de farmacia externa, estatus de pedidos y rutas de entrega.',
    'digital_pharmacy' => 'Catalogo de farmacia, pedidos, surtido y trazabilidad de despacho.',
    'doctor' => 'Consulta medica, recetas, expediente clinico y seguimiento de pacientes.',
    'patient' => 'Portal de pacientes, pedidos, recetas y seguimiento personal.',
    'messenger' => 'Rutas de entrega, evidencia, confirmaciones y cierre de pedidos.',
    'insurance_health' => 'Aseguradora, pacientes, autorizaciones, facturacion, documentos y reportes.',
    'insurance_advisor' => 'Polizas GMM, alertas, entregas y seguimiento por asesor.',
    'orders' => 'Pedidos de paciente, carrito y solicitudes de farmacia.',
    'unit' => 'Agenda, inventario, solicitudes y administracion por unidad medica.',
    'superadmin' => 'Gobierno modular, catalogos globales, usuarios y permisos.',
  ];

  $moduleDepartments = [
    'doctor' => 'Direccion Medica',
    'patient' => 'Atencion a Pacientes',
    'superadmin' => 'Gobierno modular',
  ];

@endphp

@section('content')
  <div class="superadmin-native-screen">
    @include('superadmin._sidebar', ['active' => 'dashboard'])

    <section class="superadmin-native-workspace">
      @if (session('status'))
        <div class="notice success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <header class="superadmin-native-header">
        <div>
          <p class="eyebrow">Modulo superadministrador</p>
          <h1>Gobierno de modulos</h1>
          <p>{{ $activeModules }} de {{ $totalModules }} modulos activos</p>
        </div>
        <div class="superadmin-native-actions">
          <a href="{{ route('superadmin.dashboard') }}">â†» Restablecer</a>
          <strong>{{ auth()->user()?->name ?? 'Superadministrador' }} - {{ now()->format('d/m/Y') }}</strong>
        </div>
      </header>

      <section class="superadmin-native-metrics" aria-label="Indicadores superadministrador">
        @foreach ($metrics as $label => $value)
          <article>
            <span>{{ $label }}</span>
            <strong>{{ number_format($value) }}</strong>
            <small>{{ $metricDescriptions[$label] ?? 'Administrable' }}</small>
          </article>
        @endforeach
      </section>

      <section class="superadmin-native-modules">
        <div class="superadmin-native-section-heading">
          <div>
            <h2>Estado de modulos</h2>
            <p>{{ $activeModules }} de {{ $totalModules }} modulos activos</p>
          </div>
          <a href="{{ route('superadmin.catalog', 'modules') }}">â†’ Administrar</a>
        </div>

        <div class="superadmin-native-module-grid">
          @foreach ($modules as $module)
            <article class="superadmin-native-module-card">
              <div class="superadmin-native-module-status">
                <span @class(['is-disabled' => ! $module->enabled])></span>
                {{ $module->enabled ? 'Activo' : 'Inactivo' }}
              </div>
              <h3>{{ $module->label }}</h3>
              <small>{{ $moduleDepartments[$module->key] ?? 'Direccion Administrativa' }}</small>
              <p>{{ $moduleDescriptions[$module->key] ?? 'Modulo administrable de plataforma.' }}</p>
              <div class="superadmin-native-module-footer">
                <strong>{{ count($module->roles ?? []) }} perfiles</strong>
                <a href="{{ route('superadmin.catalog', ['section' => 'modules', 'selected_module' => $module->key]) }}#module-config">âŒ Editar</a>
              </div>
            </article>
          @endforeach
        </div>
      </section>

      <section class="superadmin-native-bottom">
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
