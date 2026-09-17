<?php
  $activeMedicationCount = $medicationCatalog->filter(
    fn ($item) => $item->status === 'active' && ($item->unitSettings->first()?->is_active ?? true)
  )->count();
  $medicationColumns = [
    'status' => 'Activo',
    'product' => 'Producto',
    'presentation' => 'Presentación',
    'group' => 'Categoría',
    'condition' => 'Condición de entrega',
    'stock' => 'Existencia',
    'actions' => 'Acciones',
  ];
?>
<div class="unit-catalog-screen unit-medication-screen" data-unit-catalog-screen data-medication-catalog>
  <section class="unit-service-panel-header unit-catalog-info-card" data-unit-catalog-info>
    <div class="unit-service-panel-copy">
      <h2>Medicamentos</h2>
      <p>Catálogo institucional de <?php echo e($unit->institution?->name ?? 'la unidad'); ?></p>
      <?php if($medicationCatalog->contains(fn ($item) => data_get($item->metadata, 'source_label') === 'Listado de Insumos Esenciales, IMSS Bienestar 2026')): ?>
        <small>Incluye el <a href="https://pgsp.imssbienestar.gob.mx/rutas_de_la_salud/docs/App_RdlS_v5.1_Meds_MatCur.pdf" target="_blank" rel="noopener noreferrer">listado esencial 2026 de primer nivel</a>; la activación no indica existencia ni autorización clínica.</small>
      <?php endif; ?>
      <div class="unit-service-panel-badges">
        <span class="unit-native-status">Activo</span>
        <span><strong data-medication-visible><?php echo e($medicationCatalog->count()); ?></strong> de <?php echo e($medicationCatalog->count()); ?> medicamentos visibles</span>
      </div>
    </div>
  </section>

  <div class="unit-catalog-toolbar" data-unit-catalog-controls>
    <div class="unit-nutrition-request-tabs" role="tablist" aria-label="Filtrar medicamentos">
      <button type="button" class="is-active" data-unit-catalog-status="all" role="tab" aria-selected="true">Todos</button>
      <button type="button" data-unit-catalog-status="active" role="tab" aria-selected="false">Activos <span><?php echo e($activeMedicationCount); ?></span></button>
      <button type="button" data-unit-catalog-status="inactive" role="tab" aria-selected="false">Inactivos <span><?php echo e($medicationCatalog->count() - $activeMedicationCount); ?></span></button>
    </div>
    <div class="unit-catalog-toolbar-actions">
      <label class="unit-catalog-search">
        <span class="sr-only">Buscar medicamento</span>
        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
        <input type="search" placeholder="Buscar medicamento o clave" data-medication-search>
      </label>
      <button type="button" class="unit-catalog-secondary-action" data-medication-export>Descargar catálogo</button>
    </div>
  </div>

  <section class="unit-native-table-card unit-catalog-content-card unit-medication-card" data-unit-catalog-content>
    <div class="unit-native-table-scroll">
      <table class="unit-native-table unit-medication-catalog-table" data-medication-table>
        <thead>
          <tr>
            <?php $__currentLoopData = $medicationColumns; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $columnKey => $columnLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
              <th scope="col">
                <span class="unit-medication-column-heading">
                  <span><?php echo e($columnLabel); ?></span>
                  <button type="button" class="unit-medication-filter-trigger"
                          data-medication-filter-trigger="<?php echo e($columnKey); ?>"
                          aria-label="Buscar y filtrar <?php echo e($columnLabel); ?>"
                          aria-expanded="false"
                          title="Buscar y filtrar <?php echo e($columnLabel); ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M7 12h10M10 17h4"/></svg>
                  </button>
                </span>
              </th>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
          </tr>
        </thead>
        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $medicationCatalog; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $isActive = $item->status === 'active' && ($item->unitSettings->first()?->is_active ?? true);
              $condition = data_get($item->metadata, 'dispensing_condition')
                  ?? ($item->controlled ? 'Controlado' : ($item->requires_prescription ? 'Requiere receta' : 'No especificada'));
              $stock = $medicationStock->get($item->cnis);
              $package = null;
              if (preg_match('/\bENVASE (?:CON|PARA)\s+[^.]+/iu', (string) $item->description, $packageMatch)) {
                $package = trim($packageMatch[0]);
              }
            ?>
            <tr data-medication-row data-status="<?php echo e($isActive ? 'active' : 'inactive'); ?>">
              <td data-medication-cell="status" data-medication-value="<?php echo e($isActive ? 'Activo' : 'Inactivo'); ?>">
                <?php if($item->status === 'active'): ?>
                  <form method="post" action="<?php echo e(route('unit.medications.status', ['medication' => $item, 'unit' => $unit->id])); ?>" data-medication-toggle>
                    <?php echo csrf_field(); ?> <?php echo method_field('patch'); ?>
                    <input type="hidden" name="is_active" value="<?php echo e($isActive ? '0' : '1'); ?>">
                    <button type="submit" class="<?php echo \Illuminate\Support\Arr::toCssClasses(['unit-medication-switch', 'is-on' => $isActive]); ?>"
                            role="switch" aria-checked="<?php echo e($isActive ? 'true' : 'false'); ?>"
                            aria-label="<?php echo e($isActive ? 'Desactivar' : 'Activar'); ?> <?php echo e($item->name); ?> en esta unidad"
                            title="<?php echo e($isActive ? 'Desactivar' : 'Activar'); ?> en esta unidad">
                      <span aria-hidden="true"></span>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="unit-medication-switch is-disabled" title="Inactivo en la institución"><span aria-hidden="true"></span></span>
                <?php endif; ?>
              </td>
              <td data-medication-cell="product" data-medication-value="<?php echo e($item->name); ?>" data-medication-search-value="<?php echo e($item->name); ?> <?php echo e($item->cnis); ?>">
                <strong><?php echo e($item->name); ?></strong><small><?php echo e($item->cnis ?? 'Sin clave'); ?></small>
              </td>
              <td data-medication-cell="presentation" data-medication-value="<?php echo e($item->presentation ?? 'No especificada'); ?>">
                <?php echo e($item->presentation ?? 'No especificada'); ?>

                <?php if($package): ?><small><?php echo e($package); ?></small><?php endif; ?>
              </td>
              <td data-medication-cell="group" data-medication-value="<?php echo e($item->therapeutic_group ?? 'Sin grupo'); ?>"><?php echo e($item->therapeutic_group ?? 'Sin grupo'); ?></td>
              <td data-medication-cell="condition" data-medication-value="<?php echo e($condition); ?>"><?php echo e($condition); ?></td>
              <td data-medication-cell="stock" data-medication-value="<?php echo e($stock === null ? 'Sin registro' : $stock); ?>">
                <?php echo e($stock === null ? 'Sin registro' : number_format((int) $stock)); ?>

              </td>
              <td data-medication-cell="actions" data-medication-value="<?php echo e($item->status === 'active' ? 'Disponible' : 'Bloqueado'); ?>">
                <button type="button" class="unit-medication-view" data-medication-view
                        data-name="<?php echo e($item->name); ?>"
                        data-cnis="<?php echo e($item->cnis); ?>"
                        data-group="<?php echo e($item->therapeutic_group); ?>"
                        data-description="<?php echo e($item->description); ?>"
                        data-source="<?php echo e(data_get($item->metadata, 'source_label', 'Registro institucional')); ?>"
                        aria-label="Ver <?php echo e($item->name); ?>" title="Ver medicamento">
                  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </td>
            </tr>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr data-medication-no-data><td colspan="7" class="unit-native-empty">No hay medicamentos disponibles para esta unidad.</td></tr>
          <?php endif; ?>
          <tr data-medication-empty hidden><td colspan="7" class="unit-native-empty">No se encontraron medicamentos.</td></tr>
        </tbody>
      </table>
    </div>
  </section>
  <div class="unit-medication-feedback" data-medication-feedback role="alert" hidden></div>
