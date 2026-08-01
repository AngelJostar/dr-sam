@extends('layouts.app', ['title' => 'Expediente '.$patient->full_name])

@section('content')
  @include('insurance._nav')
  @include('insurance._flash')

  @php($policy = $patient->insurancePolicies->sortByDesc('created_at')->first())

  <section class="record-header insurance-record-header">
    <div>
      <p class="eyebrow">Expediente del paciente</p>
      <h1>{{ $patient->full_name }}</h1>
      <p>{{ $policy?->insurer_name ?? 'Sin aseguradora' }} / Poliza {{ $policy?->policy_number ?? 'sin poliza' }}</p>
    </div>
    <div class="record-actions">
      <span class="badge risk-{{ $patient->risk_level }}">{{ $patient->risk_level }}</span>
      <span class="badge">{{ $patient->status }}</span>
      @if($abilities['manage_patients'])
        <a class="secondary-button" href="{{ route('insurance.patients.edit', $patient) }}">Editar</a>
      @endif
    </div>
  </section>

  <section class="record-summary insurance-record-summary">
    <article>
      <span>CURP</span>
      <strong>{{ $patient->curp ?? 'No capturada' }}</strong>
    </article>
    <article>
      <span>RFC</span>
      <strong>{{ $patient->rfc ?? 'No capturado' }}</strong>
    </article>
    <article>
      <span>Edad</span>
      <strong>{{ $patient->birth_date ? $patient->birth_date->age.' anos' : 'No disponible' }}</strong>
    </article>
    <article>
      <span>Usuario plataforma</span>
      <strong>{{ $patient->user?->username ?? 'Sin usuario' }}</strong>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Datos generales</h2>
      </div>
      <dl class="detail-list">
        <div><dt>Telefono</dt><dd>{{ $patient->phone ?? 'Sin telefono' }}</dd></div>
        <div><dt>Correo</dt><dd>{{ $patient->email ?? 'Sin correo' }}</dd></div>
        <div><dt>Direccion</dt><dd>{{ $patient->address ?? 'Sin direccion' }}</dd></div>
        <div><dt>Medico tratante</dt><dd>{{ $patient->primaryDoctor?->full_name ?? 'Sin asignar' }}</dd></div>
        <div><dt>Alta programa</dt><dd>{{ $patient->enrolled_at?->format('d/m/Y') ?? 'Sin fecha' }}</dd></div>
        <div><dt>Observaciones</dt><dd>{{ $patient->general_observations ?? 'Sin observaciones' }}</dd></div>
      </dl>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Poliza</h2>
      </div>
      <dl class="detail-list">
        <div><dt>Numero</dt><dd>{{ $policy?->policy_number ?? 'Sin poliza' }}</dd></div>
        <div><dt>Aseguradora</dt><dd>{{ $policy?->insurer_name ?? 'Sin aseguradora' }}</dd></div>
        <div><dt>Plan</dt><dd>{{ $policy?->plan_name ?? 'Sin plan' }}</dd></div>
        <div><dt>Empresa</dt><dd>{{ $policy?->employer_name ?? 'No aplica' }}</dd></div>
      </dl>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Diagnosticos</h2>
        <span>{{ $patient->diagnoses->count() }}</span>
      </div>
      <div class="compact-list">
        @forelse($patient->diagnoses as $diagnosis)
          <div>
            <strong>{{ $diagnosis->condition_name }}</strong>
            <span>{{ $diagnosis->cie10 ?? 'Sin CIE-10' }} / {{ $diagnosis->status }}</span>
          </div>
        @empty
          <p class="empty-state">Sin diagnosticos.</p>
        @endforelse
      </div>

      @if($abilities['manage_treatments'])
        <form class="inline-form insurance-inline-form" method="post" action="{{ route('insurance.diagnoses.store') }}">
          @csrf
          <input type="hidden" name="patient_id" value="{{ $patient->id }}">
          <label>Padecimiento
            <select name="chronic_condition_id" onchange="this.form.condition_name.value=this.options[this.selectedIndex].text">
              <option value="">Otra</option>
              @foreach($conditions as $condition)
                <option value="{{ $condition->id }}">{{ $condition->name }}</option>
              @endforeach
            </select>
          </label>
          <label>Nombre diagnostico<input name="condition_name" required></label>
          <label>CIE-10<input name="cie10"></label>
          <label>Fecha diagnostico<input type="date" name="diagnosed_at"></label>
          <label>Medico
            <select name="doctor_id">
              <option value="">Sin asignar</option>
              @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Especialidad<input name="specialty"></label>
          <label>Seguimiento<input name="follow_up_frequency" placeholder="Mensual, trimestral"></label>
          <label>Estatus
            <select name="status">
              <option value="controlled">Controlado</option>
              <option value="in_surveillance">En vigilancia</option>
              <option value="decompensated">Descompensado</option>
              <option value="critical">Critico</option>
            </select>
          </label>
          <label class="span-2">Tratamiento indicado<textarea name="indicated_treatment"></textarea></label>
          <label class="span-2">Estudios requeridos<textarea name="required_studies"></textarea></label>
          <button type="submit">Agregar diagnostico</button>
        </form>
      @endif
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Tratamientos activos</h2>
        <span>{{ $patient->treatments->where('status', 'active')->count() }}</span>
      </div>
      <div class="compact-list">
        @forelse($patient->treatments as $treatment)
          <div>
            <strong>{{ $treatment->medication_name }}</strong>
            <span>{{ $treatment->dose }} / {{ $treatment->frequency }} / {{ $treatment->status }}</span>
          </div>
        @empty
          <p class="empty-state">Sin tratamientos.</p>
        @endforelse
      </div>

      @if($abilities['manage_treatments'])
        <form class="inline-form insurance-inline-form" method="post" action="{{ route('insurance.treatments.store') }}">
          @csrf
          <input type="hidden" name="patient_id" value="{{ $patient->id }}">
          <label>Diagnostico
            <select name="patient_diagnosis_id">
              <option value="">Sin ligar</option>
              @foreach($patient->diagnoses as $diagnosis)
                <option value="{{ $diagnosis->id }}">{{ $diagnosis->condition_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Medicamento catalogo
            <select name="medication_id">
              <option value="">Captura manual</option>
              @foreach($medications as $medication)
                <option value="{{ $medication->id }}">{{ $medication->name }}</option>
              @endforeach
            </select>
          </label>
          <label>Nombre medicamento<input name="medication_name" required></label>
          <label>Sustancia activa<input name="active_substance"></label>
          <label>Presentacion<input name="presentation"></label>
          <label>Dosis<input name="dose"></label>
          <label>Frecuencia<input name="frequency"></label>
          <label>Duracion<input name="duration"></label>
          <label>Inicio<input type="date" name="starts_at"></label>
          <label>Termino<input type="date" name="ends_at"></label>
          <label>Medico prescribe
            <select name="prescribing_doctor_id">
              <option value="">Sin asignar</option>
              @foreach($doctors as $doctor)
                <option value="{{ $doctor->id }}">{{ $doctor->full_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="active">Activo</option>
              <option value="suspended">Suspendido</option>
              <option value="changed">Cambiado</option>
              <option value="finished">Terminado</option>
            </select>
          </label>
          <label class="checkbox-line"><input type="checkbox" name="requires_authorization" value="1"> Requiere autorizacion</label>
          <label class="span-2">Motivo de cambio<textarea name="change_reason"></textarea></label>
          <button type="submit">Registrar tratamiento</button>
        </form>
      @endif
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Entregas de medicamentos</h2>
        <a class="text-link" href="{{ route('insurance.deliveries.index') }}">Ver todas</a>
      </div>
      <div class="compact-list">
        @forelse($patient->medicationDeliveries->sortByDesc('scheduled_delivery_date')->take(8) as $delivery)
          <div>
            <strong>{{ $delivery->treatment?->medication_name ?? 'Medicamento' }}</strong>
            <span>{{ $delivery->scheduled_delivery_date?->format('d/m/Y') }} / {{ $delivery->status }}</span>
          </div>
        @empty
          <p class="empty-state">Sin entregas.</p>
        @endforelse
      </div>

      @if($abilities['manage_deliveries'])
        <form class="inline-form insurance-inline-form" method="post" action="{{ route('insurance.deliveries.store') }}">
          @csrf
          <input type="hidden" name="patient_id" value="{{ $patient->id }}">
          <label>Tratamiento
            <select name="treatment_id">
              <option value="">Sin ligar</option>
              @foreach($patient->treatments as $treatment)
                <option value="{{ $treatment->id }}">{{ $treatment->medication_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Cantidad<input type="number" min="0" name="quantity_delivered" value="1" required></label>
          <label>Periodo cubierto<input name="covered_period" placeholder="30 dias"></label>
          <label>Fecha programada<input type="date" name="scheduled_delivery_date" required></label>
          <label>Responsable<input name="delivery_responsible"></label>
          <label>Estatus
            <select name="status">
              <option value="pending">Pendiente</option>
              <option value="in_route">En ruta</option>
              <option value="delivered">Entregado</option>
              <option value="rescheduled">Reprogramado</option>
            </select>
          </label>
          <label class="span-2">Domicilio entrega<textarea name="delivery_address">{{ $patient->address }}</textarea></label>
          <button type="submit">Programar entrega</button>
        </form>
      @endif
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Hospitalizaciones</h2>
        <a class="text-link" href="{{ route('insurance.hospitalizations.index') }}">Ver tablero</a>
      </div>
      <div class="compact-list">
        @forelse($patient->hospitalizations->sortByDesc('admitted_at')->take(8) as $hospitalization)
          <a href="{{ route('insurance.hospitalizations.show', $hospitalization) }}">
            <strong>{{ $hospitalization->hospital_name ?? $hospitalization->hospital?->name ?? 'Hospital' }}</strong>
            <span>{{ $hospitalization->admitted_at?->format('d/m/Y') }} / {{ $hospitalization->status }} / {{ $hospitalization->stay_days }} dias</span>
          </a>
        @empty
          <p class="empty-state">Sin hospitalizaciones.</p>
        @endforelse
      </div>

      @if($abilities['manage_hospitalizations'])
        <form class="inline-form insurance-inline-form" method="post" action="{{ route('insurance.hospitalizations.store') }}">
          @csrf
          <input type="hidden" name="patient_id" value="{{ $patient->id }}">
          <label>Hospital
            <select name="hospital_id">
              <option value="">Captura manual</option>
              @foreach($hospitals as $hospital)
                <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
              @endforeach
            </select>
          </label>
          <label>Nombre hospital<input name="hospital_name"></label>
          <label>Ingreso<input type="datetime-local" name="admitted_at" required></label>
          <label>Area
            <select name="area">
              <option value="urgencias">Urgencias</option>
              <option value="hospitalizacion">Hospitalizacion</option>
              <option value="uci">UCI</option>
              <option value="quirofano">Quirofano</option>
              <option value="terapia_intermedia">Terapia intermedia</option>
            </select>
          </label>
          <label>Tipo evento
            <select name="event_type">
              <option value="programmed">Programado</option>
              <option value="emergency">Urgencia</option>
              <option value="complication">Complicacion</option>
              <option value="relapse">Recaida</option>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="active">Activa</option>
              <option value="in_review">En revision</option>
              <option value="discharged">Egresada</option>
            </select>
          </label>
          <label>Autorizacion<input name="authorization_number"></label>
          <label>Monto autorizado<input type="number" step="0.01" min="0" name="authorized_amount" value="0"></label>
          <label class="span-2">Motivo<textarea name="reason"></textarea></label>
          <label class="span-2">Diagnostico ingreso<input name="admission_diagnosis"></label>
          <button type="submit">Registrar hospitalizacion</button>
        </form>
      @endif
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Autorizaciones</h2>
        <a class="text-link" href="{{ route('insurance.authorizations.index') }}">Ver todas</a>
      </div>
      <div class="compact-list">
        @forelse($patient->authorizations->sortByDesc('requested_at')->take(8) as $authorization)
          <div>
            <strong>{{ $authorization->type }} / {{ $authorization->status }}</strong>
            <span>{{ $authorization->authorization_number ?? 'Sin numero' }} / ${{ number_format((float) $authorization->authorized_amount, 2) }}</span>
          </div>
        @empty
          <p class="empty-state">Sin autorizaciones.</p>
        @endforelse
      </div>

      @if($abilities['manage_authorizations'])
        <form class="inline-form insurance-inline-form" method="post" action="{{ route('insurance.authorizations.store') }}">
          @csrf
          <input type="hidden" name="patient_id" value="{{ $patient->id }}">
          <label>Tipo
            <select name="type">
              <option value="medication">Medicamento</option>
              <option value="hospitalization">Hospitalizacion</option>
              <option value="procedure">Procedimiento</option>
              <option value="study">Estudio</option>
              <option value="stay_extension">Prorroga estancia</option>
            </select>
          </label>
          <label>Estatus
            <select name="status">
              <option value="requested">Solicitada</option>
              <option value="in_review">En revision</option>
              <option value="authorized">Autorizada</option>
              <option value="rejected">Rechazada</option>
              <option value="expired">Vencida</option>
            </select>
          </label>
          <label>Fecha solicitud<input type="date" name="requested_at" value="{{ now()->format('Y-m-d') }}"></label>
          <label>Numero autorizacion<input name="authorization_number"></label>
          <label>Monto autorizado<input type="number" step="0.01" min="0" name="authorized_amount" value="0"></label>
          <label>Vigencia<input type="date" name="valid_until"></label>
          <label class="span-2">Justificacion<textarea name="justification"></textarea></label>
          <button type="submit">Solicitar autorizacion</button>
        </form>
      @endif
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Documentos</h2>
        <a class="text-link" href="{{ route('insurance.documents.index') }}">Ver documentos</a>
      </div>
      <div class="compact-list">
        @forelse($patient->documents->sortByDesc('loaded_at')->take(8) as $document)
          <div>
            <strong>{{ $document->name }}</strong>
            <span>{{ $document->document_type }} / {{ $document->status }}</span>
          </div>
        @empty
          <p class="empty-state">Sin documentos.</p>
        @endforelse
      </div>

      @if($abilities['manage_documents'])
        <form class="inline-form insurance-inline-form" method="post" action="{{ route('insurance.documents.store') }}" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="patient_id" value="{{ $patient->id }}">
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
          <label class="span-2">Archivo<input type="file" name="document_file"></label>
          <button type="submit">Adjuntar documento</button>
        </form>
      @endif
    </article>
  </section>

  <section class="insurance-panel insurance-detail-panel insurance-timeline-panel">
    <div class="section-title">
      <h2>Linea de tiempo</h2>
      <span>{{ $timeline->count() }} eventos</span>
    </div>
    <div class="timeline">
      @forelse($timeline as $event)
        <div>
          <time>{{ $event['date']?->format('d/m/Y') }}</time>
          <strong>{{ $event['title'] }}</strong>
          <span>{{ $event['detail'] }}</span>
        </div>
      @empty
        <p class="empty-state">Sin eventos historicos.</p>
      @endforelse
    </div>
  </section>
@endsection
