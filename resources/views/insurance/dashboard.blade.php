@extends('layouts.app', ['title' => 'Aseguradora salud'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      @include('insurance._flash')

      <div class="insurance-health-native-pagebar">
        <div class="insurance-health-native-page-title">
          <p class="eyebrow">Modulo aseguradora</p>
          <h1>Control clinico-administrativo</h1>
          <p>Trazabilidad de pacientes asegurados, tratamientos continuos, entregas, hospitalizaciones, autorizaciones y facturacion.</p>
        </div>
        <div class="insurance-health-native-session">
          <span>{{ auth()->user()?->name ?? 'Superadministrador' }}</span>
          <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Salir</button>
          </form>
        </div>
      </div>

      <section class="insurance-health-native-hero">
        <div>
          <p class="eyebrow">Vista general</p>
          <h2>Gestion clinico-administrativa</h2>
          <p>Resumen operativo de pacientes, riesgos, entregas, hospitalizaciones, autorizaciones y facturacion.</p>
        </div>
        @if($abilities['manage_patients'])
          <a class="insurance-health-native-primary-action" href="{{ route('insurance.patients.create') }}">Nuevo paciente</a>
        @endif
      </section>

      <section class="insurance-health-native-metrics" aria-label="Indicadores operativos">
        @foreach($metrics as $label => $value)
          <article>
            <span>{{ $label }}</span>
            <strong>{{ is_float($value) ? '$'.number_format($value, 2) : number_format($value) }}</strong>
          </article>
        @endforeach
      </section>

      <section class="insurance-health-native-grid">
        <article class="insurance-health-native-panel">
          <div class="insurance-health-native-heading">
            <h2>Pacientes por diagnostico</h2>
            <span>{{ $patientsByDiagnosis->count() }} grupos</span>
          </div>
          <div class="insurance-health-native-list">
            @forelse($patientsByDiagnosis as $row)
              <div>
                <strong>{{ $row->label }}</strong>
                <span>{{ $row->total }} pacientes</span>
              </div>
            @empty
              <p>Sin diagnosticos registrados.</p>
            @endforelse
          </div>
        </article>

        <article class="insurance-health-native-panel">
          <div class="insurance-health-native-heading">
            <h2>Alertas operativas</h2>
            <span>{{ $alerts->count() }} visibles</span>
          </div>
          <div class="insurance-health-native-alerts">
            @forelse($alerts as $alert)
              <a class="{{ $alert['severity'] }}" href="{{ $alert['url'] }}">
                <span>{{ $alert['type'] }}</span>
                <strong>{{ $alert['message'] }}</strong>
              </a>
            @empty
              <p>No hay alertas criticas por atender.</p>
            @endforelse
          </div>
        </article>
      </section>

    </main>
  </div>
@endsection