</div>

<div class="unit-medication-filter-popover" data-medication-filter-popover role="dialog" aria-modal="false" aria-label="Filtrar columna" hidden>
  <label class="sr-only" for="medication-column-search">Buscar valor</label>
  <input id="medication-column-search" type="search" placeholder="Buscar (Todos)" data-medication-option-search>
  <label class="unit-medication-filter-all"><input type="checkbox" data-medication-select-all checked> (Todos)</label>
  <div class="unit-medication-filter-options" data-medication-filter-options></div>
  <div class="unit-medication-filter-actions">
    <button type="button" data-medication-filter-apply>Aceptar</button>
    <button type="button" data-medication-filter-cancel>Cancelar</button>
  </div>
</div>

<dialog class="unit-medication-detail-dialog" data-medication-detail>
  <header><div><h2 data-medication-detail-name></h2><small data-medication-detail-cnis></small></div><button type="button" data-medication-detail-close aria-label="Cerrar">×</button></header>
  <dl>
    <div><dt>Grupo terapéutico</dt><dd data-medication-detail-group></dd></div>
    <div><dt>Descripción</dt><dd data-medication-detail-description></dd></div>
    <div><dt>Fuente</dt><dd data-medication-detail-source></dd></div>
  </dl>
  <footer><button type="button" data-medication-detail-close>Cerrar</button></footer>
