(() => {
  'use strict';
  const search = document.querySelector('[data-patient-search]');
  const table = document.querySelector('[data-patient-catalog-table]');
  if (search && table) {
    const rows = [...table.querySelectorAll('[data-patient-row]')];
    const apply = () => {
      const term = search.value.trim().toLocaleLowerCase('es');
      rows.forEach(row => { row.hidden = !row.textContent.toLocaleLowerCase('es').includes(term); });
      const visible = rows.filter(row => !row.hidden && !row.classList.contains('drsam-table-filter-hidden')).length;
      table.querySelector('[data-patient-empty]').hidden = visible > 0 || rows.length === 0;
      const counter = document.querySelector('[data-patient-count]');
      if (counter) counter.textContent = `${visible} pacientes`;
    };
    search.addEventListener('input', apply);
    table.addEventListener('drsam:filtered', apply);
  }
  const editor = document.querySelector('[data-patient-editor].is-open');
  if (!editor) return;
  editor.querySelector('input:not([type="hidden"])')?.focus();
  editor.addEventListener('keydown', event => {
    if (event.key === 'Escape') editor.querySelector('[data-patient-editor-close]').click();
    if (event.key !== 'Tab') return;
    const controls = [...editor.querySelectorAll('a, button, input:not([type="hidden"]), select')].filter(el => el.getClientRects().length && !el.disabled);
    const first = controls[0], last = controls[controls.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
  });
})();
