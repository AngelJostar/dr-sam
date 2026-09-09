

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
  $unitPasswordText = function ($unit): string {
    $storedPassword = data_get($unit->metadata, 'demo_password');

    if (filled($storedPassword)) {
      return (string) $storedPassword;
    }

    return data_get($unit->metadata, 'review_scope') ? 'Demo2026' : 'No registrada';
  };
  $institutionLabel = $institution->name;
  $visibleUnits = $institution->medicalUnits->count();
  $institutionUnitServiceOrder = [
      'consulta-externa',
      'hemodinamia',
      'laboratorio',
      'nutricion-parenteral',
      'quimioterapias',
      'central-de-mezclas',
      'mantenimiento-equipo-medico',
      'osteosintesis',
  ];
  $institutionUnitServiceOrderIndex = array_flip($institutionUnitServiceOrder);
  $institutionUnitServiceContracts = $institution->medicalUnits
      ->mapWithKeys(function ($unit) use ($institutionUnitServiceOrderIndex) {
          $contracts = $unit->contractedServices
              ->filter(function ($contract) use ($institutionUnitServiceOrderIndex) {
                  $externalId = $contract->service?->external_id;

                  return $contract->service
                      && $contract->status === 'active'
                      && is_string($externalId)
                      && array_key_exists($externalId, $institutionUnitServiceOrderIndex);
              })
              ->sortBy(fn ($contract) => $institutionUnitServiceOrderIndex[$contract->service?->external_id] ?? PHP_INT_MAX)
              ->values();

          return [$unit->id => $contracts];
      });
  $activeSection = in_array(request('section'), ['subunits', 'pharmacies', 'services', 'medications', 'specialties', 'create-unit', 'create-service', 'edit-unit'], true) ? request('section') : 'units';
  $activeSectionTitle = match ($activeSection) {
    'pharmacies' => 'Catalogo de farmacias institucionales',
    'subunits' => 'Catalogo de subunidades',
    'services' => 'Catalogo de servicios',
    'medications' => 'Catalogo de medicamentos',
    'specialties' => 'Catalogo institucional de especialidades',
    'create-unit' => 'Alta de unidad',
    'create-service' => 'Alta de servicios',
    default => 'Catalogo de unidades',
  };
?>