</dialog>

<script>
  (() => {
    const root = document.querySelector('[data-medication-catalog]');
    if (!root) return;
    const rows = [...root.querySelectorAll('[data-medication-row]')];
    const statusButtons = [...root.querySelectorAll('[data-unit-catalog-status]')];
    const search = root.querySelector('[data-medication-search]');
    const triggers = [...root.querySelectorAll('[data-medication-filter-trigger]')];
    const popover = document.querySelector('[data-medication-filter-popover]');
    document.body.appendChild(popover);
    const optionSearch = popover.querySelector('[data-medication-option-search]');
    const optionList = popover.querySelector('[data-medication-filter-options]');
    const selectAll = popover.querySelector('[data-medication-select-all]');
    const detail = document.querySelector('[data-medication-detail]');
    const feedback = root.querySelector('[data-medication-feedback]');
    const selected = new Map();
    let activeStatus = 'all';
    let activeColumn = null;
    let draft = new Set();
    let filteredRows = rows;
    let feedbackTimer;

    const cell = (row, column) => row.querySelector('[data-medication-cell="' + column + '"]');
    const value = (row, column) => cell(row, column)?.dataset.medicationValue || '';
    const allValues = (column) => [...new Set(rows.map((row) => value(row, column)))].sort((a, b) => a.localeCompare(b, 'es'));
    const closeFilter = () => {
      popover.hidden = true;
      triggers.forEach((trigger) => trigger.setAttribute('aria-expanded', 'false'));
      activeColumn = null;
    };
    const positionFilter = (trigger) => {
      const rect = trigger.getBoundingClientRect();
      const viewportWidth = Math.min(document.documentElement.clientWidth, window.visualViewport?.width ?? window.innerWidth);
      const viewportHeight = Math.min(document.documentElement.clientHeight, window.visualViewport?.height ?? window.innerHeight);
      popover.style.width = Math.min(340, viewportWidth - 24) + 'px';
      popover.style.maxHeight = Math.min(420, viewportHeight - 24) + 'px';
      const { width, height } = popover.getBoundingClientRect();
      const left = Math.max(12, Math.min(rect.right - width, viewportWidth - width - 12));
      const below = rect.bottom + 7;
      const above = rect.top - height - 7;
      const top = below + height <= viewportHeight - 12 ? below :
        above >= 12 ? above : Math.max(12, viewportHeight - height - 12);
      popover.style.left = left + 'px';
      popover.style.top = top + 'px';
    };
    const renderOptions = () => {
      optionList.replaceChildren();
      const query = optionSearch.value.trim().toLocaleLowerCase('es');
      const values = allValues(activeColumn);
      values.forEach((item) => {
        const searchable = rows.filter((row) => value(row, activeColumn) === item)
          .map((row) => cell(row, activeColumn)?.dataset.medicationSearchValue || item).join(' ');
        if (query && !searchable.toLocaleLowerCase('es').includes(query)) return;
        const label = document.createElement('label');
        const checkbox = document.createElement('input');
        const span = document.createElement('span');
        checkbox.type = 'checkbox';
        checkbox.checked = draft.has(item);
        checkbox.addEventListener('change', () => {
          if (checkbox.checked) draft.add(item);
          else draft.delete(item);
          selectAll.checked = draft.size === values.length;
        });
        span.textContent = item;
        label.append(checkbox, span);
        optionList.append(label);
      });
      selectAll.checked = draft.size === values.length;
      selectAll.indeterminate = draft.size > 0 && draft.size < values.length;
    };
    const openFilter = (trigger) => {
      const column = trigger.dataset.medicationFilterTrigger;
      if (activeColumn === column) {
        closeFilter();
        return;
      }
      activeColumn = column;
      draft = new Set(selected.get(column) ?? allValues(column));
      optionSearch.value = '';
      popover.hidden = false;
      triggers.forEach((button) => button.setAttribute('aria-expanded', button === trigger ? 'true' : 'false'));
      renderOptions();
      positionFilter(trigger);
      optionSearch.focus();
    };
    const renderRows = () => {
      const visibleRows = new Set(filteredRows);
      rows.forEach((row) => { row.hidden = !visibleRows.has(row); });
      root.querySelector('[data-medication-visible]').textContent = filteredRows.length;
      root.querySelector('[data-medication-empty]').hidden = filteredRows.length !== 0 || rows.length === 0;
    };
    const applyFilters = () => {
      const query = search.value.trim().toLocaleLowerCase('es');
      filteredRows = rows.filter((row) => {
        if (activeStatus !== 'all' && row.dataset.status !== activeStatus) return false;
        if (query && !row.innerText.toLocaleLowerCase('es').includes(query)) return false;
        return [...selected].every(([column, values]) => values === null || values.has(value(row, column)));
      });
      renderRows();
    };
    const showError = (message) => {
      clearTimeout(feedbackTimer);
      feedback.textContent = message;
      feedback.hidden = false;
      feedbackTimer = setTimeout(() => { feedback.hidden = true; }, 4000);
    };
    const updateCounts = () => {
      const active = rows.filter((row) => row.dataset.status === 'active').length;
      statusButtons.find((button) => button.dataset.unitCatalogStatus === 'active')?.querySelector('span').replaceChildren(String(active));
      statusButtons.find((button) => button.dataset.unitCatalogStatus === 'inactive')?.querySelector('span').replaceChildren(String(rows.length - active));
    };

    root.querySelectorAll('[data-medication-toggle]').forEach((form) => form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const button = form.querySelector('button[role="switch"]');
      const row = form.closest('[data-medication-row]');
      const input = form.querySelector('[name="is_active"]');
      const scrollY = window.scrollY;
      const tableScrollX = root.querySelector('.unit-native-table-scroll')?.scrollLeft ?? 0;
      button.disabled = true;
      try {
        const response = await fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
          credentials: 'same-origin',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = response.headers.get('content-type')?.includes('application/json')
          ? await response.json() : null;
        if (!response.ok || typeof payload?.is_active !== 'boolean') {
          throw new Error(payload?.message || 'No se pudo actualizar el medicamento.');
        }
        const active = payload.is_active;
        const name = value(row, 'product');
        row.dataset.status = active ? 'active' : 'inactive';
        cell(row, 'status').dataset.medicationValue = active ? 'Activo' : 'Inactivo';
        button.classList.toggle('is-on', active);
        button.setAttribute('aria-checked', String(active));
        button.setAttribute('aria-label', (active ? 'Desactivar ' : 'Activar ') + name + ' en esta unidad');
        button.title = (active ? 'Desactivar' : 'Activar') + ' en esta unidad';
        input.value = active ? '0' : '1';
        updateCounts();
        applyFilters();
        root.querySelector('.unit-native-table-scroll').scrollLeft = tableScrollX;
        requestAnimationFrame(() => window.scrollTo(0, scrollY));
      } catch (error) {
        showError(error.message || 'No se pudo actualizar el medicamento.');
      } finally {
        button.disabled = false;
      }
    }));

    triggers.forEach((trigger) => trigger.addEventListener('click', () => openFilter(trigger)));
    optionSearch.addEventListener('input', renderOptions);
    selectAll.addEventListener('change', () => {
      draft = selectAll.checked ? new Set(allValues(activeColumn)) : new Set();
      renderOptions();
    });
    popover.querySelector('[data-medication-filter-apply]').addEventListener('click', () => {
      const all = allValues(activeColumn);
      selected.set(activeColumn, draft.size === all.length ? null : new Set(draft));
      triggers.find((trigger) => trigger.dataset.medicationFilterTrigger === activeColumn)
        ?.classList.toggle('has-filter', draft.size !== all.length);
      closeFilter();
      applyFilters();
    });
    popover.querySelector('[data-medication-filter-cancel]').addEventListener('click', closeFilter);
    document.addEventListener('click', (event) => {
      if (!popover.hidden && !popover.contains(event.target) && !event.target.closest('[data-medication-filter-trigger]')) closeFilter();
    });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') closeFilter(); });
    window.addEventListener('resize', closeFilter);
    window.visualViewport?.addEventListener('resize', closeFilter);
    window.addEventListener('scroll', closeFilter, { passive: true });
    root.querySelector('.unit-native-table-scroll')?.addEventListener('scroll', closeFilter);
    statusButtons.forEach((button) => button.addEventListener('click', () => {
      activeStatus = button.dataset.unitCatalogStatus;
      statusButtons.forEach((candidate) => {
        const active = candidate === button;
        candidate.classList.toggle('is-active', active);
        candidate.setAttribute('aria-selected', active ? 'true' : 'false');
      });
      applyFilters();
    }));
    search.addEventListener('input', applyFilters);
    root.querySelector('[data-medication-export]').addEventListener('click', () => {
      const columns = ['status', 'product', 'presentation', 'group', 'condition', 'stock'];
      const lines = [
        ['Estatus', 'Producto', 'Presentación', 'Categoría', 'Condición de entrega', 'Existencia'],
        ...filteredRows.map((row) => columns.map((column) => value(row, column))),
      ];
      const csv = lines.map((line) => line.map((item) => '"' + item.replaceAll('"', '""') + '"').join(',')).join('\n');
      const link = document.createElement('a');
      link.href = URL.createObjectURL(new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8' }));
      link.download = 'catalogo-medicamentos-unidad.csv';
      link.click();
      URL.revokeObjectURL(link.href);
    });
    root.querySelectorAll('[data-medication-view]').forEach((button) => button.addEventListener('click', () => {
      for (const field of ['name', 'cnis', 'group', 'description', 'source']) {
        detail.querySelector('[data-medication-detail-' + field + ']').textContent = button.dataset[field] || 'No especificado';
      }
      detail.showModal();
    }));
    detail.querySelectorAll('[data-medication-detail-close]').forEach((button) => button.addEventListener('click', () => detail.close()));
    detail.addEventListener('click', (event) => { if (event.target === detail) detail.close(); });
    updateCounts();
    applyFilters();
  })();
</script>
<?php /**PATH C:\laragon\www\dr-sam\resources\views/unit/_medications_catalog.blade.php ENDPATH**/ ?>