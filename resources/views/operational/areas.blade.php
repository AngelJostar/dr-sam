@extends('layouts.app', ['title' => $area['label']])
@section('body_class', 'operational-native-body')
@push('styles')
  <link rel="stylesheet" href="{{ asset('css/operational-workspace.css') }}?v={{ filemtime(public_path('css/operational-workspace.css')) }}">
@endpush
@section('content')
<div class="operational-native-screen">
  <header class="operational-native-topbar" aria-label="Barra superior operativa">
    <x-klini-brand label="MODULO OPERATIVO" />
    <div class="operational-workspace-context"><strong>{{ $unit->institution?->name }}</strong><small>{{ $unit->name }}</small></div>
    <div class="operational-workspace-session"><x-klini-session-menu :show-profile="true" /></div>
  </header>
  <aside class="operational-native-sidebar" aria-label="Navegacion operativa">@include('operational._area-menu', ['menuUnit' => $unit, 'activeArea' => $type])</aside>
  <section class="operational-native-workspace">
    @include('operational._catalog-navigation', ['catalogLabel' => $area['label'], 'catalogUrl' => $sectionUrl('catalog'), 'catalogStatus' => $areaStatus, 'catalogStatusParameter' => 'area_status', 'catalogCarouselItems' => $carouselItems, 'catalogSectionTabs' => $areaSection === 'catalog' ? null : [['label' => 'Inicio', 'url' => $sectionUrl($areaSection), 'active' => true]]])
    @if ($areaSection !== 'catalog')
      @include('operational.sections.area-workspace')
    @else
    <section class="operational-native-table-card">
      <div class="operational-native-table-heading"><div><h2>{{ $area['label'] }}</h2><span>{{ $unit->name }} · {{ $areas->count() }} áreas registradas</span></div>
        @if (in_array(auth()->user()->role, ['superadmin', 'admin', 'unit', 'institution'], true))<a class="operational-secondary-button" target="_top" href="{{ route('unit.dashboard', ['unit' => $unit->id, 'section' => 'procedure-areas', 'catalog' => $type]) }}">Administrar áreas</a>@endif
      </div>
      <div class="operational-native-table-scroll"><table class="operational-native-table" data-drsam-table-filter-empty>
        <thead><tr><th>Ubicación</th><th>Piso</th><th>Número de unidad</th><th>Capacidad simultánea</th><th>Responsable de área</th><th>Horario de atención</th><th>Estatus</th></tr></thead>
        <tbody>@forelse ($areas as $record)<tr>
          <td>{{ $record['location'] }}</td><td>{{ $record['floor'] }}</td><td>{{ $record['unit_number'] }}</td><td>{{ $record['capacity'] }}</td><td>{{ $record['responsible'] }}</td>
          <td>{{ collect($record['schedule'] ?? [])->filter(fn ($day) => data_get($day, 'enabled'))->map(fn ($day, $key) => (['monday'=>'Lunes','tuesday'=>'Martes','wednesday'=>'Miércoles','thursday'=>'Jueves','friday'=>'Viernes','saturday'=>'Sábado','sunday'=>'Domingo'][$key] ?? $key).' '.data_get($day, 'start').' - '.data_get($day, 'end'))->implode(', ') ?: 'Sin horario' }}</td>
          <td>{{ ($record['status'] ?? 'active') === 'active' ? 'Activo' : 'Inactivo' }}</td>
        </tr>@empty<tr><td colspan="7">{{ $area['empty'] }}</td></tr>@endforelse</tbody>
      </table></div>
    </section>
    @endif
  </section>
</div>
@endsection
