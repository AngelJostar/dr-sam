@extends('layouts.app', ['title' => 'Autorizaciones'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      @include('insurance._flash')

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Autorizaciones</p>
          <h1>Solicitudes y vigencias</h1>
          <p>Medicamentos, hospitalizaciones, procedimientos, estudios y prorrogas de estancia.</p>
        </div>
      </section>

  @if($abilities['manage_authorizations'])
    <form class="insurance-form insurance-entry-form compact" method="post" action="{{ route('insurance.authorizations.store') }}">
      @csrf
      <section class="form-section insurance-form-panel">
        <h2>Nueva solicitud</h2>
        <div class="form-grid">
          <label>Paciente
            <select name="patient_id" required>
              @foreach($patients as $patient)
                <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Tipo
            <select name="type">
              <option value="medication">Medicamento</option>
              <option value="hospitalization">Hospitalizacion</option>
              <option value="procedure">Procedimiento</option>
              <option value="study">Estudio</option>
              <option value="stay_extension">Prorroga estancia</option>
            </select>
          </label>
          <label>Tratamiento
            <select name="treatment_id">
              <option value="">Sin ligar</option>
              @foreach($treatments as $treatment)
                <option value="{{ $treatment->id }}">{{ $treatment->patient?->full_name }} / {{ $treatment->medication_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Hospitalizacion
            <select name="hospitalization_id">
              <option value="">Sin ligar</option>
              @foreach($hospitalizations as $hospitalization)
                <option value="{{ $hospitalization->id }}">{{ $hospitalization->patient?->full_name }} / {{ $hospitalization->hospital_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Fecha solicitud<input type="date" name="requested_at" value="{{ now()->format('Y-m-d') }}"></label>
          <label>Fecha respuesta<input type="date" name="responded_at"></label>
          <label>Estatus
            <select name="status">
              <option value="requested">Solicitada</option>
              <option value="in_review">En revision</option>
              <option value="authorized">Autorizada</option>
              <option value="rejected">Rechazada</option>
              <option value="expired">Vencida</option>
            </select>
          </label>
          <label>Numero<input name="authorization_number"></label>
          <label>Monto<input type="number" step="0.01" min="0" name="authorized_amount" value="0"></label>
          <label>Vigencia<input type="date" name="valid_until"></label>
          <label class="span-2">Justificacion<textarea name="justification"></textarea></label>
        </div>
        <button type="submit">Crear autorizacion</button>
      </section>
    </form>
  @endif

  <section class="table-card insurance-table-panel">
    <table class="data-table">
      <thead>
        <tr>
          <th>Paciente</th>
          <th>Tipo</th>
          <th>Numero</th>
          <th>Vigencia</th>
          <th>Monto</th>
          <th>Estatus</th>
        </tr>
      </thead>
      <tbody>
        @forelse($authorizations as $authorization)
          <tr>
            <td><a class="text-link" href="{{ route('insurance.patients.show', $authorization->patient) }}">{{ $authorization->patient?->full_name }}</a></td>
            <td>{{ $authorization->type }}</td>
            <td>{{ $authorization->authorization_number ?? 'Sin numero' }}</td>
            <td>{{ $authorization->valid_until?->format('d/m/Y') ?? 'Sin vigencia' }}</td>
            <td>${{ number_format((float) $authorization->authorized_amount, 2) }}</td>
            <td><span class="badge">{{ $authorization->status }}</span></td>
          </tr>
        @empty
          <tr><td colspan="6">Sin autorizaciones.</td></tr>
        @endforelse
      </tbody>
    </table>
  </section>

      <div class="pagination-wrap">{{ $authorizations->links() }}</div>
    </main>
  </div>
@endsection
