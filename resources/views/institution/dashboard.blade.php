@extends('layouts.app', ['title' => 'Institucion'])

@section('body_class', 'institution-native-body')

@php
  $statusLabels = [
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'maintenance' => 'Mantenimiento',
    'suspended' => 'Suspendido',
    'available' => 'Disponible',
    'requested' => 'Solicitada',
    'accepted' => 'Aceptada',
    'preparing' => 'Preparacion',
    'in_route' => 'En ruta',
    'delivered' => 'Entregada',
    'cancelled' => 'Cancelada',
    'rejected' => 'Rechazada',
    'scheduled' => 'Programada',
  ];

  $statusText = fn (?string $value) => $statusLabels[$value ?? ''] ?? ($value ? ucfirst(str_replace('_', ' ', $value)) : 'Sin estatus');
  $institutionLabel = $institution->name;
  $visibleUnits = $institution->medicalUnits->count();
  $activeSection = in_array(request('section'), ['subunits', 'pharmacies', 'services', 'medications', 'specialties', 'create-unit', 'create-service', 'edit-unit'], true) ? request('section') : 'units';
@endphp

@section('content')
  <div class="institution-native-screen">
    <aside class="institution-native-sidebar" aria-label="Navegacion institucional">
      <div class="institution-native-brand">
        <span aria-hidden="true">+</span>
        <div>
          <strong>Portal</strong>
          <small>Institucional</small>
        </div>
      </div>

      <nav class="institution-native-menu">
        <a @class(['is-active' => $activeSection === 'units']) href="{{ route('institution.dashboard', ['institution' => $institution->id]) }}"><span>U</span>Catalogo de Unidades</a>
        <a @class(['is-active' => $activeSection === 'subunits']) href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'subunits']) }}"><span>Su</span>Catalogo de Subunidades</a>
        <a @class(['is-active' => $activeSection === 'pharmacies']) href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'pharmacies']) }}"><span>F</span>Catalogo de Farmacias Institucionales</a>
        <a @class(['is-active' => $activeSection === 'services']) href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services']) }}"><span>S</span>Catalogo de Servicios</a>
        <a @class(['is-active' => $activeSection === 'medications']) href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications']) }}"><span>Rx</span>Catalogo de Medicamentos</a>
        <a @class(['is-active' => $activeSection === 'specialties']) href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties']) }}"><span>E</span>Catalogo de Especialidades</a>
        <a @class(['is-active' => $activeSection === 'create-unit']) href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'create-unit']) }}"><span>+</span>Alta de unidad</a>
        <a @class(['is-active' => $activeSection === 'create-service']) href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'create-service']) }}"><span>+</span>Alta de servicios</a>
      </nav>
    </aside>

    <section class="institution-native-workspace">
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

      <header class="institution-native-header">
        <div>
          <p class="eyebrow">Portal institucional - {{ strtoupper($institutionLabel) }}</p>
          <h1>{{ match ($activeSection) {
            'pharmacies' => 'Catalogo de farmacias institucionales',
            'subunits' => 'Catalogo de subunidades',
            'services' => 'Catalogo de servicios',
            'medications' => 'Catalogo institucional de medicamentos',
            'specialties' => 'Catalogo institucional de especialidades',
            'create-unit' => 'Alta de unidad',
            'create-service' => 'Alta de servicios',
            default => 'Catalogo de unidades',
          } }}</h1>
          <p>Usuario activo: {{ $institutionLabel }} ({{ $institution->owner?->username ?? $institution->external_id ?? 'institucion' }})</p>

          <form method="get" action="{{ route('institution.dashboard') }}">
            @if ($activeSection !== 'units')
              <input type="hidden" name="section" value="{{ $activeSection }}">
            @endif
            <label>
              Institucion
              <select name="institution" onchange="this.form.submit()">
                @foreach ($availableInstitutions as $institutionOption)
                  <option value="{{ $institutionOption->id }}" @selected($institutionOption->is($institution))>
                    {{ $institutionOption->name }}
                  </option>
                @endforeach
              </select>
            </label>
          </form>
        </div>
        <div class="institution-native-session">
          <strong>{{ $institutionLabel }}</strong>
          <a href="{{ route('dashboard') }}" aria-label="Salir al dashboard">-></a>
        </div>
      </header>

      @if ($activeSection === 'edit-unit' && ($editingUnit = $institution->medicalUnits->firstWhere('id', (int) request('unit'))))
        <section class="institution-edit-unit-page">
          <div class="institution-edit-unit-bar"><div><strong>{{ $editingUnit->name }}</strong><small>{{ $editingUnit->clues ?? $editingUnit->code }} · {{ $editingUnit->municipality ?? $editingUnit->city }}</small></div><a href="{{ route('institution.dashboard', ['institution' => $institution->id]) }}">← Volver a Unidades</a></div>
        <section class="institution-unit-create-card institution-unit-edit-card">
          <header><div><h2>Editar unidad</h2><p>{{ $editingUnit->name }} · {{ $editingUnit->clues ?? $editingUnit->code }}</p></div><a href="{{ route('institution.dashboard', ['institution' => $institution->id]) }}">Cancelar</a></header>
          <form method="post" action="{{ route('institution.units.update', $editingUnit) }}" class="institution-unit-create-form">
            @csrf @method('PATCH')
            <label class="is-half">Nombre de la unidad<input name="name" value="{{ $editingUnit->name }}" required></label><label class="is-half">CLUES<input name="clues" value="{{ $editingUnit->clues }}"></label>
            <label>Código interno<input name="code" value="{{ $editingUnit->code }}"></label><label>Estatus<select name="status">@foreach(['active'=>'Activo','inactive'=>'Inactivo','maintenance'=>'Mantenimiento','suspended'=>'Suspendido'] as $value=>$label)<option value="{{ $value }}" @selected($editingUnit->status === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Usuario de unidad<input name="unit_username" value="{{ $editingUnit->unit_username }}"></label><label>Nueva contraseña de unidad<input name="unit_password" type="password" value=""></label>
            <label>Entidad<input name="entity" value="{{ $editingUnit->entity ?? $editingUnit->state }}"></label><label>Municipio<input name="municipality" value="{{ $editingUnit->municipality ?? $editingUnit->city }}"></label>
            <label>Nivel de atención<input name="care_level" value="{{ $editingUnit->care_level }}"></label><label>Tipología<input name="typology" value="{{ $editingUnit->typology ?? $editingUnit->type }}"></label><label>Tipo de unidad<input name="type" value="{{ $editingUnit->type }}"></label><label>Camas<input type="number" min="0" name="beds" value="{{ $editingUnit->beds }}"></label>
            <label class="is-wide">Domicilio<textarea name="address" rows="4">{{ $editingUnit->address }}</textarea></label><label>Latitud<input type="number" step="any" name="latitude" value="{{ $editingUnit->latitude }}"></label><label>Longitud<input type="number" step="any" name="longitude" value="{{ $editingUnit->longitude }}"></label><div class="institution-edit-actions"><a href="{{ route('institution.dashboard', ['institution' => $institution->id]) }}">Cancelar</a><button type="submit">Guardar cambios</button></div>
          </form>
        </section>
        </section>
      @elseif ($activeSection === 'units')
      <section class="institution-native-filters" aria-label="Filtros de unidades">
        <label class="search">
          <span aria-hidden="true">O</span>
          <input type="search" data-unit-search placeholder="Buscar unidad, ciudad o servicio">
        </label>
        <label>
          Tipo
          <select data-unit-type-filter>
            <option>Todos</option>
            @foreach ($institution->medicalUnits->pluck('type')->filter()->unique()->sort() as $unitType)
              <option value="{{ $unitType }}">{{ $unitType }}</option>
            @endforeach
          </select>
        </label>
        <label>
          Nivel de atencion
          <select data-unit-level-filter>
            <option>Todos</option>
            @foreach ($institution->medicalUnits->pluck('care_level')->filter()->unique()->sort() as $careLevel)
              <option value="{{ $careLevel }}">{{ $careLevel }}</option>
            @endforeach
          </select>
        </label>
        <label>
          Estatus
          <select data-unit-status-filter>
            <option>Todos</option>
            @foreach ($institution->medicalUnits->pluck('status')->filter()->unique()->sort() as $unitStatus)
              <option value="{{ $unitStatus }}">{{ $statusText($unitStatus) }}</option>
            @endforeach
          </select>
        </label>
        <button type="button" data-unit-csv>&darr;&nbsp; CSV</button>
      </section>

      <section id="units" class="institution-native-table-card">
        <div class="institution-native-table-heading">
          <div>
            <h2>Unidades</h2>
            <p><span data-visible-unit-count>{{ $visibleUnits }}</span> unidades visibles</p>
          </div>
        </div>

        <div class="institution-native-table-scroll">
          <table class="institution-native-table">
            <thead>
              <tr>
                <th>Unidad</th>
                <th>CLUES</th>
                <th>Usuario / Contrasena</th>
                <th>Ubicacion</th>
                <th>Nivel / Tipo</th>
                <th>Servicios activos</th>
                <th>Estatus</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($institution->medicalUnits as $unit)
                <tr data-unit-row
                    data-unit-search-value="{{ str($unit->name.' '.$unit->city.' '.$unit->municipality.' '.$unit->state.' '.$unit->type.' '.$unit->care_level)->lower() }}"
                    data-unit-type="{{ $unit->type }}"
                    data-unit-level="{{ $unit->care_level }}"
                    data-unit-status="{{ $unit->status }}">
                  <td>
                    <strong>{{ $unit->name }}</strong>
                    <span>{{ $unit->code ?? $unit->external_id ?? 'Sin codigo' }}</span>
                  </td>
                  <td>{{ $unit->clues ?? $unit->code ?? 'Sin CLUES' }}</td>
                  <td>
                    <strong>{{ $unit->unit_username ?? 'unidad-'.$unit->id }}</strong>
                    <span>Contraseña protegida</span>
                  </td>
                  <td>
                    {{ $unit->city ?? $unit->municipality ?? 'Sin ciudad' }}
                    <span>{{ $unit->state ?? $unit->entity ?? 'Sin estado' }}</span>
                  </td>
                  <td>
                    <strong>{{ $unit->care_level ?? 'Tercer Nivel' }}</strong>
                    <span>{{ $unit->type ?? $unit->typology ?? 'Hospital general' }}</span>
                  </td>
                  <td>{{ $institution->services->where('medical_unit_id', $unit->id)->where('status', 'active')->count() }}</td>
                  <td><span class="institution-native-status">{{ $statusText($unit->status) }}</span></td>
                  <td>
                    <div class="institution-native-actions">
                      <a href="{{ route('institution.dashboard', ['institution' => $institution->id, 'section' => 'edit-unit', 'unit' => $unit->id]) }}">Editar</a>
                      <form method="post" action="{{ route('institution.units.status', $unit) }}" onsubmit="return confirm('&iquest;{{ $unit->status === 'inactive' ? 'Reactivar' : 'Desactivar' }} esta unidad?')">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $unit->status === 'inactive' ? 'active' : 'inactive' }}">
                        <button type="submit" @class(['danger' => $unit->status !== 'inactive'])>{{ $unit->status === 'inactive' ? 'Reactivar' : 'Eliminar' }}</button>
                      </form>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8">No hay unidades registradas.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>

      <script>
        (() => {
          const search = document.querySelector('[data-unit-search]');
          const typeFilter = document.querySelector('[data-unit-type-filter]');
          const levelFilter = document.querySelector('[data-unit-level-filter]');
          const statusFilter = document.querySelector('[data-unit-status-filter]');
          const rows = [...document.querySelectorAll('[data-unit-row]')];
          const visibleCount = document.querySelector('[data-visible-unit-count]');

          const normalize = (value) => value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
          const matchesFilter = (row, filter, key) => filter.value === 'Todos' || row.dataset[key] === filter.value;
          const applyFilters = () => {
            const term = normalize(search.value.trim());
            let count = 0;

            rows.forEach((row) => {
              const matches = normalize(row.dataset.unitSearchValue).includes(term)
                && matchesFilter(row, typeFilter, 'unitType')
                && matchesFilter(row, levelFilter, 'unitLevel')
                && matchesFilter(row, statusFilter, 'unitStatus');
              row.hidden = !matches;
              if (matches) count++;
            });

            visibleCount.textContent = count;
          };

          [search, typeFilter, levelFilter, statusFilter].forEach((control) => {
            control.addEventListener(control === search ? 'input' : 'change', applyFilters);
          });

          document.querySelector('[data-unit-csv]').addEventListener('click', () => {
            const tableRows = [...document.querySelectorAll('.institution-native-table tr')];
            const csv = tableRows
              .filter((row) => !row.hidden)
              .map((row) => [...row.querySelectorAll('th, td')]
                .slice(0, 7)
                .map((cell) => `"${cell.innerText.trim().replaceAll('"', '""')}"`)
                .join(','))
              .join('\r\n');
            const link = document.createElement('a');
            link.href = URL.createObjectURL(new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' }));
            link.download = 'catalogo-unidades.csv';
            link.click();
            URL.revokeObjectURL(link.href);
          });
        })();
      </script>
      @elseif ($activeSection === 'subunits')
        @include('institution._subunits')
      @elseif ($activeSection === 'pharmacies')
        <section class="institution-native-table-card institution-native-table-card-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo de farmacias institucionales</h2>
              <p>{{ $visibleUnits }} farmacias institucionales visibles</p>
            </div>
          </div>
        </section>

        <section class="institution-native-table-card institution-native-table-card-wide institution-native-pharmacy-card">
          <div class="institution-native-table-heading">
            <div>
              <h2>Farmacias institucionales</h2>
              <p>Farmacias activas por unidad de la institucion.</p>
            </div>
          </div>
          <div class="institution-native-table-scroll">
            <table class="institution-native-table institution-native-pharmacy-table">
              <thead>
                <tr>
                  <th>Farmacia</th>
                  <th>Unidad</th>
                  <th>CLUES</th>
                  <th>Ubicacion</th>
                  <th>Tipo</th>
                  <th>Estatus</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($institution->medicalUnits as $unit)
                  <tr>
                    <td>
                      <strong>{{ data_get($unit->metadata, 'pharmacy_name', 'Farmacia institucional '.$unit->name) }}</strong>
                      <span>{{ $institutionLabel }}</span>
                    </td>
                    <td>{{ $unit->name }}</td>
                    <td>{{ $unit->clues ?? $unit->code ?? 'Sin CLUES' }}</td>
                    <td>
                      {{ $unit->city ?? $unit->municipality ?? 'Sin ciudad' }},
                      {{ $unit->state ?? $unit->entity ?? 'Sin estado' }}
                    </td>
                    <td>{{ data_get($unit->metadata, 'pharmacy_type', 'Farmacia Externa') }}</td>
                    <td><span class="institution-native-status">{{ $statusText($unit->status) }}</span></td>
                  </tr>
                @empty
                  <tr><td colspan="6">No hay farmacias institucionales registradas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>
      @elseif ($activeSection === 'services')
        @php
          $serviceCategories = $servicesCatalog->pluck('category')->filter()->unique();
          $visibleContracts = $servicesCatalog->sum(fn ($service) => $service->contractedServices->count());
        @endphp

        <section class="institution-native-table-card institution-native-table-card-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo de servicios</h2>
              <p>{{ $serviceCategories->count() }} categorias, {{ $servicesCatalog->count() }} servicios, {{ $visibleContracts }} contrataciones visibles</p>
            </div>
          </div>
        </section>

        <div class="institution-service-catalog">
          @forelse ($servicesCatalog as $service)
            @php
              $contracts = $service->contractedServices;
              $activeContracts = $contracts->where('status', 'active');
              $latestEnd = $contracts->pluck('ends_at')->filter()->sortDesc()->first();
            @endphp
            <article class="institution-service-card">
              <div class="institution-service-copy">
                <h2>{{ $service->name }}</h2>
                <p>
                  {{ $service->category ?? 'Sin categoria' }} &middot;
                  {{ $service->specialty ?? 'Servicio general' }} &middot;
                  {{ $latestEnd ? 'Vigencia '.$latestEnd->format('d/m/Y') : 'Sin vigencia' }}
                </p>
                <div>
                  <span class="institution-native-status">{{ $statusText($service->status) }}</span>
                  <small>{{ $activeContracts->count() }} unidades con este servicio contratado</small>
                </div>
              </div>
              <div class="institution-service-actions">
                <button type="button" data-service-edit="{{ $service->id }}">Editar</button>
                <button type="button" class="is-info" data-service-contract="{{ $service->id }}">i&nbsp; Informacion del contrato</button>
                <button type="button" data-service-units-toggle="{{ $service->id }}">+&nbsp; Habilitar Servicio a Unidades</button>
                <label>
                  <span class="sr-only">Unidades contratadas</span>
                  <select>
                    @if ($contracts->isEmpty())
                      <option>Sin unidades</option>
                    @else
                      @foreach ($contracts as $contract)
                        <option>{{ $contract->medicalUnit?->name ?? 'Sin unidad' }}</option>
                      @endforeach
                    @endif
                  </select>
                </label>
              </div>
              <form class="institution-service-unit-panel"
                    data-service-units-panel="{{ $service->id }}"
                    method="post"
                    action="{{ route('institution.services.units.sync', $service) }}"
                    hidden>
                @csrf
                <input type="hidden" name="institution" value="{{ $institution->id }}">
                <header>
                  <h3>Habilitar Servicio a Unidades</h3>
                  <strong><span data-service-selected-count>0</span> seleccionadas de {{ $institution->medicalUnits->count() }} hospitales</strong>
                </header>
                <label class="institution-service-unit-search">
                  <span aria-hidden="true">&#128269;</span>
                  <input type="search" data-service-unit-search placeholder="Buscar unidad, CLUES o ubicacion">
                </label>
                <label class="institution-service-select-all">
                  <input type="checkbox" data-service-select-all>
                  <strong>Seleccionar todo</strong>
                </label>
                <div class="institution-service-unit-list">
                  @foreach ($institution->medicalUnits as $unit)
                    <label data-service-unit-option data-search="{{ str($unit->name.' '.$unit->clues.' '.$unit->code.' '.$unit->city.' '.$unit->state)->lower() }}">
                      <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}"
                             data-service-unit-checkbox @checked($activeContracts->contains('medical_unit_id', $unit->id))>
                      <span>
                        <strong>{{ $unit->name }}</strong>
                        <small>{{ $unit->clues ?? $unit->code ?? 'Sin CLUES' }} - {{ $unit->city ?? $unit->state ?? 'Sin ubicacion' }}</small>
                      </span>
                    </label>
                  @endforeach
                </div>
                <footer>
                  <button type="submit" class="is-primary">Aceptar</button>
                  <button type="button" data-service-units-cancel>Cancelar</button>
                </footer>
              </form>
            </article>
          @empty
            <section class="institution-native-table-card institution-native-table-card-wide">
              <div class="institution-native-table-heading"><p>No hay servicios registrados.</p></div>
            </section>
          @endforelse
        </div>
        @include('institution._service_details', ['services' => $servicesCatalog])
        <script>
          (() => {
            const serviceSummary = document.querySelector('.institution-native-table-card-summary');
            const serviceCatalog = document.querySelector('.institution-service-catalog');
            const detailPanels = [...document.querySelectorAll('[data-service-detail-panel]')];
            const showDetail = (id, mode) => {
              serviceSummary.hidden = true;
              serviceCatalog.hidden = true;
              detailPanels.forEach((panel) => panel.hidden = !(panel.dataset.serviceDetailPanel === id && panel.dataset.serviceDetailMode === mode));
              window.scrollTo({ top: 0, behavior: 'smooth' });
            };
            document.querySelectorAll('[data-service-edit]').forEach((button) => button.addEventListener('click', () => showDetail(button.dataset.serviceEdit, 'edit')));
            document.querySelectorAll('[data-service-contract]').forEach((button) => button.addEventListener('click', () => showDetail(button.dataset.serviceContract, 'contract')));
            document.querySelectorAll('[data-service-detail-back]').forEach((button) => button.addEventListener('click', () => {
              detailPanels.forEach((panel) => panel.hidden = true);
              serviceSummary.hidden = false;
              serviceCatalog.hidden = false;
              window.scrollTo({ top: 0, behavior: 'smooth' });
            }));
            detailPanels.filter((panel) => panel.dataset.serviceDetailMode === 'edit').forEach((panel) => {
              const tabs = [...panel.querySelectorAll('[data-service-tab]')];
              const tabPanels = [...panel.querySelectorAll('[data-service-tab-panel]')];
              tabs.forEach((tab) => tab.addEventListener('click', () => {
                tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
                tabPanels.forEach((item) => item.hidden = item.dataset.serviceTabPanel !== tab.dataset.serviceTab);
              }));
              const search = panel.querySelector('[data-detail-unit-search]');
              const units = [...panel.querySelectorAll('[data-detail-unit]')];
              const checks = [...panel.querySelectorAll('[data-detail-unit-checkbox]')];
              const count = panel.querySelector('[data-detail-selected-count]');
              const updateCount = () => count.textContent = checks.filter((check) => check.checked).length;
              search.addEventListener('input', () => { const term = search.value.toLocaleLowerCase('es').trim(); units.forEach((unit) => unit.hidden = !unit.dataset.search.includes(term)); });
              panel.querySelector('[data-detail-select-visible]').addEventListener('click', () => { units.filter((unit) => !unit.hidden).forEach((unit) => unit.querySelector('[data-detail-unit-checkbox]').checked = true); updateCount(); });
              panel.querySelector('[data-detail-clear]').addEventListener('click', () => { checks.forEach((check) => check.checked = false); updateCount(); });
              checks.forEach((check) => check.addEventListener('change', updateCount));

              const editSearch = panel.querySelector('[data-edit-unit-search]');
              const editUnits = [...panel.querySelectorAll('[data-edit-unit]')];
              const editChecks = [...panel.querySelectorAll('[data-edit-unit-checkbox]')];
              const editSelectedCount = panel.querySelector('[data-edit-selected-count]');
              const editVisibleCount = panel.querySelector('[data-edit-visible-count]');
              const visibleEditUnits = () => editUnits.filter((unit) => !unit.hidden);
              const updateEditCounts = () => {
                editSelectedCount.textContent = editChecks.filter((check) => check.checked).length;
                editVisibleCount.textContent = visibleEditUnits().length;
              };
              editSearch.addEventListener('input', () => {
                const term = editSearch.value.toLocaleLowerCase('es').trim();
                editUnits.forEach((unit) => unit.hidden = !unit.dataset.search.includes(term));
                updateEditCounts();
              });
              panel.querySelector('[data-edit-select-visible]').addEventListener('click', () => {
                visibleEditUnits().forEach((unit) => unit.querySelector('[data-edit-unit-checkbox]').checked = true);
                updateEditCounts();
              });
              panel.querySelector('[data-edit-clear-visible]').addEventListener('click', () => {
                visibleEditUnits().forEach((unit) => unit.querySelector('[data-edit-unit-checkbox]').checked = false);
                updateEditCounts();
              });
              editChecks.forEach((check) => check.addEventListener('change', updateEditCounts));
              const statusInput = panel.querySelector('[data-service-status-input]');
              const statusToggle = panel.querySelector('[data-service-status-toggle]');
              if (statusInput && statusToggle) {
                const statusLabel = panel.querySelector('[data-service-status-label]');
                const statusAction = panel.querySelector('[data-service-status-action]');
                statusToggle.addEventListener('click', () => {
                  const isActive = statusInput.value === 'active';
                  statusInput.value = isActive ? 'inactive' : 'active';
                  statusLabel.textContent = isActive ? 'Inactivo' : 'Activo';
                  statusAction.textContent = isActive ? 'Activar servicio' : 'Inactivar servicio';
                });
              }
              updateEditCounts();
            });
            document.querySelectorAll('[data-service-units-panel]').forEach((panel) => {
              const id = panel.dataset.serviceUnitsPanel;
              const toggle = document.querySelector(`[data-service-units-toggle="${id}"]`);
              const search = panel.querySelector('[data-service-unit-search]');
              const selectAll = panel.querySelector('[data-service-select-all]');
              const checkboxes = [...panel.querySelectorAll('[data-service-unit-checkbox]')];
              const options = [...panel.querySelectorAll('[data-service-unit-option]')];
              const count = panel.querySelector('[data-service-selected-count]');

              const updateCount = () => {
                count.textContent = checkboxes.filter((checkbox) => checkbox.checked).length;
                selectAll.checked = checkboxes.length > 0 && checkboxes.every((checkbox) => checkbox.checked);
                selectAll.indeterminate = checkboxes.some((checkbox) => checkbox.checked) && !selectAll.checked;
              };

              toggle.addEventListener('click', () => {
                panel.hidden = !panel.hidden;
                if (!panel.hidden) search.focus();
              });
              panel.querySelector('[data-service-units-cancel]').addEventListener('click', () => panel.hidden = true);
              search.addEventListener('input', () => {
                const term = search.value.toLocaleLowerCase('es').trim();
                options.forEach((option) => option.hidden = !option.dataset.search.includes(term));
              });
              selectAll.addEventListener('change', () => {
                options.filter((option) => !option.hidden).forEach((option) => {
                  option.querySelector('[data-service-unit-checkbox]').checked = selectAll.checked;
                });
                updateCount();
              });
              checkboxes.forEach((checkbox) => checkbox.addEventListener('change', updateCount));
              updateCount();
            });
          })();
        </script>
      @elseif ($activeSection === 'medications')
        <section class="institution-native-table-card institution-native-table-card-summary institution-medication-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo institucional de medicamentos</h2>
              <p>{{ $catalogItems->count() }} visibles - {{ $catalogItems->count() }} medicamentos institucionales - alimenta {{ $visibleUnits }} farmacias externas</p>
            </div>
            <button type="button" data-medication-csv>&darr;&nbsp; CSV</button>
          </div>
        </section>

        <section class="institution-native-table-card institution-native-table-card-wide institution-medication-card" data-medication-list-panel>
          <div class="institution-native-table-heading">
            <div>
              <h2>Medicamentos institucionales</h2>
              <p>Alimenta el catalogo de Farmacia Externa de las unidades.</p>
            </div>
            <button type="button" class="institution-medication-new" data-show-medication-form>Nuevo medicamento</button>
          </div>
          <div class="institution-native-table-scroll">
            <table class="institution-native-table institution-medication-table">
              <thead>
                <tr>
                  <th>Clave (CNIS)</th>
                  <th>Grupo</th>
                  <th>Insumo</th>
                  <th>Descripcion</th>
                  <th>Unidades moviles</th>
                  <th>Unidades nucleos basicos</th>
                  <th>CESSA</th>
                  <th>Estatus</th>
                  <th>Acciones</th>
                </tr>
                <tr class="institution-medication-filter-row">
                  <th><input type="search" data-medication-filter="cnis" placeholder="Buscar clave"></th>
                  <th><input type="search" data-medication-filter="group" placeholder="Buscar grupo"></th>
                  <th><input type="search" data-medication-filter="name" placeholder="Buscar insumo"></th>
                  <th><input type="search" data-medication-filter="description" placeholder="Buscar descripcion"></th>
                  <th></th><th></th><th></th>
                  <th><select data-medication-status><option value="all">Todos</option><option value="active">Activo</option><option value="inactive">Inactivo</option></select></th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @forelse ($catalogItems as $item)
                  @php
                    $itemGroup = data_get($item->metadata, 'group', $item->therapeutic_group ?? 'Grupo no clasificado');
                    $itemDescription = $item->description ?? $item->presentation ?? $item->generic_name ?? 'Sin descripcion';
                    $mobileUnits = data_get($item->metadata, 'mobile_units', 'No');
                    $basicUnits = data_get($item->metadata, 'basic_units', 'Si');
                    $cessa = data_get($item->metadata, 'cessa', 'Si');
                    $availabilityValue = fn ($value) => match (strtolower((string) $value)) {
                      'si', 'sÃ­', '1', 'true' => 'yes',
                      'no', '0', 'false' => 'no',
                      default => 'undefined',
                    };
                  @endphp
                  <tr data-medication-row
                      data-cnis="{{ str($item->cnis)->lower() }}"
                      data-group="{{ str($itemGroup)->lower() }}"
                      data-name="{{ str($item->name)->lower() }}"
                      data-description="{{ str($itemDescription)->lower() }}"
                      data-status="{{ $item->status }}">
                    <td>{{ $item->cnis ?? 'Sin CNIS' }}</td>
                    <td>{{ $itemGroup }}</td>
                    <td><strong>{{ strtoupper($item->name) }}</strong><span>Actualizado {{ $item->updated_at?->format('d/m/Y') }}</span></td>
                    <td>{{ strtoupper($itemDescription) }}</td>
                    @foreach ([$mobileUnits, $basicUnits, $cessa] as $availability)
                      @php
                        $isAvailable = in_array(strtolower((string) $availability), ['si', 'sÃ­', '1', 'true'], true);
                      @endphp
                      <td><span @class(['institution-medication-availability', 'is-yes' => $isAvailable])>{{ $isAvailable ? 'Si' : 'No' }}</span></td>
                    @endforeach
                    <td><span class="institution-native-status">{{ $statusText($item->status) }}</span></td>
                    <td><button type="button" class="institution-medication-edit"
                                data-edit-medication
                                data-update-url="{{ route('institution.medications.update', $item) }}"
                                data-cnis="{{ $item->cnis }}"
                                data-group="{{ $itemGroup }}"
                                data-name="{{ $item->name }}"
                                data-description="{{ $itemDescription }}"
                                data-mobile-units="{{ $availabilityValue($mobileUnits) }}"
                                data-basic-units="{{ $availabilityValue($basicUnits) }}"
                                data-cessa="{{ $availabilityValue($cessa) }}"
                                data-status="{{ $item->status }}">Editar</button></td>
                  </tr>
                @empty
                  <tr><td colspan="9">No hay medicamentos institucionales registrados.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="institution-native-table-card institution-native-table-card-wide institution-medication-form-card"
                 data-medication-form-panel hidden>
          <div class="institution-native-table-heading">
            <div>
              <h2 data-medication-form-title>Nuevo medicamento</h2>
              <p>Captura o actualiza medicamentos del catalogo institucional.</p>
            </div>
            <button type="button" data-hide-medication-form>Regresar</button>
          </div>
          <form method="post" action="{{ route('institution.medications.store') }}"
                data-store-action="{{ route('institution.medications.store') }}"
                class="institution-medication-form" data-medication-form>
            @csrf
            <input type="hidden" name="_method" value="patch" data-medication-method disabled>
            <input type="hidden" name="form_mode" value="create" data-medication-form-mode>
            <input type="hidden" name="institution" value="{{ $institution->id }}">
            <label>Clave (CNIS)
              <input name="cnis" value="{{ old('cnis') }}" placeholder="Ej. 010.000.0104.00" required>
            </label>
            <label>Grupo
              <textarea name="therapeutic_group" rows="3" placeholder="Ej. GRUPO No. 1: ANALGESIA" required>{{ old('therapeutic_group') }}</textarea>
            </label>
            <label>Insumo
              <textarea name="name" rows="3" required>{{ old('name') }}</textarea>
            </label>
            <label>Descripcion
              <textarea name="description" rows="3" required>{{ old('description') }}</textarea>
            </label>
            @foreach ([
              'mobile_units' => 'Unidades moviles',
              'basic_units' => 'Unidades nucleos basicos',
              'cessa' => 'CESSA',
            ] as $field => $label)
              <label>{{ $label }}
                <select name="{{ $field }}" required>
                  <option value="undefined" @selected(old($field, 'undefined') === 'undefined')>Sin definir</option>
                  <option value="yes" @selected(old($field) === 'yes')>Si</option>
                  <option value="no" @selected(old($field) === 'no')>No</option>
                </select>
              </label>
            @endforeach
            <label>Estatus
              <select name="status" required>
                <option value="active" @selected(old('status', 'active') === 'active')>Activo</option>
                <option value="inactive" @selected(old('status') === 'inactive')>Inactivo</option>
              </select>
            </label>
            <button type="submit">Guardar medicamento</button>
          </form>
        </section>

        <script>
          (() => {
            const listPanel = document.querySelector('[data-medication-list-panel]');
            const formPanel = document.querySelector('[data-medication-form-panel]');
            const medicationForm = document.querySelector('[data-medication-form]');
            const methodField = document.querySelector('[data-medication-method]');
            const modeField = document.querySelector('[data-medication-form-mode]');
            const formTitle = document.querySelector('[data-medication-form-title]');
            const setField = (name, value) => medicationForm.elements.namedItem(name).value = value ?? '';
            const showForm = () => {
              listPanel.hidden = true;
              formPanel.hidden = false;
              formPanel.querySelector('input, textarea, select')?.focus();
            };
            document.querySelector('[data-show-medication-form]').addEventListener('click', () => {
              medicationForm.reset();
              medicationForm.action = medicationForm.dataset.storeAction;
              methodField.disabled = true;
              modeField.value = 'create';
              formTitle.textContent = 'Nuevo medicamento';
              showForm();
            });
            document.querySelectorAll('[data-edit-medication]').forEach((button) => {
              button.addEventListener('click', () => {
                medicationForm.action = button.dataset.updateUrl;
                methodField.disabled = false;
                modeField.value = 'edit';
                formTitle.textContent = 'Editar medicamento';
                setField('cnis', button.dataset.cnis);
                setField('therapeutic_group', button.dataset.group);
                setField('name', button.dataset.name);
                setField('description', button.dataset.description);
                setField('mobile_units', button.dataset.mobileUnits);
                setField('basic_units', button.dataset.basicUnits);
                setField('cessa', button.dataset.cessa);
                setField('status', button.dataset.status);
                showForm();
              });
            });
            document.querySelector('[data-hide-medication-form]').addEventListener('click', () => {
              formPanel.hidden = true;
              listPanel.hidden = false;
            });

            const rows = [...document.querySelectorAll('[data-medication-row]')];
            const filters = [...document.querySelectorAll('[data-medication-filter]')];
            const status = document.querySelector('[data-medication-status]');
            const normalize = (value) => value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const applyFilters = () => rows.forEach((row) => {
              const matchesText = filters.every((filter) => normalize(row.dataset[filter.dataset.medicationFilter] ?? '').includes(normalize(filter.value.trim())));
              const matchesStatus = status.value === 'all' || row.dataset.status === status.value;
              row.hidden = !(matchesText && matchesStatus);
            });

            filters.forEach((filter) => filter.addEventListener('input', applyFilters));
            status.addEventListener('change', applyFilters);
            document.querySelector('[data-medication-csv]').addEventListener('click', () => {
              const tableRows = [...document.querySelectorAll('.institution-medication-table tr')]
                .filter((row) => !row.hidden && !row.classList.contains('institution-medication-filter-row'));
              const csv = tableRows.map((row) => [...row.querySelectorAll('th, td')].slice(0, 8)
                .map((cell) => `"${cell.innerText.trim().replaceAll('"', '""')}"`).join(',')).join('\r\n');
              const link = document.createElement('a');
              link.href = URL.createObjectURL(new Blob([`\uFEFF${csv}`], { type: 'text/csv;charset=utf-8' }));
              link.download = 'catalogo-institucional-medicamentos.csv';
              link.click();
              URL.revokeObjectURL(link.href);
            });

            @if ($errors->any() && old('cnis'))
              showForm();
            @endif
          })();
        </script>
      @elseif ($activeSection === 'specialties')
        @php
          $specialtyItems = $servicesCatalog
              ->map(fn ($service) => (object) [
                'id' => $service->id,
                'name' => $service->specialty ?? $service->name,
                'status' => $service->status,
              ])
              ->unique(fn ($specialty) => strtolower($specialty->name))
              ->sortBy('name')
              ->values();
        @endphp

        <section class="institution-native-table-card institution-native-table-card-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo institucional de especialidades</h2>
              <p>{{ $specialtyItems->count() }} visibles - {{ $specialtyItems->count() }} especialidades institucionales - disponibles para {{ $visibleUnits }} unidades</p>
            </div>
          </div>
        </section>

        <section class="institution-native-table-card institution-native-table-card-wide institution-specialty-card" data-specialty-list-panel>
          <div class="institution-native-table-heading">
            <div>
              <h2>Especialidades institucionales</h2>
              <p>Las unidades debajo de esta institucion consultan este catalogo.</p>
            </div>
            <button type="button" class="institution-medication-new" data-show-specialty-form>Nueva Especialidad</button>
          </div>
          <div class="institution-specialty-toolbar">
            <label>
              <span aria-hidden="true">âŒ•</span>
              <input type="search" data-specialty-search placeholder="Buscar especialidad">
            </label>
            <label>Estatus
              <select data-specialty-status>
                <option value="all">Todos</option>
                <option value="active">Activo</option>
                <option value="inactive">Inactivo</option>
              </select>
            </label>
          </div>
          <div class="institution-native-table-scroll">
            <table class="institution-native-table institution-specialty-table">
              <thead><tr><th>No.</th><th>Especialidad</th><th>Estatus</th><th>Acciones</th></tr></thead>
              <tbody>
                @forelse ($specialtyItems as $specialty)
                  <tr data-specialty-row data-name="{{ str($specialty->name)->lower() }}" data-status="{{ $specialty->status }}">
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $specialty->name }}</strong><span>Catalogo institucional</span></td>
                    <td><span class="institution-native-status">{{ $statusText($specialty->status) }}</span></td>
                    <td>
                      <div class="institution-specialty-actions">
                        <button type="button" data-edit-specialty
                                data-update-url="{{ route('institution.specialties.update', $specialty->id) }}"
                                data-name="{{ $specialty->name }}"
                                data-status="{{ $specialty->status }}">Editar</button>
                        <form method="post" action="{{ route('institution.specialties.status', $specialty->id) }}">
                          @csrf
                          @method('patch')
                          <input type="hidden" name="institution" value="{{ $institution->id }}">
                          <input type="hidden" name="status" value="{{ $specialty->status === 'active' ? 'inactive' : 'active' }}">
                          <button type="submit" class="status-action">
                            {{ $specialty->status === 'active' ? 'Inactivar' : 'Activar' }}
                          </button>
                        </form>
                        <form method="post" action="{{ route('institution.specialties.destroy', $specialty->id) }}"
                              onsubmit="return confirm('Â¿Deseas eliminar definitivamente esta especialidad? Esta accion no se puede deshacer.')">
                          @csrf
                          @method('delete')
                          <input type="hidden" name="institution" value="{{ $institution->id }}">
                          <button type="submit" class="danger">Eliminar</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="4">No hay especialidades institucionales registradas.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </section>

        <section class="institution-native-table-card institution-native-table-card-wide institution-specialty-form-card"
                 data-specialty-form-panel hidden>
          <div class="institution-native-table-heading">
            <div>
              <h2 data-specialty-form-title>Nueva especialidad</h2>
              <p>Captura o actualiza especialidades disponibles para las unidades de la institucion.</p>
            </div>
            <button type="button" data-hide-specialty-form>Regresar</button>
          </div>
          <form method="post" action="{{ route('institution.specialties.store') }}"
                data-store-action="{{ route('institution.specialties.store') }}"
                class="institution-specialty-form" data-specialty-form>
            @csrf
            <input type="hidden" name="_method" value="patch" data-specialty-method disabled>
            <input type="hidden" name="institution" value="{{ $institution->id }}">
            <input type="hidden" name="form_context" value="specialty">
            <input type="hidden" name="form_mode" value="create" data-specialty-mode>
            <label>Nombre de especialidad
              <input name="name" value="{{ old('form_context') === 'specialty' ? old('name') : '' }}" required>
            </label>
            <label>Estatus
              <select name="status" required>
                <option value="active" @selected(old('form_context') !== 'specialty' || old('status', 'active') === 'active')>Activo</option>
                <option value="inactive" @selected(old('form_context') === 'specialty' && old('status') === 'inactive')>Inactivo</option>
              </select>
            </label>
            <button type="submit">Guardar especialidad</button>
          </form>
        </section>

        <script>
          (() => {
            const listPanel = document.querySelector('[data-specialty-list-panel]');
            const formPanel = document.querySelector('[data-specialty-form-panel]');
            const specialtyForm = document.querySelector('[data-specialty-form]');
            const methodField = document.querySelector('[data-specialty-method]');
            const modeField = document.querySelector('[data-specialty-mode]');
            const formTitle = document.querySelector('[data-specialty-form-title]');
            const showForm = () => {
              listPanel.hidden = true;
              formPanel.hidden = false;
              formPanel.querySelector('input:not([type="hidden"])')?.focus();
            };
            document.querySelector('[data-show-specialty-form]').addEventListener('click', () => {
              specialtyForm.reset();
              specialtyForm.action = specialtyForm.dataset.storeAction;
              methodField.disabled = true;
              modeField.value = 'create';
              formTitle.textContent = 'Nueva especialidad';
              showForm();
            });
            document.querySelectorAll('[data-edit-specialty]').forEach((button) => {
              button.addEventListener('click', () => {
                specialtyForm.action = button.dataset.updateUrl;
                methodField.disabled = false;
                modeField.value = 'edit';
                formTitle.textContent = 'Editar especialidad';
                specialtyForm.elements.namedItem('name').value = button.dataset.name;
                specialtyForm.elements.namedItem('status').value = button.dataset.status;
                showForm();
              });
            });
            document.querySelector('[data-hide-specialty-form]').addEventListener('click', () => {
              formPanel.hidden = true;
              listPanel.hidden = false;
            });

            const search = document.querySelector('[data-specialty-search]');
            const status = document.querySelector('[data-specialty-status]');
            const rows = [...document.querySelectorAll('[data-specialty-row]')];
            const normalize = (value) => value.toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const apply = () => rows.forEach((row) => {
              const matchesName = normalize(row.dataset.name).includes(normalize(search.value.trim()));
              const matchesStatus = status.value === 'all' || row.dataset.status === status.value;
              row.hidden = !(matchesName && matchesStatus);
            });
            search.addEventListener('input', apply);
            status.addEventListener('change', apply);

            @if ($errors->any() && old('form_context') === 'specialty')
              showForm();
            @endif
          })();
        </script>
      @elseif ($activeSection === 'create-unit')
        <section class="institution-unit-create-card">
          <header><h2>Nueva unidad</h2></header>
          <form method="post" action="{{ route('institution.units.store') }}" class="institution-unit-create-form">
            @csrf
            <input type="hidden" name="institution" value="{{ $institution->id }}">
            <input type="hidden" name="form_context" value="unit">
            <label class="is-half">CLUES
              <input name="clues" value="{{ old('form_context') === 'unit' ? old('clues') : '' }}" required>
            </label>
            <label class="is-wide">Nombre de la unidad
              <input name="name" value="{{ old('form_context') === 'unit' ? old('name') : '' }}" required>
            </label>
            <label>Entidad<input name="entity" value="{{ old('form_context') === 'unit' ? old('entity') : '' }}" required></label>
            <label>Municipio<input name="municipality" value="{{ old('form_context') === 'unit' ? old('municipality') : '' }}" required></label>
            <label>Nivel de atencion<input name="care_level" value="{{ old('form_context') === 'unit' ? old('care_level') : '' }}" required></label>
            <label>Tipologia<input name="typology" value="{{ old('form_context') === 'unit' ? old('typology') : '' }}" required></label>
            <label class="is-wide">Domicilio
              <textarea name="address" rows="4" required>{{ old('form_context') === 'unit' ? old('address') : '' }}</textarea>
            </label>
            <label>Latitud<input name="latitude" type="number" step="any" value="{{ old('form_context') === 'unit' ? old('latitude') : '' }}"></label>
            <label>Longitud<input name="longitude" type="number" step="any" value="{{ old('form_context') === 'unit' ? old('longitude') : '' }}"></label>
            <label>Partida<input name="partida" value="{{ old('form_context') === 'unit' ? old('partida') : '' }}"></label>
            <label>Subpartida<input name="subpartida" value="{{ old('form_context') === 'unit' ? old('subpartida') : '' }}"></label>
            <label>Camas<input name="beds" type="number" min="0" value="{{ old('form_context') === 'unit' ? old('beds', 0) : 0 }}" required></label>
            <label>Usuario de unidad<input name="unit_username" value="{{ old('form_context') === 'unit' ? old('unit_username') : '' }}" placeholder="Ej. unidad.hospital" required></label>
            <label>Contrasena de unidad<input name="unit_password" type="password" placeholder="Captura la contrasena" required></label>
            <button type="submit">Guardar unidad</button>
          </form>
        </section>

      @else
        <section class="institution-unit-create-card institution-service-create-card">
          <header><h2>Nuevo servicio</h2></header>
          <form method="post" action="{{ route('institution.services.store') }}" class="institution-unit-create-form institution-service-create-form">
            @csrf
            <input type="hidden" name="institution" value="{{ $institution->id }}">
            <input type="hidden" name="form_context" value="service">
            <label class="is-wide">Unidades con servicio contratado
              <select name="unit_ids[]" multiple size="1" aria-label="Seleccionar hospitales">
                @foreach ($institution->medicalUnits as $unit)
                  <option value="{{ $unit->id }}" @selected(in_array($unit->id, old('unit_ids', [])))>{{ $unit->name }}{{ $unit->clues ? ' - '.$unit->clues : '' }}</option>
                @endforeach
              </select>
              <small>Usa Ctrl para seleccionar mÃ¡s de un hospital.</small>
            </label>
            <div class="institution-service-create-divider"><strong>Datos del Servicio</strong></div>
            <label>Categoria
              <select name="category" required>
                @foreach (['Asistenciales', 'Criticos', 'Diagnosticos', 'Farmaceuticos', 'Quirurgicos'] as $category)
                  <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                @endforeach
              </select>
            </label>
            <label>Especialidad
              <select name="specialty" required>
                @foreach ($servicesCatalog->pluck('specialty')->filter()->unique()->sort() as $specialty)
                  <option value="{{ $specialty }}" @selected(old('specialty') === $specialty)>{{ $specialty }}</option>
                @endforeach
                <option value="Servicio general" @selected(old('specialty') === 'Servicio general')>Servicio general</option>
              </select>
            </label>
            <label>Servicio<input name="name" value="{{ old('form_context') === 'service' ? old('name') : '' }}" placeholder="Captura el nombre del servicio" required></label>
            <label>Inicio<input name="starts_at" type="date" value="{{ old('form_context') === 'service' ? old('starts_at') : '' }}" required></label>
            <label>Fin<input name="ends_at" type="date" value="{{ old('form_context') === 'service' ? old('ends_at') : '' }}" required></label>
            <label>SLA<input name="sla" value="{{ old('form_context') === 'service' ? old('sla', 'Horario habil') : 'Horario habil' }}" required></label>
            <button type="submit">Guardar servicio</button>
          </form>
        </section>

      @endif

    </section>
  </div>
@endsection
