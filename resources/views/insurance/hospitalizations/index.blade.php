@extends('layouts.app', ['title' => 'Hospitalizaciones'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._topbar')
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      @include('insurance._flash')

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Hospitalizaciones</p>
          <h1>Eventos hospitalarios</h1>
          <p>Ingreso, estancia, egreso, autorizaciones pendientes y facturacion asociada.</p>
        </div>
      </section>

      @if($abilities['manage_hospitalizations'])
        <form class="insurance-form insurance-entry-form compact" method="post" action="{{ route('insurance.hospitalizations.store') }}">
          @csrf
          <section class="form-section insurance-form-panel">
            <h2>Registrar hospitalizacion</h2>
            <div class="form-grid">
              <label>Paciente
                <select name="patient_id" required>
                  @foreach($patients as $patient)
                    <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
                  @endforeach
                </select>
              </label>
              <label>Hospital
                <select name="hospital_id">
                  <option value="">Manual</option>
                  @foreach($hospitals as $hospital)
                    <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
                  @endforeach
                </select>
              </label>
              <label>Hospital manual<input name="hospital_name"></label>
              <label>Medico
                <select name="doctor_id">
                  <option value="">Sin asignar</option>
                  @foreach($doctors as $doctor)
                    <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
                  @endforeach
                </select>
              </label>
              <label>Ingreso<input type="datetime-local" name="admitted_at" required></label>
              <label>Egreso<input type="datetime-local" name="discharged_at"></label>
              <label>Area
                <select name="area">
                  <option value="urgencias">Urgencias</option>
                  <option value="hospitalizacion">Hospitalizacion</option>
                  <option value="uci">UCI</option>
                  <option value="quirofano">Quirofano</option>
                  <option value="terapia_intermedia">Terapia intermedia</option>
                </select>
              </label>
              <label>Estatus
                <select name="status">
                  <option value="active">Activa</option>
                  <option value="in_review">En revision</option>
                  <option value="discharged">Egresada</option>
                  <option value="billed">Facturada</option>
                </select>
              </label>
              <label>Tipo
                <select name="event_type">
                  <option value="programmed">Programado</option>
                  <option value="emergency">Urgencia</option>
                  <option value="complication">Complicacion</option>
                  <option value="relapse">Recaida</option>
                </select>
              </label>
              <label>Autorizacion<input name="authorization_number"></label>
              <label>Monto autorizado<input type="number" min="0" step="0.01" name="authorized_amount" value="0"></label>
              <label class="span-2">Motivo<textarea name="reason"></textarea></label>
            </div>
            <button type="submit">Registrar</button>
          </section>
        </form>
      @endif

      <form class="filter-bar insurance-filter-bar" method="get">
        <label>Estatus
          <select name="status">
            <option value="">Todos</option>
            @foreach(['active', 'discharged', 'cancelled', 'in_review', 'billed'] as $status)
              <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
            @endforeach
          </select>
        </label>
        <label>Hospital<input name="hospital" value="{{ request('hospital') }}"></label>
        <button type="submit">Filtrar</button>
      </form>

      <section class="table-card insurance-table-panel">
        <div class="insurance-health-native-table-title">
          <h2>Hospitalizaciones</h2>
          <span>{{ $hospitalizations->total() }} registros</span>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Hospital</th>
              <th>Ingreso</th>
              <th>Area</th>
              <th>Dias</th>
              <th>Estatus</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse($hospitalizations as $hospitalization)
              <tr>
                <td>{{ $hospitalization->patient?->full_name }}</td>
                <td>{{ $hospitalization->hospital_name ?? $hospitalization->hospital?->name }}</td>
                <td>{{ $hospitalization->admitted_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $hospitalization->area }}</td>
                <td>{{ $hospitalization->stay_days }}</td>
                <td><span class="badge">{{ $hospitalization->status }}</span></td>
                <td><a class="text-link" href="{{ route('insurance.hospitalizations.show', $hospitalization) }}">Detalle</a></td>
              </tr>
            @empty
              <tr><td colspan="7">Sin hospitalizaciones.</td></tr>
            @endforelse
          </tbody>
        </table>
      </section>

      <div class="pagination-wrap">{{ $hospitalizations->links() }}</div>
    </main>
  </div>
@endsection
