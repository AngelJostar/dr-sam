@php
  $baseUnits = $institution->medicalUnits->values();
  $subunitTypeLabels = [
    'consulting' => 'Consulta Externa',
    'infusion' => 'Sala de Infusion',
    'operating' => 'Quirofano',
    'recovery' => 'Sala de Recuperacion',
    'laboratory' => 'Laboratorio Clinico',
    'imaging' => 'Imagenologia',
    'therapy' => 'Terapia Fisica',
    'vaccination' => 'Unidad de Vacunacion',
  ];
  $fallbackSubunits = [
    ['name' => 'Consulta Externa', 'code' => 'SUB-EXTR-001', 'type' => 'Consulta Externa', 'services' => 8, 'status' => 'active'],
    ['name' => 'Laboratorio Clinico', 'code' => 'SUB-LAB-002', 'type' => 'Laboratorio', 'services' => 6, 'status' => 'active'],
    ['name' => 'Imagenologia', 'code' => 'SUB-IMG-003', 'type' => 'Imagenologia', 'services' => 5, 'status' => 'maintenance'],
    ['name' => 'Terapia Fisica', 'code' => 'SUB-TFIS-004', 'type' => 'Rehabilitacion', 'services' => 4, 'status' => 'active'],
    ['name' => 'Unidad de Vacunacion', 'code' => 'SUB-VAC-005', 'type' => 'Prevencion', 'services' => 3, 'status' => 'active'],
  ];
  $subunitStatusText = fn (?string $value) => [
    'active' => 'Activa',
    'inactive' => 'Inactiva',
    'maintenance' => 'Mantenimiento',
    'suspended' => 'Suspendida',
  ][$value ?? ''] ?? $statusText($value);
  $unitLocation = fn ($unit) => collect([
    $unit->city ?? $unit->municipality,
    $unit->state ?? $unit->entity,
  ])->filter()->implode(', ') ?: 'Sin ubicacion';
  $demoUnit = (object) [
    'id' => null,
    'name' => 'Hospital General Demo Dr. Sam',
    'clues' => 'DRSAM000001',
    'code' => null,
    'external_id' => null,
    'city' => 'Ciudad de Mexico',
    'municipality' => null,
    'state' => 'Ciudad de Mexico',
    'entity' => null,
    'status' => 'active',
  ];
  $subunitUnitKey = fn ($unit) => $unit?->id ? 'unit-'.$unit->id : 'demo-unit';
  $subunitRows = collect();

  foreach ($baseUnits as $unit) {
    $areas = collect(data_get($unit->metadata, 'procedure_areas', []))->values();

    foreach ($areas as $index => $area) {
      $type = (string) data_get($area, 'type', 'consulting');
      $typeLabel = $subunitTypeLabels[$type] ?? str($type)->replace(['_', '-'], ' ')->title()->toString();
      $subunitRows->push([
        'name' => data_get($area, 'name', $typeLabel),
        'code' => data_get($area, 'code') ?: data_get($area, 'clave') ?: data_get($area, 'unit_number', 'SUB-'.strtoupper(substr($type, 0, 4)).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT)),
        'parent_key' => $subunitUnitKey($unit),
        'parent' => $unit->name,
        'parent_code' => $unit->clues ?? $unit->code ?? $unit->external_id,
        'location' => $unitLocation($unit),
        'type' => $typeLabel,
        'services' => $institution->services->where('medical_unit_id', $unit->id)->where('status', 'active')->count(),
        'status' => data_get($area, 'status', $unit->status),
      ]);
    }
  }

  if ($subunitRows->count() < 5) {
    $unit = $baseUnits->first() ?? $demoUnit;
    $existingSubunits = $subunitRows
        ->pluck('name')
        ->map(fn ($name) => strtolower((string) $name))
        ->all();

    foreach ($fallbackSubunits as $fallback) {
      if ($subunitRows->count() >= 5) {
        break;
      }

      if (! in_array(strtolower($fallback['name']), $existingSubunits, true)) {
        $subunitRows->push([
          'name' => $fallback['name'],
          'code' => $fallback['code'],
          'parent_key' => $subunitUnitKey($unit),
          'parent' => $unit->name,
          'parent_code' => $unit->clues ?? $unit->code ?? $unit->external_id,
          'location' => $unitLocation($unit),
          'type' => $fallback['type'],
          'services' => $fallback['services'],
          'status' => $fallback['status'],
        ]);
        $existingSubunits[] = strtolower($fallback['name']);
      }
    }
  }

  $subunitCount = $subunitRows->count();
  $subunitUnitOptions = $baseUnits->isNotEmpty() ? $baseUnits : collect([$demoUnit]);
  $subunitShowingFrom = $subunitCount > 0 ? 1 : 0;
  $subunitShowingTo = min(10, $subunitCount);
  $subunitTotalPages = max(1, (int) ceil(max(1, $subunitCount) / 10));
  $subunitVisiblePages = range(1, min(5, $subunitTotalPages));
