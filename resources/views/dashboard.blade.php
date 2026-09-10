@extends('layouts.app', ['title' => 'Dashboard Dr. Sam'])

@section('content')
  <section class="page-heading">
    <div>
      <p class="eyebrow">Sesion de revision</p>
      <h1>{{ $user->name }}</h1>
      <p>Rol: {{ $user->role }} / Modulo inicial: {{ $user->module }}</p>
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
          <span>{{ $module['label'] }}</span>
          <small>{{ $module['target'] }}</small>
          @if (in_array($module['key'], ['operational', 'operational_outpatient', 'external_pharmacy'], true))
            <strong class="module-card-access">Acceder</strong>
          @endif
        </a>
      @endforeach
    </div>
  </section>
@endsection
