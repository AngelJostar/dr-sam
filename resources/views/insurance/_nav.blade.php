<nav class="insurance-nav insurance-health-nav" aria-label="Navegacion aseguradora">
  <a @class(['is-active' => request()->routeIs('insurance.dashboard')]) href="{{ route('insurance.dashboard') }}">Dashboard</a>
  <a @class(['is-active' => request()->routeIs('insurance.patients.*')]) href="{{ route('insurance.patients.index') }}">Pacientes</a>
  <a @class(['is-active' => request()->routeIs('insurance.deliveries.*')]) href="{{ route('insurance.deliveries.index') }}">Entregas</a>
  <a @class(['is-active' => request()->routeIs('insurance.hospitalizations.*')]) href="{{ route('insurance.hospitalizations.index') }}">Hospitalizaciones</a>
  <a @class(['is-active' => request()->routeIs('insurance.invoices.*')]) href="{{ route('insurance.invoices.index') }}">Facturacion</a>
  <a @class(['is-active' => request()->routeIs('insurance.authorizations.*')]) href="{{ route('insurance.authorizations.index') }}">Autorizaciones</a>
  <a @class(['is-active' => request()->routeIs('insurance.documents.*')]) href="{{ route('insurance.documents.index') }}">Documentos</a>
  <a @class(['is-active' => request()->routeIs('insurance.reports.*')]) href="{{ route('insurance.reports.index') }}">Reportes</a>
  @if(($abilities['admin_users'] ?? false))
    <a @class(['is-active' => request()->routeIs('insurance.admin.*')]) href="{{ route('insurance.admin.users') }}">Usuarios y roles</a>
  @endif
</nav>
