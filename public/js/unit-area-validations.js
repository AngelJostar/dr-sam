(() => {
  'use strict';
  const config = window.unitAreaValidationConfig;
  const root = document.querySelector('[data-area-validations]');
  if (!config || !root) return;
  let selectedArea = config.selectedArea;
  const editor = document.querySelector('[data-validation-editor]');
  const history = document.querySelector('[data-validation-history-dialog]');
  const form = editor.querySelector('form');
  const field = name => form.elements.namedItem(name);
  const search = root.querySelector('[data-validation-search]');
  const state = root.querySelector('[data-validation-state]');
  const applyFilters = () => {
    const query = search.value.trim().toLocaleLowerCase('es');
    let count = 0;
    root.querySelectorAll('[data-validation-area]').forEach(row => {
      row.hidden = (selectedArea !== 'all' && row.dataset.validationArea !== selectedArea)
        || !row.cells[0].textContent.toLocaleLowerCase('es').includes(query)
        || (state.value !== 'all' && !row.dataset.validationStates.split(' ').includes(state.value));
      if (!row.hidden) count++;
      if (row.hidden) {
        root.querySelector(`[data-validation-details="${row.dataset.validationArea}"]`)?.setAttribute('hidden', '');
        row.querySelector('[data-validation-expand]')?.setAttribute('aria-expanded', 'false');
      }
    });
    root.querySelector('[data-validation-empty]').hidden = count !== 0;
    root.querySelector('[data-validation-selection]').textContent = config.areas[selectedArea] || 'Todas las áreas';
  };
  document.addEventListener('unit-area-selected', event => { selectedArea = event.detail; applyFilters(); });
  search.addEventListener('input', applyFilters);
  state.addEventListener('change', applyFilters);
  const syncFields = () => {
    for (const scope of ['service', 'product']) {
      const selected = field('scope').value === scope;
      const container = form.querySelector(`[data-validation-${scope}]`);
      container.hidden = !selected;
      container.querySelector('select').disabled = !selected;
      container.querySelector('select').required = selected;
    }
    form.querySelectorAll('input[type="checkbox"]').forEach(input => {
      input.disabled = input.value === field('requester_area').value;
      if (input.disabled) input.checked = false;
    });
  };
  const openEditor = (rule = {}, area = '') => {
    form.reset();
    const id = rule.id || rule._validation_rule || '';
    form.action = config.updateUrls[id] || config.storeUrl;
    field('_method').disabled = !id;
    field('_validation_rule').value = id;
    field('version').value = rule.version || 1;
    for (const name of ['requester_area', 'scope', 'service_id', 'product_id', 'status', 'notes']) {
      field(name).value = rule[name] ?? ({requester_area: area, scope: 'all', status: 'draft'}[name] || '');
    }
    for (const name of ['review_areas', 'authorize_areas']) {
      form.querySelectorAll(`input[name="${name}[]"]`).forEach(input => { input.checked = (rule[name] || []).includes(input.value); });
    }
    editor.querySelector('h2').textContent = id ? 'Editar regla de validación' : 'Nueva regla de validación';
    syncFields();
    editor.showModal();
  };
  root.querySelectorAll('[data-validation-new]').forEach(button => button.addEventListener('click', () => openEditor({}, button.dataset.validationNew || (config.areas[selectedArea] ? selectedArea : ''))));
  root.querySelectorAll('[data-validation-edit]').forEach(button => button.addEventListener('click', () => openEditor(config.rules[button.dataset.validationEdit])));
  root.querySelectorAll('[data-validation-expand]').forEach(button => button.addEventListener('click', () => {
    const row = root.querySelector(`[data-validation-details="${button.dataset.validationExpand}"]`);
    row.hidden = !row.hidden;
    button.setAttribute('aria-expanded', String(!row.hidden));
  }));
  field('scope').addEventListener('change', syncFields);
  field('requester_area').addEventListener('change', syncFields);
  root.querySelector('[data-validation-history]').addEventListener('click', () => history.showModal());
  for (const dialog of [editor, history]) dialog.querySelectorAll('[data-validation-close]').forEach(button => button.addEventListener('click', () => dialog.close()));
  applyFilters();
  if (config.old) openEditor(config.old);
})();
