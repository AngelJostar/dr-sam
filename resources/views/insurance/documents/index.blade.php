@extends('layouts.app', ['title' => 'Documentos'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._topbar')
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      @include('insurance._flash')

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Documental</p>
          <h1>Control documental</h1>
          <p>Clasificacion de identificaciones, polizas, recetas, autorizaciones, estudios, facturas y evidencias.</p>
        </div>
      </section>

  @if($abilities['manage_documents'])
    <form class="insurance-form insurance-entry-form compact" method="post" action="{{ route('insurance.documents.store') }}" enctype="multipart/form-data">
      @csrf
      <section class="form-section insurance-form-panel">
        <h2>Subir documento</h2>
        <div class="form-grid">
          <label>Paciente
            <select name="patient_id">
              <option value="">Sin ligar</option>
              @foreach($patients as $patient)
                <option value="{{ $patient->id }}">{{ $patient->full_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Nombre<input name="name" required></label>
          <label>Tipo
            <select name="document_type">
              <option value="official_id">Identificacion oficial</option>
              <option value="policy">Poliza</option>
              <option value="prescription">Receta medica</option>
              <option value="treatment_authorization">Autorizacion tratamiento</option>
              <option value="clinical_summary">Resumen clinico</option>
              <option value="laboratory">Laboratorio</option>
              <option value="imaging">Imagen</option>
              <option value="invoice_pdf">Factura PDF</option>
              <option value="fiscal_xml">XML fiscal</option>
              <option value="medical_letter">Carta medica</option>
              <option value="informed_consent">Consentimiento informado</option>
              <option value="delivery_evidence">Evidencia entrega</option>
              <option value="other">Otros</option>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="current">Vigente</option>
              <option value="expired">Vencido</option>
              <option value="replaced">Sustituido</option>
              <option value="rejected">Rechazado</option>
            </select>
          </label>
          <label>Vence<input type="date" name="expires_at"></label>
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
          <label>Factura
            <select name="invoice_id">
              <option value="">Sin ligar</option>
              @foreach($invoices as $invoice)
                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }}</option>
              @endforeach
            </select>
          </label>
          <label class="span-2">Archivo<input type="file" name="document_file"></label>
        </div>
        <button type="submit">Registrar documento</button>
      </section>
    </form>
  @endif

  <form class="filter-bar insurance-filter-bar" method="get">
    <label>Tipo<input name="type" value="{{ request('type') }}" placeholder="policy, fiscal_xml"></label>
    <label>Estatus
      <select name="status">
        <option value="">Todos</option>
        @foreach(['current', 'expired', 'replaced', 'rejected'] as $status)
          <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
        @endforeach
      </select>
    </label>
    <button type="submit">Filtrar</button>
  </form>

  <section class="table-card insurance-table-panel">
    <table class="data-table">
      <thead>
        <tr>
          <th>Documento</th>
          <th>Paciente</th>
          <th>Tipo</th>
          <th>Carga</th>
          <th>Vence</th>
          <th>Estatus</th>
        </tr>
      </thead>
      <tbody>
        @forelse($documents as $document)
          <tr>
            <td>
              <strong>{{ $document->name }}</strong>
              <span>{{ $document->file_path ?? 'Sin archivo fisico' }}</span>
            </td>
            <td>{{ $document->patient?->full_name ?? 'Sin paciente' }}</td>
            <td>{{ $document->document_type }}</td>
            <td>{{ $document->loaded_at?->format('d/m/Y H:i') }}</td>
            <td>{{ $document->expires_at?->format('d/m/Y') ?? 'Sin vencimiento' }}</td>
            <td><span class="badge">{{ $document->status }}</span></td>
          </tr>
        @empty
          <tr><td colspan="6">Sin documentos.</td></tr>
        @endforelse
      </tbody>
    </table>
  </section>

      <div class="pagination-wrap">{{ $documents->links() }}</div>
    </main>
  </div>
@endsection