<?php $__env->startSection('content'); ?>
  <div class="institution-native-screen">
    <header class="institution-native-topbar" aria-label="Barra superior institucional">
      <strong>PORTAL INSTITUCIONAL</strong>
      <span><?php echo e(strtoupper($institutionLabel)); ?></span>
      <div class="institution-native-user">
        <button type="button" aria-label="Notificaciones">
          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9Z"/><path d="M10 21h4"/></svg>
        </button>
        <div>
          <strong>Usuario activo</strong>
          <small><?php echo e($institution->state ?? data_get($institution->metadata, 'state', 'Estado de Mexico')); ?></small>
        </div>
        <form method="post" action="<?php echo e(route('logout')); ?>" class="institution-native-logout">
          <?php echo csrf_field(); ?>
          <button type="submit">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M15 4h4a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-4"/></svg>
            Cerrar sesion
          </button>
        </form>
      </div>
    </header>

    <aside class="institution-native-sidebar" aria-label="Navegacion institucional">
      <nav class="institution-native-menu">
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => in_array($activeSection, ['units', 'create-unit', 'edit-unit'], true)]); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id])); ?>">
          <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M5 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17"/><path d="M16 8h3v13"/><path d="M8 7h4M8 11h4M8 15h4M9 21v-3h3v3"/></svg></span>
          Catalogo de Unidades
          <i aria-hidden="true">›</i>
        </a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'subunits']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'subunits'])); ?>">
          <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 21V8h6v13M14 21V3h6v18"/><path d="M7 11h1M7 15h1M17 7h1M17 11h1M17 15h1"/></svg></span>
          Catalogo de Subunidades
          <i aria-hidden="true">›</i>
        </a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'pharmacies']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'pharmacies'])); ?>">
          <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 6h16v15H4z"/><path d="M9 3h6v3H9z"/><path d="M12 10v7M8.5 13.5h7"/></svg></span>
          Catalogo de Farmacias Institucionales
          <i aria-hidden="true">›</i>
        </a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'services']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'services'])); ?>">
          <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/><path d="M7 7v10M17 7v10"/></svg></span>
          Catalogo de Servicios
          <i aria-hidden="true">›</i>
        </a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'medications']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'medications'])); ?>">
          <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m7 16 9-9a3 3 0 0 1 4 4l-9 9a3 3 0 0 1-4-4Z"/><path d="m12 11 4 4"/></svg></span>
          Catalogo de Medicamentos
          <i aria-hidden="true">›</i>
        </a>
        <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'specialties']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'specialties'])); ?>">
          <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2L12 18.2l-5.6 3 1.1-6.2L3 10.6l6.2-.9Z"/></svg></span>
          Catalogo de Especialidades
          <i aria-hidden="true">›</i>
        </a>
        <span class="institution-native-menu-divider" aria-hidden="true"></span>
        <a href="<?php echo e(route('dashboard')); ?>">
          <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 14a8 8 0 0 1 16 0"/><path d="M6 14v4a2 2 0 0 0 2 2h1v-8H8a2 2 0 0 0-2 2ZM18 14v4a2 2 0 0 1-2 2h-1v-8h1a2 2 0 0 1 2 2Z"/></svg></span>
          Centro de ayuda
          <i aria-hidden="true">›</i>
        </a>
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
        <div class="institution-native-title">
          <span aria-hidden="true">
            <?php if($activeSection === 'medications'): ?>
              <svg viewBox="0 0 24 24"><path d="m7 16 9-9a3 3 0 0 1 4 4l-9 9a3 3 0 0 1-4-4Z"/><path d="m12 11 4 4"/></svg>
            <?php else: ?>
              <svg viewBox="0 0 24 24"><path d="M5 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17"/><path d="M16 8h3v13"/><path d="M8 7h4M8 11h4M8 15h4M9 21v-3h3v3"/></svg>
            <?php endif; ?>
          </span>
          <div>
            <h1><?php echo e($activeSectionTitle); ?></h1>
          </div>
        </div>
      </header>

      <?php if(in_array($activeSection, ['units', 'create-unit', 'edit-unit'], true)): ?>
        <nav class="institution-native-tabs" aria-label="Acciones de unidades">
          <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => in_array($activeSection, ['units', 'edit-unit'], true)]); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id])); ?>">Ver unidades</a>
          <a class="<?php echo \Illuminate\Support\Arr::toCssClasses(['is-active' => $activeSection === 'create-unit']); ?>" href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'create-unit'])); ?>">Alta de unidad</a>
        </nav>
      <?php endif; ?>

      <?php if($activeSection === 'medications'): ?>
        <nav class="institution-native-tabs institution-medication-tabs" aria-label="Acciones de medicamentos">
          <button type="button" class="is-active" data-show-medication-list>Ver medicamentos</button>
          <button type="button" data-show-medication-form>Alta de medicamento</button>
        </nav>
      <?php endif; ?>

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
      <section id="units" class="institution-native-table-card">
        <div class="institution-native-table-heading">
          <span aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="M5 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17"/><path d="M16 8h3v13"/><path d="M8 7h4M8 11h4M8 15h4M9 21v-3h3v3"/></svg>
          </span>
          <div>
            <h2>Unidades</h2>
            <p><span data-visible-unit-count><?php echo e($visibleUnits); ?></span> unidades visibles</p>
          </div>
        </div>

        <div class="institution-native-table-scroll">
          <table class="institution-native-table institution-units-table">
            <thead>
              <tr>
                <th>Unidad</th>
                <th>CLUES</th>
                <th>Usuario</th>
                <th>Contraseña</th>
                <th>Ubicacion</th>
                <th>Nivel / Tipo</th>
                <th>Servicios activos</th>
                <th>Estatus</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php $__empty_1 = true; $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                  $unitActiveServiceContracts = $institutionUnitServiceContracts->get($unit->id, collect());
                  $unitActiveServiceCount = $unitActiveServiceContracts->count();
                  $unitPassword = $unitPasswordText($unit);
                ?>
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
                  <td><strong><?php echo e($unit->unit_username ?? 'unidad-'.$unit->id); ?></strong></td>
                  <td>
                    <div class="institution-unit-password-cell">
                      <span><?php echo e($unitPassword); ?></span>
                      <button type="button"
                              class="institution-unit-password-edit icon-button"
                              data-unit-password-open="<?php echo e($unit->id); ?>"
                              aria-label="Editar contraseña de <?php echo e($unit->name); ?>"
                              title="Editar contraseña">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/></svg>
                      </button>
                    </div>
                  </td>
                  <td>
                    <?php echo e($unit->city ?? $unit->municipality ?? 'Sin ciudad'); ?>

                    <span><?php echo e($unit->state ?? $unit->entity ?? 'Sin estado'); ?></span>
                  </td>
                  <td>
                    <strong><?php echo e($unit->care_level ?? 'Tercer Nivel'); ?></strong>
                    <span><?php echo e($unit->type ?? $unit->typology ?? 'Hospital general'); ?></span>
                  </td>
                  <td>
                    <button type="button"
                            class="institution-services-count-button"
                            data-unit-services-open="<?php echo e($unit->id); ?>"
                            aria-label="Ver <?php echo e($unitActiveServiceCount); ?> servicios activos de <?php echo e($unit->name); ?>">
                      <?php echo e($unitActiveServiceCount); ?>

                    </button>
                  </td>
                  <td><span class="institution-native-status"><?php echo e($statusText($unit->status)); ?></span></td>
                  <td>
                    <div class="institution-native-actions">
                      <a href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'edit-unit', 'unit' => $unit->id])); ?>">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/></svg>
                        Editar
                      </a>
                      <form method="post" action="<?php echo e(route('institution.units.status', $unit)); ?>" onsubmit="return confirm('&iquest;<?php echo e($unit->status === 'inactive' ? 'Reactivar' : 'Desactivar'); ?> esta unidad?')">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="status" value="<?php echo e($unit->status === 'inactive' ? 'active' : 'inactive'); ?>">
                        <button type="submit" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['danger' => $unit->status !== 'inactive']); ?>">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                          <?php echo e($unit->status === 'inactive' ? 'Reactivar' : 'Eliminar'); ?>

                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                  <td colspan="9">No hay unidades registradas.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <footer class="institution-native-table-footer">
          <span>Mostrando 1 a <b data-visible-unit-count><?php echo e($visibleUnits); ?></b> de <b data-visible-unit-count><?php echo e($visibleUnits); ?></b> unidades</span>
          <nav aria-label="Paginacion de unidades">
            <button type="button" aria-label="Pagina anterior">‹</button>
            <strong>1</strong>
            <button type="button" aria-label="Pagina siguiente">›</button>
          </nav>
          <label>
            <select aria-label="Unidades por pagina">
              <option>10 por pagina</option>
            </select>
          </label>
        </footer>
      </section>

      <?php $__currentLoopData = $institution->medicalUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
          $unitPassword = $unitPasswordText($unit);
          $reopenPasswordDialog = old('form_context') === 'unit-password' && (int) old('unit_id') === $unit->id;
        ?>
        <dialog class="institution-unit-password-dialog"
                data-unit-password-dialog="<?php echo e($unit->id); ?>"
                <?php if($reopenPasswordDialog): ?> data-open-on-load="true" <?php endif; ?>
                aria-labelledby="unit-password-title-<?php echo e($unit->id); ?>">
          <header>
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/></svg>
            </span>
            <div>
              <h2 id="unit-password-title-<?php echo e($unit->id); ?>">Editar contraseña</h2>
              <p><?php echo e($unit->name); ?></p>
            </div>
            <button type="button" class="icon-button" data-unit-password-close aria-label="Cerrar">
              <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>
            </button>
          </header>
          <form method="post" action="<?php echo e(route('institution.units.password', $unit)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
            <input type="hidden" name="form_context" value="unit-password">
            <input type="hidden" name="unit_id" value="<?php echo e($unit->id); ?>">
            <label for="unit-password-input-<?php echo e($unit->id); ?>">Contraseña de unidad</label>
            <div class="institution-unit-password-input">
              <svg viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
              <input id="unit-password-input-<?php echo e($unit->id); ?>"
                     name="unit_password"
                     type="text"
                     minlength="6"
                     maxlength="255"
                     value="<?php echo e($reopenPasswordDialog ? old('unit_password', $unitPassword) : $unitPassword); ?>"
                     autocomplete="new-password"
                     required>
            </div>
            <footer>
              <button type="button" class="is-secondary" data-unit-password-close>Cancelar</button>
              <button type="submit" class="is-primary">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                Guardar contraseña
              </button>
            </footer>
          </form>
        </dialog>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

      <?php echo $__env->make('institution._unit_services_mirror', ['unitServiceContractsByUnit' => $institutionUnitServiceContracts], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <script>
        (() => {
          const closeUnitServicesDialog = (dialog) => {
            if (! dialog) return;
            if (typeof dialog.close === 'function') {
              dialog.close();
              return;
            }

            dialog.removeAttribute('open');
          };

          const closeUnitServicesDialogFromEvent = (event) => {
            const target = event.target instanceof Element ? event.target : event.target?.parentElement;
            const closeButton = target?.closest('[data-unit-services-close]');
            if (! closeButton) return;

            event.preventDefault();
            event.stopPropagation();
            closeUnitServicesDialog(closeButton.closest('[data-unit-services-dialog]'));
          };

          document.querySelectorAll('[data-unit-services-open]').forEach((button) => {
            button.addEventListener('click', () => {
              const dialog = document.querySelector(`[data-unit-services-dialog="${button.dataset.unitServicesOpen}"]`);
              dialog?.showModal();
            });
          });

          document.addEventListener('click', closeUnitServicesDialogFromEvent, true);

          document.querySelectorAll('[data-unit-services-dialog]').forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
              if (event.target === dialog) closeUnitServicesDialog(dialog);
            });
          });

          document.querySelectorAll('[data-unit-password-open]').forEach((button) => {
            button.addEventListener('click', () => {
              const dialog = document.querySelector(`[data-unit-password-dialog="${button.dataset.unitPasswordOpen}"]`);
              dialog?.showModal();
              dialog?.querySelector('input[name="unit_password"]')?.focus();
            });
          });

          document.querySelectorAll('[data-unit-password-dialog]').forEach((dialog) => {
            dialog.querySelectorAll('[data-unit-password-close]').forEach((button) => {
              button.addEventListener('click', () => dialog.close());
            });
            dialog.addEventListener('click', (event) => {
              if (event.target === dialog) dialog.close();
            });
            if (dialog.dataset.openOnLoad === 'true') dialog.showModal();
          });

          document.querySelectorAll('[data-institution-unit-service-dashboard]').forEach((root) => {
            const carousel = root.querySelector('[data-unit-service-carousel]');
            const tabs = [...root.querySelectorAll('[data-unit-service-tab]')];
            const panels = [...root.querySelectorAll('[data-unit-service-panel]')];
            const setActive = (id) => {
              tabs.forEach((tab) => tab.classList.toggle('is-active', tab.dataset.unitServiceTab === id));
              panels.forEach((panel) => panel.hidden = panel.dataset.unitServicePanel !== id);
            };

            tabs.forEach((tab) => tab.addEventListener('click', () => setActive(tab.dataset.unitServiceTab)));
            root.querySelector('[data-unit-service-carousel-prev]')?.addEventListener('click', () => carousel?.scrollBy({ left: -260, behavior: 'smooth' }));
            root.querySelector('[data-unit-service-carousel-next]')?.addEventListener('click', () => carousel?.scrollBy({ left: 260, behavior: 'smooth' }));
          });
        })();
      </script>
      <?php elseif($activeSection === 'subunits'): ?>
        <?php echo $__env->make('institution._subunits', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
      <?php elseif($activeSection === 'pharmacies'): ?>
        <?php
          $pharmacyRows = $institution->medicalUnits;
          $pharmacyCount = $pharmacyRows->count();
          $pharmacyColumns = [
            'pharmacy' => 'Farmacia',
            'key' => 'Clave',
            'unit' => 'Unidad relacionada',
            'responsible' => 'Responsable',
            'location' => 'Ubicacion',
            'schedule' => 'Horario',
            'status' => 'Estatus',
          ];
          $pharmacyStatusText = fn (?string $value) => [
            'active' => 'Activa',
            'inactive' => 'Inactiva',
            'maintenance' => 'Mantenimiento',
            'suspended' => 'Suspendida',
          ][$value ?? ''] ?? $statusText($value);
        ?>
        <section class="institution-native-table-card institution-native-table-card-wide institution-native-pharmacy-card">
          <div class="institution-native-table-heading">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M5 21V4a1 1 0 0 1 1-1h9a1 1 0 0 1 1 1v17"/><path d="M16 8h3v13"/><path d="M8 7h4M8 11h4M8 15h4M9 21v-3h3v3"/></svg>
            </span>
            <div>
              <h2>Farmacias</h2>
              <p><?php echo e($pharmacyCount); ?> <?php echo e($pharmacyCount === 1 ? 'farmacia registrada' : 'farmacias registradas'); ?></p>
            </div>
          </div>
          <div class="institution-native-table-scroll">
            <table class="institution-native-table institution-native-pharmacy-table">
              <thead>
                <tr>
                  <?php $__currentLoopData = $pharmacyColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $columnKey => $columnLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <th>
                      <div class="institution-table-head-control">
                        <span><?php echo e($columnLabel); ?></span>
                        <span class="institution-table-head-buttons">
                          <button type="button" data-pharmacy-sort="<?php echo e($columnKey); ?>" aria-label="Ordenar <?php echo e(strtolower($columnLabel)); ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4v16"/><path d="M4 8l4-4 4 4"/><path d="M16 20V4"/><path d="m12 16 4 4 4-4"/></svg>
                          </button>
                          <button type="button" data-pharmacy-filter-toggle aria-label="Filtrar <?php echo e(strtolower($columnLabel)); ?>">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16l-6 7v5l-4 2v-7Z"/></svg>
                          </button>
                        </span>
                      </div>
                    </th>
                  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                  <th>Acciones</th>
                </tr>
                <tr class="institution-table-filter-row" data-pharmacy-filter-row hidden>
                  <th><input type="search" data-pharmacy-filter="pharmacy" placeholder="Filtrar farmacia"></th>
                  <th><input type="search" data-pharmacy-filter="key" placeholder="Filtrar clave"></th>
                  <th><input type="search" data-pharmacy-filter="unit" placeholder="Filtrar unidad"></th>
                  <th><input type="search" data-pharmacy-filter="responsible" placeholder="Filtrar responsable"></th>
                  <th><input type="search" data-pharmacy-filter="location" placeholder="Filtrar ubicacion"></th>
                  <th><input type="search" data-pharmacy-filter="schedule" placeholder="Filtrar horario"></th>
                  <th>
                    <select data-pharmacy-filter="status">
                      <option value="">Todos</option>
                      <option value="activa">Activa</option>
                      <option value="inactiva">Inactiva</option>
                      <option value="mantenimiento">Mantenimiento</option>
                      <option value="suspendida">Suspendida</option>
                    </select>
                  </th>
                  <th><button type="button" data-pharmacy-filter-clear>Limpiar</button></th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $pharmacyRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $pharmacyName = data_get($unit->metadata, 'pharmacy_name', 'Farmacia institucional '.$unit->name);
                    $pharmacyKey = data_get($unit->metadata, 'pharmacy_key', 'FAR-'.str_pad((string) $unit->id, 3, '0', STR_PAD_LEFT));
                    $pharmacyResponsible = data_get($unit->metadata, 'pharmacy_responsible', 'Responsable por asignar');
                    $pharmacySchedule = (string) data_get($unit->metadata, 'pharmacy_schedule', 'Lun - Vie 08:00 - 20:00|Sab 08:00 - 14:00');
                    $pharmacyStatus = data_get($unit->metadata, 'pharmacy_status', 'active');
                    $pharmacyStatusLabel = $pharmacyStatusText($pharmacyStatus);
                    $pharmacyLocation = collect([
                      $unit->city ?? $unit->municipality ?? 'Sin ciudad',
                      $unit->state ?? $unit->entity ?? 'Sin estado',
                    ])->filter()->implode(', ');
                  ?>
                  <tr data-pharmacy-row
                      data-pharmacy="<?php echo e(str($pharmacyName.' '.$institutionLabel)->lower()); ?>"
                      data-key="<?php echo e(str($pharmacyKey)->lower()); ?>"
                      data-unit="<?php echo e(str($unit->name)->lower()); ?>"
                      data-responsible="<?php echo e(str($pharmacyResponsible)->lower()); ?>"
                      data-location="<?php echo e(str($pharmacyLocation)->lower()); ?>"
                      data-schedule="<?php echo e(str(str_replace('|', ' ', $pharmacySchedule))->lower()); ?>"
                      data-status="<?php echo e(str($pharmacyStatusLabel)->lower()); ?>">
                    <td>
                      <strong><?php echo e($pharmacyName); ?></strong>
                      <span><?php echo e($institutionLabel); ?></span>
                    </td>
                    <td><?php echo e($pharmacyKey); ?></td>
                    <td><?php echo e($unit->name); ?></td>
                    <td><?php echo e($pharmacyResponsible); ?></td>
                    <td><?php echo e($pharmacyLocation); ?></td>
                    <td>
                      <?php $__currentLoopData = explode('|', $pharmacySchedule); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $scheduleLine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <span><?php echo e(trim($scheduleLine)); ?></span>
                      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </td>
                    <td><span class="institution-native-status"><?php echo e($pharmacyStatusLabel); ?></span></td>
                    <td>
                      <div class="institution-native-actions">
                        <a href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id, 'section' => 'edit-unit', 'unit' => $unit->id])); ?>">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/></svg>
                          Editar
                        </a>
                        <button type="button" class="danger" aria-label="Eliminar farmacia <?php echo e(data_get($unit->metadata, 'pharmacy_name', 'Farmacia institucional '.$unit->name)); ?>">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                          Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="8">No hay farmacias institucionales registradas.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <footer class="institution-native-table-footer institution-native-table-footer-all-results">
            <span data-pharmacy-footer-summary>Mostrando <?php echo e($pharmacyCount); ?> de <?php echo e($pharmacyCount); ?> <?php echo e($pharmacyCount === 1 ? 'farmacia' : 'farmacias'); ?></span>
          </footer>
        </section>

        <script>
          (() => {
            const table = document.querySelector('.institution-native-pharmacy-table');
            if (! table) return;

            const tbody = table.querySelector('tbody');
            const rows = [...table.querySelectorAll('[data-pharmacy-row]')];
            const filterRow = table.querySelector('[data-pharmacy-filter-row]');
            const filterControls = [...table.querySelectorAll('[data-pharmacy-filter]')];
            const filterButtons = [...table.querySelectorAll('[data-pharmacy-filter-toggle]')];
            const sortButtons = [...table.querySelectorAll('[data-pharmacy-sort]')];
            const clearButton = table.querySelector('[data-pharmacy-filter-clear]');
            const footerSummary = document.querySelector('[data-pharmacy-footer-summary]');
            const normalize = (value) => (value ?? '').toLocaleLowerCase('es').normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            const sortState = { key: null, direction: 'asc' };
            const visibleRows = () => rows.filter((row) => ! row.hidden);
            const updateFooter = () => {
              const visible = visibleRows().length;
              footerSummary.textContent = `Mostrando ${visible} de ${rows.length} ${rows.length === 1 ? 'farmacia' : 'farmacias'}`;
            };
            const applyFilters = () => {
              rows.forEach((row) => {
                const matches = filterControls.every((control) => {
                  const value = control.value.trim();
                  return value === '' || normalize(row.dataset[control.dataset.pharmacyFilter]).includes(normalize(value));
                });
                row.hidden = ! matches;
              });
              updateFooter();
            };
            const sortRows = (key) => {
              sortState.direction = sortState.key === key && sortState.direction === 'asc' ? 'desc' : 'asc';
              sortState.key = key;

              rows.sort((first, second) => {
                const firstValue = normalize(first.dataset[key]);
                const secondValue = normalize(second.dataset[key]);
                return sortState.direction === 'asc'
                  ? firstValue.localeCompare(secondValue, 'es', { numeric: true })
                  : secondValue.localeCompare(firstValue, 'es', { numeric: true });
              }).forEach((row) => tbody.appendChild(row));

              sortButtons.forEach((button) => {
                const active = button.dataset.pharmacySort === key;
                button.classList.toggle('is-active', active);
                button.dataset.direction = active ? sortState.direction : '';
              });
            };

            filterButtons.forEach((button) => button.addEventListener('click', () => {
              filterRow.hidden = ! filterRow.hidden;
              if (! filterRow.hidden) {
                filterRow.querySelector('input, select')?.focus();
              }
            }));
            filterControls.forEach((control) => control.addEventListener(control.tagName === 'SELECT' ? 'change' : 'input', applyFilters));
            sortButtons.forEach((button) => button.addEventListener('click', () => sortRows(button.dataset.pharmacySort)));
            clearButton?.addEventListener('click', () => {
              filterControls.forEach((control) => control.value = '');
              applyFilters();
            });
            updateFooter();
          })();
        </script>
      <?php elseif($activeSection === 'services'): ?>
        <div class="institution-service-catalog" data-service-catalog>
          <?php if($servicesCatalog->isNotEmpty()): ?>
            <section class="institution-service-carousel-card">
              <button type="button" class="institution-service-carousel-arrow" data-service-carousel-prev aria-label="Servicio anterior">&lsaquo;</button>
              <div class="institution-service-carousel" data-service-carousel aria-label="Servicios institucionales">
                <button type="button" class="institution-service-carousel-new" data-service-create-open>
                  <span class="institution-service-carousel-icon is-new" aria-hidden="true">+</span>
                  <strong>Nuevo servicio</strong>
                  <small>Registrar servicio</small>
                </button>
                <?php $__currentLoopData = $servicesCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                  <?php
                    $contracts = $service->contractedServices;
                    $activeContracts = $contracts->where('status', 'active');
                    $serviceIcon = match ($service->external_id) {
                      'consulta-externa' => 'consultation',
                      'hemodinamia' => 'heart',
                      'laboratorio' => 'microscope',
                      'nutricion-parenteral' => 'nutrition',
                      'quimioterapias' => 'infusion',
                      'central-de-mezclas' => 'mixtures',
                      'mantenimiento-equipo-medico' => 'maintenance',
                      'osteosintesis' => 'osteosynthesis',
                      default => 'service',
                    };
                  ?>
                  <button type="button"
                          class="<?php echo \Illuminate\Support\Arr::toCssClasses(['institution-service-carousel-button', 'is-active' => $loop->first]); ?>"
                          data-service-carousel-button="<?php echo e($service->id); ?>">
                    <span class="institution-service-carousel-icon is-<?php echo e($serviceIcon); ?>" aria-hidden="true">
                      <?php switch($serviceIcon):
                        case ('consultation'): ?>
                          <svg viewBox="0 0 24 24"><path d="M6 4v5a4 4 0 0 0 8 0V4"/><path d="M10 13v2a5 5 0 0 0 10 0v-2"/><circle cx="20" cy="10" r="2"/><path d="M4 4h4M12 4h4"/></svg>
                          <?php break; ?>
                        <?php case ('heart'): ?>
                          <svg viewBox="0 0 24 24"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/><path d="M3 12h4l2-4 4 8 2-4h6"/></svg>
                          <?php break; ?>
                        <?php case ('microscope'): ?>
                          <svg viewBox="0 0 24 24"><path d="M6 18h8"/><path d="M3 22h18"/><path d="M14 22a7 7 0 0 0 7-7h-4a3 3 0 0 1-3 3"/><path d="m9 14 6-6"/><path d="m7 12 4 4"/><path d="M10 5 7 8l6 6 3-3Z"/></svg>
                          <?php break; ?>
                        <?php case ('nutrition'): ?>
                          <svg viewBox="0 0 24 24"><path d="M9 2h6"/><path d="M10 2v7l-4 8a3.5 3.5 0 0 0 3.1 5h5.8a3.5 3.5 0 0 0 3.1-5l-4-8V2"/><path d="M8 15h8"/></svg>
                          <?php break; ?>
                        <?php case ('infusion'): ?>
                          <svg viewBox="0 0 24 24"><path d="M9 2h6v9a3 3 0 0 1-6 0V2Z"/><path d="M12 14v8"/><path d="M8 22h8"/><path d="M9 6h6"/></svg>
                          <?php break; ?>
                        <?php case ('mixtures'): ?>
                          <svg viewBox="0 0 24 24"><path d="M8 3h8"/><path d="M10 3v6l-4.5 8.5A3 3 0 0 0 8.2 22h7.6a3 3 0 0 0 2.7-4.5L14 9V3"/><path d="M8 16h8"/></svg>
                          <?php break; ?>
                        <?php case ('maintenance'): ?>
                          <svg viewBox="0 0 24 24"><path d="m14.7 6.3 3-3a4 4 0 0 1-5 5l-7 7a2 2 0 0 0 2.8 2.8l7-7a4 4 0 0 1 5-5l-3 3"/><path d="m3 21 5-5"/></svg>
                          <?php break; ?>
                        <?php case ('osteosynthesis'): ?>
                          <svg viewBox="0 0 24 24"><path d="M8.5 8.5 15.5 15.5"/><path d="M6.5 11.5a3 3 0 1 1 3-5l8 8a3 3 0 1 1-5 3Z"/></svg>
                          <?php break; ?>
                        <?php default: ?>
                          <svg viewBox="0 0 24 24"><path d="M8 6h13"/><path d="M8 12h13"/><path d="M8 18h13"/><path d="M3 6h.01M3 12h.01M3 18h.01"/></svg>
                      <?php endswitch; ?>
                    </span>
                    <strong><?php echo e($service->name); ?></strong>
                    <small><?php echo e($activeContracts->count()); ?> <?php echo e($activeContracts->count() === 1 ? 'hospital' : 'hospitales'); ?></small>
                  </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
              </div>
              <button type="button" class="institution-service-carousel-arrow" data-service-carousel-next aria-label="Servicio siguiente">&rsaquo;</button>
            </section>

            <dialog class="institution-service-create-dialog" data-service-create-dialog <?php if($errors->any() && old('form_context') === 'service'): ?> data-open-on-load="true" <?php endif; ?>>
              <header>
                <div>
                  <h2>Nuevo servicio</h2>
                  <p>Captura los datos y las unidades donde estara habilitado.</p>
                </div>
                <button type="button" data-service-create-close aria-label="Cerrar">&times;</button>
              </header>
              <?php echo $__env->make('institution._service_create_form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </dialog>

            <div class="institution-service-panels">
              <?php $__currentLoopData = $servicesCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $service): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                  $contracts = $service->contractedServices;
                  $activeContracts = $contracts->where('status', 'active');
                  $latestEnd = $contracts->pluck('ends_at')->filter()->sortDesc()->first();
                ?>
                <article class="institution-service-panel" data-service-panel="<?php echo e($service->id); ?>" <?php if(! $loop->first): ?> hidden <?php endif; ?>>
                  <header class="institution-service-panel-header">
                    <div class="institution-service-copy">
                      <h2><?php echo e($service->name); ?></h2>
                      <p>
                        <?php echo e($service->category ?? 'Sin categoria'); ?> &middot;
                        <?php echo e($service->specialty ?? 'Servicio general'); ?> &middot;
                        <?php echo e($latestEnd ? 'Vigencia '.$latestEnd->format('d/m/Y') : 'Sin vigencia'); ?>

                      </p>
                      <div>
                        <span class="institution-native-status"><?php echo e($statusText($service->status)); ?></span>
                        <small><?php echo e($activeContracts->count()); ?> <?php echo e($activeContracts->count() === 1 ? 'hospital habilitado' : 'hospitales habilitados'); ?></small>
                      </div>
                    </div>
                    <div class="institution-service-actions">
                      <button type="button" data-service-units-toggle="<?php echo e($service->id); ?>">+&nbsp; Habilitar Servicio a Unidades</button>
                      <button type="button" data-service-edit="<?php echo e($service->id); ?>">Editar</button>
                      <button type="button" class="is-info" data-service-contract="<?php echo e($service->id); ?>">i&nbsp; Informacion del contrato</button>
                    </div>
                  </header>

                  <div class="institution-native-table-scroll">
                    <table class="institution-native-table institution-service-hospitals-table">
                      <thead>
                        <tr>
                          <th>Hospital</th>
                          <th>CLUES</th>
                          <th>Ubicacion</th>
                          <th>Vigencia</th>
                          <th>Contrato</th>
                          <th>Estatus</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $activeContracts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contract): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                          <?php
                            $unit = $contract->medicalUnit;
                            $unitLocation = collect([
                              $unit?->city ?? $unit?->municipality,
                              $unit?->state ?? $unit?->entity,
                            ])->filter()->implode(', ');
                          ?>
                          <tr>
                            <td>
                              <strong><?php echo e($unit?->name ?? 'Sin unidad'); ?></strong>
                              <small><?php echo e($unit?->type ?? $unit?->typology ?? 'Unidad medica'); ?></small>
                            </td>
                            <td><?php echo e($unit?->clues ?? $unit?->code ?? 'Sin CLUES'); ?></td>
                            <td><?php echo e($unitLocation ?: 'Sin ubicacion'); ?></td>
                            <td><?php echo e($contract->starts_at?->format('d/m/Y') ?? 'Sin inicio'); ?> - <?php echo e($contract->ends_at?->format('d/m/Y') ?? 'Sin vencimiento'); ?></td>
                            <td><?php echo e($contract->contract_number ?? 'Sin contrato'); ?></td>
                            <td><span class="institution-native-status"><?php echo e($statusText($contract->status)); ?></span></td>
                          </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                          <tr><td colspan="6">No hay hospitales habilitados para este servicio.</td></tr>
                        <?php endif; ?>
                      </tbody>
                    </table>
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
              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
          <?php else: ?>
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
            const serviceCarousel = document.querySelector('[data-service-carousel]');
            const serviceCreateDialog = document.querySelector('[data-service-create-dialog]');
            const serviceCarouselButtons = [...document.querySelectorAll('[data-service-carousel-button]')];
            const servicePanels = [...document.querySelectorAll('[data-service-panel]')];
            const showServicePanel = (id) => {
              serviceCarouselButtons.forEach((button) => button.classList.toggle('is-active', button.dataset.serviceCarouselButton === id));
              servicePanels.forEach((panel) => panel.hidden = panel.dataset.servicePanel !== id);
            };
            const showDetail = (id, mode) => {
              if (serviceSummary) serviceSummary.hidden = true;
              serviceCatalog.hidden = true;
              detailPanels.forEach((panel) => panel.hidden = !(panel.dataset.serviceDetailPanel === id && panel.dataset.serviceDetailMode === mode));
              window.scrollTo({ top: 0, behavior: 'smooth' });
            };
            serviceCarouselButtons.forEach((button) => button.addEventListener('click', () => showServicePanel(button.dataset.serviceCarouselButton)));
            document.querySelector('[data-service-create-open]')?.addEventListener('click', () => serviceCreateDialog?.showModal());
            document.querySelector('[data-service-create-close]')?.addEventListener('click', () => serviceCreateDialog?.close());
            serviceCreateDialog?.addEventListener('click', (event) => {
              if (event.target === serviceCreateDialog) serviceCreateDialog.close();
            });
            if (serviceCreateDialog?.dataset.openOnLoad === 'true') serviceCreateDialog.showModal();
            document.querySelector('[data-service-carousel-prev]')?.addEventListener('click', () => serviceCarousel?.scrollBy({ left: -280, behavior: 'smooth' }));
            document.querySelector('[data-service-carousel-next]')?.addEventListener('click', () => serviceCarousel?.scrollBy({ left: 280, behavior: 'smooth' }));
            document.querySelectorAll('[data-service-edit]').forEach((button) => button.addEventListener('click', () => showDetail(button.dataset.serviceEdit, 'edit')));
            document.querySelectorAll('[data-service-contract]').forEach((button) => button.addEventListener('click', () => showDetail(button.dataset.serviceContract, 'contract')));
            document.querySelectorAll('[data-service-detail-back]').forEach((button) => button.addEventListener('click', () => {
              detailPanels.forEach((panel) => panel.hidden = true);
              if (serviceSummary) serviceSummary.hidden = false;
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
        <?php
          $medicationCount = $catalogItems->count();
        ?>
        <section class="institution-native-table-card institution-native-table-card-wide institution-medication-card" data-medication-list-panel>
          <div class="institution-native-table-heading">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="m7 16 9-9a3 3 0 0 1 4 4l-9 9a3 3 0 0 1-4-4Z"/><path d="m12 11 4 4"/></svg>
            </span>
            <div>
              <h2>Medicamentos</h2>
              <p><?php echo e(number_format($medicationCount)); ?> <?php echo e($medicationCount === 1 ? 'medicamento' : 'medicamentos'); ?></p>
            </div>
          </div>
          <div class="institution-native-table-scroll">
            <table class="institution-native-table institution-medication-table">
              <thead>
                <tr>
                  <th>Medicamento</th>
                  <th>Clave</th>
                  <th>Categoria</th>
                  <th>Presentacion</th>
                  <th>Concentracion</th>
                  <th>Via de administracion</th>
                  <th>Estatus</th>
                  <th>Acciones</th>
                </tr>
              </thead>
              <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $catalogItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                  <?php
                    $itemGroup = data_get($item->metadata, 'group', $item->therapeutic_group ?? 'Grupo no clasificado');
                    $rawDescription = (string) ($item->description ?? '');
                    $itemDescription = $rawDescription !== '' ? $rawDescription : ($item->presentation ?? $item->generic_name ?? 'Sin descripcion');
                    $itemPresentation = $item->presentation ?? data_get($item->metadata, 'presentation');
                    if (!$itemPresentation && preg_match('/^[^,\d]+/', $itemDescription, $presentationMatches)) {
                      $itemPresentation = trim($presentationMatches[0]);
                    }
                    $itemConcentration = data_get($item->metadata, 'concentration') ?? data_get($item->metadata, 'dose');
                    if (!$itemConcentration && preg_match('/\b\d+(?:[.,]\d+)?\s*(?:mg|mcg|g|ml|ui|%|mg\/ml|mg\/dosis)\b/i', $itemDescription, $concentrationMatches)) {
                      $itemConcentration = $concentrationMatches[0];
                    }
                    $routeSource = strtolower($itemDescription.' '.($itemPresentation ?? ''));
                    $itemRoute = data_get($item->metadata, 'route') ?? data_get($item->metadata, 'administration_route');
                    if (!$itemRoute) {
                      $itemRoute = match (true) {
                        str_contains($routeSource, 'inhal') || str_contains($routeSource, 'aerosol') => 'Inhalatoria',
                        str_contains($routeSource, 'inyect') || str_contains($routeSource, 'intraven') || str_contains($routeSource, 'intramus') => 'Parenteral',
                        str_contains($routeSource, 'crema') || str_contains($routeSource, 'unguento') || str_contains($routeSource, 'gel') => 'Topica',
                        str_contains($routeSource, 'tableta') || str_contains($routeSource, 'capsula') || str_contains($routeSource, 'suspension') || str_contains($routeSource, 'jarabe') => 'Oral',
                        default => 'No especificada',
                      };
                    }
                    $mobileUnits = data_get($item->metadata, 'mobile_units', 'No');
                    $basicUnits = data_get($item->metadata, 'basic_units', 'Si');
                    $cessa = data_get($item->metadata, 'cessa', 'Si');
                    $availabilityValue = fn ($value) => match (strtolower((string) $value)) {
                      'si', 'sÃ­', '1', 'true' => 'yes',
                      'no', '0', 'false' => 'no',
                      default => 'undefined',
                    };
                  ?>
                  <tr data-medication-row>
                    <td><strong><?php echo e($item->name); ?></strong></td>
                    <td><?php echo e($item->cnis ?? 'Sin clave'); ?></td>
                    <td><?php echo e($itemGroup); ?></td>
                    <td><?php echo e($itemPresentation ?? 'Sin presentacion'); ?></td>
                    <td><?php echo e($itemConcentration ?? 'No especificada'); ?></td>
                    <td><?php echo e($itemRoute); ?></td>
                    <td><span class="institution-native-status"><?php echo e($statusText($item->status)); ?></span></td>
                    <td>
                      <div class="institution-native-actions institution-medication-actions">
                        <button type="button" class="institution-medication-edit"
                                    data-edit-medication
                                    data-update-url="<?php echo e(route('institution.medications.update', $item)); ?>"
                                    data-cnis="<?php echo e($item->cnis); ?>"
                                    data-group="<?php echo e($itemGroup); ?>"
                                    data-name="<?php echo e($item->name); ?>"
                                    data-description="<?php echo e($itemDescription); ?>"
                                    data-mobile-units="<?php echo e($availabilityValue($mobileUnits)); ?>"
                                    data-basic-units="<?php echo e($availabilityValue($basicUnits)); ?>"
                                    data-cessa="<?php echo e($availabilityValue($cessa)); ?>"
                                    data-status="<?php echo e($item->status); ?>">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="m16.5 3.5 4 4L8 20H4v-4Z"/></svg>
                          Editar
                        </button>
                        <button type="button" class="danger" aria-label="Eliminar medicamento <?php echo e($item->name); ?>">
                          <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                          Eliminar
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                  <tr><td colspan="8">No hay medicamentos institucionales registrados.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <footer class="institution-native-table-footer institution-native-table-footer-all-results">
            <span>Mostrando <?php echo e(number_format($medicationCount)); ?> de <?php echo e(number_format($medicationCount)); ?> <?php echo e($medicationCount === 1 ? 'medicamento' : 'medicamentos'); ?></span>
          </footer>
        </section>

        <section class="institution-native-table-card institution-native-table-card-wide institution-medication-form-card"
                 data-medication-form-panel hidden>
          <div class="institution-native-table-heading">
            <div>
              <h2 data-medication-form-title>Alta de medicamento</h2>
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
            const listTab = document.querySelector('[data-show-medication-list]');
            const formTabs = [...document.querySelectorAll('[data-show-medication-form]')];
            const activateTabs = (mode) => {
              listTab?.classList.toggle('is-active', mode === 'list');
              formTabs.forEach((button) => button.classList.toggle('is-active', mode === 'form'));
            };
            const showList = () => {
              formPanel.hidden = true;
              listPanel.hidden = false;
              activateTabs('list');
            };
            const showForm = () => {
              listPanel.hidden = true;
              formPanel.hidden = false;
              activateTabs('form');
              formPanel.querySelector('input, textarea, select')?.focus();
            };
            listTab?.addEventListener('click', showList);
            formTabs.forEach((button) => button.addEventListener('click', () => {
              medicationForm.reset();
              medicationForm.action = medicationForm.dataset.storeAction;
              methodField.disabled = true;
              modeField.value = 'create';
              formTitle.textContent = 'Alta de medicamento';
              showForm();
            }));
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
              showList();
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
        <section class="institution-native-table-card institution-native-table-card-wide institution-specialty-card" data-specialty-list-panel>
          <div class="institution-native-table-heading">
            <div>
              <h2>Especialidades institucionales</h2>
              <p>Las unidades debajo de esta institucion consultan este catalogo.</p>
            </div>
            <button type="button" class="institution-medication-new" data-show-specialty-form>Nueva Especialidad</button>
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

            <?php if($errors->any() && old('form_context') === 'specialty'): ?>
              showForm();
            <?php endif; ?>
          })();
        </script>
      <?php elseif($activeSection === 'create-unit'): ?>
        <?php
          $hasUnitFormValues = old('form_context') === 'unit';
          $unitEntityOptions = [
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua',
            'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México', 'Guanajuato', 'Guerrero',
            'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla',
            'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas',
            'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas',
          ];
          $unitMunicipalityOptions = $institution->medicalUnits
            ->map(fn ($unit) => $unit->municipality ?? $unit->city)
            ->filter()
            ->unique()
            ->sort()
            ->values();
        ?>
        <section class="institution-unit-create-card institution-unit-registration-card">
          <header class="institution-unit-registration-header">
            <span aria-hidden="true">
              <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6M9 13h6M9 17h6"/></svg>
            </span>
            <div><h2>Nueva unidad</h2><p>Completa la información de la unidad de salud</p></div>
          </header>
          <form method="post" action="<?php echo e(route('institution.units.store')); ?>" class="institution-unit-create-form institution-unit-registration-form" data-unit-create-form>
            <?php echo csrf_field(); ?>
            <input type="hidden" name="institution" value="<?php echo e($institution->id); ?>">
            <input type="hidden" name="form_context" value="unit">

            <section class="institution-unit-form-section" aria-labelledby="unit-general-title">
              <header class="institution-unit-form-section-header">
                <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg></span>
                <div><h3 id="unit-general-title">Datos generales</h3><p>Información básica de la unidad</p></div>
              </header>
              <div class="institution-unit-form-grid">
                <label>CLUES
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5v14M7 5v14M11 5v14M15 5v14M21 5v14M18 5v14"/></svg>
                    <input name="clues" value="<?php echo e($hasUnitFormValues ? old('clues') : ''); ?>" placeholder="Ingresa la CLUES" autocomplete="off" required>
                  </span>
                </label>
                <label>Nombre de la unidad
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M9 7h2M13 7h2M9 11h2M13 11h2M10 21v-5h4v5"/></svg>
                    <input name="name" value="<?php echo e($hasUnitFormValues ? old('name') : ''); ?>" placeholder="Nombre oficial de la unidad" autocomplete="organization" required>
                  </span>
                </label>
                <label>Entidad
                  <span class="institution-unit-field-control has-indicator">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 6 6-3 6 3 6-3v15l-6 3-6-3-6 3Z"/><path d="M9 3v15M15 6v15"/></svg>
                    <input name="entity" list="unit-entity-options" value="<?php echo e($hasUnitFormValues ? old('entity') : ''); ?>" placeholder="Selecciona una entidad" autocomplete="address-level1" required>
                    <svg class="institution-unit-field-indicator" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                  </span>
                </label>
                <label>Municipio
                  <span class="institution-unit-field-control has-indicator">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/></svg>
                    <input name="municipality" list="unit-municipality-options" value="<?php echo e($hasUnitFormValues ? old('municipality') : ''); ?>" placeholder="Selecciona un municipio" autocomplete="address-level2" required>
                    <svg class="institution-unit-field-indicator" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                  </span>
                </label>
                <label>Nivel de atención
                  <span class="institution-unit-field-control has-indicator">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                    <input name="care_level" list="unit-care-level-options" value="<?php echo e($hasUnitFormValues ? old('care_level') : ''); ?>" placeholder="Selecciona el nivel de atención" required>
                    <svg class="institution-unit-field-indicator" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                  </span>
                </label>
                <label>Tipología
                  <span class="institution-unit-field-control has-indicator">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 10h6M9 14h6M10 21v-3h4v3"/></svg>
                    <input name="typology" list="unit-typology-options" value="<?php echo e($hasUnitFormValues ? old('typology') : ''); ?>" placeholder="Selecciona la tipología" required>
                    <svg class="institution-unit-field-indicator" viewBox="0 0 24 24" aria-hidden="true"><path d="m7 10 5 5 5-5"/></svg>
                  </span>
                </label>
              </div>
            </section>

            <section class="institution-unit-form-section" aria-labelledby="unit-location-title">
              <header class="institution-unit-form-section-header">
                <span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/></svg></span>
                <div><h3 id="unit-location-title">Ubicación</h3><p>Datos de localización de la unidad</p></div>
              </header>
              <div class="institution-unit-form-grid">
                <label class="is-wide">Domicilio
                  <span class="institution-unit-field-control is-textarea">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 11 9-8 9 8"/><path d="M5 10v11h14V10M9 21v-6h6v6"/></svg>
                    <textarea name="address" rows="3" maxlength="500" placeholder="Ingresa el domicilio completo de la unidad" autocomplete="street-address" data-unit-address required><?php echo e($hasUnitFormValues ? old('address') : ''); ?></textarea>
                    <small><span data-unit-address-count>0</span>/500</small>
                  </span>
                </label>
                <label>Latitud
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
                    <input name="latitude" type="number" step="any" min="-90" max="90" value="<?php echo e($hasUnitFormValues ? old('latitude') : ''); ?>" placeholder="Ej. 19.432608">
                  </span>
                </label>
                <label>Longitud
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="8"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2"/></svg>
                    <input name="longitude" type="number" step="any" min="-180" max="180" value="<?php echo e($hasUnitFormValues ? old('longitude') : ''); ?>" placeholder="Ej. -99.133209">
                  </span>
                </label>
                <label>Partida
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                    <input name="partida" value="<?php echo e($hasUnitFormValues ? old('partida') : ''); ?>" placeholder="Ingresa la partida">
                  </span>
                </label>
                <label>Subpartida
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>
                    <input name="subpartida" value="<?php echo e($hasUnitFormValues ? old('subpartida') : ''); ?>" placeholder="Ingresa la subpartida">
                  </span>
                </label>
                <label>Camas
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 20v-8M22 20v-5a3 3 0 0 0-3-3H2v5h20M6 12V7h5a3 3 0 0 1 3 3v2"/></svg>
                    <input name="beds" type="number" min="0" max="100000" value="<?php echo e($hasUnitFormValues ? old('beds', 0) : 0); ?>" required>
                  </span>
                </label>
              </div>
            </section>

            <section class="institution-unit-form-section" aria-labelledby="unit-access-title">
              <header class="institution-unit-form-section-header">
                <span aria-hidden="true"><svg viewBox="0 0 24 24"><rect width="16" height="12" x="4" y="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3M12 14v2"/></svg></span>
                <div><h3 id="unit-access-title">Acceso al sistema</h3><p>Credenciales de acceso para la unidad</p></div>
              </header>
              <div class="institution-unit-form-grid">
                <label>Usuario de unidad
                  <span class="institution-unit-field-control">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>
                    <input name="unit_username" value="<?php echo e($hasUnitFormValues ? old('unit_username') : ''); ?>" placeholder="Ej. unidad.hospital" autocomplete="username" required>
                  </span>
                </label>
                <label>Contraseña de unidad
                  <span class="institution-unit-field-control has-action">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect width="16" height="12" x="4" y="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                    <input id="institution-unit-password" name="unit_password" type="password" minlength="6" maxlength="255" placeholder="Mínimo 6 caracteres" autocomplete="new-password" required>
                    <button type="button" data-unit-password-toggle aria-label="Mostrar contraseña" aria-controls="institution-unit-password" aria-pressed="false" title="Mostrar contraseña">
                      <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                  </span>
                </label>
              </div>
            </section>

            <datalist id="unit-entity-options"><?php $__currentLoopData = $unitEntityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($entity); ?>"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></datalist>
            <datalist id="unit-municipality-options"><?php $__currentLoopData = $unitMunicipalityOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $municipality): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($municipality); ?>"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></datalist>
            <datalist id="unit-care-level-options"><option value="Primer Nivel"><option value="Segundo Nivel"><option value="Tercer Nivel"><option value="Alta Especialidad"></datalist>
            <datalist id="unit-typology-options"><option value="Centro de Salud"><option value="Clínica"><option value="Hospital Comunitario"><option value="Hospital General"><option value="Hospital de Especialidades"><option value="Unidad de Medicina Familiar"></datalist>

            <footer class="institution-unit-form-actions">
              <a href="<?php echo e(route('institution.dashboard', ['institution' => $institution->id])); ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12M6 6l12 12"/></svg>Cancelar
              </a>
              <button type="submit">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                <span>Guardar unidad</span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
              </button>
            </footer>
          </form>
        </section>
        <script>
          (() => {
            const form = document.querySelector('[data-unit-create-form]');
            if (!form) return;

            const address = form.querySelector('[data-unit-address]');
            const addressCount = form.querySelector('[data-unit-address-count]');
            const syncAddressCount = () => {
              if (addressCount) addressCount.textContent = String(address?.value.length || 0);
            };
            address?.addEventListener('input', syncAddressCount);
            syncAddressCount();

            const password = form.querySelector('#institution-unit-password');
            const passwordToggle = form.querySelector('[data-unit-password-toggle]');
            passwordToggle?.addEventListener('click', event => {
              event.preventDefault();
              const shouldShow = password?.type === 'password';
              if (password) password.type = shouldShow ? 'text' : 'password';
              passwordToggle.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
              passwordToggle.setAttribute('aria-label', shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
              passwordToggle.setAttribute('title', shouldShow ? 'Ocultar contraseña' : 'Mostrar contraseña');
              password?.focus({ preventScroll: true });
            });
          })();
        </script>

      <?php else: ?>
        <section class="institution-unit-create-card institution-service-create-card">
          <header><h2>Nuevo servicio</h2></header>
          <?php echo $__env->make('institution._service_create_form', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </section>

      <?php endif; ?>

    </section>
  </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', ['title' => 'Institucion'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\dr-sam\resources\views\institution\dashboard.blade.php ENDPATH**/ ?>