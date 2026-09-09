@extends('layouts.app', ['title' => 'Entregas medicamentos'])

@section('body_class', 'insurance-health-native-body')

@section('content')
  <div class="insurance-health-native-screen insurance-health-native-screen--sidebar">
    @include('insurance._topbar')
    @include('insurance._sidebar')

    <main class="insurance-health-native-workspace">
      @include('insurance._flash')

      <section class="page-heading insurance-section-hero insurance-health-native-page-head">
        <div>
          <p class="eyebrow">Entregas</p>
          <h1>Seguimiento de medicamentos</h1>
          <p>Entregas programadas, realizadas, atrasadas y evidencias operativas.</p>
        </div>
      </section>

      <form class="filter-bar insurance-filter-bar" method="get">
        <label>Paciente<input name="patient" value="{{ request('patient') }}" placeholder="Nombre"></label>
        <label>Estatus
          <select name="status">
            <option value="">Todos</option>
            @foreach(['pending' => 'Pendiente', 'in_route' => 'En ruta', 'delivered' => 'Entregado', 'not_delivered' => 'No entregado', 'rescheduled' => 'Reprogramado', 'cancelled' => 'Cancelado'] as $key => $label)
              <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
          </select>
        </label>
        <button type="submit">Filtrar</button>
      </form>

      <section class="table-card insurance-table-panel">
        <div class="insurance-health-native-table-title">
          <h2>Seguimiento de medicamentos</h2>
          <span>{{ $deliveries->total() }} registros</span>
        </div>
        <table class="data-table">
          <thead>
            <tr>
              <th>Paciente</th>
              <th>Medicamento</th>
              <th>Programada</th>
              <th>Real</th>
              <th>Estatus</th>
              <th>Accion</th>
            </tr>
          </thead>
          <tbody>
            @forelse($deliveries as $delivery)
              <tr>
                <td><a class="text-link" href="{{ route('insurance.patients.show', $delivery->patient) }}">{{ $delivery->patient?->full_name }}</a></td>
                <td>
                  <strong>{{ $delivery->treatment?->medication_name ?? $delivery->medication?->name ?? 'Medicamento' }}</strong>
                  <span>{{ $delivery->quantity_delivered }} piezas / {{ $delivery->covered_period }}</span>
                </td>
                <td>{{ $delivery->scheduled_delivery_date?->format('d/m/Y') }}</td>
                <td>{{ $delivery->actual_delivery_date?->format('d/m/Y') ?? 'Pendiente' }}</td>
                <td><span class="badge">{{ $delivery->status }}</span></td>
                <td>
                  @if($abilities['manage_deliveries'])
                    <form class="table-action" method="post" action="{{ route('insurance.deliveries.status', $delivery) }}">
                      @csrf
                      @method('PATCH')
                      <select name="status">
                        @foreach(['pending', 'in_route', 'delivered', 'not_delivered', 'rescheduled', 'cancelled'] as $status)
                          <option value="{{ $status }}" @selected($delivery->status === $status)>{{ $status }}</option>
                        @endforeach
                      </select>
                      <input type="date" name="actual_delivery_date" value="{{ $delivery->actual_delivery_date?->format('Y-m-d') }}">
                      <button type="submit">Actualizar</button>
                    </form>
                  @else
                    <span>Solo lectura</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6">Sin entregas registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </section>

      <div class="pagination-wrap">{{ $deliveries->links() }}</div>
    </main>
  </div>
@endsection
