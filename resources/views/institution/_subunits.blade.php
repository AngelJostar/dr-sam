@php
  $pendingValue = '—';
  $baseUnits = $institution->medicalUnits->values();
  $catalogDefinitions = collect(config('drsam_subunits', []))->values();
  $subunitCatalogNames = [
    'consulting' => 'Consulta externa',
    'infusion' => 'Unidad de infusión y quimioterapia',
    'operating' => 'Quirófanos',
    'uci' => 'Cuidados intensivos de adultos',
    'uti' => 'Cuidados intermedios',
    'recovery' => 'Recuperación posanestésica',
    'laboratory' => 'Laboratorio clínico',
    'imaging' => 'Imagenología',
    'therapy' => 'Rehabilitación y terapia física',
    'vaccination' => 'Unidad de vacunación',
  ];
  $fallbackRegistrations = [
    ['catalog_name' => 'Consulta externa', 'code' => 'C-01', 'services' => 6, 'status' => 'maintenance'],
    ['catalog_name' => 'Laboratorio clínico', 'code' => 'SUB-LAB-002', 'services' => 6, 'status' => 'active'],
    ['catalog_name' => 'Imagenología', 'code' => 'SUB-IMG-003', 'services' => 5, 'status' => 'maintenance'],
    ['catalog_name' => 'Rehabilitación y terapia física', 'code' => 'SUB-TFIS-004', 'services' => 4, 'status' => 'active'],
    ['catalog_name' => 'Unidad de vacunación', 'code' => 'SUB-VAC-005', 'services' => 3, 'status' => 'active'],
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
  ])->filter()->implode(', ') ?: null;
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
  $normalizeSubunitName = fn ($value) => str((string) $value)
      ->ascii()
      ->lower()
      ->replaceMatches('/[^a-z0-9]+/', ' ')
      ->trim()
      ->toString();
  $registeredSubunits = collect();

  foreach ($baseUnits as $unit) {
    $persistedSignatures = collect();

    foreach ($unit->procedureAreas as $area) {
      $type = (string) $area->type;
      $catalogName = data_get($area->metadata, 'catalog_name')
          ?: data_get($area->metadata, 'source_payload.name')
          ?: ($subunitCatalogNames[$type] ?? str($type)->replace(['_', '-'], ' ')->title()->toString());
      $equipment = data_get($area->metadata, 'equipment', data_get($area->metadata, 'equipment_ids'));
      $equipmentCount = is_countable($equipment)
          ? count($equipment)
          : data_get($area->metadata, 'equipment_count');

      $registeredSubunits->push([
        'catalog_name' => $catalogName,
        'parent_key' => $subunitUnitKey($unit),
        'parent' => $unit->name,
        'parent_code' => $unit->clues ?? $unit->code ?? $unit->external_id,
        'code' => $area->unit_number,
        'location' => $area->location ?: $unitLocation($unit),
        'responsible' => $area->responsible_name,
        'equipment_count' => is_numeric($equipmentCount) ? (int) $equipmentCount : null,
        'services' => $institution->services->where('medical_unit_id', $unit->id)->where('status', 'active')->count(),
        'status' => $area->status ?: $unit->status,
      ]);

      $persistedSignatures->push(strtolower($type.'|'.($area->unit_number ?? '')));
    }

    foreach (collect(data_get($unit->metadata, 'procedure_areas', []))->values() as $index => $area) {
      $type = (string) data_get($area, 'type', 'consulting');
      $code = data_get($area, 'code')
          ?: data_get($area, 'clave')
          ?: data_get($area, 'unit_number', 'SUB-'.strtoupper(substr($type, 0, 4)).'-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT));
      $signature = strtolower($type.'|'.$code);
      if ($persistedSignatures->contains($signature)) {
        continue;
      }

      $equipment = data_get($area, 'equipment', data_get($area, 'equipment_ids'));
      $equipmentCount = is_countable($equipment) ? count($equipment) : data_get($area, 'equipment_count');
      $registeredSubunits->push([
        'catalog_name' => data_get($area, 'catalog_name')
            ?: data_get($area, 'name')
            ?: ($subunitCatalogNames[$type] ?? str($type)->replace(['_', '-'], ' ')->title()->toString()),
        'parent_key' => $subunitUnitKey($unit),
        'parent' => $unit->name,
        'parent_code' => $unit->clues ?? $unit->code ?? $unit->external_id,
        'code' => $code,
        'location' => data_get($area, 'location') ?: $unitLocation($unit),
        'responsible' => data_get($area, 'responsible'),
        'equipment_count' => is_numeric($equipmentCount) ? (int) $equipmentCount : null,
        'services' => $institution->services->where('medical_unit_id', $unit->id)->where('status', 'active')->count(),
        'status' => data_get($area, 'status', $unit->status),
      ]);
    }
  }

  if ($registeredSubunits->count() < count($fallbackRegistrations)) {
    $unit = $baseUnits->first() ?? $demoUnit;
    $existingCatalogNames = $registeredSubunits
        ->pluck('catalog_name')
        ->map($normalizeSubunitName)
        ->all();

    foreach ($fallbackRegistrations as $fallback) {
      if ($registeredSubunits->count() >= count($fallbackRegistrations)) {
        break;
      }

      if (! in_array($normalizeSubunitName($fallback['catalog_name']), $existingCatalogNames, true)) {
        $registeredSubunits->push([
          ...$fallback,
          'parent_key' => $subunitUnitKey($unit),
          'parent' => $unit->name,
          'parent_code' => $unit->clues ?? $unit->code ?? $unit->external_id,
          'location' => $unitLocation($unit),
          'responsible' => null,
          'equipment_count' => null,
        ]);
        $existingCatalogNames[] = $normalizeSubunitName($fallback['catalog_name']);
      }
    }
  }

  $registeredByCatalog = $registeredSubunits->groupBy(
      fn ($row) => $normalizeSubunitName($row['catalog_name'])
  );
  $subunitRows = $catalogDefinitions->map(function ($definition, $index) use ($registeredByCatalog, $normalizeSubunitName) {
    $registrations = $registeredByCatalog->get($normalizeSubunitName($definition['name']), collect());
    $firstRegistration = $registrations->first();
    $collectValues = fn (string $key) => $registrations
        ->pluck($key)
        ->filter(fn ($value) => $value !== null && $value !== '')
        ->unique()
        ->values();
    $statuses = $collectValues('status');
    $status = match (true) {
      $statuses->contains('active') => 'active',
      $statuses->contains('maintenance') => 'maintenance',
      $statuses->contains('inactive') => 'inactive',
      $statuses->contains('suspended') => 'suspended',
      default => 'pending',
    };
    $equipmentCounts = $collectValues('equipment_count');
    $serviceCounts = $collectValues('services');

    return [
      ...$definition,
      'number' => $index + 1,
      'parent_key' => $firstRegistration['parent_key'] ?? 'unassigned',
      'parent' => $collectValues('parent')->implode(' / ') ?: null,
      'parent_code' => $collectValues('parent_code')->implode(', ') ?: null,
      'code' => $collectValues('code')->implode(', ') ?: null,
      'location' => $collectValues('location')->implode(' / ') ?: null,
      'responsible' => $collectValues('responsible')->implode(', ') ?: null,
      'spaces_registered' => $registrations->isNotEmpty() ? $registrations->count() : null,
      'equipment_assigned' => $equipmentCounts->isNotEmpty() ? $equipmentCounts->sum() : null,
      'services' => $serviceCounts->isNotEmpty() ? $serviceCounts->max() : null,
      'status' => $status,
    ];
  });

  $subunitCount = $subunitRows->count();
  $subunitUnitOptions = $baseUnits->isNotEmpty() ? $baseUnits : collect([$demoUnit]);
@endphp

<section class="institution-native-table-card institution-native-table-card-wide institution-subunit-catalog-card">
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

  <div class="institution-native-table-scroll institution-subunit-catalog-scroll">
    <table class="institution-native-table institution-subunit-catalog-table">
      <thead>
        <tr>
          <th>N.º</th>
          <th>Categoría</th>
          <th>Subunidad</th>
          <th>Espacios que puede incluir</th>
          <th>Equipamiento asociado</th>
          <th>Hospital / Unidad padre</th>
          <th>Clave</th>
          <th>Ubicación</th>
          <th>Responsable</th>
          <th>Espacios registrados</th>
          <th>Equipos asignados</th>
          <th>Servicios activos</th>
          <th>Estado</th>
          <th>Ver</th>
          <th data-drsam-table-filter-skip-column>Editar</th>
          <th data-drsam-table-filter-skip-column>Espacios</th>
          <th data-drsam-table-filter-skip-column>Equipos</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($subunitRows as $row)
          @php
            $searchText = collect([
              $row['category'], $row['name'], $row['spaces'], $row['equipment'], $row['parent'],
              $row['parent_code'], $row['code'], $row['location'], $row['responsible'], $row['status'],
            ])->filter()->implode(' ');
          @endphp
          <tr data-subunit-row
              data-subunit-catalog-index="{{ $row['number'] }}"
              data-institution-catalog-row="subunits"
              data-institution-catalog-status="{{ $row['status'] }}"
              data-institution-catalog-search="{{ str($searchText)->lower() }}"
              data-subunit-unit="{{ $row['parent_key'] }}">
            <td class="institution-subunit-number">{{ $row['number'] }}</td>
            <td>{{ $row['category'] }}</td>
            <td><strong>{{ $row['name'] }}</strong></td>
            <td>{{ $row['spaces'] }}</td>
            <td>{{ $row['equipment'] }}</td>
            <td>
              {{ $row['parent'] ?? $pendingValue }}
              @if ($row['parent_code'])
                <span>{{ $row['parent_code'] }}</span>
              @endif
            </td>
            <td>{{ $row['code'] ?? $pendingValue }}</td>
            <td>{{ $row['location'] ?? $pendingValue }}</td>
            <td>{{ $row['responsible'] ?? $pendingValue }}</td>
            <td>{{ $row['spaces_registered'] ?? $pendingValue }}</td>
            <td>{{ $row['equipment_assigned'] ?? $pendingValue }}</td>
            <td>{{ $row['services'] ?? $pendingValue }}</td>
            <td>
              @if ($row['status'] === 'pending')
                <span class="institution-subunit-pending" aria-label="Pendiente de captura">{{ $pendingValue }}</span>
              @else
                <span @class([
                  'institution-native-status',
                  'is-inactive' => in_array($row['status'], ['inactive', 'suspended'], true),
                  'is-maintenance' => $row['status'] === 'maintenance',
                ])>{{ $subunitStatusText($row['status']) }}</span>
              @endif
            </td>
            <td class="institution-subunit-action-cell">
              <div class="institution-native-actions institution-subunit-control-actions">
                <button type="button" data-subunit-action="view" title="Ver detalle de {{ $row['name'] }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                  Ver
                </button>
              </div>
            </td>
            <td class="institution-subunit-action-cell">
              <div class="institution-native-actions institution-subunit-control-actions">
                <button type="button" data-subunit-action="edit" title="Editar {{ $row['name'] }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/></svg>
                  Editar
                </button>
              </div>
            </td>
            <td class="institution-subunit-action-cell">
              <div class="institution-native-actions institution-subunit-control-actions">
                <button type="button" data-subunit-action="spaces" title="Administrar espacios de {{ $row['name'] }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21V5h16v16"/><path d="M8 9h3v4H8zM15 9h2v4h-2zM8 17h9"/></svg>
                  Espacios
                </button>
              </div>
            </td>
            <td class="institution-subunit-action-cell">
              <div class="institution-native-actions institution-subunit-control-actions">
                <button type="button" data-subunit-action="equipment" title="Asignar equipamiento a {{ $row['name'] }}">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7h10v10H7z"/><path d="M9 3v4M15 3v4M9 17v4M15 17v4M3 9h4M17 9h4M3 15h4M17 15h4"/></svg>
                  Equipos
                </button>
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="17">No hay subunidades en el catálogo.</td></tr>
        @endforelse
        <tr data-subunit-empty hidden><td colspan="17">No hay subunidades para los filtros seleccionados.</td></tr>
      </tbody>
    </table>
  </div>

  <footer class="institution-native-table-footer institution-native-table-footer-all-results">
    <span data-subunit-footer-summary>Mostrando {{ number_format($subunitCount) }} de {{ number_format($subunitCount) }} subunidades</span>
  </footer>
</section>

<script>
  (() => {
    const filter = document.querySelector('[data-subunit-unit-filter]');
    const rows = [...document.querySelectorAll('[data-subunit-row]')];
    const emptyRow = document.querySelector('[data-subunit-empty]');
    const footerSummary = document.querySelector('[data-subunit-footer-summary]');
    if (! filter || rows.length === 0) return;

    const updateResultSummary = (visible) => {
      if (emptyRow) emptyRow.hidden = visible > 0;
      if (footerSummary) footerSummary.textContent = `Mostrando ${visible} de ${rows.length} subunidades`;
    };
    const applyUnitFilter = () => {
      const selectedUnit = filter.value;

      rows.forEach((row) => {
        const matches = selectedUnit === 'all' || row.dataset.subunitUnit === selectedUnit;
        row.dataset.institutionLocalMatch = matches ? 'true' : 'false';
      });

      document.dispatchEvent(new CustomEvent('institution:catalog-local-filter'));
    };

    filter.addEventListener('change', applyUnitFilter);
    document.addEventListener('institution:catalog-applied', (event) => {
      if (event.detail?.section === 'subunits') updateResultSummary(event.detail.visible);
    });
    updateResultSummary(rows.filter((row) => ! row.hidden).length);
  })();
</script>
