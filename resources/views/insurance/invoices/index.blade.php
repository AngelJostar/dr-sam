@extends('layouts.app', ['title' => 'Facturacion hospitalaria'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._topbar')
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      @include('insurance._flash')

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Facturacion</p>
          <h1>Facturacion hospitalaria</h1>
          <p>Control de CFDI, conceptos, pagos, rechazos y comparativo contra monto autorizado.</p>
        </div>
      </section>

  @if($abilities['manage_billing'])
    <form class="insurance-form insurance-entry-form compact" method="post" action="{{ route('insurance.invoices.store') }}">
      @csrf
      <section class="form-section insurance-form-panel">
        <h2>Registrar factura</h2>
        <div class="form-grid">
          <label>Hospitalizacion
            <select name="hospitalization_id">
              <option value="">Sin ligar</option>
              @foreach($hospitalizations as $hospitalization)
                <option value="{{ $hospitalization->id }}">{{ $hospitalization->patient?->full_name }} / {{ $hospitalization->hospital_name }}</option>
              @endforeach
            </select>
          </label>
          <label>Hospital
            <select name="hospital_id">
              <option value="">Sin ligar</option>
              @foreach($hospitals as $hospital)
                <option value="{{ $hospital->id }}">{{ $hospital->name }}</option>
              @endforeach
            </select>
          </label>
          <label>Proveedor
            <select name="provider_id">
              <option value="">Sin ligar</option>
              @foreach($providers as $provider)
                <option value="{{ $provider->id }}">{{ $provider->name }}</option>
              @endforeach
            </select>
          </label>
          <label>Proveedor manual<input name="provider_name"></label>
          <label>RFC proveedor<input name="provider_rfc" maxlength="13"></label>
          <label>Factura<input name="invoice_number" required></label>
          <label>UUID fiscal<input name="fiscal_uuid"></label>
          <label>Fecha factura<input type="date" name="invoice_date"></label>
          <label>Concepto
            <select name="concept_type">
              <option value="room">Habitacion</option>
              <option value="medical_fees">Honorarios</option>
              <option value="medications">Medicamentos</option>
              <option value="supplies">Material curacion</option>
              <option value="laboratory">Laboratorio</option>
              <option value="imaging">Imagenologia</option>
              <option value="operating_room">Quirofano</option>
              <option value="intensive_care">Terapia intensiva</option>
              <option value="other">Otros</option>
            </select>
          </label>
          <label>Subtotal<input type="number" step="0.01" min="0" name="subtotal" required></label>
          <label>IVA<input type="number" step="0.01" min="0" name="vat" value="0"></label>
          <label>Retenciones<input type="number" step="0.01" min="0" name="withholdings" value="0"></label>
          <label>Total<input type="number" step="0.01" min="0" name="total" required></label>
          <label>Moneda<input name="currency" value="MXN" maxlength="3" required></label>
          <label>Estatus
            <select name="status">
              <option value="received">Recibida</option>
              <option value="in_review">En revision</option>
              <option value="approved">Aprobada</option>
              <option value="rejected">Rechazada</option>
              <option value="paid">Pagada</option>
              <option value="partially_paid">Parcialmente pagada</option>
            </select>
          </label>
          <label class="span-2">Descripcion concepto<textarea name="concept"></textarea></label>
        </div>
        <button type="submit">Registrar factura</button>
      </section>
    </form>
  @endif

  <form class="filter-bar insurance-filter-bar" method="get">
    <label>Estatus
      <select name="status">
        <option value="">Todos</option>
        @foreach(['received', 'in_review', 'approved', 'rejected', 'paid', 'partially_paid'] as $status)
          <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
        @endforeach
      </select>
    </label>
    <label>Proveedor<input name="provider" value="{{ request('provider') }}"></label>
    <button type="submit">Filtrar</button>
  </form>

  <section class="table-card insurance-table-panel">
    <table class="data-table">
      <thead>
        <tr>
          <th>Factura</th>
          <th>Hospitalizacion</th>
          <th>Proveedor</th>
          <th>Total</th>
          <th>Estatus</th>
          <th>Revision</th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $invoice)
          @php($authorized = (float) ($invoice->hospitalization?->authorized_amount ?? 0))
          <tr>
            <td>
              <strong>{{ $invoice->invoice_number }}</strong>
              <span>{{ $invoice->fiscal_uuid ?? 'Sin UUID' }}</span>
            </td>
            <td>{{ $invoice->hospitalization?->patient?->full_name ?? 'Sin ligar' }}</td>
            <td>{{ $invoice->provider_name ?? $invoice->provider?->name ?? 'Sin proveedor' }}</td>
            <td>${{ number_format((float) $invoice->total, 2) }}</td>
            <td><span class="badge">{{ $invoice->status }}</span></td>
            <td>
              @if(blank($invoice->xml_path))
                <span class="badge risk-high">Sin XML</span>
              @endif
              @if($authorized > 0 && (float) $invoice->total > $authorized)
                <span class="badge risk-critical">Excede autorizado</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="6">Sin facturas.</td></tr>
        @endforelse
      </tbody>
    </table>
  </section>

      <div class="pagination-wrap">{{ $invoices->links() }}</div>
    </main>
  </div>
@endsection
