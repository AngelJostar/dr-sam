@push('styles')
  <link rel="stylesheet" href="{{ asset('css/superadmin-workspace.css') }}?v={{ filemtime(public_path('css/superadmin-workspace.css')) }}">
  <link rel="stylesheet" href="{{ asset('css/superadmin-catalog.css') }}?v={{ filemtime(public_path('css/superadmin-catalog.css')) }}">
@endpush
@push('scripts')
  <script src="{{ asset('js/superadmin-workspace.js') }}?v={{ filemtime(public_path('js/superadmin-workspace.js')) }}" defer></script>
@endpush
@php
  $active = $active ?? 'dashboard';
  if (in_array($active, ['prescription-format', 'mix-request-format'], true)) $active = 'services';
  $sidebarItems = [
    ['key' => 'dashboard', 'icon' => '#', 'label' => 'Dashboard', 'href' => route('superadmin.dashboard')],
    ['key' => 'mobile-app', 'icon' => 'App', 'label' => 'Aplicacion Movil', 'href' => route('superadmin.mobile-app')],
    ['key' => 'digital-pharmacy', 'icon' => 'Rx', 'label' => 'Farmacia digital', 'href' => route('superadmin.digital-pharmacy')],
    ['key' => 'distributors', 'icon' => 'D', 'label' => 'Distribuidores', 'href' => route('superadmin.distributors')],
    ['key' => 'users', 'icon' => 'U', 'label' => 'Usuarios de la plataforma', 'href' => route('superadmin.catalog', 'users')],
    ['key' => 'institutions', 'icon' => 'I', 'label' => 'Instituciones', 'href' => route('superadmin.catalog', 'institutions')],
    ['key' => 'services', 'icon' => 'S', 'label' => 'Servicios integrales', 'href' => route('superadmin.catalog', 'services')],
    ['key' => 'doctors', 'icon' => 'M', 'label' => 'Médicos', 'href' => route('superadmin.catalog', 'doctors')],
    ['key' => 'providers', 'icon' => 'V', 'label' => 'Vendedores', 'href' => route('superadmin.catalog', 'providers')],
    ['key' => 'insurance-carriers', 'icon' => 'A', 'label' => 'Aseguradoras', 'href' => route('superadmin.catalog', 'insurance-carriers')],
    ['key' => 'insurance-advisors', 'icon' => 'As', 'label' => 'Asesores de seguros', 'href' => route('superadmin.catalog', 'insurance-advisors')],
    ['key' => 'specialties', 'icon' => 'E', 'label' => 'Especialidades', 'href' => route('superadmin.catalog', 'specialties')],
    ['key' => 'medications', 'icon' => 'Rx', 'label' => 'Medicamentos e insumos', 'href' => route('superadmin.catalog', 'medications')],
    ['key' => 'medical-devices', 'icon' => 'Dm', 'label' => 'Dispositivos médicos', 'href' => route('superadmin.catalog', 'medical-devices')],
    ['key' => 'patients', 'icon' => 'P', 'label' => 'Pacientes', 'href' => route('superadmin.catalog', 'patients')],
    ['key' => 'hospitals', 'icon' => 'H', 'label' => 'Hospitales', 'href' => route('superadmin.catalog', 'hospitals')],
    ['key' => 'subscriptions', 'icon' => 'S', 'label' => 'Suscripciones', 'href' => route('superadmin.catalog', 'subscriptions')],
    ['key' => 'advertising', 'icon' => 'Ad', 'label' => 'Publicidad', 'href' => route('superadmin.catalog', 'advertising')],
    ['key' => 'modules', 'icon' => 'Mo', 'label' => 'Modulos', 'href' => route('superadmin.catalog', 'modules')],
    ['key' => 'access', 'icon' => 'K', 'label' => 'Acceso', 'href' => route('superadmin.catalog', 'access')],
  ];
@endphp

<aside class="superadmin-native-sidebar" aria-label="Navegacion superadministrador">
  <nav class="superadmin-native-menu" tabindex="0" aria-label="Menú del Superadministrador">
    @foreach ($sidebarItems as $item)
      <a @class(['is-active' => $active === $item['key']]) href="{{ $item['href'] }}">
        <span class="klini-nav-icon" aria-hidden="true"><x-klini-icon name="{{ ['doctors' => 'admin-doctor', 'medications' => 'admin-medication', 'medical-devices' => 'admin-device', 'subscriptions' => 'admin-subscription', 'prescription-format' => 'admin-document'][$item['key']] ?? \Illuminate\Support\Str::ascii($item['label']) }}" /></span>
        {{ $item['label'] }}
      </a>
    @endforeach
  </nav>
</aside>
