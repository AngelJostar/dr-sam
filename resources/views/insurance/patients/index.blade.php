@extends('layouts.app', ['title' => 'Pacientes asegurados'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._topbar')
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      @include('insurance._flash')

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Pacientes</p>
          <h1>Pacientes asegurados</h1>
          <p>Consulta, filtrado y seguimiento de asegurados ligados a usuarios de la plataforma.</p>
        </div>
        @if($abilities['manage_patients'])
          <a class="primary-button action-link" href="{{ route('insurance.patients.create') }}">Alta paciente</a>
        @endif
      </section>

      <form class="filter-bar insurance-filter-bar" method="get">
        <label>
          Buscar
          <input name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nombre, CURP, RFC, poliza">
        </label>
        <label>
          Estatus
          <select name="status">
            <option value="">Todos</option>
            @foreach(['active' => 'Activo', 'suspended' => 'Suspendido', 'discharged' => 'Dado de baja', 'deceased' => 'Fallecido'] as $key => $label)
              <option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label>
          Riesgo
          <select name="risk_level">
            <option value="">Todos</option>
            @foreach(['low' => 'Bajo', 'medium' => 'Medio', 'high' => 'Alto', 'critical' => 'Critico'] as $key => $label)
              <option value="{{ $key }}" @selected(($filters['risk_level'] ?? '') === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <label>
          Diagnostico
          <input name="diagnosis" value="{{ $filters['diagnosis'] ?? '' }}" placeholder="Diabetes, cancer">
        </label>
        <button type="submit">Filtrar</button>
      </form>

      <section class="table-card insurance-table-panel">
        <div class="insurance-health-native-table-title">
          <h2>Pacientes asegurados</h2>
          <span>{{ $patients->total() }} registros</span>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Poliza</th>
              <th>Riesgo</th>
              <th>Estatus</th>
              <th>Actividad</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($patients as $patient)
              @php($policy = $patient->insurancePolicies->first())
              <tr>
                <td>
                  <strong>{{ $patient->full_name }}</strong>
                  <span>{{ $patient->email ?? 'Sin correo' }} / {{ $patient->phone ?? 'Sin telefono' }}</span>
                </td>
                <td>
                  <strong>{{ $policy?->policy_number ?? 'Sin poliza' }}</strong>
                  <span>{{ $policy?->insurer_name ?? 'Sin aseguradora' }}</span>
                </td>
                <td><span class="badge risk-{{ $patient->risk_level }}">{{ $patient->risk_level }}</span></td>
                <td><span class="badge">{{ $patient->status }}</span></td>
                <td>
                  <span>{{ $patient->diagnoses_count }} dx</span>
                  <span>{{ $patient->treatments_count }} tx</span>
                  <span>{{ $patient->hospitalizations_count }} hosp</span>
                </td>
                <td><a class="text-link" href="{{ route('insurance.patients.show', $patient) }}">Expediente</a></td>
              </tr>
            @empty
              <tr>
                <td colspan="6">No hay pacientes con esos filtros.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </section>

      <div class="pagination-wrap">{{ $patients->links() }}</div>
    </main>
  </div>
@endsection
