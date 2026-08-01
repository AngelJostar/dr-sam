@php
  $insuranceMenu = [
    ['label' => 'Dashboard', 'route' => 'insurance.dashboard', 'match' => 'insurance.dashboard', 'icon' => 'D'],
    ['label' => 'Pacientes', 'route' => 'insurance.patients.index', 'match' => 'insurance.patients.*', 'icon' => 'P'],
    ['label' => 'Entregas', 'route' => 'insurance.deliveries.index', 'match' => 'insurance.deliveries.*', 'icon' => 'E'],
    ['label' => 'Hospitalizaciones', 'route' => 'insurance.hospitalizations.index', 'match' => 'insurance.hospitalizations.*', 'icon' => 'H'],
    ['label' => 'Facturacion', 'route' => 'insurance.invoices.index', 'match' => 'insurance.invoices.*', 'icon' => 'F'],
    ['label' => 'Autorizaciones', 'route' => 'insurance.authorizations.index', 'match' => 'insurance.authorizations.*', 'icon' => 'A'],
    ['label' => 'Documentos', 'route' => 'insurance.documents.index', 'match' => 'insurance.documents.*', 'icon' => 'Doc'],
    ['label' => 'Reportes', 'route' => 'insurance.reports.index', 'match' => 'insurance.reports.*', 'icon' => 'R'],
  ];

  if (($abilities['admin_users'] ?? false)) {
    $insuranceMenu[] = ['label' => 'Usuarios y roles', 'route' => 'insurance.admin.users', 'match' => 'insurance.admin.*', 'icon' => 'U'];
  }
@endphp

<aside class="insurance-health-native-sidebar">
  <div class="insurance-health-native-sidebar-brand">
    <span>DS</span>
    <div>
      <strong>Aseguradora salud</strong>
      <small>Control clinico</small>
    </div>
  </div>

  <nav class="insurance-health-native-sidebar-menu" aria-label="Menu aseguradora salud">
    @foreach($insuranceMenu as $item)
      <a @class(['is-active' => request()->routeIs($item['match'])]) href="{{ route($item['route']) }}">
        <span>{{ $item['icon'] }}</span>
        <strong>{{ $item['label'] }}</strong>
      </a>
    @endforeach
  </nav>

  <a class="insurance-health-native-access" href="{{ route('dashboard') }}">
    <span>&lt;</span>
    Modulo de acceso
  </a>
</aside>
