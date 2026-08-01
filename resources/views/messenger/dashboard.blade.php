@extends('layouts.app', ['title' => 'Modulo Mensajero'])

@section('body_class', 'messenger-native-body')

@php
  $statusLabels = [
    'planned' => 'Planeada',
    'assigned' => 'En curso',
    'picked_up' => 'Recolectada',
    'in_route' => 'En ruta',
    'delivered' => 'Entregada',
    'failed' => 'Fallida',
    'cancelled' => 'Cancelada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $messengerName = $profile?->user?->name ?? auth()->user()?->name ?? 'Luis Hernandez';
  $messengerPhone = $profile?->phone ?? '55 2148 9300';
  $messengerShift = data_get($profile?->metadata, 'shift', 'Matutino');
  $routeCollection = $routes->getCollection();
  $hasRoutes = $routeCollection->isNotEmpty();
  $activeRoutes = $routeCollection->filter(fn ($route) => in_array($route->status, ['planned', 'assigned', 'picked_up', 'in_route'], true));
  $routeNames = $hasRoutes
    ? $routeCollection->pluck('route_code')->filter()->take(2)->implode(', ')
    : 'Ruta EdoMex Norte, Ruta CDMX Oncologica';
  $scannedToday = $recentReports->where('reported_at', '>=', now()->startOfDay())->count();

  $demoRoutes = collect([
    [
      'code' => 'Ruta EdoMex Norte',
      'qr' => 'QR-RUTA-EDOMEX-NORTE',
      'client' => 'IMSS Bienestar Estado de Mexico',
      'hospital' => 'Hospital Monica Pretelini / Hospital Nicolas San Juan',
      'schedule' => '08:00 - 15:00',
      'status' => 'Sin solicitudes enviadas para esta ruta.',
      'rows' => collect(),
    ],
    [
      'code' => 'Ruta CDMX Oncologica',
      'qr' => 'QR-RUTA-CDMX-ONCOLOGICA',
      'client' => 'IMSS Bienestar Estado de Mexico',
      'hospital' => 'Hospital General Norte',
      'schedule' => '09:00 - 14:00',
      'status' => null,
      'rows' => collect([
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Garcia Moreno, Valeria', 'folio' => 'QT-23'],
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Rodriguez Luna, Camila', 'folio' => 'QT-22'],
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Sanchez Ruiz, Diego', 'folio' => 'QT-21'],
        ['client' => 'IMSS Bienestar Estado de Mexico', 'hospital' => 'Hospital General Norte', 'address' => 'Entrada de proveedores, Hospital General Norte, Ciudad de Mexico', 'patient' => 'Flores Mendoza, Renata', 'folio' => 'QT-20'],
      ]),
    ],
  ]);
@endphp

@section('content')
  <div class="messenger-native-screen">
    <aside class="messenger-native-sidebar" aria-label="Navegacion mensajero">
      <div class="messenger-native-brand">
        <span>M</span>
        <div>
          <strong>Modulo Mensajero</strong>
          <small>Entregas proveedor NPT</small>
        </div>
      </div>

      <nav class="messenger-native-menu">
        <a class="is-active" href="{{ route('messenger.dashboard') }}"><span>D</span>Dashboard / Nueva ruta</a>
        <a href="{{ route('messenger.dashboard') }}"><span>E</span>Entregas en Curso</a>
      </nav>
    </aside>

    <main class="messenger-native-workspace">
      @if (session('status'))
        <div class="notice success">{{ session('status') }}</div>
      @endif

      @if ($errors->any())
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <header class="messenger-native-topbar">
        <div>
          <p class="eyebrow">Usuario mensajero</p>
          <h1>Dashboard / Nueva ruta</h1>
          <span>{{ $messengerName }} - {{ $messengerPhone }} - {{ $messengerShift }}</span>
        </div>
        <a href="{{ route('dashboard') }}">&larr; Salir</a>
      </header>

      <section class="messenger-native-summary" aria-label="Resumen de mensajeria">
        <article class="messenger-native-wide-stat">
          <span>Ruta asignada</span>
          <strong>{{ $routeNames ?: 'Sin ruta asignada' }}</strong>
        </article>
        <article>
          <span>Pedidos en curso</span>
          <strong>{{ number_format($activeRoutes->count()) }}</strong>
        </article>
        <article>
          <span>Historial</span>
          <strong>{{ number_format($recentReports->count()) }}</strong>
        </article>
        <article>
          <span>Escaneados hoy</span>
          <strong>{{ number_format($scannedToday) }}</strong>
        </article>
      </section>

      <section class="messenger-native-card messenger-native-scan">
        <div class="messenger-native-card-head">
          <div>
            <h2>Dashboard / Nueva ruta</h2>
            <p>Escanea el codigo QR de la ruta que vas a entregar.</p>
          </div>
        </div>
        <form>
          <label>Codigo QR de la Ruta</label>
          <div>
            <input value="{{ $hasRoutes ? ($routeCollection->first()->route_code ?? 'QR-RUTA-ASIGNADA') : 'QR-RUTA-EDOMEX-NORTE' }}" aria-label="Codigo QR de la ruta">
            <button type="button">Escanear ruta</button>
          </div>
        </form>
      </section>

      <section class="messenger-native-route-grid">
        @if ($hasRoutes)
          @foreach ($routeCollection as $route)
            <article class="messenger-native-route-card">
              <div class="messenger-native-route-head">
                <div>
                  <span>Ruta asignada</span>
                  <h3>{{ $route->route_code ?? 'Ruta asignada' }}</h3>
                  <p>{{ $route->origin ?? 'Origen pendiente' }}</p>
                  <p>{{ $route->scheduled_at?->format('H:i') ?? 'Sin horario' }} - {{ $route->destination ?? 'Destino pendiente' }}</p>
                </div>
                <form method="post" action="{{ route('messenger.routes.status', $route) }}">
                  @csrf
                  @method('patch')
                  <input type="hidden" name="status" value="in_route">
                  <input type="hidden" name="notes" value="Ruta iniciada desde tablero">
                  <button type="submit">Comenzar ruta</button>
                </form>
              </div>

              <div class="messenger-native-tags">
                <span>QR: {{ $route->route_code ?? 'QR-RUTA' }}</span>
                <span>{{ $route->destination ?? $route->patientOrder?->patient?->full_name ?? $route->providerRequest?->medicalUnit?->name ?? 'Destino pendiente' }}</span>
              </div>

              <div class="messenger-native-table-wrap">
                <table class="messenger-native-table">
                  <thead>
                    <tr>
                      <th>Cliente</th>
                      <th>Hospital</th>
                      <th>Direccion</th>
                      <th>Paciente</th>
                      <th>Estatus</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>
                        <strong>{{ $route->providerRequest?->medicalUnit?->institution?->name ?? 'Farmacia Digital' }}</strong>
                        <span>{{ $route->patientOrder?->order_number ?? $route->providerRequest?->external_id ?? 'Pedido asignado' }}</span>
                      </td>
                      <td>{{ $route->providerRequest?->medicalUnit?->name ?? $route->origin ?? 'Farmacia Digital' }}</td>
                      <td>{{ $route->destination ?? 'Destino pendiente' }}</td>
                      <td>{{ $route->patientOrder?->patient?->full_name ?? $route->providerRequest?->patient?->full_name ?? 'Sin paciente asociado' }}</td>
                      <td>
                        <form method="post" action="{{ route('messenger.routes.status', $route) }}">
                          @csrf
                          @method('patch')
                          <select name="status">
                            @foreach (['planned', 'assigned', 'picked_up', 'in_route', 'delivered', 'failed', 'cancelled'] as $status)
                              <option value="{{ $status }}" @selected($route->status === $status)>{{ $statusText($status) }}</option>
                            @endforeach
                          </select>
                          <input name="notes" placeholder="Nota">
                          <button type="submit">Reportar</button>
                        </form>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </article>
          @endforeach
        @else
          @foreach ($demoRoutes as $demoRoute)
            <article class="messenger-native-route-card">
              <div class="messenger-native-route-head">
                <div>
                  <span>Ruta asignada</span>
                  <h3>{{ $demoRoute['code'] }}</h3>
                  <p>{{ $demoRoute['client'] }}</p>
                  <p>{{ $demoRoute['schedule'] }} - {{ $demoRoute['rows']->count() }} solicitudes enviadas</p>
                </div>
                <button type="button">Comenzar ruta</button>
              </div>

              <div class="messenger-native-tags">
                <span>QR: {{ $demoRoute['qr'] }}</span>
                <span>{{ $demoRoute['hospital'] }}</span>
              </div>

              <div class="messenger-native-table-wrap">
                <table class="messenger-native-table">
                  <thead>
                    <tr>
                      <th>Cliente</th>
                      <th>Hospital</th>
                      <th>Direccion</th>
                      <th>Paciente</th>
                      <th>Estatus</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse ($demoRoute['rows'] as $row)
                      <tr>
                        <td><strong>{{ $row['client'] }}</strong><span>{{ $row['folio'] }}</span></td>
                        <td>{{ $row['hospital'] }}</td>
                        <td>{{ $row['address'] }}</td>
                        <td>{{ $row['patient'] }}</td>
                        <td><span class="messenger-native-status">En curso</span></td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="messenger-native-empty">{{ $demoRoute['status'] }}</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </article>
          @endforeach
        @endif
      </section>

      <section class="messenger-native-card messenger-native-history">
        <div class="messenger-native-card-head">
          <div>
            <h2>Historial reciente</h2>
            <p>Ultimos reportes y entregas registradas por el mensajero.</p>
          </div>
          <span>{{ $recentReports->count() }} registros</span>
        </div>
        <div class="messenger-native-history-list">
          @forelse ($recentReports as $report)
            <div>
              <strong>{{ $report->route?->route_code ?? 'Ruta sin folio' }} / {{ $statusText($report->status) }}</strong>
              <span>{{ $report->route?->patientOrder?->patient?->full_name ?? 'Sin paciente asociado' }}</span>
              <span>{{ $report->notes ?? 'Sin notas' }} / {{ $report->reported_at?->format('d/m/Y H:i') ?? 'Sin fecha' }}</span>
            </div>
          @empty
            <p>Todavia no hay reportes de ruta.</p>
          @endforelse
        </div>
      </section>
    </main>
  </div>
@endsection
