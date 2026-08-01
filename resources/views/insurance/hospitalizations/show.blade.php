@extends('layouts.app', ['title' => 'Detalle hospitalizacion'])

@section('content')
  @include('insurance._nav')
  @include('insurance._flash')

  <section class="record-header insurance-record-header">
    <div>
      <p class="eyebrow">Detalle hospitalario</p>
      <h1>{{ $hospitalization->patient?->full_name }}</h1>
      <p>{{ $hospitalization->hospital_name ?? $hospitalization->hospital?->name }} / {{ $hospitalization->stay_days }} dias de estancia</p>
    </div>
    <div class="record-actions">
      <span class="badge">{{ $hospitalization->status }}</span>
      <a class="secondary-button" href="{{ route('insurance.patients.show', $hospitalization->patient) }}">Expediente</a>
    </div>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title"><h2>Datos del evento</h2></div>
      <dl class="detail-list">
        <div><dt>Ingreso</dt><dd>{{ $hospitalization->admitted_at?->format('d/m/Y H:i') }}</dd></div>
        <div><dt>Egreso</dt><dd>{{ $hospitalization->discharged_at?->format('d/m/Y H:i') ?? 'Activo' }}</dd></div>
        <div><dt>Area</dt><dd>{{ $hospitalization->area }}</dd></div>
        <div><dt>Tipo</dt><dd>{{ $hospitalization->event_type }}</dd></div>
        <div><dt>Autorizacion</dt><dd>{{ $hospitalization->authorization_number ?? 'Sin numero' }}</dd></div>
        <div><dt>Monto autorizado</dt><dd>${{ number_format((float) $hospitalization->authorized_amount, 2) }}</dd></div>
        <div><dt>Motivo</dt><dd>{{ $hospitalization->reason ?? 'Sin motivo capturado' }}</dd></div>
      </dl>
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title">
        <h2>Facturacion</h2>
        <span>${{ number_format((float) $hospitalization->invoices->sum('total'), 2) }}</span>
      </div>
      <div class="compact-list">
        @forelse($hospitalization->invoices as $invoice)
          <div>
            <strong>{{ $invoice->invoice_number }} / {{ $invoice->status }}</strong>
            <span>{{ $invoice->fiscal_uuid ?? 'Sin UUID' }} / ${{ number_format((float) $invoice->total, 2) }}</span>
          </div>
        @empty
          <p class="empty-state">Sin facturas ligadas.</p>
        @endforelse
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-detail-panel insurance-timeline-panel">
      <div class="section-title">
        <h2>Bitacora diaria</h2>
        <span>{{ $hospitalization->dailyNotes->count() }} notas</span>
      </div>
      <div class="timeline">
        @forelse($hospitalization->dailyNotes->sortByDesc('note_date') as $note)
          <div>
            <time>{{ $note->note_date?->format('d/m/Y') }}</time>
            <strong>{{ $note->general_clinical_status ?? 'Seguimiento' }}</strong>
            <span>{{ $note->administrative_evolution }}</span>
          </div>
        @empty
          <p class="empty-state">Sin notas diarias.</p>
        @endforelse
      </div>

      @if($abilities['manage_hospitalizations'])
        <form class="inline-form insurance-inline-form" method="post" action="{{ route('insurance.hospitalization-notes.store') }}">
          @csrf
          <input type="hidden" name="hospitalization_id" value="{{ $hospitalization->id }}">
          <label>Fecha<input type="date" name="note_date" value="{{ now()->format('Y-m-d') }}" required></label>
          <label>Estatus clinico<input name="general_clinical_status"></label>
          <label>Posible egreso<input type="date" name="possible_discharge_date"></label>
          <label class="checkbox-line"><input type="checkbox" name="prolonged_stay_risk" value="1"> Riesgo de prolongacion</label>
          <label class="span-2">Evolucion administrativa<textarea name="administrative_evolution"></textarea></label>
          <label class="span-2">Cambios relevantes<textarea name="relevant_changes"></textarea></label>
          <label class="span-2">Pendientes<textarea name="pending_authorizations"></textarea></label>
          <button type="submit">Agregar nota</button>
        </form>
      @endif
    </article>

    <article class="insurance-panel insurance-detail-panel">
      <div class="section-title"><h2>Autorizaciones y documentos</h2></div>
      <div class="compact-list">
        @forelse($hospitalization->authorizations as $authorization)
          <div>
            <strong>{{ $authorization->type }} / {{ $authorization->status }}</strong>
            <span>{{ $authorization->authorization_number ?? 'Sin numero' }}</span>
          </div>
        @empty
          <p class="empty-state">Sin autorizaciones ligadas.</p>
        @endforelse
      </div>
      <div class="compact-list separated">
        @forelse($hospitalization->documents as $document)
          <div>
            <strong>{{ $document->name }}</strong>
            <span>{{ $document->document_type }} / {{ $document->status }}</span>
          </div>
        @empty
          <p class="empty-state">Sin documentos hospitalarios.</p>
        @endforelse
      </div>
    </article>
  </section>
@endsection
