@extends('layouts.app', ['title' => 'Dashboard Klini'])

@section('body_class', 'klini-access-body')

@php
  $moduleDescriptions = [
    'community_admin' => 'Comunidades, experiencias y conexiones que inspiran bienestar.',
    'superadmin' => 'Gobierno de la plataforma, usuarios y permisos.',
    'institution' => 'Una red organizada de instituciones y servicios.',
    'unit' => 'Agenda, inventario y operación de tu unidad.',
    'operational' => 'Solicitudes, autorizaciones y seguimiento operativo.',
    'operational_outpatient' => 'Atención y seguimiento de consulta externa.',
    'external_pharmacy' => 'Surtido y entregas de farmacia externa.',
    'digital_pharmacy' => 'Productos, pedidos y despacho de farmacia.',
    'orders' => 'Consulta y seguimiento de pedidos del paciente.',
    'insurance_health' => 'Atención, autorizaciones y cuentas conectadas.',
    'insurance_advisor' => 'Cartera de pólizas, renovaciones y siniestros.',
    'doctor' => 'Consultas, recetas y expediente clínico.',
    'patient' => 'Tu información de salud y seguimiento personal.',
    'provider_npt' => 'Productos y solicitudes de nutrición parenteral.',
    'provider_import' => 'Expedientes, permisos y trazabilidad de importación.',
    'provider_medicines' => 'Distribución y seguimiento de medicamentos.',
    'provider_clinical_labs' => 'Solicitudes y resultados de laboratorio.',
    'messenger' => 'Rutas, evidencias y confirmación de entregas.',
  ];
@endphp
@section('content')
  <section class="page-heading klini-access-hero">
    <div>
      <x-klini-brand inverse />
      <p class="eyebrow">Tu espacio de trabajo</p>
      <h1>{{ $user->name }}</h1>
      <p>Todo Klini. Una sola visión. Elige el módulo en el que quieres trabajar.</p>
    </div>
    <form method="post" action="{{ route('logout') }}" class="dashboard-logout-form">
      @csrf
      <button type="submit" class="dashboard-logout-button" aria-label="Cerrar sesión">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
        Cerrar sesión
      </button>
    </form>
  </section>

  <section class="metric-grid" aria-label="Resumen de datos">
    @foreach ($summary as $label => $value)
      <article class="metric-card">
        <span>{{ $label }}</span>
        <strong>{{ number_format($value) }}</strong>
      </article>
    @endforeach
  </section>

  <section class="module-section">
    <div class="section-title">
      <h2>Flujos disponibles</h2>
      <span>{{ $modules->count() }} modulos</span>
    </div>

    <div class="module-grid">
      @foreach ($modules as $module)
        <a class="module-card{{ $module['priority'] ? ' module-card-priority-'.$module['priority'] : '' }}" href="{{ $module['url'] }}">
          @if ($module['priority'])
            <span class="module-priority-mark">
              <i aria-hidden="true"></i>
              {{ $module['priority'] === 'high' ? 'Prioridad alta' : 'Prioridad media' }}
            </span>
          @endif
          <span class="klini-module-symbol" aria-hidden="true"><x-klini-icon name="{{ $module['key'] }}" /></span>
          <span>{{ $module['label'] }}</span>
          <small>{{ $moduleDescriptions[$module['key']] ?? 'Accede a tu espacio de trabajo y seguimiento.' }}</small>
<strong class="module-card-access">Acceder <span aria-hidden="true">↗</span></strong>
        </a>
      @endforeach
    </div>
  </section>
@endsection