@endphp

<nav class="institution-native-tabs institution-subunit-tabs" aria-label="Acciones de subunidades">
  <button type="button" class="is-active">Ver subunidades</button>
  <button type="button">Alta de subunidad</button>
</nav>

<section class="institution-native-table-card institution-native-table-card-wide institution-subunit-catalog-card">
  <div class="institution-native-table-heading institution-subunit-catalog-heading">
    <div>
      <h2>Subunidades</h2>
      <p>{{ number_format($subunitCount) }} {{ $subunitCount === 1 ? 'subunidad' : 'subunidades' }}</p>
    </div>
  </div>
  <div class="institution-subunit-unit-filter">
    <label>Unidad
      <select data-subunit-unit-filter>
        <option value="all">Todas las unidades</option>
        @foreach ($subunitUnitOptions as $unit)
          <option value="{{ $subunitUnitKey($unit) }}">
            {{ $unit->name }}{{ $unit->clues ? ' - '.$unit->clues : '' }}
          </option>
        @endforeach
      </select>
    </label>
  </div>
  <div class="institution-native-table-scroll">
    <table class="institution-native-table institution-subunit-catalog-table">
      <thead>
        <tr>
          <th>Subunidad</th>
          <th>Clave</th>
          <th>Unidad padre</th>
          <th>Ubicacion</th>
          <th>Tipo</th>
          <th>Servicios activos</th>
          <th>Estatus</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($subunitRows as $row)
          <tr data-subunit-row data-subunit-unit="{{ $row['parent_key'] }}">
            <td><strong>{{ $row['name'] }}</strong></td>
            <td>{{ $row['code'] }}</td>
            <td>
              {{ $row['parent'] }}
              @if ($row['parent_code'])
                <span>{{ $row['parent_code'] }}</span>
              @endif
            </td>
            <td>{{ $row['location'] }}</td>
            <td>{{ $row['type'] }}</td>
            <td>{{ $row['services'] }}</td>
            <td>
              <span @class([
                'institution-native-status',
                'is-inactive' => $row['status'] !== 'active',
                'is-maintenance' => $row['status'] === 'maintenance',
              ])>{{ $subunitStatusText($row['status']) }}</span>
            </td>
            <td>
              <div class="institution-native-actions institution-subunit-actions">
                <button type="button">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/></svg>
                  Editar
                </button>
                <button type="button" class="danger" aria-label="Eliminar subunidad {{ $row['name'] }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                  Eliminar
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="8">No hay subunidades registradas.</td></tr>
        @endforelse
        <tr data-subunit-empty hidden><td colspan="8">No hay subunidades para la unidad seleccionada.</td></tr>
      </tbody>
    </table>
  </div>
  <footer class="institution-native-table-footer">
    <span data-subunit-footer-summary>Mostrando {{ $subunitShowingFrom }} a <b>{{ $subunitShowingTo }}</b> de <b>{{ number_format($subunitCount) }}</b> subunidades</span>
    <nav aria-label="Paginacion de subunidades">
      <button type="button" aria-label="Pagina anterior">&lsaquo;</button>
      @foreach ($subunitVisiblePages as $page)
        @if ($page === 1)
          <strong>{{ $page }}</strong>
        @else
          <button type="button" aria-label="Pagina {{ $page }}">{{ $page }}</button>
        @endif
      @endforeach
      @if ($subunitTotalPages > 5)
        <span aria-hidden="true">&hellip;</span>
        <button type="button" aria-label="Pagina {{ $subunitTotalPages }}">{{ $subunitTotalPages }}</button>
      @endif
      <button type="button" aria-label="Pagina siguiente">&rsaquo;</button>
    </nav>
    <label>
      <select aria-label="Subunidades por pagina">
        <option>10 por pagina</option>
      </select>
    </label>
  </footer>
</section>

<script>
  (() => {
    const filter = document.querySelector('[data-subunit-unit-filter]');
    const rows = [...document.querySelectorAll('[data-subunit-row]')];
    const emptyRow = document.querySelector('[data-subunit-empty]');
    const footerSummary = document.querySelector('[data-subunit-footer-summary]');
    if (! filter || rows.length === 0) return;

    const labelForCount = (count) => count === 1 ? 'subunidad' : 'subunidades';
    const applyUnitFilter = () => {
      const selectedUnit = filter.value;
      let visible = 0;

      rows.forEach((row) => {
        const matches = selectedUnit === 'all' || row.dataset.subunitUnit === selectedUnit;
        row.hidden = !matches;
        if (matches) visible += 1;
      });

      if (emptyRow) emptyRow.hidden = visible > 0;
      if (footerSummary) {
        footerSummary.textContent = `Mostrando ${visible} de ${rows.length} ${labelForCount(rows.length)}`;
      }
    };

    filter.addEventListener('change', applyUnitFilter);
  })();
</script>
