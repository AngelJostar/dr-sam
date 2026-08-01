@extends('layouts.app', ['title' => 'Reportes aseguradora'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Reportes</p>
          <h1>Reportes operativos</h1>
          <p>Consultas filtrables para seguimiento clinico-administrativo y control financiero.</p>
        </div>
      </section>

  <form class="filter-bar insurance-filter-bar" method="get">
    <label>Desde<input type="date" name="period_from" value="{{ $filters['period_from'] ?? '' }}"></label>
    <label>Hasta<input type="date" name="period_to" value="{{ $filters['period_to'] ?? '' }}"></label>
    <button type="submit">Actualizar</button>
  </form>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Pacientes activos por diagnostico</h2></div>
      <div class="compact-list">
        @foreach($reports['active_patients_by_diagnosis'] as $row)
          <div><strong>{{ $row->label }}</strong><span>{{ $row->total }} pacientes</span></div>
        @endforeach
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Hospitalizaciones por hospital</h2></div>
      <div class="compact-list">
        @foreach($reports['hospitalizations_by_hospital'] as $row)
          <div><strong>{{ $row->label }}</strong><span>{{ $row->total }} eventos / {{ number_format((float) $row->average_stay, 1) }} dias promedio</span></div>
        @endforeach
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Medicamentos entregados</h2></div>
      <div class="compact-list">
        @forelse($reports['delivered_medications'] as $delivery)
          <div><strong>{{ $delivery->patient?->full_name }}</strong><span>{{ $delivery->treatment?->medication_name }} / {{ $delivery->actual_delivery_date?->format('d/m/Y') }}</span></div>
        @empty
          <p class="empty-state">Sin entregas en el periodo.</p>
        @endforelse
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Entregas pendientes</h2></div>
      <div class="compact-list">
        @forelse($reports['pending_medication_deliveries'] as $delivery)
          <div><strong>{{ $delivery->patient?->full_name }}</strong><span>{{ $delivery->treatment?->medication_name }} / {{ $delivery->scheduled_delivery_date?->format('d/m/Y') }}</span></div>
        @empty
          <p class="empty-state">Sin entregas pendientes.</p>
        @endforelse
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Facturacion por proveedor</h2></div>
      <div class="compact-list">
        @foreach($reports['billing_by_provider'] as $row)
          <div><strong>{{ $row->label }}</strong><span>{{ $row->invoices_count }} facturas / ${{ number_format((float) $row->total, 2) }}</span></div>
        @endforeach
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Facturas pendientes de pago</h2></div>
      <div class="compact-list">
        @forelse($reports['pending_invoices'] as $invoice)
          <div><strong>{{ $invoice->invoice_number }}</strong><span>{{ $invoice->status }} / ${{ number_format((float) $invoice->total, 2) }}</span></div>
        @empty
          <p class="empty-state">Sin facturas pendientes.</p>
        @endforelse
      </div>
    </article>
  </section>

  <section class="insurance-grid two-col">
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Autorizaciones pendientes</h2></div>
      <div class="compact-list">
        @forelse($reports['pending_authorizations'] as $authorization)
          <div><strong>{{ $authorization->patient?->full_name }}</strong><span>{{ $authorization->type }} / {{ $authorization->requested_at?->format('d/m/Y') }}</span></div>
        @empty
          <p class="empty-state">Sin autorizaciones pendientes.</p>
        @endforelse
      </div>
    </article>
    <article class="insurance-panel insurance-report-panel">
      <div class="section-title"><h2>Tratamientos por renovar</h2></div>
      <div class="compact-list">
        @forelse($reports['treatments_to_renew'] as $treatment)
          <div><strong>{{ $treatment->patient?->full_name }}</strong><span>{{ $treatment->medication_name }} / vence {{ $treatment->ends_at?->format('d/m/Y') }}</span></div>
        @empty
          <p class="empty-state">Sin tratamientos por renovar.</p>
        @endforelse
      </div>
    </article>
      </section>
    </main>
  </div>
@endsection
