@php
  $institutionType = ['publica' => 'Pública', 'privada' => 'Privada', 'institution' => 'Institución', 'institucion' => 'Institución'][$selectedInstitution->type] ?? ucfirst($selectedInstitution->type ?? 'Institución');
  $institutionMetrics = [
    ['label' => 'Unidades', 'count' => $selectedInstitution->medical_units_count ?? 0, 'description' => 'Unidades registradas', 'icon' => 'institution', 'section' => 'units', 'action' => 'Ver unidades'],
    ['label' => 'Servicios', 'count' => $selectedInstitution->services_count ?? 0, 'description' => 'Servicios habilitados', 'icon' => 'services', 'section' => 'services', 'action' => 'Ver servicios'],
    ['label' => 'Usuarios', 'count' => $selectedInstitution->owner ? 1 : 0, 'description' => 'Usuarios asignados', 'icon' => 'usuarios'],
    ['label' => 'Módulos', 'count' => $catalog['module_count'], 'description' => 'Módulos activos', 'icon' => 'modules'],
  ];
@endphp
<section id="institution-detail" class="superadmin-institution-detail" aria-labelledby="institution-detail-title">
  <header>
    <div>
      <p class="superadmin-institution-eyebrow">Institución seleccionada</p>
      <h3 id="institution-detail-title">{{ $selectedInstitution->name }}</h3>
      <div class="superadmin-institution-meta">
        <span>Tipo: {{ $institutionType }}</span>
        <span class="superadmin-native-status-pill {{ $selectedInstitution->status === 'active' ? '' : 'is-danger' }}">{{ $selectedInstitution->status === 'active' ? 'Activo' : 'Inactivo' }}</span>
      </div>
    </div>
    <a href="{{ route('institution.dashboard', ['institution' => $selectedInstitution->id]) }}">Abrir módulo <span aria-hidden="true">→</span></a>
  </header>
  <div class="superadmin-institution-stats">
    @foreach ($institutionMetrics as $metric)
      <article>
        <span class="superadmin-institution-stat-icon" aria-hidden="true">
          @if ($metric['icon'] === 'services')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 3-.6 2.2-1.6.9-2.2-.5-2 3.4 1.6 1.7v2.6L2.6 15l2 3.4 2.2-.5 1.6.9L9 21h4l.6-2.2 1.6-.9 2.2.5 2-3.4-1.6-1.7v-2.6L19.4 9l-2-3.4-2.2.5-1.6-.9L13 3Z"/><circle cx="11" cy="12" r="3"/></svg>
          @else
            <x-klini-icon :name="$metric['icon']" />
          @endif
        </span>
        <div class="superadmin-institution-stat-copy">
          <h4>{{ $metric['label'] }}</h4>
          <div><strong>{{ $metric['count'] }}</strong><span>{{ $metric['description'] }}</span></div>
        </div>
        @isset ($metric['action'])
          <a href="{{ route('institution.dashboard', ['institution' => $selectedInstitution->id, 'section' => $metric['section']]) }}">{{ $metric['action'] }}</a>
        @endisset
      </article>
    @endforeach
  </div>
</section>
