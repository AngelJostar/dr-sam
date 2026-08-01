<?php $__env->startSection('body_class', 'institution-native-body'); ?>

<?php
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
?>

<?php $__env->startSection('content'); ?>
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
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'units']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id])); ?>"><span>U</span>Catalogo de Unidades</a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'subunits']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'subunits'])); ?>"><span>Su</span>Catalogo de Subunidades</a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'pharmacies']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'pharmacies'])); ?>"><span>F</span>Catalogo de Farmacias Institucionales</a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'services']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services'])); ?>"><span>S</span>Catalogo de Servicios</a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'medications']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications'])); ?>"><span>Rx</span>Catalogo de Medicamentos</a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'specialties']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties'])); ?>"><span>E</span>Catalogo de Especialidades</a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'create-unit']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'create-unit'])); ?>"><span>+</span>Alta de unidad</a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'create-service']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'create-service'])); ?>"><span>+</span>Alta de servicios</a>
      </nav>
    </aside>

    <section class="institution-native-workspace">
      <?php if(session('status')): ?>
        <div class="notice success"><?php echo e(session('status')); ?></div>
      <?php endif; ?>

      <?php if($errors->any()): ?>
        <div class="notice danger">
          <strong>No se pudo actualizar.</strong>
          <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </ul>
        </div>
      <?php endif; ?>

      <header class="institution-native-header">
        <div>
          <p class="eyebrow">Portal institucional - <?php echo e(strtoupper($institutionLabel)); ?></p>
          <h1><?php echo e(match ($activeSection) {
            'pharmacies' => 'Catalogo de farmacias institucionales',
            'subunits' => 'Catalogo de subunidades',
            'services' => 'Catalogo de servicios',
            'medications' => 'Catalogo institucional de medicamentos',
            'specialties' => 'Catalogo institucional de especialidades',
            'create-unit' => 'Alta de unidad',
            'create-service' => 'Alta de servicios',
            default => 'Catalogo de unidades',
          }); ?></h1>
          <p>Usuario activo: <?php echo e($institutionLabel); ?> (<?php echo e($institution->owner?->username ?? $institution->external_id ?? 'institucion'); ?>)</p>

          <form method="get" action="<?php echo e(route('institution.dashboard')); ?>">
            <?php if($activeSection !== 'units'): ?>
              <input type="hidden" name="section" value="<?php echo e($activeSection); ?>">
            <?php endif; ?>
            <label>
              Institucion
              <select name="institution" onchange="this.form.submit()">
                <?php $__currentLoopData = $availableInstitutions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $institutionOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($institutionOption->id); ?>" <?php if($institutionOption->is($institution)): echo 'selected'; endif; ?>>
                    <?php echo e($institutionOption->name); ?>

                  </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </label>
          </form>
        </div>
        <div class="institution-native-session">
          <strong><?php echo e($institutionLabel); ?></strong>
          <a href="<?php echo e(route('dashboard')); ?>" aria-label="Salir al dashboard">-></a>
        </div>
      </header>

      <?php if($activeSection === 'edit-unit' && ($editingUnit = $institution->medicalUnits->firstWhere('id', (int) request('unit')))): ?>
        <section class="institution-edit-unit-page">
          <div class="institution-edit-unit-bar"><div><strong><?php echo e($editingUnit->name); ?></strong><small><?php echo e($editingUnit->clues ?? $editingUnit->code); ?> · <?php echo e($editingUnit->municipality ?? $editingUnit->city); ?></small></div><a href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id])); ?>">← Volver a Unidades</a></div>
        <section class="institution-unit-create-card institution-unit-edit-card">
          <header><div><h2>Editar unidad</h2><p><?php echo e($editingUnit->name); ?> · <?php echo e($editingUnit->clues ?? $editingUnit->code); ?></p></div><a href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id])); ?>">Cancelar</a></header>
          <form method="post" action="<?php echo e(route('institution.units.update', $editingUnit)); ?>" class="institution-unit-create-form">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <label class="is-half">Nombre de la unidad<input name="name" value="<?php echo e($editingUnit->name); ?>" required></label><label class="is-half">CLUES<input name="clues" value="<?php echo e($editingUnit->clues); ?>"></label>
            <label>Código interno<input name="code" value="<?php echo e($editingUnit->code); ?>"></label><label>Estatus<select name="status"><?php $__currentLoopData = ['active'=>'Activo','inactive'=>'Inactivo','maintenance'=>'Mantenimiento','suspended'=>'Suspendido']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if($editingUnit->status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></label>
            <label>Usuario de unidad<input name="unit_username" value="<?php echo e($editingUnit->unit_username); ?>"></label><label>Nueva contraseña de unidad<input name="unit_password" type="password" value=""></label>
            <label>Entidad<input name="entity" value="<?php echo e($editingUnit->entity ?? $editingUnit->state); ?>"></label><label>Municipio<input name="municipality" value="<?php echo e($editingUnit->municipality ?? $editingUnit->city); ?>"></label>
            <label>Nivel de atención<input name="care_level" value="<?php echo e($editingUnit->care_level); ?>"></label><label>Tipología<input name="typology" value="<?php echo e($editingUnit->typology ?? $editingUnit->type); ?>"></label><label>Tipo de unidad<input name="type" value="<?php echo e($editingUnit->type); ?>"></label><label>Camas<input type="number" min="0" name="beds" value="<?php echo e($editingUnit->beds); ?>"></label>
            <label class="is-wide">Domicilio<textarea name="address" rows="4"><?php echo e($editingUnit->address); ?></textarea></label><label>Latitud<input type="number" step="any" name="latitude" value="<?php echo e($editingUnit->latitude); ?>"></label><label>Longitud<input type="number" step="any" name="longitude" value="<?php echo e($editingUnit->longitude); ?>"></label><div class="institution-edit-actions"><a href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id])); ?>">Cancelar</a><button type="submit">Guardar cambios</button></div>
          </form>
        </section>
        </section>
      <?php elseif($activeSection === 'units'): ?>
      <section class="institution-native-filters" aria-label="Filtros de unidades">
        <label class="search">
          <span aria-hidden="true">O</span>
          <input type="search" data-unit-search placeholder="Buscar unidad, ciudad o servicio">
        </label>
        <label>
          Tipo
          <select data-unit-type-filter>
            <option>Todos</option>
            <?php $__currentLoopData = $institution->medicalUnits->pluck('type')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unitType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($unitType); ?>"><?php echo e($unitType); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>
          Nivel de atencion
          <select data-unit-level-filter>
            <option>Todos</option>
            <?php $__currentLoopData = $institution->medicalUnits->pluck('care_level')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $careLevel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($careLevel); ?>"><?php echo e($careLevel); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <label>
          Estatus
          <select data-unit-status-filter>
            <option>Todos</option>
            <?php $__currentLoopData = $institution->medicalUnits->pluck('status')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unitStatus): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <option value="<?php echo e($unitStatus); ?>"><?php echo e($statusText($unitStatus)); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </select>
        </label>
        <button type="button" data-unit-csv>&darr;&nbsp; CSV</button>
      </section>

      <section id="units" class="institution-native-table-card">
        <div class="institution-native-table-heading">
          <div>
            <h2>Unidades</h2>
            <p><span data-visible-unit-count><?php echo e($visibleUnits); ?></span> unidades visibles</p>
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
              <?php $__empty_1 = true; $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr data-unit-row
                    data-unit-search-value="<?php echo e(str($unit->name.' '.$unit->city.' '.$unit->municipality.' '.$unit->state.' '.$unit->type.' '.$unit->care_level)->lower()); ?>"
                    data-unit-type="<?php echo e($unit->type); ?>"
                    data-unit-level="<?php echo e($unit->care_level); ?>"
                    data-unit-status="<?php echo e($unit->status); ?>">
                  <td>
                    <strong><?php echo e($unit->name); ?></strong>
                    <span><?php echo e($unit->code ?? $unit->external_id ?? 'Sin codigo'); ?></span>
                  </td>
                  <td><?php echo e($unit->clues ?? $unit->code ?? 'Sin CLUES'); ?></td>
                  <td>
                    <strong><?php echo e($unit->unit_username ?? 'unidad-'.$unit->id); ?></strong>
                    <span>Contraseña protegida</span>
                  </td>
                  <td>
                    <?php echo e($unit->city ?? $unit->municipality ?? 'Sin ciudad'); ?>

                    <span><?php echo e($unit->state ?? $unit->entity ?? 'Sin estado'); ?></span>
                  </td>
                  <td>
                    <strong><?php echo e($unit->care_level ?? 'Tercer Nivel'); ?></strong>
                    <span><?php echo e($unit->type ?? $unit->typology ?? 'Hospital general'); ?></span>
                  </td>
                  <td><?php echo e($institution->services->where('medical_unit_id', $unit->id)->where('status', 'active')->count()); ?></td>
                  <td><span class="institution-native-status"><?php echo e($statusText($unit->status)); ?></span></td>
                  <td>
                    <div class="institution-native-actions">
                      <a href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'edit-unit', 'unit' => $unit->id])); ?>">Editar</a>
                      <form method="post" action="<?php echo e(route('institution.units.status', $unit)); ?>" onsubmit="return confirm('&iquest;<?php echo e($unit->status === 'inactive' ? 'Reactivar' : 'Desactivar'); ?> esta unidad?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="status" value="<?php echo e($unit->status === 'inactive' ? 'active' : 'inactive'); ?>">
                        <button type="submit" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['danger' => $unit->status !== 'inactive']); ?>"><?php echo e($unit->status === 'inactive' ? 'Reactivar' : 'Eliminar'); ?></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                  <td colspan="8">No hay unidades registradas.</td>
                </tr>
              <?php endif; ?>
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
      <?php elseif($activeSection === 'subunits'): ?>
        <?php echo $__env->make('institution._subunits', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php elseif($activeSection === 'pharmacies'): ?>
        <section class="institution-native-table-card institution-native-table-card-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo de farmacias institucionales</h2>
              <p><?php echo e($visibleUnits); ?> farmacias institucionales visibles</p>
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
                <?php $__empty_1 = true; $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr>
                    <td>
                      <strong><?php echo e(data_get($unit->metadata, 'pharmacy_name', 'Farmacia institucional '.$unit->name)); ?></strong>
                      <span><?php echo e($institutionLabel); ?></span>
                    </td>
                    <td><?php echo e($unit->name); ?></td>
                    <td><?php echo e($unit->clues ?? $unit->code ?? 'Sin CLUES'); ?></td>
                    <td>
                      <?php echo e($unit->city ?? $unit->municipality ?? 'Sin ciudad'); ?>,
                      <?php echo e($unit->state ?? $unit->entity ?? 'Sin estado'); ?>

                    </td>
                    <td><?php echo e(data_get($unit->metadata, 'pharmacy_type', 'Farmacia Externa')); ?></td>
                    <td><span class="institution-native-status"><?php echo e($statusText($unit->status)); ?></span></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="6">No hay farmacias institucionales registradas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      <?php elseif($activeSection === 'services'): ?>
        <?php
          $serviceCategories = $servicesCatalog->pluck('category')->filter()->unique();
          $visibleContracts = $servicesCatalog->sum(fn ($service) => $service->contractedServices->count());
        ?>

        <section class="institution-native-table-card institution-native-table-card-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo de servicios</h2>
              <p><?php echo e($serviceCategories->count()); ?> categorias, <?php echo e($servicesCatalog->count()); ?> servicios, <?php echo e($visibleContracts); ?> contrataciones visibles</p>
            </div>
          </div>
        </section>

        <div class="institution-service-catalog">
          <?php $__empty_1 = true; $__currentLoopData = $servicesCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $contracts = $service->contractedServices;
              $activeContracts = $contracts->where('status', 'active');
              $latestEnd = $contracts->pluck('ends_at')->filter()->sortDesc()->first();
            ?>
            <article class="institution-service-card">
              <div class="institution-service-copy">
                <h2><?php echo e($service->name); ?></h2>
                <p>
                  <?php echo e($service->category ?? 'Sin categoria'); ?> &middot;
                  <?php echo e($service->specialty ?? 'Servicio general'); ?> &middot;
                  <?php echo e($latestEnd ? 'Vigencia '.$latestEnd->format('d/m/Y') : 'Sin vigencia'); ?>

                </p>
                <div>
                  <span class="institution-native-status"><?php echo e($statusText($service->status)); ?></span>
                  <small><?php echo e($activeContracts->count()); ?> unidades con este servicio contratado</small>
                </div>
              </div>
              <div class="institution-service-actions">
                <button type="button" data-service-edit="<?php echo e($service->id); ?>">Editar</button>
                <button type="button" class="is-info" data-service-contract="<?php echo e($service->id); ?>">i&nbsp; Informacion del contrato</button>
                <button type="button" data-service-units-toggle="<?php echo e($service->id); ?>">+&nbsp; Habilitar Servicio a Unidades</button>
                <label>
                  <span class="sr-only">Unidades contratadas</span>
                  <select>
                    <?php if($contracts->isEmpty()): ?>
                      <option>Sin unidades</option>
                    <?php else: ?>
                      <?php $__currentLoopData = $contracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option><?php echo e($contract->medicalUnit?->name ?? 'Sin unidad'); ?></option>
                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php endif; ?>
                  </select>
                </label>
              </div>
              <form class="institution-service-unit-panel"
                    data-service-units-panel="<?php echo e($service->id); ?>"
                    method="post"
                    action="<?php echo e(route('institution.services.units.sync', $service)); ?>"
                    hidden>
                <?php echo csrf_field(); ?>
                <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
                <header>
                  <h3>Habilitar Servicio a Unidades</h3>
                  <strong><span data-service-selected-count>0</span> seleccionadas de <?php echo e($institution->medicalUnits->count()); ?> hospitales</strong>
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
                  <?php $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <label data-service-unit-option data-search="<?php echo e(str($unit->name.' '.$unit->clues.' '.$unit->code.' '.$unit->city.' '.$unit->state)->lower()); ?>">
                      <input type="checkbox" name="unit_ids[]" value="<?php echo e($unit->id); ?>"
                             data-service-unit-checkbox <?php if($activeContracts->contains('medical_unit_id', $unit->id)): echo 'checked'; endif; ?>>
                      <span>
                        <strong><?php echo e($unit->name); ?></strong>
                        <small><?php echo e($unit->clues ?? $unit->code ?? 'Sin CLUES'); ?> - <?php echo e($unit->city ?? $unit->state ?? 'Sin ubicacion'); ?></small>
                      </span>
                    </label>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <footer>
                  <button type="submit" class="is-primary">Aceptar</button>
                  <button type="button" data-service-units-cancel>Cancelar</button>
                </footer>
              </form>
            </article>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <section class="institution-native-table-card institution-native-table-card-wide">
              <div class="institution-native-table-heading"><p>No hay servicios registrados.</p></div>
            </section>
          <?php endif; ?>
        </div>
        <?php echo $__env->make('institution._service_details', ['services' => $servicesCatalog], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
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
      <?php elseif($activeSection === 'medications'): ?>
        <section class="institution-native-table-card institution-native-table-card-summary institution-medication-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo institucional de medicamentos</h2>
              <p><?php echo e($catalogItems->count()); ?> visibles - <?php echo e($catalogItems->count()); ?> medicamentos institucionales - alimenta <?php echo e($visibleUnits); ?> farmacias externas</p>
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
                <?php $__empty_1 = true; $__currentLoopData = $catalogItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
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
                  ?>
                  <tr data-medication-row
                      data-cnis="<?php echo e(str($item->cnis)->lower()); ?>"
                      data-group="<?php echo e(str($itemGroup)->lower()); ?>"
                      data-name="<?php echo e(str($item->name)->lower()); ?>"
                      data-description="<?php echo e(str($itemDescription)->lower()); ?>"
                      data-status="<?php echo e($item->status); ?>">
                    <td><?php echo e($item->cnis ?? 'Sin CNIS'); ?></td>
                    <td><?php echo e($itemGroup); ?></td>
                    <td><strong><?php echo e(strtoupper($item->name)); ?></strong><span>Actualizado <?php echo e($item->updated_at?->format('d/m/Y')); ?></span></td>
                    <td><?php echo e(strtoupper($itemDescription)); ?></td>
                    <?php $__currentLoopData = [$mobileUnits, $basicUnits, $cessa]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $availability): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                      <?php
                        $isAvailable = in_array(strtolower((string) $availability), ['si', 'sÃ­', '1', 'true'], true);
                      ?>
                      <td><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['institution-medication-availability', 'is-yes' => $isAvailable]); ?>"><?php echo e($isAvailable ? 'Si' : 'No'); ?></span></td>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <td><span class="institution-native-status"><?php echo e($statusText($item->status)); ?></span></td>
                    <td><button type="button" class="institution-medication-edit"
                                data-edit-medication
                                data-update-url="<?php echo e(route('institution.medications.update', $item)); ?>"
                                data-cnis="<?php echo e($item->cnis); ?>"
                                data-group="<?php echo e($itemGroup); ?>"
                                data-name="<?php echo e($item->name); ?>"
                                data-description="<?php echo e($itemDescription); ?>"
                                data-mobile-units="<?php echo e($availabilityValue($mobileUnits)); ?>"
                                data-basic-units="<?php echo e($availabilityValue($basicUnits)); ?>"
                                data-cessa="<?php echo e($availabilityValue($cessa)); ?>"
                                data-status="<?php echo e($item->status); ?>">Editar</button></td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="9">No hay medicamentos institucionales registrados.</td></tr>
                <?php endif; ?>
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
          <form method="post" action="<?php echo e(route('institution.medications.store')); ?>"
                data-store-action="<?php echo e(route('institution.medications.store')); ?>"
                class="institution-medication-form" data-medication-form>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="_method" value="patch" data-medication-method disabled>
            <input type="hidden" name="form_mode" value="create" data-medication-form-mode>
            <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
            <label>Clave (CNIS)
              <input name="cnis" value="<?php echo e(old('cnis')); ?>" placeholder="Ej. 010.000.0104.00" required>
            </label>
            <label>Grupo
              <textarea name="therapeutic_group" rows="3" placeholder="Ej. GRUPO No. 1: ANALGESIA" required><?php echo e(old('therapeutic_group')); ?></textarea>
            </label>
            <label>Insumo
              <textarea name="name" rows="3" required><?php echo e(old('name')); ?></textarea>
            </label>
            <label>Descripcion
              <textarea name="description" rows="3" required><?php echo e(old('description')); ?></textarea>
            </label>
            <?php $__currentLoopData = [
              'mobile_units' => 'Unidades moviles',
              'basic_units' => 'Unidades nucleos basicos',
              'cessa' => 'CESSA',
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <label><?php echo e($label); ?>

                <select name="<?php echo e($field); ?>" required>
                  <option value="undefined" <?php if(old($field, 'undefined') === 'undefined'): echo 'selected'; endif; ?>>Sin definir</option>
                  <option value="yes" <?php if(old($field) === 'yes'): echo 'selected'; endif; ?>>Si</option>
                  <option value="no" <?php if(old($field) === 'no'): echo 'selected'; endif; ?>>No</option>
                </select>
              </label>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <label>Estatus
              <select name="status" required>
                <option value="active" <?php if(old('status', 'active') === 'active'): echo 'selected'; endif; ?>>Activo</option>
                <option value="inactive" <?php if(old('status') === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option>
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

            <?php if($errors->any() && old('cnis')): ?>
              showForm();
            <?php endif; ?>
          })();
        </script>
      <?php elseif($activeSection === 'specialties'): ?>
        <?php
          $specialtyItems = $servicesCatalog
              ->map(fn ($service) => (object) [
                'id' => $service->id,
                'name' => $service->specialty ?? $service->name,
                'status' => $service->status,
              ])
              ->unique(fn ($specialty) => strtolower($specialty->name))
              ->sortBy('name')
              ->values();
        ?>

        <section class="institution-native-table-card institution-native-table-card-summary">
          <div class="institution-native-table-heading">
            <div>
              <h2>Catalogo institucional de especialidades</h2>
              <p><?php echo e($specialtyItems->count()); ?> visibles - <?php echo e($specialtyItems->count()); ?> especialidades institucionales - disponibles para <?php echo e($visibleUnits); ?> unidades</p>
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
                <?php $__empty_1 = true; $__currentLoopData = $specialtyItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <tr data-specialty-row data-name="<?php echo e(str($specialty->name)->lower()); ?>" data-status="<?php echo e($specialty->status); ?>">
                    <td><?php echo e($loop->iteration); ?></td>
                    <td><strong><?php echo e($specialty->name); ?></strong><span>Catalogo institucional</span></td>
                    <td><span class="institution-native-status"><?php echo e($statusText($specialty->status)); ?></span></td>
                    <td>
                      <div class="institution-specialty-actions">
                        <button type="button" data-edit-specialty
                                data-update-url="<?php echo e(route('institution.specialties.update', $specialty->id)); ?>"
                                data-name="<?php echo e($specialty->name); ?>"
                                data-status="<?php echo e($specialty->status); ?>">Editar</button>
                        <form method="post" action="<?php echo e(route('institution.specialties.status', $specialty->id)); ?>">
                          <?php echo csrf_field(); ?>
                          <?php echo method_field('patch'); ?>
                          <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
                          <input type="hidden" name="status" value="<?php echo e($specialty->status === 'active' ? 'inactive' : 'active'); ?>">
                          <button type="submit" class="status-action">
                            <?php echo e($specialty->status === 'active' ? 'Inactivar' : 'Activar'); ?>

                          </button>
                        </form>
                        <form method="post" action="<?php echo e(route('institution.specialties.destroy', $specialty->id)); ?>"
                              onsubmit="return confirm('Â¿Deseas eliminar definitivamente esta especialidad? Esta accion no se puede deshacer.')">
                          <?php echo csrf_field(); ?>
                          <?php echo method_field('delete'); ?>
                          <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
                          <button type="submit" class="danger">Eliminar</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="4">No hay especialidades institucionales registradas.</td></tr>
                <?php endif; ?>
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
          <form method="post" action="<?php echo e(route('institution.specialties.store')); ?>"
                data-store-action="<?php echo e(route('institution.specialties.store')); ?>"
                class="institution-specialty-form" data-specialty-form>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="_method" value="patch" data-specialty-method disabled>
            <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
            <input type="hidden" name="form_context" value="specialty">
            <input type="hidden" name="form_mode" value="create" data-specialty-mode>
            <label>Nombre de especialidad
              <input name="name" value="<?php echo e(old('form_context') === 'specialty' ? old('name') : ''); ?>" required>
            </label>
            <label>Estatus
              <select name="status" required>
                <option value="active" <?php if(old('form_context') !== 'specialty' || old('status', 'active') === 'active'): echo 'selected'; endif; ?>>Activo</option>
                <option value="inactive" <?php if(old('form_context') === 'specialty' && old('status') === 'inactive'): echo 'selected'; endif; ?>>Inactivo</option>
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

            <?php if($errors->any() && old('form_context') === 'specialty'): ?>
              showForm();
            <?php endif; ?>
          })();
        </script>
      <?php elseif($activeSection === 'create-unit'): ?>
        <section class="institution-unit-create-card">
          <header><h2>Nueva unidad</h2></header>
          <form method="post" action="<?php echo e(route('institution.units.store')); ?>" class="institution-unit-create-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
            <input type="hidden" name="form_context" value="unit">
            <label class="is-half">CLUES
              <input name="clues" value="<?php echo e(old('form_context') === 'unit' ? old('clues') : ''); ?>" required>
            </label>
            <label class="is-wide">Nombre de la unidad
              <input name="name" value="<?php echo e(old('form_context') === 'unit' ? old('name') : ''); ?>" required>
            </label>
            <label>Entidad<input name="entity" value="<?php echo e(old('form_context') === 'unit' ? old('entity') : ''); ?>" required></label>
            <label>Municipio<input name="municipality" value="<?php echo e(old('form_context') === 'unit' ? old('municipality') : ''); ?>" required></label>
            <label>Nivel de atencion<input name="care_level" value="<?php echo e(old('form_context') === 'unit' ? old('care_level') : ''); ?>" required></label>
            <label>Tipologia<input name="typology" value="<?php echo e(old('form_context') === 'unit' ? old('typology') : ''); ?>" required></label>
            <label class="is-wide">Domicilio
              <textarea name="address" rows="4" required><?php echo e(old('form_context') === 'unit' ? old('address') : ''); ?></textarea>
            </label>
            <label>Latitud<input name="latitude" type="number" step="any" value="<?php echo e(old('form_context') === 'unit' ? old('latitude') : ''); ?>"></label>
            <label>Longitud<input name="longitude" type="number" step="any" value="<?php echo e(old('form_context') === 'unit' ? old('longitude') : ''); ?>"></label>
            <label>Partida<input name="partida" value="<?php echo e(old('form_context') === 'unit' ? old('partida') : ''); ?>"></label>
            <label>Subpartida<input name="subpartida" value="<?php echo e(old('form_context') === 'unit' ? old('subpartida') : ''); ?>"></label>
            <label>Camas<input name="beds" type="number" min="0" value="<?php echo e(old('form_context') === 'unit' ? old('beds', 0) : 0); ?>" required></label>
            <label>Usuario de unidad<input name="unit_username" value="<?php echo e(old('form_context') === 'unit' ? old('unit_username') : ''); ?>" placeholder="Ej. unidad.hospital" required></label>
            <label>Contrasena de unidad<input name="unit_password" type="password" placeholder="Captura la contrasena" required></label>
            <button type="submit">Guardar unidad</button>
          </form>
        </section>

      <?php else: ?>
        <section class="institution-unit-create-card institution-service-create-card">
          <header><h2>Nuevo servicio</h2></header>
          <form method="post" action="<?php echo e(route('institution.services.store')); ?>" class="institution-unit-create-form institution-service-create-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
            <input type="hidden" name="form_context" value="service">
            <label class="is-wide">Unidades con servicio contratado
              <select name="unit_ids[]" multiple size="1" aria-label="Seleccionar hospitales">
                <?php $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($unit->id); ?>" <?php if(in_array($unit->id, old('unit_ids', []))): echo 'selected'; endif; ?>><?php echo e($unit->name); ?><?php echo e($unit->clues ? ' - '.$unit->clues : ''); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
              <small>Usa Ctrl para seleccionar mÃ¡s de un hospital.</small>
            </label>
            <div class="institution-service-create-divider"><strong>Datos del Servicio</strong></div>
            <label>Categoria
              <select name="category" required>
                <?php $__currentLoopData = ['Asistenciales', 'Criticos', 'Diagnosticos', 'Farmaceuticos', 'Quirurgicos']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($category); ?>" <?php if(old('category') === $category): echo 'selected'; endif; ?>><?php echo e($category); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </select>
            </label>
            <label>Especialidad
              <select name="specialty" required>
                <?php $__currentLoopData = $servicesCatalog->pluck('specialty')->filter()->unique()->sort(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $specialty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <option value="<?php echo e($specialty); ?>" <?php if(old('specialty') === $specialty): echo 'selected'; endif; ?>><?php echo e($specialty); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <option value="Servicio general" <?php if(old('specialty') === 'Servicio general'): echo 'selected'; endif; ?>>Servicio general</option>
              </select>
            </label>
            <label>Servicio<input name="name" value="<?php echo e(old('form_context') === 'service' ? old('name') : ''); ?>" placeholder="Captura el nombre del servicio" required></label>
            <label>Inicio<input name="starts_at" type="date" value="<?php echo e(old('form_context') === 'service' ? old('starts_at') : ''); ?>" required></label>
            <label>Fin<input name="ends_at" type="date" value="<?php echo e(old('form_context') === 'service' ? old('ends_at') : ''); ?>" required></label>
            <label>SLA<input name="sla" value="<?php echo e(old('form_context') === 'service' ? old('sla', 'Horario habil') : 'Horario habil'); ?>" required></label>
            <button type="submit">Guardar servicio</button>
          </form>
        </section>

      <?php endif; ?>

    </section>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Institucion'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam-laravel\resources\views/institution/dashboard.blade.php ENDPATH**/ ?>